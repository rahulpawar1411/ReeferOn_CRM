<?php
/**
 * Create missing demo PDF files and fix DB paths for View / Download.
 * Run: php tools/fix_demo_documents.php
 */
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
$demoDir = __DIR__ . '/../uploads/documents/demo';
if (!is_dir($demoDir) && !mkdir($demoDir, 0755, true) && !is_dir($demoDir)) {
    fwrite(STDERR, "Cannot create {$demoDir}\n");
    exit(1);
}

/** Tiny valid PDF so browser can open it */
$makePdf = static function (string $title): string {
    $text = substr(preg_replace('/[^A-Za-z0-9 \-]/', '', $title) ?: 'Demo', 0, 40);
    return "%PDF-1.4\n"
        . "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n"
        . "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n"
        . "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 400 200] /Contents 4 0 R /Resources<< /Font<< /F1 5 0 R >> >> >>endobj\n"
        . "4 0 obj<< /Length 55 >>stream\n"
        . "BT /F1 16 Tf 40 100 Td ({$text}) Tj ET\n"
        . "endstream endobj\n"
        . "5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n"
        . "xref\n0 6\n0000000000 65535 f \n"
        . "trailer<< /Size 6 /Root 1 0 R >>\n"
        . "startxref\n0\n%%EOF\n";
};

$rows = $pdo->query(
    "SELECT id, doc_type, file_name, file_path
     FROM onboarding_documents
     WHERE file_path IS NOT NULL AND file_path <> ''"
)->fetchAll(PDO::FETCH_ASSOC);

$fixed = 0;
$created = 0;

foreach ($rows as $r) {
    $name = $r['file_name'] ?: (strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $r['doc_type'])) . '-demo.pdf');
    if (!str_ends_with(strtolower($name), '.pdf')) {
        $name .= '.pdf';
    }
    $rel = 'uploads/documents/demo/' . $name;
    $abs = __DIR__ . '/../' . $rel;

    if (!is_file($abs)) {
        file_put_contents($abs, $makePdf((string) $r['doc_type']));
        $created++;
    }

    $size = filesize($abs) ?: 0;
    $pdo->prepare(
        "UPDATE onboarding_documents
         SET file_path = ?, file_name = ?, file_size = ?, mime_type = 'application/pdf'
         WHERE id = ?"
    )->execute([$rel, $name, $size, (int) $r['id']]);
    $fixed++;
    echo "ok id={$r['id']} → {$rel}\n";
}

echo "updated_rows={$fixed} files_created={$created}\n";
echo "OK – try View / Download again\n";
