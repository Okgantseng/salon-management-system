<?php
/** Reusable CRUD handler for customers, staff, services, suppliers and products. */
require_once __DIR__ . '/auth.php';
require_login();

function master_config(string $entity): array
{
    $common = ['search' => [], 'columns' => [], 'fields' => []];
    $configs = [
        'customers' => [
            'label' => 'Customer', 'plural' => 'Customers', 'table' => 'customers', 'pk' => 'customer_id',
            'search' => ['first_name', 'last_name', 'phone', 'email'],
            'columns' => ['first_name' => 'First name', 'last_name' => 'Last name', 'phone' => 'Phone', 'email' => 'Email', 'gender' => 'Gender', 'date_registered' => 'Registered'],
            'fields' => [
                'first_name' => ['label' => 'First name', 'required' => true],
                'last_name' => ['label' => 'Last name', 'required' => true],
                'phone' => ['label' => 'South African phone number', 'required' => true, 'validator' => 'phone', 'placeholder' => '0823456710'],
                'email' => ['label' => 'Email address', 'type' => 'email', 'validator' => 'email'],
                'gender' => ['label' => 'Gender', 'type' => 'select', 'required' => true, 'options' => ['Female', 'Male', 'Other']],
                'date_registered' => ['label' => 'Date registered', 'type' => 'date', 'required' => true, 'default' => date('Y-m-d')],
            ],
        ],
        'staff' => [
            'label' => 'Staff member', 'plural' => 'Staff', 'table' => 'staff', 'pk' => 'staff_id',
            'search' => ['first_name', 'last_name', 'phone', 'email', 'position', 'status'],
            'columns' => ['first_name' => 'First name', 'last_name' => 'Last name', 'phone' => 'Phone', 'email' => 'Email', 'position' => 'Position', 'status' => 'Status'],
            'fields' => [
                'first_name' => ['label' => 'First name', 'required' => true],
                'last_name' => ['label' => 'Last name', 'required' => true],
                'phone' => ['label' => 'South African phone number', 'required' => true, 'validator' => 'phone', 'placeholder' => '0823456710'],
                'email' => ['label' => 'Email address', 'type' => 'email', 'validator' => 'email'],
                'position' => ['label' => 'Position', 'required' => true, 'placeholder' => 'e.g. Senior Hair Stylist'],
                'status' => ['label' => 'Availability status', 'type' => 'select', 'required' => true, 'options' => ['Available', 'Unavailable', 'Inactive'], 'default' => 'Available'],
            ],
        ],
        'services' => [
            'label' => 'Service', 'plural' => 'Services', 'table' => 'services', 'pk' => 'service_id',
            'search' => ['service_name', 'description', 'status'],
            'columns' => ['service_name' => 'Service', 'description' => 'Description', 'duration_minutes' => 'Duration', 'price' => 'Price', 'status' => 'Status'],
            'fields' => [
                'service_name' => ['label' => 'Service name', 'required' => true],
                'description' => ['label' => 'Description', 'type' => 'textarea'],
                'duration_minutes' => ['label' => 'Duration (minutes)', 'type' => 'number', 'required' => true, 'min' => 5, 'max' => 600],
                'price' => ['label' => 'Price (R)', 'type' => 'number', 'required' => true, 'min' => 0, 'step' => '0.01'],
                'status' => ['label' => 'Service status', 'type' => 'select', 'required' => true, 'options' => ['Active', 'Inactive'], 'default' => 'Active'],
            ],
        ],
        'suppliers' => [
            'label' => 'Supplier', 'plural' => 'Suppliers', 'table' => 'suppliers', 'pk' => 'supplier_id',
            'search' => ['supplier_name', 'phone', 'email', 'address'],
            'columns' => ['supplier_name' => 'Supplier', 'phone' => 'Phone', 'email' => 'Email', 'address' => 'Address'],
            'fields' => [
                'supplier_name' => ['label' => 'Supplier name', 'required' => true],
                'phone' => ['label' => 'Phone number', 'required' => true, 'validator' => 'phone', 'placeholder' => '0115550111'],
                'email' => ['label' => 'Email address', 'type' => 'email', 'validator' => 'email'],
                'address' => ['label' => 'Address', 'type' => 'textarea'],
            ],
        ],
        'products' => [
            'label' => 'Product', 'plural' => 'Products', 'table' => 'products', 'pk' => 'product_id',
            'search' => ['product_name', 'category', 'status'],
            'columns' => ['product_name' => 'Product', 'supplier_name' => 'Supplier', 'category' => 'Category', 'quantity' => 'In stock', 'reorder_level' => 'Reorder level', 'unit_price' => 'Unit price', 'status' => 'Status'],
            'index_sql' => 'SELECT p.*, s.supplier_name FROM products p JOIN suppliers s ON s.supplier_id = p.supplier_id',
            'search_prefix' => 'p.',
            'fields' => [
                'supplier_id' => ['label' => 'Supplier', 'type' => 'relation', 'required' => true, 'query' => 'SELECT supplier_id AS id, supplier_name AS name FROM suppliers ORDER BY supplier_name'],
                'product_name' => ['label' => 'Product name', 'required' => true],
                'category' => ['label' => 'Category', 'required' => true, 'placeholder' => 'e.g. Hair Care'],
                'quantity' => ['label' => 'Current quantity', 'type' => 'number', 'required' => true, 'min' => 0, 'default' => 0],
                'reorder_level' => ['label' => 'Reorder level', 'type' => 'number', 'required' => true, 'min' => 0, 'default' => 0],
                'unit_price' => ['label' => 'Unit price (R)', 'type' => 'number', 'required' => true, 'min' => 0, 'step' => '0.01'],
                'status' => ['label' => 'Product status', 'type' => 'select', 'required' => true, 'options' => ['Active', 'Inactive'], 'default' => 'Active'],
            ],
        ],
    ];

    if (!isset($configs[$entity])) {
        http_response_code(404);
        exit('Unknown module.');
    }
    return array_merge($common, $configs[$entity]);
}

