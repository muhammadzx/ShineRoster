<?php
require_once __DIR__ . '/../includes/init.php';
$currentUser = $auth->requireRole('provider', '../login.php');
$userId = (int) $currentUser['id'];
$providerObject = new Provider($db);
$providerProfile = $providerObject->getProfileByUserId($userId);
if (!$providerProfile) {
    header('Location: ../profile.php');
    exit();
}
$providerId = (int) $providerProfile['id'];
$booking = new Booking($db);
$pageTitle = 'Bookings';
$activePage = 'bookings';
$error = '';
$success = inputText($_SESSION, 'bookings_success');
unset($_SESSION['bookings_success']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = positiveId($_POST, 'booking_id');
    $action = inputText($_POST, 'action');
    $saved = false;
    if ($action === 'complete') {
        $saved = $booking->complete($id, $providerId);
    } elseif ($action === 'cancel') {
        $saved = $booking->cancel($id, $providerId);
    } else {
        $error = 'Invalid action.';
    }
    if ($saved) {
        $_SESSION['bookings_success'] = 'Booking updated successfully.';
        header('Location: bookings.php', true, 303);
        exit();
    }
    if ($error === '') {
        $error = $booking->getError();
    }
}
$search = inputText($_GET, 'search');
$status = inputText($_GET, 'status');
$date = inputText($_GET, 'date');
if (!in_array($status, ['', 'confirmed', 'completed', 'cancelled'])) {
    $status = '';
}
if ($date !== '' && (!strtotime($date) || date('Y-m-d', strtotime($date)) !== $date)) {
    $date = '';
    $error = 'Please choose a valid appointment date.';
}
$stats = $booking->getStats($providerId);
$bookings = $booking->getByProvider($providerId, $search, $status, $date);
$now = time();
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
    <title>Bookings | ShineRoster</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="../images/favicon.ico" sizes="any">
    <link rel="icon" href="../images/favicon.png" type="image/png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/provider-bookings.css">
