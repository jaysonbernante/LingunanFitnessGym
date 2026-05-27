<?php
// Session check and timeout logic
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Session timeout: auto-logout after 20 minutes of inactivity ───────────
define('SESSION_TIMEOUT_SECONDS', 1200); // 20 minutes
$_inSubHeader = (strpos(str_replace('\\','/',$_SERVER['SCRIPT_FILENAME']), '/management/') !== false
              || strpos(str_replace('\\','/',$_SERVER['SCRIPT_FILENAME']), '/system/')    !== false);
$_loginUrl = $_inSubHeader ? '../index.php' : 'index.php';

if (!isset($_SESSION['user_id'])) {
    // Not logged in — redirect to login
    header('Location: ' . '../../client/index.php');
    exit();
}
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT_SECONDS) {
    // Session expired
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['login_error'] = 'Your session has expired due to inactivity. Please log in again.';
    header('Location: ' . $_loginUrl);
    exit();
}
$_SESSION['last_activity'] = time();
// ──────────────────────────────────────────────────────────────────────────
?>