function master_default_record(array $config): array
{
    $record = [];
    foreach ($config['fields'] as $name => $field) {
        $record[$name] = $field['default'] ?? '';
    }
    return $record;
}

function master_relation_options(array $field): array
{
    return all_rows($field['query']);
}

function master_validate(array $config): array
{
    $data = [];
    $errors = [];
    foreach ($config['fields'] as $name => $field) {
        $value = posted($name);
        $type = $field['type'] ?? 'text';

        if (($field['required'] ?? false) && $value === '') {
            $errors[] = $field['label'] . ' is required.';
        }
        if ($value !== '' && ($field['validator'] ?? '') === 'email' && !valid_email_or_blank($value)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if ($value !== '' && ($field['validator'] ?? '') === 'phone' && !valid_phone($value)) {
            $errors[] = 'Please enter a valid South African phone number.';
        }
        if ($value !== '' && $type === 'date' && !is_valid_date($value)) {
            $errors[] = $field['label'] . ' must be a valid date.';
        }
        if ($value !== '' && $type === 'number') {
            if (!is_numeric($value)) {
                $errors[] = $field['label'] . ' must be a number.';
            } elseif (isset($field['min']) && (float) $value < (float) $field['min']) {
                $errors[] = $field['label'] . ' cannot be less than ' . $field['min'] . '.';
            } elseif (isset($field['max']) && (float) $value > (float) $field['max']) {
                $errors[] = $field['label'] . ' cannot exceed ' . $field['max'] . '.';
            }
        }
        if ($type === 'select' && $value !== '' && !in_array($value, $field['options'], true)) {
            $errors[] = 'An invalid value was selected for ' . $field['label'] . '.';
        }
        if ($type === 'relation' && $value !== '' && !ctype_digit($value)) {
            $errors[] = 'Please select a valid ' . strtolower($field['label']) . '.';
        }
        $data[$name] = $value;
    }
    return [$data, $errors];
}

function master_save(string $entity, array $config, int $id = 0): void
{
    require_csrf_or_redirect($entity . '/' . ($id ? 'edit.php?id=' . $id : 'add.php'));
    [$data, $errors] = master_validate($config);
    $record = array_merge(master_default_record($config), $data);

    $serviceIds = [];
    if ($entity === 'staff') {
        foreach ((array) ($_POST['service_ids'] ?? []) as $serviceId) {
            if (ctype_digit((string) $serviceId)) {
                $serviceIds[] = (int) $serviceId;
            }
        }
        $serviceIds = array_values(array_unique($serviceIds));
        if ($serviceIds) {
            $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
            $validServices = (int) scalar("SELECT COUNT(*) FROM services WHERE service_id IN ($placeholders)", $serviceIds);
            if ($validServices !== count($serviceIds)) {
                $errors[] = 'One or more assigned services are invalid.';
            }
        }
    }

    if ($errors) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $record;
        if ($entity === 'staff') {
            $_SESSION['form_services'] = $serviceIds;
        }
        redirect($entity . '/' . ($id ? 'edit.php?id=' . $id : 'add.php'));
    }

    $columns = array_keys($config['fields']);
    try {
        db()->beginTransaction();
        if ($id > 0) {
            $sets = implode(', ', array_map(static fn ($column) => "$column = :$column", $columns));
            $data['id'] = $id;
            $statement = db()->prepare("UPDATE {$config['table']} SET $sets WHERE {$config['pk']} = :id");
            $statement->execute($data);
            $savedId = $id;
        } else {
            $columnSql = implode(', ', $columns);
            $placeholderSql = implode(', ', array_map(static fn ($column) => ':' . $column, $columns));
            $statement = db()->prepare("INSERT INTO {$config['table']} ($columnSql) VALUES ($placeholderSql)");
            $statement->execute($data);
            $savedId = (int) db()->lastInsertId();
        }

        if ($entity === 'staff') {
            $delete = db()->prepare('DELETE FROM staff_services WHERE staff_id = ?');
            $delete->execute([$savedId]);
            if ($serviceIds) {
                $assignment = db()->prepare('INSERT INTO staff_services (staff_id, service_id) VALUES (?, ?)');
                foreach ($serviceIds as $serviceId) {
                    $assignment->execute([$savedId, $serviceId]);
                }
            }
        }
        db()->commit();
        set_flash('success', $config['label'] . ($id ? ' updated successfully.' : ' added successfully.'));
        redirect($entity . '/index.php');
    } catch (PDOException $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $_SESSION['form_errors'] = [database_message($exception, 'Unable to save the ' . strtolower($config['label']) . '.')];
        $_SESSION['form_data'] = $record;
        if ($entity === 'staff') {
            $_SESSION['form_services'] = $serviceIds;
        }
        redirect($entity . '/' . ($id ? 'edit.php?id=' . $id : 'add.php'));
    }
}

