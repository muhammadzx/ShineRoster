<?php
require_once __DIR__ . '/includes/init.php';
$overview = $admin->getOverview();
$stats = $overview['stats'];
$pageTitle = 'Overview';
$pageDescription = 'A clear view of your community, bookings and service activity.';
require __DIR__ . '/includes/header.php';
?>

<section class="admin-welcome"><div><span class="admin-kicker">Your platform at a glance</span><h2>Welcome back, <?= escapeHtml(
    $currentUser['full_name']
) ?>.</h2><p>Keep the community organized and help every cleaning visit run smoothly.</p></div><a class="admin-button admin-button-white" href="bookings.php?status=confirmed">Review confirmed bookings ↗</a></section>
<div class="admin-stats">
    <a class="admin-stat" href="users.php"><span>Total accounts</span><strong><?= (int) $stats[
        'users'
    ] ?></strong><small><?= (int) $stats['customers'] ?> customers · <?= (int) $stats[
     'providers'
 ] ?> providers</small></a>
    <a class="admin-stat" href="services.php"><span>Active services</span><strong><?= (int) $stats[
        'active_services'
    ] ?></strong><small>Out of <?= (int) $stats['services'] ?> total services</small></a>
    <a class="admin-stat" href="bookings.php"><span>Total bookings</span><strong><?= (int) $stats[
        'bookings'
    ] ?></strong><small><?= (int) $stats['confirmed'] ?> currently confirmed</small></a>
    <div class="admin-stat"><span>Completed booking value</span><strong class="admin-money">JOD <?= number_format(
        $stats['completed_value'],
        3
    ) ?></strong><small>Total value of completed visits</small></div>
</div>
<div class="admin-stats admin-stats-three">
    <a class="admin-stat admin-stat-small" href="bookings.php?status=completed"><span>Completed visits</span><strong><?= (int) $stats[
        'completed'
    ] ?></strong></a>
    <a class="admin-stat admin-stat-small" href="reviews.php"><span>Customer reviews</span><strong><?= (int) $stats[
        'reviews'
    ] ?></strong></a>
    <a class="admin-stat admin-stat-small" href="messages.php"><span>Contact messages</span><strong><?= (int) $stats[
        'messages'
    ] ?></strong></a>
</div>
<div class="admin-two-columns">
<section class="admin-panel"><div class="admin-section-heading"><h2>Recent bookings</h2><a href="bookings.php">View all ↗</a></div>
<?php if (
    !$overview['recent_bookings']
): ?><p class="admin-empty">No bookings yet.</p><?php endif; ?>
<?php foreach ($overview['recent_bookings'] as $booking): ?>
<a class="admin-preview" href="bookings.php?search=<?= (int) $booking[
    'id'
] ?>"><div><strong>#<?= (int) $booking['id'] ?> · <?= escapeHtml(
     $booking['service_name']
 ) ?></strong><span><?= escapeHtml($booking['customer_name']) ?> · <?= escapeHtml(
     $booking['provider_name']
 ) ?></span><small><?= escapeHtml(
    date('d M Y, g:i A', strtotime($booking['starts_at']))
) ?></small></div><span class="admin-badge"><?= escapeHtml(
    ucfirst($booking['status'])
) ?></span></a>
<?php endforeach; ?></section>
<section class="admin-panel"><div class="admin-section-heading"><h2>Latest messages</h2><a href="messages.php">Open inbox ↗</a></div>
<?php if (
    !$overview['latest_messages']
): ?><p class="admin-empty">Your inbox is empty.</p><?php endif; ?>
<?php foreach (
    $overview['latest_messages']
    as $message
): ?><a class="admin-preview" href="messages.php?search=<?= escapeHtml(
    rawurlencode($message['email'])
) ?>"><div><strong><?= escapeHtml($message['full_name']) ?></strong><span><?= escapeHtml(
    ucfirst($message['subject'])
) ?></span><small><?= escapeHtml(
    date('d M Y', strtotime($message['created_at']))
) ?></small></div><span aria-hidden="true">↗</span></a><?php endforeach; ?>
</section></div>

<?php require __DIR__ . '/includes/footer.php'; ?>
