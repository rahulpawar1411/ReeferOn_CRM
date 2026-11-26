<?php
/**
 * Customer Master list
 *
 * Official customers from approved onboarding or added directly.
 * Excel export uses current filters. Permission: customers.view
 */
$pageTitle = 'Customer Master';
$currentPage = 'customers';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('customers.view');

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$source = $_GET['source'] ?? '';
$export = isset($_GET['export']) && $_GET['export'] === '1';

// Build list filters from GET params
$where = ' WHERE 1=1';
$params = [];
if ($q !== '') {
    $where .= ' AND (customer_code LIKE ? OR customer_name LIKE ? OR mobile LIKE ? OR gstin LIKE ? OR contact_person LIKE ?)';
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if (in_array($status, customer_statuses(), true)) {
    $where .= ' AND status = ?';
    $params[] = $status;
}
if (in_array($source, ['New Onboarding', 'Existing Customer'], true)) {
    $where .= ' AND source = ?';
    $params[] = $source;
}

// Excel export (current filters, max 5000 rows)
if ($export) {
    $stmt = db()->prepare('SELECT * FROM customers' . $where . ' ORDER BY id DESC LIMIT 5000');
    $stmt->execute($params);
    $exportRows = $stmt->fetchAll();
    $headers = [
        'Customer Code', 'Name', 'Brand', 'Business Type', 'Contact', 'Phone', 'Email',
        'GSTIN', 'Source', 'Status', 'Payment Terms', 'Credit Limit', 'Account Owner', 'Created',
    ];
    $out = [];
    foreach ($exportRows as $r) {
        $out[] = [
            $r['customer_code'],
            $r['customer_name'],
            $r['brand_name'] ?? '',
            $r['business_type'],
            $r['contact_person'],
            $r['mobile'],
            $r['email'] ?? '',
            $r['gstin'] ?? '',
            $r['source'],
            $r['status'],
            $r['payment_terms'] ?? '',
            $r['credit_limit'] ?? '',
            $r['account_owner_name'] ?? '',
            $r['created_at'] ?? '',
        ];
    }
    export_csv('reeferon_customers_' . date('Ymd_His') . '.csv', $headers, $out);
}

[$rows, $pg] = paginate_sql(
    'SELECT COUNT(*) FROM customers' . $where,
    'SELECT * FROM customers' . $where . ' ORDER BY id DESC',
    $params,
    page_size_default()
);

$renderCustomerRows = static function (array $rows): string {
    ob_start();
    foreach ($rows as $r): ?>
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
                        <?php if (can('customers.edit')): ?>
                            | <a href="<?= e(base_url('modules/customers/edit.php?id=' . $r['id'])) ?>">Edit</a>
                        <?php endif; ?>
                        <?php if (can('customers.delete') && ($r['status'] ?? '') !== 'Inactive'): ?>
                            | <a class="link-danger" href="<?= e(base_url('modules/customers/delete.php?id=' . $r['id'])) ?>">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
    <?php endforeach;
    return (string) ob_get_clean();
};

if (wants_ajax()) {
    json_response(lazy_ajax_payload($pg, $renderCustomerRows($rows)));
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Existing Customer Master</h2>
    <p class="step-note"><strong>Official customers:</strong> From Admin-approved onboarding, or added directly here. <strong>Delete</strong> = soft Inactive (code + history kept).</p>

    <?php
    $filterActive = ($q !== '' || $status !== '' || $source !== '');
    $excelUrl = base_url('modules/customers/index.php') . '?' . http_build_query(array_filter([
        'q' => $q,
        'status' => $status,
        'source' => $source,
        'export' => '1',
    ], static fn($v) => $v !== '' && $v !== null));
    $leading = '';
    if (can('customers.create')) {
        $leading .= '<a class="btn" href="' . e(base_url('modules/customers/create.php')) . '">Add Existing Customer</a> ';
    }
    $leading .= '<a class="btn btn-secondary" href="' . e($excelUrl) . '">Excel (CSV)</a>';
    filter_flip_start('Customer Filters', $filterActive, base_url('modules/customers/index.php'), $leading);
    ?>
    <form class="filter-flip-form" method="get" action="">
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
        <div class="filter-flip-actions">
            <button type="submit" class="btn">Apply Filters</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/customers/index.php')) ?>">Reset</a>
        </div>
    </form>
    <?php filter_flip_end(); ?>

    <?php if (!$rows): ?>
        <div class="empty">No customers yet. Approve an onboarding or <a href="<?= e(base_url('modules/customers/create.php')) ?>">add an existing customer</a>.</div>
    <?php else: ?>
        <table class="data" data-lazy="1">
            <thead>
            <tr>
                <th>Customer Code</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Phone</th>
                <th>GSTIN</th>
                <th>Source</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?= $renderCustomerRows($rows) ?>
            </tbody>
        </table>
        <?php render_pagination($pg); ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
