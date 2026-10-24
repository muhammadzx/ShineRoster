<?php

require_once __DIR__ . '/includes/init.php';

$currentAccount = $auth->requireRole('customer', 'login.php');

$slotId = filter_var($_GET['slot_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

$slotId = $slotId === false ? 0 : (int) $slotId;

$checkout = new BookingCheckout($db);

$appointment = $slotId > 0 ? $checkout->getAppointment($slotId) : null;

$form = [
    'address' => inputText($_POST, 'address'),
    'notes' => inputText($_POST, 'notes')
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!validCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(403);

            throw new RuntimeException(
                'The form expired. Please review your details and try again.'
            );
        }

        if (!$appointment) {
            http_response_code(409);

            throw new RuntimeException(
                'This appointment is no longer available. ' . 'Please choose another time.'
            );
        }

        $quote = $_SESSION['booking_quotes'][$slotId] ?? [];

        if (!is_array($quote)) {
            $quote = [];
        }

        $bookingId = $checkout->confirm(
            (int) $currentAccount['id'],
            $slotId,
            $form['address'],
            $form['notes'],
            $quote
        );

        unset($_SESSION['booking_quotes'][$slotId]);

        $_SESSION['booking_success'] = 'Booking #' . $bookingId . ' confirmed successfully.';

        header('Location: my-bookings.php', true, 303);
        exit();
    } catch (PDOException $e) {
        error_log((string) $e);

        http_response_code(500);

        $error = 'Your booking could not be saved. Please try again.';
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }

    $appointment = $slotId > 0 ? $checkout->getAppointment($slotId) : null;
}

