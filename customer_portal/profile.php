<?php
require_once __DIR__ . '/../includes/auth.php';
require_customer();
$customerId = (int) current_user()['customer_id'];
$customer = one_row('SELECT * FROM customers WHERE customer_id = ?', [$customerId]);
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_redirect('customer_portal/profile.php');
    $phone = posted('phone'); $email = posted('email'); $gender = posted('gender');
    if (!valid_phone($phone)) $errors[] = 'Please enter a valid South African phone number.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!in_array($gender, ['Female', 'Male', 'Other'], true)) $errors[] = 'Please select a valid gender.';
    if (!$errors && scalar('SELECT COUNT(*) FROM customers WHERE phone = ? AND customer_id <> ?', [$phone, $customerId])) $errors[] = 'That phone number is already in use.';
    if (!$errors && scalar('SELECT COUNT(*) FROM customers WHERE email = ? AND customer_id <> ?', [$email, $customerId])) $errors[] = 'That email address is already in use.';
    if (!$errors) {
        $statement = db()->prepare('UPDATE customers SET phone = ?, email = ?, gender = ? WHERE customer_id = ?');
        $statement->execute([$phone, $email, $gender, $customerId]);
        db()->prepare('UPDATE users SET full_name = ? WHERE customer_id = ?')->execute([$customer['first_name'] . ' ' . $customer['last_name'], $customerId]);
        set_flash('success', 'Your profile was updated successfully.');
        redirect('customer_portal/profile.php');
    }
    $customer['phone'] = $phone; $customer['email'] = $email; $customer['gender'] = $gender;
}
$page_title = 'My Profile'; $customer_active = 'profile'; require __DIR__ . '/../includes/customer_header.php';
?>
<div class="row g-4"><div class="col-lg-7"><div class="card booking-card"><div class="card-body p-4 p-md-5"><div class="booking-intro mb-4"><span class="customer-stat-icon rose"><i class="bi bi-person-heart"></i></span><div><h2 class="h4 mb-1">Your profile</h2><p class="text-secondary mb-0">Keep your contact details up to date for a smooth visit.</p></div></div><?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><div class="alert alert-light border small"><strong><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></strong><br>Your name is managed by the salon. You can update your contact details below.</div><form method="post"><?= csrf_input() ?><div class="mb-3"><label class="form-label">Phone number</label><input class="form-control" name="phone" value="<?= e($customer['phone']) ?>" required></div><div class="mb-3"><label class="form-label">Email address</label><input class="form-control" type="email" name="email" value="<?= e($customer['email']) ?>" required></div><div class="mb-4"><label class="form-label">Gender</label><select class="form-select" name="gender" required><?php foreach(['Female','Male','Other'] as $option): ?><option <?= $customer['gender'] === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save profile</button></form></div></div></div><div class="col-lg-5"><div class="customer-tip-card"><i class="bi bi-shield-check"></i><h3 class="h5 mt-3">Your account is secure</h3><p class="text-secondary mb-0">Your login password is securely hashed and your appointment information is private to your account.</p></div></div></div>
<?php require __DIR__ . '/../includes/customer_footer.php';
