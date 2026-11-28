<?php
/**
 * Toast Undo API
 *
 * Reverses a soft Inactive ↔ Active change started from a success toast.
 * Expects POST JSON: { "token": "..." } matching the one-time session token
 * created by flash(..., $undo). Token is cleared only after a successful reverse.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST required.']);
    exit;
}

$raw = file_get_contents('php://input');
$json = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
$data = is_array($json) ? $json : $_POST;
$token = trim((string) ($data['token'] ?? ''));

// One-time undo payload stored when flash() was called with $undo
$undo = $_SESSION['toast_undo'] ?? null;

if (
    !is_array($undo)
    || $token === ''
    || empty($undo['token'])
    || !hash_equals((string) $undo['token'], $token)
    || empty($undo['expires'])
    || time() > (int) $undo['expires']
) {
    unset($_SESSION['toast_undo']);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Undo expired. Refresh and try the action again.']);
    exit;
}

$entity = (string) ($undo['entity'] ?? '');
$id = (int) ($undo['id'] ?? 0);
$to = (string) ($undo['to'] ?? '');
$redirect = (string) ($undo['redirect'] ?? '');

try {
    if ($entity === 'prospect') {
        if (!can('prospects.delete')) {
            throw new RuntimeException('No permission to undo prospect status.');
        }
        if ($to === 'Active') {
            soft_activate_prospect($id, 'Undo from toast');
        } elseif ($to === 'Inactive') {
            soft_delete_prospect($id, 'Undo from toast');
        } else {
            throw new RuntimeException('Invalid undo target.');
        }
        if ($redirect === '') {
            $redirect = base_url('modules/prospects/view.php?id=' . $id);
        }
    } elseif ($entity === 'customer') {
        if (!can('customers.delete')) {
            throw new RuntimeException('No permission to undo customer status.');
        }
        if ($to === 'Active') {
            soft_activate_customer($id, 'Undo from toast');
        } elseif ($to === 'Inactive') {
            soft_delete_customer($id, 'Undo from toast');
        } else {
            throw new RuntimeException('Invalid undo target.');
        }
        if ($redirect === '') {
            $redirect = base_url('modules/customers/view.php?id=' . $id);
        }
    } elseif ($entity === 'user') {
        if (!can('users.manage')) {
            throw new RuntimeException('No permission to undo user status.');
        }
        if ($to === 'Active') {
            soft_activate_user($id, 'Undo from toast');
        } elseif ($to === 'Inactive') {
            soft_delete_user($id, 'Undo from toast');
        } else {
            throw new RuntimeException('Invalid undo target.');
        }
        if ($redirect === '') {
            $redirect = base_url('modules/users/index.php');
        }
    } else {
        throw new RuntimeException('Unknown undo entity.');
    }

    // Consume token only after a successful reverse
    unset($_SESSION['toast_undo']);

    echo json_encode([
        'ok' => true,
        'message' => $to === 'Active' ? 'Restored to Active.' : 'Set back to Inactive.',
        'redirect' => $redirect,
    ]);
} catch (Throwable $e) {
    // Keep token so the user can retry Undo if the reverse failed
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
