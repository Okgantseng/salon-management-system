<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_redirect('login.php');
    $username = posted('username');
    $password = (string) ($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Enter both your username and password.';
    } elseif (!attempt_login($username, $password)) {
        $error = 'The username or password is incorrect.';
    } else {
        set_flash('success', 'Welcome back.');
        redirect('index.php');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrator Login | GlowHub Salon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="login-page">
<main class="login-card card shadow-lg border-0"><div class="card-body p-4 p-md-5">
    <div class="text-center mb-4"><div class="login-logo">G</div><h1 class="h3 fw-bold mt-3">GlowHub Salon</h1><p class="text-secondary mb-0">Administrator sign in</p></div>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" novalidate><?= csrf_input() ?>
        <div class="mb-3"><label class="form-label" for="username">Username</label><input class="form-control" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus></div>
        <div class="mb-4"><label class="form-label" for="password">Password</label><input class="form-control" id="password" name="password" type="password" required></div>
        <button class="btn btn-primary w-100" type="submit">Sign in</button>
    </form>
    <div class="alert alert-light border small mt-4 mb-0"><strong>Demo login:</strong> admin / Admin@123</div>
</div></main>
</body></html>
