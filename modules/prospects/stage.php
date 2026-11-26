<?php
/**
 * Change pipeline stage
 *
 * Moves a lead to a new sales stage and saves remarks + history.
 */
$pageTitle = 'Update Pipeline Stage';
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

$errors = [];

// Save stage change on form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toStage = $_POST['pipeline_stage'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');
    $nextFollowup = trim($_POST['next_followup_date'] ?? '');

    if (!in_array($toStage, pipeline_stages(), true)) {
        $errors[] = 'Invalid pipeline stage.';
    }
    if ($remarks === '') {
        $errors[] = 'Remarks are required when changing stage.';
    }
    if ($toStage === $p['pipeline_stage']) {
        $errors[] = 'Select a different stage than the current one.';
    }

    if (!$errors) {
        try {
            change_prospect_stage($id, $toStage, $remarks, $nextFollowup !== '' ? $nextFollowup : null);
            flash('success', 'Stage updated to: ' . $toStage);
            redirect(base_url('modules/prospects/view.php?id=' . $id));
        } catch (Throwable $e) {
            $errors[] = 'Could not update stage: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Stage – ' . $p['prospect_id'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Update Sales Pipeline Stage</h2>
    <p class="step-note"><strong>Step 2 – Sales Pipeline:</strong> Update where this lead stands (Calling → Meeting → … → Commercial Approved). Every change is saved in history. When the customer accepts the rates, select <strong>Commercial Approved</strong>.</p>
    <p>
        <strong><?= e($p['company_name']) ?></strong>
        (<?= e($p['prospect_id']) ?>) —
        Current stage: <span class="badge"><?= e($p['pipeline_stage']) ?></span>
    </p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-grid">
            <div class="form-group">
                <label>New Stage <span class="required">*</span></label>
                <select name="pipeline_stage" required>
                    <?php foreach (pipeline_stages() as $s): ?>
                        <option value="<?= e($s) ?>" <?= (($_POST['pipeline_stage'] ?? '') === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Next Follow-up Date</label>
                <input type="date" name="next_followup_date" value="<?= e($_POST['next_followup_date'] ?? ($p['next_followup_date'] ?? '')) ?>">
            </div>
            <div class="form-group full">
                <label>Remarks <span class="required">*</span></label>
                <textarea name="remarks" required placeholder="Why is the stage changing?"><?= e($_POST['remarks'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Save Stage Change</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/view.php?id=' . $id)) ?>">Cancel</a>
        </div>
    </form>

    <?php if ($p['pipeline_stage'] === 'Commercial Approved'): ?>
        <p class="muted" style="margin-top:12px;">
            Tip: After Commercial Approved, use <strong>Initiate Customer Onboarding</strong> on the prospect view (do not move to Onboarding manually).
        </p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
