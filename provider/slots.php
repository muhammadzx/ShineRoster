<?php

require_once __DIR__ . "/../includes/init.php";
$currentUser = $auth->requireRole('provider', '../login.php');
require_once __DIR__ . "/../classes/Appointment.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Amman");

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// استخدام نفس التوقيت عند مقارنة المواعيد في SQL.
$db->exec("SET time_zone = '+03:00'");

// استرجاع الحساب من قاعدة البيانات للتحقق من الدور والتفعيل.
$userId = filter_var(
    $_SESSION["user_id"] ?? null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if (!$userId) {
    header("Location: ../login.php");
    exit;
}

$stmt = $db->prepare(
    "SELECT u.role, u.is_active, p.id AS provider_id
     FROM users u
     LEFT JOIN provider_profiles p ON p.user_id = u.id
     WHERE u.id = ?"
);

$stmt->execute([$userId]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (
    !$account ||
    $account["role"] !== "provider" ||
    !(int) $account["is_active"]
) {
    http_response_code(403);
    exit("You do not have permission to access this page.");
}

if (!$account["provider_id"]) {
    header("Location: ../profile.php");
    exit;
}

$providerId = (int) $account["provider_id"];
$appointment = new Appointment($db);

// متغيرات جاهزة للاستخدام داخل المكونات المشتركة.
$pageTitle = "My appointments";
$activePage = "slots";

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};

$getText = static function (array $source, string $key): string {
    return isset($source[$key]) && is_string($source[$key])
        ? trim($source[$key])
        : "";
};

$getId = static function ($value): int {
    $id = filter_var(
        $value,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    return $id === false ? 0 : (int) $id;
};

$isValidDate = static function (string $value): bool {
    $date = DateTimeImmutable::createFromFormat("!Y-m-d", $value);

    return $date && $date->format("Y-m-d") === $value;
};

if (empty($_SESSION["slots_csrf"])) {
    $_SESSION["slots_csrf"] = bin2hex(random_bytes(32));
}

$error = "";
$success = $_SESSION["slots_success"] ?? "";
unset($_SESSION["slots_success"]);

$form = [
    "slot_id" => 0,
    "service_id" => "",
    "date" => "",
    "time" => ""
];

// معالجة العمليات قبل عرض HTML.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $getText($_POST, "action");
    $slotId = $getId($_POST["slot_id"] ?? null);

    if ($action === "save") {
        $form = [
            "slot_id" => $slotId,
            "service_id" => $getText($_POST, "service_id"),
            "date" => $getText($_POST, "date"),
            "time" => $getText($_POST, "time")
        ];
    }

    try {
        $token = $getText($_POST, "csrf_token");

        if (!hash_equals($_SESSION["slots_csrf"], $token)) {
            throw new RuntimeException(
                "Your form has expired. Refresh the page and try again."
            );
        }

        if ($action === "save") {
            // منع معرّف تعديل غير صالح من التحول إلى إضافة.
            if (
                $getText($_POST, "slot_id") !== "" &&
                $slotId === 0
            ) {
                throw new RuntimeException("Invalid appointment.");
            }

            $serviceId = $getId($_POST["service_id"] ?? null);

            if (!$serviceId) {
                throw new RuntimeException("Please select a service.");
            }

            $appointment->save(
                $providerId,
                $serviceId,
                $form["date"],
                $form["time"],
                $slotId
            );

            $_SESSION["slots_success"] = $slotId
                ? "Appointment updated successfully."
                : "Appointment created successfully.";

        } elseif (in_array($action, ["open", "close", "delete"], true)) {
            if (!$slotId) {
                throw new RuntimeException("Invalid appointment.");
            }

            if ($action === "delete") {
                $appointment->delete($slotId, $providerId);
                $_SESSION["slots_success"] = "Appointment deleted successfully.";
            } else {
                $appointment->setOpen(
                    $slotId,
                    $providerId,
                    $action === "open"
                );

                $_SESSION["slots_success"] = $action === "open"
                    ? "Appointment opened successfully."
                    : "Appointment closed successfully.";
            }
        } else {
            throw new RuntimeException("Invalid action.");
        }

        header("Location: slots.php", true, 303);
        exit;

    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = "The appointment could not be saved. Please try again.";

    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

// تحميل موعد التعديل بعد الضغط على Edit.
if (
    $_SERVER["REQUEST_METHOD"] === "GET" &&
    isset($_GET["edit"])
) {
    $editId = $getId($_GET["edit"]);
    $editSlot = $editId
        ? $appointment->getById($editId, $providerId)
        : null;

    if ($editSlot) {
        $start = new DateTimeImmutable($editSlot["starts_at"]);

        $form = [
            "slot_id" => (int) $editSlot["id"],
            "service_id" => (string) $editSlot["service_id"],
            "date" => $start->format("Y-m-d"),
            "time" => $start->format("H:i")
        ];
    } else {
        $error = "Appointment not found.";
    }
}

$filterService = $getId($_GET["service_id"] ?? null);
$filterDate = $getText($_GET, "date");

if ($filterDate !== "" && !$isValidDate($filterDate)) {
    $filterDate = "";
    $error = "Please select a valid filter date.";
}

$services = $appointment->getServices($providerId);

$activeServices = array_filter(
    $services,
    static fn($service) => (int) $service["is_active"] === 1
);

$slots = $appointment->getAll(
    $providerId,
    $filterService,
    $filterDate
);

$now = new DateTimeImmutable();
$isEditing = $form["slot_id"] > 0;

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

        <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

        <div class="dashboard-workspace">

            <?php require_once __DIR__ . "/../includes/header.php"; ?>

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

                <?php if ($success !== ""): ?>
                    <div class="slots-alert slots-alert-success" role="status">
                        <?= $escape($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ""): ?>
                    <div class="slots-alert slots-alert-error" role="alert">
                        <?= $escape($error) ?>
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

                <!-- APPOINTMENTS -->
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
                                        value="<?= (int) $service["id"] ?>"
                                        <?= $filterService === (int) $service["id"]
                                            ? "selected" : "" ?>
                                    >
                                        <?= $escape($service["name"]) ?>
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
                                value="<?= $escape($filterDate) ?>"
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
                                        $start = new DateTimeImmutable($slot["starts_at"]);
                                        $end = new DateTimeImmutable($slot["ends_at"]);

                                        $hasStarted = $start <= $now;
                                        $hasHistory = (bool) $slot["has_history"];
                                        $reserved = (bool) $slot["is_reserved"];
                                        $open = (bool) $slot["is_open"];

                                        if ($end <= $now) {
                                            $status = "Ended";
                                            $statusClass = "slot-status-ended";
                                        } elseif ($hasStarted) {
                                            $status = "Started";
                                            $statusClass = "slot-status-ended";
                                        } elseif ($reserved) {
                                            $status = "Booked";
                                            $statusClass = "status-confirmed";
                                        } elseif (!$open) {
                                            $status = "Closed";
                                            $statusClass = "slot-status-closed";
                                        } else {
                                            $status = "Open";
                                            $statusClass = "status-completed";
                                        }
                                        ?>

                                        <tr>
                                            <th scope="row">
                                                <?= $escape($slot["service_name"]) ?>
                                            </th>

                                            <td>
                                                <?= $escape($start->format("d M Y")) ?>
                                            </td>

                                            <td>
                                                <?= $escape($start->format("h:i A")) ?>
                                                <span class="table-secondary">
                                                    to <?= $escape($end->format("h:i A")) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="status-badge <?= $escape($statusClass) ?>">
                                                    <?= $escape($status) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <div class="slot-actions">

                                                    <?php if (!$hasHistory && !$hasStarted): ?>
                                                        <a
                                                            class="slot-action"
                                                            href="slots.php?edit=<?= (int) $slot["id"] ?>#appointment-form"
                                                        >
                                                            Edit
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if (!$hasStarted && !$reserved): ?>
                                                        <form action="slots.php" method="post">
                                                            <input
                                                                type="hidden"
                                                                name="csrf_token"
                                                                value="<?= $escape($_SESSION["slots_csrf"]) ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="slot_id"
                                                                value="<?= (int) $slot["id"] ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="action"
                                                                value="<?= $open ? "close" : "open" ?>"
                                                            >

                                                            <button class="slot-action" type="submit">
                                                                <?= $open ? "Close" : "Open" ?>
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
                                                                    name="csrf_token"
                                                                    value="<?= $escape($_SESSION["slots_csrf"]) ?>"
                                                                >

                                                                <input
                                                                    type="hidden"
                                                                    name="slot_id"
                                                                    value="<?= (int) $slot["id"] ?>"
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

                <!-- ADD / EDIT -->
                <section
                    class="dashboard-card slots-form-panel"
                    id="appointment-form"
                    aria-labelledby="appointment-form-title"
                >
                    <div class="card-heading">
                        <div>
                            <h2 id="appointment-form-title">
                                <?= $isEditing ? "Edit appointment" : "Add an appointment" ?>
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
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= $escape($_SESSION["slots_csrf"]) ?>"
                            >

                            <input type="hidden" name="action" value="save">

                            <input
                                type="hidden"
                                name="slot_id"
                                value="<?= $isEditing ? (int) $form["slot_id"] : "" ?>"
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
                                            value="<?= (int) $service["id"] ?>"
                                            <?= (int) $form["service_id"] === (int) $service["id"]
                                                ? "selected" : "" ?>
                                        >
                                            <?= $escape($service["name"]) ?>
                                            — <?= (int) $service["duration_minutes"] ?> minutes
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
                                        min="<?= date("Y-m-d") ?>"
                                        value="<?= $escape($form["date"]) ?>"
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
                                        value="<?= $escape($form["time"]) ?>"
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
                                    <?= $isEditing ? "Save changes" : "Create appointment" ?>
                                </button>

                            </div>

                        </form>

                    <?php endif; ?>

                </section>

            </main>

            <?php require_once __DIR__ . "/../includes/footer.php"; ?>

        </div>
    </div>

</body>
</html>