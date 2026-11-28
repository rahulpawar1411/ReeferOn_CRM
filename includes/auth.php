<?php
/**
 * ReeferON CRM – Login, roles, and permissions
 *
 * How access works (simple):
 * 1) User logs in → we save their user id in the PHP session.
 * 2) Each user has a Role (Admin, Salesperson, Management, Viewer…).
 * 3) Each role has checkbox permissions stored as JSON.
 * 4) Pages call require_login() and/or require_perm('something').
 *
 * Useful functions:
 * - login_user() / logout_user()
 * - can('permission.key') / require_perm('permission.key')
 * - role_default_checks() = default checkboxes for a role type
 */
require_once __DIR__ . '/../config/database.php';

// Start session once so login state is available on every page
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/** Full list of permission keys shown as checkboxes on the Role edit screen. */
function permission_catalog(): array
{
    return [
        'dashboard.view' => 'Dashboard – View',
        'prospects.view' => 'Prospects – View',
        'prospects.create' => 'Prospects – Create',
        'prospects.edit' => 'Prospects – Edit',
        'prospects.stage' => 'Prospects – Change stage',
        // Inactive = soft status only. Default ON for Admin; grant to others via checkbox.
        'prospects.delete' => 'Inactive Prospects – give only to trusted roles (Admin default)',
        'followups.view' => 'Follow-ups – View',
        'followups.create' => 'Follow-ups – Create / Complete',
        'onboarding.view' => 'Onboarding – View',
        'onboarding.edit' => 'Onboarding – Edit / Documents / Submit',
        'onboarding.delete_docs' => 'Clear Document files – give only to trusted roles (Admin default)',
        'approvals.view' => 'Approvals – View',
        'approvals.decide' => 'Approvals – Approve / Reject',
        'customers.view' => 'Customers – View',
        'customers.create' => 'Customers – Add existing',
        'customers.edit' => 'Customers – Edit',
        'customers.delete' => 'Inactive Customers – give only to trusted roles (Admin default)',
        'reports.view' => 'Reports – View',
        'reports.export' => 'Reports – Excel export',
        'notifications.view' => 'Notifications',
        'logs.view' => 'Logs – View / Export',
        'roles.manage' => 'Roles – Manage (checkboxes)',
        'users.manage' => 'Users – Create / Edit / Make Inactive – Admin default',
        'masters.dropdowns' => 'Masters – Dropdown options (add / remove)',
        'help.view' => 'Help',
    ];
}

/** Group permissions under headings for easier editing. */
function permission_groups(): array
{
    return [
        'Dashboard' => ['dashboard.view'],
        'Sales' => ['prospects.view', 'prospects.create', 'prospects.edit', 'prospects.stage', 'followups.view', 'followups.create'],
        'Onboarding' => ['onboarding.view', 'onboarding.edit'],
        'Account / Approvals' => ['approvals.view', 'approvals.decide', 'customers.view', 'customers.create', 'customers.edit'],
        'Inactive / Clear (Admin default – tick to give others access)' => [
            'prospects.delete',
            'customers.delete',
            'onboarding.delete_docs',
        ],
        'Reports & Alerts' => ['reports.view', 'reports.export', 'notifications.view', 'logs.view'],
        'Admin' => ['roles.manage', 'users.manage', 'masters.dropdowns', 'help.view'],
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

/**
 * Make sure roles table exists, then seed default roles.
 * Safe to call many times (runs once per request).
 */
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

/** Create / update the standard roles. Does not wipe custom checkbox edits. */
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

    // Newer Inactive / Clear keys (*.delete, delete_docs): grant Admin / Super Admin if missing in saved JSON
    $adminRows = $pdo->query(
        "SELECT id, name, permissions_json FROM roles WHERE name IN ('Admin', 'Super Admin')"
    )->fetchAll();
    foreach ($adminRows as $ar) {
        $raw = json_decode((string) ($ar['permissions_json'] ?? ''), true);
        if (!is_array($raw)) {
            $raw = [];
        }
        $perms = decode_permissions($ar['permissions_json'] ?? null);
        $changed = false;
        foreach (['prospects.delete', 'customers.delete', 'onboarding.delete_docs', 'masters.dropdowns'] as $key) {
            // Always keep these ON for Admin / Super Admin (even if older save had false)
            if (empty($perms[$key])) {
                $perms[$key] = true;
                $changed = true;
            }
        }
        if ($changed) {
            $pdo->prepare('UPDATE roles SET permissions_json = ? WHERE id = ?')
                ->execute([encode_permissions($perms), (int) $ar['id']]);
        }
    }

    // Link system user to Super Admin
    $roleId = (int) $pdo->query("SELECT id FROM roles WHERE name = 'Super Admin' LIMIT 1")->fetchColumn();
    if ($roleId) {
        $pdo->prepare('UPDATE users SET role_id = ? WHERE id = 1 AND (role_id IS NULL OR role_id = 0)')
            ->execute([$roleId]);
    }
}

/** Turn permissions JSON from DB into a true/false array. */
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

/** Turn a true/false permissions array into JSON for saving. */
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

/** Read checked boxes from the Role edit form (POST). */
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

/** Load the logged-in user from session + DB. Returns null if not logged in. */
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
        // Super Admin always gets every permission
        if (($u['role_name'] ?? '') === 'Super Admin') {
            $u['permissions'] = array_fill_keys(array_keys(permission_catalog()), true);
        }
        $u['name'] = $u['employee_name'];
        return $u;
    } catch (Throwable $e) {
        return null;
    }
}

