<?php
require_once __DIR__ . '/includes/init.php';

$filters = ['search' => $search, 'status' => $status];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($admin->reviewAction(positiveId($_POST, 'id'), inputText($_POST, 'action'))) {
        adminSaved('reviews.php', $filters);
    }
    $error = $admin->getError();
}
$result = $admin->getReviews($search, $status, $page);

$pageTitle = 'Reviews';
$pageDescription =
    'Moderate public feedback. Hidden reviews no longer contribute to public ratings.';
require __DIR__ . '/includes/header.php';
?>
<form method="get" class="admin-filters"><div class="admin-field"><label for="search">Search</label><input type="search" id="search" name="search" value="<?= escapeHtml(
    $search
) ?>" placeholder="Search records" maxlength="120"></div><div class="admin-field"><label for="filter-status">Status</label><select id="filter-status" name="status"><option value="">All status</option><option value="visible" <?php if (
    $status === 'visible'
) {
    echo 'selected';
} ?>>Visible</option><option value="hidden" <?php if ($status === 'hidden') {
    echo 'selected';
} ?>>Hidden</option></select></div><button class="admin-button" type="submit">Search</button><a class="admin-reset" href="<?= escapeHtml(
    $adminPage
) ?>">Reset</a></form><div class="admin-list"><?php
if (!$result['rows']): ?><p class="admin-empty">No records match your filters.</p><?php endif;
foreach (
    $result['rows']
    as $review
): ?><article class="admin-record"><div class="admin-record-main"><span class="admin-record-id">Review #<?= (int) $review[
    'id'
] ?> · Booking #<?= (int) $review['booking_id'] ?></span><h2><?= escapeHtml(
    $review['customer_name']
) ?></h2><p><?= escapeHtml($review['service_name']) ?> · <?= escapeHtml(
     $review['provider_name']
 ) ?></p><div class="admin-tags"><span class="admin-rating"><?= (int) $review[
    'rating'
] ?> / 5 ★</span><span class="admin-badge admin-badge-muted"><?php if ($review['is_visible']) {
     echo 'Visible';
 } else {
     echo 'Hidden';
 } ?></span><span class="admin-meta"><?= escapeHtml(
    date('d M Y', strtotime($review['created_at']))
) ?></span></div><p class="admin-preserve admin-review-text"><?= escapeHtml(
    $review['comment']
) ?></p></div><div class="admin-actions"><form method="post"><input type="hidden" name="id" value="<?= (int) $review[
    'id'
] ?>"><?php if (
    $review['is_visible']
): ?><button class="admin-button admin-button-light" name="action" value="hide">Hide review</button><?php else: ?><button class="admin-button" name="action" value="show">Show review</button><?php endif; ?></form><details class="admin-confirm"><summary>Delete</summary><form method="post"><input type="hidden" name="id" value="<?= (int) $review[
    'id'
] ?>"><p>Delete this review permanently. Hiding it keeps a moderation record.</p><button class="admin-button admin-button-danger" name="action" value="delete">Confirm delete</button></form></details></div></article><?php endforeach;
?></div><?php require __DIR__ . '/includes/pagination.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
