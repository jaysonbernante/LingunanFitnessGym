<?php
require 'app/config/connection.php';
require 'app/config/mail.php';
$result = send_gmail_smtp('admin@example.com', 'Test', '<p>test</p>');
var_dump($result);
