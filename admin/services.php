<?php
require_once __DIR__ . '/includes/init.php';

$filters = ['search' => $search, 'status' => $status];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = positiveId($_POST, 'id');
    $action = inputText($_POST, 'action');
    $saved = false;
    if ($action === 'activate') {
        $saved = $admin->setServiceStatus($id, 1);
    } elseif ($action === 'deactivate') {
        $saved = $admin->setServiceStatus($id, 0);
    } elseif ($action === 'delete') {
        $saved = $admin->deleteService($id);
    } else {
        $error = 'Invalid action.';
    }
    if ($saved) {
        adminSaved('services.php', $filters);
    }
    if ($error === '') {
        $error = $admin->getError();
    }
}
$result = $admin->getServices($search, $status, $page);

$pageTitle = 'Services';
$pageDescription =
    'Review service listings and manage their visibility. Providers create and edit their services.';
require __DIR__ . '/includes/header.php';
?>
<form method="get" class="admin-filters"><div class="admin-field"><label for="search">Search</label><input type="search" id="search" name="search" value="<?= escapeHtml(
    $search
) ?>" placeholder="Search records" maxlength="120"></div><div class="admin-field"><label for="filter-status">Status</label><select id="filter-status" name="status"><option value="">All status</option><option value="active" <?php if (
    $status === 'active'
) {
    echo 'selected';
} ?>>Active</option><option value="inactive" <?php if ($status === 'inactive') {
    echo 'selected';
} ?>>Inactive</option></select></div><button class="admin-button" type="submit">Search</button><a class="admin-reset" href="<?= escapeHtml(
    $adminPage
) ?>">Reset</a></form><div class="admin-list"><?php
if (!$result['rows']): ?><p class="admin-empty">No records match your filters.</p><?php endif;
foreach (
    $result['rows']
    as $service
): ?><article class="admin-record"><div class="admin-record-main"><span class="admin-record-id">Service #<?= (int) $service[
    'id'
] ?></span><h2><?= escapeHtml($service['name']) ?></h2><p><?= escapeHtml(
    $service['provider_name']
) ?> · <?= escapeHtml(
     $service['city']
 ) ?></p><div class="admin-tags"><span class="admin-badge <?php if (!$service['is_active']) {
    echo 'admin-badge-muted';
} ?>"><?php if ($service['is_active']) {
    echo 'Active';
} else {
    echo 'Inactive';
} ?></span><span class="admin-badge admin-badge-muted">JOD <?= number_format(
    $service['price'],
    3
) ?></span><span class="admin-badge admin-badge-muted"><?= (int) $service[
    'duration_minutes'
] ?> minutes</span><?php if (
     !$service['provider_active']
 ): ?><span class="admin-badge admin-badge-warn">Provider disabled</span><?php endif; ?></div><details class="admin-details"><summary>Read description</summary><p class="admin-preserve"><?= escapeHtml(
    $service['description']
) ?></p></details></div><div class="admin-actions"><form method="post"><input type="hidden" name="id" value="<?= (int) $service[
    'id'
] ?>"><?php if (
    $service['is_active']
): ?><button class="admin-button admin-button-light" name="action" value="deactivate">Disable</button><?php else: ?><button class="admin-button" name="action" value="activate">Activate</button><?php endif; ?></form><a class="admin-button admin-button-light" href="appointments.php?service_id=<?= (int) $service[
    'id'
] ?>">Appointments</a><details class="admin-confirm"><summary>Delete</summary><form method="post"><input type="hidden" name="id" value="<?= (int) $service[
    'id'
] ?>"><p>A service can only be deleted when it has no appointments.</p><button class="admin-button admin-button-danger" name="action" value="delete">Confirm delete</button></form></details></div></article><?php endforeach;
?></div><?php require __DIR__ . '/includes/pagination.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
