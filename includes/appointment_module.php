<?php
require_once __DIR__ . '/auth.php';
require_login();
update_overdue_appointments();
$active_menu = 'appointments';

function appointment_options(): array
{
    return [
        'customers' => all_rows("SELECT customer_id, CONCAT(first_name, ' ', last_name, ' — ', phone) AS label FROM customers ORDER BY first_name, last_name"),
        'services' => all_rows("SELECT service_id, service_name, price, duration_minutes FROM services WHERE status = 'Active' ORDER BY service_name"),
        'staff' => all_rows("SELECT st.staff_id, CONCAT(st.first_name, ' ', st.last_name, ' — ', st.position) AS label, st.status, GROUP_CONCAT(ss.service_id ORDER BY ss.service_id) AS service_ids FROM staff st LEFT JOIN staff_services ss ON ss.staff_id = st.staff_id GROUP BY st.staff_id ORDER BY st.first_name, st.last_name"),
    ];
}

function appointment_save(int $id = 0): void
{
    require_csrf_or_redirect('appointments/' . ($id ? 'edit.php?id=' . $id : 'add.php'));
    $customerId = post_id('customer_id');
    $serviceId = post_id('service_id');
    $staffId = post_id('staff_id');
    $date = posted('appointment_date');
    $time = posted('appointment_time');
    $status = posted('status', 'Scheduled');
    $notes = posted('notes');
    $errors = [];
    $existing = $id ? one_row('SELECT staff_id FROM appointments WHERE appointment_id = ?', [$id]) : null;

    if ($customerId < 1 || !scalar('SELECT COUNT(*) FROM customers WHERE customer_id = ?', [$customerId])) $errors[] = 'Please select a valid customer.';
    $service = $serviceId ? one_row("SELECT service_id, price FROM services WHERE service_id = ? AND status = 'Active'", [$serviceId]) : null;
    if (!$service) $errors[] = 'Please select an active service.';
    $staff = $staffId ? one_row('SELECT staff_id, status FROM staff WHERE staff_id = ?', [$staffId]) : null;
    if (!$staff) $errors[] = 'Please select a valid staff member.';
    if ($staff && (!$existing || (int) $existing['staff_id'] !== $staffId) && $staff['status'] !== 'Available') $errors[] = 'The selected staff member is unavailable.';
    if ($staffId && $serviceId && !scalar('SELECT COUNT(*) FROM staff_services WHERE staff_id = ? AND service_id = ?', [$staffId, $serviceId])) $errors[] = 'That staff member is not assigned to the selected service.';
    if (!is_valid_date($date)) $errors[] = 'Please enter a valid appointment date.';
    if (!is_valid_time($time)) $errors[] = 'Please enter a valid appointment time.';
    if ($id === 0 && $date && $time && strtotime($date . ' ' . $time) < time()) $errors[] = 'New appointments cannot be booked in the past.';
    if (!in_array($status, ['Scheduled', 'Completed', 'Cancelled', 'No-show'], true)) $errors[] = 'An invalid appointment status was selected.';
    if (mb_strlen($notes) > 500) $errors[] = 'Notes cannot exceed 500 characters.';

    if (!$errors && $status !== 'Cancelled') {
        $conflict = scalar("SELECT COUNT(*) FROM appointments WHERE staff_id = ? AND appointment_date = ? AND appointment_time = ? AND status <> 'Cancelled' AND appointment_id <> ?", [$staffId, $date, $time, $id]);
        if ($conflict) $errors[] = 'That staff member is already booked for this time.';
    }

    $form = compact('customerId', 'serviceId', 'staffId', 'date', 'time', 'status', 'notes');
    if ($errors) {
        $_SESSION['appointment_errors'] = $errors;
        $_SESSION['appointment_form'] = $form;
        redirect('appointments/' . ($id ? 'edit.php?id=' . $id : 'add.php'));
    }

    try {
        if ($id) {
            $statement = db()->prepare('UPDATE appointments SET customer_id=?, staff_id=?, service_id=?, appointment_date=?, appointment_time=?, status=?, notes=?, service_price=? WHERE appointment_id=?');
            $statement->execute([$customerId, $staffId, $serviceId, $date, $time, $status, $notes ?: null, $service['price'], $id]);
            set_flash('success', 'Appointment updated successfully.');
        } else {
            $statement = db()->prepare('INSERT INTO appointments (customer_id, staff_id, service_id, appointment_date, appointment_time, status, notes, service_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $statement->execute([$customerId, $staffId, $serviceId, $date, $time, $status, $notes ?: null, $service['price']]);
            set_flash('success', 'Appointment booked successfully.');
        }
    } catch (PDOException $exception) {
        $_SESSION['appointment_errors'] = [database_message($exception, 'Unable to save the appointment.')];
        $_SESSION['appointment_form'] = $form;
        redirect('appointments/' . ($id ? 'edit.php?id=' . $id : 'add.php'));
    }
    redirect('appointments/index.php');
}

if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('appointments/index.php');
    require_csrf_or_redirect('appointments/index.php');
    $id = post_id('appointment_id');
    try {
        $statement = db()->prepare('DELETE FROM appointments WHERE appointment_id = ?');
        $statement->execute([$id]);
        set_flash($statement->rowCount() ? 'success' : 'warning', $statement->rowCount() ? 'Appointment deleted successfully.' : 'Appointment no longer exists.');
    } catch (PDOException $exception) {
        set_flash('danger', database_message($exception, 'Unable to delete the appointment. Remove linked product usage first.'));
    }
    redirect('appointments/index.php');
}

