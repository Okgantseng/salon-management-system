<?php
require_once __DIR__ . '/../includes/auth.php';
require_customer();
$customerId = (int) current_user()['customer_id'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_redirect('customer_portal/book.php');
    $serviceId = post_id('service_id'); $staffId = post_id('staff_id'); $date = posted('appointment_date'); $time = posted('appointment_time'); $notes = posted('notes');
    $service = $serviceId ? one_row("SELECT service_id, price FROM services WHERE service_id = ? AND status = 'Active'", [$serviceId]) : null;
    $staff = $staffId ? one_row("SELECT staff_id, status FROM staff WHERE staff_id = ?", [$staffId]) : null;
    if (!$service) $errors[] = 'Please choose an active service.';
    if (!$staff || $staff['status'] !== 'Available') $errors[] = 'Please choose an available staff member.';
    if ($staff && $service && !scalar('SELECT COUNT(*) FROM staff_services WHERE staff_id = ? AND service_id = ?', [$staffId, $serviceId])) $errors[] = 'That staff member does not provide the selected service.';
    if (!is_valid_date($date) || strtotime($date) < strtotime(date('Y-m-d'))) $errors[] = 'Please choose today or a future date.';
    if (!is_valid_time($time)) $errors[] = 'Please choose a valid appointment time.';
    if ($date && $time && strtotime($date . ' ' . $time) < time()) $errors[] = 'Please choose a future appointment time.';
    if (strlen($notes) > 500) $errors[] = 'Notes cannot exceed 500 characters.';
    if (!$errors && scalar("SELECT COUNT(*) FROM appointments WHERE staff_id = ? AND appointment_date = ? AND appointment_time = ? AND status <> 'Cancelled'", [$staffId, $date, $time])) $errors[] = 'That staff member is already booked for this time. Please choose another time.';
    if (!$errors) {
        try {
            $statement = db()->prepare("INSERT INTO appointments (customer_id, staff_id, service_id, appointment_date, appointment_time, status, notes, service_price) VALUES (?, ?, ?, ?, ?, 'Scheduled', ?, ?)");
            $statement->execute([$customerId, $staffId, $serviceId, $date, $time, $notes ?: null, $service['price']]);
            set_flash('success', 'Your appointment request was booked successfully.');
            redirect('customer_portal/appointments.php');
        } catch (PDOException $exception) {
            $errors[] = 'We could not book this appointment. Please try again.';
        }
    }
}
$services = all_rows("SELECT service_id, service_name, description, duration_minutes, price FROM services WHERE status = 'Active' ORDER BY service_name");
$staff = all_rows("SELECT st.staff_id, CONCAT(st.first_name, ' ', st.last_name) AS staff_name, st.position, GROUP_CONCAT(ss.service_id ORDER BY ss.service_id) service_ids FROM staff st JOIN staff_services ss ON ss.staff_id = st.staff_id WHERE st.status = 'Available' GROUP BY st.staff_id ORDER BY st.first_name, st.last_name");
$page_title = 'Book an Appointment'; $customer_active = 'book'; require __DIR__ . '/../includes/customer_header.php';
?>
<div class="card booking-card"><div class="card-body p-4 p-md-5"><div class="booking-intro mb-4"><span class="customer-stat-icon blue"><i class="bi bi-calendar-heart"></i></span><div><h2 class="h4 mb-1">Plan your next visit</h2><p class="text-secondary mb-0">Select a service, stylist and time that works for you.</p></div></div><?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="post" novalidate><?= csrf_input() ?><div class="row"><div class="col-lg-6 mb-3"><label class="form-label">What would you like?</label><select class="form-select" name="service_id" data-service-price required><option value="">Choose a service…</option><?php foreach ($services as $service): ?><option value="<?= (int) $service['service_id'] ?>" data-price="<?= e($service['price']) ?>" <?= (int) ($_POST['service_id'] ?? 0) === (int) $service['service_id'] ? 'selected' : '' ?>><?= e($service['service_name']) ?> · <?= money($service['price']) ?> · <?= (int) $service['duration_minutes'] ?> min</option><?php endforeach; ?></select></div><div class="col-lg-6 mb-3"><label class="form-label">Preferred stylist</label><select class="form-select" name="staff_id" data-staff-select required><option value="">Choose a stylist…</option><?php foreach ($staff as $member): ?><option value="<?= (int) $member['staff_id'] ?>" data-services="<?= e($member['service_ids']) ?>" <?= (int) ($_POST['staff_id'] ?? 0) === (int) $member['staff_id'] ? 'selected' : '' ?>><?= e($member['staff_name']) ?> · <?= e($member['position']) ?></option><?php endforeach; ?></select><div class="form-text">Stylists are filtered to match your selected service.</div></div><div class="col-lg-6 mb-3"><label class="form-label">Date</label><input class="form-control" type="date" name="appointment_date" min="<?= e(date('Y-m-d')) ?>" value="<?= e($_POST['appointment_date'] ?? date('Y-m-d')) ?>" required></div><div class="col-lg-6 mb-3"><label class="form-label">Time</label><input class="form-control" type="time" name="appointment_time" value="<?= e($_POST['appointment_time'] ?? '') ?>" required></div><div class="col-12 mb-3"><label class="form-label">Service price</label><input class="form-control" type="text" data-price-output readonly value=""><div class="form-text">The price is retrieved securely from the salon services list.</div></div><div class="col-12 mb-4"><label class="form-label">Notes for the salon <span class="text-secondary fw-normal">(optional)</span></label><textarea class="form-control" name="notes" maxlength="500" rows="3" placeholder="Anything you would like us to know?"><?= e($_POST['notes'] ?? '') ?></textarea></div></div><div class="d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-calendar-check me-1"></i> Confirm booking</button><a class="btn btn-outline-secondary" href="<?= e(app_url('customer_portal/index.php')) ?>">Cancel</a></div></form></div></div>
<?php require __DIR__ . '/../includes/customer_footer.php';
