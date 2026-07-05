<?php
session_start();
require_once '../../../app/config/connection.php';

$token = trim($_GET['token'] ?? '');
if ($token === '') {
    $_SESSION['login_error'] = 'Invalid or missing auto-login token.';
    header('Location: ../../index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM password_reset_requests WHERE auto_login_token = ? AND auto_login_expiry IS NOT NULL AND auto_login_expiry >= NOW() LIMIT 1");
$stmt->execute([$token]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) {
    $_SESSION['login_error'] = 'This auto-login link is invalid or has expired.';
    header('Location: ../../index.php');
    exit;
}

// Load user
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$userStmt->execute([$req['user_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    $_SESSION['login_error'] = 'User account not found.';
    header('Location: ../../index.php');
    exit;
}

// Create session as if they logged in
$_SESSION['login_success'] = 'You have been signed in automatically.';
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['username'];
$_SESSION['user_role'] = $user['role'];

// Clear token so it cannot be reused and mark request as used
$pdo->prepare("UPDATE password_reset_requests SET auto_login_token = NULL, auto_login_expiry = NULL, status = 'used', handled_at = NOW() WHERE id = ?")
    ->execute([$req['id']]);

// Ensure the user is active and no longer flagged for reset
$pdo->prepare("UPDATE users SET status = 'active', password_reset_required = 0 WHERE id = ?")
    ->execute([$user['id']]);

// Redirect to staff dashboard
header('Location: dashboard.php');
exit;
