<?php
require_once __DIR__ . '/app/config/connection.php';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'member';
    $table = $role === 'admin' ? 'admins' : 'members';

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    if ($role === 'staff' || $role === 'super_admin') {
        // Register staff or super_admin in users table
        $sql = "INSERT INTO users (username, password, email, role, created_at) VALUES (:username, :password, :email, :role, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'username' => $name,
            'password' => $hashedPassword,
            'email' => $name . '@example.com', // Placeholder, update as needed
            'role' => $role
        ]);
    } elseif ($role === 'member') {
        // Register member in members table (now using 'type' instead of 'role')
        $sql = "INSERT INTO members (username, gmail, first_name, last_name, password, phone, address, type, membership_start, membership_end) VALUES (:username, :gmail, :first_name, :last_name, :password, :phone, :address, :type, :membership_start, :membership_end)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'username' => $name, // Placeholder, update as needed
            'gmail' => $name . '@example.com', // Placeholder, update as needed
            'first_name' => $name,
            'last_name' => '', // Placeholder, update as needed
            'password' => $hashedPassword,
            'phone' => '', // Placeholder, update as needed
            'address' => '', // Placeholder, update as needed
            'type' => $role,
            'membership_start' => null,
            'membership_end' => null
        ]);
    } else {
        echo '<p>Invalid role selected.</p>';
        exit;
    }
    echo '<p>Registration successful!</p>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Simple Register</title>
</head>
<body>
    <h2>Register (Admin or Member)</h2>
    <form method="post">
        <label>Name: <input type="text" name="name" required></label><br><br>
        <label>Password: <input type="password" name="password" required></label><br><br>
        <label>Role:
            <select name="role">
                <option value="member">Member</option>
                <option value="staff">Staff</option>
                <option value="super_admin">super_admin</option>
            </select>
        </label><br><br>
        <button type="submit">Register</button>
    </form>
</body>
</html>
