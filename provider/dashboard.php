<?php

require_once __DIR__ . '/../includes/init.php';
$currentUser = $auth->requireRole('provider', '../login.php');

$userId = (int) $currentUser['id'];
$account = $currentUser;
$providerObject = new Provider($db);

$providerProfile = $providerObject->getProfileByUserId((int) $userId);

if (!$providerProfile || trim($providerProfile['city']) === '') {
    header('Location: ../profile.php');
    exit();
}

$providerId = (int) $providerProfile['id'];

$data = $providerObject->getDashboardData($providerId);

$stats = $data['stats'];
$nextBooking = $data['next_booking'];
$recentBookings = $data['recent_bookings'];
$services = $data['services'];
$rating = $data['rating'];
$latestReview = $data['latest_review'];

$pageTitle = 'Overview';
$activePage = 'dashboard';

$nameParts = preg_split('/\s+/u', trim($account['full_name']), -1, PREG_SPLIT_NO_EMPTY);

$firstName = $nameParts[0] ?? $account['full_name'];

$statusClasses = [
    'confirmed' => 'status-confirmed',
    'completed' => 'status-completed',
    'cancelled' => 'status-cancelled'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Dashboard | ShineRoster</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="../images/favicon.ico" sizes="any">
    <link rel="icon" href="../images/favicon.png" type="image/png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body class="dashboard-page">
    <a class="skip-link" href="#dashboard-main">Skip to content</a>
    <div class="dashboard-layout">
        <input
            type="checkbox"
            id="sidebar-toggle"
            class="sidebar-toggle"
            aria-label="Toggle navigation menu"
            aria-controls="provider-sidebar"
        >
        <label
            class="sidebar-overlay"
            for="sidebar-toggle"
            aria-hidden="true"
        ></label>

        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="dashboard-workspace">

            <?php require_once __DIR__ . '/../includes/header.php'; ?>

            <main class="dashboard-main" id="dashboard-main">
                <div class="dashboard-page-heading">
                    <div>
                        <span class="eyebrow">Your business at a glance</span>
                        <h1>
                            Welcome back, <?= escapeHtml($firstName) ?>
                            <span aria-hidden="true">✦</span>
                        </h1>
                        <p>
                            Here’s what’s happening with your cleaning services.
                        </p>
                    </div>
                    <a class="btn btn-primary" href="slots.php">
                        Manage appointments
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>
                <section class="stats-grid" aria-label="Booking summary">
                    <article class="stat-card">
                        <div class="stat-card-top">
                            <span>Total bookings</span>
                            <span class="stat-icon" aria-hidden="true">▣</span>
                        </div>
                        <strong class="stat-value">
                            <?= (int) $stats['total'] ?>
                        </strong>
                        <p>All booking statuses</p>
                    </article>
                    <article class="stat-card">
                        <div class="stat-card-top">
                            <span>Upcoming bookings</span>
                            <span class="stat-icon" aria-hidden="true">▤</span>
                        </div>
                        <strong class="stat-value">
                            <?= (int) $stats['upcoming'] ?>
                        </strong>
                        <p>Confirmed future visits</p>
                    </article>
                    <article class="stat-card">
                        <div class="stat-card-top">
                            <span>Completed bookings</span>
                            <span class="stat-icon" aria-hidden="true">✓</span>
                        </div>
                        <strong class="stat-value">
                            <?= (int) $stats['completed'] ?>
                        </strong>
                        <p>Finished cleaning visits</p>
                    </article>
                    <article class="stat-card">
                        <div class="stat-card-top">
                            <span>Cancelled bookings</span>
                            <span class="stat-icon" aria-hidden="true">×</span>
                        </div>
                        <strong class="stat-value">
                            <?= (int) $stats['cancelled'] ?>
                        </strong>
                        <p>Cancelled reservations</p>
                    </article>
                </section>
                <div class="dashboard-content-grid">
                    <section
                        class="next-visit"
                        aria-labelledby="next-visit-title"
                    >
                        <div class="next-visit-top">
                            <span class="next-visit-label">
                                <span aria-hidden="true">●</span>
                                Next scheduled visit
                            </span>

                            <?php if ($nextBooking): ?>
                                <span class="status-badge status-confirmed">
                                    Confirmed
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($nextBooking): ?>
                            <?php
                            $nextStart = new DateTimeImmutable($nextBooking['starts_at']);

                            $nextEnd = new DateTimeImmutable($nextBooking['ends_at']);
                            ?>

                            <h2 id="next-visit-title">
                                <?= escapeHtml($nextBooking['service_name']) ?>
                            </h2>
                            <p class="next-visit-customer">
                                For <?= escapeHtml($nextBooking['customer_name']) ?>
                                · Booking #<?= (int) $nextBooking['id'] ?>
                            </p>
                            <dl class="visit-details">
                                <div>
                                    <dt>Date</dt>
                                    <dd>
                                        <time datetime="<?= $nextStart->format('Y-m-d') ?>">
                                            <?= $nextStart->format('d M Y') ?>
                                        </time>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Time</dt>
                                    <dd>
                                        <?= $nextStart->format('h:i A') ?>
                                        –
                                        <?= $nextEnd->format('h:i A') ?>

                                        <?php if (
                                            $nextStart->format('Y-m-d') !==
                                            $nextEnd->format('Y-m-d')
                                        ): ?>
                                            <br>
                                            Ends <?= $nextEnd->format('d M Y') ?>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Location</dt>
                                    <dd>
                                        <?= escapeHtml($nextBooking['city']) ?>
                                    </dd>
                                </div>
                            </dl>
                            <div class="next-visit-bottom">
                                <p>All times are in Jordan local time.</p>
                                <a
                                    class="btn btn-white btn-small"
                                    href="bookings.php"
                                >
                                    View bookings
                                    <span aria-hidden="true">↗</span>
                                </a>
                            </div>

                        <?php else: ?>

                            <h2 id="next-visit-title">
                                No upcoming bookings
                            </h2>
                            <p class="next-visit-customer">
                                Keep your services active and add available
                                appointments so customers can book.
                            </p>
                            <div class="next-visit-bottom">
                                <p>Get ready for your next customer.</p>
                                <a
                                    class="btn btn-white btn-small"
                                    href="slots.php"
                                >
                                    Manage appointments
                                    <span aria-hidden="true">↗</span>
                                </a>
                            </div>

                        <?php endif; ?>

                    </section>
                    <section
                        class="dashboard-card quick-actions"
                        aria-labelledby="quick-actions-title"
                    >
                        <div class="card-heading">
                            <div>
                                <h2 id="quick-actions-title">Quick actions</h2>
                                <p>Keep your workspace ready.</p>
                            </div>
                        </div>
                        <a class="quick-action" href="services.php">
                            <span class="quick-action-icon" aria-hidden="true">✦</span>
                            <span class="quick-action-text">
                                <strong>Manage services</strong>
                                <small>Update your offers and prices</small>
                            </span>
                            <span class="quick-action-arrow" aria-hidden="true">↗</span>
                        </a>
                        <a class="quick-action" href="slots.php">
                            <span class="quick-action-icon" aria-hidden="true">▤</span>
                            <span class="quick-action-text">
                                <strong>Manage availability</strong>
                                <small>Add or update appointments</small>
                            </span>
                            <span class="quick-action-arrow" aria-hidden="true">↗</span>
                        </a>
                        <a class="quick-action" href="../profile.php">
                            <span class="quick-action-icon" aria-hidden="true">◎</span>
                            <span class="quick-action-text">
                                <strong>Edit your profile</strong>
                                <small>Refresh your bio and skills</small>
                            </span>
                            <span class="quick-action-arrow" aria-hidden="true">↗</span>
                        </a>
                    </section>
                </div>
                <section
                    class="dashboard-card bookings-card"
                    aria-labelledby="recent-bookings-title"
                >
                    <div class="card-heading">
                        <div>
                            <h2 id="recent-bookings-title">Recent bookings</h2>
                            <p>Your most recently created reservations.</p>
                        </div>
                        <a class="text-link" href="bookings.php">
                            View all
                            <span aria-hidden="true">↗</span>
                        </a>
                    </div>
                    <div
                        class="table-scroll"
                        role="region"
                        aria-labelledby="recent-bookings-title"
                        tabindex="0"
                    >
                        <table class="dashboard-table">
                            <caption class="visually-hidden">
                                Your latest bookings and their current status.
                            </caption>
                            <thead>
                                <tr>
                                    <th scope="col">Booking</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Service</th>
                                    <th scope="col">Appointment</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php if (!$recentBookings): ?>
                                    <tr>
                                        <td colspan="6">
                                            You have no bookings yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($recentBookings as $item): ?>
                                    <?php
                                    $start = new DateTimeImmutable($item['starts_at']);

                                    $end = new DateTimeImmutable($item['ends_at']);

                                    $statusClass = $statusClasses[$item['status']] ?? '';
                                    ?>

                                    <tr>
                                        <th scope="row">
                                            #<?= (int) $item['id'] ?>
                                        </th>
                                        <td>
                                            <?= escapeHtml($item['customer_name']) ?>
                                        </td>
                                        <td>
                                            <?= escapeHtml($item['service_name']) ?>
                                        </td>
                                        <td>
                                            <time datetime="<?= $start->format('Y-m-d') ?>">
                                                <?= $start->format('d M Y') ?>
                                            </time>
                                            <span class="table-secondary">
                                                <?= $start->format('h:i A') ?>
                                                –
                                                <?= $end->format('h:i A') ?>

                                                <?php if (
                                                    $start->format('Y-m-d') !==
                                                    $end->format('Y-m-d')
                                                ): ?>
                                                    <br>
                                                    Ends <?= $end->format('d M Y') ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td class="table-price">
                                            JOD <?= number_format( (float) $item['total_price'], 3 ) ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= escapeHtml( $statusClass ) ?>">
                                                <?= escapeHtml(ucfirst($item['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>
                </section>
                <div class="dashboard-content-grid">
                    <section
                        class="dashboard-card"
                        aria-labelledby="services-overview-title"
                    >
                        <div class="card-heading">
                            <div>
                                <h2 id="services-overview-title">Your services</h2>
                                <p>Your most recently added services.</p>
                            </div>
                            <a class="text-link" href="services.php">
                                Manage
                                <span aria-hidden="true">↗</span>
                            </a>
                        </div>
                        <div class="service-overview-list">

                            <?php if (!$services): ?>
                                <p class="dashboard-empty">
                                    No services yet.
                                    <a href="services.php">Add your first service ↗</a>
                                </p>
                            <?php endif; ?>

                            <?php foreach ($services as $service): ?>

                                <article class="service-overview-item">
                                    <span
                                        class="service-overview-icon"
                                        aria-hidden="true"
                                    >
                                        ✦
                                    </span>
                                    <div class="service-overview-info">
                                        <h3>
                                            <?= escapeHtml($service['name']) ?>
                                        </h3>
                                        <p>
                                            <?= (int) $service['duration_minutes'] ?>
                                            minutes · JOD
                                            <?= number_format((float) $service['price'], 3) ?>
                                        </p>
                                    </div>
                                    <span class="status-badge <?= (int) $service['is_active'] ? 'status-completed' : 'dashboard-status-inactive' ?>">
                                        <?= (int) $service['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </article>

                            <?php endforeach; ?>

                        </div>
                    </section>
                    <section
                        class="dashboard-card reviews-summary"
                        aria-labelledby="reviews-title"
                    >
                        <div class="card-heading">
                            <div>
                                <h2 id="reviews-title">Customer feedback</h2>
                                <p>From completed bookings.</p>
                            </div>
                            <span class="review-heading-icon" aria-hidden="true">
                                ☆
                            </span>
                        </div>

                        <?php if ((int) $rating['review_count'] > 0): ?>

                            <div class="rating-summary">
                                <strong>
                                    <?= number_format( (float) $rating['average_rating'], 1 ) ?><span>/ 5</span>
                                </strong>
                                <div>
                                    <span class="rating-stars" aria-hidden="true">
                                        ★
                                    </span>
                                    <p>
                                        Based on <?= (int) $rating['review_count'] ?>
                                        review(s)
                                    </p>
                                </div>
                            </div>

                            <?php if ($latestReview): ?>
                                <blockquote class="review-quote">
                                    <p>
                                        <?= escapeHtml( trim($latestReview['comment'] ?? '') !== '' ? $latestReview['comment'] : 'The customer left a rating without a comment.' ) ?>
                                    </p>
                                    <footer>
                                        <span class="review-avatar" aria-hidden="true">
                                            ☆
                                        </span>
                                        <div>
                                            <strong>
                                                <?= escapeHtml($latestReview['customer_name']) ?>
                                            </strong>
                                            <span>
                                                Rated <?= (int) $latestReview['rating'] ?>/5
                                                ·
                                                <?= (new DateTimeImmutable( $latestReview['created_at'] ))->format('d M Y') ?>
                                            </span>
                                        </div>
                                    </footer>
                                </blockquote>
                            <?php endif; ?>

                        <?php else: ?>

                            <div class="dashboard-empty">
                                <strong>No reviews yet</strong>
                                <p>
                                    Customer ratings will appear here
                                    after completed bookings are reviewed.
                                </p>
                            </div>

                        <?php endif; ?>

                    </section>
                </div>
            </main>

            <?php require_once __DIR__ . '/../includes/footer.php'; ?>

        </div>
    </div>
</body>
</html>