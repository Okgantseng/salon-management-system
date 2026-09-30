<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
update_overdue_appointments();

$statistics = [
    ['Customers', scalar('SELECT COUNT(*) FROM customers'), 'primary'],
    ['Staff', scalar('SELECT COUNT(*) FROM staff'), 'info'],
    ['Active services', scalar("SELECT COUNT(*) FROM services WHERE status = 'Active'"), 'success'],
    ['Today\'s appointments', scalar('SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()'), 'warning'],
    ['Completed appointments', scalar("SELECT COUNT(*) FROM appointments WHERE status = 'Completed'"), 'success'],
    ['Cancelled appointments', scalar("SELECT COUNT(*) FROM appointments WHERE status = 'Cancelled'"), 'danger'],
    ['Total revenue', money(scalar("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'Paid'")), 'dark'],
    ['Low-stock products', scalar('SELECT COUNT(*) FROM products WHERE quantity <= reorder_level'), 'danger'],
];
$today = all_rows("SELECT a.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, s.service_name, CONCAT(st.first_name, ' ', st.last_name) AS staff_name FROM appointments a JOIN customers c ON c.customer_id = a.customer_id JOIN services s ON s.service_id = a.service_id JOIN staff st ON st.staff_id = a.staff_id WHERE a.appointment_date = CURDATE() ORDER BY a.appointment_time");
$upcoming = all_rows("SELECT a.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, s.service_name, CONCAT(st.first_name, ' ', st.last_name) AS staff_name FROM appointments a JOIN customers c ON c.customer_id = a.customer_id JOIN services s ON s.service_id = a.service_id JOIN staff st ON st.staff_id = a.staff_id WHERE a.status = 'Scheduled' AND a.appointment_date >= CURDATE() ORDER BY a.appointment_date, a.appointment_time LIMIT 6");
$recentPayments = all_rows("SELECT p.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, s.service_name FROM payments p JOIN appointments a ON a.appointment_id = p.appointment_id JOIN customers c ON c.customer_id = a.customer_id JOIN services s ON s.service_id = a.service_id ORDER BY p.created_at DESC LIMIT 6");
$lowStock = all_rows('SELECT * FROM products WHERE quantity <= reorder_level ORDER BY quantity ASC LIMIT 6');
$page_title = 'Dashboard';
$active_menu = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="row g-3 mb-4"><?php foreach ($statistics as [$label, $value, $colour]): ?><div class="col-sm-6 col-xl-3"><div class="card metric-card metric-<?= e($colour) ?> shadow-sm"><div class="card-body"><div class="metric-label"><?= e($label) ?></div><div class="metric-value"><?= e($value) ?></div></div></div></div><?php endforeach; ?></div>
<div class="row g-4">
    <div class="col-xl-8"><div class="card shadow-sm table-card h-100"><div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">Today’s appointments</h2><a href="<?= e(app_url('appointments/add.php')) ?>" class="btn btn-sm btn-primary">Book appointment</a></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Time</th><th>Customer</th><th>Service</th><th>Staff</th><th>Status</th></tr></thead><tbody><?php if (!$today): ?><tr><td colspan="5" class="text-center text-secondary py-4">No appointments scheduled for today.</td></tr><?php endif; ?><?php foreach ($today as $appointment): ?><tr><td><?= e(date('H:i', strtotime($appointment['appointment_time']))) ?></td><td><?= e($appointment['customer_name']) ?></td><td><?= e($appointment['service_name']) ?></td><td><?= e($appointment['staff_name']) ?></td><td><?= status_badge($appointment['status']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <div class="col-xl-4"><div class="card shadow-sm h-100"><div class="card-header bg-white"><h2 class="h5 mb-0">Low-stock alerts</h2></div><ul class="list-group list-group-flush"><?php if (!$lowStock): ?><li class="list-group-item text-secondary">All products are above reorder level.</li><?php endif; ?><?php foreach ($lowStock as $product): ?><li class="list-group-item d-flex justify-content-between align-items-center"><span><strong><?= e($product['product_name']) ?></strong><br><small class="text-secondary">Reorder at <?= (int) $product['reorder_level'] ?></small></span><span class="badge text-bg-danger"><?= (int) $product['quantity'] ?> left</span></li><?php endforeach; ?></ul><div class="card-footer bg-white"><a href="<?= e(app_url('reports/low_stock.php')) ?>" class="small">View low-stock report</a></div></div></div>
    <div class="col-lg-6"><div class="card shadow-sm table-card"><div class="card-header bg-white"><h2 class="h5 mb-0">Upcoming appointments</h2></div><div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead><tr><th>Date</th><th>Customer</th><th>Service</th></tr></thead><tbody><?php foreach ($upcoming as $appointment): ?><tr><td><?= e(date('d M H:i', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']))) ?></td><td><?= e($appointment['customer_name']) ?></td><td><?= e($appointment['service_name']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <div class="col-lg-6"><div class="card shadow-sm table-card"><div class="card-header bg-white"><h2 class="h5 mb-0">Recent payments</h2></div><div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead><tr><th>Customer</th><th>Amount</th><th>Status</th></tr></thead><tbody><?php foreach ($recentPayments as $payment): ?><tr><td><?= e($payment['customer_name']) ?><br><small class="text-secondary"><?= e($payment['service_name']) ?></small></td><td><?= money($payment['amount']) ?></td><td><?= status_badge($payment['payment_status']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
</div>
<?php require __DIR__ . '/includes/footer.php';
