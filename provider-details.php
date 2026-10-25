<?php

require_once __DIR__ . '/includes/init.php';

$currentAccount = $auth->currentUser();
if (isset($currentAccount['role'])) {
    $accountRole = $currentAccount['role'];
} else {
    $accountRole = '';
}

if (isset($_GET['id'])) {
    $providerId = filter_var($_GET['id'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
} else {
    $providerId = filter_var(null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
}

if ($providerId === false) {
    $providerId = 0;
} else {
    $providerId = (int) $providerId;
}

if (isset($_GET['reviews_page'])) {
    $reviewsPage = filter_var($_GET['reviews_page'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
} else {
    $reviewsPage = filter_var(null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
}

if ($reviewsPage === false) {
    $reviewsPage = 1;
} else {
    $reviewsPage = max(1, (int) $reviewsPage);
}

$providerDetails = new ProviderDetails($db);

if ($providerId > 0) {
    $provider = $providerDetails->getProvider($providerId);
} else {
    $provider = null;
}

$skills = [];
$services = [];
$reviews = [];

$reviewsResult = [
    'reviews' => [],
    'total' => 0,
    'page' => 1,
    'pages' => 1
];

if ($provider) {
    $skills = $providerDetails->getSkills($providerId);
    $services = $providerDetails->getServices($providerId);

    $reviewsResult = $providerDetails->getReviews($providerId, $reviewsPage);

    $reviews = $reviewsResult['reviews'];
    $reviewsPage = $reviewsResult['page'];
} else {
    http_response_code(404);
}

if ($provider) {
    $reviewCount = (int) $provider['review_count'];
} else {
    $reviewCount = 0;
}

if ($provider) {
    $serviceCount = (int) $provider['service_count'];
} else {
    $serviceCount = 0;
}

if ($provider) {
    $availableCount = (int) $provider['available_count'];
} else {
    $availableCount = 0;
}

if ($provider) {
    $providerInitial = mb_strtoupper(mb_substr($provider['full_name'], 0, 1, 'UTF-8'), 'UTF-8');
} else {
    $providerInitial = '';
}

if ($provider && $provider['next_appointment']) {
    $nextAppointment = strtotime($provider['next_appointment']);
} else {
    $nextAppointment = null;
}

function reviewPageUrl($targetPage, $providerId)
{
    return 'provider-details.php?' .
        http_build_query([
            'id' => $providerId,
            'reviews_page' => $targetPage
        ]) .
        '#provider-reviews';
}

if ($provider) {
    $pageTitle = $provider['full_name'] . ' | ShineRoster';
} else {
    $pageTitle = 'Provider not found | ShineRoster';
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
    <title><?= escapeHtml($pageTitle) ?></title>
    <meta
        name="description"
        content="<?php if ($provider) {
            echo escapeHtml(
                'Explore ' .
                    $provider['full_name'] .
                    '’s cleaning services, listed skills, ' .
                    'customer reviews and available appointments.'
            );
        } else {
            echo escapeHtml('The requested cleaning provider could not be found.');
        } ?>"
    >

    <?php if (!$provider): ?>
        <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>

    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="images/favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/services.css">
    <link rel="stylesheet" href="css/provider-details.css">
</head>
<body class="services-page provider-details-page">
    <a class="catalog-skip-link" href="#provider-main">
        Skip to provider profile
    </a>

    

    <?php require __DIR__ . '/includes/catalog-header.php'; ?>

    <main id="provider-main">

        <?php if (!$provider): ?>

            <section class="profile-not-found">
                <div class="catalog-container">
                    <div class="catalog-empty">
                        <span
                            class="catalog-empty-icon"
                            aria-hidden="true"
                        >
                            ◎
                        </span>
                        <h1>Provider not found</h1>
                        <p>
                            This provider’s profile is currently unavailable.
                            Explore other providers to find the right
                            fit for your home.
                        </p>
                        <a
                            class="catalog-button catalog-button-primary"
                            href="providers.php"
                        >
                            Explore providers
                            <span aria-hidden="true">↗</span>
                        </a>
                    </div>
                </div>
            </section>

        <?php else: ?>

            <section
                class="profile-hero"
                aria-labelledby="provider-title"
            >
                <div class="catalog-container">
                    <nav
                        class="catalog-breadcrumb"
                        aria-label="Breadcrumb"
                    >
                        <a href="index.php">Home</a>
                        <span aria-hidden="true">/</span>
                        <a href="providers.php">Find a cleaner</a>
                        <span aria-hidden="true">/</span>
                        <span aria-current="page" dir="auto">
                            <?= escapeHtml($provider['full_name']) ?>
                        </span>
                    </nav>
                    <div class="profile-hero-row">
                        <div class="profile-identity">
                            <span
                                class="profile-avatar"
                                aria-hidden="true"
                            >
                                <?= escapeHtml($providerInitial) ?>
                            </span>
                            <div class="profile-identity-copy">
                                <span class="catalog-eyebrow">
                                    Your cleaning provider
                                </span>
                                <h1 id="provider-title" dir="auto">
                                    <?= escapeHtml($provider['full_name']) ?>
                                </h1>
                                <div class="profile-identity-meta">
                                    <span class="profile-location">
                                        <span aria-hidden="true">⌖</span>
                                        <span dir="auto">
                                            <?= escapeHtml($provider['city']) ?>
                                        </span>
                                    </span>

                                    <?php if ($reviewCount > 0): ?>

                                        <span class="profile-rating">
                                            <span aria-hidden="true">★</span>
                                            <strong>
                                                <?= number_format(
                                                    (float) $provider['average_rating'],
                                                    1
                                                ) ?>
                                            </strong>
                                            <span>
                                                (<?= $reviewCount ?>
                                                <?php if ($reviewCount === 1) {
                                                    echo 'review';
                                                } else {
                                                    echo 'reviews';
                                                } ?>)
                                            </span>
                                        </span>

                                    <?php else: ?>

                                        <span class="profile-no-reviews">
                                            No reviews yet
                                        </span>

                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                        <a
                            class="catalog-button catalog-button-secondary"
                            href="providers.php"
                        >
                            <span aria-hidden="true">←</span>
                            All providers
                        </a>
                    </div>
                    <nav
                        class="profile-section-nav"
                        aria-label="Provider profile sections"
                    >
                        <a href="#provider-about">About</a>
                        <a href="#provider-services">Services</a>
                        <a href="#provider-reviews">Reviews</a>
                    </nav>
                </div>
            </section>
            <div class="catalog-container profile-layout">
                <div class="profile-content">
                    <section
                        class="profile-panel"
                        id="provider-about"
                        aria-labelledby="provider-about-title"
                    >
                        <div class="profile-panel-heading">
                            <span
                                class="profile-panel-icon"
                                aria-hidden="true"
                            >
                                ◎
                            </span>
                            <div>
                                <span class="catalog-eyebrow">
                                    Meet the person behind the clean
                                </span>
                                <h2 id="provider-about-title">
                                    About this provider
                                </h2>
                            </div>
                        </div>

                        <?php if (trim((string) $provider['bio']) !== ''): ?>

                            <div class="profile-bio" dir="auto">
                                <?= nl2br(escapeHtml($provider['bio'])) ?>
                            </div>

                        <?php else: ?>

                            <p class="profile-muted-text">
                                This provider hasn’t added a description yet.
                                Explore their services and customer reviews
                                to learn more.
                            </p>

                        <?php endif; ?>

                        <div class="profile-stats">
                            <div>
                                <strong><?= $serviceCount ?></strong>
                                <span>Active services</span>
                            </div>
                            <div>
                                <strong>
                                    <?= (int) $provider['completed_count'] ?>
                                </strong>
                                <span>Completed visits</span>
                            </div>
                            <div>
                                <strong><?= $reviewCount ?></strong>
                                <span>Customer reviews</span>
                            </div>
                        </div>
                        <div class="profile-skills-section">
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
                                        No skills listed yet
                                    </span>

                                <?php endif; ?>

                            </div>
                        </div>
                    </section>
                    <section
                        class="profile-panel"
                        id="provider-services"
                        aria-labelledby="provider-services-title"
                    >
                        <div class="profile-panel-heading">
                            <span
                                class="profile-panel-icon"
                                aria-hidden="true"
                            >
                                ✦
                            </span>
                            <div>
                                <span class="catalog-eyebrow">
                                    Find your next fresh start
                                </span>
                                <h2 id="provider-services-title">
                                    Cleaning services
                                </h2>
                            </div>
                        </div>
                        <p class="profile-section-intro">
                            Explore this provider’s active services.
                            Open a service to view its details and appointments.
                        </p>

                        <?php if ($services): ?>

                            <div class="profile-services-grid">

                                <?php foreach ($services as $service): ?>
                                    <?php
                                    $serviceAvailableCount = (int) $service['available_count'];

                                    $serviceUrl = 'service-details.php?id=' . (int) $service['id'];

                                    if ($serviceAvailableCount > 0) {
                                        $appointmentUrl = $serviceUrl . '#appointments';
                                    } else {
                                        $appointmentUrl = $serviceUrl;
                                    }
                                    ?>

                                    <article class="profile-service-card">
                                        <div class="profile-service-top">
                                            <span
                                                class="profile-service-icon"
                                                aria-hidden="true"
                                            >
                                                ✦
                                            </span>

                                            <?php if ($serviceAvailableCount > 0): ?>

                                                <span class="catalog-availability">
                                                    <span aria-hidden="true"></span>
                                                    Open appointments
                                                </span>

                                            <?php else: ?>

                                                <span class="catalog-availability catalog-availability-muted">
                                                    No open appointments
                                                </span>

                                            <?php endif; ?>

                                        </div>
                                        <h3 dir="auto">
                                            <?= escapeHtml($service['name']) ?>
                                        </h3>
                                        <p
                                            class="profile-service-description"
                                            dir="auto"
                                        >
                                            <?= escapeHtml($service['description']) ?>
                                        </p>
                                        <dl class="profile-service-meta">
                                            <div>
                                                <dt>Duration</dt>
                                                <dd>
                                                    <?= (int) $service['duration_minutes'] ?>
                                                    minutes
                                                </dd>
                                            </div>
                                            <div>
                                                <dt>Open times</dt>
                                                <dd>
                                                    <?= $serviceAvailableCount ?>
                                                </dd>
                                            </div>
                                        </dl>
                                        <div class="profile-service-bottom">
                                            <div class="catalog-price">
                                                <span>Price per visit</span>
                                                <strong>
                                                    <small>JOD</small>

                                                    <?= number_format(
                                                        (float) $service['price'],
                                                        3
                                                    ) ?>
                                                </strong>
                                            </div>
                                            <a
                                                class="catalog-button catalog-button-secondary"
                                                href="<?= escapeHtml($appointmentUrl) ?>"
                                                aria-label="<?= escapeHtml(
                                                    'View ' .
                                                        $service['name'] .
                                                        ' and its appointments'
                                                ) ?>"
                                            >
                                                <?php if ($serviceAvailableCount > 0) {
                                                    echo 'Choose a time';
                                                } else {
                                                    echo 'View service';
                                                } ?>

                                                <span aria-hidden="true">↗</span>
                                            </a>
                                        </div>
                                    </article>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <div class="profile-empty-section">
                                <span aria-hidden="true">✦</span>
                                <h3>No active services right now</h3>
                                <p>
                                    This provider hasn’t listed an active
                                    service at the moment.
                                    You can check back later or explore
                                    other providers.
                                </p>
                                <a
                                    class="catalog-button catalog-button-secondary"
                                    href="providers.php"
                                >
                                    Explore providers
                                    <span aria-hidden="true">↗</span>
                                </a>
                            </div>

                        <?php endif; ?>

                    </section>
                    <section
                        class="profile-panel"
                        id="provider-reviews"
                        aria-labelledby="provider-reviews-title"
                    >
                        <div class="profile-panel-heading">
                            <span
                                class="profile-panel-icon"
                                aria-hidden="true"
                            >
                                ☆
                            </span>
                            <div>
                                <span class="catalog-eyebrow">
                                    Experiences from completed visits
                                </span>
                                <h2 id="provider-reviews-title">
                                    Customer reviews
                                </h2>
                            </div>
                        </div>

                        <?php if ($reviews): ?>

                            <div class="profile-review-summary">
                                <strong>
                                    <?= number_format((float) $provider['average_rating'], 1) ?>
                                </strong>
                                <div>
                                    <span class="profile-review-summary-label">
                                        <span aria-hidden="true">★</span>
                                        Provider rating
                                    </span>
                                    <p>
                                        Based on <?= $reviewCount ?>
                                        <?php if ($reviewCount === 1) {
                                            echo 'customer review';
                                        } else {
                                            echo 'customer reviews';
                                        } ?>
                                        across completed bookings.
                                    </p>
                                </div>
                            </div>
                            <div class="profile-reviews-list">

                                <?php foreach ($reviews as $review): ?>
                                    <?php
                                    $reviewDate = strtotime($review['created_at']);

                                    $customerInitial = mb_strtoupper(
                                        mb_substr($review['customer_name'], 0, 1, 'UTF-8'),
                                        'UTF-8'
                                    );
                                    ?>

                                    <article class="profile-review-card">
                                        <div class="profile-review-heading">
                                            <span
                                                class="profile-review-avatar"
                                                aria-hidden="true"
                                            >
                                                <?= escapeHtml($customerInitial) ?>
                                            </span>
                                            <div class="profile-review-customer">
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
                                            <span class="profile-review-score">
                                                <span aria-hidden="true">★</span>

                                                <?= (int) $review['rating'] ?>/5
                                            </span>
                                        </div>
                                        <p class="profile-review-service" dir="auto">
                                            <?= escapeHtml($review['service_name']) ?>
                                        </p>

                                        <?php if (trim((string) $review['comment']) !== ''): ?>

                                            <p
                                                class="profile-review-comment"
                                                dir="auto"
                                            >
                                                <?= nl2br(escapeHtml($review['comment'])) ?>
                                            </p>

                                        <?php else: ?>

                                            <p class="profile-review-no-comment">
                                                Rating submitted without a comment.
                                            </p>

                                        <?php endif; ?>

                                    </article>

                                <?php endforeach; ?>

                            </div>

                            <?php if ($reviewsResult['pages'] > 1): ?>

                                <nav
                                    class="catalog-pagination"
                                    aria-label="Provider reviews pagination"
                                >

                                    <?php if ($reviewsPage > 1): ?>
                                        <a
                                            href="<?= escapeHtml(
                                                reviewPageUrl($reviewsPage - 1, $providerId)
                                            ) ?>"
                                        >
                                            <span aria-hidden="true">←</span>
                                            Previous
                                        </a>
                                    <?php endif; ?>

                                    <span
                                        class="catalog-page-current"
                                        aria-current="page"
                                    >
                                        Page <?= $reviewsPage ?>
                                        of <?= $reviewsResult['pages'] ?>
                                    </span>

                                    <?php if ($reviewsPage < $reviewsResult['pages']): ?>
                                        <a
                                            href="<?= escapeHtml(
                                                reviewPageUrl($reviewsPage + 1, $providerId)
                                            ) ?>"
                                        >
                                            Next
                                            <span aria-hidden="true">→</span>
                                        </a>
                                    <?php endif; ?>

                                </nav>

                            <?php endif; ?>

                        <?php else: ?>

                            <div class="profile-empty-section">
                                <span aria-hidden="true">☆</span>
                                <h3>No customer reviews yet</h3>
                                <p>
                                    Reviews appear after customers complete
                                    a booking and share their experience.
                                </p>
                            </div>

                        <?php endif; ?>

                    </section>
                </div>
                <aside
                    class="profile-summary"
                    aria-labelledby="profile-summary-title"
                >
                    <span class="catalog-eyebrow">
                        A cleaner home starts here
                    </span>
                    <h2 id="profile-summary-title">
                        Plan your next clean
                    </h2>

                    <?php if ($provider['minimum_price'] !== null): ?>

                        <div class="profile-summary-price">
                            <span>Services from</span>
                            <strong>
                                <small>JOD</small>

                                <?= number_format((float) $provider['minimum_price'], 3) ?>
                            </strong>
                            <p>Prices vary by service.</p>
                        </div>

                    <?php endif; ?>

                    <dl class="profile-summary-details">
                        <div>
                            <dt>City</dt>
                            <dd dir="auto">
                                <?= escapeHtml($provider['city']) ?>
                            </dd>
                        </div>
                        <div>
                            <dt>Active services</dt>
                            <dd><?= $serviceCount ?></dd>
                        </div>
                        <div>
                            <dt>Open appointments</dt>
                            <dd><?= $availableCount ?></dd>
                        </div>
                    </dl>
                    <div class="profile-next-appointment">
                        <span aria-hidden="true">▦</span>
                        <div>
                            <?php if ($nextAppointment): ?>

                                <span>Next available · Jordan time</span>
                                <strong>
                                    <?= escapeHtml(date('d M Y · g:i A', $nextAppointment)) ?>
                                </strong>

                            <?php else: ?>

                                <span>Appointments</span>
                                <strong>
                                    No open times at the moment
                                </strong>

                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($services): ?>

                        <a
                            class="catalog-button catalog-button-primary"
                            href="#provider-services"
                        >
                            Explore services
                            <span aria-hidden="true">↓</span>
                        </a>

                    <?php else: ?>

                        <a
                            class="catalog-button catalog-button-secondary"
                            href="providers.php"
                        >
                            Find another provider
                            <span aria-hidden="true">↗</span>
                        </a>

                    <?php endif; ?>

                    <p class="profile-summary-note">
                        Choose a service to view its details
                        and select an available appointment.
                    </p>
                    <div class="profile-summary-help">
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