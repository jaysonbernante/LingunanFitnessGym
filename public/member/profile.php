<?php
session_start();
require_once 'session_check.php';
require_once '../../app/config/connection.php';

$memberId = $_SESSION['member_id'];
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ? AND type IN ('member', 'session') LIMIT 1");
$stmt->execute([$memberId]);
$member = $stmt->fetch();
if (!$member) {
    session_destroy();
    header('Location: index.php');
    exit();
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gmail = trim($_POST['gmail'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($first_name === '' || $last_name === '') {
        $errorMessage = 'Please enter your first and last name.';
    } else {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE members SET first_name = ?, last_name = ?, phone = ?, address = ?, gmail = ?, password = ? WHERE id = ?";
            $params = [$first_name, $last_name, $phone, $address, $gmail, $hash, $memberId];
        } else {
            $sql = "UPDATE members SET first_name = ?, last_name = ?, phone = ?, address = ?, gmail = ? WHERE id = ?";
            $params = [$first_name, $last_name, $phone, $address, $gmail, $memberId];
        }

        $updateStmt = $pdo->prepare($sql);
        if ($updateStmt->execute($params)) {
            $successMessage = 'Your profile has been updated successfully.';
            $stmt->execute([$memberId]);
            $member = $stmt->fetch();
        } else {
            $errorMessage = 'Unable to update your profile. Please try again later.';
        }
    }
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$fullName = trim($member['first_name'] . ' ' . $member['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Member Profile</title>
  <link href="../../assets/css/headerComponent.css" rel="stylesheet">
  <link href="../../assets/css/footerComponents.css" rel="stylesheet">
  <link href="../../assets/css/toastednotif.css" rel="stylesheet">
  <link href="../../assets/css/member.css" rel="stylesheet">
</head>
<body>
 
  <div class="member-layout member-page">
    <div class="member-sidebar-backdrop" id="memberSidebarBackdrop"></div>
    <button class="member-sidebar-toggle" id="memberSidebarToggle" aria-label="Open sidebar" type="button"><span></span></button>
    <aside class="member-sidebar">
      <div class="brand">Lingunan Gym</div>
      <div class="profile-card">
        <div class="name"><?php echo htmlspecialchars($fullName ?: $member['username']); ?></div>
        <div class="type">Member Account</div>
      </div>
      <nav class="member-menu">
        <a href="dashboard.php" class="member-menu-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
        <a href="profile.php" class="member-menu-item <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">Profile</a>
        <a href="membership.php" class="member-menu-item <?php echo $currentPage === 'membership' ? 'active' : ''; ?>">Membership</a>
        <a href="attendance.php" class="member-menu-item <?php echo $currentPage === 'attendance' ? 'active' : ''; ?>">Attendance</a>
        <a href="payments.php" class="member-menu-item <?php echo $currentPage === 'payments' ? 'active' : ''; ?>">Payments</a>
        <a href="support.php" class="member-menu-item <?php echo $currentPage === 'support' ? 'active' : ''; ?>">Support</a>
      </nav>
      <a class="member-logout" href="logout.php">Logout</a>
    </aside>

    <main class="member-main">
      <div class="page-heading">
        <div>
          <h1>My Profile</h1>
          <p class="card-small">Manage your account details and update contact information.</p>
        </div>
      </div>

      <?php if ($successMessage): ?>
        <div class="alert success"><?php echo htmlspecialchars($successMessage); ?></div>
      <?php endif; ?>
      <?php if ($errorMessage): ?>
        <div class="alert error"><?php echo htmlspecialchars($errorMessage); ?></div>
      <?php endif; ?>

      <section class="member-form">
        <form method="post" action="profile.php">
          <div class="section-card">
            <h2>Account Information</h2>
            <div class="form-row">
              <div>
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($member['first_name']); ?>" required>
              </div>
              <div>
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($member['last_name']); ?>" required>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label for="username">Username</label>
                <input type="text" id="username" value="<?php echo htmlspecialchars($member['username']); ?>" readonly>
              </div>
              <div>
                <label for="gmail">Email</label>
                <input type="email" id="gmail" name="gmail" value="<?php echo htmlspecialchars($member['gmail']); ?>">
              </div>
            </div>
            <div class="form-row">
              <div>
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>">
              </div>
              <div>
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($member['address']); ?>">
              </div>
            </div>
            <div class="form-row">
              <div style="grid-column:1/-1;">
                <label for="password">New Password <span style="font-size:0.9rem;color:#6b7280;">(leave blank to keep current password)</span></label>
                <input type="password" id="password" name="password" placeholder="Enter a new password if you want to change it">
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="button">Save Changes</button>
            <a href="dashboard.php" class="button secondary small">Back to Dashboard</a>
          </div>
        </form>
      </section>
    </main>
  </div>
  <script src="../../assets/js/member-sidebar-toggle.js"></script>
  <?php include "../../component/landingPage-footer.php"; ?>
</body>
</html>
