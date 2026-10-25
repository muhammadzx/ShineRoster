<?php
if (isset($currentAccount['full_name'])) {
    $accountName = $currentAccount['full_name'];
} else {
    $accountName = '';
}
if (isset($currentAccount['role'])) {
    $accountRole = $currentAccount['role'];
} else {
    $accountRole = '';
}
if ($accountName !== '') {
    $accountInitial = mb_strtoupper(mb_substr($accountName, 0, 1, 'UTF-8'), 'UTF-8');
} else {
    $accountInitial = '';
}
if ($accountRole === 'provider') {
    $workspaceUrl = 'provider/dashboard.php';
} elseif ($accountRole === 'admin') {
    $workspaceUrl = 'admin/dashboard.php';
} else {
    $workspaceUrl = '';
}

if (isset($_SERVER['SCRIPT_NAME'])) {
    $catalogPage = basename($_SERVER['SCRIPT_NAME']);
} else {
    $catalogPage = basename('');
}
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
            <a href="index.php" <?php if ($catalogPage === 'index.php') {
                echo 'aria-current="page"';
            } else {
                echo '';
            } ?>>
                Home
            </a>
            <a href="services.php" <?php if ($catalogPage === 'services.php') {
                echo 'aria-current="page"';
            } else {
                echo '';
            } ?>>
                Services
            </a>
            <a href="providers.php" <?php if ($catalogPage === 'providers.php') {
                echo 'aria-current="page"';
            } else {
                echo '';
            } ?>>
                Find a cleaner
            </a>

            <?php if ($accountRole === 'customer'): ?>
                <a href="my-bookings.php" <?php if ($catalogPage === 'my-bookings.php') {
                    echo 'aria-current="page"';
                } else {
                    echo '';
                } ?>>
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