if ($action === 'add' || $action === 'edit') {
    $id = $action === 'edit' ? get_id('id') : 0;
    if ($action === 'edit' && $id < 1) { set_flash('danger', 'Invalid appointment selected.'); redirect('appointments/index.php'); }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') appointment_save($id);

    $record = ['customer_id' => '', 'service_id' => '', 'staff_id' => '', 'appointment_date' => date('Y-m-d'), 'appointment_time' => '', 'status' => 'Scheduled', 'notes' => ''];
    if ($id) {
        $record = one_row('SELECT * FROM appointments WHERE appointment_id = ?', [$id]);
        if (!$record) { set_flash('danger', 'Appointment not found.'); redirect('appointments/index.php'); }
    }
    if (isset($_SESSION['appointment_form'])) {
        $form = $_SESSION['appointment_form']; unset($_SESSION['appointment_form']);
        $record = array_merge($record, ['customer_id' => $form['customerId'], 'service_id' => $form['serviceId'], 'staff_id' => $form['staffId'], 'appointment_date' => $form['date'], 'appointment_time' => $form['time'], 'status' => $form['status'], 'notes' => $form['notes']]);
    }
    $errors = $_SESSION['appointment_errors'] ?? []; unset($_SESSION['appointment_errors']);
    $options = appointment_options();
    $page_title = $id ? 'Edit Appointment' : 'Book Appointment'; require __DIR__ . '/header.php';
    ?>
    <div class="card form-card shadow-sm"><div class="card-body p-4">
        <p class="text-secondary">The service price is obtained from the Services table; it cannot be typed into this form.</p>
        <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" novalidate><?= csrf_input() ?><div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Customer</label><select class="form-select" name="customer_id" required><option value="">Choose customer…</option><?php foreach ($options['customers'] as $customer): ?><option value="<?= (int) $customer['customer_id'] ?>" <?= (int) $record['customer_id'] === (int) $customer['customer_id'] ? 'selected' : '' ?>><?= e($customer['label']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6 mb-3"><label class="form-label">Service</label><select class="form-select" name="service_id" data-service-price required><option value="">Choose service…</option><?php foreach ($options['services'] as $service): ?><option value="<?= (int) $service['service_id'] ?>" data-price="<?= e($service['price']) ?>" <?= (int) $record['service_id'] === (int) $service['service_id'] ? 'selected' : '' ?>><?= e($service['service_name']) ?> — <?= money($service['price']) ?> (<?= (int) $service['duration_minutes'] ?> min)</option><?php endforeach; ?></select></div>
            <div class="col-md-6 mb-3"><label class="form-label">Staff member</label><select class="form-select" name="staff_id" data-staff-select required><option value="">Choose staff…</option><?php foreach ($options['staff'] as $staff): ?><option value="<?= (int) $staff['staff_id'] ?>" data-services="<?= e($staff['service_ids'] ?? '') ?>" <?= $staff['status'] !== 'Available' && (int) $record['staff_id'] !== (int) $staff['staff_id'] ? 'disabled' : '' ?> <?= (int) $record['staff_id'] === (int) $staff['staff_id'] ? 'selected' : '' ?>><?= e($staff['label']) ?><?= $staff['status'] !== 'Available' ? ' (' . e($staff['status']) . ')' : '' ?></option><?php endforeach; ?></select><div class="form-text">Only staff assigned to the service are available for booking.</div></div>
            <div class="col-md-6 mb-3"><label class="form-label">Service price</label><input class="form-control" type="text" data-price-output readonly value=""></div>
            <div class="col-md-4 mb-3"><label class="form-label">Date</label><input class="form-control" type="date" name="appointment_date" value="<?= e($record['appointment_date']) ?>" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Time</label><input class="form-control" type="time" name="appointment_time" value="<?= e(substr((string) $record['appointment_time'], 0, 5)) ?>" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Status</label><select class="form-select" name="status" required><?php foreach (['Scheduled', 'Completed', 'Cancelled', 'No-show'] as $status): ?><option value="<?= e($status) ?>" <?= $record['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
            <div class="col-12 mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" maxlength="500" rows="3"><?= e($record['notes']) ?></textarea></div>
        </div><div class="d-flex gap-2"><button class="btn btn-primary">Save appointment</button><a class="btn btn-outline-secondary" href="<?= e(app_url('appointments/index.php')) ?>">Cancel</a></div></form>
    </div></div>
    <?php require __DIR__ . '/footer.php'; exit;
}

if ($action === 'view') {
    $id = get_id('id');
    $appointment = $id ? one_row("SELECT a.*, CONCAT(c.first_name, ' ', c.last_name) customer_name, c.phone, c.email, s.service_name, st.first_name staff_first, st.last_name staff_last, st.position FROM appointments a JOIN customers c ON c.customer_id=a.customer_id JOIN services s ON s.service_id=a.service_id JOIN staff st ON st.staff_id=a.staff_id WHERE a.appointment_id=?", [$id]) : null;
    if (!$appointment) { set_flash('danger', 'Appointment not found.'); redirect('appointments/index.php'); }
    $usage = all_rows('SELECT pu.*, p.product_name FROM product_usage pu JOIN products p ON p.product_id=pu.product_id WHERE pu.appointment_id=? ORDER BY pu.usage_date DESC', [$id]);
    $payment = one_row('SELECT * FROM payments WHERE appointment_id=?', [$id]);
    $page_title='Appointment #'.$id; require __DIR__ . '/header.php';
    ?>
    <div class="row g-4"><div class="col-lg-7"><div class="card shadow-sm"><div class="card-body p-4"><h2 class="h5">Booking details</h2><dl class="row mb-0"><dt class="col-sm-4">Customer</dt><dd class="col-sm-8"><?= e($appointment['customer_name']) ?><br><small><?= e($appointment['phone']) ?></small></dd><dt class="col-sm-4">Service</dt><dd class="col-sm-8"><?= e($appointment['service_name']) ?> · <?= money($appointment['service_price']) ?></dd><dt class="col-sm-4">Staff</dt><dd class="col-sm-8"><?= e($appointment['staff_first'].' '.$appointment['staff_last']) ?> · <?= e($appointment['position']) ?></dd><dt class="col-sm-4">Date & time</dt><dd class="col-sm-8"><?= e(date('d F Y H:i', strtotime($appointment['appointment_date'].' '.$appointment['appointment_time']))) ?></dd><dt class="col-sm-4">Status</dt><dd class="col-sm-8"><?= status_badge($appointment['status']) ?></dd><dt class="col-sm-4">Notes</dt><dd class="col-sm-8"><?= e($appointment['notes'] ?: '—') ?></dd></dl></div></div></div><div class="col-lg-5"><div class="card shadow-sm mb-4"><div class="card-body"><h2 class="h5">Payment</h2><?php if ($payment): ?><p class="mb-1"><?= money($payment['amount']) ?> via <?= e($payment['payment_method']) ?></p><?= status_badge($payment['payment_status']) ?><?php else: ?><span class="badge text-bg-danger">Unpaid</span><?php endif; ?><div class="mt-3"><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('payments/add.php?appointment_id='.$id)) ?>">Record / update payment</a></div></div></div><div class="card shadow-sm"><div class="card-body"><h2 class="h5">Products used</h2><?php if (!$usage): ?><span class="text-secondary">No product usage recorded.</span><?php endif; ?><?php foreach ($usage as $item): ?><div><?= e($item['product_name']) ?> <span class="text-secondary">× <?= (int) $item['quantity_used'] ?></span></div><?php endforeach; ?></div></div></div></div><div class="mt-3"><a class="btn btn-outline-secondary" href="<?= e(app_url('appointments/index.php')) ?>">Back to appointments</a></div>
    <?php require __DIR__ . '/footer.php'; exit;
}

