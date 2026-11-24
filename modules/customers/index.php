<?php
$pageTitle = 'Customer Master';
$currentPage = 'customers';
require_once __DIR__ . '/../../includes/functions.php';

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$source = $_GET['source'] ?? '';

$sql = 'SELECT * FROM customers WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (customer_code LIKE ? OR customer_name LIKE ? OR mobile LIKE ? OR gstin LIKE ? OR contact_person LIKE ?)';
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if (in_array($status, customer_statuses(), true)) {
    $sql .= ' AND status = ?';
    $params[] = $status;
}
if (in_array($source, ['New Onboarding', 'Existing Customer'], true)) {
    $sql .= ' AND source = ?';
    $params[] = $source;
}
$sql .= ' ORDER BY id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toolbar">
    <a class="btn" href="<?= e(base_url('modules/customers/create.php')) ?>">Add Existing Customer</a>
</div>

<div class="card">
    <h2>Existing Customer Master</h2>
    <p class="step-note"><strong>Official customers:</strong> From Admin-approved onboarding, or added directly here (skip prospect pipeline). Soft status only — no permanent delete.</p>

    <form class="toolbar" method="get" action="">
        <div class="field">
            <label>Search</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Code / name / mobile / GSTIN">
        </div>
        <div class="field">
            <label>Status</label>
            <select name="status">
                <option value="">All</option>
                <?php foreach (customer_statuses() as $s): ?>
                    <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Source</label>
            <select name="source">
                <option value="">All</option>
                <?php foreach (['New Onboarding', 'Existing Customer'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $source === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn">Filter</button>
    </form>

    <?php if (!$rows): ?>
        <div class="empty">No customers yet. Approve an onboarding or <a href="<?= e(base_url('modules/customers/create.php')) ?>">add an existing customer</a>.</div>
    <?php else: ?>
        <table class="data">
            <thead>
            <tr>
                <th>Customer Code</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Mobile</th>
                <th>GSTIN</th>
                <th>Source</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['customer_code']) ?></td>
                    <td><?= e($r['customer_name']) ?></td>
                    <td><?= e($r['contact_person']) ?></td>
                    <td><?= e($r['mobile']) ?></td>
                    <td><?= e($r['gstin'] ?? '-') ?></td>
                    <td><?= e($r['source']) ?></td>
                    <td><span class="badge"><?= e($r['status']) ?></span></td>
                    <td>
                        <a href="<?= e(base_url('modules/customers/view.php?id=' . $r['id'])) ?>">View</a>
                        |
                        <a href="<?= e(base_url('modules/customers/edit.php?id=' . $r['id'])) ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
