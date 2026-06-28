<?php
session_start();
require_once '../../app/config/connection.php';

$toast = '';
if (isset($_SESSION['login_error'])) {
  $toast = $_SESSION['login_error'];
  unset($_SESSION['login_error']);
} elseif (isset($_SESSION['login_success'])) {
  $toast = $_SESSION['login_success'];
  unset($_SESSION['login_success']);
}

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

    <div class="modal-overlay" id="forgotModal">
      <div class="modal-card">
        <h3>Forgot Password?</h3>
        <p>Enter your staff username and a short reason. An admin will review the request and unlock your account.</p>
        <form method="post" action="login.php">
          <input type="hidden" name="forgot_password" value="1">
          <label for="forgot_username">Username</label>
          <input type="text" id="forgot_username" name="forgot_username" placeholder="Enter username" required>
          <label for="forgot_reason">Reason</label>
          <textarea id="forgot_reason" name="forgot_reason" rows="3" placeholder="Why do you need help?" required></textarea>
          <div class="modal-actions">
            <button type="submit" class="btn-cta btn-small">Send Request</button>
            <button type="button" class="btn-cancel" id="closeForgotModal">Cancel</button>
          </div>
        </form>
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