$search = trim((string) ($_GET['q'] ?? '')); $statusFilter = trim((string) ($_GET['status'] ?? '')); $dateFilter = trim((string) ($_GET['date'] ?? ''));
$sql = "SELECT a.*, CONCAT(c.first_name, ' ', c.last_name) customer_name, s.service_name, CONCAT(st.first_name, ' ', st.last_name) staff_name FROM appointments a JOIN customers c ON c.customer_id=a.customer_id JOIN services s ON s.service_id=a.service_id JOIN staff st ON st.staff_id=a.staff_id";
$where=[]; $params=[];
if ($search !== '') { $where[]='(c.first_name LIKE :q OR c.last_name LIKE :q OR s.service_name LIKE :q OR st.first_name LIKE :q OR st.last_name LIKE :q)'; $params[':q']='%'.$search.'%'; }
if (in_array($statusFilter, ['Scheduled','Completed','Cancelled','No-show'], true)) { $where[]='a.status=:status'; $params[':status']=$statusFilter; }
if (is_valid_date($dateFilter)) { $where[]='a.appointment_date=:date'; $params[':date']=$dateFilter; }
if ($where) $sql.=' WHERE '.implode(' AND ', $where); $sql.=' ORDER BY a.appointment_date DESC, a.appointment_time DESC'; $rows=all_rows($sql,$params);
$page_title='Appointments'; require __DIR__ . '/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><form method="get" class="row g-2 align-items-center"><div class="col-auto"><input class="form-control" name="q" value="<?= e($search) ?>" placeholder="Customer, staff or service"></div><div class="col-auto"><input class="form-control" type="date" name="date" value="<?= e($dateFilter) ?>"></div><div class="col-auto"><select class="form-select" name="status"><option value="">All statuses</option><?php foreach(['Scheduled','Completed','Cancelled','No-show'] as $status): ?><option <?= $statusFilter===$status?'selected':'' ?>><?= e($status) ?></option><?php endforeach; ?></select></div><div class="col-auto"><button class="btn btn-outline-primary">Filter</button><a class="btn btn-outline-secondary" href="<?= e(app_url('appointments/index.php')) ?>">Clear</a></div></form><a class="btn btn-primary" href="<?= e(app_url('appointments/add.php')) ?>">+ Book appointment</a></div>
<div class="card shadow-sm table-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>ID</th><th>Date & time</th><th>Customer</th><th>Service</th><th>Staff</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody><?php if(!$rows): ?><tr><td colspan="7" class="text-center py-4 text-secondary">No appointments found.</td></tr><?php endif; ?><?php foreach($rows as $row): ?><tr><td>#<?= (int)$row['appointment_id'] ?></td><td><?= e(date('d M Y H:i',strtotime($row['appointment_date'].' '.$row['appointment_time']))) ?></td><td><?= e($row['customer_name']) ?></td><td><?= e($row['service_name']) ?><br><small><?= money($row['service_price']) ?></small></td><td><?= e($row['staff_name']) ?></td><td><?= status_badge($row['status']) ?></td><td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-info" href="<?= e(app_url('appointments/view.php?id='.$row['appointment_id'])) ?>">View</a><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('appointments/edit.php?id='.$row['appointment_id'])) ?>">Edit</a><form class="d-inline" method="post" action="<?= e(app_url('appointments/delete.php')) ?>"><input type="hidden" name="appointment_id" value="<?= (int)$row['appointment_id'] ?>"><?= csrf_input() ?><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this appointment? Linked payment records will also be removed.">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php require __DIR__ . '/footer.php';
