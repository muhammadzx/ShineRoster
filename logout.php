<?php

require_once __DIR__ . '/includes/init.php';

$currentAccount = $auth->currentUser();
if (!$currentAccount) {
    header('Location: login.php');
    exit();
}

$stayUrl = match ($_SESSION['role'] ?? '') {
    'provider' => 'provider/dashboard.php',
    'admin' => 'admin/dashboard.php',
    default => 'services.php'
};
$error = '';

if (empty($_SESSION['logout_csrf'])) {
    $_SESSION['logout_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['logout_csrf'], $token)) {
        $error = 'Unable to confirm your request. Please try again.';
    } else {
        $auth->logout();

        header('Location: login.php', true, 303);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Out | ShineRoster</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="images/favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body class="logout-page">
    <main class="logout-card" aria-labelledby="logout-title">
        <a class="brand" href="index.php" aria-label="ShineRoster home">
            <img
                src="images/ShineRoster_Logo.png"
                alt="ShineRoster"
                width="220"
                height="74"
            >
        </a>
        <span class="logout-icon" aria-hidden="true">↪</span>
        <h1 id="logout-title">Ready to log out?</h1>
        <p>
            You can log back in whenever you need to manage
            your account.
        </p>

        <?php if ($error !== ''): ?>
            <div class="auth-alert auth-alert-error" role="alert">
                <p>
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        <?php endif; ?>

        <form action="logout.php" method="post">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['logout_csrf'], ENT_QUOTES, 'UTF-8') ?>"
            >
            <button class="btn btn-primary" type="submit">
                Yes, log out
                <span aria-hidden="true">↗</span>
            </button>
            <a class="btn btn-secondary" href="<?= escapeHtml($stayUrl) ?>">
                Stay logged in
            </a>
        </form>
    </main>
</body>
</html>