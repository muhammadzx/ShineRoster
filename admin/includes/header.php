<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0F766E">
    <title><?= escapeHtml($pageTitle) ?> | ShineRoster Admin</title>
    <link rel="icon" href="../images/favicon.ico" sizes="any">
    <link rel="icon" href="../images/favicon.png" type="image/png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="dashboard-page admin-page">
<a class="skip-link" href="#admin-main">Skip to content</a>
<div class="dashboard-layout">
    <input type="checkbox" id="sidebar-toggle" class="sidebar-toggle" aria-label="Toggle navigation" aria-controls="admin-sidebar">
    <label class="sidebar-overlay" for="sidebar-toggle" aria-hidden="true"></label>
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="dashboard-workspace">
        <header class="dashboard-header">
            <div class="dashboard-header-start">
                <label class="sidebar-toggle-button" for="sidebar-toggle"><span class="burger-lines" aria-hidden="true"><span></span><span></span><span></span></span><span class="visually-hidden">Toggle navigation</span></label>
                <div class="header-location"><span>Administration</span><span aria-hidden="true">/</span><strong><?= escapeHtml(
                    $pageTitle
                ) ?></strong></div>
            </div>
            <a class="dashboard-account" href="account.php" aria-label="Open my owner account">
                <span class="account-avatar" aria-hidden="true"><?= escapeHtml(
                    mb_strtoupper(mb_substr($currentUser['full_name'], 0, 1, 'UTF-8'), 'UTF-8')
                ) ?></span>
                <span class="account-details"><strong><?= escapeHtml(
                    $currentUser['full_name']
                ) ?></strong><span>Site owner</span></span><span class="account-arrow" aria-hidden="true">↗</span>
            </a>
        </header>
        <main class="dashboard-main" id="admin-main">
            <div class="admin-heading"><div><span class="eyebrow">ShineRoster administration</span><h1><?= escapeHtml(
                $pageTitle
            ) ?></h1><p><?= escapeHtml(
    $pageDescription
) ?></p></div><a class="admin-button admin-button-light" href="../index.php">View website ↗</a></div>
            <?php if (
                $error !== ''
            ): ?><div class="admin-alert admin-alert-error" role="alert"><?= escapeHtml(
    $error
) ?></div><?php endif; ?>
            <?php if (
                $success !== ''
            ): ?><div class="admin-alert admin-alert-success" role="status"><?= escapeHtml(
    $success
) ?></div><?php endif; ?>
