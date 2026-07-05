<?php
session_start();
if (isset($_SESSION['member_id'])) {
  header('Location: dashboard.php');
  exit();
}
require_once '../../app/config/connection.php';
require_once '../../app/config/mail.php';

$toast = '';
if (isset($_SESSION['login_error'])) {
  $toast = $_SESSION['login_error'];
  unset($_SESSION['login_error']);
}

$resetMessage = '';
$resetError = '';
$resetStep = 'login';
$resetEmail = '';
$resetOtp = '';

if (isset($_GET['reset_cancel'])) {
  unset($_SESSION['member_password_reset']);
}

$pendingReset = $_SESSION['member_password_reset'] ?? null;
if (is_array($pendingReset) && !empty($pendingReset['otp_hash']) && isset($pendingReset['otp_expiry']) && time() <= intval($pendingReset['otp_expiry'])) {
  $resetStep = 'otp';
  $resetEmail = $pendingReset['email'] ?? '';
} elseif (isset($_SESSION['member_password_reset'])) {
  unset($_SESSION['member_password_reset']);
}

$resetBlockKey = 'member_password_reset_blocks';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['forgot_password_submit'])) {
    $resetEmail = trim($_POST['reset_email'] ?? '');
    if ($resetEmail === '') {
      $resetStep = 'forgot';
      $resetError = 'Please enter your email address.';
    } else {
      $stmt = $pdo->prepare("SELECT id, gmail, first_name, last_name, username FROM members WHERE gmail = ? AND type IN ('member', 'session') LIMIT 1");
      $stmt->execute([$resetEmail]);
      $member = $stmt->fetch();

      if (!$member) {
        $resetStep = 'forgot';
        $resetError = 'No member account was found with that email.';
      } else {
        $normalizedEmail = strtolower($resetEmail);
        if (isset($resetBlocks[$normalizedEmail]) && is_array($resetBlocks[$normalizedEmail]) && isset($resetBlocks[$normalizedEmail]['blocked_until']) && time() < intval($resetBlocks[$normalizedEmail]['blocked_until'])) {
          $resetStep = 'forgot';
          $resetError = 'This email is temporarily blocked after too many failed attempts. Please try again in 10 minutes.';
        } else {
          unset($resetBlocks[$normalizedEmail]);
          $_SESSION[$resetBlockKey] = $resetBlocks;

          $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);
        $_SESSION['member_password_reset'] = [
          'member_id' => intval($member['id']),
          'email' => $resetEmail,
          'otp_hash' => $otpHash,
          'otp_expiry' => time() + 600,
        ];

        $mailBody = '<html><body><h2>Member password reset</h2><p>Your verification code is:</p><h1 style="font-size:32px;letter-spacing:4px;">' . htmlspecialchars($otp) . '</h1><p>This code expires in 10 minutes.</p><p>Thank you,<br>Lingunan Fitness Gym</p></body></html>';
        $mailResult = send_gmail_smtp($resetEmail, 'Member password reset verification', $mailBody);

          if ($mailResult === true) {
            $resetStep = 'otp';
            $resetMessage = 'A 6-digit OTP was sent to your email. Enter it below to reset your password.';
          } else {
            unset($_SESSION['member_password_reset']);
            $resetError = 'Unable to send the OTP right now. Please try again.';
          }
        }
      }
    }
  } elseif (isset($_POST['member_reset_submit'])) {
    $pendingReset = $_SESSION['member_password_reset'] ?? null;
    $resetOtp = trim($_POST['reset_otp'] ?? '');
    $newPassword = $_POST['reset_password'] ?? '';
    $confirmPassword = $_POST['reset_confirm_password'] ?? '';

    $resetEmail = $pendingReset['email'] ?? $resetEmail;
    $normalizedEmail = strtolower($resetEmail);
    if (isset($resetBlocks[$normalizedEmail]) && is_array($resetBlocks[$normalizedEmail]) && isset($resetBlocks[$normalizedEmail]['blocked_until']) && time() < intval($resetBlocks[$normalizedEmail]['blocked_until'])) {
      unset($_SESSION['member_password_reset']);
      $resetStep = 'forgot';
      $resetError = 'This email is temporarily blocked after too many failed attempts. Please try again in 10 minutes.';
    } elseif (!$pendingReset || !isset($pendingReset['otp_hash']) || time() > intval($pendingReset['otp_expiry'] ?? 0)) {
      unset($_SESSION['member_password_reset']);
      $resetError = 'The OTP has expired. Please request a new one.';
      $resetStep = 'login';
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
      $_SESSION['member_password_reset'] = $pendingReset;

      if ($attempts >= 3) {
        $resetBlocks[$normalizedEmail] = [
          'blocked_until' => time() + 600,
          'attempts' => $attempts,
        ];
        $_SESSION[$resetBlockKey] = $resetBlocks;
        unset($_SESSION['member_password_reset']);
        $resetStep = 'forgot';
        $resetError = 'Too many incorrect OTP attempts. This email is blocked for 10 minutes.';
      } else {
        $remaining = 3 - $attempts;
        $resetError = 'Invalid OTP. Please try again. Attempts remaining: ' . $remaining;
        $resetStep = 'otp';
      }
    } else {
      $stmt = $pdo->prepare("UPDATE members SET password = ? WHERE id = ? AND type IN ('member', 'session') LIMIT 1");
      $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), intval($pendingReset['member_id'])]);
      unset($_SESSION['member_password_reset']);
      unset($resetBlocks[$normalizedEmail]);
      $_SESSION[$resetBlockKey] = $resetBlocks;
      $resetStep = 'login';
      $resetMessage = 'Password changed successfully. You can now sign in.';
    }
  }
}

