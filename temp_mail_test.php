<?php
require 'app/config/mail.php';
$result = send_gmail_smtp('otpsenderviagmail@gmail.com', 'SMTP Test', '<p>SMTP test</p>');
var_dump($result);
