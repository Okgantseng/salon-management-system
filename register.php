<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) redirect(login_home());

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_redirect('register.php');
    $firstName = posted('first_name'); $lastName = posted('last_name'); $phone = posted('phone');
    $email = posted('email'); $username = posted('username'); $password = (string) ($_POST['password'] ?? '');
    $gender = posted('gender'); $errors = [];
    if (!customer_accounts_enabled()) $errors[] = customer_schema_message();
    if ($firstName === '' || $lastName === '') $errors[] = 'Please enter your first and last name.';
    if (!valid_phone($phone)) $errors[] = 'Please enter a valid South African phone number.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_.-]{3,49}$/', $username)) $errors[] = 'Username must be 4–50 characters and use letters, numbers, dots, hyphens or underscores.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (!in_array($gender, ['Female', 'Male', 'Other'], true)) $errors[] = 'Please select a gender.';
    if (!$errors && scalar('SELECT COUNT(*) FROM users WHERE username = ?', [$username])) $errors[] = 'That username is already in use.';
    if (!$errors && scalar('SELECT COUNT(*) FROM customers WHERE email = ?', [$email])) $errors[] = 'That email address already has an account.';
    if (!$errors && scalar('SELECT COUNT(*) FROM customers WHERE phone = ?', [$phone])) $errors[] = 'That phone number is already registered.';
    if ($errors) $error = implode(' ', $errors);
    else {
        try {
            db()->beginTransaction();
            $customer = db()->prepare('INSERT INTO customers (first_name, last_name, phone, email, gender, date_registered) VALUES (?, ?, ?, ?, ?, CURDATE())');
            $customer->execute([$firstName, $lastName, $phone, $email, $gender]);
            $customerId = (int) db()->lastInsertId();
            $user = db()->prepare('INSERT INTO users (customer_id, full_name, username, password_hash, role) VALUES (?, ?, ?, ?, \'Customer\')');
            $user->execute([$customerId, $firstName . ' ' . $lastName, $username, password_hash($password, PASSWORD_DEFAULT)]);
            db()->commit();
            set_flash('success', 'Account created successfully. Welcome to GlowHub.');
            redirect('login.php');
        } catch (PDOException $exception) {
            if (db()->inTransaction()) db()->rollBack();
            $error = 'We could not create your account. Please check your details and try again.';
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Create Customer Account | GlowHub Salon</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"><link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet"></head>
<body class="login-page"><main class="login-card card shadow-lg border-0"><div class="card-body p-4 p-md-5"><div class="text-center mb-4"><div class="login-logo">G</div><h1 class="h3 fw-bold mt-3">Create your GlowHub account</h1><p class="text-secondary mb-0">Book and manage your salon visits online.</p></div><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post" novalidate><?= csrf_input() ?><div class="row"><div class="col-md-6 mb-3"><label class="form-label">First name</label><input class="form-control" name="first_name" value="<?= e($_POST['first_name'] ?? '') ?>" required></div><div class="col-md-6 mb-3"><label class="form-label">Last name</label><input class="form-control" name="last_name" value="<?= e($_POST['last_name'] ?? '') ?>" required></div><div class="col-md-6 mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" placeholder="0823456710" value="<?= e($_POST['phone'] ?? '') ?>" required></div><div class="col-md-6 mb-3"><label class="form-label">Gender</label><select class="form-select" name="gender" required><option value="">Choose…</option><?php foreach(['Female','Male','Other'] as $option): ?><option <?= ($_POST['gender'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div><div class="col-12 mb-3"><label class="form-label">Email address</label><input class="form-control" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></div><div class="col-md-6 mb-3"><label class="form-label">Username</label><input class="form-control" name="username" value="<?= e($_POST['username'] ?? '') ?>" required></div><div class="col-md-6 mb-4"><label class="form-label">Password</label><input class="form-control" type="password" name="password" minlength="8" required></div></div><button class="btn btn-primary w-100">Create account</button></form><p class="text-center small text-secondary mt-4 mb-0">Already have an account? <a href="<?= e(app_url('login.php')) ?>">Sign in</a></p></div></main></body></html>
