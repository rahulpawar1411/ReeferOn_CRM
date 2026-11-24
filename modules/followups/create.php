<?php
$pageTitle = 'Add Follow-up';
$currentPage = 'followups';
require_once __DIR__ . '/../../includes/functions.php';

$user = current_user();
$errors = [];
$prospectPk = (int) ($_GET['prospect_id'] ?? $_POST['prospect_pk'] ?? 0);

$prospects = db()->query(
    "SELECT id, prospect_id, company_name, contact_person, next_followup_date
     FROM prospects
     WHERE status = 'Active'
     ORDER BY company_name ASC"
)->fetchAll();

$selected = null;
foreach ($prospects as $row) {
    if ((int) $row['id'] === $prospectPk) {
        $selected = $row;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prospectPk = (int) ($_POST['prospect_pk'] ?? 0);
    $followup_date = trim($_POST['followup_date'] ?? '');
    $interaction_type = $_POST['interaction_type'] ?? '';
    $discussion_summary = trim($_POST['discussion_summary'] ?? '');
    $next_followup_date = trim($_POST['next_followup_date'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    $selected = null;
    foreach ($prospects as $row) {
        if ((int) $row['id'] === $prospectPk) {
            $selected = $row;
            break;
        }
    }

    if (!$selected) {
        $errors[] = 'Please select a prospect.';
    }
    if ($followup_date === '') {
        $errors[] = 'Follow-up Date is required.';
    }
    if (!in_array($interaction_type, interaction_types(), true)) {
        $errors[] = 'Interaction Type is required.';
    }
    if ($discussion_summary === '') {
        $errors[] = 'Discussion Summary is required.';
    }

    if (!$errors) {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            $ins = $pdo->prepare(
                "INSERT INTO followups
                 (prospect_pk, prospect_id, company_name, contact_person, followup_date,
                  interaction_type, discussion_summary, next_followup_date, remarks,
                  status, created_by, created_by_name)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Open', ?, ?)"
            );
            $ins->execute([
                $selected['id'],
                $selected['prospect_id'],
                $selected['company_name'],
                $selected['contact_person'],
                $followup_date,
                $interaction_type,
                $discussion_summary,
                $next_followup_date !== '' ? $next_followup_date : null,
                $remarks !== '' ? $remarks : null,
                $user['id'],
                $user['name'],
            ]);

            if ($next_followup_date !== '') {
                $upd = $pdo->prepare(
                    'UPDATE prospects SET next_followup_date = ?, updated_by = ? WHERE id = ?'
                );
                $upd->execute([$next_followup_date, $user['id'], $selected['id']]);
            }

            write_audit(
                'followups',
                $selected['prospect_id'],
                'CREATE',
                null,
                $interaction_type . ' on ' . $followup_date
            );

            $pdo->commit();
            flash('success', 'Follow-up saved for ' . $selected['prospect_id']);
            redirect(base_url('modules/prospects/view.php?id=' . $selected['id']));
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'Could not save follow-up: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Add Follow-up</h2>
    <p class="step-note"><strong>Step 2b – Follow-up:</strong> Save a call, meeting, or WhatsApp note for this lead, plus the next follow-up date. Follow-ups are used before commercial confirmation too.</p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!$prospects): ?>
        <div class="empty">No active prospects. Create a prospect first.</div>
    <?php else: ?>
        <form method="post" action="">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Prospect <span class="required">*</span></label>
                    <select name="prospect_pk" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($prospects as $row): ?>
                            <option value="<?= (int) $row['id'] ?>" <?= ($selected && (int) $selected['id'] === (int) $row['id']) ? 'selected' : '' ?>>
                                <?= e($row['prospect_id'] . ' – ' . $row['company_name'] . ' (' . $row['contact_person'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Follow-up Date <span class="required">*</span></label>
                    <input type="date" name="followup_date" required
                           value="<?= e($_POST['followup_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="form-group">
                    <label>Interaction Type <span class="required">*</span></label>
                    <select name="interaction_type" required>
                        <option value="">-- Select --</option>
                        <?php foreach (interaction_types() as $t): ?>
                            <option value="<?= e($t) ?>" <?= (($_POST['interaction_type'] ?? '') === $t) ? 'selected' : '' ?>><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Next Follow-up Date</label>
                    <input type="date" name="next_followup_date"
                           value="<?= e($_POST['next_followup_date'] ?? ($selected['next_followup_date'] ?? '')) ?>">
                </div>

                <div class="form-group full">
                    <label>Discussion Summary <span class="required">*</span></label>
                    <textarea name="discussion_summary" required><?= e($_POST['discussion_summary'] ?? '') ?></textarea>
                </div>
                <div class="form-group full">
                    <label>Remarks</label>
                    <textarea name="remarks"><?= e($_POST['remarks'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Save Follow-up</button>
                <a class="btn btn-secondary" href="<?= e(base_url('modules/followups/index.php')) ?>">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
