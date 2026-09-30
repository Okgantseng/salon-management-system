<?php
require_once __DIR__ . '/auth.php';
require_login();
$active_menu = 'payments';

function payment_appointments(bool $includePaid = false): array
{
    $where = $includePaid ? '' : ' WHERE p.payment_id IS NULL';
    return all_rows("SELECT a.appointment_id, a.service_price, a.status, a.appointment_date, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, s.service_name FROM appointments a JOIN customers c ON c.customer_id=a.customer_id JOIN services s ON s.service_id=a.service_id LEFT JOIN payments p ON p.appointment_id=a.appointment_id $where ORDER BY a.appointment_date DESC, a.appointment_time DESC");
}

function payment_save(int $id = 0): void
{
    require_csrf_or_redirect('payments/' . ($id ? 'edit.php?id=' . $id : 'add.php'));
    $appointmentId = post_id('appointment_id');
    $method = posted('payment_method');
    $status = posted('payment_status');
    $enteredAmount = posted('amount');
    $date = posted('payment_date');
    $errors = [];
    $appointment = $appointmentId ? one_row('SELECT appointment_id, service_price FROM appointments WHERE appointment_id = ?', [$appointmentId]) : null;
    if (!$appointment) $errors[] = 'Please select a valid appointment.';
    if (!in_array($method, ['Cash', 'Card', 'EFT'], true)) $errors[] = 'Please select a valid payment method.';
    if (!in_array($status, ['Paid', 'Partially Paid', 'Unpaid'], true)) $errors[] = 'Please select a valid payment status.';
    if ($enteredAmount !== '' && !is_numeric($enteredAmount)) $errors[] = 'Amount must be a valid number.';

    $amount = 0.0;
    if ($appointment && $status === 'Paid') {
        $amount = (float) $appointment['service_price'];
    } elseif ($appointment && $status === 'Partially Paid') {
        $amount = (float) $enteredAmount;
        if ($amount <= 0 || $amount >= (float) $appointment['service_price']) $errors[] = 'A partial payment must be greater than R 0 and less than the appointment charge.';
    } elseif ($status === 'Unpaid') {
        $amount = 0.0;
    }
    if ($status !== 'Unpaid' && !is_valid_date($date)) $errors[] = 'A payment date is required for paid and partially paid payments.';
    if ($status === 'Unpaid') $date = '';

    if ($appointmentId && !$id && scalar('SELECT COUNT(*) FROM payments WHERE appointment_id = ?', [$appointmentId])) $errors[] = 'This appointment already has a payment record. Update it instead.';
    $form = compact('appointmentId', 'method', 'status', 'enteredAmount', 'date');
    if ($errors) { $_SESSION['payment_errors']=$errors; $_SESSION['payment_form']=$form; redirect('payments/' . ($id ? 'edit.php?id='.$id : 'add.php')); }

    try {
        if ($id) {
            $statement=db()->prepare('UPDATE payments SET appointment_id=?, amount=?, payment_method=?, payment_status=?, payment_date=? WHERE payment_id=?');
            $statement->execute([$appointmentId,$amount,$method,$status,$date ?: null,$id]);
            set_flash('success','Payment updated successfully.');
        } else {
            $statement=db()->prepare('INSERT INTO payments (appointment_id, amount, payment_method, payment_status, payment_date) VALUES (?, ?, ?, ?, ?)');
            $statement->execute([$appointmentId,$amount,$method,$status,$date ?: null]);
            set_flash('success','Payment recorded successfully.');
        }
    } catch (PDOException $exception) {
        $_SESSION['payment_errors']=[database_message($exception,'Unable to save the payment.')]; $_SESSION['payment_form']=$form;
        redirect('payments/' . ($id ? 'edit.php?id='.$id : 'add.php'));
    }
    redirect('payments/index.php');
}

if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('payments/index.php');
    require_csrf_or_redirect('payments/index.php'); $id=post_id('payment_id');
    try { $statement=db()->prepare('DELETE FROM payments WHERE payment_id=?'); $statement->execute([$id]); set_flash($statement->rowCount()?'success':'warning',$statement->rowCount()?'Payment deleted successfully.':'Payment no longer exists.'); }
    catch (PDOException $exception) { set_flash('danger',database_message($exception,'Unable to delete the payment.')); }
    redirect('payments/index.php');
}

