<?php
require_once __DIR__ . '/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return is_logged_in() && (current_user()['role'] ?? '') === 'Administrator';
}

function is_customer(): bool
{
    return is_logged_in() && (current_user()['role'] ?? '') === 'Customer';
}

function login_home(): string
{
    return is_customer() ? 'customer_portal/index.php' : 'index.php';
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
    if (is_customer()) {
        redirect('customer_portal/index.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        set_flash('danger', 'Administrator access is required for that page.');
        redirect(login_home());
    }
}

function require_customer(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
    if (!is_customer()) {
        set_flash('danger', 'This page is for customer accounts.');
        redirect('index.php');
    }
}

function attempt_login(string $username, string $password): bool
{
    $user = one_row('SELECT user_id, customer_id, full_name, username, password_hash, role FROM users WHERE username = :username LIMIT 1', [
        ':username' => $username,
    ]);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
