<?php
require_once __DIR__ . '/auth.php';
$page_title = $page_title ?? 'Salon Management System';
$active_menu = $active_menu ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | GlowHub Salon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 fw-bold"><?= e($page_title) ?></h1>
                <span class="text-secondary small">GlowHub Salon Management</span>
            </div>
            <div class="text-end small">
                <div class="fw-semibold"><?= e(current_user()['full_name'] ?? '') ?></div>
                <a href="<?= e(app_url('logout.php')) ?>" class="link-danger">Log out</a>
            </div>
        </div>
        <?php display_flashes(); ?>