$showResetFlow = (isset($_GET['forgot']) || $resetStep === 'otp' || isset($_POST['forgot_password_submit']) || isset($_POST['member_reset_submit']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Lingunan Fitness Gym</title>
      <link href="../../assets/css/headerComponent.css" rel="stylesheet">
<link href="../../assets/css/login.css" rel="stylesheet">
<link href="../../assets/css/footerComponents.css" rel="stylesheet">
<link href="../../assets/css/toastednotif.css" rel="stylesheet">
</head>

<body>
   <?php
    include "../../component/landingPage-header.php"
    ?>
    <main>
      <section class="login-section">
        <div class="login-card">
          <?php if ($showResetFlow): ?>
            <form class="login-form" method="post" action="index.php">
              <h2><?php echo $resetStep === 'otp' ? 'Reset Password' : 'Forgot Password'; ?></h2>
              <p class="login-subtitle">
                <?php echo $resetStep === 'otp' ? 'Enter the 6-digit code we sent to your email and choose a new password.' : 'Enter your registered email to receive a one-time verification code.'; ?>
              </p>
              <?php if ($resetMessage): ?><div class="alert success"><?php echo htmlspecialchars($resetMessage); ?></div><?php endif; ?>
              <?php if ($resetError): ?><div class="alert error"><?php echo htmlspecialchars($resetError); ?></div><?php endif; ?>
              <?php if ($resetStep === 'otp'): ?>
                <input type="hidden" name="member_reset_submit" value="1">
                <div class="form-group">
                  <label for="reset_otp">OTP Code</label>
                  <input type="text" id="reset_otp" name="reset_otp" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="Enter 6-digit code" required>
                </div>
                <div class="form-group">
                  <label for="reset_password">New Password</label>
                  <input type="password" id="reset_password" name="reset_password" placeholder="Enter new password" required>
                </div>
                <div class="form-group">
                  <label for="reset_confirm_password">Confirm Password</label>
                  <input type="password" id="reset_confirm_password" name="reset_confirm_password" placeholder="Confirm new password" required>
                </div>
                <button type="submit" class="btn-cta">Save Password</button>
              <?php else: ?>
                <input type="hidden" name="forgot_password_submit" value="1">
                <div class="form-group">
                  <label for="reset_email">Email Address</label>
                  <input type="email" id="reset_email" name="reset_email" value="<?php echo htmlspecialchars($resetEmail); ?>" placeholder="Enter your gmail" required>
                </div>
                <button type="submit" class="btn-cta">Send OTP</button>
              <?php endif; ?>
              <a href="index.php?reset_cancel=1" class="forgot-link">Back to login</a>
            </form>
          <?php else: ?>
            <form class="login-form" method="post" action="login.php">
              <h2>Member Login</h2>
              <p class="login-subtitle">Welcome back! Sign in to your account.</p>
              <div class="form-group">
                <label for="login">Username or Gmail</label>
                <input type="text" id="login" name="login" placeholder="Enter your username or gmail" required>
              </div>
              <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
              </div>
              <button type="submit" class="btn-cta">Login</button>
              <a href="index.php?forgot=1" class="forgot-link">Forgot password?</a>
            </form>
          <?php endif; ?>
        </div>
      </section>
    </main>

    <?php 
        include "../../component/landingPage-footer.php";
    ?>
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
      });
    </script>

</body>
</html>