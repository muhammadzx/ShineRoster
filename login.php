<?php
require_once __DIR__ . '/includes/init.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403); $error = 'The form expired. Reload the page and try again.';
    } elseif (!$auth->allowAttempt('login', 30)) {
        http_response_code(429); $error = 'Too many attempts. Try again in 15 minutes.';
    } else {
        if ($auth->login(inputText($_POST,'email'), $_POST['password'] ?? null)) {
            $destination = match ($_SESSION['role']) {
                'provider' => 'provider/dashboard.php', 'admin' => 'admin/dashboard.php', default => 'services.php'
            };
            header('Location: ' . $destination, true, 303); exit;
        }
        $error = $auth->getError();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Log in | ShineRoster</title>

    <meta name="description" content="Log in to your ShineRoster account to manage your cleaning bookings or services.">

    <meta name="theme-color" content="#0F766E">

    <link rel="icon" href="images/favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.png" type="image/png">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>

<body class="auth-page">

    <a class="skip-link" href="#auth-main">Skip to form</a>

    <main class="auth-layout" id="auth-main">

        <!-- BRAND PANEL -->
        <aside class="auth-brand-panel" aria-labelledby="brand-title">

            <a class="brand auth-brand" href="index.php" aria-label="ShineRoster home">
                <img src="images/ShineRoster_Logo.png" alt="ShineRoster" width="220" height="74">
            </a>

            <div class="auth-brand-content">
                <span class="eyebrow">
                    Your home. Your cleaner. Your choice.
                </span>

                <h2 id="brand-title">
                    Welcome back<br>
                    to a <span>fresh start.</span>
                </h2>

                <p>
                    Whether you’re booking your next clean or managing
                    your services, everything starts here.
                </p>

                <div class="auth-art" aria-hidden="true">
                    <div class="auth-art-circle"></div>

                    <div class="auth-art-house">
                        <svg viewBox="0 0 160 160" fill="none">
                            <path d="M30 76 80 34l50 42"></path>
                            <path d="M43 66v65h74V66"></path>
                            <path d="m61 96 15 15 29-31"></path>
                            <path d="M127 24v24M115 36h24"></path>
                        </svg>
                    </div>

                    <div class="auth-art-label">
                        <span>✓</span>
                        A little help. More time for you.
                    </div>
                </div>
            </div>

            <p class="auth-brand-footer">
                A cleaner space. A calmer day.
            </p>

        </aside>

        <!-- FORM PANEL -->
        <section class="auth-form-panel" aria-labelledby="login-title">

            <div class="auth-form-container">

                <a class="auth-back-link" href="index.php">
                    <span aria-hidden="true">←</span>
                    Back to home
                </a>

                <header class="auth-heading">
                    <span class="eyebrow">Good to see you again</span>
                    <h1 id="login-title">Log in to your account</h1>
                    <p>
                        Enter your details to continue to ShineRoster.
                    </p>
                </header>

                <?php if ($error !== ''): ?><div class="auth-alert auth-alert-error" role="alert"><p><?= escapeHtml($error) ?></p></div><?php endif; ?>

                <?php if (isset($_GET['registered'])): ?><p role="status">Account created. You can now log in.</p><?php endif; ?>
                <form class="auth-form" action="login.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">
                    <div class="auth-field">
                        <label for="login-email">Email address</label>

                        <input type="email" id="login-email" name="email" value="<?= escapeHtml(inputText($_POST,'email')) ?>" placeholder="you@example.com"
                            autocomplete="username" maxlength="190" required>
                    </div>

                    <div class="auth-field">
                        <label for="login-password">Password</label>

                        <input type="password" id="login-password" name="password" placeholder="Enter your password"
                            autocomplete="current-password" required>
                    </div>

                    <button class="btn btn-primary auth-submit" type="submit" name="login" value="1">
                        Log in
                        <span aria-hidden="true">↗</span>
                    </button>
                </form>

                <p class="auth-switch">
                    New to ShineRoster?
                    <a href="register.php">Create an account</a>
                </p>

                <div class="auth-help">
                    <span class="auth-help-icon" aria-hidden="true">?</span>

                    <p>
                        Need help accessing your account?
                        <a href="index.php#contact">Contact us</a>
                    </p>
                </div>

                <footer class="auth-page-footer">
                    © 2026 ShineRoster. All rights reserved.
                </footer>

            </div>

        </section>

    </main>

</body>

</html>