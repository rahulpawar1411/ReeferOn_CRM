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

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Onboarding List</h2>
    <p class="step-note"><strong>Step 3 – Onboarding list:</strong> Only leads that have started onboarding appear here. To start a new one: open the Prospect → set stage to <strong>Commercial Approved</strong> → click <em>Initiate Customer Onboarding</em>.</p>

    <form class="toolbar" method="get" action="">
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
        <button type="submit" class="btn">Filter</button>
    </form>

    <?php if (!$rows): ?>
        <div class="empty">No onboarding records yet.</div>
    <?php else: ?>
        <table class="data">
            <thead>
            <tr>
                <th>Onboarding ID</th>
                <th>Prospect</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Mobile</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
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
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php render_pagination($pg); ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
