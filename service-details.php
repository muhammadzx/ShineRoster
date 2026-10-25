<?php

require_once __DIR__ . '/includes/init.php';

$currentAccount = $auth->currentUser();
if (isset($currentAccount['role'])) {
    $accountRole = $currentAccount['role'];
} else {
    $accountRole = '';
}

if (isset($_GET['id'])) {
    $serviceId = filter_var($_GET['id'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
} else {
    $serviceId = filter_var(null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
}

$details = new ServiceDetails($db);

if ($serviceId) {
    $service = $details->getService((int) $serviceId);
} else {
    $service = null;
}

$skills = [];
$appointments = [];
$reviews = [];

if ($service) {
    $skills = $details->getProviderSkills((int) $service['provider_id']);

    $appointments = $details->getAvailableAppointments((int) $service['id']);

    $reviews = $details->getReviews((int) $service['id']);
} else {
    http_response_code(404);
}

if ($service) {
    $pageTitle = $service['name'] . ' | ShineRoster';
} else {
    $pageTitle = 'Service not found | ShineRoster';
}

if ($service) {
    $providerInitial = mb_strtoupper(mb_substr($service['provider_name'], 0, 1, 'UTF-8'), 'UTF-8');
} else {
    $providerInitial = '';
}

if ($service) {
    $providerReviewCount = (int) $service['review_count'];
} else {
    $providerReviewCount = 0;
}

$canBook = $accountRole === 'customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title><?= escapeHtml($pageTitle) ?></title>
    <meta
        name="description"
        content="<?php if ($service) {
            echo escapeHtml(
                'Explore ' .
                    $service['name'] .
                    ' by ' .
                    $service['provider_name'] .
                    '. View service details, reviews and available appointments.'
            );
        } else {
            echo escapeHtml('The requested cleaning service could not be found.');
        } ?>"
    >

    <?php if (!$service): ?>
        <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>

    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="images/favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/services.css">
    <link rel="stylesheet" href="css/service-details.css">
</head>
<body class="services-page service-details-page">
    <a class="catalog-skip-link" href="#details-main">
        Skip to service details
    </a>

    

    <?php require __DIR__ . '/includes/catalog-header.php'; ?>

    <main id="details-main">

        <?php if (!$service): ?>

            <section class="details-not-found">
                <div class="catalog-container">
                    <div class="catalog-empty">
                        <span
                            class="catalog-empty-icon"
                            aria-hidden="true"
                        >
                            ⌕
                        </span>
                        <h1>Service not found</h1>
                        <p>
                            This service may be unavailable or no longer
                            listed. Explore other cleaning services
                            to find the right fit.
                        </p>
                        <a
                            class="catalog-button catalog-button-primary"
                            href="services.php"
                        >
                            Explore services
                            <span aria-hidden="true">↗</span>
                        </a>
                    </div>
                </div>
            </section>

        <?php else: ?>

            <section
                class="details-hero"
                aria-labelledby="service-title"
            >
                <div class="catalog-container">
                    <nav
                        class="catalog-breadcrumb"
                        aria-label="Breadcrumb"
                    >
                        <a href="index.php">Home</a>
                        <span aria-hidden="true">/</span>
                        <a href="services.php">Services</a>
                        <span aria-hidden="true">/</span>
                        <span aria-current="page" dir="auto">
                            <?= escapeHtml($service['name']) ?>
                        </span>
                    </nav>
                    <div class="details-heading-row">
                        <div class="details-heading-copy">
                            <span class="catalog-eyebrow">
                                A little help for your home
                            </span>
                            <h1 id="service-title" dir="auto">
                                <?= escapeHtml($service['name']) ?>
                            </h1>
                            <div class="details-heading-meta">
                                <span>
                                    <span aria-hidden="true">⌖</span>
                                    <span dir="auto">
                                        <?= escapeHtml($service['city']) ?>
                                    </span>
                                </span>
                                <span>
                                    <span aria-hidden="true">◷</span>

                                    <?= (int) $service['duration_minutes'] ?>
                                    minutes
                                </span>
                                <span>
                                    <span aria-hidden="true">◎</span>
                                    <span dir="auto">
                                        <?= escapeHtml($service['provider_name']) ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                        <a
                            class="catalog-button catalog-button-secondary"
                            href="services.php"
                        >
                            <span aria-hidden="true">←</span>
                            All services
                        </a>
                    </div>
                </div>
            </section>
            <div class="catalog-container details-layout">
                <div class="details-content">
                    <section
                        class="details-panel"
                        aria-labelledby="about-service-title"
                    >
                        <div class="details-panel-heading">
                            <span
                                class="details-panel-icon"
                                aria-hidden="true"
                            >
                                ✦
                            </span>
                            <div>
                                <span class="catalog-eyebrow">
                                    Know your clean
                                </span>
                                <h2 id="about-service-title">
                                    About this service
                                </h2>
                            </div>
                        </div>
                        <div class="details-description" dir="auto">
                            <?= nl2br(escapeHtml($service['description'])) ?>
                        </div>
                        <div class="details-service-facts">
                            <div>
                                <span>Service price</span>
                                <strong>
                                    JOD <?= number_format((float) $service['price'], 3) ?>
                                </strong>
                            </div>
                            <div>
                                <span>Service duration</span>
                                <strong>
                                    <?= (int) $service['duration_minutes'] ?>
                                    minutes
                                </strong>
                            </div>
                            <div>
                                <span>City</span>
                                <strong dir="auto">
                                    <?= escapeHtml($service['city']) ?>
                                </strong>
                            </div>
                        </div>
                    </section>
                    <section
                        class="details-panel"
                        aria-labelledby="service-provider-title"
                    >
                        <div class="details-provider-heading">
                            <span
                                class="details-provider-avatar"
                                aria-hidden="true"
                            >
                                <?= escapeHtml($providerInitial) ?>
                            </span>
                            <div>
                                <span class="catalog-eyebrow">
                                    Your cleaning provider
                                </span>
                                <h2 id="service-provider-title" dir="auto">
                                    <?= escapeHtml($service['provider_name']) ?>
                                </h2>
                                <p class="details-provider-city" dir="auto">
                                    <?= escapeHtml($service['city']) ?>
                                </p>
                            </div>
                        </div>
                        <div class="details-provider-rating">

                            <?php if ($providerReviewCount > 0): ?>

                                <strong>
                                    <span aria-hidden="true">★</span>

                                    <?= number_format((float) $service['average_rating'], 1) ?>
                                </strong>
                                <span>
                                    Based on <?= $providerReviewCount ?>
                                    <?php if ($providerReviewCount === 1) {
                                        echo 'provider review';
                                    } else {
                                        echo 'provider reviews';
                                    } ?>
                                </span>

                            <?php else: ?>

                                <strong>New provider</strong>
                                <span>No reviews yet</span>

                            <?php endif; ?>

                        </div>

                        <?php if (trim((string) $service['bio']) !== ''): ?>
                            <p class="details-provider-bio" dir="auto">
                                <?= nl2br(escapeHtml($service['bio'])) ?>
                            </p>
                        <?php endif; ?>

                        <div class="details-provider-skills">
                            <h3>Listed cleaning skills</h3>
                            <div class="catalog-skills">

                                <?php if ($skills): ?>

                                    <?php foreach ($skills as $skill): ?>
                                        <span dir="auto">
                                            <?= escapeHtml($skill['name']) ?>
                                        </span>
                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <span class="catalog-skill-empty">
                                        No skills listed
                                    </span>

                                <?php endif; ?>

                            </div>
                        </div>
                        <a
                            class="details-text-link"
                            href="provider-details.php?id=<?= (int) $service['provider_id'] ?>"
                        >
                            View provider profile
                            <span aria-hidden="true">↗</span>
                        </a>
                    </section>
                    <section
                        class="details-panel"
                        id="appointments"
                        aria-labelledby="appointments-title"
                    >
                        <div class="details-panel-heading">
                            <span
                                class="details-panel-icon"
                                aria-hidden="true"
                            >
                                ▦
                            </span>
                            <div>
                                <span class="catalog-eyebrow">
                                    Make time for a fresh start
                                </span>
                                <h2 id="appointments-title">
                                    Available appointments
                                </h2>
                            </div>
                        </div>
                        <p class="details-section-intro">
                            Choose an available appointment.
                            All dates and times are shown in Jordan time.
                        </p>

                        <?php if ($appointments): ?>

                            <form
                                class="details-booking-form"
                                action="booking.php"
                                method="get"
                            >
                                <fieldset class="details-appointment-fieldset">
                                    <legend>
                                        Choose your preferred appointment
                                    </legend>
                                    <div class="details-appointments-grid">

                                        <?php foreach ($appointments as $appointment): ?>
                                            <?php
                                            $slotId = (int) $appointment['slot_id'];

                                            $startsAt = strtotime($appointment['starts_at']);

                                            $endsAt = strtotime($appointment['ends_at']);
                                            ?>

                                            <label class="details-slot">
                                                <input
                                                    class="details-slot-radio"
                                                    type="radio"
                                                    name="slot_id"
                                                    value="<?= $slotId ?>"
                                                    required
                                                >
                                                <span class="details-slot-body">
                                                    <span class="details-slot-day">
                                                        <?= escapeHtml(date('l', $startsAt)) ?>
                                                    </span>
                                                    <strong class="details-slot-date">
                                                        <?= escapeHtml(date('d M Y', $startsAt)) ?>
                                                    </strong>
                                                    <span class="details-slot-time">
                                                        <?= escapeHtml(date('g:i A', $startsAt)) ?>

                                                        –

                                                        <?= escapeHtml(date('g:i A', $endsAt)) ?>
                                                    </span>

                                                    <?php if (
                                                        date('Y-m-d', $startsAt) !==
                                                        date('Y-m-d', $endsAt)
                                                    ): ?>
                                                        <span class="details-slot-end-date">
                                                            Ends <?= escapeHtml(
                                                                date('d M Y', $endsAt)
                                                            ) ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <span class="details-slot-select">
                                                        <span aria-hidden="true">✓</span>
                                                        Select appointment
                                                    </span>
                                                </span>
                                            </label>

                                        <?php endforeach; ?>

                                    </div>
                                </fieldset>
                                <p class="details-appointment-note">
                                    Showing up to 30 upcoming appointments.
                                    Availability is checked again when you book.
                                </p>
                                <div class="details-booking-actions">

                                    <?php if ($canBook): ?>

                                        <p>
                                            You’ll review your appointment
                                            and add your address next.
                                        </p>
                                        <button
                                            class="catalog-button catalog-button-primary"
                                            type="submit"
                                        >
                                            Continue to booking
                                            <span aria-hidden="true">↗</span>
                                        </button>

                                    <?php elseif (!$currentAccount): ?>

                                        <p>
                                            Log in to your customer account
                                            to continue with a booking.
                                        </p>
                                        <a
                                            class="catalog-button catalog-button-primary"
                                            href="login.php"
                                        >
                                            Log in to book
                                            <span aria-hidden="true">↗</span>
                                        </a>

                                    <?php else: ?>

                                        <p>
                                            Booking services requires
                                            a customer account.
                                        </p>
                                        <a
                                            class="catalog-button catalog-button-secondary"
                                            href="<?= escapeHtml($workspaceUrl) ?>"
                                        >
                                            Back to dashboard
                                            <span aria-hidden="true">↗</span>
                                        </a>

                                    <?php endif; ?>

                                </div>
                            </form>

                        <?php else: ?>

                            <div class="details-no-appointments">
                                <span aria-hidden="true">▦</span>
                                <h3>No open appointments right now</h3>
                                <p>
                                    This provider has not listed an available
                                    time for this service. You can check back
                                    later or explore another service.
                                </p>
                                <a
                                    class="catalog-button catalog-button-secondary"
                                    href="services.php"
                                >
                                    Explore other services
                                    <span aria-hidden="true">↗</span>
                                </a>
                            </div>

                        <?php endif; ?>

                    </section>
                    <section
                        class="details-panel"
                        aria-labelledby="service-reviews-title"
                    >
                        <div class="details-panel-heading">
                            <span
                                class="details-panel-icon"
                                aria-hidden="true"
                            >
                                ☆
                            </span>
                            <div>
                                <span class="catalog-eyebrow">
                                    Experiences from completed visits
                                </span>
                                <h2 id="service-reviews-title">
                                    Reviews for this service
                                </h2>
                            </div>
                        </div>

                        <?php if ($reviews): ?>

                            <p class="details-section-intro">
                                The latest customer reviews for this service.
                            </p>
                            <div class="details-reviews-list">

                                <?php foreach ($reviews as $review): ?>
                                    <?php
                                    $reviewDate = strtotime($review['created_at']);

                                    $reviewInitial = mb_strtoupper(
                                        mb_substr($review['customer_name'], 0, 1, 'UTF-8'),
                                        'UTF-8'
                                    );
                                    ?>

                                    <article class="details-review">
                                        <div class="details-review-heading">
                                            <span
                                                class="details-review-avatar"
                                                aria-hidden="true"
                                            >
                                                <?= escapeHtml($reviewInitial) ?>
                                            </span>
                                            <div class="details-review-customer">
                                                <h3 dir="auto">
                                                    <?= escapeHtml($review['customer_name']) ?>
                                                </h3>
                                                <time
                                                    datetime="<?= escapeHtml(
                                                        date('Y-m-d', $reviewDate)
                                                    ) ?>"
                                                >
                                                    <?= escapeHtml(date('d M Y', $reviewDate)) ?>
                                                </time>
                                            </div>
                                            <span class="details-review-score">
                                                <span aria-hidden="true">★</span>

                                                <?= (int) $review['rating'] ?>/5
                                            </span>
                                        </div>

                                        <?php if (trim((string) $review['comment']) !== ''): ?>
                                            <p dir="auto">
                                                <?= nl2br(escapeHtml($review['comment'])) ?>
                                            </p>
                                        <?php else: ?>
                                            <p class="details-review-no-comment">
                                                Rating submitted without a comment.
                                            </p>
                                        <?php endif; ?>

                                    </article>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <p class="details-section-intro">
                                No reviews for this service yet.
                                Reviews appear after completed bookings.
                            </p>

                        <?php endif; ?>

                    </section>
                </div>
                <aside
                    class="details-summary"
                    aria-labelledby="summary-title"
                >
                    <span class="catalog-eyebrow">
                        Your next clean
                    </span>
                    <h2 id="summary-title">
                        Service at a glance
                    </h2>
                    <div class="details-summary-price">
                        <span>Price per visit</span>
                        <strong>
                            <small>JOD</small>

                            <?= number_format((float) $service['price'], 3) ?>
                        </strong>
                    </div>
                    <dl class="details-summary-list">
                        <div>
                            <dt>Service</dt>
                            <dd dir="auto">
                                <?= escapeHtml($service['name']) ?>
                            </dd>
                        </div>
                        <div>
                            <dt>Provider</dt>
                            <dd dir="auto">
                                <?= escapeHtml($service['provider_name']) ?>
                            </dd>
                        </div>
                        <div>
                            <dt>City</dt>
                            <dd dir="auto">
                                <?= escapeHtml($service['city']) ?>
                            </dd>
                        </div>
                        <div>
                            <dt>Duration</dt>
                            <dd>
                                <?= (int) $service['duration_minutes'] ?>
                                minutes
                            </dd>
                        </div>
                    </dl>

                    <?php if ($appointments): ?>

                        <a
                            class="catalog-button catalog-button-primary"
                            href="#appointments"
                        >
                            Choose an appointment
                            <span aria-hidden="true">↓</span>
                        </a>

                    <?php else: ?>

                        <a
                            class="catalog-button catalog-button-secondary"
                            href="services.php"
                        >
                            Find another service
                            <span aria-hidden="true">↗</span>
                        </a>

                    <?php endif; ?>

                    <p class="details-summary-note">
                        Review the appointment details before
                        confirming your booking.
                    </p>
                    <div class="details-summary-help">
                        <span aria-hidden="true">?</span>
                        <p>
                            Have a question?
                            <a href="index.php#contact">
                                Contact us
                            </a>
                        </p>
                    </div>
                </aside>
            </div>

        <?php endif; ?>

    </main>

    

    <?php require __DIR__ . '/includes/catalog-footer.php'; ?>

</body>
</html>