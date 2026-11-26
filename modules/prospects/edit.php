<?php
$pageTitle = 'Edit Prospect';
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

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $mobile = phone_digits($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $business_type = $_POST['business_type'] ?? '';
    $industry = trim($_POST['industry'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $service_required = $_POST['service_required'] ?? '';
    $temperature_requirement = $_POST['temperature_requirement'] ?? '';
    $requirement_details = trim($_POST['requirement_details'] ?? '');
    $estimated_monthly_business = trim($_POST['estimated_monthly_business'] ?? '');
    $lead_source = $_POST['lead_source'] ?? '';
    $pipeline_stage = $_POST['pipeline_stage'] ?? '';
    $next_followup_date = trim($_POST['next_followup_date'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    if ($company_name === '') $errors[] = 'Company Name is required.';
    if ($contact_person === '') $errors[] = 'Contact Person is required.';
    $phoneErr = phone_error($mobile, true);
    if ($phoneErr) $errors[] = $phoneErr;
    if ($business_type === '') $errors[] = 'Business Type is required.';
    if ($service_required === '') $errors[] = 'Service Required is required.';
    if ($temperature_requirement === '') $errors[] = 'Temperature Requirement is required.';
    if ($lead_source === '') $errors[] = 'Lead Source is required.';
    if (!in_array($pipeline_stage, pipeline_stages(), true)) $errors[] = 'Invalid pipeline stage.';
    if (!in_array($status, ['Active', 'Inactive'], true)) $errors[] = 'Invalid status.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email ID is invalid.';
    if ($estimated_monthly_business !== '' && !is_numeric($estimated_monthly_business)) {
        $errors[] = 'Estimated Monthly Business must be a number.';
    }

    if (!$errors) {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            $oldStage = $p['pipeline_stage'];

            $upd = $pdo->prepare(
                "UPDATE prospects SET
                    company_name = ?, contact_person = ?, designation = ?, mobile = ?, email = ?,
                    business_type = ?, industry = ?, city = ?, service_required = ?,
                    temperature_requirement = ?, requirement_details = ?, estimated_monthly_business = ?,
                    lead_source = ?, pipeline_stage = ?, next_followup_date = ?, remarks = ?,
                    status = ?, updated_by = ?
                 WHERE id = ?"
            );

            $upd->execute([
                $company_name,
                $contact_person,
                $designation !== '' ? $designation : null,
                $mobile,
                $email !== '' ? $email : null,
                $business_type,
                $industry !== '' ? $industry : null,
                $city !== '' ? $city : null,
                $service_required,
                $temperature_requirement,
                $requirement_details !== '' ? $requirement_details : null,
                $estimated_monthly_business !== '' ? $estimated_monthly_business : null,
                $lead_source,
                $pipeline_stage,
                $next_followup_date !== '' ? $next_followup_date : null,
                $remarks !== '' ? $remarks : null,
                $status,
                $user['id'],
                $id,
            ]);

            if ($oldStage !== $pipeline_stage) {
                $hist = $pdo->prepare(
                    "INSERT INTO prospect_stage_history
                     (prospect_pk, from_stage, to_stage, remarks, updated_by, updated_by_name)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $hist->execute([
                    $id,
                    $oldStage,
                    $pipeline_stage,
                    $remarks !== '' ? $remarks : 'Stage updated from edit form',
                    $user['id'],
                    $user['name'],
                ]);
            }

            write_audit('prospects', $p['prospect_id'], 'UPDATE', $oldStage, $pipeline_stage);

            $pdo->commit();
            flash('success', 'Prospect updated: ' . $p['prospect_id']);
            redirect(base_url('modules/prospects/view.php?id=' . $id));
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'Could not update: ' . $e->getMessage();
        }
    }

    // Keep posted values on error
    $p = array_merge($p, $_POST);
}

$pageTitle = 'Edit ' . $p['prospect_id'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Edit Prospect</h2>
    <p class="step-note"><strong>Edit Lead:</strong> Update company and contact details. Prospect ID <strong><?= e($p['prospect_id']) ?></strong> cannot be changed. Prefer <em>Change Stage</em> for pipeline updates (saves remarks in history).</p>

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
                <label>Company Name <span class="required">*</span></label>
                <input type="text" name="company_name" required value="<?= e($p['company_name']) ?>">
            </div>
            <div class="form-group">
                <label>Contact Person <span class="required">*</span></label>
                <input type="text" name="contact_person" required value="<?= e($p['contact_person']) ?>">
            </div>

            <div class="form-group">
                <label>Designation</label>
                <input type="text" name="designation" value="<?= e($p['designation'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Phone number <span class="required">*</span></label>
                <?php render_phone_input('mobile', $p['mobile'] ?? '', true); ?>
            </div>

            <div class="form-group">
                <label>Email ID</label>
                <input type="email" name="email" value="<?= e($p['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Business Type <span class="required">*</span></label>
                <select name="business_type" required>
                    <?php foreach (['QSR','FMCG','Ice Cream','Retail','Other'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= ($p['business_type'] === $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Industry</label>
                <input type="text" name="industry" value="<?= e($p['industry'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?= e($p['city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Service Required <span class="required">*</span></label>
                <select name="service_required" required>
                    <?php foreach (['Transportation','Warehousing','EV','Integrated Supply Chain','Other'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= ($p['service_required'] === $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Temperature Requirement <span class="required">*</span></label>
                <select name="temperature_requirement" required>
                    <?php foreach (['Ambient','Chiller','Frozen'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= ($p['temperature_requirement'] === $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Estimated Monthly Business (₹)</label>
                <input type="number" step="0.01" name="estimated_monthly_business" value="<?= e($p['estimated_monthly_business'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Lead Source <span class="required">*</span></label>
                <select name="lead_source" required>
                    <?php foreach (['Reference','Website','Direct','Other','LinkedIn'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= ($p['lead_source'] === $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Pipeline Stage</label>
                <select name="pipeline_stage">
                    <?php foreach (pipeline_stages() as $s): ?>
                        <option value="<?= e($s) ?>" <?= ($p['pipeline_stage'] === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Next Follow-up Date</label>
                <input type="date" name="next_followup_date" value="<?= e($p['next_followup_date'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Record Status</label>
                <select name="status">
                    <option value="Active" <?= ($p['status'] === 'Active') ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= ($p['status'] === 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="form-group full">
                <label>Requirement Details</label>
                <textarea name="requirement_details"><?= e($p['requirement_details'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Remarks</label>
                <textarea name="remarks"><?= e($p['remarks'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Update Prospect</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/view.php?id=' . $id)) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
