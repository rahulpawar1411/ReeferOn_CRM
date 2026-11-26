<?php
require_once __DIR__ . '/includes/functions.php';

$u = auth_user();
if ($u) {
    write_audit('users', $u['user_id'], 'LOGOUT', null, 'Logged out');
}
logout_user();
flash('success', 'You have been logged out.');
redirect(base_url('login.php'));
