<?php
$navigation = [
    'dashboard' => ['Dashboard', 'index.php', 'bi-grid-1x2'],
    'customers' => ['Customers', 'customers/index.php', 'bi-people'],
    'staff' => ['Staff', 'staff/index.php', 'bi-person-badge'],
    'services' => ['Services', 'services/index.php', 'bi-scissors'],
    'appointments' => ['Appointments', 'appointments/index.php', 'bi-calendar-check'],
    'payments' => ['Payments', 'payments/index.php', 'bi-credit-card'],
    'suppliers' => ['Suppliers', 'suppliers/index.php', 'bi-truck'],
    'products' => ['Products & Stock', 'products/index.php', 'bi-box-seam'],
    'reports' => ['Reports', 'reports/appointments.php', 'bi-bar-chart'],
    'database' => ['Table Management', 'database/index.php', 'bi-database-gear'],
];
?>
<aside class="sidebar">
    <a class="brand" href="<?= e(app_url('index.php')) ?>">
        <span class="brand-mark">G</span><span>GlowHub</span>
    </a>
    <div class="brand-subtitle">SALON MANAGEMENT</div>
    <nav class="nav flex-column mt-3">
        <?php foreach ($navigation as $key => [$label, $path, $icon]): ?>
            <a class="nav-link <?= $active_menu === $key ? 'active' : '' ?>" href="<?= e(app_url($path)) ?>">
                <i class="bi <?= e($icon) ?> nav-icon" aria-hidden="true"></i><span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">Database Systems Project<br><span>PHP · MySQL · Bootstrap 5</span></div>
</aside>
