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
$appointment = new Appointment($db);
$pageTitle = 'My appointments';
$activePage = 'slots';
$error = '';
$success = inputText($_SESSION, 'slots_success');
unset($_SESSION['slots_success']);
$form = ['slot_id' => 0, 'service_id' => '', 'date' => '', 'time' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = inputText($_POST, 'action');
    $slotId = positiveId($_POST, 'slot_id');
    $saved = false;
    if ($action === 'save') {
        $form = [
            'slot_id' => $slotId,
            'service_id' => inputText($_POST, 'service_id'),
            'date' => inputText($_POST, 'date'),
            'time' => inputText($_POST, 'time')
        ];
        $saved = $appointment->save(
            $providerId,
            positiveId($_POST, 'service_id'),
            $form['date'],
            $form['time'],
            $slotId
        );
    } elseif ($action === 'delete') {
        $saved = $appointment->delete($slotId, $providerId);
    } elseif ($action === 'open') {
        $saved = $appointment->setOpen($slotId, $providerId, true);
    } elseif ($action === 'close') {
        $saved = $appointment->setOpen($slotId, $providerId, false);
    } else {
        $error = 'Invalid action.';
    }
    if ($saved) {
        $_SESSION['slots_success'] = 'Your appointment changes have been saved.';
        header('Location: slots.php', true, 303);
        exit();
    }
    if ($error === '') {
        $error = $appointment->getError();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editSlot = $appointment->getById(positiveId($_GET, 'edit'), $providerId);
    if ($editSlot) {
        $start = strtotime($editSlot['starts_at']);
        $form = [
            'slot_id' => (int) $editSlot['id'],
            'service_id' => (string) $editSlot['service_id'],
            'date' => date('Y-m-d', $start),
            'time' => date('H:i', $start)
        ];
    } else {
        $error = 'Appointment not found.';
    }
}
$filterService = positiveId($_GET, 'service_id');
$filterDate = inputText($_GET, 'date');
if (
    $filterDate !== '' &&
    (!strtotime($filterDate) || date('Y-m-d', strtotime($filterDate)) !== $filterDate)
) {
    $filterDate = '';
    $error = 'Please select a valid filter date.';
}
$services = $appointment->getServices($providerId);
$activeServices = [];
foreach ($services as $service) {
    if ($service['is_active']) {
        $activeServices[] = $service;
    }
}
$slots = $appointment->getAll($providerId, $filterService, $filterDate);
$now = time();
$isEditing = $form['slot_id'] > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments | ShineRoster</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="../images/favicon.ico" sizes="any">
    <link rel="icon" href="../images/favicon.png" type="image/png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/provider-slots.css">
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
                        <span class="eyebrow">Make time for your customers</span>
                        <h1>My appointments</h1>
                        <p>
                            Set your availability and manage your cleaning visits.
                        </p>
                    </div>
                    <a class="btn btn-primary" href="#appointment-form">
                        Add an appointment
                        <span aria-hidden="true">+</span>
                    </a>
                </div>

                <?php if ($success !== ''): ?>
                    <div class="slots-alert slots-alert-success" role="status">
                        <?= escapeHtml($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="slots-alert slots-alert-error" role="alert">
                        <?= escapeHtml($error) ?>
                    </div>
                <?php endif; ?>

                <div class="slots-notice">
                    <strong>Your time, clearly organized.</strong>
                    <p>
                        Appointment end times follow the service duration.
                        Overlapping appointments are not allowed, including
                        closed appointments. All times use Jordan local time.
                    </p>
                </div>
                <section
                    class="dashboard-card slots-list"
                    aria-labelledby="appointments-title"
                >
                    <div class="card-heading">
                        <div>
                            <h2 id="appointments-title">Your appointments</h2>
                            <p>
                                <?= count($slots) ?>
                                appointment(s) in this view.
                            </p>
                        </div>
                    </div>
                    <form class="slots-filters" action="slots.php" method="get">
                        <div class="slots-field">
                            <label for="filter-service">Service</label>
                            <select id="filter-service" name="service_id">
                                <option value="">All services</option>

                                <?php foreach ($services as $service): ?>
                                    <option
                                        value="<?= (int) $service['id'] ?>"
                                        <?php if ($filterService === (int) $service['id']) {
                                            echo 'selected';
                                        } else {
                                            echo '';
                                        } ?>
                                    >
                                        <?= escapeHtml($service['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="slots-field">
                            <label for="filter-date">Date</label>
                            <input
                                type="date"
                                id="filter-date"
                                name="date"
                                value="<?= escapeHtml($filterDate) ?>"
                            >
                        </div>
                        <div class="slots-filter-actions">
                            <button class="btn btn-primary" type="submit">
                                Filter
                            </button>
                            <a class="btn btn-secondary" href="slots.php">
                                Reset
                            </a>
                        </div>
                    </form>

                    <?php if (!$slots): ?>

                        <div class="slots-empty">
                            <span aria-hidden="true">▤</span>
                            <h3>No appointments found</h3>
                            <p>
                                Add an appointment or try another filter.
                            </p>
                        </div>

                    <?php else: ?>

                        <div
                            class="table-scroll"
                            role="region"
                            aria-labelledby="appointments-title"
                            tabindex="0"
                        >
                            <table class="dashboard-table slots-table">
                                <caption class="visually-hidden">
                                    Your appointments, availability, and actions.
                                </caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Service</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Time</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php foreach ($slots as $slot): ?>
                                        <?php
                                        $start = strtotime($slot['starts_at']);
                                        $end = strtotime($slot['ends_at']);

                                        $hasStarted = $start <= $now;
                                        $hasHistory = (bool) $slot['has_history'];
                                        $reserved = (bool) $slot['is_reserved'];
                                        $open = (bool) $slot['is_open'];

                                        if ($end <= $now) {
                                            $status = 'Ended';
                                            $statusClass = 'slot-status-ended';
                                        } elseif ($hasStarted) {
                                            $status = 'Started';
                                            $statusClass = 'slot-status-ended';
                                        } elseif ($reserved) {
                                            $status = 'Booked';
                                            $statusClass = 'status-confirmed';
                                        } elseif (!$open) {
                                            $status = 'Closed';
                                            $statusClass = 'slot-status-closed';
                                        } else {
                                            $status = 'Open';
                                            $statusClass = 'status-completed';
                                        }
                                        ?>

                                        <tr>
                                            <th scope="row">
                                                <?= escapeHtml($slot['service_name']) ?>
                                            </th>
                                            <td>
                                                <?= escapeHtml(date('d M Y', $start)) ?>
                                            </td>
                                            <td>
                                                <?= escapeHtml(date('h:i A', $start)) ?>
                                                <span class="table-secondary">
                                                    to <?= escapeHtml(date('h:i A', $end)) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge <?= escapeHtml(
                                                    $statusClass
                                                ) ?>">
                                                    <?= escapeHtml($status) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="slot-actions">

                                                    <?php if (!$hasHistory && !$hasStarted): ?>
                                                        <a
                                                            class="slot-action"
                                                            href="slots.php?edit=<?= (int) $slot[
                                                                'id'
                                                            ] ?>#appointment-form"
                                                        >
                                                            Edit
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if (!$hasStarted && !$reserved): ?>
                                                        <form action="slots.php" method="post">
                                                            
                                                            <input
                                                                type="hidden"
                                                                name="slot_id"
                                                                value="<?= (int) $slot['id'] ?>"
                                                            >
                                                            <input
                                                                type="hidden"
                                                                name="action"
                                                                value="<?php if ($open) {
                                                                    echo 'close';
                                                                } else {
                                                                    echo 'open';
                                                                } ?>"
                                                            >
                                                            <button class="slot-action" type="submit">
                                                                <?php if ($open) {
                                                                    echo 'Close';
                                                                } else {
                                                                    echo 'Open';
                                                                } ?>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <?php if (!$hasHistory): ?>
                                                        <details class="slot-delete">
                                                            <summary class="slot-action slot-action-danger">
                                                                Delete
                                                            </summary>
                                                            <form
                                                                class="slot-delete-confirm"
                                                                action="slots.php"
                                                                method="post"
                                                            >
                                                                
                                                                <input
                                                                    type="hidden"
                                                                    name="slot_id"
                                                                    value="<?= (int) $slot['id'] ?>"
                                                                >
                                                                <input
                                                                    type="hidden"
                                                                    name="action"
                                                                    value="delete"
                                                                >
                                                                <p>
                                                                    Permanently delete this appointment?
                                                                </p>
                                                                <button
                                                                    class="slot-action slot-action-danger"
                                                                    type="submit"
                                                                >
                                                                    Confirm deletion
                                                                </button>
                                                            </form>
                                                        </details>
                                                    <?php endif; ?>

                                                    <?php if ($hasHistory): ?>
                                                        <span class="slot-history">
                                                            Booking history retained
                                                        </span>
                                                    <?php endif; ?>

                                                </div>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>

                    <?php endif; ?>

                </section>
                <section
                    class="dashboard-card slots-form-panel"
                    id="appointment-form"
                    aria-labelledby="appointment-form-title"
                >
                    <div class="card-heading">
                        <div>
                            <h2 id="appointment-form-title">
                                <?php if ($isEditing) {
                                    echo 'Edit appointment';
                                } else {
                                    echo 'Add an appointment';
                                } ?>
                            </h2>
                            <p>
                                Select an active service and choose its start time.
                            </p>
                        </div>
                    </div>

                    <?php if (!$activeServices): ?>

                        <div class="slots-empty">
                            <h3>You need an active service first</h3>
                            <p>
                                Add or activate a service before creating appointments.
                            </p>
                            <a class="btn btn-primary" href="services.php">
                                Manage services
                            </a>
                        </div>

                    <?php else: ?>

                        <form
                            class="slots-form"
                            action="slots.php#appointment-form"
                            method="post"
                        >
                            
                            <input type="hidden" name="action" value="save">
                            <input
                                type="hidden"
                                name="slot_id"
                                value="<?php if ($isEditing) {
                                    echo (int) $form['slot_id'];
                                } else {
                                    echo '';
                                } ?>"
                            >
                            <div class="slots-field">
                                <label for="appointment-service">Service</label>
                                <select
                                    id="appointment-service"
                                    name="service_id"
                                    required
                                >
                                    <option value="">Select a service</option>

                                    <?php foreach ($activeServices as $service): ?>
                                        <option
                                            value="<?= (int) $service['id'] ?>"
                                            <?php if (
                                                (int) $form['service_id'] === (int) $service['id']
                                            ) {
                                                echo 'selected';
                                            } else {
                                                echo '';
                                            } ?>
                                        >
                                            <?= escapeHtml($service['name']) ?>
                                            — <?= (int) $service['duration_minutes'] ?> minutes
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="slots-form-row">
                                <div class="slots-field">
                                    <label for="appointment-date">Date</label>
                                    <input
                                        type="date"
                                        id="appointment-date"
                                        name="date"
                                        min="<?= date('Y-m-d') ?>"
                                        value="<?= escapeHtml($form['date']) ?>"
                                        required
                                    >
                                </div>
                                <div class="slots-field">
                                    <label for="appointment-time">Start time</label>
                                    <input
                                        type="time"
                                        id="appointment-time"
                                        name="time"
                                        step="60"
                                        value="<?= escapeHtml($form['time']) ?>"
                                        required
                                    >
                                </div>
                            </div>
                            <p class="slots-form-hint">
                                The end time is calculated automatically when saved.
                                Existing appointment durations stay unchanged if
                                you later change the service duration.
                            </p>
                            <div class="slots-form-actions">

                                <?php if ($isEditing): ?>
                                    <a class="btn btn-secondary" href="slots.php">
                                        Cancel editing
                                    </a>
                                <?php endif; ?>

                                <button class="btn btn-primary" type="submit">
                                    <?php if ($isEditing) {
                                        echo 'Save changes';
                                    } else {
                                        echo 'Create appointment';
                                    } ?>
                                </button>
                            </div>
                        </form>

                    <?php endif; ?>

                </section>
            </main>

            <?php require_once __DIR__ . '/../includes/footer.php'; ?>

        </div>
    </div>
</body>
</html>