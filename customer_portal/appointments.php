<?php
require_once __DIR__ . '/../includes/auth.php';
require_customer();
$customerId = (int) current_user()['customer_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_redirect('customer_portal/appointments.php');
    $appointmentId = post_id('appointment_id');
    $statement = db()->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ? AND customer_id = ? AND status = 'Scheduled'");
    $statement->execute([$appointmentId, $customerId]);
    set_flash($statement->rowCount() ? 'success' : 'warning', $statement->rowCount() ? 'Your appointment was cancelled.' : 'That appointment could not be cancelled.');
    redirect('customer_portal/appointments.php');
}
$appointments = all_rows("SELECT a.*, s.service_name, CONCAT(st.first_name, ' ', st.last_name) staff_name FROM appointments a JOIN services s ON s.service_id = a.service_id JOIN staff st ON st.staff_id = a.staff_id WHERE a.customer_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC", [$customerId]);
$page_title = 'My Appointments'; $customer_active = 'appointments'; require __DIR__ . '/../includes/customer_header.php';
?>
<div class="d-flex justify-content-end mb-3"><a class="btn btn-primary" href="<?= e(app_url('customer_portal/book.php')) ?>"><i class="bi bi-plus-lg me-1"></i> Book another appointment</a></div><div class="card table-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Date & time</th><th>Service</th><th>Stylist</th><th>Price</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody><?php if (!$appointments): ?><tr><td colspan="6" class="text-center text-secondary py-5">You have no appointments yet.</td></tr><?php endif; ?><?php foreach ($appointments as $appointment): ?><tr><td><?= e(date('d M Y', strtotime($appointment['appointment_date']))) ?><br><small class="text-secondary"><?= e(date('H:i', strtotime($appointment['appointment_time']))) ?></small></td><td><?= e($appointment['service_name']) ?><br><small class="text-secondary"><?= e($appointment['notes'] ?: 'No notes') ?></small></td><td><?= e($appointment['staff_name']) ?></td><td><?= money($appointment['service_price']) ?></td><td><?= status_badge($appointment['status']) ?></td><td class="text-end"><?php if ($appointment['status'] === 'Scheduled' && strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']) > time()): ?><form method="post" class="d-inline"><?= csrf_input() ?><input type="hidden" name="appointment_id" value="<?= (int) $appointment['appointment_id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Cancel this appointment?">Cancel</button></form><?php else: ?><span class="text-secondary small">—</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php require __DIR__ . '/../includes/customer_footer.php';
