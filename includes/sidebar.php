<?php

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? ''); ?>

<aside class="dashboard-sidebar" id="provider-sidebar">
    <label
        class="sidebar-close"
        for="sidebar-toggle"
        aria-label="Close navigation menu"
    >
        <span aria-hidden="true">×</span>
    </label>
    <a
        class="brand dashboard-brand"
        href="dashboard.php"
        aria-label="ShineRoster provider dashboard"
    >
        <img
            src="../images/ShineRoster_Logo.png"
            alt="ShineRoster"
            width="220"
            height="74"
        >
    </a>
    <span class="sidebar-label">Provider workspace</span>
    <nav class="sidebar-nav" aria-label="Provider navigation">
        <a
            href="dashboard.php"
            <?= $currentPage === 'dashboard.php' ? 'aria-current="page"' : '' ?>
        >
            <span class="nav-icon" aria-hidden="true">▦</span>
            Overview
        </a>
        <a
            href="services.php"
            <?= $currentPage === 'services.php' ? 'aria-current="page"' : '' ?>
        >
            <span class="nav-icon" aria-hidden="true">✦</span>
            My services
        </a>
        <a
            href="slots.php"
            <?= $currentPage === 'slots.php' ? 'aria-current="page"' : '' ?>
        >
            <span class="nav-icon" aria-hidden="true">▤</span>
            My appointments
        </a>
        <a
            href="bookings.php"
            <?= $currentPage === 'bookings.php' ? 'aria-current="page"' : '' ?>
        >
            <span class="nav-icon" aria-hidden="true">▣</span>
            Bookings
        </a>
        <a
            href="../profile.php"
            <?= $currentPage === 'profile.php' ? 'aria-current="page"' : '' ?>
        >
            <span class="nav-icon" aria-hidden="true">◎</span>
            My profile
        </a>
    </nav>
    <div class="sidebar-bottom">
        <div class="sidebar-tip">
            <span aria-hidden="true">✧</span>
            <h2>Make room for new bookings.</h2>
            <p>
                Keep your services and available appointments up to date.
            </p>
            <a href="slots.php">
                Manage appointments
                <span aria-hidden="true">↗</span>
            </a>
        </div>
        <a class="sidebar-help" href="../index.php#contact">
            <span aria-hidden="true">?</span>
            Help & contact
        </a>
        <a class="sidebar-logout" href="../logout.php">
            <span aria-hidden="true">↪</span>
            Log out
        </a>
    </div>
</aside>