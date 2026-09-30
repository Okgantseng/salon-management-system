<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$customerId = get_id('id');
$customer = $customerId ? one_row('SELECT * FROM customers WHERE customer_id = ?', [$customerId]) : null;
if (!$customer) { set_flash('danger', 'Customer not found.'); redirect('customers/index.php'); }
$appointments = all_rows("SELECT a.*, s.service_name, CONCAT(st.first_name, ' ', st.last_name) AS staff_name, p.payment_id, p.amount, p.payment_status FROM appointments a JOIN services s ON s.service_id = a.service_id JOIN staff st ON st.staff_id = a.staff_id LEFT JOIN payments p ON p.appointment_id = a.appointment_id WHERE a.customer_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC", [$customerId]);
$page_title = 'Customer Booking History'; $active_menu = 'customers'; require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-1"><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></h2><span class="text-secondary"><?= e($customer['phone']) ?><?= $customer['email'] ? ' · ' . e($customer['email']) : '' ?></span></div><a class="btn btn-outline-secondary" href="<?= e(app_url('customers/index.php')) ?>">Back to customers</a></div>
<div class="card shadow-sm table-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>ID</th><th>Date & time</th><th>Service</th><th>Staff</th><th>Appointment</th><th>Payment</th></tr></thead><tbody><?php if (!$appointments): ?><tr><td colspan="6" class="text-center py-4 text-secondary">No booking history yet.</td></tr><?php endif; ?><?php foreach ($appointments as $appointment): ?><tr><td>#<?= (int) $appointment['appointment_id'] ?></td><td><?= e(date('d M Y H:i', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']))) ?></td><td><?= e($appointment['service_name']) ?><br><small><?= money($appointment['service_price']) ?></small></td><td><?= e($appointment['staff_name']) ?></td><td><?= status_badge($appointment['status']) ?></td><td><?= $appointment['payment_id'] ? money($appointment['amount']) . ' ' . status_badge($appointment['payment_status']) : '<span class="badge text-bg-danger">Unpaid</span>' ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php';
