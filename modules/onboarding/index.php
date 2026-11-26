<?php
$pageTitle = 'Customer Onboarding';
$currentPage = 'onboarding';
require_once __DIR__ . '/../../includes/functions.php';

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

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
    $filterActive = ($q !== '' || $status !== '');
    filter_flip_start('Onboarding Filters', $filterActive, base_url('modules/onboarding/index.php'));
    ?>
    <form class="filter-flip-form" method="get" action="">
        <div class="field">
            <label>Search</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="ID, company, mobile, GSTIN">
        </div>
        <div class="field">
            <label>Status</label>
            <select name="status">
                <option value="">All</option>
                <?php foreach (onboarding_statuses() as $s): ?>
                    <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
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
