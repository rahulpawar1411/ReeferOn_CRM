<?php
require_once __DIR__ . '/../../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM onboarding_documents WHERE id = ?');
$stmt->execute([$id]);
$d = $stmt->fetch();

if (!$d || empty($d['file_path'])) {
    flash('error', 'File not found.');
    redirect(base_url('modules/onboarding/index.php'));
}

$full = realpath(__DIR__ . '/../../' . $d['file_path']);
$root = realpath(__DIR__ . '/../../uploads/documents');

if (!$full || !$root || !str_starts_with($full, $root) || !is_file($full)) {
    flash('error', 'Invalid file path.');
    redirect(base_url('modules/onboarding/index.php'));
}

$mime = $d['mime_type'] ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename((string) $d['file_name']) . '"');
header('Content-Length: ' . filesize($full));
readfile($full);
exit;
