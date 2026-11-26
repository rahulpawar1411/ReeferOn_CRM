<?php
/**
 * Prospects list
 *
 * Shows all sales leads with search + Filters drawer + Excel export.
 * Permission: prospects.view
 */
$pageTitle = 'Prospects';
$currentPage = 'prospects';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('prospects.view');

$q = trim($_GET['q'] ?? '');
$stage = trim($_GET['stage'] ?? '');
$business_type = trim($_GET['business_type'] ?? '');
$city = trim($_GET['city'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$export = isset($_GET['export']) && $_GET['export'] === '1';

// Build list filters from GET params
$where = ' WHERE 1=1';
$params = [];

if ($q !== '') {
    $where .= ' AND (
        prospect_id LIKE ? OR company_name LIKE ? OR contact_person LIKE ?
        OR mobile LIKE ? OR email LIKE ?
    )';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($stage !== '') {
    $where .= ' AND pipeline_stage = ?';
    $params[] = $stage;
}
if ($business_type !== '') {
    $where .= ' AND business_type = ?';
    $params[] = $business_type;
}
if ($city !== '') {
    $where .= ' AND city LIKE ?';
    $params[] = '%' . $city . '%';
}
if ($date_from !== '') {
    $where .= ' AND lead_creation_date >= ?';
    $params[] = $date_from;
}
if ($date_to !== '') {
    $where .= ' AND lead_creation_date <= ?';
    $params[] = $date_to;
}

// Excel export (current filters, max 5000 rows)
if ($export) {
    $stmt = db()->prepare('SELECT * FROM prospects' . $where . ' ORDER BY id DESC LIMIT 5000');
    $stmt->execute($params);
    $exportRows = $stmt->fetchAll();
    $headers = [
        'Prospect ID', 'Lead Date', 'Company', 'Contact', 'Designation', 'Phone', 'Email',
        'Business Type', 'Industry', 'City', 'Service', 'Temperature', 'Stage', 'Status',
        'Salesperson', 'Next Follow-up', 'Created',
    ];
    $out = [];
    foreach ($exportRows as $r) {
        $out[] = [
            $r['prospect_id'],
            $r['lead_creation_date'],
            $r['company_name'],
            $r['contact_person'],
            $r['designation'] ?? '',
            $r['mobile'],
            $r['email'] ?? '',
            $r['business_type'],
            $r['industry'] ?? '',
            $r['city'] ?? '',
            $r['service_required'],
            $r['temperature_requirement'] ?? '',
            $r['pipeline_stage'],
            $r['status'],
            $r['salesperson_name'] ?? '',
            $r['next_followup_date'] ?? '',
            $r['created_at'] ?? '',
        ];
    }
    export_csv('reeferon_prospects_' . date('Ymd_His') . '.csv', $headers, $out);
}

[$rows, $pg] = paginate_sql(
    'SELECT COUNT(*) FROM prospects' . $where,
    'SELECT * FROM prospects' . $where . ' ORDER BY id DESC',
    $params,
    page_size_default()
);

$renderProspectRows = static function (array $rows): string {
    ob_start();
    foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['prospect_id']) ?></td>
                    <td><?= e($row['lead_creation_date']) ?></td>
                    <td><?= e($row['company_name']) ?></td>
                    <td><?= e($row['contact_person']) ?></td>
                    <td><?= e($row['mobile']) ?></td>
                    <td><?= e($row['city'] ?? '-') ?></td>
                    <td><?= e($row['service_required']) ?></td>
                    <td><span class="badge"><?= e($row['pipeline_stage']) ?></span></td>
                    <td><?= e($row['next_followup_date'] ?? '-') ?></td>
                    <td>
                        <a href="<?= e(base_url('modules/prospects/view.php?id=' . $row['id'])) ?>">View</a>
                        <?php if (can('prospects.edit')): ?>
                            | <a href="<?= e(base_url('modules/prospects/edit.php?id=' . $row['id'])) ?>">Edit</a>
                        <?php endif; ?>
                        <?php if (can('prospects.delete') && ($row['status'] ?? '') !== 'Inactive'): ?>
                            | <a class="link-danger" href="<?= e(base_url('modules/prospects/delete.php?id=' . $row['id'])) ?>">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
    <?php endforeach;
    return (string) ob_get_clean();
};

if (wants_ajax()) {
    json_response(lazy_ajax_payload($pg, $renderProspectRows($rows)));
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <p class="step-note"><strong>Step 1 – Prospects list:</strong> All sales leads are listed here. Add a new lead with <em>+ New Prospect</em>. Open a lead to update stage or follow-ups. <?= (int) page_size_default() ?> rows per page.</p>
    <?php
    $filterActive = ($q !== '' || $stage !== '' || $business_type !== '' || $city !== '' || $date_from !== '' || $date_to !== '');
    $excelUrl = base_url('modules/prospects/index.php') . '?' . http_build_query(array_filter([
        'q' => $q,
        'stage' => $stage,
        'business_type' => $business_type,
        'city' => $city,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'export' => '1',
    ], static fn($v) => $v !== '' && $v !== null));
    $leading = '<a class="btn" href="' . e(base_url('modules/prospects/create.php')) . '">+ New Prospect</a> '
        . '<a class="btn btn-secondary" href="' . e($excelUrl) . '">Excel (CSV)</a>';
    filter_flip_start('Prospect Filters', $filterActive, base_url('modules/prospects/index.php'), $leading);
    ?>
    <form method="get" class="filter-flip-form" action="">
        <div class="field">
            <label>Search</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="ID / company / mobile / email">
        </div>
        <div class="field">
            <label>Stage</label>
            <select name="stage">
                <option value="">All</option>
                <?php foreach (pipeline_stages() as $s): ?>
                    <option value="<?= e($s) ?>" <?= $stage === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Business Type</label>
            <select name="business_type">
                <option value="">All</option>
                <?php foreach (['QSR','FMCG','Ice Cream','Retail','Other'] as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= $business_type === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>City</label>
            <input type="text" name="city" value="<?= e($city) ?>">
        </div>
        <div class="field">
            <label>From</label>
            <input type="date" name="date_from" value="<?= e($date_from) ?>">
        </div>
        <div class="field">
            <label>To</label>
            <input type="date" name="date_to" value="<?= e($date_to) ?>">
        </div>
        <div class="filter-flip-actions">
            <button class="btn" type="submit">Apply Filters</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/index.php')) ?>">Reset</a>
        </div>
    </form>
    <?php filter_flip_end(); ?>
</div>

<div class="card">
    <?php if (!$rows): ?>
        <div class="empty">No prospects found. <a href="<?= e(base_url('modules/prospects/create.php')) ?>">Create first prospect</a></div>
    <?php else: ?>
        <table class="data" data-lazy="1">
            <thead>
            <tr>
                <th>Prospect ID</th>
                <th>Date</th>
                <th>Company</th>
                <th>Contact</th>
                <th>Phone</th>
                <th>City</th>
                <th>Service</th>
                <th>Stage</th>
                <th>Next Follow-up</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?= $renderProspectRows($rows) ?>
            </tbody>
        </table>
        <?php render_pagination($pg); ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
