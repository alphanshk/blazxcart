<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!is_logged_in()) {
    redirect('/auth/login.php');
}

$user = current_user();
if ($user['role'] === 'admin') {
    redirect('/admin/dashboard.php');
}
if ($user['role'] === 'seller') {
    redirect('/seller/dashboard.php');
}
redirect('/user/home.php');
