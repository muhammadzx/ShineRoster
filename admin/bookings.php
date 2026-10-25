<?php
require_once __DIR__ . '/includes/init.php';

$filters = ['search' => $search, 'status' => $status];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($admin->bookingAction(positiveId($_POST, 'id'), inputText($_POST, 'action'))) {
        adminSaved('bookings.php', $filters);
    }
    $error = $admin->getError();
}
$result = $admin->getBookings($search, $status, $page);

$pageTitle = 'Bookings';
$pageDescription =
    'Follow every booking and resolve confirmed visits without deleting their history.';
require __DIR__ . '/includes/header.php';
?>
<form method="get" class="admin-filters"><div class="admin-field"><label for="search">Search</label><input type="search" id="search" name="search" value="<?= escapeHtml(
    $search
) ?>" placeholder="Search records" maxlength="120"></div><div class="admin-field"><label for="filter-status">Status</label><select id="filter-status" name="status"><option value="">All status</option><option value="confirmed" <?php if (
    $status === 'confirmed'
) {
    echo 'selected';
} ?>>Confirmed</option><option value="completed" <?php if ($status === 'completed') {
    echo 'selected';
} ?>>Completed</option><option value="cancelled" <?php if ($status === 'cancelled') {
    echo 'selected';
} ?>>Cancelled</option></select></div><button class="admin-button" type="submit">Search</button><a class="admin-reset" href="<?= escapeHtml(
    $adminPage
) ?>">Reset</a></form><div class="admin-list"><?php
if (!$result['rows']): ?><p class="admin-empty">No records match your filters.</p><?php endif;
foreach (
    $result['rows']
    as $booking
): ?><article class="admin-record"><div class="admin-record-main"><span class="admin-record-id">Booking #<?= (int) $booking[
    'id'
] ?></span><h2><?= escapeHtml($booking['service_name']) ?></h2><p><?= escapeHtml(
    $booking['customer_name']
) ?> → <?= escapeHtml($booking['provider_name']) ?></p><p class="admin-date"><?= escapeHtml(
    date('d M Y, g:i A', strtotime($booking['starts_at']))
) ?> – <?= escapeHtml(
     date('d M Y, g:i A', strtotime($booking['ends_at']))
 ) ?></p><div class="admin-tags"><span class="admin-badge"><?= escapeHtml(
    ucfirst($booking['status'])
) ?></span><span class="admin-badge admin-badge-muted">JOD <?= number_format(
    $booking['total_price'],
    3
) ?></span></div><details class="admin-details"><summary>Booking details</summary><dl class="admin-detail-grid"><div><dt>Customer email</dt><dd><?= escapeHtml(
    $booking['customer_email']
) ?></dd></div><div><dt>Customer phone</dt><dd><?= escapeHtml(
    $booking['customer_phone']
) ?></dd></div><div><dt>City</dt><dd><?= escapeHtml(
    $booking['city']
) ?></dd></div><div><dt>Address</dt><dd><?= escapeHtml(
    $booking['address']
) ?></dd></div><div><dt>Notes</dt><dd class="admin-preserve"><?= escapeHtml(
    $booking['notes']
) ?></dd></div><div><dt>Created</dt><dd><?= escapeHtml(
    $booking['created_at']
) ?></dd></div></dl></details></div><div class="admin-actions"><?php if (
    $booking['status'] === 'confirmed'
):
    if (
        strtotime($booking['ends_at']) <= time()
    ): ?><form method="post"><input type="hidden" name="id" value="<?= (int) $booking[
    'id'
] ?>"><button class="admin-button" name="action" value="complete">Mark completed</button></form><?php endif; ?><details class="admin-confirm"><summary>Cancel booking</summary><form method="post"><input type="hidden" name="id" value="<?= (int) $booking[
    'id'
] ?>"><p>Cancel this confirmed booking for the customer and provider?</p><button class="admin-button admin-button-danger" name="action" value="cancel">Confirm cancellation</button></form></details><?php
else:
     ?><span class="admin-protected">History retained</span><?php
endif; ?></div></article><?php endforeach;
?></div><?php require __DIR__ . '/includes/pagination.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
