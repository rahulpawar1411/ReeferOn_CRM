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
    <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">ReeferON CRM</div>
        <nav>
            <a class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="<?= e(base_url('index.php')) ?>">Dashboard</a>
            <a class="<?= $currentPage === 'prospects' ? 'active' : '' ?>" href="<?= e(base_url('modules/prospects/index.php')) ?>">Prospects</a>
            <a class="<?= $currentPage === 'prospect_create' ? 'active' : '' ?>" href="<?= e(base_url('modules/prospects/create.php')) ?>">New Prospect</a>
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
