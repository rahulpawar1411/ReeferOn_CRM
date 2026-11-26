<?php
/**
 * View / Download onboarding document
 *
 * Opens a stored file safely (must stay under /uploads).
 *   ?id=123               → View in browser (PDF/image opens inline)
 *   ?id=123&mode=download → Force download to computer
 *
 * Works anytime after upload — even after you leave the upload page.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$mode = strtolower(trim((string) ($_GET['mode'] ?? 'view')));
if ($mode !== 'download') {
    $mode = 'view';
}

$stmt = db()->prepare('SELECT * FROM onboarding_documents WHERE id = ?');
$stmt->execute([$id]);
$d = $stmt->fetch();

if (!$d || empty($d['file_path'])) {
    flash('error', 'File not found.');
    redirect(base_url('modules/onboarding/index.php'));
}

/**
 * Normalize Windows/Unix paths so safety checks work on WAMP.
 */
$norm = static function (?string $path): string {
    return strtolower(str_replace('\\', '/', (string) $path));
};

$appRoot = realpath(__DIR__ . '/../../');
$uploadsRoot = realpath(__DIR__ . '/../../uploads');
$rel = ltrim(str_replace('\\', '/', (string) $d['file_path']), '/');
$candidate = $appRoot ? ($appRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel)) : '';
$full = $candidate !== '' ? realpath($candidate) : false;

$safe =
    $appRoot
    && $uploadsRoot
    && $full
    && is_file($full)
    && str_starts_with($norm($full), $norm($uploadsRoot) . '/');

if (!$safe) {
    flash('error', 'Invalid file path. File may be missing — please upload again.');
    // Prefer returning to the related onboarding view if we know it
    $backId = (int) ($d['onboarding_pk'] ?? 0);
    redirect($backId
        ? base_url('modules/onboarding/view.php?id=' . $backId)
        : base_url('modules/onboarding/index.php'));
}

// Resolve onboarding_id for audit
$onbId = (string) $d['onboarding_pk'];
try {
    $o = db()->prepare('SELECT onboarding_id FROM onboarding WHERE id = ?');
    $o->execute([(int) $d['onboarding_pk']]);
    $onbId = (string) ($o->fetchColumn() ?: $onbId);
} catch (Throwable $e) {
    // continue even if audit lookup fails
}
write_audit(
    'onboarding_documents',
    $onbId,
    $mode === 'download' ? 'DOWNLOAD' : 'VIEW',
    $d['doc_type'],
    $d['file_name'] ?? ''
);

$safeName = basename((string) ($d['file_name'] ?: 'document'));
$mime = $d['mime_type'] ?: 'application/octet-stream';
$disposition = $mode === 'download' ? 'attachment' : 'inline';

header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');
header('Content-Length: ' . filesize($full));
header('X-Content-Type-Options: nosniff');
readfile($full);
exit;
