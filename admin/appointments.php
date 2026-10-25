<?php
require_once __DIR__ . '/includes/init.php';

$serviceId = positiveId($_GET, 'service_id');
$filters = ['search' => $search, 'status' => $status, 'service_id' => $serviceId];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($admin->appointmentAction(positiveId($_POST, 'id'), inputText($_POST, 'action'))) {
        adminSaved('appointments.php', $filters);
    }
    $error = $admin->getError();
}
$result = $admin->getAppointments($search, $status, $serviceId, $page);

$pageTitle = 'Appointments';
$pageDescription =
    'Inspect provider availability, open or close future appointments, and remove unused entries.';
require __DIR__ . '/includes/header.php';
?>
<form method="get" class="admin-filters"><div class="admin-field"><label for="search">Search</label><input type="search" id="search" name="search" value="<?= escapeHtml(
    $search
) ?>" placeholder="Search records" maxlength="120"></div><div class="admin-field"><label for="filter-status">Status</label><select id="filter-status" name="status"><option value="">All status</option><option value="available" <?php if (
    $status === 'available'
) {
    echo 'selected';
} ?>>Available</option><option value="reserved" <?php if ($status === 'reserved') {
    echo 'selected';
} ?>>Reserved</option><option value="closed" <?php if ($status === 'closed') {
    echo 'selected';
} ?>>Closed</option><option value="past" <?php if ($status === 'past') {
    echo 'selected';
} ?>>Past / started</option></select></div><input type="hidden" name="service_id" value="<?= (int) $serviceId ?>"><button class="admin-button" type="submit">Search</button><a class="admin-reset" href="<?= escapeHtml(
    $adminPage
) ?>">Reset</a></form><?php if (
    $serviceId
): ?><p class="admin-note">Filtered to service #<?= (int) $serviceId ?>. <a href="appointments.php">Show all services</a></p><?php endif; ?><div class="admin-list"><?php
if (!$result['rows']): ?><p class="admin-empty">No records match your filters.</p><?php endif;
foreach (
    $result['rows']
    as $slot
): ?><article class="admin-record"><div class="admin-record-main"><span class="admin-record-id">Appointment #<?= (int) $slot[
    'id'
] ?></span><h2><?= escapeHtml($slot['service_name']) ?></h2><p><?= escapeHtml(
    $slot['provider_name']
) ?> · <?= escapeHtml($slot['city']) ?></p><p class="admin-date"><?= escapeHtml(
    date('d M Y, g:i A', strtotime($slot['starts_at']))
) ?> – <?= escapeHtml(
     date('d M Y, g:i A', strtotime($slot['ends_at']))
 ) ?></p><div class="admin-tags"><?php
if ($slot['available']): ?><span class="admin-badge">Available</span><?php endif;
if ($slot['reserved']): ?><span class="admin-badge admin-badge-warn">Reserved</span><?php endif;
if (!$slot['is_open']): ?><span class="admin-badge admin-badge-muted">Closed</span><?php endif;
if ($slot['past']): ?><span class="admin-badge admin-badge-muted">Past / started</span><?php endif;
if (
    !$slot['available'] &&
    !$slot['reserved'] &&
    $slot['is_open'] &&
    !$slot['past']
): ?><span class="admin-badge admin-badge-muted">Not publicly available</span><?php endif;
?></div></div><div class="admin-actions"><?php
if (!$slot['past']): ?><form method="post"><input type="hidden" name="id" value="<?= (int) $slot[
    'id'
] ?>"><?php if (
    $slot['is_open']
): ?><button class="admin-button admin-button-light" name="action" value="close">Close</button><?php else: ?><button class="admin-button" name="action" value="open">Open</button><?php endif; ?></form><?php endif;
if (
    !$slot['history']
): ?><details class="admin-confirm"><summary>Delete</summary><form method="post"><input type="hidden" name="id" value="<?= (int) $slot[
    'id'
] ?>"><p>Permanently remove this unused appointment.</p><button class="admin-button admin-button-danger" name="action" value="delete">Confirm delete</button></form></details><?php else: ?><span class="admin-protected">Booking history retained</span><?php endif;
?></div></article><?php endforeach;
?></div><?php require __DIR__ . '/includes/pagination.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
