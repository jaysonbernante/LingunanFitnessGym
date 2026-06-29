<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'admin';
$_SESSION['user_role'] = 'super_admin';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['profile_update'] = 1;
$_POST['username'] = 'admin';
$_POST['email'] = 'admin@example.com';
$_POST['password'] = '';
$_POST['confirm_password'] = '';

require 'app/config/connection.php';
require 'component/admin_header.php';
