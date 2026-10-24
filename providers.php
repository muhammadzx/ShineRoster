<?php

require_once __DIR__ . '/includes/init.php';

$currentAccount = $auth->currentUser();
$accountRole = $currentAccount['role'] ?? '';

$search = inputText($_GET, 'search', 120);
$city = inputText($_GET, 'city', 100);
$sort = inputText($_GET, 'sort', 30);
$ratingValue = inputText($_GET, 'rating', 10);

$allowedSorts = ['recommended', 'rating', 'price_low', 'price_high', 'newest'];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'recommended';
}

$allowedRatings = ['0', '3', '4', '4.5'];

if (!in_array($ratingValue, $allowedRatings, true)) {
    $ratingValue = '0';
}

$minimumRating = (float) $ratingValue;

$skillId = filter_var($_GET['skill_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

$skillId = $skillId === false ? 0 : (int) $skillId;

$page = filter_var($_GET['page'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$page = $page === false ? 1 : max(1, (int) $page);

$directory = new ProviderDirectory($db);

$cities = $directory->getCities();
$skills = $directory->getSkills();

$result = $directory->search($search, $city, $skillId, $minimumRating, $sort, $page);

$providers = $result['providers'];
$total = $result['total'];
$page = $result['page'];
$pages = $result['pages'];
$perPage = $result['per_page'];

$firstResult = $total > 0 ? ($page - 1) * $perPage + 1 : 0;

$lastResult = min($page * $perPage, $total);

$hasFilters = $search !== '' || $city !== '' || $skillId > 0 || $minimumRating > 0;

$selectedSkillName = '';

foreach ($skills as $skill) {
    if ((int) $skill['id'] === $skillId) {
        $selectedSkillName = $skill['name'];
        break;
    }
}

$pageUrl = static function (int $targetPage) use (
    $search,
    $city,
    $skillId,
    $ratingValue,
    $sort
): string {
    $query = [
        'sort' => $sort,
        'page' => $targetPage
    ];

    if ($search !== '') {
        $query['search'] = $search;
    }

    if ($city !== '') {
        $query['city'] = $city;
    }

    if ($skillId > 0) {
        $query['skill_id'] = $skillId;
    }

    if ($ratingValue !== '0') {
        $query['rating'] = $ratingValue;
    }

    return 'providers.php?' . http_build_query($query);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Find a Cleaner | ShineRoster</title>
    <meta
        name="description"
        content="Find home cleaning providers on ShineRoster. Compare skills, customer ratings, service prices and available appointments."
    >
    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="images/favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/services.css">
    <link rel="stylesheet" href="css/providers.css">
</head>
<body class="services-page providers-page">
    <a class="catalog-skip-link" href="#directory-main">
        Skip to providers
    </a>

    

    <?php require __DIR__ . '/includes/catalog-header.php'; ?>

    <main id="directory-main">
        <section
            class="catalog-hero"
            aria-labelledby="directory-title"
        >
            <div class="catalog-container catalog-hero-inner">
                <div class="catalog-hero-copy">
                    <nav
                        class="catalog-breadcrumb"
                        aria-label="Breadcrumb"
                    >
                        <a href="index.php">Home</a>
                        <span aria-hidden="true">/</span>
                        <span aria-current="page">
                            Find a cleaner
                        </span>
                    </nav>
                    <span class="catalog-eyebrow">
                        Good people. A fresher home.
                    </span>
                    <h1 id="directory-title">
                        Find your cleaner.<br>
                        <span>Feel good about your choice.</span>
                    </h1>
                    <p>
                        Meet the people behind the clean.
                        Compare their skills, customer reviews and service
                        prices to find the right provider for your home.
                    </p>
                    <div class="catalog-hero-points">
                        <span>
                            <span aria-hidden="true">✓</span>
                            Compare providers
                        </span>
                        <span>
                            <span aria-hidden="true">✓</span>
                            Read customer reviews
                        </span>
                        <span>
                            <span aria-hidden="true">✓</span>
                            Explore cleaning skills
                        </span>
                    </div>
                </div>
                <aside class="catalog-hero-note directory-hero-note">
                    <span
                        class="catalog-note-icon"
                        aria-hidden="true"
                    >
                        ◎
                    </span>
                    <span class="catalog-eyebrow">
                        Your home is personal
                    </span>
                    <h2>
                        Choose the person.<br>
                        Then choose your clean.
                    </h2>
                    <p>
                        Find a provider whose services and availability
                        match what you need.
                    </p>
                    <a href="#provider-results">
                        Meet the providers
                        <span aria-hidden="true">↓</span>
                    </a>
                </aside>
            </div>
        </section>
        <section
            class="catalog-section"
            id="provider-results"
            aria-labelledby="provider-results-title"
        >
            <div class="catalog-container">
                <form
                    class="catalog-filters directory-filters"
                    action="providers.php"
                    method="get"
                >
                    <div class="catalog-filter-field catalog-search-field">
                        <label for="provider-search">
                            Find your provider
                        </label>
                        <input
                            type="search"
                            id="provider-search"
                            name="search"
                            value="<?= escapeHtml($search) ?>"
                            placeholder="Name, service or skill"
                            maxlength="120"
                        >
                    </div>
                    <div class="catalog-filter-field">
                        <label for="provider-city">
                            City
                        </label>
                        <select id="provider-city" name="city">
                            <option value="">
                                All cities
                            </option>

                            <?php foreach ($cities as $availableCity): ?>
                                <option
                                    value="<?= escapeHtml($availableCity) ?>"
                                    <?= $city === $availableCity ? 'selected' : '' ?>
                                >
                                    <?= escapeHtml($availableCity) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>
                    <div class="catalog-filter-field">
                        <label for="provider-skill">
                            Cleaning skill
                        </label>
                        <select id="provider-skill" name="skill_id">
                            <option value="">
                                All skills
                            </option>

                            <?php foreach ($skills as $skill): ?>
                                <option
                                    value="<?= (int) $skill['id'] ?>"
                                    <?= $skillId === (int) $skill['id'] ? 'selected' : '' ?>
                                >
                                    <?= escapeHtml($skill['name']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>
                    <div class="catalog-filter-field">
                        <label for="provider-rating">
                            Minimum rating
                        </label>
                        <select id="provider-rating" name="rating">
                            <option
                                value="0"
                                <?= $ratingValue === '0' ? 'selected' : '' ?>
                            >
                                Any rating
                            </option>
                            <option
                                value="3"
                                <?= $ratingValue === '3' ? 'selected' : '' ?>
                            >
                                3.0 and above
                            </option>
                            <option
                                value="4"
                                <?= $ratingValue === '4' ? 'selected' : '' ?>
                            >
                                4.0 and above
                            </option>
                            <option
                                value="4.5"
                                <?= $ratingValue === '4.5' ? 'selected' : '' ?>
                            >
                                4.5 and above
                            </option>
                        </select>
                    </div>
                    <div class="catalog-filter-field">
                        <label for="provider-sort">
                            Sort by
                        </label>
                        <select id="provider-sort" name="sort">
                            <option
                                value="recommended"
                                <?= $sort === 'recommended' ? 'selected' : '' ?>
                            >
                                Recommended
                            </option>
                            <option
                                value="rating"
                                <?= $sort === 'rating' ? 'selected' : '' ?>
                            >
                                Highest rated
                            </option>
                            <option
                                value="price_low"
                                <?= $sort === 'price_low' ? 'selected' : '' ?>
                            >
                                Starting price: low to high
                            </option>
                            <option
                                value="price_high"
                                <?= $sort === 'price_high' ? 'selected' : '' ?>
                            >
                                Starting price: high to low
                            </option>
                            <option
                                value="newest"
                                <?= $sort === 'newest' ? 'selected' : '' ?>
                            >
                                Newest providers
                            </option>
                        </select>
                    </div>
                    <div class="directory-filter-actions">
                        <button
                            class="catalog-button catalog-button-primary"
                            type="submit"
                        >
                            Find a cleaner
                            <span aria-hidden="true">↗</span>
                        </button>
                        <a class="catalog-reset" href="providers.php">
                            Reset
                        </a>
                    </div>
                </form>
                <div class="catalog-results-heading">
                    <div>
                        <span class="catalog-eyebrow">
                            People who care for your space
                        </span>
                        <h2 id="provider-results-title">
                            Meet your next cleaning provider
                        </h2>
                        <p>
                            <?php if ($total > 0): ?>

                                Showing <?= $firstResult ?>–<?= $lastResult ?>
                                of <?= $total ?>
                                <?= $total === 1 ? 'provider' : 'providers' ?>.

                            <?php else: ?>

                                No providers match your current search.

                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="catalog-results-count">
                        <?= $total ?>
                        <?= $total === 1 ? 'provider' : 'providers' ?>
                    </span>
                </div>

                

                <?php if ($hasFilters): ?>
                    <div class="catalog-active-filters">
                        <span>Filtered results</span>

                        <?php if ($search !== ''): ?>
                            <span class="catalog-filter-chip" dir="auto">
                                Search: <?= escapeHtml($search) ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($city !== ''): ?>
                            <span class="catalog-filter-chip" dir="auto">
                                City: <?= escapeHtml($city) ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($selectedSkillName !== ''): ?>
                            <span class="catalog-filter-chip" dir="auto">
                                <?= escapeHtml($selectedSkillName) ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($minimumRating > 0): ?>
                            <span class="catalog-filter-chip">
                                Rating: <?= escapeHtml($ratingValue) ?>+
                            </span>
                        <?php endif; ?>

                        <a href="providers.php">
                            Clear filters
                        </a>
                    </div>
                <?php endif; ?>

                

                <?php if ($providers): ?>

                    <div class="catalog-grid directory-grid">

                        <?php foreach ($providers as $provider): ?>
                            <?php
                            $nameParts = preg_split(
                                '/\s+/u',
                                trim($provider['full_name']),
                                -1,
                                PREG_SPLIT_NO_EMPTY
                            );

                            $initials = '';

                            if ($nameParts) {
                                $initials = mb_substr($nameParts[0], 0, 1, 'UTF-8');

                                if (count($nameParts) > 1) {
                                    $initials .= mb_substr(
                                        $nameParts[count($nameParts) - 1],
                                        0,
                                        1,
                                        'UTF-8'
                                    );
                                }
                            }

                            $initials = mb_strtoupper($initials, 'UTF-8');

                            $reviewCount = (int) $provider['review_count'];
                            $serviceCount = (int) $provider['service_count'];
                            $availableCount = (int) $provider['available_count'];

                            $visibleSkills = array_slice($provider['skills'], 0, 3);

                            $remainingSkills = max(0, count($provider['skills']) - 3);

                            $bio = trim((string) $provider['bio']);

                            $profileUrl = 'provider-details.php?id=' . (int) $provider['id'];

                            $nextAppointment = $provider['next_appointment']
                                ? new DateTimeImmutable($provider['next_appointment'])
                                : null;
                            ?>

                            <article class="catalog-card directory-card">
                                <div class="directory-card-top">
                                    <span
                                        class="directory-avatar"
                                        aria-hidden="true"
                                    >
                                        <?= escapeHtml($initials) ?>
                                    </span>

                                    <?php if ($availableCount > 0): ?>

                                        <span class="catalog-availability">
                                            <span aria-hidden="true"></span>
                                            Appointments available
                                        </span>

                                    <?php else: ?>

                                        <span class="catalog-availability catalog-availability-muted">
                                            No open appointments
                                        </span>

                                    <?php endif; ?>

                                </div>
                                <div class="directory-heading">
                                    <span class="directory-provider-label">
                                        Cleaning provider
                                    </span>
                                    <h3 dir="auto">
                                        <?= escapeHtml($provider['full_name']) ?>
                                    </h3>
                                    <p class="directory-city">
                                        <span aria-hidden="true">⌖</span>
                                        <span dir="auto">
                                            <?= escapeHtml($provider['city']) ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="directory-review">

                                    <?php if ($reviewCount > 0): ?>

                                        <strong>
                                            <span aria-hidden="true">★</span>

                                            <?= number_format( (float) $provider['average_rating'], 1 ) ?>
                                        </strong>
                                        <span>
                                            <?= $reviewCount ?>
                                            <?= $reviewCount === 1 ? 'customer review' : 'customer reviews' ?>
                                        </span>

                                    <?php else: ?>

                                        <strong>New provider</strong>
                                        <span>No reviews yet</span>

                                    <?php endif; ?>

                                </div>
                                <p class="directory-bio" dir="auto">
                                    <?= escapeHtml( $bio !== '' ? $bio : 'Explore this provider’s cleaning services and available appointments.' ) ?>
                                </p>
                                <div class="directory-skills-section">
                                    <span class="directory-section-label">
                                        Listed skills
                                    </span>
                                    <div class="catalog-skills directory-skills">

                                        <?php if ($visibleSkills): ?>

                                            <?php foreach ($visibleSkills as $skillName): ?>
                                                <span dir="auto">
                                                    <?= escapeHtml($skillName) ?>
                                                </span>
                                            <?php endforeach; ?>

                                            <?php if ($remainingSkills > 0): ?>
                                                <span>
                                                    +<?= $remainingSkills ?> more
                                                </span>
                                            <?php endif; ?>

                                        <?php else: ?>

                                            <span class="catalog-skill-empty">
                                                No skills listed
                                            </span>

                                        <?php endif; ?>

                                    </div>
                                </div>
                                <div class="directory-stats">
                                    <div>
                                        <strong><?= $serviceCount ?></strong>
                                        <span>
                                            <?= $serviceCount === 1 ? 'Service' : 'Services' ?>
                                        </span>
                                    </div>
                                    <div>
                                        <strong><?= $availableCount ?></strong>
                                        <span>Open times</span>
                                    </div>
                                </div>
                                <div class="directory-next-appointment">
                                    <span aria-hidden="true">▦</span>
                                    <div>

                                        <?php if ($nextAppointment): ?>

                                            <span>Next available · Jordan time</span>
                                            <strong>
                                                <?= escapeHtml( $nextAppointment->format('d M Y · g:i A') ) ?>
                                            </strong>

                                        <?php else: ?>

                                            <span>Appointments</span>
                                            <strong>
                                                No open times at the moment
                                            </strong>

                                        <?php endif; ?>

                                    </div>
                                </div>
                                <div class="catalog-card-bottom">
                                    <div class="catalog-price">
                                        <span>Services from</span>
                                        <strong>
                                            <small>JOD</small>

                                            <?= number_format( (float) $provider['minimum_price'], 3 ) ?>
                                        </strong>
                                    </div>
                                    <a
                                        class="catalog-button catalog-button-secondary"
                                        href="<?= escapeHtml($profileUrl) ?>"
                                        aria-label="<?= escapeHtml( 'View ' . $provider['full_name'] . ' provider profile' ) ?>"
                                    >
                                        View profile
                                        <span aria-hidden="true">↗</span>
                                    </a>
                                </div>
                            </article>

                        <?php endforeach; ?>

                    </div>

                    

                    <?php if ($pages > 1): ?>
                        <nav
                            class="catalog-pagination"
                            aria-label="Providers pagination"
                        >

                            <?php if ($page > 1): ?>
                                <a href="<?= escapeHtml($pageUrl($page - 1)) ?>">
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
                                <a href="<?= escapeHtml($pageUrl($page + 1)) ?>">
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
                            ◎
                        </span>
                        <h3>No providers found</h3>
                        <p>
                            Try another name, city or cleaning skill.
                            You can also lower the minimum rating or
                            clear your filters.
                        </p>
                        <a
                            class="catalog-button catalog-button-primary"
                            href="providers.php"
                        >
                            Show all providers
                            <span aria-hidden="true">↗</span>
                        </a>
                    </div>

                <?php endif; ?>

                <aside class="catalog-help">
                    <span aria-hidden="true">✧</span>
                    <div>
                        <h2>Prefer to start with a service?</h2>
                        <p>
                            Explore cleaning services and compare
                            prices, durations and providers.
                        </p>
                    </div>
                    <a
                        class="catalog-button catalog-button-secondary"
                        href="services.php"
                    >
                        Explore services
                        <span aria-hidden="true">↗</span>
                    </a>
                </aside>
            </div>
        </section>
    </main>

    

    <?php require __DIR__ . '/includes/catalog-footer.php'; ?>

</body>
</html>