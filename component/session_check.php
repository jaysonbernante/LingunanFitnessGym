<?php
// Session check and timeout logic
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Session timeout: auto-logout after 20 minutes of inactivity ───────────
define('SESSION_TIMEOUT_SECONDS', 1200); // 20 minutes

function clear_staff_session() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    setcookie('gym_staff_remember', '', time() - 3600, '/');
    session_destroy();
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    clear_staff_session();
    $_SESSION['login_error'] = 'Please log in to continue.';
    header('Location: /LingunanFitnessGym/public/client/index.php');
    exit();
}

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ? AND role IN ('super_admin','staff') LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || ($user['status'] ?? 'active') !== 'active') {
            clear_staff_session();
            $_SESSION['login_error'] = 'Your account is no longer active. Please log in again.';
            header('Location: /LingunanFitnessGym/public/client/index.php');
            exit();
        }

        $_SESSION['user_role'] = $user['role'];
    } catch (Exception $e) {
        clear_staff_session();
        $_SESSION['login_error'] = 'Session validation failed. Please log in again.';
        header('Location: /LingunanFitnessGym/public/client/index.php');
        exit();
    }
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT_SECONDS) {
    clear_staff_session();
    $_SESSION['login_error'] = 'Your session has expired due to inactivity. Please log in again.';
    header('Location: /LingunanFitnessGym/public/client/index.php');
    exit();
}

$_SESSION['last_activity'] = time();
// ──────────────────────────────────────────────────────────────────────────
?>