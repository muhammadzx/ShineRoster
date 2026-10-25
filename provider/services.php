<?php
require_once __DIR__ . '/../includes/init.php';
$currentUser = $auth->requireRole('provider', '../login.php');
$providerObject = new Provider($db);
$profile = $providerObject->getProfileByUserId($currentUser['id']);
if (!$profile) {
    header('Location: ../profile.php');
    exit();
}
$service = new Service($db, $currentUser['id']);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = inputText($_POST, 'action');
    $id = positiveId($_POST, 'service_id');
    $saved = false;
    if ($action === 'create') {
        $saved = $service->addService(
            inputText($_POST, 'name'),
            inputText($_POST, 'description'),
            inputText($_POST, 'price'),
            inputText($_POST, 'duration_minutes'),
            inputText($_POST, 'is_active')
        );
    } elseif ($action === 'update') {
        $saved = $service->updateService(
            $id,
            inputText($_POST, 'name'),
            inputText($_POST, 'description'),
            inputText($_POST, 'price'),
            inputText($_POST, 'duration_minutes')
        );
    } elseif ($action === 'activate') {
        $saved = $service->changeStatus($id, 1);
    } elseif ($action === 'deactivate') {
        $saved = $service->changeStatus($id, 0);
    } else {
        $error = 'Invalid action.';
    }
    if ($saved) {
        $_SESSION['service_success'] = 'Your changes have been saved successfully.';
        header('Location: services.php', true, 303);
        exit();
    }
    if ($error === '') {
        $error = $service->getError();
    }
}
$success = inputText($_SESSION, 'service_success');
unset($_SESSION['service_success']);
$search = inputText($_GET, 'search', 120);
$status = inputText($_GET, 'status');
$pageTitle = 'My services';
$serviceStats = $service->getStats();
$serviceRows = $service->getServices($search, $status);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Services | ShineRoster</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="../images/favicon.ico" sizes="any">
    <link rel="icon" href="../images/favicon.png" type="image/png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/provider-services.css">
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
                        <span class="eyebrow">Your skills, your services</span>
                        <h1>My services</h1>
                        <p>
                            Manage your cleaning services, prices, and durations.
                        </p>
                    </div>
                    <a class="btn btn-primary" href="#add-service">
                        Add a service
                        <span aria-hidden="true">+</span>
                    </a>
                </div>

                

                

                <?php if ($success !== ''): ?>
                    <div class="service-alert service-alert-success" role="status">
                        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="service-alert service-alert-error" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <section class="service-stats" aria-label="Services summary">

                    <?php foreach (
                        [
                            [
                                'Total services',
                                $serviceStats['total'],
                                'All your cleaning offers',
                                '✦'
                            ],
                            [
                                'Active services',
                                $serviceStats['active'],
                                'Available for new bookings',
                                '✓'
                            ],
                            [
                                'Inactive services',
                                $serviceStats['inactive'],
                                'Hidden from new bookings',
                                'Ⅱ'
                            ]
                        ]
                        as [$label, $value, $description, $icon]
                    ): ?>
                        <article class="stat-card">
                            <div class="stat-card-top">
                                <span><?= escapeHtml($label) ?></span>
                                <span class="stat-icon" aria-hidden="true"><?= escapeHtml(
                                    $icon
                                ) ?></span>
                            </div>
                            <strong class="stat-value"><?= $value ?></strong>
                            <p><?= escapeHtml($description) ?></p>
                        </article>
                    <?php endforeach; ?>

                </section>
                <section class="dashboard-card service-list-panel" aria-labelledby="service-list-title">
                    <div class="card-heading">
                        <div>
                            <h2 id="service-list-title">Your service collection</h2>
                            <p>Keep your offers clear and up to date.</p>
                        </div>
                    </div>
                    <form class="service-filters" action="services.php" method="get">
    <div class="service-field">
        <label for="service-search">Search services</label>
        <input
            type="search"
            id="service-search"
            name="search"
            placeholder="Search by service name"
            maxlength="120"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
        >
    </div>
    <div class="service-field">
        <label for="service-status-filter">Status</label>
        <select id="service-status-filter" name="status">
            <option value="">All services</option>
            <option value="active"
                <?php if ($status === 'active') {
                    echo 'selected';
                } else {
                    echo '';
                } ?>>
                Active
            </option>
            <option value="inactive"
                <?php if ($status === 'inactive') {
                    echo 'selected';
                } else {
                    echo '';
                } ?>>
                Inactive
            </option>
        </select>
    </div>
    <div class="service-filter-actions">
        <button class="btn btn-primary" type="submit">
            Apply
        </button>
        <a class="btn btn-secondary" href="services.php">
            Reset
        </a>
    </div>
