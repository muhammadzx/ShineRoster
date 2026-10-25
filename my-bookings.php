<?php

require_once __DIR__ . '/includes/init.php';

$currentAccount = $auth->requireRole('customer', 'login.php');
$customerId = (int) $currentAccount['id'];

$search = mb_substr(inputText($_GET, 'search'), 0, 120, 'UTF-8');

$status = inputText($_GET, 'status');

if (!in_array($status, ['', 'confirmed', 'completed', 'cancelled'], true)) {
    $status = '';
}

if (isset($_GET['page'])) {
    $page = filter_var($_GET['page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
} else {
    $page = filter_var(null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
}

if ($page === false) {
    $page = 1;
} else {
    $page = max(1, (int) $page);
}

function pageUrl($targetPage, $search, $status)
{
    $query = ['page' => $targetPage];

    if ($search !== '') {
        $query['search'] = $search;
    }

    if ($status !== '') {
        $query['status'] = $status;
    }

    return 'my-bookings.php?' . http_build_query($query);
}

$customerBookings = new CustomerBookings($db);

$error = '';
if (isset($_SESSION['booking_success'])) {
    $success = $_SESSION['booking_success'];
} else {
    $success = '';
}

unset($_SESSION['booking_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (inputText($_POST, 'action') !== 'cancel') {
        $error = 'Invalid action.';
    } elseif ($customerBookings->cancel($customerId, positiveId($_POST, 'booking_id'))) {
        $_SESSION['booking_success'] = 'Your booking has been cancelled.';
        header('Location: my-bookings.php', true, 303);
        exit();
    } else {
        $error = $customerBookings->getError();
    }
}

$stats = $customerBookings->getStats($customerId);

$result = $customerBookings->getBookings($customerId, $search, $status, $page);

$bookings = $result['bookings'];
$total = $result['total'];
$page = $result['page'];
$pages = $result['pages'];
$perPage = $result['per_page'];

if ($total > 0) {
    $firstResult = ($page - 1) * $perPage + 1;
} else {
    $firstResult = 0;
}

$lastResult = min($page * $perPage, $total);

$statusLabels = [
    'confirmed' => 'Confirmed',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>My Bookings | ShineRoster</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="images/favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/services.css">
    <link rel="stylesheet" href="css/my-bookings.css">
</head>
<body class="services-page my-bookings-page">
    <a class="catalog-skip-link" href="#my-bookings-main">
        Skip to bookings
    </a>

    

    <?php require __DIR__ . '/includes/catalog-header.php'; ?>

    <main id="my-bookings-main">
        <section class="my-bookings-hero">
            <div class="catalog-container">
                <nav
                    class="catalog-breadcrumb"
                    aria-label="Breadcrumb"
                >
                    <a href="index.php">Home</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">My bookings</span>
                </nav>
                <div class="my-bookings-heading-row">
                    <div>
                        <span class="catalog-eyebrow">
                            Your home, your schedule
                        </span>
                        <h1>Your cleaning visits, together.</h1>
                        <p>
                            Keep track of upcoming appointments,
                            revisit past bookings and manage your plans.
                        </p>
                    </div>
                    <a
                        class="catalog-button catalog-button-primary"
                        href="services.php"
                    >
                        Book a new clean
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>
            </div>
        </section>
        <div class="catalog-container my-bookings-content">

            

            <?php if ($success !== ''): ?>
                <div
                    class="my-bookings-alert my-bookings-alert-success"
                    role="status"
                >
                    <span aria-hidden="true">✓</span>
                    <p><?= escapeHtml($success) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div
                    class="my-bookings-alert my-bookings-alert-error"
                    role="alert"
                >
                    <span aria-hidden="true">!</span>
                    <p><?= escapeHtml($error) ?></p>
                </div>
            <?php endif; ?>

            <section
                class="my-bookings-stats"
                aria-label="Your booking statistics"
            >
                <article class="my-bookings-stat">
                    <div>
                        <span>Total bookings</span>
                        <span aria-hidden="true">▣</span>
                    </div>
                    <strong><?= $stats['total'] ?></strong>
                    <p>All your cleaning visits</p>
                </article>
                <article class="my-bookings-stat">
                    <div>
                        <span>Upcoming visits</span>
                        <span aria-hidden="true">▦</span>
                    </div>
                    <strong><?= $stats['upcoming'] ?></strong>
                    <p>Confirmed future appointments</p>
                </article>
                <article class="my-bookings-stat">
                    <div>
                        <span>Completed visits</span>
                        <span aria-hidden="true">✓</span>
                    </div>
                    <strong><?= $stats['completed'] ?></strong>
                    <p>Finished cleaning visits</p>
                </article>
                <article class="my-bookings-stat">
                    <div>
                        <span>Cancelled bookings</span>
                        <span aria-hidden="true">×</span>
                    </div>
                    <strong><?= $stats['cancelled'] ?></strong>
                    <p>Plans that changed</p>
                </article>
            </section>
            <form
                class="my-bookings-filters"
                action="my-bookings.php"
                method="get"
            >
                <div class="catalog-filter-field">
                    <label for="booking-search">
                        Find a booking
                    </label>
                    <input
                        type="search"
                        id="booking-search"
                        name="search"
                        value="<?= escapeHtml($search) ?>"
                        maxlength="120"
                        placeholder="Service, provider, city or #booking number"
                    >
                </div>
                <div class="catalog-filter-field">
                    <label for="booking-status">
                        Booking status
                    </label>
                    <select id="booking-status" name="status">
                        <option value="">
                            All statuses
                        </option>
                        <option
                            value="confirmed"
                            <?php if ($status === 'confirmed') {
                                echo 'selected';
                            } else {
                                echo '';
                            } ?>
                        >
                            Confirmed
                        </option>
                        <option
                            value="completed"
                            <?php if ($status === 'completed') {
                                echo 'selected';
                            } else {
                                echo '';
                            } ?>
                        >
                            Completed
                        </option>
                        <option
                            value="cancelled"
                            <?php if ($status === 'cancelled') {
                                echo 'selected';
                            } else {
                                echo '';
                            } ?>
                        >
                            Cancelled
                        </option>
                    </select>
                </div>
                <button
                    class="catalog-button catalog-button-primary"
                    type="submit"
                >
                    Search
                    <span aria-hidden="true">↗</span>
                </button>
                <a class="catalog-reset" href="my-bookings.php">
                    Reset
                </a>
            </form>
            <div class="catalog-results-heading">
                <div>
                    <span class="catalog-eyebrow">
                        A little more organised
                    </span>
                    <h2>Your bookings</h2>
                    <p>
                        <?php if ($total > 0): ?>

                            Showing <?= $firstResult ?>–<?= $lastResult ?>
                            of <?= $total ?>
                            <?php if ($total === 1) {
                                echo 'booking';
                            } else {
                                echo 'bookings';
                            } ?>.

                        <?php else: ?>

                            No bookings match this view.

                        <?php endif; ?>
                    </p>
                </div>
                <span class="catalog-results-count">
                    <?= $total ?>
                    <?php if ($total === 1) {
                        echo 'booking';
                    } else {
                        echo 'bookings';
                    } ?>
                </span>
            </div>

            <?php if ($bookings): ?>

                <div class="my-bookings-list">

                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $bookingId = (int) $booking['id'];

                        $startsAt = strtotime($booking['starts_at']);

                        $endsAt = strtotime($booking['ends_at']);

                        $createdAt = strtotime($booking['created_at']);

                        $canCancel = (int) $booking['can_cancel'] === 1;
                        $hasReview = $booking['review_id'] !== null;

                        $bookingStatus = $booking['status'];

                        $bookAgainUrl =
                            'services.php?' .
                            http_build_query([
                                'search' => $booking['service_name']
                            ]);
                        ?>

                        <article class="my-booking-card">
                            <div class="my-booking-top">
                                <div>
                                    <span class="my-booking-number">
                                        Booking #<?= $bookingId ?>
                                    </span>
                                    <h3 dir="auto">
                                        <?= escapeHtml($booking['service_name']) ?>
                                    </h3>
                                    <p class="my-booking-provider" dir="auto">
                                        With <?= escapeHtml($booking['provider_name']) ?>
                                    </p>
                                </div>
                                <span
                                    class="my-booking-badge my-booking-badge-<?= escapeHtml(
                                        $bookingStatus
                                    ) ?>"
                                >
                                    <?= escapeHtml($statusLabels[$bookingStatus]) ?>
                                </span>
                            </div>
                            <dl class="my-booking-facts">
                                <div>
                                    <dt>Appointment</dt>
                                    <dd>
                                        <?= escapeHtml(date('d M Y', $startsAt)) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Time · Jordan</dt>
                                    <dd>
                                        <?= escapeHtml(date('g:i A', $startsAt)) ?>

                                        –

                                        <?= escapeHtml(date('g:i A', $endsAt)) ?>

                                        <?php if (
                                            date('Y-m-d', $startsAt) !== date('Y-m-d', $endsAt)
                                        ): ?>
                                            <small>
                                                Ends <?= escapeHtml(date('d M Y', $endsAt)) ?>
                                            </small>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt>City</dt>
                                    <dd dir="auto">
                                        <?= escapeHtml($booking['city']) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Booked service price</dt>
                                    <dd class="my-booking-price">
                                        JOD <?= number_format((float) $booking['total_price'], 3) ?>
                                    </dd>
                                </div>
                            </dl>
                            <details class="my-booking-details">
                                <summary>
                                    Address & booking details
                                    <span aria-hidden="true">+</span>
                                </summary>
                                <div class="my-booking-details-body">
                                    <div>
                                        <h4>Home address</h4>
                                        <p dir="auto">
                                            <?= nl2br(escapeHtml($booking['address'])) ?>
                                        </p>
                                    </div>

                                    <?php if (trim((string) $booking['notes']) !== ''): ?>
                                        <div>
                                            <h4>Your notes</h4>
                                            <p dir="auto">
                                                <?= nl2br(escapeHtml($booking['notes'])) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <h4>Booked on</h4>
                                        <p>
                                            <?= escapeHtml(date('d M Y · g:i A', $createdAt)) ?>
                                        </p>
                                    </div>

                                    <?php if ($booking['cancelled_at']): ?>
                                        <div>
                                            <h4>Cancelled on</h4>
                                            <p>
                                                <?= escapeHtml(
                                                    date(
                                                        'd M Y · g:i A',
                                                        strtotime($booking['cancelled_at'])
                                                    )
                                                ) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($booking['completed_at']): ?>
                                        <div>
                                            <h4>Marked as completed</h4>
                                            <p>
                                                <?= escapeHtml(
                                                    date(
                                                        'd M Y · g:i A',
                                                        strtotime($booking['completed_at'])
                                                    )
                                                ) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </details>
                            <div class="my-booking-bottom">

                                <?php if ($bookingStatus === 'confirmed'): ?>

                                    <?php if ($canCancel): ?>

                                        <p class="my-booking-status-note">
                                            You can cancel before the
                                            scheduled start time.
                                        </p>
                                        <details class="my-booking-cancel">
                                            <summary>
                                                Cancel booking
                                            </summary>
                                            <form
                                                class="my-booking-cancel-form"
                                                action="<?= escapeHtml(
                                                    pageUrl($page, $search, $status)
                                                ) ?>"
                                                method="post"
                                            >
                                                
                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="cancel"
                                                >
                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?= $bookingId ?>"
                                                >
                                                <p>
                                                    Cancel booking
                                                    #<?= $bookingId ?>?
                                                    This action cannot
                                                    be undone.
                                                </p>
                                                <button
                                                    class="my-booking-cancel-button"
                                                    type="submit"
                                                >
                                                    Yes, cancel booking
                                                </button>
                                            </form>
                                        </details>

                                    <?php else: ?>

                                        <p class="my-booking-status-note">
                                            The cancellation window has closed
                                            because the appointment has started.
                                        </p>

                                    <?php endif; ?>

                                <?php elseif ($bookingStatus === 'completed'): ?>

                                    <?php if ($hasReview): ?>

                                        <span class="my-booking-reviewed">
                                            <span aria-hidden="true">★</span>

                                            Review submitted:
                                            <?= (int) $booking['review_rating'] ?>/5
                                        </span>

                                    <?php else: ?>

                                        <p class="my-booking-status-note">
                                            How was your visit?
                                            Share your experience.
                                        </p>
                                        <a
                                            class="catalog-button catalog-button-secondary"
                                            href="review.php?booking_id=<?= $bookingId ?>"
                                        >
                                            Leave a review
                                            <span aria-hidden="true">☆</span>
                                        </a>

                                    <?php endif; ?>

                                <?php else: ?>

                                    <p class="my-booking-status-note">
                                        This booking was cancelled.
                                        You can explore another cleaning visit.
                                    </p>
                                    <a
                                        class="catalog-button catalog-button-secondary"
                                        href="<?= escapeHtml($bookAgainUrl) ?>"
                                    >
                                        Explore services
                                        <span aria-hidden="true">↗</span>
                                    </a>

                                <?php endif; ?>

                            </div>
                        </article>

                    <?php endforeach; ?>

                </div>

                

                <?php if ($pages > 1): ?>
                    <nav
                        class="catalog-pagination"
                        aria-label="Bookings pagination"
                    >

                        <?php if ($page > 1): ?>
                            <a href="<?= escapeHtml(pageUrl($page - 1, $search, $status)) ?>">
                                <span aria-hidden="true">←</span>
                                Previous
                            </a>
                        <?php endif; ?>

                        <span
                            class="catalog-page-current"
                            aria-current="page"
                        >
                            Page <?= $page ?> of <?= $pages ?>
                        </span>

                        <?php if ($page < $pages): ?>
                            <a href="<?= escapeHtml(pageUrl($page + 1, $search, $status)) ?>">
                                Next
                                <span aria-hidden="true">→</span>
                            </a>
                        <?php endif; ?>

                    </nav>
                <?php endif; ?>

            <?php else: ?>

                <div class="catalog-empty">
                    <span
                        class="catalog-empty-icon"
                        aria-hidden="true"
                    >
                        ▦
                    </span>

                    <?php if ($search !== '' || $status !== ''): ?>

                        <h3>No matching bookings</h3>
                        <p>
                            Try another search or clear the filters
                            to see all your bookings.
                        </p>
                        <a
                            class="catalog-button catalog-button-primary"
                            href="my-bookings.php"
                        >
                            Show all bookings
                            <span aria-hidden="true">↗</span>
                        </a>

                    <?php else: ?>

                        <h3>Your next fresh start is waiting</h3>
                        <p>
                            You haven’t booked a cleaning visit yet.
                            Explore services and find a provider
                            that fits your home.
                        </p>
                        <a
                            class="catalog-button catalog-button-primary"
                            href="services.php"
                        >
                            Find your first clean
                            <span aria-hidden="true">↗</span>
                        </a>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

            <aside class="catalog-help">
                <span aria-hidden="true">?</span>
                <div>
                    <h2>Need help with a booking?</h2>
                    <p>
                        Send us your booking number and let us
                        know what you need help with.
                    </p>
                </div>
                <a
                    class="catalog-button catalog-button-secondary"
                    href="index.php#contact"
                >
                    Contact us
                    <span aria-hidden="true">↗</span>
                </a>
            </aside>
        </div>
    </main>

    

    <?php require __DIR__ . '/includes/catalog-footer.php'; ?>

</body>
</html>