function master_render_field(string $name, array $field, mixed $value): void
{
    $type = $field['type'] ?? 'text';
    $required = ($field['required'] ?? false) ? ' required' : '';
    $min = isset($field['min']) ? ' min="' . e($field['min']) . '"' : '';
    $max = isset($field['max']) ? ' max="' . e($field['max']) . '"' : '';
    $step = isset($field['step']) ? ' step="' . e($field['step']) . '"' : '';
    echo '<div class="col-md-6 mb-3"><label class="form-label" for="' . e($name) . '">' . e($field['label']) . '</label>';
    if ($type === 'textarea') {
        echo '<textarea class="form-control" id="' . e($name) . '" name="' . e($name) . '" rows="3"' . $required . '>' . e($value) . '</textarea>';
    } elseif ($type === 'select') {
        echo '<select class="form-select" id="' . e($name) . '" name="' . e($name) . '"' . $required . '><option value="">Choose…</option>';
        foreach ($field['options'] as $option) {
            $selected = (string) $value === (string) $option ? ' selected' : '';
            echo '<option value="' . e($option) . '"' . $selected . '>' . e($option) . '</option>';
        }
        echo '</select>';
    } elseif ($type === 'relation') {
        echo '<select class="form-select" id="' . e($name) . '" name="' . e($name) . '"' . $required . '><option value="">Choose…</option>';
        foreach (master_relation_options($field) as $option) {
            $selected = (string) $value === (string) $option['id'] ? ' selected' : '';
            echo '<option value="' . e($option['id']) . '"' . $selected . '>' . e($option['name']) . '</option>';
        }
        echo '</select>';
    } else {
        $placeholder = !empty($field['placeholder']) ? ' placeholder="' . e($field['placeholder']) . '"' : '';
        $inputType = in_array($type, ['email', 'date', 'number'], true) ? $type : 'text';
        echo '<input class="form-control" id="' . e($name) . '" type="' . e($inputType) . '" name="' . e($name) . '" value="' . e($value) . '"' . $placeholder . $min . $max . $step . $required . '>';
    }
    echo '</div>';
}

