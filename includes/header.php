<?php

require_once __DIR__ . "/init.php";
require_once __DIR__ . "/../classes/User.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Parent pages authorize before sending any output.
if (!isset($currentUser)) throw new RuntimeException('Missing provider authorization.');

$headerEscape = static function ($value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
};

$headerName = $currentUser["full_name"];
$headerTitle = $pageTitle ?? "Overview";

// أول حرف من أول كلمة وآخر كلمة.
$nameParts = preg_split(
    '/\s+/u',
    trim($headerName),
    -1,
    PREG_SPLIT_NO_EMPTY
);

$nameParts = $nameParts ?: [];

$initials = "";

if ($nameParts) {
    $initialWords = [$nameParts[0]];

    if (count($nameParts) > 1) {
        $initialWords[] = $nameParts[count($nameParts) - 1];
    }

    foreach ($initialWords as $word) {
        if (preg_match('/^./us', $word, $match)) {
            $initials .= $match[0];
        }
    }
}

$initials = function_exists("mb_strtoupper")
    ? mb_strtoupper($initials, "UTF-8")
    : strtoupper($initials);

?>

<header class="dashboard-header">

    <div class="dashboard-header-start">

        <label
            class="sidebar-toggle-button"
            for="sidebar-toggle"
        >
            <span class="burger-lines" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </span>

            <span class="visually-hidden">
                Toggle navigation menu
            </span>
        </label>

        <div class="header-location">
            <span>Workspace</span>
            <span aria-hidden="true">/</span>

            <strong>
                <?= $headerEscape($headerTitle) ?>
            </strong>
        </div>

    </div>

    <a
        class="dashboard-account"
        href="../profile.php"
        aria-label="Open my profile"
    >
        <span class="account-avatar" aria-hidden="true">
            <?= $headerEscape($initials) ?>
        </span>

        <span class="account-details">

            <strong dir="auto">
                <?= $headerEscape($headerName) ?>
            </strong>

            <span>Service provider</span>

        </span>

        <span class="account-arrow" aria-hidden="true">↗</span>
    </a>

</header>