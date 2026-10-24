<?php
$accountName = $currentAccount['full_name'] ?? '';
$accountRole = $currentAccount['role'] ?? '';
$accountInitial =
    $accountName !== '' ? mb_strtoupper(mb_substr($accountName, 0, 1, 'UTF-8'), 'UTF-8') : '';
$workspaceUrl = match ($accountRole) {
    'provider' => 'provider/dashboard.php',
    'admin' => 'admin/dashboard.php',
    default => ''
};
$catalogPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<header class="catalog-header">
    <div class="catalog-container catalog-header-inner">
        <a
            class="catalog-brand"
            href="index.php"
            aria-label="ShineRoster home"
        >
            <img
                src="images/ShineRoster_Logo.png"
                alt="ShineRoster"
                width="220"
                height="74"
            >
        </a>
        <nav class="catalog-nav" aria-label="Main navigation">
            <a href="index.php" <?= $catalogPage === 'index.php' ? 'aria-current="page"' : '' ?>>
                Home
            </a>
            <a href="services.php" <?= $catalogPage === 'services.php' ? 'aria-current="page"' : '' ?>>
                Services
            </a>
            <a href="providers.php" <?= $catalogPage === 'providers.php' ? 'aria-current="page"' : '' ?>>
                Find a cleaner
            </a>

            <?php if ($accountRole === 'customer'): ?>
                <a href="my-bookings.php" <?= $catalogPage === 'my-bookings.php' ? 'aria-current="page"' : '' ?>>
                    My bookings
                </a>
            <?php else: ?>
                <a href="index.php#how-it-works">
                    How it works
                </a>
            <?php endif; ?>

            <a href="index.php#contact">
                Contact
            </a>
        </nav>
        <div class="catalog-header-actions">

            <?php if ($currentAccount): ?>

                <?php if ($workspaceUrl !== ''): ?>
                    <a
                        class="catalog-login"
                        href="<?= escapeHtml($workspaceUrl) ?>"
                    >
                        Dashboard
                    </a>
                <?php endif; ?>

                <span
                    class="catalog-account"
                    title="<?= escapeHtml($accountName) ?>"
                >
                    <span
                        class="catalog-avatar"
                        aria-hidden="true"
                    >
                        <?= escapeHtml($accountInitial) ?>
                    </span>
                    <span
                        class="catalog-account-name"
                        dir="auto"
                    >
                        <?= escapeHtml($accountName) ?>
                    </span>
                </span>
                <a class="catalog-login" href="logout.php">
                    Log out
                </a>

            <?php else: ?>

                <a class="catalog-login" href="login.php">
                    Log in
                </a>
                <a
                    class="catalog-button catalog-button-primary"
                    href="register.php"
                >
                    Get started
                    <span aria-hidden="true">↗</span>
                </a>

            <?php endif; ?>

        </div>
    </div>
</header>