/** Max wrong passwords before a short lock. */
function login_max_attempts(): int
{
    return 5;
}

/** Lock duration in seconds after too many wrong passwords. */
function login_lock_seconds(): int
{
    return 30;
}

/** Seconds left on login lock (0 = not locked). */
function login_lock_remaining(): int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $until = (int) ($_SESSION['login_lock_until'] ?? 0);
    if ($until <= 0) {
        return 0;
    }
    $left = $until - time();
    if ($left <= 0) {
        unset($_SESSION['login_lock_until'], $_SESSION['login_fails']);
        return 0;
    }
    return $left;
}

/** Clear failed-login counters after a successful sign-in. */
function login_clear_attempts(): void
{
    unset($_SESSION['login_fails'], $_SESSION['login_lock_until']);
}

/**
 * Record a failed login. After 5 failures, start a 30-second lock.
 * Returns remaining lock seconds (0 if not locked yet).
 */
function login_register_failure(): int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $fails = (int) ($_SESSION['login_fails'] ?? 0) + 1;
    $_SESSION['login_fails'] = $fails;
    if ($fails >= login_max_attempts()) {
        $_SESSION['login_lock_until'] = time() + login_lock_seconds();
        $_SESSION['login_fails'] = 0;
        return login_lock_seconds();
    }
    return 0;
}

/** How many wrong tries are left before lock (for UI messages). */
function login_attempts_left(): int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $fails = (int) ($_SESSION['login_fails'] ?? 0);
    $left = login_max_attempts() - $fails;
    return max(0, $left);
}

/** Check login ID + password. On success, store user id in session. */
function login_user(string $userId, string $password): array
{
    ensure_auth_schema();

    $lockLeft = login_lock_remaining();
    if ($lockLeft > 0) {
        return [
            'ok' => false,
            'error' => 'Too many wrong passwords. Try again in ' . $lockLeft . ' seconds.',
            'lock_seconds' => $lockLeft,
        ];
    }

    $stmt = db()->prepare(
        'SELECT u.*, r.name AS role_name, r.permissions_json
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.user_id = ? LIMIT 1'
    );
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    if (!$u) {
        $locked = login_register_failure();
        if ($locked > 0) {
            return [
                'ok' => false,
                'error' => 'Too many wrong passwords. Try again in ' . $locked . ' seconds.',
                'lock_seconds' => $locked,
            ];
        }
        return [
            'ok' => false,
            'error' => 'Invalid login ID or password. ' . login_attempts_left() . ' attempt(s) left.',
            'attempts_left' => login_attempts_left(),
        ];
    }
    if ($u['status'] !== 'Active') {
        return ['ok' => false, 'error' => 'Account is inactive. Contact Admin.'];
    }
    if (!password_verify($password, $u['password_hash'])) {
        $locked = login_register_failure();
        if ($locked > 0) {
            return [
                'ok' => false,
                'error' => 'Too many wrong passwords. Try again in ' . $locked . ' seconds.',
                'lock_seconds' => $locked,
            ];
        }
        return [
            'ok' => false,
            'error' => 'Invalid login ID or password. ' . login_attempts_left() . ' attempt(s) left.',
            'attempts_left' => login_attempts_left(),
        ];
    }
    login_clear_attempts();
    session_regenerate_id(true);
    $_SESSION['auth_user_id'] = (int) $u['id'];
    return ['ok' => true, 'user' => $u];
}

/** Clear session and log the user out. */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** True if the current user has this permission checkbox. */
function can(string $permission): bool
{
    $u = current_user();
    if (!empty($u['permissions'][$permission])) {
        return true;
    }
    if (($u['role_name'] ?? '') === 'Super Admin') {
        return true;
    }
    return false;
}

/** Stop guests — send them to the login page. */
function require_login(): void
{
    if (auth_user() === null) {
        $next = $_SERVER['REQUEST_URI'] ?? base_url('index.php');
        flash('error', 'Please login to continue.');
        redirect(base_url('login.php?next=' . urlencode($next)));
    }
}

/** Stop users who do not have this permission. */
function require_perm(string $permission): void
{
    require_login();
    if (!can($permission)) {
        http_response_code(403);
        flash('error', 'You do not have access to this action.');
        redirect(base_url('index.php'));
    }
}

/** Print the permission checkbox groups on the Role edit page. */
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
