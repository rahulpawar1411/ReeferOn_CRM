<?php
/**
 * Clear an uploaded document file
 *
 * Resets document row to Pending. Permission: onboarding.delete_docs
 * Popup: type "clear" to confirm.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_perm('onboarding.delete_docs');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT d.*, o.onboarding_id, o.id AS onboarding_pk, o.overall_status
     FROM onboarding_documents d
     JOIN onboarding o ON o.id = d.onboarding_pk
     WHERE d.id = ?'
);
$stmt->execute([$id]);
$d = $stmt->fetch();

if (!$d) {
    flash('error', 'Document not found.');
    redirect(base_url('modules/onboarding/index.php'));
}

$back = base_url('modules/onboarding/view.php?id=' . (int) $d['onboarding_pk']);

if (empty($d['file_path'])) {
    flash('error', 'No file to clear.');
    redirect($back);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!delete_confirm_ok($_POST['confirm_text'] ?? '', 'clear')) {
        $errors[] = 'Please type clear in the box to confirm.';
    } else {
        try {
            clear_onboarding_document($id);
            flash('success', $d['doc_type'] . ' file cleared.', 'delete');
            redirect($back);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Clear Document';
$currentPage = 'onboarding';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error" style="max-width:480px;margin:16px auto">
        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
render_delete_confirm_modal([
    'title' => 'Clear document file?',
    'message' => 'File will be removed and status set to Pending. Type clear below to confirm.',
    'item_label' => $d['onboarding_id'] . ' — ' . $d['doc_type'] . ' (' . ($d['file_name'] ?? 'file') . ')',
    'cancel_url' => $back,
    'confirm_word' => 'clear',
    'confirm_btn' => 'Clear',
    'hidden' => ['id' => (int) $d['id']],
]);
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
