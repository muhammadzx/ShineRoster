<?php
require_once __DIR__ . '/includes/init.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saved = $auth->register(
        inputText($_POST, 'full_name'),
        inputText($_POST, 'email'),
        inputText($_POST, 'phone'),
        inputValue($_POST, 'password'),
        inputText($_POST, 'role'),
        inputValue($_POST, 'confirm_password')
    );
    if ($saved) {
        header('Location: login.php?registered=1', true, 303);
        exit();
    }
    $error = $auth->getError();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an account | ShineRoster</title>
    <meta name="description"
        content="Join ShineRoster as a customer to book home cleaning, or as a provider to offer your cleaning services.">
    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="images/favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body class="auth-page">
    <a class="skip-link" href="#auth-main">Skip to form</a>
    <main class="auth-layout" id="auth-main">
        <aside class="auth-brand-panel" aria-labelledby="brand-title">
            <a class="brand auth-brand" href="index.php" aria-label="ShineRoster home">
                <img src="images/ShineRoster_Logo.png" alt="ShineRoster" width="220" height="74">
            </a>
            <div class="auth-brand-content">
                <span class="eyebrow">Make room for something better</span>
                <h2 id="brand-title">
                    A fresh space.<br>
                    A new <span>opportunity.</span>
                </h2>
                <p>
                    Find the right cleaning provider for your home,
                    or share your skills with customers who need them.
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
                        <span>✦</span>
                        Your next chapter starts here.
                    </div>
                </div>
            </div>
            <p class="auth-brand-footer">
                Bringing homes and helping hands together.
            </p>
        </aside>
        <section class="auth-form-panel" aria-labelledby="register-title">
            <div class="auth-form-container">
                <a class="auth-back-link" href="index.php">
                    <span aria-hidden="true">←</span>
                    Back to home
                </a>
                <header class="auth-heading">
                    <span class="eyebrow">Welcome to ShineRoster</span>
                    <h1 id="register-title">Create your account</h1>
                    <p>
                        Choose how you’d like to join, then add your details.
                    </p>
                </header>


              <?php if (
                  $error !== ''
              ): ?><div class="auth-alert auth-alert-error" role="alert"><p><?= escapeHtml(
    $error
) ?></p></div><?php endif; ?>

                <form class="auth-form" action="register.php" method="post">
                    
                    <fieldset class="account-types">
                        <legend>I want to join as a</legend>
                        <div class="account-type-grid">
                            <label class="account-option">
                                <input class="account-radio" type="radio" name="role" value="customer" <?php if (
                                    inputText($_POST, 'role') !== 'provider'
                                ) {
                                    echo 'checked';
                                } else {
                                    echo '';
                                } ?> required>
                                <span class="account-card">
                                    <span class="account-card-top" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="m3 10 9-7 9 7"></path>
                                            <path d="M5 9v12h14V9"></path>
                                            <path d="M9 21v-8h6v8"></path>
                                        </svg>
                                        <span class="account-indicator"></span>
                                    </span>
                                    <span class="account-title">Customer</span>
                                    <span class="account-description">
                                        Find a cleaner and book a home clean.
                                    </span>
                                </span>
                            </label>
                            <label class="account-option">
                                <input class="account-radio" type="radio" name="role" value="provider" <?php if (
                                    inputText($_POST, 'role') === 'provider'
                                ) {
                                    echo 'checked';
                                } else {
                                    echo '';
                                } ?> required>
                                <span class="account-card">
                                    <span class="account-card-top" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <rect x="3" y="7" width="18" height="14" rx="2"></rect>
                                            <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <path d="M3 12a21 21 0 0 0 18 0"></path>
                                            <path d="M10 12v3h4v-3"></path>
                                        </svg>
                                        <span class="account-indicator"></span>
                                    </span>
                                    <span class="account-title">
                                        Service provider
                                    </span>
                                    <span class="account-description">
                                        Offer cleaning services and manage bookings.
                                    </span>
                                </span>
                            </label>
                        </div>
                        <p class="auth-field-hint">
                            Providers can complete their profile, city,
                            and skills after creating an account.
                        </p>
                    </fieldset>
                    <div class="auth-field">
                        <label for="register-name">Full name</label>
                        <input type="text" id="register-name" name="full_name" value="<?= escapeHtml(
                            inputText($_POST, 'full_name')
                        ) ?>" placeholder="Enter your full name"
                            autocomplete="name" minlength="2" maxlength="100" required>
                    </div>
                    <div class="auth-field">
                        <label for="register-email">Email address</label>
                        <input type="email" id="register-email" name="email" value="<?= escapeHtml(
                            inputText($_POST, 'email')
                        ) ?>" placeholder="you@example.com"
                            autocomplete="username" maxlength="190" required>
                    </div>
                    <div class="auth-field">
                        <label for="register-phone">Phone number</label>
                        <input type="tel" id="register-phone" name="phone" value="<?= escapeHtml(
                            inputText($_POST, 'phone')
                        ) ?>" placeholder="e.g. 0791234567"
                            autocomplete="tel" maxlength="20" required>
                    </div>
                    <div class="auth-form-row">
                        <div class="auth-field">
                            <label for="register-password">Password</label>
                            <input type="password" id="register-password" name="password"
                                placeholder="Create a password" autocomplete="new-password" minlength="8"
                                aria-describedby="password-hint" required>
                        </div>
                        <div class="auth-field">
                            <label for="register-confirm-password">
                                Confirm password
                            </label>
                            <input type="password" id="register-confirm-password" name="confirm_password"
                                placeholder="Repeat your password" autocomplete="new-password" minlength="8" required>
                        </div>
                    </div>
                    <p class="auth-field-hint" id="password-hint">
                        Use at least 8 characters for your password.
                    </p>
                    <button class="btn btn-primary auth-submit" type="submit" name="register" value="1">
                        Create account
                        <span aria-hidden="true">↗</span>
                    </button>
                </form>
                <p class="auth-switch">
                    Already have an account?
                    <a href="login.php">Log in</a>
                </p>
                <footer class="auth-page-footer">
                    © 2026 ShineRoster. All rights reserved.
                </footer>
            </div>
        </section>
    </main>
</body>
</html>