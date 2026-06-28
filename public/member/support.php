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

$successMessage = '';
$errorMessage = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject === '' || $message === '') {
        $errorMessage = 'Please enter both a subject and a message.';
    } else {
        $filePath = __DIR__ . '/../../backups/support_messages.txt';
        $record = sprintf("[%s] Member #%s - %s (%s): %s%s", date('Y-m-d H:i:s'), $member['id'], trim($member['first_name'] . ' ' . $member['last_name']) ?: $member['username'], $member['gmail'] ?: 'no-email', $subject, PHP_EOL);
        $record .= $message . PHP_EOL . str_repeat('-', 80) . PHP_EOL;

        if (@file_put_contents($filePath, $record, FILE_APPEND | LOCK_EX) !== false) {
            $successMessage = 'Your support request has been submitted. Our team will review it soon.';
            $subject = '';
            $message = '';
        } else {
            $errorMessage = 'Unable to save your message. Please contact the gym directly.';
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
  <title>Support</title>
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
          <h1>Support</h1>
          <p class="card-small">Send a message to gym staff about membership, billing, or visits.</p>
        </div>
      </div>

      <?php if ($successMessage): ?>
        <div class="alert success"><?php echo htmlspecialchars($successMessage); ?></div>
      <?php endif; ?>
      <?php if ($errorMessage): ?>
        <div class="alert error"><?php echo htmlspecialchars($errorMessage); ?></div>
      <?php endif; ?>

      <section class="member-form">
        <form method="post" action="support.php">
          <div class="section-card">
            <div class="form-row">
              <div>
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject); ?>" required>
              </div>
            </div>
            <div class="form-row">
              <div style="grid-column:1/-1;">
                <label for="message">Message</label>
                <textarea id="message" name="message" required><?php echo htmlspecialchars($message); ?></textarea>
              </div>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="button">Send Message</button>
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