$config = master_config($entity);
$active_menu = $entity;

if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect($entity . '/index.php');
    }
    require_csrf_or_redirect($entity . '/index.php');
    $id = post_id($config['pk']);
    if ($id < 1) {
        set_flash('danger', 'Invalid record selected.');
        redirect($entity . '/index.php');
    }
    try {
        $statement = db()->prepare("DELETE FROM {$config['table']} WHERE {$config['pk']} = ?");
        $statement->execute([$id]);
        if ($statement->rowCount() === 0) {
            set_flash('warning', 'That record no longer exists.');
        } else {
            set_flash('success', $config['label'] . ' deleted successfully.');
        }
    } catch (PDOException $exception) {
        set_flash('danger', database_message($exception, 'Unable to delete the ' . strtolower($config['label']) . '.'));
    }
    redirect($entity . '/index.php');
}

if ($action === 'add' || $action === 'edit') {
    $id = $action === 'edit' ? get_id($config['pk']) : 0;
    if ($action === 'edit' && $id < 1) {
        set_flash('danger', 'Invalid record selected.');
        redirect($entity . '/index.php');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        master_save($entity, $config, $id);
    }

    $record = master_default_record($config);
    if ($id > 0) {
        $record = one_row("SELECT * FROM {$config['table']} WHERE {$config['pk']} = ?", [$id]);
        if (!$record) {
            set_flash('danger', 'That record could not be found.');
            redirect($entity . '/index.php');
        }
    }
    if (isset($_SESSION['form_data'])) {
        $record = array_merge($record, $_SESSION['form_data']);
        unset($_SESSION['form_data']);
    }
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['form_errors']);

    $page_title = ($id ? 'Edit ' : 'Add ') . $config['label'];
    require __DIR__ . '/header.php';
    ?>
    <div class="card form-card shadow-sm">
        <div class="card-body p-4">
            <p class="text-secondary">Fields marked as required must be completed before saving.</p>
            <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" novalidate>
                <?= csrf_input() ?>
                <div class="row">
                    <?php foreach ($config['fields'] as $name => $field): master_render_field($name, $field, $record[$name] ?? ''); endforeach; ?>
                    <?php if ($entity === 'staff'):
                        $assigned = $id ? array_column(all_rows('SELECT service_id FROM staff_services WHERE staff_id = ?', [$id]), 'service_id') : [];
                        if (isset($_SESSION['form_services'])) { $assigned = $_SESSION['form_services']; unset($_SESSION['form_services']); }
                        $services = all_rows("SELECT service_id, service_name FROM services WHERE status = 'Active' ORDER BY service_name");
                    ?>
                    <div class="col-12 mb-3">
                        <label class="form-label">Services this staff member can perform</label>
                        <div class="row g-2 border rounded p-3 mx-0">
                            <?php foreach ($services as $service): ?>
                                <div class="col-md-4 form-check ms-2"><input class="form-check-input" type="checkbox" name="service_ids[]" value="<?= (int) $service['service_id'] ?>" id="service<?= (int) $service['service_id'] ?>" <?= in_array((int) $service['service_id'], array_map('intval', $assigned), true) ? 'checked' : '' ?>><label class="form-check-label" for="service<?= (int) $service['service_id'] ?>"><?= e($service['service_name']) ?></label></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text">Only assigned services can be booked with this staff member.</div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2"><button class="btn btn-primary" type="submit">Save <?= e($config['label']) ?></button><a class="btn btn-outline-secondary" href="<?= e(app_url($entity . '/index.php')) ?>">Cancel</a></div>
            </form>
        </div>
    </div>
    <?php require __DIR__ . '/footer.php'; exit;
}

