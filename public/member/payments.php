<?php
session_start();
require_once 'session_check.php';
require_once '../../app/config/connection.php';

$memberId = $_SESSION['member_id'];
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ? AND type = 'member' LIMIT 1");
$stmt->execute([$memberId]);
$member = $stmt->fetch();
if (!$member) {
    session_destroy();
    header('Location: index.php');
    exit();
}

$memberName = trim($member['first_name'] . ' ' . $member['last_name']);
$paymentStmt = $pdo->prepare("SELECT product_name, qty_sold, total, payment_method, sold_at FROM sales WHERE member_name IN (?, ?) ORDER BY sold_at DESC LIMIT 10");
$paymentStmt->execute([$memberName, $member['username']]);
$salesRecords = $paymentStmt->fetchAll();

$chargeStmt = $pdo->prepare("SELECT entry_type, amount_charged, payment_method, entry_time FROM entry_logs WHERE member_id = ? AND amount_charged > 0 ORDER BY entry_time DESC LIMIT 10");
$chargeStmt->execute([$memberId]);
$chargeRecords = $chargeStmt->fetchAll();

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$fullName = trim($member['first_name'] . ' ' . $member['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payments</title>
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
          <h1>Payments</h1>
          <p class="card-small">Track your wallet balance, recent session charges, and purchases.</p>
        </div>
      </div>

      <div class="member-grid">
        <div class="card">
          <div class="card-label">Current Wallet Credit</div>
          <div class="card-strong">₱<?php echo number_format($member['credit'] ?? 0, 2); ?></div>
          <div class="card-small">Use this credit for sessions and services.</div>
        </div>
        <div class="card">
          <div class="card-label">Membership Expiry</div>
          <div class="card-strong"><?php echo $member['membership_expiry'] ? date('F j, Y', strtotime($member['membership_expiry'])) : 'Not set'; ?></div>
          <div class="card-small">If your plan expires, contact support to renew.</div>
        </div>
      </div>

      <section class="card section-card">
        <h2>Entry Charges</h2>
        <?php if (empty($chargeRecords)): ?>
          <div class="empty-state">No entry charges have been recorded yet.</div>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="member-table">
              <thead>
                <tr>
                  <th>Date & Time</th>
                  <th>Type</th>
                  <th>Amount</th>
                  <th>Payment Method</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($chargeRecords as $record): ?>
                  <tr>
                    <td><?php echo date('F j, Y g:i A', strtotime($record['entry_time'])); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($record['entry_type'])); ?></td>
                    <td><?php echo '₱' . number_format($record['amount_charged'], 2); ?></td>
                    <td><?php echo htmlspecialchars($record['payment_method'] ?: '-'); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <section class="card section-card">
        <h2>Product Purchases</h2>
        <?php if (empty($salesRecords)): ?>
          <div class="empty-state">No product purchase history is available.</div>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="member-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Item</th>
                  <th>Qty</th>
                  <th>Total</th>
                  <th>Payment</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($salesRecords as $sale): ?>
                  <tr>
                    <td><?php echo date('F j, Y', strtotime($sale['sold_at'])); ?></td>
                    <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                    <td><?php echo intval($sale['qty_sold']); ?></td>
                    <td><?php echo '₱' . number_format($sale['total'], 2); ?></td>
                    <td><?php echo htmlspecialchars($sale['payment_method'] ?: '-'); ?></td>
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
