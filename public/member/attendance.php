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

$entryStmt = $pdo->prepare("SELECT * FROM entry_logs WHERE member_id = ? ORDER BY entry_time DESC LIMIT 20");
$entryStmt->execute([$memberId]);
$entries = $entryStmt->fetchAll();

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$fullName = trim($member['first_name'] . ' ' . $member['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance History</title>
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
      <a class="member-logout" href="logout.php">Logout</a>
    </aside>

    <main class="member-main">
      <div class="page-heading">
        <div>
          <h1>Attendance History</h1>
          <p class="card-small">Review your gym visit history and recent entry records.</p>
        </div>
      </div>

      <section class="card section-card">
        <?php if (empty($entries)): ?>
          <div class="empty-state">No attendance records have been found yet.</div>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="member-table">
              <thead>
                <tr>
                  <th>Date & Time</th>
                  <th>Entry Type</th>
                  <th>Charge</th>
                  <th>Payment Method</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($entries as $entry): ?>
                  <tr>
                    <td><?php echo date('F j, Y g:i A', strtotime($entry['entry_time'])); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($entry['entry_type'])); ?></td>
                    <td><?php echo '₱' . number_format($entry['amount_charged'] ?? 0, 2); ?></td>
                    <td><?php echo htmlspecialchars($entry['payment_method'] ?: '-'); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
  <script src="../../assets/js/member-sidebar-toggle.js"></script>
  <?php include "../../component/landingPage-footer.php"; ?>
</body>
</html>
