<?php
/**
 * Roles list
 *
 * Shows each role and how many permissions are allowed.
 * Permission: roles.manage
 */
$pageTitle = 'Roles';
$currentPage = 'roles';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('roles.manage');
ensure_auth_schema();

$rows = db()->query('SELECT * FROM roles ORDER BY is_system DESC, name ASC')->fetchAll();
$firstId = $rows ? (int) $rows[0]['id'] : 0;

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toolbar toolbar-end">
    <?php if ($firstId): ?>
        <a class="btn" href="<?= e(base_url('modules/roles/edit.php?id=' . $firstId)) ?>">Edit Checkboxes</a>
    <?php endif; ?>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/roles/edit.php')) ?>">+ New Role</a>
</div>

<div class="card">
    <h2>Roles &amp; access</h2>
    <p class="step-note">
        Defaults for <strong>Admin</strong>, <strong>Salesperson</strong>, <strong>Management</strong>, <strong>Viewer</strong>.
        <strong>Edit Checkboxes</strong> pe jaake role dropdown se choose karo.
    </p>

    <?php if (!$rows): ?>
        <div class="empty">No roles yet.</div>
    <?php else: ?>
        <table class="data">
            <thead>
            <tr>
                <th>Role</th>
                <th>Description</th>
                <th>Allowed</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $perms = decode_permissions($r['permissions_json']);
                $allowed = count(array_filter($perms));
                $total = count($perms);
                ?>
                <tr>
                    <td><strong><?= e($r['name']) ?></strong></td>
                    <td><?= e($r['description'] ?? '-') ?></td>
                    <td><?= (int) $allowed ?> / <?= (int) $total ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
