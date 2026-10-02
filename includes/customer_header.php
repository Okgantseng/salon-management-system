<?php
require_once __DIR__ . '/auth.php';
require_customer();
$page_title = $page_title ?? 'Customer Portal';
$customer = one_row('SELECT * FROM customers WHERE customer_id = ?', [(int) current_user()['customer_id']]);
if (!$customer) {
    logout_user();
    redirect('login.php');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | GlowHub Salon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="customer-shell">
    <header class="customer-nav">
        <a class="brand" href="<?= e(app_url('customer_portal/index.php')) ?>"><span class="brand-mark">G</span><span>GlowHub</span></a>
        <nav class="customer-links">
            <a class="customer-link <?= ($customer_active ?? '') === 'home' ? 'active' : '' ?>" href="<?= e(app_url('customer_portal/index.php')) ?>"><i class="bi bi-house-heart"></i> My portal</a>
            <a class="customer-link <?= ($customer_active ?? '') === 'book' ? 'active' : '' ?>" href="<?= e(app_url('customer_portal/book.php')) ?>"><i class="bi bi-calendar-plus"></i> Book appointment</a>
            <a class="customer-link <?= ($customer_active ?? '') === 'appointments' ? 'active' : '' ?>" href="<?= e(app_url('customer_portal/appointments.php')) ?>"><i class="bi bi-calendar2-check"></i> My appointments</a>
        </nav>
        <div class="customer-account"><span class="customer-avatar"><?= e(strtoupper(substr($customer['first_name'], 0, 1))) ?></span><span class="d-none d-md-inline">Hi, <?= e($customer['first_name']) ?></span><a href="<?= e(app_url('logout.php')) ?>" class="btn btn-sm btn-outline-secondary">Log out</a></div>
    </header>
    <main class="customer-main">
        <div class="customer-page-heading"><div><div class="hero-kicker text-primary">GLOWHUB CUSTOMER PORTAL</div><h1 class="h2 fw-bold mb-1"><?= e($page_title) ?></h1><p class="text-secondary mb-0">Your beauty appointments, all in one place.</p></div></div>
        <?php display_flashes(); ?>
