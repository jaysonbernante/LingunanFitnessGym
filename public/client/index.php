<?php
session_start();
require_once '../../app/config/connection.php';
require_once '../../app/config/mail.php';

$toast = '';
if (isset($_SESSION['login_error'])) {
  $toast = $_SESSION['login_error'];
  unset($_SESSION['login_error']);
} elseif (isset($_SESSION['login_success'])) {
  $toast = $_SESSION['login_success'];
  unset($_SESSION['login_success']);
}

$resetMessage = '';
$resetError = '';
$resetStep = 'forgot';
$resetEmail = '';
$resetOtp = '';
$resetBlockKey = 'staff_password_reset_blocks';
$resetBlocks = $_SESSION[$resetBlockKey] ?? [];
if (!is_array($resetBlocks)) {
  $resetBlocks = [];
}
foreach ($resetBlocks as $emailKey => $block) {
  if (is_array($block) && isset($block['blocked_until']) && time() >= intval($block['blocked_until'])) {
    unset($resetBlocks[$emailKey]);
  }
}
$_SESSION[$resetBlockKey] = $resetBlocks;

if (isset($_GET['reset_cancel'])) {
  unset($_SESSION['staff_password_reset']);
}

$pendingReset = $_SESSION['staff_password_reset'] ?? null;
if (is_array($pendingReset) && !empty($pendingReset['otp_hash']) && isset($pendingReset['otp_expiry']) && time() <= intval($pendingReset['otp_expiry'])) {
  $resetStep = 'otp';
  $resetEmail = $pendingReset['email'] ?? '';
} elseif (isset($_SESSION['staff_password_reset'])) {
  unset($_SESSION['staff_password_reset']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['staff_forgot_password_submit'])) {
    $resetEmail = trim($_POST['reset_email'] ?? '');

    if ($resetEmail === '') {
      $resetStep = 'forgot';
      $resetError = 'Please enter your email address.';
    } else {
      $normalizedEmail = strtolower($resetEmail);
      if (isset($resetBlocks[$normalizedEmail]) && is_array($resetBlocks[$normalizedEmail]) && isset($resetBlocks[$normalizedEmail]['blocked_until']) && time() < intval($resetBlocks[$normalizedEmail]['blocked_until'])) {
        $resetStep = 'forgot';
        $resetError = 'This email is temporarily blocked after too many failed attempts. Please try again in 10 minutes.';
      } else {
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ? AND role IN ('super_admin','staff') LIMIT 1");
        $stmt->execute([$resetEmail]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
          $resetStep = 'forgot';
          $resetError = 'No staff account was found with that email.';
        } else {
          unset($resetBlocks[$normalizedEmail]);
          $_SESSION[$resetBlockKey] = $resetBlocks;

          $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
          $otpHash = password_hash($otp, PASSWORD_DEFAULT);
          $_SESSION['staff_password_reset'] = [
            'user_id' => intval($account['id']),
            'email' => $resetEmail,
            'otp_hash' => $otpHash,
            'otp_expiry' => time() + 600,
            'otp_plain' => $otp,
          ];

          $mailBody = '<html><body><h2>Staff password reset</h2><p>Your verification code is:</p><h1 style="font-size:32px;letter-spacing:4px;">' . htmlspecialchars($otp) . '</h1><p>This code expires in 10 minutes.</p><p>Thank you,<br>Lingunan Fitness Gym</p></body></html>';
          $mailResult = send_gmail_smtp($resetEmail, 'Staff password reset verification', $mailBody);

          if ($mailResult === true) {
            $resetStep = 'otp';
            $resetMessage = 'A 6-digit OTP was sent to your email. Enter it below to reset your password.';
          } else {
            $mailError = is_string($mailResult) ? $mailResult : 'Mail delivery failed.';
            error_log('OTP mail failed for ' . $resetEmail . ': ' . $mailError);

            $requestCheck = $pdo->prepare("SELECT id FROM password_reset_requests WHERE user_id = ? AND status = 'pending' LIMIT 1");
            $requestCheck->execute([intval($account['id'])]);
            if (!$requestCheck->fetch()) {
              $pdo->prepare("INSERT INTO password_reset_requests (user_id, username, email, reason, status, requested_by, requested_at) VALUES (?, ?, ?, ?, 'pending', ?, NOW())")
                ->execute([intval($account['id']), $account['username'], $resetEmail, 'Forgot password request', intval($account['id'])]);
            }

            unset($_SESSION['staff_password_reset']);
            $resetStep = 'forgot';
            $resetMessage = 'We could not send the OTP automatically. Your password reset request has been sent to admin for approval.';
            $resetError = '';
          }
        }
      }
    }
  } elseif (isset($_POST['staff_reset_submit'])) {
    $pendingReset = $_SESSION['staff_password_reset'] ?? null;
    $resetOtp = trim($_POST['reset_otp'] ?? '');
    $newPassword = $_POST['reset_password'] ?? '';
    $confirmPassword = $_POST['reset_confirm_password'] ?? '';
    $resetEmail = $pendingReset['email'] ?? $resetEmail;
    $normalizedEmail = strtolower($resetEmail);

    if (isset($resetBlocks[$normalizedEmail]) && is_array($resetBlocks[$normalizedEmail]) && isset($resetBlocks[$normalizedEmail]['blocked_until']) && time() < intval($resetBlocks[$normalizedEmail]['blocked_until'])) {
      unset($_SESSION['staff_password_reset']);
      $resetStep = 'forgot';
      $resetError = 'This email is temporarily blocked after too many failed attempts. Please try again in 10 minutes.';
    } elseif (!$pendingReset || !isset($pendingReset['otp_hash']) || time() > intval($pendingReset['otp_expiry'] ?? 0)) {
      unset($_SESSION['staff_password_reset']);
      $resetError = 'The OTP has expired. Please request a new one.';
      $resetStep = 'forgot';
    } elseif ($resetOtp === '' || $newPassword === '' || $confirmPassword === '') {
      $resetError = 'Please enter the OTP and both password fields.';
      $resetStep = 'otp';
    } elseif ($newPassword !== $confirmPassword) {
      $resetError = 'Passwords do not match.';
      $resetStep = 'otp';
    } elseif (strlen($newPassword) < 6) {
      $resetError = 'Password must be at least 6 characters.';
      $resetStep = 'otp';
    } elseif (!password_verify($resetOtp, $pendingReset['otp_hash'])) {
      $attempts = isset($pendingReset['attempts']) ? intval($pendingReset['attempts']) : 0;
      $attempts += 1;
      $pendingReset['attempts'] = $attempts;
      $_SESSION['staff_password_reset'] = $pendingReset;

      if ($attempts >= 3) {
        $resetBlocks[$normalizedEmail] = [
          'blocked_until' => time() + 600,
          'attempts' => $attempts,
        ];
        $_SESSION[$resetBlockKey] = $resetBlocks;
        unset($_SESSION['staff_password_reset']);
        $resetStep = 'forgot';
        $resetError = 'Too many incorrect OTP attempts. This email is blocked for 10 minutes.';
      } else {
        $remaining = 3 - $attempts;
        $resetError = 'Invalid OTP. Please try again. Attempts remaining: ' . $remaining;
        $resetStep = 'otp';
      }
    } else {
      $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role IN ('super_admin','staff') LIMIT 1");
      $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), intval($pendingReset['user_id'])]);
      unset($_SESSION['staff_password_reset']);
      unset($resetBlocks[$normalizedEmail]);
      $_SESSION[$resetBlockKey] = $resetBlocks;
      $resetStep = 'forgot';
      $resetMessage = 'Password changed successfully. You can now sign in.';
    }
  }
}

