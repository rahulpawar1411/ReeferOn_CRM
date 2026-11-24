<?php
require_once __DIR__ . '/../config/database.php';

/**
 * App base URL path (works from any module depth under /CRM or /reeferon-crm).
 */
function base_url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));

        if ($docRoot && $appRoot && str_starts_with($appRoot, $docRoot)) {
            $base = substr($appRoot, strlen($docRoot));
        } else {
            $base = '';
        }
        $base = rtrim(str_replace('\\', '/', $base), '/');
    }

    $path = ltrim($path, '/');
    if ($base === '') {
        return '/' . $path;
    }
    return $base . ($path !== '' ? '/' . $path : '');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function current_user(): array
{
    return [
        'id'   => TEMP_USER_ID,
        'name' => TEMP_USER_NAME,
    ];
}

/**
 * Generate next business ID using id_sequences table (safe under concurrency).
 */
function next_business_id(string $seqName): string
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT prefix, next_number, pad_length FROM id_sequences WHERE seq_name = ? FOR UPDATE');
        $stmt->execute([$seqName]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Sequence not found: ' . $seqName);
        }

        $number = (int) $row['next_number'];
        $code = $row['prefix'] . str_pad((string) $number, (int) $row['pad_length'], '0', STR_PAD_LEFT);

        $upd = $pdo->prepare('UPDATE id_sequences SET next_number = next_number + 1 WHERE seq_name = ?');
        $upd->execute([$seqName]);

        $pdo->commit();
        return $code;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function write_audit(string $module, string $recordId, string $action, ?string $oldValue, ?string $newValue): void
{
    $user = current_user();
    $stmt = db()->prepare(
        'INSERT INTO audit_logs (module, record_id, action, old_value, new_value, performed_by, performed_by_name)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $module,
        $recordId,
        $action,
        $oldValue,
        $newValue,
        $user['id'],
        $user['name'],
    ]);
}

function format_money($amount): string
{
    if ($amount === null || $amount === '') {
        return '-';
    }
    return '₹' . number_format((float) $amount, 2);
}

function pipeline_stages(): array
{
    return [
        'New Prospect',
        'Calling',
        'Meeting',
        'Proposal Sent',
        'Negotiation',
        'Commercial Sent',
        'Commercial Approved',
        'Onboarding',
        'Customer Activated',
        'Lost',
        'On Hold',
        'Cancelled',
    ];
}