if ($appointment) {
    if (!isset($_SESSION['booking_quotes']) || !is_array($_SESSION['booking_quotes'])) {
        $_SESSION['booking_quotes'] = [];
    }

    $_SESSION['booking_quotes'][$slotId] = $appointment;

    $_SESSION['booking_quotes'] = array_slice($_SESSION['booking_quotes'], -5, null, true);

    $startsAt = new DateTimeImmutable($appointment['starts_at']);
    $endsAt = new DateTimeImmutable($appointment['ends_at']);

    $durationMinutes = (int) (($endsAt->getTimestamp() - $startsAt->getTimestamp()) / 60);
} else {
    unset($_SESSION['booking_quotes'][$slotId]);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(404);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Confirm Your Booking | ShineRoster</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="images/favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/services.css">
    <link rel="stylesheet" href="css/booking.css">
</head>
<body class="services-page booking-page">
    <a class="catalog-skip-link" href="#booking-main">
        Skip to booking
    </a>

    

    <?php require __DIR__ . '/includes/catalog-header.php'; ?>

    <main id="booking-main">
        <section class="booking-heading">
            <div class="catalog-container">
                <nav
                    class="catalog-breadcrumb"
                    aria-label="Breadcrumb"
                >
                    <a href="index.php">Home</a>
                    <span aria-hidden="true">/</span>
                    <a href="services.php">Services</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">
                        Confirm booking
                    </span>
                </nav>
                <span class="catalog-eyebrow">
                    A fresh start is nearly yours
                </span>
                <h1>Confirm your cleaning visit.</h1>
                <p>
                    Review your appointment and tell your provider
                    where to find you.
                </p>
            </div>
        </section>
        <div class="catalog-container booking-container">

            <?php if ($error !== ''): ?>
                <div class="booking-alert" role="alert">
                    <span aria-hidden="true">!</span>
                    <div>
                        <strong>We couldn’t confirm your booking.</strong>
                        <p><?= escapeHtml($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$appointment): ?>

                <div class="catalog-empty">
                    <span
                        class="catalog-empty-icon"
                        aria-hidden="true"
                    >
                        ▦
                    </span>
                    <h2>This appointment is unavailable</h2>
                    <p>
                        It may have been booked, closed or changed.
                        Please explore the available services and
                        choose another appointment.
                    </p>
                    <a
                        class="catalog-button catalog-button-primary"
                        href="services.php"
                    >
                        Explore services
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>

            <?php else: ?>

                <div class="booking-layout">
                    <div class="booking-content">
                        <section
                            class="booking-panel"
                            aria-labelledby="customer-title"
                        >
                            <div class="booking-panel-heading">
                                <span
                                    class="booking-panel-icon"
                                    aria-hidden="true"
                                >
                                    ◎
                                </span>
                                <div>
                                    <span class="catalog-eyebrow">
                                        Your customer account
                                    </span>
                                    <h2 id="customer-title">
                                        Contact details
                                    </h2>
                                </div>
                            </div>
                            <dl class="booking-customer-details">
                                <div>
                                    <dt>Full name</dt>
                                    <dd dir="auto">
                                        <?= escapeHtml($currentAccount['full_name']) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Email address</dt>
                                    <dd>
                                        <?= escapeHtml($currentAccount['email']) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Phone number</dt>
                                    <dd>
                                        <?= escapeHtml($currentAccount['phone']) ?>
                                    </dd>
                                </div>
                            </dl>
                        </section>
                        <form
                            class="booking-panel booking-form"
                            action="booking.php?slot_id=<?= $slotId ?>"
                            method="post"
                            aria-labelledby="address-title"
                        >
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= escapeHtml(csrfToken()) ?>"
                            >
                            <div class="booking-panel-heading">
                                <span
                                    class="booking-panel-icon"
                                    aria-hidden="true"
                                >
                                    ⌖
                                </span>
                                <div>
                                    <span class="catalog-eyebrow">
                                        A place for your fresh start
                                    </span>
                                    <h2 id="address-title">
                                        Where is your home?
                                    </h2>
                                </div>
                            </div>
                            <p class="booking-form-intro">
                                Add a clear address so your provider
                                can find the right place.
                            </p>
                            <div class="booking-field">
                                <label for="booking-city">
                                    Service city
                                </label>
                                <input
                                    type="text"
                                    id="booking-city"
                                    value="<?= escapeHtml($appointment['city']) ?>"
                                    aria-describedby="booking-city-hint"
                                    readonly
                                >
                                <p
                                    class="booking-field-hint"
                                    id="booking-city-hint"
                                >
                                    This appointment is offered in
                                    <?= escapeHtml($appointment['city']) ?>.
                                </p>
                            </div>
                            <div class="booking-field">
                                <label for="booking-address">
                                    Detailed address
                                    <span aria-hidden="true">*</span>
                                </label>
                                <textarea
                                    id="booking-address"
                                    name="address"
                                    rows="4"
                                    minlength="10"
                                    maxlength="255"
                                    autocomplete="street-address"
                                    placeholder="Area, street, building number, floor and apartment"
                                    aria-describedby="booking-address-hint"
                                    required
                                ><?= escapeHtml($form['address']) ?></textarea>
                                <p
                                    class="booking-field-hint"
                                    id="booking-address-hint"
                                >
                                    Include the area, street, building
                                    and apartment. Between 10 and 255 characters.
                                </p>
                            </div>
                            <div class="booking-field">
                                <label for="booking-notes">
                                    Notes for your provider
                                    <span class="booking-optional">
                                        (optional)
                                    </span>
                                </label>
                                <textarea
                                    id="booking-notes"
                                    name="notes"
                                    rows="4"
                                    maxlength="2000"
                                    placeholder="Access instructions or details your provider should know"
                                    aria-describedby="booking-notes-hint"
                                ><?= escapeHtml($form['notes']) ?></textarea>
                                <p
                                    class="booking-field-hint"
                                    id="booking-notes-hint"
                                >
                                    Up to 2,000 characters.
                                </p>
                            </div>
                            <div class="booking-information">
                                <span aria-hidden="true">✓</span>
                                <p>
                                    Your appointment will be reserved
                                    after you confirm. You can cancel
                                    a confirmed booking before its
                                    scheduled start time.
                                </p>
                            </div>
                            <div class="booking-form-actions">
                                <a
                                    class="catalog-button catalog-button-secondary"
                                    href="service-details.php?id=<?= (int) $appointment[ 'service_id' ] ?>"
                                >
                                    <span aria-hidden="true">←</span>
                                    Change appointment
                                </a>
                                <button
                                    class="catalog-button catalog-button-primary"
                                    type="submit"
                                >
                                    Confirm booking
                                    <span aria-hidden="true">✓</span>
                                </button>
                            </div>
                        </form>
                    </div>
                    <aside
                        class="booking-summary"
                        aria-labelledby="summary-title"
                    >
                        <span class="catalog-eyebrow">
                            Your upcoming visit
                        </span>
                        <h2 id="summary-title">
                            Booking summary
                        </h2>
                        <div class="booking-summary-service">
                            <span
                                class="booking-summary-icon"
                                aria-hidden="true"
                            >
                                ✦
                            </span>
                            <div>
                                <h3 dir="auto">
                                    <?= escapeHtml($appointment['service_name']) ?>
                                </h3>
                                <p dir="auto">
                                    <?= escapeHtml($appointment['provider_name']) ?>
                                </p>
                            </div>
                        </div>
                        <dl class="booking-summary-details">
                            <div>
                                <dt>Date</dt>
                                <dd>
                                    <?= escapeHtml($startsAt->format('l, d M Y')) ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Start time</dt>
                                <dd>
                                    <?= escapeHtml($startsAt->format('g:i A')) ?>
                                </dd>
                            </div>
                            <div>
                                <dt>End time</dt>
                                <dd>
                                    <?= escapeHtml($endsAt->format('g:i A')) ?>

                                    <?php if (
                                        $startsAt->format('Y-m-d') !== $endsAt->format('Y-m-d')
                                    ): ?>
                                        <span class="booking-end-date">
                                            <?= escapeHtml($endsAt->format('d M Y')) ?>
                                        </span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Duration</dt>
                                <dd>
                                    <?= $durationMinutes ?> minutes
                                </dd>
                            </div>
                            <div>
                                <dt>City</dt>
                                <dd dir="auto">
                                    <?= escapeHtml($appointment['city']) ?>
                                </dd>
                            </div>
                        </dl>
                        <p class="booking-timezone">
                            All times are shown in Jordan time.
                        </p>
                        <div class="booking-total">
                            <span>Total service price</span>
                            <strong>
                                <small>JOD</small>

                                <?= number_format((float) $appointment['price'], 3) ?>
                            </strong>
                        </div>
                        <p class="booking-price-note">
                            Price for this appointment.
                            Review all details before confirming.
                        </p>
                        <div class="booking-summary-help">
                            <span aria-hidden="true">?</span>
                            <p>
                                Need help?
                                <a href="index.php#contact">
                                    Contact us
                                </a>
                            </p>
                        </div>
                    </aside>
                </div>

            <?php endif; ?>

        </div>
    </main>

    

    <?php require __DIR__ . '/includes/catalog-footer.php'; ?>

</body>
</html>