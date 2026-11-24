<?php
$pageTitle = 'View Prospect';
$currentPage = 'prospects';
require_once __DIR__ . '/../../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM prospects WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    flash('error', 'Prospect not found.');
    redirect(base_url('modules/prospects/index.php'));
}

$histStmt = db()->prepare(
    'SELECT * FROM prospect_stage_history WHERE prospect_pk = ? ORDER BY id DESC'
);
$histStmt->execute([$id]);
$history = $histStmt->fetchAll();

$fuStmt = db()->prepare(
    'SELECT * FROM followups WHERE prospect_pk = ? ORDER BY followup_date DESC, id DESC LIMIT 20'
);
$fuStmt->execute([$id]);
$followups = $fuStmt->fetchAll();

$pageTitle = 'Prospect ' . $p['prospect_id'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toolbar">
    <a class="btn" href="<?= e(base_url('modules/prospects/edit.php?id=' . $p['id'])) ?>">Edit</a>
    <a class="btn" href="<?= e(base_url('modules/prospects/stage.php?id=' . $p['id'])) ?>">Change Stage</a>
    <a class="btn" href="<?= e(base_url('modules/followups/create.php?prospect_id=' . $p['id'])) ?>">Add Follow-up</a>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/index.php')) ?>">Back to List</a>
</div>

<?php if ($p['pipeline_stage'] === 'Commercial Approved'): ?>
    <div class="alert alert-success">
        Commercial Approved — next phase will enable <strong>Initiate Customer Onboarding</strong> (not built yet).
    </div>
<?php endif; ?>

<div class="card">
    <h2>Prospect Details</h2>
    <dl class="dl-grid">
        <dt>Prospect ID</dt><dd><?= e($p['prospect_id']) ?></dd>
        <dt>Lead Creation Date</dt><dd><?= e($p['lead_creation_date']) ?></dd>
        <dt>Company Name</dt><dd><?= e($p['company_name']) ?></dd>
        <dt>Contact Person</dt><dd><?= e($p['contact_person']) ?></dd>
        <dt>Designation</dt><dd><?= e($p['designation'] ?? '-') ?></dd>
        <dt>Mobile</dt><dd><?= e($p['mobile']) ?></dd>
        <dt>Email</dt><dd><?= e($p['email'] ?? '-') ?></dd>
        <dt>Business Type</dt><dd><?= e($p['business_type']) ?></dd>
        <dt>Industry</dt><dd><?= e($p['industry'] ?? '-') ?></dd>
        <dt>City</dt><dd><?= e($p['city'] ?? '-') ?></dd>
        <dt>Service Required</dt><dd><?= e($p['service_required']) ?></dd>
        <dt>Temperature</dt><dd><?= e($p['temperature_requirement']) ?></dd>
        <dt>Est. Monthly Business</dt><dd><?= e(format_money($p['estimated_monthly_business'])) ?></dd>
        <dt>Lead Source</dt><dd><?= e($p['lead_source']) ?></dd>
        <dt>Salesperson</dt><dd><?= e($p['salesperson_name']) ?></dd>
        <dt>Pipeline Stage</dt><dd><span class="badge"><?= e($p['pipeline_stage']) ?></span></dd>
        <dt>Next Follow-up</dt><dd><?= e($p['next_followup_date'] ?? '-') ?></dd>
        <dt>Status</dt><dd><?= e($p['status']) ?></dd>
        <dt>Requirement Details</dt><dd><?= nl2br(e($p['requirement_details'] ?? '-')) ?></dd>
        <dt>Remarks</dt><dd><?= nl2br(e($p['remarks'] ?? '-')) ?></dd>
        <dt>Created At</dt><dd><?= e($p['created_at']) ?></dd>
        <dt>Updated At</dt><dd><?= e($p['updated_at'] ?? '-') ?></dd>
    </dl>
</div>

<div class="card">
    <h2>Follow-ups</h2>
    <?php if (!$followups): ?>
        <div class="empty">No follow-ups yet. <a href="<?= e(base_url('modules/followups/create.php?prospect_id=' . $p['id'])) ?>">Add one</a>.</div>
    <?php else: ?>
        <table class="data">
            <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Summary</th>
                <th>Next</th>
                <th>Status</th>
                <th>By</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($followups as $f): ?>
                <tr>
                    <td><?= e($f['followup_date']) ?></td>
                    <td><?= e($f['interaction_type']) ?></td>
                    <td><?= e($f['discussion_summary'] ?? '-') ?></td>
                    <td><?= e($f['next_followup_date'] ?? '-') ?></td>
                    <td><span class="badge"><?= e($f['status']) ?></span></td>
                    <td><?= e($f['created_by_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Stage History</h2>
    <?php if (!$history): ?>
        <div class="empty">No history yet.</div>
    <?php else: ?>
        <table class="data">
            <thead>
            <tr>
                <th>Date/Time</th>
                <th>From</th>
                <th>To</th>
                <th>By</th>
                <th>Remarks</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td><?= e($h['created_at']) ?></td>
                    <td><?= e($h['from_stage'] ?? '-') ?></td>
                    <td><?= e($h['to_stage']) ?></td>
                    <td><?= e($h['updated_by_name']) ?></td>
                    <td><?= e($h['remarks'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
