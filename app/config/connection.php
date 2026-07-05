<?php
date_default_timezone_set('Asia/Manila');

$host = 'sql201.infinityfree.com';
$db   = 'if0_41655270_dbgym';
$user = 'if0_41655270'; // Change if your MySQL user is different
$pass = '6IdBOGd4gag';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
	PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
	$pdo = new PDO($dsn, $user, $pass, $options);

	$pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_requests (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		username VARCHAR(100) NOT NULL,
		reason TEXT DEFAULT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'pending',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		handled_by VARCHAR(100) DEFAULT NULL,
		handled_at DATETIME DEFAULT NULL
	)");

	$columns = [
		['status', "VARCHAR(10) NOT NULL DEFAULT 'active'"],
		['failed_login_attempts', 'INT NOT NULL DEFAULT 0'],
		['locked_until', 'DATETIME NULL DEFAULT NULL'],
		['remember_token', 'VARCHAR(255) NULL DEFAULT NULL'],
		['remember_expires_at', 'DATETIME NULL DEFAULT NULL'],
		['password_reset_required', 'TINYINT(1) NOT NULL DEFAULT 0']
	];

	foreach ($columns as $column) {
		try {
			$pdo->exec("ALTER TABLE users ADD COLUMN {$column[0]} {$column[1]}");
		} catch (PDOException $e) {
			$message = $e->getMessage();
			if (stripos($message, 'Duplicate column') === false && stripos($message, 'already exists') === false) {
				throw $e;
			}
		}
	}

    // Ensure admin notifications table exists for system-wide alerts
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        created_by VARCHAR(100) DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS support_conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'open',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(member_id),
        INDEX(updated_at)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS support_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        sender_type VARCHAR(20) NOT NULL,
        sender_id INT DEFAULT NULL,
        sender_name VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(conversation_id),
        INDEX(created_at)
    )");

    // Add email and request metadata columns to password_reset_requests if missing
    try {
        $pdo->exec("ALTER TABLE password_reset_requests 
            ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS requested_by INT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS requested_at DATETIME DEFAULT CURRENT_TIMESTAMP");
    } catch (PDOException $e) {
        try { $pdo->exec("ALTER TABLE password_reset_requests ADD COLUMN email VARCHAR(255) DEFAULT NULL"); } catch (PDOException $__e) {}
        try { $pdo->exec("ALTER TABLE password_reset_requests ADD COLUMN requested_by INT DEFAULT NULL"); } catch (PDOException $__e) {}
        try { $pdo->exec("ALTER TABLE password_reset_requests ADD COLUMN requested_at DATETIME DEFAULT CURRENT_TIMESTAMP"); } catch (PDOException $__e) {}
    }

    // Add auto-login token columns to password_reset_requests if missing
    try {
        // Try to add both columns in one statement (MySQL 8+ supports IF NOT EXISTS per column)
        $pdo->exec("ALTER TABLE password_reset_requests 
            ADD COLUMN IF NOT EXISTS auto_login_token VARCHAR(128) NULL DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS auto_login_expiry DATETIME NULL DEFAULT NULL");
    } catch (PDOException $e) {
        // Fallback for older MySQL versions: add columns individually and ignore errors if they exist
        try { $pdo->exec("ALTER TABLE password_reset_requests ADD COLUMN auto_login_token VARCHAR(128) NULL DEFAULT NULL"); } catch (PDOException $__e) {}
        try { $pdo->exec("ALTER TABLE password_reset_requests ADD COLUMN auto_login_expiry DATETIME NULL DEFAULT NULL"); } catch (PDOException $__e) {}
    }
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

function add_admin_notification(PDO $pdo, string $type, string $title, string $message, string $createdBy = null): bool {
    $stmt = $pdo->prepare("INSERT INTO admin_notifications (type, title, message, created_by) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$type, $title, $message, $createdBy]);
}
