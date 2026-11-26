<?php
/**
 * Edit / create role
 *
 * Set role name and permission checkboxes (what menus/actions are allowed).
 * Permission: roles.manage
 */
$pageTitle = 'Edit Role';
$currentPage = 'roles';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('roles.manage');
ensure_auth_schema();

$allRoles = db()->query('SELECT id, name FROM roles ORDER BY is_system DESC, name ASC')->fetchAll();

$id = (int) ($_GET['id'] ?? 0);
$role = null;
if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM roles WHERE id = ?');
    $stmt->execute([$id]);
    $role = $stmt->fetch();
    if (!$role) {
        flash('error', 'Role not found.');
        redirect(base_url('modules/roles/index.php'));
    }
}

$errors = [];
$name = $role['name'] ?? '';
$description = $role['description'] ?? '';
$perms = $role ? decode_permissions($role['permissions_json']) : role_default_checks('salesperson');

// Save role name + permission checkboxes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $perms = permissions_from_post($_POST);
    $preset = $_POST['preset'] ?? '';

    $presetKeys = ['admin', 'salesperson', 'management', 'viewer', 'super_admin', 'account', 'sales'];
    if ($preset !== '' && in_array($preset, $presetKeys, true)) {
        $perms = role_default_checks($preset);
    }

    if ($name === '') {
        $errors[] = 'Role name is required.';
    }

    if (!$errors) {
        try {
            $json = encode_permissions($perms);
            if ($role) {
                $upd = db()->prepare(
                    'UPDATE roles SET name = ?, description = ?, permissions_json = ? WHERE id = ?'
                );
                $upd->execute([
                    $name,
                    $description !== '' ? $description : null,
                    $json,
                    $id,
                ]);
                write_audit('roles', $name, 'UPDATE', null, 'Permissions updated');
                flash('success', 'Role updated.', 'edit');
                redirect(base_url('modules/roles/edit.php?id=' . $id));
            } else {
                $ins = db()->prepare(
                    'INSERT INTO roles (name, description, permissions_json, is_system, status)
                     VALUES (?, ?, ?, 0, \'Active\')'
                );
                $ins->execute([
                    $name,
                    $description !== '' ? $description : null,
                    $json,
                ]);
                $newId = (int) db()->lastInsertId();
                write_audit('roles', $name, 'CREATE', null, 'Role created');
                flash('success', 'Role created.', 'create');
                redirect(base_url('modules/roles/edit.php?id=' . $newId));
            }
        } catch (Throwable $e) {
            $errors[] = 'Could not save: ' . $e->getMessage();
        }
    }
}

$pageTitle = $role ? ('Edit Checkboxes – ' . $role['name']) : 'New Role';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2><?= $role ? 'Edit checkboxes' : 'Create role' ?></h2>
    <p class="step-note">
        Role choose karo → checkboxes tick/untick → Save.
        <strong>Delete</strong> by default sirf Admin pe ON hai.
        Dusre role ko dena ho to section <em>“Delete (Admin default…)”</em> mein checkbox tick karo.
    </p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($allRoles): ?>
        <div class="form-group" style="max-width:320px;margin-bottom:16px">
            <label for="switch_role">Role</label>
            <select id="switch_role">
                <option value="0" <?= !$role ? 'selected' : '' ?>>+ New Role</option>
                <?php foreach ($allRoles as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" <?= $role && (int) $role['id'] === (int) $r['id'] ? 'selected' : '' ?>>
                        <?= e($r['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <script>
        (function () {
          var sel = document.getElementById('switch_role');
          if (!sel) return;
          sel.addEventListener('change', function () {
            var id = sel.value;
            if (id === '0') {
              window.location.href = <?= json_encode(base_url('modules/roles/edit.php')) ?>;
            } else {
              window.location.href = <?= json_encode(base_url('modules/roles/edit.php?id=')) ?> + id;
            }
          });
        })();
        </script>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-grid">
            <div class="form-group">
                <label>Role name <span class="required">*</span></label>
                <input type="text" name="name" required value="<?= e($name) ?>" <?= (!empty($role['is_system']) ? 'readonly' : '') ?>>
            </div>
            <div class="form-group">
                <label>Load default checks</label>
                <select name="preset">
                    <option value="">— keep current ticks —</option>
                    <option value="admin">Admin defaults</option>
                    <option value="salesperson">Salesperson defaults</option>
                    <option value="management">Management defaults</option>
                    <option value="viewer">Viewer defaults</option>
                    <option value="super_admin">Super Admin (all)</option>
                </select>
            </div>
            <div class="form-group full">
                <label>Description</label>
                <input type="text" name="description" value="<?= e($description) ?>" placeholder="Short description">
            </div>
        </div>

        <h2 style="margin-top:18px">Access allows</h2>
        <?php render_permission_checkboxes($perms); ?>

        <div class="form-actions">
            <button type="submit" class="btn">Save role</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/roles/index.php')) ?>">Back to Roles</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
