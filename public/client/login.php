<?php
session_start();
require_once '../../app/config/connection.php';

function redirectWithMessage($message, $type = 'error') {
    if ($type === 'success') {
        $_SESSION['login_success'] = $message;
    } else {
        $_SESSION['login_error'] = $message;
    }
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['forgot_password'])) {
        $username = trim($_POST['forgot_username'] ?? '');
        $reason = trim($_POST['forgot_reason'] ?? '');

        if ($username === '') {
            redirectWithMessage('Please provide a username for the password request.');
        }

        $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = ? AND role = 'staff' LIMIT 1");
        $stmt->execute([$username]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            redirectWithMessage('No staff account was found with that username.');
        }

        $pdo->prepare("INSERT INTO password_reset_requests (user_id, username, reason, status, created_at) VALUES (?, ?, ?, 'pending', NOW())")
            ->execute([$account['id'], $account['username'], $reason]);

        $pdo->prepare("UPDATE users SET status = 'inactive', password_reset_required = 1 WHERE id = ? AND role = 'staff'")
            ->execute([$account['id']]);

        redirectWithMessage('Your password reset request was sent to the admin. Please wait for approval.', 'success');
    }

    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $sql = "SELECT * FROM users 
            WHERE (email = :email OR username = :username)
            AND role IN ('super_admin','staff')
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'email' => $login,
        'username' => $login
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $isStaff = ($user['role'] ?? '') === 'staff';

        if ($isStaff) {
            $lockedUntil = $user['locked_until'] ?? null;
            if ($lockedUntil && strtotime($lockedUntil) > time()) {
                redirectWithMessage('This staff account is temporarily blocked for 10 minutes. Please use Forgot Password to request an unlock.');
            }

            if ($lockedUntil && strtotime($lockedUntil) <= time()) {
                $pdo->prepare("UPDATE users SET locked_until = NULL, failed_login_attempts = 0 WHERE id = ?")
                    ->execute([$user['id']]);
            }
        }

        if ($user && password_verify($password, $user['password'])) {
            $pdo->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, password_reset_required = 0 WHERE id = ?")
                ->execute([$user['id']]);

            $_SESSION['login_success'] = 'Login successful! Welcome.';
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                $pdo->prepare("UPDATE users SET remember_token = ?, remember_expires_at = ? WHERE id = ?")
                    ->execute([$token, $expires, $user['id']]);
                setcookie('gym_staff_remember', $token, time() + 60 * 60 * 24 * 30, '/');
            } else {
                $pdo->prepare("UPDATE users SET remember_token = NULL, remember_expires_at = NULL WHERE id = ?")
                    ->execute([$user['id']]);
                setcookie('gym_staff_remember', '', time() - 3600, '/');
            }

            if ($user['role'] === 'staff') {
                header('Location: staff/dashboard.php');
            } else {
                header('Location: admin/dashboard.php');
            }
            exit();
        }

        if ($isStaff) {
            $attempts = intval($user['failed_login_attempts'] ?? 0) + 1;
            $lockedUntil = null;
            $message = 'Invalid login credentials. ' . (6 - $attempts) . ' attempts remaining before lockout.';

            if ($attempts >= 6) {
                $lockedUntil = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                $message = 'Too many failed login attempts. This staff account is blocked for 10 minutes. Please use Forgot Password to request an unlock.';
            }

            $pdo->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?")
                ->execute([$attempts, $lockedUntil, $user['id']]);

            redirectWithMessage($message);
        }

        redirectWithMessage('Invalid login credentials.');
    }

    redirectWithMessage('Invalid login credentials.');
} else {
    header('Location: index.php');
    exit();
}