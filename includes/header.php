<?php
/**
 * Shared page header + left menu
 *
 * Every module page includes this after setting:
 *   $pageTitle   = browser tab title
 *   $currentPage = which menu item is active
 *
 * It starts the HTML, shows the sidebar, and prints flash messages.
 * Pair with includes/footer.php at the end of the page.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/functions.php';

ensure_auth_schema();
// Most pages need login. Set $skipAuth = true before include to skip (rare).
if (empty($skipAuth)) {
    require_login();
}

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
    <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>?v=<?= (int) @filemtime(__DIR__ . '/../assets/css/style.css') ?>">
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
            <img src="<?= e(base_url('assets/logo.png')) ?>?v=<?= (int) @filemtime(__DIR__ . '/../assets/logo.png') ?>" alt="ReeferON" class="brand-logo">
            <span class="brand-text">ReeferON CRM</span>
        </a>
        <nav>
            <?php if (can('dashboard.view')): ?>
                <a class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="<?= e(base_url('index.php')) ?>">Dashboard</a>
            <?php endif; ?>
            <?php if (can('prospects.view')): ?>
                <a class="<?= $currentPage === 'prospects' ? 'active' : '' ?>" href="<?= e(base_url('modules/prospects/index.php')) ?>">Prospects</a>
            <?php endif; ?>
            <?php if (can('prospects.create')): ?>
                <a class="<?= $currentPage === 'prospect_create' ? 'active' : '' ?>" href="<?= e(base_url('modules/prospects/create.php')) ?>">New Prospect</a>
            <?php endif; ?>
            <?php if (can('followups.view')): ?>
                <a class="<?= $currentPage === 'followups' ? 'active' : '' ?>" href="<?= e(base_url('modules/followups/index.php')) ?>">Follow-ups</a>
            <?php endif; ?>
            <?php if (can('onboarding.view')): ?>
                <a class="<?= $currentPage === 'onboarding' ? 'active' : '' ?>" href="<?= e(base_url('modules/onboarding/index.php')) ?>">Onboarding</a>
            <?php endif; ?>
            <?php if (can('approvals.view')):
                $pendingApprovals = pending_approvals_count();
                ?>
                <a class="nav-with-dot <?= $currentPage === 'approvals' ? 'active' : '' ?>" href="<?= e(base_url('modules/approvals/index.php')) ?>">
                    Approvals<?php if ($pendingApprovals > 0): ?><span class="nav-dot" title="<?= (int) $pendingApprovals ?> pending"></span><?php endif; ?>
                </a>
            <?php endif; ?>
            <?php if (can('customers.view')): ?>
                <a class="<?= $currentPage === 'customers' ? 'active' : '' ?>" href="<?= e(base_url('modules/customers/index.php')) ?>">Customers</a>
            <?php endif; ?>
            <?php if (can('customers.create')): ?>
                <a class="<?= $currentPage === 'customer_create' ? 'active' : '' ?>" href="<?= e(base_url('modules/customers/create.php')) ?>">Add Existing Customer</a>
            <?php endif; ?>
            <?php if (can('notifications.view')): ?>
                <a class="<?= $currentPage === 'notifications' ? 'active' : '' ?>" href="<?= e(base_url('modules/notifications/index.php')) ?>">Notifications<?php
                    $nUnread = unread_notifications_count();
                    if ($nUnread > 0): ?> (<?= (int) $nUnread ?>)<?php endif;
                ?></a>
            <?php endif; ?>
            <?php
            $mastersOpen = in_array($currentPage, ['roles', 'users', 'reports', 'logs'], true);
            $showMasters = can('roles.manage') || can('users.manage') || can('reports.view') || can('logs.view');
            if ($showMasters):
            ?>
                <div class="nav-group<?= $mastersOpen ? ' open' : '' ?>">
                    <button type="button" class="nav-group-toggle<?= $mastersOpen ? ' active' : '' ?>"
                            aria-expanded="<?= $mastersOpen ? 'true' : 'false' ?>"
                            onclick="(function(btn){var g=btn.closest('.nav-group');if(!g)return;var o=g.classList.toggle('open');btn.setAttribute('aria-expanded',o?'true':'false');btn.classList.toggle('active',o);})(this)">
                        Masters
                        <span class="nav-caret" aria-hidden="true"></span>
                    </button>
                    <div class="nav-sub">
                        <div class="nav-sub-inner">
                            <?php if (can('roles.manage')): ?>
                                <a class="<?= $currentPage === 'roles' ? 'active' : '' ?>" href="<?= e(base_url('modules/roles/index.php')) ?>">Roles</a>
                            <?php endif; ?>
                            <?php if (can('users.manage')): ?>
                                <a class="<?= $currentPage === 'users' ? 'active' : '' ?>" href="<?= e(base_url('modules/users/index.php')) ?>">Users</a>
                            <?php endif; ?>
                            <?php if (can('reports.view')): ?>
                                <a class="<?= $currentPage === 'reports' ? 'active' : '' ?>" href="<?= e(base_url('modules/reports/index.php')) ?>">Reports</a>
                            <?php endif; ?>
                            <?php if (can('logs.view')): ?>
                                <a class="<?= $currentPage === 'logs' ? 'active' : '' ?>" href="<?= e(base_url('modules/logs/index.php')) ?>">Logs</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (can('help.view')): ?>
                <a class="<?= $currentPage === 'help' ? 'active' : '' ?>" href="<?= e(base_url('modules/help/index.php')) ?>">Help</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-foot">
            <div class="user-welcome">Welcome</div>
            <div class="user-name"><?= e($user['name'] ?? 'User') ?></div>
            <a class="btn-logout" href="<?= e(base_url('logout.php')) ?>">Logout</a>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <h1><?= e($pageTitle) ?></h1>
        </header>
        <div class="content">
            <?php
            // Errors stay as page alerts. Success create/edit/Inactive/Active → top-right toast (JS).
            if ($flash && ($flash['type'] ?? '') === 'error'): ?>
                <div class="alert alert-error"><?= e($flash['message']) ?></div>
            <?php elseif ($flash && ($flash['type'] ?? '') === 'success' && empty($flash['action'])): ?>
                <div class="alert alert-success"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <div id="toast-host" class="toast-host" aria-live="polite" aria-atomic="true"
                 data-flash="<?= e(json_encode($flash && ($flash['type'] ?? '') === 'success' && !empty($flash['action']) ? [
                     'type' => $flash['type'],
                     'message' => $flash['message'],
                     'action' => $flash['action'],
                 ] : null, JSON_UNESCAPED_UNICODE)) ?>"></div>
