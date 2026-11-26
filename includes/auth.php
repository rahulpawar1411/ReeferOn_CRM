<?php
/**
 * Phase 7 – Login, roles, checkbox permissions.
 */
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/** All permission keys shown as checkboxes */
function permission_catalog(): array
{
    return [
        'dashboard.view' => 'Dashboard – View',
        'prospects.view' => 'Prospects – View',
        'prospects.create' => 'Prospects – Create',
        'prospects.edit' => 'Prospects – Edit',
        'prospects.stage' => 'Prospects – Change stage',
        'followups.view' => 'Follow-ups – View',
        'followups.create' => 'Follow-ups – Create / Complete',
        'onboarding.view' => 'Onboarding – View',
        'onboarding.edit' => 'Onboarding – Edit / Documents / Submit',
        'approvals.view' => 'Approvals – View',
        'approvals.decide' => 'Approvals – Approve / Reject',
        'customers.view' => 'Customers – View',
        'customers.create' => 'Customers – Add existing',
        'customers.edit' => 'Customers – Edit',
        'reports.view' => 'Reports – View',
        'reports.export' => 'Reports – Excel export',
        'notifications.view' => 'Notifications',
        'logs.view' => 'Logs – View / Export',
        'roles.manage' => 'Roles – Manage (checkboxes)',
        'users.manage' => 'Users – Create / Edit / Activate',
        'help.view' => 'Help',
    ];
}

function permission_groups(): array
{
    return [
        'Dashboard' => ['dashboard.view'],
        'Sales' => ['prospects.view', 'prospects.create', 'prospects.edit', 'prospects.stage', 'followups.view', 'followups.create'],
        'Onboarding' => ['onboarding.view', 'onboarding.edit'],
        'Account / Approvals' => ['approvals.view', 'approvals.decide', 'customers.view', 'customers.create', 'customers.edit'],
        'Reports & Alerts' => ['reports.view', 'reports.export', 'notifications.view', 'logs.view'],
        'Admin' => ['roles.manage', 'users.manage', 'help.view'],
    ];
}

/** Default checked permissions by role key (Admin / Salesperson / Management / Viewer) */
function role_default_checks(string $key): array
{
    $all = array_keys(permission_catalog());
    $set = static function (array $keys) use ($all): array {
        $out = array_fill_keys($all, false);
        foreach ($keys as $k) {
            if (isset($out[$k])) {
                $out[$k] = true;
            }
        }
        return $out;
    };

    switch ($key) {
        case 'super_admin':
        case 'admin':
        case 'account': // legacy alias → Admin
            return array_fill_keys($all, true);

        case 'salesperson':
        case 'sales': // legacy alias
            return $set([
                'dashboard.view',
                'prospects.view', 'prospects.create', 'prospects.edit', 'prospects.stage',
                'followups.view', 'followups.create',
                'onboarding.view', 'onboarding.edit',
                'customers.view',
                'reports.view',
                'notifications.view',
                'help.view',
            ]);

        case 'management':
            return $set([
                'dashboard.view',
                'prospects.view',
                'followups.view',
                'onboarding.view',
                'approvals.view',
                'customers.view',
                'reports.view', 'reports.export',
                'notifications.view',
                'logs.view',
                'help.view',
            ]);

        case 'viewer':
            return $set([
                'dashboard.view',
                'prospects.view',
                'followups.view',
                'onboarding.view',
                'customers.view',
                'reports.view',
                'notifications.view',
                'help.view',
            ]);

        default:
            return array_fill_keys($all, false);
    }
}

function ensure_auth_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $pdo = db();
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS roles (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              name VARCHAR(80) NOT NULL,
              description VARCHAR(255) DEFAULT NULL,
              permissions_json TEXT NOT NULL,
              is_system TINYINT(1) NOT NULL DEFAULT 0,
              status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY uq_roles_name (name)
            ) ENGINE=InnoDB"
        );

        $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'role_id'")->fetch();
        if (!$cols) {
            $pdo->exec('ALTER TABLE users ADD COLUMN role_id INT UNSIGNED NULL AFTER role');
        }

        seed_default_roles();
    } catch (Throwable $e) {
        // schema may be mid-setup
    }
}