$showResetFlow = isset($_GET['forgot']) || $resetStep === 'otp' || isset($_POST['staff_forgot_password_submit']) || isset($_POST['staff_reset_submit']);

if (!isset($_SESSION['user_id']) && isset($_COOKIE['gym_staff_remember'])) {
  $rememberToken = $_COOKIE['gym_staff_remember'];
  $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = :token AND remember_expires_at > NOW() AND role IN ('super_admin','staff') LIMIT 1");
  $stmt->execute(['token' => $rememberToken]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
    header('Location: ' . ($user['role'] === 'staff' ? 'staff/dashboard.php' : 'admin/dashboard.php'));
    exit();
  }

  setcookie('gym_staff_remember', '', time() - 3600, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lingunan Fitness Gym - Login</title>
  <link href="../../assets/css/headerComponent.css" rel="stylesheet">
  <link href="../../assets/css/login.css" rel="stylesheet">
  <link href="../../assets/css/footerComponents.css" rel="stylesheet">
  <link href="../../assets/css/toastednotif.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
   <?php include "../../component/landingPage-header.php"; ?>

    <main class="login-wrapper">
      <section class="login-container">
        <div class="avatar-container">
          <div class="avatar-icon">
            <i class="bi bi-person-fill"></i>
          </div>
        </div>

        <form class="login-form" method="post" action="login.php">
          <h2>LOGIN</h2>

          <div class="form-group">
            <label for="login">Username or Gmail</label>
            <div class="input-with-icon">
              <i class="bi bi-person icon-addon"></i>
              <input type="text" id="login" name="login" placeholder="Enter your username or gmail" required>
            </div>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="input-with-icon">
              <i class="bi bi-lock icon-addon"></i>
              <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
          </div>

          <div class="form-options">
            <label class="remember-me">
              <input type="checkbox" name="remember"> Remember me
            </label>
            <button type="button" class="forgot-pass" id="forgotPasswordBtn">Forgot Password?</button>
          </div>

          <button type="submit" class="btn-cta">Login</button>
        </form>
      </section>
    </main>

    <div class="modal-overlay<?php echo $showResetFlow ? ' active' : ''; ?>" id="forgotModal">
      <div class="modal-card">
        <h3><?php echo $resetStep === 'otp' ? 'Reset Password' : 'Forgot Password?'; ?></h3>
        <p><?php echo $resetStep === 'otp' ? 'Enter the 6-digit code we sent to your email and choose a new password.' : 'Enter your staff email to receive a one-time verification code.'; ?></p>
        <?php if ($resetMessage): ?><div class="alert success"><?php echo htmlspecialchars($resetMessage); ?></div><?php endif; ?>
        <?php if ($resetError): ?><div class="alert error"><?php echo htmlspecialchars($resetError); ?></div><?php endif; ?>
        <?php if ($resetStep === 'otp'): ?>
          <form method="post" action="index.php">
            <input type="hidden" name="staff_reset_submit" value="1">
            <label for="reset_otp">OTP Code</label>
            <input type="text" id="reset_otp" name="reset_otp" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="Enter 6-digit code" required>
            <label for="reset_password">New Password</label>
            <input type="password" id="reset_password" name="reset_password" placeholder="Enter new password" required>
            <label for="reset_confirm_password">Confirm Password</label>
            <input type="password" id="reset_confirm_password" name="reset_confirm_password" placeholder="Confirm new password" required>
            <div class="modal-actions">
              <button type="submit" class="btn-cta btn-small">Save Password</button>
              <a href="index.php?reset_cancel=1" class="btn-cancel" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Back to login</a>
            </div>
          </form>
        <?php else: ?>
          <form method="post" action="index.php">
            <input type="hidden" name="staff_forgot_password_submit" value="1">
            <label for="reset_email">Email Address</label>
            <input type="email" id="reset_email" name="reset_email" value="<?php echo htmlspecialchars($resetEmail); ?>" placeholder="Enter your email" required>
            <div class="modal-actions">
              <button type="submit" class="btn-cta btn-small">Send OTP</button>
              <button type="button" class="btn-cancel" id="closeForgotModal">Cancel</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <?php include "../../component/landingPage-footer.php"; ?>

    <div id="toast" class="toast" style="display:none;"></div>
    <script src="../../assets/js/landing-page.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var toastMsg = <?php echo json_encode($toast); ?>;
        if (toastMsg) {
          var toast = document.getElementById('toast');
          toast.textContent = toastMsg;
          toast.style.display = 'flex';
          setTimeout(function() {
            toast.classList.add('show');
          }, 100);
          setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() { toast.style.display = 'none'; }, 300);
          }, 3500);
        }

        var forgotBtn = document.getElementById('forgotPasswordBtn');
        var forgotModal = document.getElementById('forgotModal');
        var closeForgotModal = document.getElementById('closeForgotModal');

        if (forgotBtn && forgotModal) {
          forgotBtn.addEventListener('click', function() {
            forgotModal.classList.add('active');
          });
        }

        if (closeForgotModal && forgotModal) {
          closeForgotModal.addEventListener('click', function() {
            forgotModal.classList.remove('active');
          });
        }

        if (forgotModal) {
          forgotModal.addEventListener('click', function(e) {
            if (e.target === this) {
              this.classList.remove('active');
            }
          });
        }
      });
    </script>
</body>
</html>