<?php
/**
 * Users list
 *
 * Employee logins with role and Active / Inactive status.
 * Permission: users.manage
 */
$pageTitle = 'Users';
$currentPage = 'users';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('users.manage');
ensure_auth_schema();

$rows = db()->query(
    'SELECT u.id, u.employee_id, u.employee_name, u.email, u.user_id, u.department, u.status, u.role, r.name AS role_name
     FROM users u
     LEFT JOIN roles r ON r.id = u.role_id
     ORDER BY u.id ASC'
)->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toolbar">
    <a class="btn" href="<?= e(base_url('modules/users/edit.php')) ?>">+ New User</a>
</div>

<div class="card">
    <h2>Employee logins</h2>
    <p class="step-note">Each user gets a role. Role checkboxes control what menus and actions they can use.</p>

    <table class="data">
        <thead>
        <tr>
            <th>Employee</th>
            <th>Username</th>
            <th>Email</th>
            <th>Department</th>
            <th>Role</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['employee_name']) ?> <span class="muted">(<?= e($r['employee_id']) ?>)</span></td>
                <td><code><?= e($r['user_id']) ?></code></td>
                <td><?= e($r['email']) ?></td>
                <td><?= e($r['department']) ?></td>
                <td><?= e($r['role_name'] ?: $r['role']) ?></td>
                <td><span class="badge"><?= e($r['status']) ?></span></td>
                <td>
                    <a href="<?= e(base_url('modules/users/edit.php?id=' . $r['id'])) ?>">Edit</a>
                    <?php if (($r['status'] ?? '') !== 'Inactive' && (int) $r['id'] !== (int) current_user()['id']): ?>
                        | <a class="link-danger" href="<?= e(base_url('modules/users/delete.php?id=' . $r['id'])) ?>">Inactive</a>
                    <?php elseif (($r['status'] ?? '') === 'Inactive'): ?>
                        | <a class="link-success" href="<?= e(base_url('modules/users/activate.php?id=' . $r['id'])) ?>">Active</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
