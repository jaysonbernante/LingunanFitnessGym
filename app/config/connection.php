<?php
date_default_timezone_set('Asia/Manila');

// Database connection for dbgym
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
} catch (PDOException $e) {
	throw new PDOException($e->getMessage(), (int)$e->getCode());
}
