<?php
/**
 * Login page
 *
 * User enters Username + Password.
 * On success we save the session and send them to Dashboard (or ?next= URL).
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
$lockSeconds = login_lock_remaining();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($lockSeconds > 0) {
        $errors[] = 'Too many wrong passwords. Try again in ' . $lockSeconds . ' seconds.';
    } else {
        $password = $_POST['password'] ?? '';
        $result = login_user($userId, $password);
        if ($result['ok']) {
            write_audit('users', $userId, 'LOGIN', null, 'Logged in');
            flash('success', 'Welcome, ' . $result['user']['employee_name'] . '.', 'login');

            $next = is_string($nextRaw) ? $nextRaw : '';
            // Security: only allow in-app paths like /CRM/... (block external redirects)
            if ($next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//') || str_contains($next, 'login.php')) {
                $next = $defaultNext;
            }
            redirect($next);
        }
        $errors[] = $result['error'] ?? 'Login failed.';
        $lockSeconds = (int) ($result['lock_seconds'] ?? login_lock_remaining());
    }
}

$locked = $lockSeconds > 0;
$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1a3a6b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="ReeferON CRM">
    <title>Login | ReeferON CRM</title>
    <link rel="icon" type="image/png" href="<?= e(base_url('assets/logo.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(base_url('assets/logo.png')) ?>">
    <link rel="manifest" href="<?= e(base_url('manifest.webmanifest')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>?v=<?= (int) @filemtime(__DIR__ . '/assets/css/style.css') ?>">
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

        <?php if ($locked): ?>
            <div class="alert alert-error" id="login-lock-box" data-lock-seconds="<?= (int) $lockSeconds ?>">
                <div>Too many wrong passwords.</div>
                <div>Please wait <strong id="login-lock-timer"><?= (int) $lockSeconds ?></strong> seconds, then try again.</div>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(base_url('login.php')) ?>" data-loading-text="Signing in…" id="login-form" <?= $locked ? 'aria-disabled="true"' : '' ?>>
            <input type="hidden" name="next" value="<?= e(is_string($nextRaw) ? $nextRaw : '') ?>">
            <div class="form-group">
                <label for="user_id">Username <span class="required">*</span></label>
                <input id="user_id" type="text" name="user_id" required <?= $locked ? '' : 'autofocus' ?> autocomplete="username"
                       value="<?= e($userId) ?>" placeholder="e.g. admin" <?= $locked ? 'disabled' : '' ?>>
            </div>
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <div class="password-field">
                    <input id="password" type="password" name="password" required autocomplete="current-password" <?= $locked ? 'disabled' : '' ?>>
                    <button type="button" class="password-toggle" id="togglePassword" aria-label="Show password" title="Show password" <?= $locked ? 'disabled' : '' ?>>
                        <span class="eye-slot" aria-hidden="true">
                            <svg class="eye-icon eye-open" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3.2"/>
                            </svg>
                            <svg class="eye-icon eye-closed" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.77 21.77 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 7 11 7a21.9 21.9 0 0 1-2.16 3.19"/>
                                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn login-btn" id="login-submit" <?= $locked ? 'disabled' : '' ?>>
                    <?= $locked ? 'Locked' : 'Login' ?>
                </button>
            </div>
        </form>

    </div>
</div>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
<script>
(function () {
  var input = document.getElementById('password');
  var btn = document.getElementById('togglePassword');
  if (input && btn) {
    var openIcon = btn.querySelector('.eye-open');
    var closedIcon = btn.querySelector('.eye-closed');
    function syncEye(showPassword) {
      // Only one icon at a time
      if (openIcon) openIcon.style.display = showPassword ? 'none' : 'block';
      if (closedIcon) closedIcon.style.display = showPassword ? 'block' : 'none';
      btn.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
      btn.title = showPassword ? 'Hide password' : 'Show password';
      btn.classList.toggle('is-showing', showPassword);
    }
    syncEye(false);
    btn.addEventListener('click', function () {
      if (btn.disabled) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      syncEye(show);
      input.focus();
    });
  }

  // After 5 wrong passwords: 30s countdown, then unlock the form
  var box = document.getElementById('login-lock-box');
  var timerEl = document.getElementById('login-lock-timer');
  if (!box || !timerEl) return;
  var left = parseInt(box.getAttribute('data-lock-seconds') || '0', 10);
  if (!(left > 0)) return;

  var userInput = document.getElementById('user_id');
  var passInput = document.getElementById('password');
  var submitBtn = document.getElementById('login-submit');
  var toggleBtn = document.getElementById('togglePassword');

  var tick = setInterval(function () {
    left -= 1;
    if (left <= 0) {
      clearInterval(tick);
      timerEl.textContent = '0';
      if (userInput) userInput.disabled = false;
      if (passInput) passInput.disabled = false;
      if (toggleBtn) toggleBtn.disabled = false;
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Login';
      }
      box.innerHTML = '<div>You can try logging in again.</div>';
      if (userInput) userInput.focus();
      return;
    }
    timerEl.textContent = String(left);
    if (submitBtn) submitBtn.textContent = 'Wait ' + left + 's';
  }, 1000);
})();

(function () {
  if (!('serviceWorker' in navigator)) return;
  var swUrl = <?= json_encode(base_url('sw.js')) ?>;
  window.addEventListener('load', function () {
    navigator.serviceWorker.register(swUrl).catch(function () {});
  });
})();
</script>
</body>
</html>
