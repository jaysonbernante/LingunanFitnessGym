<?php
session_start();
$toast = '';
if (isset($_SESSION['login_error'])) {
  $toast = $_SESSION['login_error'];
  unset($_SESSION['login_error']);
}
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
        <form class="login-form" method="post" action="login.php">
          <h2>Login</h2>
          <div class="form-group">
            <label for="login">Username or Gmail</label>
            <input type="text" id="login" name="login" placeholder="Enter your username or gmail" required>
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
          </div>
          <button type="submit" class="btn-cta">Login</button>
        </form>
      </section>
    </main>

    <?php 
        include "../../component/landingPage-footer.php";
    ?>
    <div id="toast" class="toast" style="display:none;"></div>
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