<?php
session_start();
require_once '../../app/config/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    $sql = "SELECT * FROM members 
            WHERE (username = :username OR gmail = :gmail) 
            AND type = 'member' 
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'username' => $login,
        'gmail' => $login
    ]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['login_success'] = 'Login successful! Welcome.';
        $_SESSION['member_id'] = $user['id'];
        $_SESSION['member_username'] = $user['username'];
        $_SESSION['member_type'] = $user['type'];

        header('Location: dashboard.php');
        exit();

    } else {

        $_SESSION['login_error'] = 'Invalid username/gmail or password.';
        header('Location: index.php');
        exit();
    }

} else {
    header('Location: index.php');
    exit();
}