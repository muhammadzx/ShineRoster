<?php

require_once __DIR__ . '/includes/init.php';
$currentUser = $auth->requireRole('provider', 'login.php');

$userId = (int) $currentUser['id'];
$account = $currentUser;
$providerObject = new Provider($db);

$profile = $providerObject->getProfileByUserId((int) $userId);
$skills = $providerObject->getSkills();

$selectedSkills = $profile ? $providerObject->getSkillIds((int) $profile['id']) : [];

$form = [
    'full_name' => $account['full_name'],
    'email' => $account['email'],
    'phone' => $account['phone'],
    'city' => $profile['city'] ?? '',
    'bio' => $profile['bio'] ?? '',
    'skills' => $selectedSkills
];

$pageTitle = 'My profile';
$activePage = 'profile';

$error = '';
$success = $_SESSION['profile_success'] ?? '';

unset($_SESSION['profile_success']);

if (empty($_SESSION['profile_csrf'])) {
    $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawSkills = $_POST['skills'] ?? [];

    $form = [
        'full_name' => inputText($_POST, 'full_name'),
        'email' => inputText($_POST, 'email'),
        'phone' => inputText($_POST, 'phone'),
        'city' => inputText($_POST, 'city'),
        'bio' => inputText($_POST, 'bio'),
        'skills' => []
    ];

    try {
        if (!hash_equals($_SESSION['profile_csrf'], inputText($_POST, 'csrf_token'))) {
            throw new RuntimeException('Your form has expired. Refresh the page and try again.');
        }

        if (!is_array($rawSkills)) {
            throw new RuntimeException('Invalid skill selection.');
        }

        foreach ($rawSkills as $value) {
            $skillId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($skillId === false) {
                throw new RuntimeException('Invalid skill selection.');
            }

            $form['skills'][] = (int) $skillId;
        }

        $providerObject->saveAccountProfile((int) $userId, $form);

        $_SESSION['full_name'] = $form['full_name'];

        $_SESSION['profile_success'] = 'Your profile has been updated successfully.';

        header('Location: profile.php', true, 303);
        exit();
    } catch (PDOException $e) {
        error_log($e->getMessage());

        $error =
            (int) ($e->errorInfo[1] ?? 0) === 1062
                ? 'This email address is already registered.'
                : 'Your profile could not be saved. Please try again.';
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="./provider/">
    <title>My Profile | ShineRoster</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="../images/favicon.ico" sizes="any">
    <link rel="icon" href="../images/favicon.png" type="image/png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/provider-profile.css">
</head>
<body class="dashboard-page">
    <a class="skip-link" href="../profile.php#dashboard-main">
        Skip to content
    </a>
    <div class="dashboard-layout">
        <input type="checkbox" id="sidebar-toggle" class="sidebar-toggle" aria-label="Toggle navigation menu"
            aria-controls="provider-sidebar">
        <label class="sidebar-overlay" for="sidebar-toggle" aria-hidden="true"></label>

        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <div class="dashboard-workspace">

            <?php require_once __DIR__ . '/includes/header.php'; ?>

            <main class="dashboard-main" id="dashboard-main">
                <div class="dashboard-page-heading">
                    <div>
                        <span class="eyebrow">Introduce yourself</span>
                        <h1>My profile</h1>
                        <p>
                            Keep your account details and provider profile up to date.
                        </p>
                    </div>
                    <a class="btn btn-secondary" href="dashboard.php">
                        Back to overview
                    </a>
                </div>

                <?php if ($success !== ''): ?>
                    <div class="profile-alert profile-alert-success" role="status">
                        <?= escapeHtml($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="profile-alert profile-alert-error" role="alert">
                        <?= escapeHtml($error) ?>
                    </div>
                <?php endif; ?>

                <div class="provider-profile-layout">
                    <aside class="dashboard-card profile-summary" aria-labelledby="profile-summary-title">
                        <div class="profile-avatar" aria-hidden="true">
                            <svg viewBox="0 0 48 48" fill="none">
                                <circle cx="24" cy="16" r="8"></circle>
                                <path d="M9 41v-3a15 15 0 0 1 30 0v3"></path>
                            </svg>
                        </div>
                        <span class="profile-role">Service provider</span>
                        <h2 id="profile-summary-title">
                            <?= escapeHtml($account['full_name']) ?>
                        </h2>
                        <p class="profile-summary-email">
                            <?= escapeHtml($account['email']) ?>
                        </p>
                        <dl class="profile-summary-details">
                            <div>
                                <dt>Account status</dt>
                                <dd>
                                    <span class="status-badge status-completed">
                                        Active
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt>City</dt>
                                <dd>
                                    <?= escapeHtml($profile['city'] ?? '' ?: 'Not added yet') ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Selected skills</dt>
                                <dd><?= count($selectedSkills) ?></dd>
                            </div>
                        </dl>
                        <div class="profile-tip">
                            <strong>Help customers get to know you.</strong>
                            <p>
                                Add a clear description and choose the skills
                                that match the services you offer.
                            </p>
                        </div>
                    </aside>
                    <form class="profile-form" action="../profile.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= escapeHtml( $_SESSION['profile_csrf'] ) ?>">
                        <section class="dashboard-card" aria-labelledby="account-details-title">
                            <div class="card-heading">
                                <div>
                                    <h2 id="account-details-title">
                                        Account details
                                    </h2>
                                    <p>
                                        Manage your name and contact information.
                                    </p>
                                </div>
                            </div>
                            <div class="profile-fields">
                                <div class="profile-field">
                                    <label for="profile-name">Full name</label>
                                    <input type="text" id="profile-name" name="full_name"
                                        value="<?= escapeHtml( $form['full_name'] ) ?>" autocomplete="name" minlength="2"
                                        maxlength="100" required>
                                </div>
                                <div class="profile-form-row">
                                    <div class="profile-field">
                                        <label for="profile-email">
                                            Email address
                                        </label>
                                        <input type="email" id="profile-email" name="email"
                                            value="<?= escapeHtml( $form['email'] ) ?>" autocomplete="email" maxlength="190"
                                            required>
                                    </div>
                                    <div class="profile-field">
                                        <label for="profile-phone">
                                            Phone number
                                        </label>
                                        <input type="tel" id="profile-phone" name="phone"
                                            value="<?= escapeHtml( $form['phone'] ) ?>" placeholder="e.g. 0791234567"
                                            autocomplete="tel" maxlength="20" required>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="dashboard-card" aria-labelledby="provider-details-title">
                            <div class="card-heading">
                                <div>
                                    <h2 id="provider-details-title">
                                        Provider information
                                    </h2>
                                    <p>
                                        Tell customers about your cleaning services.
                                    </p>
                                </div>
                            </div>
                            <div class="profile-fields">
                                <div class="profile-field">
                                    <label for="profile-city">City</label>
                                    <input type="text" id="profile-city" name="city"
                                        value="<?= escapeHtml( $form['city'] ) ?>" placeholder="e.g. Amman"
                                        autocomplete="address-level2" maxlength="100" required>
                                </div>
                                <div class="profile-field">
                                    <label for="profile-bio">
                                        About you
                                        <span class="profile-optional">
                                            (optional)
                                        </span>
                                    </label>
                                    <textarea id="profile-bio" name="bio" rows="6" maxlength="3000"
                                        placeholder="Describe your experience, approach, and the cleaning services you provide."
                                        aria-describedby="profile-bio-hint"><?= escapeHtml( $form['bio'] ) ?></textarea>
                                    <p class="profile-field-hint" id="profile-bio-hint">
                                        Up to 3,000 characters.
                                        Avoid sharing private information here.
                                    </p>
                                </div>
                            </div>
                        </section>
                        <section class="dashboard-card" aria-labelledby="profile-skills-title">
                            <div class="card-heading">
                                <div>
                                    <h2 id="profile-skills-title">
                                        Your cleaning skills
                                    </h2>
                                    <p>
                                        Select all skills that apply.
                                        These are self-reported skills.
                                    </p>
                                </div>
                            </div>
                            <fieldset class="profile-skills">
                                <legend class="visually-hidden">
                                    Select your cleaning skills
                                </legend>

                                <?php foreach ($skills as $skill): ?>
                                    <label class="profile-skill-option">
                                        <input type="checkbox" name="skills[]" value="<?= (int) $skill[ 'id' ] ?>"
                                            <?= in_array((int) $skill['id'], $form['skills'], true) ? 'checked' : '' ?>>
                                        <span dir="auto">
                                            <?= escapeHtml($skill['name']) ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>

                                <?php if (!$skills): ?>
                                    <p class="profile-field-hint">
                                        No selectable skills are currently available.
                                    </p>
                                <?php endif; ?>

                            </fieldset>
                        </section>
                        <div class="profile-save-bar">
                            <p>
                                Name, email, phone, and city are required.
                            </p>
                            <button class="btn btn-primary" type="submit">
                                Save changes
                                <span aria-hidden="true">✓</span>
                            </button>
                        </div>
                    </form>
                </div>
            </main>

            <?php require_once __DIR__ . '/includes/footer.php'; ?>

        </div>
    </div>
</body>
</html>