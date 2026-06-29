<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'admin';
$_SESSION['user_role'] = 'super_admin';

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'profile_update' => 1,
    'username' => 'admin',
    'email' => 'admin@example.com',
    'password' => '',
    'confirm_password' => ''
];

require 'app/config/connection.php';
require 'component/admin_header.php';

$pending = $_SESSION['profile_pending_update'] ?? null;
if (!$pending) {
    echo "NO_PENDING\n";
    exit;
}

$_POST = [
    'profile_update' => 1,
    'username' => 'admin',
    'email' => 'admin@example.com',
    'password' => '',
    'confirm_password' => '',
    'otp_code' => '000000'
];

require 'component/admin_header.php';
