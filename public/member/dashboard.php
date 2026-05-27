<?php
session_start();
$toast = '';
if (isset($_SESSION['login_success'])) {
  $toast = $_SESSION['login_success'];
  unset($_SESSION['login_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link href="../../assets/css/toastednotif.css" rel="stylesheet">
</head>
<body>
  <h1>Welcome to the Dashboard</h1>
  <div id="toast" class="toast" style="display:none;"></div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var toastMsg = <?php echo json_encode($toast); ?>;
      if (toastMsg) {
        var toast = document.getElementById('toast');
        toast.textContent = toastMsg;
        toast.classList.add('success');
        toast.style.display = 'flex';
        setTimeout(function() {
          toast.classList.add('show');
        }, 100);
        setTimeout(function() {
          toast.classList.remove('show');
          setTimeout(function() { toast.style.display = 'none'; toast.classList.remove('success'); }, 300);
        }, 3500);
      }
    });
  </script>
</body>
</html>