if ($action === 'add' || $action === 'edit') {
    $id=$action==='edit'?get_id('id'):0;
    if ($action==='edit' && $id<1) { set_flash('danger','Invalid payment selected.'); redirect('payments/index.php'); }
    if ($_SERVER['REQUEST_METHOD']==='POST') payment_save($id);
    $record=['appointment_id'=>(int)($_GET['appointment_id']??0),'amount'=>'','payment_method'=>'Cash','payment_status'=>'Paid','payment_date'=>date('Y-m-d')];
    if ($id) { $record=one_row('SELECT * FROM payments WHERE payment_id=?',[$id]); if(!$record){set_flash('danger','Payment not found.');redirect('payments/index.php');} }
    elseif ($record['appointment_id'] && ($existing=one_row('SELECT payment_id FROM payments WHERE appointment_id=?',[$record['appointment_id']]))) redirect('payments/edit.php?id='.$existing['payment_id']);
    if (isset($_SESSION['payment_form'])) { $form=$_SESSION['payment_form']; unset($_SESSION['payment_form']); $record=array_merge($record,['appointment_id'=>$form['appointmentId'],'amount'=>$form['enteredAmount'],'payment_method'=>$form['method'],'payment_status'=>$form['status'],'payment_date'=>$form['date']]); }
    $errors=$_SESSION['payment_errors']??[]; unset($_SESSION['payment_errors']);
    $appointments=$id?payment_appointments(true):payment_appointments();
    $page_title=$id?'Edit Payment':'Record Payment'; require __DIR__.'/header.php';
    ?>
    <div class="card form-card shadow-sm"><div class="card-body p-4"><p class="text-secondary">Selecting an appointment retrieves its service charge automatically. A paid status records the full charge; partial payments can be entered below the charge.</p><?php if($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $error): ?><li><?=e($error)?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" novalidate><?=csrf_input()?><div class="row"><div class="col-12 mb-3"><label class="form-label">Appointment</label><select class="form-select" name="appointment_id" data-payment-appointment required><option value="">Choose appointment…</option><?php foreach($appointments as $appointment): ?><option value="<?= (int)$appointment['appointment_id'] ?>" data-price="<?=e($appointment['service_price'])?>" <?= (int)$record['appointment_id']===(int)$appointment['appointment_id']?'selected':'' ?>>#<?= (int)$appointment['appointment_id'] ?> · <?=e($appointment['customer_name'])?> · <?=e($appointment['service_name'])?> · <?=money($appointment['service_price'])?></option><?php endforeach; ?></select></div><div class="col-md-4 mb-3"><label class="form-label">Payment method</label><select class="form-select" name="payment_method" required><?php foreach(['Cash','Card','EFT'] as $method): ?><option <?= $record['payment_method']===$method?'selected':'' ?>><?=e($method)?></option><?php endforeach;?></select></div><div class="col-md-4 mb-3"><label class="form-label">Payment status</label><select class="form-select" name="payment_status" required><?php foreach(['Paid','Partially Paid','Unpaid'] as $status): ?><option <?= $record['payment_status']===$status?'selected':'' ?>><?=e($status)?></option><?php endforeach;?></select></div><div class="col-md-4 mb-3"><label class="form-label">Amount received (R)</label><input class="form-control" type="number" min="0" step="0.01" name="amount" data-payment-amount value="<?=e($record['amount'])?>" required><div class="form-text">Paid/Unpaid values are set automatically when saved.</div></div><div class="col-md-6 mb-3"><label class="form-label">Payment date</label><input class="form-control" type="date" name="payment_date" value="<?=e($record['payment_date'])?>"></div></div><div class="d-flex gap-2"><button class="btn btn-primary">Save payment</button><a class="btn btn-outline-secondary" href="<?=e(app_url('payments/index.php'))?>">Cancel</a></div></form></div></div>
    <?php require __DIR__.'/footer.php'; exit;
}

$search=trim((string)($_GET['q']??''));$statusFilter=trim((string)($_GET['status']??''));$sql="SELECT p.*,a.service_price,a.appointment_date,CONCAT(c.first_name,' ',c.last_name) customer_name,s.service_name FROM payments p JOIN appointments a ON a.appointment_id=p.appointment_id JOIN customers c ON c.customer_id=a.customer_id JOIN services s ON s.service_id=a.service_id";$where=[];$params=[];if($search!==''){$where[]="(c.first_name LIKE :q OR c.last_name LIKE :q OR s.service_name LIKE :q OR p.payment_method LIKE :q)";$params[':q']='%'.$search.'%';}if(in_array($statusFilter,['Paid','Partially Paid','Unpaid'],true)){$where[]='p.payment_status=:status';$params[':status']=$statusFilter;}if($where)$sql.=' WHERE '.implode(' AND ',$where);$sql.=' ORDER BY p.created_at DESC';$rows=all_rows($sql,$params);$page_title='Payments';require __DIR__.'/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><form method="get" class="d-flex gap-2"><input class="form-control" name="q" value="<?=e($search)?>" placeholder="Customer, service or method"><select class="form-select" name="status"><option value="">All statuses</option><?php foreach(['Paid','Partially Paid','Unpaid'] as $status):?><option <?= $statusFilter===$status?'selected':''?>><?=e($status)?></option><?php endforeach;?></select><button class="btn btn-outline-primary">Filter</button></form><a class="btn btn-primary" href="<?=e(app_url('payments/add.php'))?>">+ Record payment</a></div><div class="card shadow-sm table-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>ID</th><th>Appointment</th><th>Customer</th><th>Charge</th><th>Received</th><th>Method</th><th>Date</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody><?php if(!$rows):?><tr><td colspan="9" class="text-center py-4 text-secondary">No payments found.</td></tr><?php endif;?><?php foreach($rows as $row):?><tr><td>#<?= (int)$row['payment_id']?></td><td>#<?= (int)$row['appointment_id']?> · <?=e($row['service_name'])?></td><td><?=e($row['customer_name'])?></td><td><?=money($row['service_price'])?></td><td><?=money($row['amount'])?></td><td><?=e($row['payment_method'])?></td><td><?=e($row['payment_date']?:'—')?></td><td><?=status_badge($row['payment_status'])?></td><td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="<?=e(app_url('payments/edit.php?id='.$row['payment_id']))?>">Edit</a><form class="d-inline" method="post" action="<?=e(app_url('payments/delete.php'))?>"><input type="hidden" name="payment_id" value="<?= (int)$row['payment_id']?>"><?=csrf_input()?><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this payment record?">Delete</button></form></td></tr><?php endforeach;?></tbody></table></div></div>
<?php require __DIR__.'/footer.php';