</form>
                    <div class="provider-service-grid">

                        <?php foreach ($serviceRows as $serviceRow): ?>
                            <?php
                            $serviceId = (int) $serviceRow['id'];
                            $isActive = (int) $serviceRow['is_active'] === 1;
                            ?>
                            <article class="provider-service-card">
                                <div class="provider-service-top">
                                    <span class="provider-service-icon" aria-hidden="true">✦</span>
                                    <span class="status-badge <?php if ($isActive) {
                                        echo 'status-completed';
                                    } else {
                                        echo 'status-inactive';
                                    } ?>">
                                        <?php if ($isActive) {
                                            echo 'Active';
                                        } else {
                                            echo 'Inactive';
                                        } ?>
                                    </span>
                                </div>
                                <h3><?= escapeHtml($serviceRow['name']) ?></h3>
                                <p class="provider-service-description"><?= escapeHtml(
                                    $serviceRow['description']
                                ) ?></p>
                                <dl class="provider-service-details">
                                    <div><dt>Price per visit</dt><dd>JOD <?= escapeHtml(
                                        $serviceRow['price']
                                    ) ?></dd></div>
                                    <div><dt>Duration</dt><dd><?= (int) $serviceRow[
                                        'duration_minutes'
                                    ] ?> minutes</dd></div>
                                </dl>
                                <a class="service-appointments-link" href="slots.php?service_id=<?= $serviceId ?>">
                                    Manage appointments <span aria-hidden="true">↗</span>
                                </a>
                                <div class="provider-service-actions">
                                    <details class="service-edit">
                                        <summary>Edit service</summary>
                                        <form class="service-edit-form" action="services.php" method="post">
                                            
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="service_id" value="<?= $serviceId ?>">
                                            <div class="service-field">
                                                <label for="edit-name-<?= $serviceId ?>">Service name</label>
                                                <input type="text" id="edit-name-<?= $serviceId ?>" name="name" value="<?= escapeHtml(
    $serviceRow['name']
) ?>" maxlength="120" required>
                                            </div>
                                            <div class="service-field">
                                                <label for="edit-description-<?= $serviceId ?>">Description</label>
                                                <textarea id="edit-description-<?= $serviceId ?>" name="description" rows="4" required><?= escapeHtml(
    $serviceRow['description']
) ?></textarea>
                                            </div>
                                            <div class="service-form-row">
                                                <div class="service-field">
                                                    <label for="edit-price-<?= $serviceId ?>">Price (JOD)</label>
                                                    <input type="number" id="edit-price-<?= $serviceId ?>" name="price" value="<?= escapeHtml(
    $serviceRow['price']
) ?>" min="0" max="9999999.999" step="0.001" required>
                                                </div>
                                                <div class="service-field">
                                                    <label for="edit-duration-<?= $serviceId ?>">Minutes</label>
                                                    <input type="number" id="edit-duration-<?= $serviceId ?>" name="duration_minutes" value="<?= (int) $serviceRow[
    'duration_minutes'
] ?>" min="1" max="65535" step="1" required>
                                                </div>
                                            </div>
                                            <button class="btn btn-primary" type="submit">Save changes</button>
                                        </form>
                                    </details>
                                    <form action="services.php" method="post">
                                        
                                        <input type="hidden" name="action" value="<?php if (
                                            $isActive
                                        ) {
                                            echo 'deactivate';
                                        } else {
                                            echo 'activate';
                                        } ?>">
                                        <input type="hidden" name="service_id" value="<?= $serviceId ?>">
                                        <button class="service-action-button" type="submit"><?php if (
                                            $isActive
                                        ) {
                                            echo 'Deactivate';
                                        } else {
                                            echo 'Activate';
                                        } ?></button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>

                    </div>
                    <div class="service-empty" <?php if (count($serviceRows) > 0) {
                        echo 'hidden';
                    } else {
                        echo '';
                    } ?>>
                        <span aria-hidden="true">✦</span>
                        <h3>No services found</h3>
                        <p>
                            Try another search or add your first cleaning service.
                        </p>
                        <a class="btn btn-primary" href="#add-service">
                            Add a service
                        </a>
                    </div>
                </section>
                <section class="dashboard-card add-service-panel" id="add-service" aria-labelledby="add-service-title">
                    <div class="card-heading">
                        <div>
                            <h2 id="add-service-title">Add a new service</h2>
                            <p>
                                Describe what you offer and set a clear price
                                and visit duration.
                            </p>
                        </div>
                        <span class="service-panel-icon" aria-hidden="true">+</span>
                    </div>
                    <form class="service-create-form" action="services.php" method="post">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="service-field">
                            <label for="new-service-name">Service name</label>
                            <input type="text" id="new-service-name" name="name"
                                placeholder="e.g. Regular home cleaning" maxlength="120" required>
                        </div>
                        <div class="service-field">
                            <label for="new-service-description">Description</label>
                            <textarea id="new-service-description" name="description" rows="5"
                                placeholder="Explain what the visit includes and any important limitations."
                                aria-describedby="service-description-hint" required></textarea>
                            <p class="service-field-hint" id="service-description-hint">
                                Help customers understand exactly what they are booking.
                            </p>
                        </div>
                        <div class="service-form-row">
                            <div class="service-field">
                                <label for="new-service-price">Price per visit (JOD)</label>
                                <input type="number" id="new-service-price" name="price" placeholder="25.000" min="0"
                                    max="9999999.999" step="0.001" required>
                            </div>
                            <div class="service-field">
                                <label for="new-service-duration">
                                    Duration in minutes
                                </label>
                                <input type="number" id="new-service-duration" name="duration_minutes" placeholder="120"
                                    min="1" max="65535" step="1" required>
                            </div>
                        </div>
                        <div class="service-field">
                            <label for="new-service-status">Service status</label>
                            <select id="new-service-status" name="is_active" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="service-form-note">
                            <strong>Next step: add appointments.</strong>
                            <p>
                                An active service needs available appointments
                                before customers can book a visit.
                            </p>
                        </div>
                        <div class="service-form-footer">
                            <p>All fields are required.</p>
                            <button class="btn btn-primary" type="submit">
                                Create service
                                <span aria-hidden="true">+</span>
                            </button>
                        </div>
                    </form>
                </section>
            </main>

            

            <?php require_once __DIR__ . '/../includes/footer.php'; ?>

        </div>
    </div>
</body>
</html>