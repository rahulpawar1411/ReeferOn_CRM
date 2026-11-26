<?php
/**
 * Login page
 *
 * User enters Username + Password.
 * On success we save the session and send them to Dashboard (or ?next= URL).
 * Demo accounts are listed on the page for easy testing.
 */
require_once __DIR__ . '/includes/functions.php';

ensure_auth_schema();

// Already logged in → go to dashboard
if (auth_user()) {
    redirect(base_url('index.php'));
}

$errors = [];
$userId = trim($_POST['user_id'] ?? '');
$nextRaw = $_GET['next'] ?? ($_POST['next'] ?? '');
$defaultNext = base_url('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $result = login_user($userId, $password);
    if ($result['ok']) {
        write_audit('users', $userId, 'LOGIN', null, 'Logged in');
        flash('success', 'Welcome, ' . $result['user']['employee_name'] . '.');

        $next = is_string($nextRaw) ? $nextRaw : '';
        // Security: only allow in-app paths like /CRM/... (block external redirects)
        if ($next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//') || str_contains($next, 'login.php')) {
            $next = $defaultNext;
        }
        redirect($next);
    }
    $errors[] = $result['error'] ?? 'Login failed.';
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ReeferON CRM</title>
    <link rel="icon" type="image/png" href="<?= e(base_url('assets/logo.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body class="login-page">
<div class="login-wrap">
    <div class="login-card">
        <div class="login-brand">
            <img src="<?= e(base_url('assets/logo.png')) ?>?v=<?= (int) @filemtime(__DIR__ . '/assets/logo.png') ?>"
                 alt="ReeferON" class="login-logo">
            <h1>ReeferON CRM</h1>
            <p class="muted">Sign in with your Username</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <div><?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(base_url('login.php')) ?>" data-loading-text="Signing in…">
            <input type="hidden" name="next" value="<?= e(is_string($nextRaw) ? $nextRaw : '') ?>">
            <div class="form-group">
                <label for="user_id">Username <span class="required">*</span></label>
                <input id="user_id" type="text" name="user_id" required autofocus autocomplete="username"
                       value="<?= e($userId) ?>" placeholder="e.g. admin">
            </div>
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <div class="password-field">
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                    <button type="button" class="password-toggle" id="togglePassword" aria-label="Show password" title="Show password">
                        <!-- Open eye (password hidden) -->
                        <svg class="eye-icon eye-open" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <!-- Closed eye / slash (password visible) -->
                        <svg class="eye-icon eye-closed" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" hidden fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                            <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn login-btn">Login</button>
            </div>
        </form>

        <div class="login-demo">
            <div class="login-demo-title">Demo Usernames</div>
            <table class="login-demo-table">
                <thead>
                <tr><th>Username</th><th>Password</th><th>Role</th></tr>
                </thead>
                <tbody>
                <tr><td><code>system</code></td><td><code>password</code></td><td>Super Admin</td></tr>
                <tr><td><code>admin</code></td><td><code>admin123</code></td><td>Admin</td></tr>
                <tr><td><code>sales</code></td><td><code>sales123</code></td><td>Salesperson</td></tr>
                <tr><td><code>management</code></td><td><code>mgmt123</code></td><td>Management</td></tr>
                <tr><td><code>viewer</code></td><td><code>viewer123</code></td><td>Viewer</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
<script>
(function () {
  var input = document.getElementById('password');
  var btn = document.getElementById('togglePassword');
  if (!input || !btn) return;
  var openIcon = btn.querySelector('.eye-open');
  var closedIcon = btn.querySelector('.eye-closed');
  btn.addEventListener('click', function () {
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    if (openIcon) openIcon.hidden = show;
    if (closedIcon) closedIcon.hidden = !show;
    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    btn.title = show ? 'Hide password' : 'Show password';
    input.focus();
  });
})();
</script>
</body>
</html>
