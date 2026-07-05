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
$status = 'No active membership';
$daysLeft = null;
if ($membershipExpiry) {
    $expiryDate = new DateTime($membershipExpiry);
    $today = new DateTime('today');
    $interval = $today->diff($expiryDate);
    $daysLeft = (int)$interval->format('%r%a');
    $status = $expiryDate >= $today ? 'Active' : 'Expired';
}

$countEntriesStmt = $pdo->prepare("SELECT COUNT(*) FROM entry_logs WHERE member_id = ?");
$countEntriesStmt->execute([$memberId]);
$totalVisits = intval($countEntriesStmt->fetchColumn());

$countSalesStmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE member_name IN (?, ?)");
$countSalesStmt->execute([trim($member['first_name'] . ' ' . $member['last_name']), $member['username']]);
$totalPurchases = intval($countSalesStmt->fetchColumn());

$recentEntryStmt = $pdo->prepare("SELECT * FROM entry_logs WHERE member_id = ? ORDER BY entry_time DESC LIMIT 5");
$recentEntryStmt->execute([$memberId]);
$recentEntries = $recentEntryStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Member Dashboard</title>
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
          <h1>Welcome back, <?php echo htmlspecialchars($member['first_name'] ?: $member['username']); ?>!</h1>
          <p class="card-small">See your latest membership information, visits, and payment activity.</p>
        </div>
      </div>

      <div class="member-grid">
        <div class="card">
          <div class="card-label">Membership Status</div>
          <div class="card-strong"><?php echo htmlspecialchars($status); ?></div>
          <?php if ($membershipExpiry): ?>
            <div class="card-small">Expires on <?php echo date('F j, Y', strtotime($membershipExpiry)); ?></div>
            <?php if ($status === 'Active'): ?><div class="card-small"><?php echo max(0, $daysLeft) . ' day(s) left'; ?></div><?php endif; ?>
          <?php else: ?>
            <div class="card-small">No plan expiry recorded.</div>
          <?php endif; ?>
        </div>
        <div class="card">
          <div class="card-label">Visits This Account</div>
          <div class="card-strong"><?php echo $totalVisits; ?></div>
          <div class="card-small">Track your gym attendance over time.</div>
        </div>
        <div class="card">
          <div class="card-label">Recent Purchases</div>
          <div class="card-strong"><?php echo $totalPurchases; ?></div>
          <div class="card-small">Products and services charged to your account.</div>
        </div>
      </div>

      <section class="section-card card">
        <h2>Quick Actions</h2>
        <ul class="card-list">
          <li class="card-list-item"><span>Update profile details</span><a class="button small" href="profile.php">Edit Profile</a></li>
          <li class="card-list-item"><span>Review membership plan</span><a class="button secondary small" href="membership.php">View Membership</a></li>
          <li class="card-list-item"><span>See attendance history</span><a class="button secondary small" href="attendance.php">View Attendance</a></li>
          <li class="card-list-item"><span>Open support ticket</span><a class="button small" href="support.php">Contact Support</a></li>
        </ul>
      </section>

      <section class="section-card card">
        <h2>Recent Visits</h2>
        <?php if (empty($recentEntries)): ?>
          <div class="empty-state">No recent visit records are available yet.</div>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="member-table">
              <thead>
                <tr>
                  <th>Date & Time</th>
                  <th>Type</th>
                  <th>Charge</th>
                  <th>Payment</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentEntries as $entry): ?>
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
