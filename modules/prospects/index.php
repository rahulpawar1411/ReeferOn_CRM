<?php
$pageTitle = 'Prospects';
$currentPage = 'prospects';
require_once __DIR__ . '/../../includes/functions.php';

$q = trim($_GET['q'] ?? '');
$stage = trim($_GET['stage'] ?? '');
$business_type = trim($_GET['business_type'] ?? '');
$city = trim($_GET['city'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');

$sql = "SELECT * FROM prospects WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (
        prospect_id LIKE ? OR company_name LIKE ? OR contact_person LIKE ?
        OR mobile LIKE ? OR email LIKE ?
    )";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($stage !== '') {
    $sql .= " AND pipeline_stage = ?";
    $params[] = $stage;
}
if ($business_type !== '') {
    $sql .= " AND business_type = ?";
    $params[] = $business_type;
}
if ($city !== '') {
    $sql .= " AND city LIKE ?";
    $params[] = '%' . $city . '%';
}
if ($date_from !== '') {
    $sql .= " AND lead_creation_date >= ?";
    $params[] = $date_from;
}
if ($date_to !== '') {
    $sql .= " AND lead_creation_date <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY id DESC LIMIT 500";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <p class="step-note"><strong>Step 1 – Prospects list:</strong> All sales leads are listed here. Add a new lead with <em>+ New Prospect</em>. Open a lead to update stage or follow-ups.</p>
    <div class="toolbar">
        <a class="btn" href="<?= e(base_url('modules/prospects/create.php')) ?>">+ New Prospect</a>
    </div>

    <form method="get" class="toolbar">
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
        <button class="btn" type="submit">Filter</button>
        <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/index.php')) ?>">Reset</a>
    </form>
</div>

<div class="card">
    <?php if (!$rows): ?>
        <div class="empty">No prospects found. <a href="<?= e(base_url('modules/prospects/create.php')) ?>">Create first prospect</a></div>
    <?php else: ?>
        <table class="data">
            <thead>
            <tr>
                <th>Prospect ID</th>
                <th>Date</th>
                <th>Company</th>
                <th>Contact</th>
                <th>Mobile</th>
                <th>City</th>
                <th>Service</th>
                <th>Stage</th>
                <th>Next Follow-up</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
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
                        <a href="<?= e(base_url('modules/prospects/view.php?id=' . $row['id'])) ?>">View</a> |
                        <a href="<?= e(base_url('modules/prospects/edit.php?id=' . $row['id'])) ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
