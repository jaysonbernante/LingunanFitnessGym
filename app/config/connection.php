<?php
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
} catch (PDOException $e) {
	throw new PDOException($e->getMessage(), (int)$e->getCode());
}
