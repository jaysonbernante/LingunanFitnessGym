<?php
function get_gmail_smtp_config() {
    $envUsername = getenv('GMAIL_SMTP_USERNAME');
    $envPassword = getenv('GMAIL_SMTP_PASSWORD');
    $envFromEmail = getenv('GMAIL_SMTP_FROM_EMAIL');
    $envFromName = getenv('GMAIL_SMTP_FROM_NAME');

    $password = $envPassword !== false ? trim((string) $envPassword) : 'lpzslmrjyxbcbwo';
    $password = preg_replace('/\s+/', '', $password);

    return [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => $envUsername !== false ? trim((string) $envUsername) : 'otpsenderviagmail@gmail.com',
        'password' => $password,
        'from_email' => $envFromEmail !== false ? trim((string) $envFromEmail) : 'otpsenderviagmail@gmail.com',
        'from_name' => $envFromName !== false ? trim((string) $envFromName) : 'Lingunan Fitness Gym',
        'timeout' => 30,
    ];
}

function otp_get_send_limit_state(string $scope, int $maxAttempts = 5, int $windowSeconds = 3600, int $cooldownSeconds = 600): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $key = 'otp_send_limit_' . md5($scope);
    $now = time();
    $state = $_SESSION[$key] ?? ['attempts' => []];

    if (!isset($state['attempts']) || !is_array($state['attempts'])) {
        $state['attempts'] = [];
    }

    $state['attempts'] = array_values(array_filter(
        $state['attempts'],
        static function ($timestamp) use ($now, $windowSeconds): bool {
            return is_numeric($timestamp) && ($now - intval($timestamp) <= $windowSeconds);
        }
    ));

    $attemptCount = count($state['attempts']);
    $allowed = true;
    $retryAt = null;
    $message = '';

    if ($attemptCount >= $maxAttempts) {
        $lastAttemptAt = intval(end($state['attempts']));
        $cooldownUntil = $lastAttemptAt + $cooldownSeconds;
        if ($now < $cooldownUntil) {
            $allowed = false;
            $retryAt = $cooldownUntil;
            $remainingSeconds = max(0, $cooldownUntil - $now);
            $message = 'You have reached the maximum OTP sends. Please wait ' . (int) ceil($remainingSeconds / 60) . ' minute(s) before requesting another code.';
        } else {
            $state['attempts'] = [];
            $attemptCount = 0;
        }
    }

    $_SESSION[$key] = $state;

    return [
        'allowed' => $allowed,
        'message' => $message,
        'attempts' => $attemptCount,
        'remaining' => max(0, $maxAttempts - $attemptCount),
        'retry_at' => $retryAt,
    ];
}

function otp_record_send_attempt(string $scope, int $windowSeconds = 3600): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $key = 'otp_send_limit_' . md5($scope);
    $now = time();
    $state = $_SESSION[$key] ?? ['attempts' => []];

    if (!isset($state['attempts']) || !is_array($state['attempts'])) {
        $state['attempts'] = [];
    }

    $state['attempts'] = array_values(array_filter(
        $state['attempts'],
        static function ($timestamp) use ($now, $windowSeconds): bool {
            return is_numeric($timestamp) && ($now - intval($timestamp) <= $windowSeconds);
        }
    ));

    $state['attempts'][] = $now;
    $_SESSION[$key] = $state;
}

function get_site_login_url() {
    return 'https://lingunanfitnessgym.free.nf';
}

function smtp_get_response($socket) {
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (preg_match('/^[0-9]{3} /', $line)) {
            break;
        }
    }
    return trim($response);
}

