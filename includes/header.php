<?php

require_once __DIR__ . '/init.php';

if (!isset($currentUser)) {
    exit('Access denied.');
}

$headerName = $currentUser['full_name'];
if (isset($pageTitle)) {
    $headerTitle = $pageTitle;
} else {
    $headerTitle = 'Overview';
}

$nameParts = explode(' ', trim($headerName));
$words = [];
foreach ($nameParts as $word) {
    if ($word !== '') {
        $words[] = $word;
    }
}
$initials = '';
if (count($words) > 0) {
    $initials = mb_substr($words[0], 0, 1, 'UTF-8');
    if (count($words) > 1) {
        $lastWord = $words[count($words) - 1];
        $initials .= mb_substr($lastWord, 0, 1, 'UTF-8');
    }
}
$initials = mb_strtoupper($initials, 'UTF-8');
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
                <?= escapeHtml($headerTitle) ?>
            </strong>
        </div>
    </div>
    <a
        class="dashboard-account"
        href="../profile.php"
        aria-label="Open my profile"
    >
        <span class="account-avatar" aria-hidden="true">
            <?= escapeHtml($initials) ?>
        </span>
        <span class="account-details">
            <strong dir="auto">
                <?= escapeHtml($headerName) ?>
            </strong>
            <span>Service provider</span>
        </span>
        <span class="account-arrow" aria-hidden="true">↗</span>
    </a>
</header>