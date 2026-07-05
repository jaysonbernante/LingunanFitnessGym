<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SESSION_TIMEOUT_SECONDS', 1200); // 20 minutes

function clear_member_session() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
}

$memberType = $_SESSION['member_type'] ?? '';
if (!isset($_SESSION['member_id']) || !in_array($memberType, ['member', 'session'], true)) {
    clear_member_session();
    header('Location: /LingunanFitnessGym/public/member/index.php');
    exit();
}

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT id, type FROM members WHERE id = ? AND type IN ('member', 'session') LIMIT 1");
        $stmt->execute([$_SESSION['member_id']]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            clear_member_session();
            header('Location: /LingunanFitnessGym/public/member/index.php');
            exit();
        }
    } catch (Exception $e) {
        clear_member_session();
        header('Location: /LingunanFitnessGym/public/member/index.php');
        exit();
    }
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT_SECONDS) {
    clear_member_session();
    header('Location: /LingunanFitnessGym/public/member/index.php');
    exit();
}

$_SESSION['last_activity'] = time();
