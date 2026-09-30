<?php
require_once __DIR__ . '/../config/database.php';

function app_url(string $path = ''): string
{
    return rtrim(APP_BASE_URL, '/') . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function display_flashes(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    foreach ($_SESSION['flash'] as $flash) {
        $type = in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info';
        echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show" role="alert">'
            . e($flash['message'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    unset($_SESSION['flash']);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token']);
}

function require_csrf_or_redirect(string $path): void
{
    if (!verify_csrf()) {
        set_flash('danger', 'Your form session expired. Please try again.');
        redirect($path);
    }
}

function posted(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function get_id(string $key = 'id'): int
{
    return filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT) ?: 0;
}

function post_id(string $key = 'id'): int
{
    return filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT) ?: 0;
}

function valid_email_or_blank(string $email): bool
{
    return $email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function valid_phone(string $phone): bool
{
    return (bool) preg_match('/^(?:\\+27|0)[1-9][0-9]{8}$/', preg_replace('/[\s-]+/', '', $phone));
}

function is_valid_date(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date;
}

function is_valid_time(string $time): bool
{
    $parsed = DateTime::createFromFormat('H:i', $time) ?: DateTime::createFromFormat('H:i:s', $time);
    return $parsed !== false;
}

function money(float|string|int|null $amount): string
{
    return 'R ' . number_format((float) $amount, 2);
}

function scalar(string $sql, array $params = []): mixed
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchColumn();
}

function all_rows(string $sql, array $params = []): array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function one_row(string $sql, array $params = []): ?array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $row = $statement->fetch();
    return $row ?: null;
}

function database_message(PDOException $exception, string $fallback): string
{
    $sqlState = $exception->errorInfo[0] ?? '';
    if ($sqlState === '23000') {
        return 'This record conflicts with existing data or is used by another record.';
    }
    return $fallback;
}

/** Mark unattended, past Scheduled appointments as No-show. */
function update_overdue_appointments(): void
{
    $statement = db()->prepare(
        "UPDATE appointments
         SET status = 'No-show'
         WHERE status = 'Scheduled'
           AND TIMESTAMP(appointment_date, appointment_time) < NOW()"
    );
    $statement->execute();
}

function status_badge(string $status): string
{
    $classes = [
        'Scheduled' => 'primary', 'Completed' => 'success', 'Cancelled' => 'danger',
        'No-show' => 'dark', 'Paid' => 'success', 'Partially Paid' => 'warning',
        'Unpaid' => 'danger', 'Available' => 'success', 'Unavailable' => 'warning',
        'Inactive' => 'secondary', 'Active' => 'success',
    ];
    return '<span class="badge text-bg-' . ($classes[$status] ?? 'secondary') . '">' . e($status) . '</span>';
}

function select_options(array $rows, string $valueKey, string $labelKey, int|string|null $selected = null): string
{
    $html = '';
    foreach ($rows as $row) {
        $value = (string) $row[$valueKey];
        $isSelected = (string) $selected === $value ? ' selected' : '';
        $html .= '<option value="' . e($value) . '"' . $isSelected . '>' . e($row[$labelKey]) . '</option>';
    }
    return $html;
}
