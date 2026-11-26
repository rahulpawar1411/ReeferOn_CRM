<?php
/**
 * One-time: ensure roles + demo Admin / Salesperson / Management / Viewer users.
 * Run: php sql/seed_auth_demo.php
 */
require_once __DIR__ . '/../includes/functions.php';

ensure_auth_schema();

echo 'roles=' . db()->query('SELECT COUNT(*) FROM roles')->fetchColumn() . PHP_EOL;

$roleId = static function (string $name): int {
    return (int) db()->query("SELECT id FROM roles WHERE name=" . db()->quote($name))->fetchColumn();
};

$sales = $roleId('Salesperson');
$admin = $roleId('Admin');
$mgmt = $roleId('Management');
$viewer = $roleId('Viewer');
echo "salesperson={$sales} admin={$admin} management={$mgmt} viewer={$viewer}\n";

$ensureUser = static function (string $login, array $row, ?int $forceRoleId = null) {
    $chk = db()->prepare('SELECT id, role_id FROM users WHERE user_id = ?');
    $chk->execute([$login]);
    $existing = $chk->fetch();
    if ($existing) {
        if ($forceRoleId && (int) $existing['role_id'] !== $forceRoleId) {
            db()->prepare('UPDATE users SET role_id = ?, role = ? WHERE id = ?')
                ->execute([$forceRoleId, $row[7], (int) $existing['id']]);
            echo "updated role {$login}\n";
        } else {
            echo "exists {$login}\n";
        }
        return;
    }
    $ins = db()->prepare(
        'INSERT INTO users (employee_id, employee_name, email, mobile, user_id, password_hash, department, role, role_id, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute($row);
    echo "created {$login}\n";
};

if ($sales) {
    $ensureUser('sales', [
        'EMP-002', 'Sales Demo', 'sales@reeferon.local', '9888888888', 'sales',
        password_hash('sales123', PASSWORD_DEFAULT), 'Sales', 'Salesperson', $sales, 'Active',
    ], $sales);
}
if ($admin) {
    $ensureUser('admin', [
        'EMP-010', 'Admin Demo', 'admin@reeferon.local', '9777777777', 'admin',
        password_hash('admin123', PASSWORD_DEFAULT), 'Admin', 'Admin', $admin, 'Active',
    ], $admin);
    // Legacy login id "account" → Admin role
    $ensureUser('account', [
        'EMP-003', 'Admin Demo (legacy)', 'account@reeferon.local', '9777777776', 'account',
        password_hash('account123', PASSWORD_DEFAULT), 'Admin', 'Admin', $admin, 'Active',
    ], $admin);
}
if ($mgmt) {
    $ensureUser('management', [
        'EMP-004', 'Management Demo', 'mgmt@reeferon.local', '9666666666', 'management',
        password_hash('mgmt123', PASSWORD_DEFAULT), 'Management', 'Management', $mgmt, 'Active',
    ], $mgmt);
}
if ($viewer) {
    $ensureUser('viewer', [
        'EMP-005', 'Viewer Demo', 'viewer@reeferon.local', '9555555555', 'viewer',
        password_hash('viewer123', PASSWORD_DEFAULT), 'Admin', 'Viewer', $viewer, 'Active',
    ], $viewer);
}

echo "OK\n";
