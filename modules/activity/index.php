<?php
$pageTitle = 'Activity / Audit Trail';
$currentPage = 'activity';
require_once __DIR__ . '/../../includes/functions.php';

$module = trim($_GET['module'] ?? '');
$q = trim($_GET['q'] ?? '');

$sql = 'SELECT * FROM audit_logs WHERE 1=1';
$params = [];
if ($module !== '') {
    $sql .= ' AND module = ?';
    $params[] = $module;
}
if ($q !== '') {
    $sql .= ' AND (record_id LIKE ? OR action LIKE ? OR performed_by_name LIKE ? OR old_value LIKE ? OR new_value LIKE ?)';
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
$sql .= ' ORDER BY id DESC LIMIT 300';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$modules = db()->query('SELECT DISTINCT module FROM audit_logs ORDER BY module')->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Activity History / Audit Trail</h2>
    <p class="step-note">Shows create / update / stage / approval / customer code actions recorded by the CRM.</p>

    <form class="toolbar" method="get" action="">
        <div class="field">
            <label>Module</label>
            <select name="module">
                <option value="">All</option>
                <?php foreach ($modules as $m): ?>
                    <option value="<?= e($m) ?>" <?= $module === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Search</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Record / action / user">
        </div>
        <button type="submit" class="btn">Filter</button>
    </form>

    <?php if (!$rows): ?>
        <div class="empty">No audit rows match.</div>
    <?php else: ?>
        <table class="data">
            <thead>
            <tr>
                <th>When</th>
                <th>Module</th>
                <th>Record</th>
                <th>Action</th>
                <th>Old → New</th>
                <th>By</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['created_at']) ?></td>
                    <td><?= e($r['module']) ?></td>
                    <td><?= e($r['record_id']) ?></td>
                    <td><?= e($r['action']) ?></td>
                    <td><?= e(($r['old_value'] ?? '-') . ' → ' . ($r['new_value'] ?? '-')) ?></td>
                    <td><?= e($r['performed_by_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
