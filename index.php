<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!is_logged_in()) {
    redirect(base_url('auth/login.php'));
}

$user = current_user();
if ($user['role'] === 'admin') {
    redirect(base_url('admin/dashboard.php'));
}
if ($user['role'] === 'seller') {
    redirect(base_url('seller/dashboard.php'));
}
redirect(base_url('user/home.php'));