</head>
<body class="dashboard-page">
    <a class="skip-link" href="#dashboard-main">Skip to content</a>
    <div class="dashboard-layout">
        <input type="checkbox" id="sidebar-toggle" class="sidebar-toggle" aria-label="Toggle navigation menu"
            aria-controls="provider-sidebar">
        <label class="sidebar-overlay" for="sidebar-toggle" aria-hidden="true"></label>

        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="dashboard-workspace">

            <?php require_once __DIR__ . '/../includes/header.php'; ?>

            <main class="dashboard-main" id="dashboard-main">
                <div class="dashboard-page-heading">
                    <div>
                        <span class="eyebrow">Every visit, in one place</span>
                        <h1>Bookings</h1>
                        <p>
                            Review customer details and manage your cleaning visits.
                        </p>
                    </div>
                    <a class="btn btn-primary" href="slots.php">
                        Manage appointments
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>

                <?php if ($success !== ''): ?>
                    <div class="booking-alert booking-alert-success" role="status">
                        <?= escapeHtml($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="booking-alert booking-alert-error" role="alert">
                        <?= escapeHtml($error) ?>
                    </div>
                <?php endif; ?>

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
                <section class="dashboard-card booking-filter-panel" aria-labelledby="booking-filter-title">
                    <div class="card-heading">
                        <div>
                            <h2 id="booking-filter-title">Find a booking</h2>
                            <p>Search by customer, service, or booking number.</p>
                        </div>
                    </div>
                    <form class="booking-filters" action="bookings.php" method="get">
                        <div class="booking-field">
                            <label for="booking-search">Search</label>
                            <input type="search" id="booking-search" name="search" placeholder="Name, service, or #1042"
                                value="<?= escapeHtml($search) ?>" maxlength="120">
                        </div>
                        <div class="booking-field">
                            <label for="booking-status">Status</label>
                            <select id="booking-status" name="status">
                                <option value="">All statuses</option>
                                <option value="confirmed" <?php if ($status === 'confirmed') {
                                    echo 'selected';
                                } else {
                                    echo '';
                                } ?>>
                                    Confirmed
                                </option>
                                <option value="completed" <?php if ($status === 'completed') {
                                    echo 'selected';
                                } else {
                                    echo '';
                                } ?>>
                                    Completed
                                </option>
                                <option value="cancelled" <?php if ($status === 'cancelled') {
                                    echo 'selected';
                                } else {
                                    echo '';
                                } ?>>
                                    Cancelled
                                </option>
                            </select>
                        </div>
                        <div class="booking-field">
                            <label for="booking-date">Appointment date</label>
                            <input type="date" id="booking-date" name="date" value="<?= escapeHtml(
                                $date
                            ) ?>">
                        </div>
                        <div class="booking-filter-actions">
                            <button class="btn btn-primary" type="submit">
                                Search
                            </button>
                            <a class="btn btn-secondary" href="bookings.php">
                                Reset
                            </a>
                        </div>
                    </form>
                </section>
                <div class="booking-results-heading">
                    <h2>Your reservations</h2>
                    <span><?= count($bookings) ?> result(s)</span>
                </div>

                <?php if (!$bookings): ?>

                    <section class="dashboard-card booking-empty">
                        <span aria-hidden="true">▣</span>
                        <h2>No bookings found</h2>
                        <p>
                            Try different filters, or check that your active
                            services have available appointments.
                        </p>
                        <a class="btn btn-primary" href="slots.php">
                            Manage appointments
                        </a>
                    </section>

                <?php else: ?>

                    <div class="booking-list">

                        <?php foreach ($bookings as $item): ?>
                            <?php
                            $start = strtotime($item['starts_at']);
                            $end = strtotime($item['ends_at']);

                            $confirmed = $item['status'] === 'confirmed';

                            $canCancel = $confirmed && $start > $now;
                            $canComplete = $confirmed && $end <= $now;

                            if (isset($statusClasses[$item['status']])) {
                                $statusClass = $statusClasses[$item['status']];
                            } else {
                                $statusClass = '';
                            }

                            $bookingNumber = (int) $item['id'];
                            ?>

                            <article class="dashboard-card booking-card" aria-labelledby="booking-title-<?= $bookingNumber ?>">
                                <div class="booking-card-heading">
                                    <div>
                                        <span class="booking-number">
                                            Booking #<?= $bookingNumber ?>
                                        </span>
                                        <h2 id="booking-title-<?= $bookingNumber ?>">
                                            <?= escapeHtml($item['service_name']) ?>
                                        </h2>
                                        <p class="booking-customer">
                                            For <?= escapeHtml($item['customer_name']) ?>
                                        </p>
                                    </div>
                                    <span class="status-badge <?= escapeHtml($statusClass) ?>">
                                        <?= escapeHtml(ucfirst($item['status'])) ?>
                                    </span>
                                </div>
                                <dl class="booking-overview">
                                    <div>
                                        <dt>Appointment</dt>
                                        <dd>
                                            <?= escapeHtml(date('d M Y', $start)) ?>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt>Time · Jordan</dt>
                                        <dd>
                                            <?= escapeHtml(date('h:i A', $start)) ?>
                                            –
                                            <?= escapeHtml(date('h:i A', $end)) ?>

                                            <?php if (
                                                date('Y-m-d', $start) !== date('Y-m-d', $end)
                                            ): ?>
                                                <span class="booking-small">
                                                    Ends <?= escapeHtml(date('d M Y', $end)) ?>
                                                </span>
                                            <?php endif; ?>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt>City</dt>
                                        <dd><?= escapeHtml($item['city']) ?></dd>
                                    </div>
                                    <div>
                                        <dt>Booked price</dt>
                                        <dd>
                                            JOD <?= number_format(
                                                (float) $item['total_price'],
                                                3
                                            ) ?>
                                        </dd>
                                    </div>
                                </dl>
                                <details class="booking-details">
                                    <summary>
                                        Customer & booking details
                                        <span aria-hidden="true">+</span>
                                    </summary>
                                    <div class="booking-details-content">
                                        <dl class="booking-detail-grid">
                                            <div>
                                                <dt>Customer</dt>
                                                <dd>
                                                    <?= escapeHtml($item['customer_name']) ?>
                                                </dd>
                                            </div>
                                            <div>
                                                <dt>Phone number</dt>
                                                <dd>
                                                    <?= escapeHtml($item['customer_phone']) ?>
                                                </dd>
                                            </div>
                                            <div>
                                                <dt>Email address</dt>
                                                <dd>
                                                    <?= escapeHtml($item['customer_email']) ?>
                                                </dd>
                                            </div>
                                            <div>
                                                <dt>Booking created</dt>
                                                <dd>
                                                    <?= escapeHtml(
                                                        date(
                                                            'd M Y, h:i A',
                                                            strtotime($item['created_at'])
                                                        )
                                                    ) ?>
                                                </dd>
                                            </div>
                                            <div class="booking-detail-wide">
                                                <dt>Home address</dt>
                                                <dd>
                                                    <?= escapeHtml($item['address']) ?>
                                                </dd>
                                            </div>
                                            <div class="booking-detail-wide">
                                                <dt>Customer notes</dt>
                                                <dd class="booking-notes"><?php if (
                                                    inputText($item, 'notes') !== ''
                                                ) {
                                                    echo escapeHtml($item['notes']);
                                                } else {
                                                    echo escapeHtml('No additional notes.');
                                                } ?></dd>
                                            </div>
                                        </dl>

                                        <?php if ($item['completed_at']): ?>
                                            <p class="booking-record">
                                                Completed:
                                                <?= escapeHtml(
                                                    date(
                                                        'd M Y, h:i A',
                                                        strtotime($item['completed_at'])
                                                    )
                                                ) ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if ($item['cancelled_at']): ?>
                                            <p class="booking-record">
                                                Cancelled:
                                                <?= escapeHtml(
                                                    date(
                                                        'd M Y, h:i A',
                                                        strtotime($item['cancelled_at'])
                                                    )
                                                ) ?>
                                            </p>
                                        <?php endif; ?>

                                    </div>
                                </details>
                                <div class="booking-card-bottom">
                                    <p class="booking-action-hint">
                                        <?php if ($canCancel): ?>
                                            Cancellation is available before the visit starts.

                                        <?php elseif ($canComplete): ?>
                                            The scheduled visit has ended.
                                            Mark it completed after the service is delivered.

                                        <?php elseif ($confirmed): ?>
                                            This visit has started.
                                            Completion is available after its scheduled end.

                                        <?php else: ?>
                                            This booking is <?= escapeHtml($item['status']) ?>.
                                            Its history is retained.
                                        <?php endif; ?>
                                    </p>

                                    <?php if ($canComplete || $canCancel): ?>

                                        <details class="booking-confirm">
                                            <summary class="booking-action-button <?php if (
                                                $canCancel
                                            ) {
                                                echo 'booking-action-danger';
                                            } else {
                                                echo '';
                                            } ?>">
                                                <?php if ($canComplete) {
                                                    echo 'Mark completed';
                                                } else {
                                                    echo 'Cancel booking';
                                                } ?>
                                            </summary>
                                            <form class="booking-confirm-form" action="bookings.php" method="post">
                                                
                                                <input type="hidden" name="booking_id" value="<?= $bookingNumber ?>">
                                                <input type="hidden" name="action" value="<?php if (
                                                    $canComplete
                                                ) {
                                                    echo 'complete';
                                                } else {
                                                    echo 'cancel';
                                                } ?>">
                                                <p>
                                                    <?php if ($canComplete): ?>
                                                        Confirm that this cleaning
                                                        service was delivered.
                                                    <?php else: ?>
                                                        Cancel this booking?
                                                        This action cannot be undone.
                                                    <?php endif; ?>
                                                </p>
                                                <button class="booking-action-button <?php if (
                                                    $canCancel
                                                ) {
                                                    echo 'booking-action-danger';
                                                } else {
                                                    echo '';
                                                } ?>" type="submit">
                                                    <?php if ($canComplete) {
                                                        echo 'Confirm completion';
                                                    } else {
                                                        echo 'Confirm cancellation';
                                                    } ?>
                                                </button>
                                            </form>
                                        </details>

                                    <?php endif; ?>

                                </div>
                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </main>

            <?php require_once __DIR__ . '/../includes/footer.php'; ?>

        </div>
    </div>
</body>
</html>