function seed_default_roles(): void
{
    $pdo = db();

    // Rename legacy role names → standard set
    $renames = [
        'Account' => ['Admin', 'Admin – approvals, customers, users, reports (editable checkboxes)'],
        'Sales' => ['Salesperson', 'Salesperson – prospects, follow-ups, onboarding (editable checkboxes)'],
    ];
    foreach ($renames as $old => [$new, $desc]) {
        $hasOld = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
        $hasOld->execute([$old]);
        $oldRow = $hasOld->fetch();
        if (!$oldRow) {
            continue;
        }
        $hasNew = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
        $hasNew->execute([$new]);
        $newRow = $hasNew->fetch();
        if ($newRow) {
            // Prefer the new name; move users then drop old
            $pdo->prepare('UPDATE users SET role_id = ? WHERE role_id = ?')
                ->execute([(int) $newRow['id'], (int) $oldRow['id']]);
            $pdo->prepare('DELETE FROM roles WHERE id = ?')->execute([(int) $oldRow['id']]);
        } else {
            $pdo->prepare('UPDATE roles SET name = ?, description = ? WHERE id = ?')
                ->execute([$new, $desc, (int) $oldRow['id']]);
        }
    }

    $seeds = [
        ['Super Admin', 'Full access to everything', 'super_admin', 1],
        ['Admin', 'Admin – approvals, customers, users, reports (customize with checkboxes)', 'admin', 1],
        ['Salesperson', 'Salesperson – prospects, follow-ups, onboarding (customize with checkboxes)', 'salesperson', 1],
        ['Management', 'Management – view data + reports export (customize with checkboxes)', 'management', 1],
        ['Viewer', 'Viewer – read-only access (customize with checkboxes)', 'viewer', 1],
    ];

    $ins = $pdo->prepare(
        'INSERT INTO roles (name, description, permissions_json, is_system, status)
         VALUES (?, ?, ?, ?, \'Active\')'
    );

    foreach ($seeds as [$name, $desc, $key, $sys]) {
        $perms = role_default_checks($key);
        // Only set permissions_json on insert; don't wipe custom checkbox edits on re-seed
        $exists = $pdo->prepare('SELECT id, permissions_json FROM roles WHERE name = ?');
        $exists->execute([$name]);
        $row = $exists->fetch();
        if (!$row) {
            $ins->execute([$name, $desc, json_encode($perms), $sys]);
        } elseif ($row['permissions_json'] === '' || $row['permissions_json'] === '[]' || $row['permissions_json'] === '{}') {
            $pdo->prepare('UPDATE roles SET permissions_json = ?, description = ?, is_system = ? WHERE id = ?')
                ->execute([json_encode($perms), $desc, $sys, $row['id']]);
        } else {
            $pdo->prepare('UPDATE roles SET description = ?, is_system = ? WHERE id = ?')
                ->execute([$desc, $sys, $row['id']]);
        }
    }

    // Link system user to Super Admin
    $roleId = (int) $pdo->query("SELECT id FROM roles WHERE name = 'Super Admin' LIMIT 1")->fetchColumn();
    if ($roleId) {
        $pdo->prepare('UPDATE users SET role_id = ? WHERE id = 1 AND (role_id IS NULL OR role_id = 0)')
            ->execute([$roleId]);
    }
}

function decode_permissions(?string $json): array
{
    $all = array_fill_keys(array_keys(permission_catalog()), false);
    if ($json === null || $json === '') {
        return $all;
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return $all;
    }
    foreach ($data as $k => $v) {
        if (array_key_exists($k, $all)) {
            $all[$k] = (bool) $v;
        }
    }
    return $all;
}

function encode_permissions(array $perms): string
{
    $all = array_fill_keys(array_keys(permission_catalog()), false);
    foreach ($perms as $k => $v) {
        if (array_key_exists($k, $all)) {
            $all[$k] = (bool) $v;
        }
    }
    return json_encode($all);
}

function permissions_from_post(array $post): array
{
    $all = array_fill_keys(array_keys(permission_catalog()), false);
    $checked = $post['perm'] ?? [];
    if (!is_array($checked)) {
        return $all;
    }
    foreach ($checked as $key => $val) {
        if (array_key_exists($key, $all)) {
            $all[$key] = true;
        }
    }
    return $all;
}

function auth_user(): ?array
{
    if (empty($_SESSION['auth_user_id'])) {
        return null;
    }
    try {
        ensure_auth_schema();
        $stmt = db()->prepare(
            'SELECT u.*, r.name AS role_name, r.permissions_json
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([(int) $_SESSION['auth_user_id']]);
        $u = $stmt->fetch();
        if (!$u || $u['status'] !== 'Active') {
            return null;
        }
        $u['permissions'] = decode_permissions($u['permissions_json'] ?? null);
        // Super Admin / Account name always full access if role says so
        if (($u['role_name'] ?? '') === 'Super Admin') {
            $u['permissions'] = array_fill_keys(array_keys(permission_catalog()), true);
        }
        $u['name'] = $u['employee_name'];
        return $u;
    } catch (Throwable $e) {
        return null;
    }
}

function login_user(string $userId, string $password): array
{
    ensure_auth_schema();
    $stmt = db()->prepare(
        'SELECT u.*, r.name AS role_name, r.permissions_json
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.user_id = ? LIMIT 1'
    );
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    if (!$u) {
        return ['ok' => false, 'error' => 'Invalid login ID or password.'];
    }
    if ($u['status'] !== 'Active') {
        return ['ok' => false, 'error' => 'Account is inactive. Contact Admin.'];
    }
    if (!password_verify($password, $u['password_hash'])) {
        return ['ok' => false, 'error' => 'Invalid login ID or password.'];
    }
    session_regenerate_id(true);
    $_SESSION['auth_user_id'] = (int) $u['id'];
    return ['ok' => true, 'user' => $u];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function can(string $permission): bool
{
    $u = current_user();
    if (!empty($u['permissions'][$permission])) {
        return true;
    }
    // Super Admin always
    if (($u['role_name'] ?? '') === 'Super Admin') {
        return true;
    }
    return false;
}

function require_login(): void
{
    if (auth_user() === null) {
        $next = $_SERVER['REQUEST_URI'] ?? base_url('index.php');
        flash('error', 'Please login to continue.');
        redirect(base_url('login.php?next=' . urlencode($next)));
    }
}

function require_perm(string $permission): void
{
    require_login();
    if (!can($permission)) {
        http_response_code(403);
        flash('error', 'You do not have access to this action.');
        redirect(base_url('index.php'));
    }
}

function render_permission_checkboxes(array $currentPerms): void
{
    foreach (permission_groups() as $group => $keys) {
        echo '<h3>' . e($group) . '</h3>';
        echo '<div class="perm-grid">';
        foreach ($keys as $key) {
            $label = permission_catalog()[$key] ?? $key;
            $checked = !empty($currentPerms[$key]) ? ' checked' : '';
            echo '<label class="perm-item"><input type="checkbox" name="perm[' . e($key) . ']" value="1"' . $checked . '> ' . e($label) . '</label>';
        }
        echo '</div>';
    }
}
