<?php

require_once __DIR__ . '/includes/init.php';

$search = inputText($_GET, 'search', 120);
$city = inputText($_GET, 'city', 100);
$sort = inputText($_GET, 'sort', 30);

$allowedSorts = ['recommended', 'rating', 'price_low', 'price_high', 'newest'];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'recommended';
}

if (isset($_GET['skill_id'])) {
    $skillId = filter_var($_GET['skill_id'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
} else {
    $skillId = filter_var(null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
}

if ($skillId === false) {
    $skillId = 0;
} else {
    $skillId = (int) $skillId;
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

$catalog = new ServiceCatalog($db);

$cities = $catalog->getCities();
$skills = $catalog->getSkills();

$result = $catalog->search($search, $city, $skillId, $sort, $page);

$services = $result['services'];
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

$hasFilters = $search !== '' || $city !== '' || $skillId > 0;

$currentAccount = $auth->currentUser();
if (isset($currentAccount['role'])) {
    $accountRole = $currentAccount['role'];
} else {
    $accountRole = '';
}

function pageUrl($targetPage, $search, $city, $skillId, $sort)
{
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

    return 'services.php?' . http_build_query($query);
}

$startPage = max(1, $page - 2);
$endPage = min($pages, $page + 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleaning Services | ShineRoster</title>
    <meta
        name="description"
        content="Compare home cleaning services, provider ratings, skills and prices. Find a cleaning service that fits your home."
    >
    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="images/favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/services.css">
</head>
<body class="services-page">
    <a class="catalog-skip-link" href="#catalog-main">
        Skip to services
    </a>

    

    <?php require __DIR__ . '/includes/catalog-header.php'; ?>

    <main id="catalog-main">
        <section class="catalog-hero" aria-labelledby="catalog-title">
            <div class="catalog-container catalog-hero-inner">
                <div class="catalog-hero-copy">
                    <nav class="catalog-breadcrumb" aria-label="Breadcrumb">
                        <a href="index.php">Home</a>
                        <span aria-hidden="true">/</span>
                        <span aria-current="page">Services</span>
                    </nav>
                    <span class="catalog-eyebrow">
                        A cleaner home starts here
                    </span>
                    <h1 id="catalog-title">
                        Find your clean.<br>
                        <span>Choose your expert.</span>
                    </h1>
                    <p>
                        Explore cleaning services from independent providers.
                        Compare their skills, ratings and prices to find the
                        right fit for your home.
                    </p>
                    <div class="catalog-hero-points">
                        <span>
                            <span aria-hidden="true">✓</span>
                            Clear prices
                        </span>
                        <span>
                            <span aria-hidden="true">✓</span>
                            Customer reviews
                        </span>
                        <span>
                            <span aria-hidden="true">✓</span>
                            Your choice of provider
                        </span>
                    </div>
                </div>
                <aside class="catalog-hero-note">
                    <span class="catalog-note-icon" aria-hidden="true">✦</span>
                    <span class="catalog-eyebrow">Your home, your choice</span>
                    <h2>A little help.<br>A lot more free time.</h2>
                    <p>
                        Choose a service that matches your needs and compare
                        the people behind it.
                    </p>
                    <a href="#service-results">
                        Explore services
                        <span aria-hidden="true">↓</span>
                    </a>
                </aside>
            </div>
        </section>
        <section
            class="catalog-section"
            id="service-results"
            aria-labelledby="results-title"
        >
            <div class="catalog-container">
                <form class="catalog-filters" action="services.php" method="get">
                    <div class="catalog-filter-field catalog-search-field">
                        <label for="catalog-search">What do you need?</label>
                        <input
                            type="search"
                            id="catalog-search"
                            name="search"
                            value="<?= escapeHtml($search) ?>"
                            placeholder="Service or provider name"
                            maxlength="120"
                        >
                    </div>
                    <div class="catalog-filter-field">
                        <label for="catalog-city">City</label>
                        <select id="catalog-city" name="city">
                            <option value="">All cities</option>

                            <?php foreach ($cities as $availableCity): ?>
                                <option
                                    value="<?= escapeHtml($availableCity) ?>"
                                    <?php if ($city === $availableCity) {
                                        echo 'selected';
                                    } else {
                                        echo '';
                                    } ?>
                                >
                                    <?= escapeHtml($availableCity) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="catalog-filter-field">
                        <label for="catalog-skill">Provider skill</label>
                        <select id="catalog-skill" name="skill_id">
                            <option value="">All skills</option>

                            <?php foreach ($skills as $skill): ?>
                                <option
                                    value="<?= (int) $skill['id'] ?>"
                                    <?php if ($skillId === (int) $skill['id']) {
                                        echo 'selected';
                                    } else {
                                        echo '';
                                    } ?>
                                >
                                    <?= escapeHtml($skill['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="catalog-filter-field">
                        <label for="catalog-sort">Sort by</label>
                        <select id="catalog-sort" name="sort">
                            <option
                                value="recommended"
                                <?php if ($sort === 'recommended') {
                                    echo 'selected';
                                } else {
                                    echo '';
                                } ?>
                            >
                                Recommended
                            </option>
                            <option
                                value="rating"
                                <?php if ($sort === 'rating') {
                                    echo 'selected';
                                } else {
                                    echo '';
                                } ?>
                            >
                                Highest provider rating
                            </option>
                            <option
                                value="price_low"
                                <?php if ($sort === 'price_low') {
                                    echo 'selected';
                                } else {
                                    echo '';
                                } ?>
                            >
                                Price: low to high
                            </option>
                            <option
                                value="price_high"
                                <?php if ($sort === 'price_high') {
                                    echo 'selected';
                                } else {
                                    echo '';
                                } ?>
                            >
                                Price: high to low
                            </option>
                            <option
                                value="newest"
                                <?php if ($sort === 'newest') {
                                    echo 'selected';
                                } else {
                                    echo '';
                                } ?>
                            >
                                Newest services
                            </option>
                        </select>
                    </div>
                    <button class="catalog-button catalog-button-primary" type="submit">
                        Search
                        <span aria-hidden="true">↗</span>
                    </button>
                    <a class="catalog-reset" href="services.php">
                        Reset
                    </a>
                </form>
                <div class="catalog-results-heading">
                    <div>
                        <span class="catalog-eyebrow">Made for your home</span>
                        <h2 id="results-title">Explore cleaning services</h2>
                        <p>
                            <?php if ($total > 0): ?>
                                Showing <?= $firstResult ?>–<?= $lastResult ?>
                                of <?= $total ?>
                                <?php if ($total === 1) {
                                    echo 'service';
                                } else {
                                    echo 'services';
                                } ?>.
                            <?php else: ?>
                                No services match your current search.
                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="catalog-results-count">
                        <?= $total ?>
                        <?php if ($total === 1) {
                            echo 'service';
                        } else {
                            echo 'services';
                        } ?>
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

                        <?php if ($skillId > 0): ?>
                            <span class="catalog-filter-chip">
                                Provider skill selected
                            </span>
                        <?php endif; ?>

                        <a href="services.php">Clear filters</a>
                    </div>
                <?php endif; ?>

                <?php if ($services): ?>

                    <div class="catalog-grid">

                        <?php foreach ($services as $service): ?>
                            <?php
                            $providerInitial = mb_strtoupper(
                                mb_substr($service['provider_name'], 0, 1, 'UTF-8'),
                                'UTF-8'
                            );

                            $reviewCount = (int) $service['review_count'];
                            $availableCount = (int) $service['available_count'];

                            $visibleSkills = array_slice($service['skills'], 0, 2);
                            $remainingSkills = max(0, count($service['skills']) - 2);

                            $detailsUrl = 'service-details.php?id=' . (int) $service['id'];
                            ?>

                            <article class="catalog-card">
                                <div class="catalog-card-top">
                                    <span class="catalog-service-icon" aria-hidden="true">
                                        ✦
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
                                <div class="catalog-card-copy">
                                    <h3 dir="auto">
                                        <?= escapeHtml($service['name']) ?>
                                    </h3>
                                    <p dir="auto">
                                        <?= escapeHtml($service['description']) ?>
                                    </p>
                                </div>
                                <div class="catalog-provider">
                                    <span class="catalog-provider-avatar" aria-hidden="true">
                                        <?= escapeHtml($providerInitial) ?>
                                    </span>
                                    <div class="catalog-provider-info">
                                        <strong dir="auto">
                                            <?= escapeHtml($service['provider_name']) ?>
                                        </strong>
                                        <span dir="auto">
                                            <?= escapeHtml($service['city']) ?>
                                        </span>
                                    </div>
                                    <div class="catalog-rating">
                                        <?php if ($reviewCount > 0): ?>
                                            <strong>
                                                <span aria-hidden="true">★</span>

                                                <?= number_format(
                                                    (float) $service['average_rating'],
                                                    1
                                                ) ?>
                                            </strong>
                                            <span>
                                                <?= $reviewCount ?>
                                                <?php if ($reviewCount === 1) {
                                                    echo 'review';
                                                } else {
                                                    echo 'reviews';
                                                } ?>
                                            </span>
                                        <?php else: ?>
                                            <strong>New</strong>
                                            <span>No reviews yet</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="catalog-rating-note">
                                    Rating reflects this provider’s completed bookings.
                                </span>
                                <div class="catalog-skills">
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
                                <div class="catalog-service-meta">
                                    <div>
                                        <span>Duration</span>
                                        <strong>
                                            <?= (int) $service['duration_minutes'] ?>
                                            minutes
                                        </strong>
                                    </div>
                                    <div>
                                        <span>Open appointments</span>
                                        <strong>
                                            <?= $availableCount ?>
                                        </strong>
                                    </div>
                                </div>
                                <div class="catalog-card-bottom">
                                    <div class="catalog-price">
                                        <span>Price per visit</span>
                                        <strong>
                                            <small>JOD</small>

                                            <?= number_format((float) $service['price'], 3) ?>
                                        </strong>
                                    </div>
                                    <a
                                        class="catalog-button catalog-button-secondary"
                                        href="<?= escapeHtml($detailsUrl) ?>"
                                        aria-label="<?= escapeHtml(
                                            'View ' .
                                                $service['name'] .
                                                ' by ' .
                                                $service['provider_name']
                                        ) ?>"
                                    >
                                        View service
                                        <span aria-hidden="true">↗</span>
                                    </a>
                                </div>
                            </article>

                        <?php endforeach; ?>

                    </div>

                    <?php if ($pages > 1): ?>
                        <nav class="catalog-pagination" aria-label="Services pagination">

                            <?php if ($page > 1): ?>
                                <a href="<?= escapeHtml(
                                    pageUrl($page - 1, $search, $city, $skillId, $sort)
                                ) ?>">
                                    <span aria-hidden="true">←</span>
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php if ($startPage > 1): ?>
                                <a href="<?= escapeHtml(
                                    pageUrl(1, $search, $city, $skillId, $sort)
                                ) ?>" aria-label="Page 1">
                                    1
                                </a>

                                <?php if ($startPage > 2): ?>
                                    <span aria-hidden="true">…</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($number = $startPage; $number <= $endPage; $number++): ?>

                                <?php if ($number === $page): ?>
                                    <span
                                        class="catalog-page-current"
                                        aria-current="page"
                                        aria-label="Page <?= $number ?>"
                                    >
                                        <?= $number ?>
                                    </span>
                                <?php else: ?>
                                    <a
                                        href="<?= escapeHtml(
                                            pageUrl($number, $search, $city, $skillId, $sort)
                                        ) ?>"
                                        aria-label="Page <?= $number ?>"
                                    >
                                        <?= $number ?>
                                    </a>
                                <?php endif; ?>

                            <?php endfor; ?>

                            <?php if ($endPage < $pages): ?>
                                <?php if ($endPage < $pages - 1): ?>
                                    <span aria-hidden="true">…</span>
                                <?php endif; ?>

                                <a
                                    href="<?= escapeHtml(
                                        pageUrl($pages, $search, $city, $skillId, $sort)
                                    ) ?>"
                                    aria-label="Page <?= $pages ?>"
                                >
                                    <?= $pages ?>
                                </a>
                            <?php endif; ?>

                            <?php if ($page < $pages): ?>
                                <a href="<?= escapeHtml(
                                    pageUrl($page + 1, $search, $city, $skillId, $sort)
                                ) ?>">
                                    Next
                                    <span aria-hidden="true">→</span>
                                </a>
                            <?php endif; ?>

                        </nav>
                    <?php endif; ?>

                <?php else: ?>

                    <div class="catalog-empty">
                        <span class="catalog-empty-icon" aria-hidden="true">⌕</span>
                        <h3>No services found</h3>
                        <p>
                            Try another keyword, city or provider skill.
                            You can also clear the filters to see all services.
                        </p>
                        <a class="catalog-button catalog-button-primary" href="services.php">
                            Show all services
                            <span aria-hidden="true">↗</span>
                        </a>
                    </div>

                <?php endif; ?>

                <aside class="catalog-help">
                    <span aria-hidden="true">✧</span>
                    <div>
                        <h2>Not sure where to start?</h2>
                        <p>
                            Tell us what you need help with.
                            We’ll help you understand your options.
                        </p>
                    </div>
                    <a class="catalog-button catalog-button-secondary" href="index.php#contact">
                        Contact us
                        <span aria-hidden="true">↗</span>
                    </a>
                </aside>
            </div>
        </section>
    </main>

    

    <?php require __DIR__ . '/includes/catalog-footer.php'; ?>

</body>
</html>