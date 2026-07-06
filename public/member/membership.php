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

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$fullName = trim($member['first_name'] . ' ' . $member['last_name']);
$membershipExpiry = $member['membership_expiry'] ?: $member['membership_end'];
$membershipStart = $member['membership_start'] ?: $member['Joined_Date'];
$status = 'No active membership';
$daysLeft = null;
if ($membershipExpiry) {
    $expiryDate = new DateTime($membershipExpiry);
    $today = new DateTime('today');
    $interval = $today->diff($expiryDate);
    $daysLeft = (int)$interval->format('%r%a');
    $status = $expiryDate >= $today ? 'Active' : 'Expired';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Membership Details</title>
  <link href="../../assets/css/headerComponent.css" rel="stylesheet">
  <link href="../../assets/css/footerComponents.css" rel="stylesheet">
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
      <a class="member-logout" href="logout.php" id="memberLogoutTrigger">Logout</a>
    </aside>

    <main class="member-main">
      <div class="page-heading">
        <div>
          <h1>Membership Details</h1>
          <p class="card-small">View your plan, renewal dates, and membership status.</p>
        </div>
      </div>

      <div class="member-grid">
        <div class="card">
          <div class="card-label">Current Plan</div>
          <div class="card-strong"><?php echo htmlspecialchars($member['type'] === 'member' ? 'Member' : 'Guest'); ?></div>
          <div class="card-small">Plan months: <?php echo $member['plan_months'] ? intval($member['plan_months']) . ' month(s)' : 'Not available'; ?></div>
        </div>
        <div class="card">
          <div class="card-label">Membership status</div>
          <div class="card-strong"><?php echo htmlspecialchars($status); ?></div>
          <?php if ($membershipExpiry): ?>
            <div class="card-small">Expires on <?php echo date('F j, Y', strtotime($membershipExpiry)); ?></div>
            <?php if ($status === 'Active'): ?>
              <div class="card-small"><?php echo $daysLeft >= 0 ? $daysLeft . ' day(s) remaining' : 'Expiring soon'; ?></div>
            <?php endif; ?>
          <?php else: ?>
            <div class="card-small">No membership expiry recorded.</div>
          <?php endif; ?>
        </div>
        <div class="card">
          <div class="card-label">Joined Date</div>
          <div class="card-strong"><?php echo date('F j, Y', strtotime($membershipStart)); ?></div>
          <div class="card-small">Account created on this date.</div>
        </div>
      </div>

      <section class="section-card card">
        <h2>Membership Summary</h2>
        <ul class="card-list">
          <li class="card-list-item"><span>Member ID</span><strong><?php echo htmlspecialchars($member['id']); ?></strong></li>
          <li class="card-list-item"><span>Membership type</span><strong><?php echo htmlspecialchars($member['type']); ?></strong></li>
          <li class="card-list-item"><span>RFID</span><strong><?php echo htmlspecialchars($member['RFID'] ?: 'Not assigned'); ?></strong></li>
          <li class="card-list-item"><span>Membership expiry</span><strong><?php echo $membershipExpiry ? date('F j, Y', strtotime($membershipExpiry)) : 'Not set'; ?></strong></li>
          <li class="card-list-item"><span>Membership credit</span><strong>₱<?php echo number_format($member['credit'] ?? 0, 2); ?></strong></li>
        </ul>
      </section>

      <section class="section-card card">
        <h2>Renewal & Support</h2>
        <p class="card-small">To renew or change your membership plan, contact gym staff using the support page or visit the gym in person.</p>
        <a href="support.php" class="button">Contact Support</a>
      </section>
    </main>
  </div>
  <script src="../../assets/js/member-sidebar-toggle.js"></script>
  <?php include "../../component/landingPage-footer.php"; ?>
  <?php include "../../component/member_logout_modal.php"; ?>
</body>
</html>
