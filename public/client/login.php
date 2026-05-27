<?php
session_start();
require_once '../../app/config/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check admin or staff
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

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['login_success'] = 'Login successful! Welcome.';
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];

        if ($user['role'] === 'staff') {
            header('Location: staff/dashboard.php');
        } else {
            header('Location: admin/dashboard.php');
        }
        exit();

    } else {

        $_SESSION['login_error'] = 'Invalid login credentials.';
        header('Location: index.php');
        exit();

    }

} else {
    header('Location: index.php');
    exit();
}