$search = trim((string) ($_GET['q'] ?? ''));
$sql = $config['index_sql'] ?? "SELECT * FROM {$config['table']}";
$params = [];
if ($search !== '') {
    $prefix = $config['search_prefix'] ?? '';
    $conditions = [];
    foreach ($config['search'] as $index => $column) {
        $key = ':search' . $index;
        $conditions[] = $prefix . $column . " LIKE $key";
        $params[$key] = '%' . $search . '%';
    }
    $sql .= ' WHERE ' . implode(' OR ', $conditions);
}
$sql .= ' ORDER BY ' . ($config['index_order'] ?? $config['pk'] . ' DESC');
$rows = all_rows($sql, $params);
$page_title = $config['plural'];
require __DIR__ . '/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <form method="get" class="d-flex gap-2 search-form"><input class="form-control" type="search" name="q" value="<?= e($search) ?>" placeholder="Search <?= e(strtolower($config['plural'])) ?>"><button class="btn btn-outline-primary">Search</button><?php if ($search !== ''): ?><a class="btn btn-outline-secondary" href="<?= e(app_url($entity . '/index.php')) ?>">Clear</a><?php endif; ?></form>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($entity === 'products'): ?><a class="btn btn-outline-primary" href="<?= e(app_url('products/history.php')) ?>">Usage history</a><a class="btn btn-outline-primary" href="<?= e(app_url('products/usage.php')) ?>">Record usage</a><a class="btn btn-outline-primary" href="<?= e(app_url('products/stock.php')) ?>">Add stock</a><?php endif; ?>
        <a class="btn btn-primary" href="<?= e(app_url($entity . '/add.php')) ?>">+ Add <?= e($config['label']) ?></a>
    </div>
</div>
<div class="card shadow-sm table-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr>
    <?php foreach ($config['columns'] as $heading): ?><th><?= e($heading) ?></th><?php endforeach; ?><th class="text-end">Actions</th>
</tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="<?= count($config['columns']) + 1 ?>" class="text-center py-4 text-secondary">No <?= e(strtolower($config['plural'])) ?> found.</td></tr><?php endif; ?>
<?php foreach ($rows as $row): ?><tr>
    <?php foreach ($config['columns'] as $column => $heading): ?><td>
        <?php if (in_array($column, ['status'], true)): ?><?= status_badge((string) $row[$column]) ?>
        <?php elseif (in_array($column, ['price', 'unit_price'], true)): ?><?= money($row[$column]) ?>
        <?php elseif ($column === 'duration_minutes'): ?><?= (int) $row[$column] ?> min
        <?php elseif ($column === 'quantity'): ?><span class="<?= (int) $row['quantity'] <= (int) $row['reorder_level'] ? 'text-danger fw-bold' : '' ?>"><?= (int) $row[$column] ?><?= (int) $row['quantity'] <= (int) $row['reorder_level'] ? ' (Low)' : '' ?></span>
        <?php else: ?><?= e($row[$column] ?? '') ?><?php endif; ?>
    </td><?php endforeach; ?>
    <td class="text-end text-nowrap">
        <?php if ($entity === 'customers'): ?><a class="btn btn-sm btn-outline-info" href="<?= e(app_url('customers/view.php?id=' . $row[$config['pk']])) ?>">History</a><?php endif; ?>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url($entity . '/edit.php?' . $config['pk'] . '=' . $row[$config['pk']])) ?>">Edit</a>
        <form class="d-inline" method="post" action="<?= e(app_url($entity . '/delete.php')) ?>"><input type="hidden" name="<?= e($config['pk']) ?>" value="<?= (int) $row[$config['pk']] ?>"><?= csrf_input() ?><button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Delete this <?= e(strtolower($config['label'])) ?>? This cannot be undone.">Delete</button></form>
    </td>
</tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/footer.php';