function send_gmail_smtp($to, $subject, $htmlBody) {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid recipient email.';
    }

    $config = get_gmail_smtp_config();
    $host = $config['host'];
    $port = $config['port'];
    $username = $config['username'];
    $password = $config['password'];
    $fromEmail = $config['from_email'];
    $fromName = $config['from_name'];
    $timeout = $config['timeout'];
    $eol = "\r\n";

    $socket = fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        return "SMTP connection failed: $errno $errstr";
    }
    stream_set_timeout($socket, $timeout);

    $response = smtp_get_response($socket);
    if (substr($response, 0, 3) !== '220') {
        fclose($socket);
        return 'SMTP handshake failed: ' . $response;
    }

    fwrite($socket, "EHLO localhost{$eol}");
    $response = smtp_get_response($socket);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        return 'EHLO failed: ' . $response;
    }

    fwrite($socket, "STARTTLS{$eol}");
    $response = smtp_get_response($socket);
    if (substr($response, 0, 3) !== '220') {
        fclose($socket);
        return 'STARTTLS failed: ' . $response;
    }

    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        fclose($socket);
        return 'TLS handshake failed.';
    }

    fwrite($socket, "EHLO localhost{$eol}");
    $response = smtp_get_response($socket);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        return 'EHLO after STARTTLS failed: ' . $response;
    }

    fwrite($socket, "AUTH LOGIN{$eol}");
    $response = smtp_get_response($socket);
    if (substr($response, 0, 3) !== '334') {
        fclose($socket);
        return 'AUTH LOGIN failed: ' . $response;
    }

    fwrite($socket, base64_encode($username) . $eol);
    $response = smtp_get_response($socket);
    if (substr($response, 0, 3) !== '334') {
        fclose($socket);
        return 'SMTP username rejected: ' . $response;
    }

    fwrite($socket, base64_encode($password) . $eol);
    $response = smtp_get_response($socket);
    if (substr($response, 0, 3) !== '235') {
        fclose($socket);
        return 'SMTP authentication failed: ' . $response;
    }

    fwrite($socket, "MAIL FROM:<{$fromEmail}>{$eol}");
    $response = smtp_get_response($socket);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        return 'MAIL FROM rejected: ' . $response;
    }

    fwrite($socket, "RCPT TO:<{$to}>{$eol}");
    $response = smtp_get_response($socket);
    if (substr($response, 0, 3) !== '250' && substr($response, 0, 3) !== '251') {
        fclose($socket);
        return 'RCPT TO rejected: ' . $response;
    }

    fwrite($socket, "DATA{$eol}");
    $response = smtp_get_response($socket);
    if (substr($response, 0, 3) !== '354') {
        fclose($socket);
        return 'DATA command rejected: ' . $response;
    }

    $headers  = "From: {$fromName} <{$fromEmail}>{$eol}";
    $headers .= "To: {$to}{$eol}";
    $headers .= "Subject: {$subject}{$eol}";
    $headers .= "MIME-Version: 1.0{$eol}";
    $headers .= "Content-Type: text/html; charset=UTF-8{$eol}";
    $headers .= "Content-Transfer-Encoding: 8bit{$eol}{$eol}";

    $message = $headers . $htmlBody . $eol . ".{$eol}";
    fwrite($socket, $message);
    $response = smtp_get_response($socket);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        return 'Message rejected: ' . $response;
    }

    fwrite($socket, "QUIT{$eol}");
    fclose($socket);

    return true;
}

function send_registration_email($toEmail, $firstName, $lastName, $username, $plainPassword) {
    $loginUrl = get_site_login_url();
    $subject = 'Your Lingunan Fitness Gym account is ready';
    $body = '<html><body>' .
        '<h2>Welcome to Lingunan Fitness Gym</h2>' .
        '<p>Hi ' . htmlspecialchars($firstName . ' ' . $lastName) . ',</p>' .
        '<p>Your account has been created successfully.</p>' .
        '<p><strong>Username:</strong> ' . htmlspecialchars($username) . '<br>' .
        '<strong>Password:</strong> ' . htmlspecialchars($plainPassword) . '</p>' .
        '<p>You can log in using the link below:</p>' .
        '<p><a href="' . htmlspecialchars($loginUrl) . '">' . htmlspecialchars($loginUrl) . '</a></p>' .
        '<p>Please change your password after logging in.</p>' .
        '<p>Thank you,<br>Lingunan Fitness Gym</p>' .
        '</body></html>';

    return send_gmail_smtp($toEmail, $subject, $body);
}
