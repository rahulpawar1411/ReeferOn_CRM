<?php
/**
 * Edit / create user
 *
 * Set employee login details, password, department, and role.
 * Permission: users.manage
 */
$pageTitle = 'Edit User';
$currentPage = 'users';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('users.manage');
ensure_auth_schema();

$id = (int) ($_GET['id'] ?? 0);
$userRow = null;
if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $userRow = $stmt->fetch();
    if (!$userRow) {
        flash('error', 'User not found.');
        redirect(base_url('modules/users/index.php'));
    }
}

$roles = db()->query("SELECT id, name FROM roles WHERE status = 'Active' ORDER BY name")->fetchAll();
$errors = [];

$form = [
    'employee_id' => $userRow['employee_id'] ?? '',
    'employee_name' => $userRow['employee_name'] ?? '',
    'email' => $userRow['email'] ?? '',
    'mobile' => phone_digits($userRow['mobile'] ?? ''),
    'user_id' => $userRow['user_id'] ?? '',
    'department' => $userRow['department'] ?? 'Sales',
    'role_id' => $userRow['role_id'] ?? '',
    'status' => $userRow['status'] ?? 'Active',
];

// Save user details (and optional new password)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) {
        if (isset($_POST[$k])) {
            $form[$k] = is_string($_POST[$k]) ? trim($_POST[$k]) : $_POST[$k];
        }
    }
    $form['mobile'] = phone_digits((string) $form['mobile']);
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($form['employee_id'] === '') $errors[] = 'Employee ID required.';
    if ($form['employee_name'] === '') $errors[] = 'Employee name required.';
    if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if ($form['user_id'] === '') $errors[] = 'Username required.';
    $phoneErr = phone_error($form['mobile'], false);
    if ($phoneErr) $errors[] = $phoneErr;
    if (!in_array($form['department'], ['Sales','Business Development','Operations','Finance','Admin','Management'], true)) {
        $errors[] = 'Invalid department.';
    }
    if (!in_array($form['status'], ['Active', 'Inactive'], true)) $errors[] = 'Invalid status.';
    if ($form['role_id'] === '' || !ctype_digit((string) $form['role_id'])) {
        $errors[] = 'Select a role.';
    }
    if (!$userRow && $password === '') {
        $errors[] = 'Password required for new user.';
    }
    if ($password !== '' && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $password2) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (!$errors) {
        try {
            $roleStmt = db()->prepare('SELECT id, name FROM roles WHERE id = ?');
            $roleStmt->execute([(int) $form['role_id']]);
            $role = $roleStmt->fetch();
            if (!$role) {
                throw new RuntimeException('Role not found.');
            }

            // Map to legacy ENUM column
            $legacyRole = 'Salesperson';
            if ($role['name'] === 'Super Admin') $legacyRole = 'Super Admin';
            elseif ($role['name'] === 'Admin' || $role['name'] === 'Account') $legacyRole = 'Admin';
            elseif ($role['name'] === 'Management') $legacyRole = 'Management';
            elseif ($role['name'] === 'Viewer') $legacyRole = 'Viewer';
            elseif ($role['name'] === 'Salesperson' || $role['name'] === 'Sales') $legacyRole = 'Salesperson';

            $mobileSave = phone_store($form['mobile']);

            if ($userRow) {
                if ($password !== '') {
                    $upd = db()->prepare(
                        'UPDATE users SET employee_id=?, employee_name=?, email=?, mobile=?, user_id=?,
                            department=?, role=?, role_id=?, status=?, password_hash=? WHERE id=?'
                    );
                    $upd->execute([
                        $form['employee_id'], $form['employee_name'], $form['email'],
                        $mobileSave,
                        $form['user_id'], $form['department'], $legacyRole, (int) $form['role_id'],
                        $form['status'], password_hash($password, PASSWORD_DEFAULT), $id,
                    ]);
                } else {
                    $upd = db()->prepare(
                        'UPDATE users SET employee_id=?, employee_name=?, email=?, mobile=?, user_id=?,
                            department=?, role=?, role_id=?, status=? WHERE id=?'
                    );
                    $upd->execute([
                        $form['employee_id'], $form['employee_name'], $form['email'],
                        $mobileSave,
                        $form['user_id'], $form['department'], $legacyRole, (int) $form['role_id'],
                        $form['status'], $id,
                    ]);
                }
                write_audit('users', $form['user_id'], 'UPDATE', null, 'User updated / role=' . $role['name']);
                flash('success', 'User updated.', 'edit');
            } else {
                $ins = db()->prepare(
                    'INSERT INTO users (employee_id, employee_name, email, mobile, user_id, password_hash, department, role, role_id, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([
                    $form['employee_id'], $form['employee_name'], $form['email'],
                    $mobileSave,
                    $form['user_id'], password_hash($password, PASSWORD_DEFAULT),
                    $form['department'], $legacyRole, (int) $form['role_id'], $form['status'],
                ]);
                write_audit('users', $form['user_id'], 'CREATE', null, 'User created / role=' . $role['name']);
                flash('success', 'User created.', 'create');
            }
            redirect(base_url('modules/users/index.php'));
        } catch (Throwable $e) {
            $errors[] = 'Could not save: ' . $e->getMessage();
        }
    }
}

$pageTitle = $userRow ? ('Edit User – ' . $userRow['employee_name']) : 'New User';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2><?= $userRow ? 'Edit user' : 'Create user' ?></h2>
    <p class="step-note">Assign a role (Admin / Salesperson / Management / Viewer). Access comes from that role’s checkboxes — customize under Roles.</p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-grid">
            <div class="form-group">
                <label>Employee ID <span class="required">*</span></label>
                <input type="text" name="employee_id" required value="<?= e($form['employee_id']) ?>">
            </div>
            <div class="form-group">
                <label>Employee name <span class="required">*</span></label>
                <input type="text" name="employee_name" required value="<?= e($form['employee_name']) ?>">
            </div>
            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" required value="<?= e($form['email']) ?>">
            </div>
            <div class="form-group">
                <label>Phone number</label>
                <?php render_phone_input('mobile', $form['mobile'], false); ?>
            </div>
            <div class="form-group">
                <label>Username <span class="required">*</span></label>
                <input type="text" name="user_id" required value="<?= e($form['user_id']) ?>">
            </div>
            <div class="form-group">
                <label>Department</label>
                <select name="department">
                    <?php foreach (['Sales','Business Development','Operations','Finance','Admin','Management'] as $d): ?>
                        <option value="<?= e($d) ?>" <?= $form['department'] === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Role <span class="required">*</span></label>
                <div class="input-with-action">
                    <select name="role_id" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int) $r['id'] ?>" <?= ((string) $form['role_id'] === (string) $r['id']) ? 'selected' : '' ?>>
                                <?= e($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a class="input-action-btn" href="<?= e(base_url('modules/roles/edit.php')) ?>"
                       title="Create new role" aria-label="Create new role">+</a>
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <?php foreach (['Active', 'Inactive'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= $form['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Password <?= $userRow ? '' : '<span class="required">*</span>' ?></label>
                <input type="password" name="password" <?= $userRow ? '' : 'required' ?> autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Confirm password</label>
                <input type="password" name="password2" autocomplete="new-password">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn">Save</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/users/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
