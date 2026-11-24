<?php
$pageTitle = 'Upload Document';
$currentPage = 'onboarding';
require_once __DIR__ . '/../../includes/functions.php';

$docId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT d.*, o.onboarding_id, o.id AS onboarding_pk
     FROM onboarding_documents d
     JOIN onboarding o ON o.id = d.onboarding_pk
     WHERE d.id = ?'
);
$stmt->execute([$docId]);
$d = $stmt->fetch();

if (!$d) {
    flash('error', 'Document row not found.');
    redirect(base_url('modules/onboarding/index.php'));
}

$user = current_user();
$errors = [];
$allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
$maxBytes = 5 * 1024 * 1024; // 5 MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'Received';
    $remarks = trim($_POST['remarks'] ?? '');

    if (!in_array($status, doc_statuses(), true)) {
        $errors[] = 'Invalid document status.';
    }

    $hasFile = isset($_FILES['document']) && ($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($status === 'Received' && !$hasFile && empty($d['file_path'])) {
        $errors[] = 'Please upload a file for Received status.';
    }

    $savedRelPath = $d['file_path'];
    $fileName = $d['file_name'];
    $fileSize = $d['file_size'];
    $mimeType = $d['mime_type'];

    if ($hasFile && !$errors) {
        $file = $_FILES['document'];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed (error code ' . (int) $file['error'] . ').';
        } elseif (($file['size'] ?? 0) > $maxBytes) {
            $errors[] = 'File too large. Max 5 MB.';
        } else {
            $orig = $file['name'] ?? 'file';
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $errors[] = 'Allowed file types: PDF, JPG, PNG, WEBP.';
            } else {
                $dir = __DIR__ . '/../../uploads/documents/' . preg_replace('/[^A-Za-z0-9\-]/', '', $d['onboarding_id']);
                if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                    $errors[] = 'Could not create upload folder.';
                } else {
                    $safeBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
                    $newName = $safeBase . '_' . time() . '.' . $ext;
                    $dest = $dir . '/' . $newName;
                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $errors[] = 'Could not save uploaded file.';
                    } else {
                        $savedRelPath = 'uploads/documents/' . preg_replace('/[^A-Za-z0-9\-]/', '', $d['onboarding_id']) . '/' . $newName;
                        $fileName = $orig;
                        $fileSize = (int) $file['size'];
                        $mimeType = $file['type'] ?: null;
                        if ($status === 'Pending') {
                            $status = 'Received';
                        }
                    }
                }
            }
        }
    }

    if (!$errors) {
        try {
            $upd = db()->prepare(
                "UPDATE onboarding_documents SET
                    file_name = ?, file_path = ?, file_size = ?, mime_type = ?,
                    status = ?, uploaded_by = ?, uploaded_by_name = ?, remarks = ?
                 WHERE id = ?"
            );
            $upd->execute([
                $fileName,
                $savedRelPath,
                $fileSize,
                $mimeType,
                $status,
                $user['id'],
                $user['name'],
                $remarks !== '' ? $remarks : null,
                $docId,
            ]);

            write_audit('onboarding_documents', $d['onboarding_id'], 'UPLOAD', $d['doc_type'], $status);
            flash('success', $d['doc_type'] . ' updated.');
            redirect(base_url('modules/onboarding/view.php?id=' . $d['onboarding_pk']));
        } catch (Throwable $e) {
            $errors[] = 'Could not save: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Upload – ' . $d['doc_type'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Document: <?= e($d['doc_type']) ?></h2>
    <p class="step-note"><strong>Step 3b – Documents:</strong> Upload GST, PAN, KYC, Agreement, etc. (PDF/JPG, max 5 MB). If rejected, upload a replacement file here.</p>
    <p>Onboarding <strong><?= e($d['onboarding_id']) ?></strong> | Current status: <span class="badge"><?= e($d['status']) ?></span></p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <?php foreach (doc_statuses() as $s): ?>
                        <option value="<?= e($s) ?>" <?= ($d['status'] === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>File (PDF / JPG / PNG, max 5 MB)</label>
                <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp">
            </div>
            <div class="form-group full">
                <label>Remarks</label>
                <textarea name="remarks"><?= e($d['remarks'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn">Save Document</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/onboarding/view.php?id=' . $d['onboarding_pk'])) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
