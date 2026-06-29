<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'admin';
$_SESSION['user_role'] = 'super_admin';

require 'app/config/connection.php';

$otp = '123456';
$pendingHash = password_hash($otp, PASSWORD_DEFAULT);
$_SESSION['profile_pending_update'] = [
    'username' => 'admin',
    'email' => 'admin@example.com',
    'password_hash' => null,
    'otp_hash' => $pendingHash,
    'otp_expiry' => time() + 600
];

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'profile_update' => '1',
    'username' => 'admin',
    'email' => 'admin@example.com',
    'password' => '',
    'confirm_password' => '',
    'otp_code' => $otp
];

ob_start();
require 'component/admin_header.php';
$output = ob_get_clean();
var_dump($output);
