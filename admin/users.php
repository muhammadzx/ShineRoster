<?php
require_once __DIR__ . '/includes/init.php';

$role = inputText($_GET, 'role');
$filters = ['search' => $search, 'role' => $role, 'status' => $status];
$form = ['id' => 0, 'full_name' => '', 'email' => '', 'phone' => '', 'role' => 'customer'];
$editId = positiveId($_GET, 'edit');
if ($editId) {
    $user = $admin->getUser($editId);
    if ($user && $user['role'] !== 'admin') {
        $form = $user;
    } else {
        $error = 'Account not found or protected. Owner details can be changed in My account.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = inputText($_POST, 'action');
    $id = positiveId($_POST, 'id');
    $saved = false;
    if ($action === 'create' || $action === 'save') {
        $form = [
            'id' => $id,
            'full_name' => inputText($_POST, 'full_name'),
            'email' => inputText($_POST, 'email'),
            'phone' => inputText($_POST, 'phone'),
            'role' => inputText($_POST, 'role'),
            'password' => inputValue($_POST, 'password'),
            'confirm_password' => inputValue($_POST, 'confirm_password')
        ];
        if ($action === 'create') {
            $saved = $admin->createUser($form);
        } else {
            $saved = $admin->saveUser($id, $form);
        }
    } elseif ($action === 'activate') {
        $saved = $admin->setUserStatus($id, 1);
    } elseif ($action === 'deactivate') {
        $saved = $admin->setUserStatus($id, 0);
    } elseif ($action === 'delete') {
        $saved = $admin->deleteUser($id);
    } else {
        $error = 'Invalid action.';
    }
    if ($saved) {
        adminSaved('users.php', $filters);
    }
    if ($error === '') {
        $error = $admin->getError();
    }
}
$result = $admin->getUsers($search, $role, $status, $page);

$pageTitle = 'Users';
$pageDescription = 'Manage customer and provider accounts. The owner account stays protected.';
require __DIR__ . '/includes/header.php';
?>
<section class="admin-panel"><div class="admin-section-heading"><h2><?php if ($form['id']) {
    echo 'Edit account';
} else {
    echo 'Add an account';
} ?></h2><?php if ($form['id']): ?><a href="users.php">Cancel edit</a><?php endif; ?></div>
<form method="post" class="admin-form"><input type="hidden" name="id" value="<?= (int) $form[
    'id'
] ?>">
<div class="admin-field"><label for="full_name">Full name</label><input id="full_name" name="full_name" value="<?= escapeHtml(
    $form['full_name']
) ?>" required maxlength="100" autocomplete="name"></div>
<div class="admin-field"><label for="email">Email</label><input id="email" name="email" type="email" value="<?= escapeHtml(
    $form['email']
) ?>" required maxlength="190" autocomplete="email"></div>
<div class="admin-field"><label for="phone">Phone</label><input id="phone" name="phone" type="tel" value="<?= escapeHtml(
    $form['phone']
) ?>" required maxlength="20" autocomplete="tel"></div>
<?php if (!$form['id']): ?>
<div class="admin-field"><label for="role">Account type</label><select id="role" name="role"><option value="customer" <?php if (
    $form['role'] === 'customer'
) {
    echo 'selected';
} ?>>Customer</option><option value="provider" <?php if ($form['role'] === 'provider') {
    echo 'selected';
} ?>>Service provider</option></select></div>
<div class="admin-field"><label for="password">Password</label><input type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required></div>
<div class="admin-field"><label for="confirm_password">Confirm password</label><input type="password" id="confirm_password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required></div>
<?php endif; ?><div class="admin-form-bottom"><button class="admin-button" name="action" value="<?php if (
    $form['id']
) {
    echo 'save';
} else {
    echo 'create';
} ?>">Save account</button><span>Account types cannot be changed after creation.</span></div></form></section>
<form method="get" class="admin-filters"><div class="admin-field"><label for="search">Search</label><input type="search" id="search" name="search" value="<?= escapeHtml(
    $search
) ?>" placeholder="Search records" maxlength="120"></div><div class="admin-field"><label for="filter-role">Type</label><select id="filter-role" name="role"><option value="">All type</option><option value="customer" <?php if (
    $role === 'customer'
) {
    echo 'selected';
} ?>>Customer</option><option value="provider" <?php if ($role === 'provider') {
    echo 'selected';
} ?>>Provider</option><option value="admin" <?php if ($role === 'admin') {
    echo 'selected';
} ?>>Site owner</option></select></div><div class="admin-field"><label for="filter-status">Status</label><select id="filter-status" name="status"><option value="">All status</option><option value="active" <?php if (
    $status === 'active'
) {
    echo 'selected';
} ?>>Active</option><option value="inactive" <?php if ($status === 'inactive') {
    echo 'selected';
} ?>>Inactive</option></select></div><button class="admin-button" type="submit">Search</button><a class="admin-reset" href="<?= escapeHtml(
    $adminPage
) ?>">Reset</a></form><div class="admin-list"><?php if (
    !$result['rows']
): ?><p class="admin-empty">No records match your filters.</p><?php endif; ?>
<?php foreach (
    $result['rows']
    as $user
): ?><article class="admin-record"><div class="admin-record-main"><span class="admin-record-id">Account #<?= (int) $user[
    'id'
] ?></span><h2><?= escapeHtml($user['full_name']) ?></h2><p><?= escapeHtml(
    $user['email']
) ?> · <?= escapeHtml(
     $user['phone']
 ) ?></p><div class="admin-tags"><span class="admin-badge admin-badge-muted"><?= escapeHtml(
    ucfirst($user['role'])
) ?></span><span class="admin-badge <?php if (!$user['is_active']) {
    echo 'admin-badge-muted';
} ?>"><?php if ($user['is_active']) {
    echo 'Active';
} else {
    echo 'Inactive';
} ?></span></div></div><div class="admin-actions">
<?php if (
    $user['role'] !== 'admin'
): ?><a class="admin-button admin-button-light" href="users.php?edit=<?= (int) $user[
    'id'
] ?>">Edit</a><form method="post"><input type="hidden" name="id" value="<?= (int) $user[
    'id'
] ?>"><?php if (
    $user['is_active']
): ?><button class="admin-button admin-button-light" name="action" value="deactivate">Disable</button><?php else: ?><button class="admin-button" name="action" value="activate">Activate</button><?php endif; ?></form><details class="admin-confirm"><summary>Delete</summary><form method="post"><input type="hidden" name="id" value="<?= (int) $user[
    'id'
] ?>"><p>Only accounts without services or booking history can be deleted.</p><button class="admin-button admin-button-danger" name="action" value="delete">Confirm delete</button></form></details><?php else: ?><span class="admin-protected">Protected owner account</span><?php endif; ?></div></article><?php endforeach; ?></div><?php require __DIR__ .
    '/includes/pagination.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
