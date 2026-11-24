<?php
$pageTitle = 'Logs';
$currentPage = 'logs';
require_once __DIR__ . '/../../includes/functions.php';

$module = trim($_GET['module'] ?? '');
$action = trim($_GET['action'] ?? '');
$q = trim($_GET['q'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$export = isset($_GET['export']) && $_GET['export'] === '1';

$where = ' WHERE 1=1';
$params = [];

if ($module !== '') {
    $where .= ' AND module = ?';
    $params[] = $module;
}
if ($action !== '') {
    $where .= ' AND action = ?';
    $params[] = $action;
}
if ($q !== '') {
    $where .= ' AND (record_id LIKE ? OR action LIKE ? OR performed_by_name LIKE ? OR old_value LIKE ? OR new_value LIKE ? OR module LIKE ?)';
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where .= ' AND created_at >= ?';
    $params[] = $from . ' 00:00:00';
}
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where .= ' AND created_at <= ?';
    $params[] = $to . ' 23:59:59';
}

if ($export) {
    $stmt = db()->prepare('SELECT * FROM audit_logs' . $where . ' ORDER BY id DESC LIMIT 5000');
    $stmt->execute($params);
    $exportRows = $stmt->fetchAll();
    $headers = ['When', 'Module', 'Record', 'Action', 'Old Value', 'New Value', 'By'];
    $out = [];
    foreach ($exportRows as $r) {
        $out[] = [
            $r['created_at'],
            $r['module'],
            $r['record_id'],
            $r['action'],
            $r['old_value'] ?? '',
            $r['new_value'] ?? '',
            $r['performed_by_name'],
        ];
    }
    export_csv('reeferon_logs_' . date('Ymd_His') . '.csv', $headers, $out);
}

[$rows, $pg] = paginate_sql(
    'SELECT COUNT(*) FROM audit_logs' . $where,
    'SELECT * FROM audit_logs' . $where . ' ORDER BY id DESC',
    $params,
    page_size_logs()
);

$keepQuery = array_filter([
    'module' => $module,
    'action' => $action,
    'from' => $from,
    'to' => $to,
    'q' => $q,
], static fn($v) => $v !== '' && $v !== null);

$renderLogRows = static function (array $rows): string {
    ob_start();
    foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['created_at']) ?></td>
                    <td><span class="badge"><?= e($r['module']) ?></span></td>
                    <td><?= e($r['record_id']) ?></td>
                    <td><?= e($r['action']) ?></td>
                    <td><?= e(($r['old_value'] ?? '-') . ' → ' . ($r['new_value'] ?? '-')) ?></td>
                    <td><?= e($r['performed_by_name']) ?></td>
                </tr>
    <?php endforeach;
    return (string) ob_get_clean();
};

if (wants_ajax()) {
    json_response(lazy_ajax_payload($pg, $renderLogRows($rows), $keepQuery));
}

$modules = db()->query('SELECT DISTINCT module FROM audit_logs ORDER BY module')->fetchAll(PDO::FETCH_COLUMN);
$actions = db()->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);

$counts = [
    'total' => 0,
    'today' => 0,
];
try {
    $counts['total'] = (int) db()->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
    $counts['today'] = (int) db()->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
} catch (Throwable $e) {
    // ignore
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Activity Logs</h2>
    <p class="step-note">
        Every important CRM action is recorded here (create, update, stage change, follow-up, document upload,
        submit, approve/reject, customer code). Total: <strong><?= (int) $counts['total'] ?></strong>
        — today: <strong><?= (int) $counts['today'] ?></strong>
        — <strong><?= (int) page_size_logs() ?></strong> rows per page.
    </p>

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
            <label>Action</label>
            <select name="action">
                <option value="">All</option>
                <?php foreach ($actions as $a): ?>
                    <option value="<?= e($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= e($a) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>From</label>
            <input type="date" name="from" value="<?= e($from) ?>">
        </div>
        <div class="field">
            <label>To</label>
            <input type="date" name="to" value="<?= e($to) ?>">
        </div>
        <div class="field">
            <label>Search</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Record / action / user">
        </div>
        <button type="submit" class="btn">Filter</button>
        <a class="btn btn-secondary" href="?<?= e(http_build_query(array_filter([
            'module' => $module,
            'action' => $action,
            'from' => $from,
            'to' => $to,
            'q' => $q,
            'export' => '1',
        ], static fn($v) => $v !== '' && $v !== null))) ?>">Excel (CSV)</a>
    </form>

    <?php if (!$rows): ?>
        <div class="empty">No log rows match. Use the CRM (create prospect, change stage, upload docs…) and refresh.</div>
    <?php else: ?>
        <table class="data" data-lazy="1">
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
            <?= $renderLogRows($rows) ?>
            </tbody>
        </table>
        <?php render_pagination($pg, $keepQuery); ?>
    <?php endif; ?>
</div>

<div class="card">
    <h2>What gets logged</h2>
    <ul>
        <li><strong>prospects</strong> — CREATE, UPDATE, STAGE_CHANGE</li>
        <li><strong>followups</strong> — CREATE, COMPLETE</li>
        <li><strong>onboarding</strong> — CREATE, UPDATE, SUBMIT, APPROVED, REJECTED</li>
        <li><strong>onboarding_documents</strong> — UPLOAD / status change, DOWNLOAD</li>
        <li><strong>customers</strong> — CREATE (from approval or direct add), UPDATE</li>
    </ul>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
