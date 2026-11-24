<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'ReeferON CRM';
$currentPage = $currentPage ?? '';
$flash = get_flash();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | ReeferON CRM</title>
    <link rel="icon" type="image/png" href="<?= e(base_url('assets/logo.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
    <meta name="crm-base" content="<?= e(base_url()) ?>">
</head>
<body>
<div id="app-loader" class="app-loader" hidden aria-live="polite" aria-busy="true">
    <div class="app-loader-card">
        <div class="app-spinner" aria-hidden="true"></div>
        <div class="app-loader-text">Loading…</div>
    </div>
</div>
<div class="app">
    <aside class="sidebar">
        <a class="brand" href="<?= e(base_url('index.php')) ?>">
            <img src="<?= e(base_url('assets/logo.png')) ?>" alt="ReeferON" class="brand-logo">
            <span class="brand-text">ReeferON CRM</span>
        </a>
        <nav>
            <a class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="<?= e(base_url('index.php')) ?>">Dashboard</a>
            <a class="<?= $currentPage === 'prospects' ? 'active' : '' ?>" href="<?= e(base_url('modules/prospects/index.php')) ?>">Prospects</a>
            <a class="<?= $currentPage === 'prospect_create' ? 'active' : '' ?>" href="<?= e(base_url('modules/prospects/create.php')) ?>">New Prospect</a>
            <a class="<?= $currentPage === 'followups' ? 'active' : '' ?>" href="<?= e(base_url('modules/followups/index.php')) ?>">Follow-ups</a>
            <a class="<?= $currentPage === 'onboarding' ? 'active' : '' ?>" href="<?= e(base_url('modules/onboarding/index.php')) ?>">Onboarding</a>
            <a class="<?= $currentPage === 'approvals' ? 'active' : '' ?>" href="<?= e(base_url('modules/approvals/index.php')) ?>">Approvals</a>
            <a class="<?= $currentPage === 'customers' ? 'active' : '' ?>" href="<?= e(base_url('modules/customers/index.php')) ?>">Customers</a>
            <a class="<?= $currentPage === 'customer_create' ? 'active' : '' ?>" href="<?= e(base_url('modules/customers/create.php')) ?>">Add Existing Customer</a>
            <a class="<?= $currentPage === 'reports' ? 'active' : '' ?>" href="<?= e(base_url('modules/reports/index.php')) ?>">Reports</a>
            <a class="<?= $currentPage === 'notifications' ? 'active' : '' ?>" href="<?= e(base_url('modules/notifications/index.php')) ?>">Notifications<?php
                $nUnread = unread_notifications_count();
                if ($nUnread > 0): ?> (<?= (int) $nUnread ?>)<?php endif;
            ?></a>
            <a class="<?= $currentPage === 'logs' ? 'active' : '' ?>" href="<?= e(base_url('modules/logs/index.php')) ?>">Logs</a>
            <a class="<?= $currentPage === 'help' ? 'active' : '' ?>" href="<?= e(base_url('modules/help/index.php')) ?>">Help</a>
        </nav>
        <div class="sidebar-foot">
            Logged in as<br><strong><?= e($user['name']) ?></strong>
            <div class="muted">(temp user – auth later)</div>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <h1><?= e($pageTitle) ?></h1>
        </header>
        <div class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
