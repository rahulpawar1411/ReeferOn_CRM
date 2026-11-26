<?php
/**
 * Onboarding list
 *
 * Customer onboarding records with search, status filter, Excel export, Prev / Next.
 * Permission: onboarding.view
 */
$pageTitle = 'Customer Onboarding';
$currentPage = 'onboarding';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('onboarding.view');

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$export = isset($_GET['export']) && $_GET['export'] === '1';

// Build list filters from GET params
$where = ' WHERE 1=1';
$params = [];

if ($q !== '') {
    $where .= ' AND (onboarding_id LIKE ? OR prospect_id LIKE ? OR customer_name LIKE ? OR mobile LIKE ? OR gstin LIKE ?)';
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($status !== '' && in_array($status, onboarding_statuses(), true)) {
    $where .= ' AND overall_status = ?';
    $params[] = $status;
}
if ($date_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $where .= ' AND DATE(created_at) >= ?';
    $params[] = $date_from;
}
if ($date_to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $where .= ' AND DATE(created_at) <= ?';
    $params[] = $date_to;
}

// Excel export: From + To date required (created_at), max 5000 rows
if ($export) {
    $dateErr = export_date_range_error($date_from, $date_to);
    if ($dateErr !== null) {
        flash('error', $dateErr);
        redirect(base_url('modules/onboarding/index.php?' . http_build_query(array_filter([
            'q' => $q, 'status' => $status,
            'date_from' => $date_from, 'date_to' => $date_to,
        ], static fn($v) => $v !== '' && $v !== null))));
    }
    $stmt = db()->prepare('SELECT * FROM onboarding' . $where . ' ORDER BY id DESC LIMIT 5000');
    $stmt->execute($params);
    $exportRows = $stmt->fetchAll();
    $headers = [
        'Onboarding ID', 'Prospect ID', 'Customer', 'Contact', 'Phone', 'Email',
        'GSTIN', 'PAN', 'Service', 'Temperature', 'Status', 'Payment Terms', 'Credit Limit', 'Created',
    ];
    $out = [];
    foreach ($exportRows as $r) {
        $out[] = [
            $r['onboarding_id'],
            $r['prospect_id'],
            $r['customer_name'],
            $r['contact_person'],
            $r['mobile'],
            $r['email'] ?? '',
            $r['gstin'] ?? '',
            $r['pan'] ?? '',
            $r['service_required'] ?? '',
            $r['temperature_requirement'] ?? '',
            $r['overall_status'],
            $r['payment_terms'] ?? '',
            $r['credit_limit'] ?? '',
            $r['created_at'] ?? '',
        ];
    }
    export_csv('reeferon_onboarding_' . date('Ymd_His') . '.csv', $headers, $out);
}

[$rows, $pg] = paginate_sql(
    'SELECT COUNT(*) FROM onboarding' . $where,
    'SELECT * FROM onboarding' . $where . ' ORDER BY id DESC',
    $params,
    page_size_default()
);

$renderOnboardingRows = static function (array $rows): string {
    ob_start();
    foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['onboarding_id']) ?></td>
                    <td>
                        <a href="<?= e(base_url('modules/prospects/view.php?id=' . $r['prospect_pk'])) ?>">
                            <?= e($r['prospect_id']) ?>
                        </a>
                    </td>
                    <td><?= e($r['customer_name']) ?></td>
                    <td><?= e($r['contact_person']) ?></td>
                    <td><?= e($r['mobile']) ?></td>
                    <td><span class="badge"><?= e($r['overall_status']) ?></span></td>
                    <td><?= e($r['created_at']) ?></td>
                    <td>
                        <a href="<?= e(base_url('modules/onboarding/view.php?id=' . $r['id'])) ?>">View</a>
                        |
                        <a href="<?= e(base_url('modules/onboarding/edit.php?id=' . $r['id'])) ?>">Edit</a>
                    </td>
                </tr>
    <?php endforeach;
    return (string) ob_get_clean();
};

if (wants_ajax()) {
    json_response(lazy_ajax_payload($pg, $renderOnboardingRows($rows)));
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Onboarding List</h2>
    <p class="step-note"><strong>Step 3 – Onboarding list:</strong> Only leads that have started onboarding appear here. To start a new one: open the Prospect → set stage to <strong>Commercial Approved</strong> → click <em>Initiate Customer Onboarding</em>.</p>

    <?php
    $filterActive = ($status !== '' || $date_from !== '' || $date_to !== '');
    $excelUrl = base_url('modules/onboarding/index.php') . '?' . http_build_query(array_filter([
        'q' => $q,
        'status' => $status,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'export' => '1',
    ], static fn($v) => $v !== '' && $v !== null));
    $leading = '<a class="btn btn-secondary js-export-excel" href="' . e($excelUrl) . '"'
        . ' data-from-param="date_from" data-to-param="date_to"'
        . ' title="Select From & To date in Filters first">Excel (CSV)</a>';
    filter_flip_start(
        'Onboarding Filters',
        $filterActive,
        base_url('modules/onboarding/index.php'),
        $leading,
        ['name' => 'q', 'value' => $q, 'placeholder' => 'Search ID / company / mobile / GSTIN']
    );
    ?>
    <form class="filter-flip-form" method="get" action="">
        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
        <div class="field">
            <label>Status</label>
            <select name="status">
                <option value="">All</option>
                <?php foreach (onboarding_statuses() as $s): ?>
                    <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>From date <span class="required">*</span> (for Excel)</label>
            <input type="date" name="date_from" value="<?= e($date_from) ?>">
        </div>
        <div class="field">
            <label>To date <span class="required">*</span> (for Excel)</label>
            <input type="date" name="date_to" value="<?= e($date_to) ?>">
        </div>
        <p class="step-note" style="margin:0 0 8px">Excel export needs <strong>From</strong> and <strong>To</strong> dates (created date).</p>
        <div class="filter-flip-actions">
            <button type="submit" class="btn">Apply Filters</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/onboarding/index.php')) ?>">Reset</a>
        </div>
    </form>
    <?php filter_flip_end(); ?>

    <?php if (!$rows): ?>
        <div class="empty">No onboarding records yet.</div>
    <?php else: ?>
        <table class="data" data-lazy="1">
            <thead>
            <tr>
                <th>Onboarding ID</th>
                <th>Prospect</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?= $renderOnboardingRows($rows) ?>
            </tbody>
        </table>
        <?php render_pagination($pg); ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
