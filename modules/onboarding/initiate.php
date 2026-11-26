<?php
/**
 * Initiate onboarding
 *
 * Starts onboarding from a Commercial Approved prospect (copies sales data).
 * Also creates pending document rows and Admin approval.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_perm('onboarding.edit');

$prospectPk = (int) ($_GET['prospect_id'] ?? 0);
$user = current_user();

$stmt = db()->prepare('SELECT * FROM prospects WHERE id = ?');
$stmt->execute([$prospectPk]);
$p = $stmt->fetch();

if (!$p) {
    flash('error', 'Prospect not found.');
    redirect(base_url('modules/prospects/index.php'));
}

// Already has onboarding?
$exist = db()->prepare('SELECT id, onboarding_id FROM onboarding WHERE prospect_pk = ?');
$exist->execute([$prospectPk]);
$existing = $exist->fetch();
if ($existing) {
    flash('success', 'Onboarding already exists: ' . $existing['onboarding_id']);
    redirect(base_url('modules/onboarding/view.php?id=' . $existing['id']));
}

if ($p['pipeline_stage'] !== 'Commercial Approved') {
    flash('error', 'Onboarding only after Commercial Approved. Current stage: ' . $p['pipeline_stage']);
    redirect(base_url('modules/prospects/view.php?id=' . $prospectPk));
}

try {
    // Generate ID before outer transaction (next_business_id uses its own transaction)
    $onboardingId = next_business_id('onboarding');

    $pdo = db();
    $pdo->beginTransaction();

    $ins = $pdo->prepare(
        "INSERT INTO onboarding (
            onboarding_id, prospect_pk, prospect_id,
            customer_name, business_type, contact_person, mobile, email,
            service_required, temperature_requirement,
            overall_status, created_by
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft', ?)"
    );
    $ins->execute([
        $onboardingId,
        $p['id'],
        $p['prospect_id'],
        $p['company_name'],
        $p['business_type'],
        $p['contact_person'],
        $p['mobile'],
        $p['email'],
        $p['service_required'],
        $p['temperature_requirement'],
        $user['id'],
    ]);
    $onboardingPk = (int) $pdo->lastInsertId();

    $docIns = $pdo->prepare(
        "INSERT INTO onboarding_documents (onboarding_pk, doc_type, status) VALUES (?, ?, 'Pending')"
    );
    foreach (onboarding_doc_types() as $docType) {
        $docIns->execute([$onboardingPk, $docType]);
    }

    // Direct Admin approval only (details filled before submit)
    $apprIns = $pdo->prepare(
        "INSERT INTO onboarding_approvals (onboarding_pk, department, status) VALUES (?, 'Admin', 'Pending')"
    );
    $apprIns->execute([$onboardingPk]);

    write_audit('onboarding', $onboardingId, 'CREATE', null, 'From prospect ' . $p['prospect_id']);

    $pdo->commit();

    // Move prospect stage to Onboarding (separate transaction)
    change_prospect_stage($prospectPk, 'Onboarding', 'Customer onboarding initiated: ' . $onboardingId);

    flash('success', 'Onboarding started: ' . $onboardingId . ' (prospect data copied). Complete extra details.', 'create');
    redirect(base_url('modules/onboarding/edit.php?id=' . $onboardingPk));
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash('error', 'Could not start onboarding: ' . $e->getMessage());
    redirect(base_url('modules/prospects/view.php?id=' . $prospectPk));
}
