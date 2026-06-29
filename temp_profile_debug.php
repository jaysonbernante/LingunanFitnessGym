<?php
session_start();
// Simulate a live page request for admin_header.php profile update
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'admin';
$_SESSION['user_role'] = 'super_admin';

// Step 1: initial request to send OTP
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'profile_update' => '1',
    'username' => 'admin',
    'email' => 'jaysonbernante1@gmail.com',
    'password' => 'newpass123',
    'confirm_password' => 'newpass123',
    'otp_code' => ''
];
ob_start();
require 'component/admin_header.php';
$output1 = ob_get_clean();
$result1 = json_decode($output1, true);
echo "STEP1: "; var_export($result1); echo "\n";

if (isset($_SESSION['profile_pending_update'])) {
    $pending = $_SESSION['profile_pending_update'];
    echo "PENDING OK\n";
    echo "OTP_HASH_LEN=" . strlen($pending['otp_hash']) . "\n";
    echo "OTP_PLAIN=" . ($pending['otp_plain'] ?? 'MISSING') . "\n";
} else {
    echo "PENDING MISSING\n";
}

// Simulate second step with the same session
$otp = $_SESSION['profile_pending_update']['otp_plain'] ?? '000000';
$_POST = [
    'profile_update' => '1',
    'username' => 'admin',
    'email' => 'jaysonbernante1@gmail.com',
    'password' => 'newpass123',
    'confirm_password' => 'newpass123',
    'otp_code' => $otp
];
ob_start();
require 'component/admin_header.php';
$output2 = ob_get_clean();
$result2 = json_decode($output2, true);
echo "STEP2: "; var_export($result2); echo "\n";
