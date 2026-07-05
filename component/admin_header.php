<?php
// component/admin_header.php
if (!isset($pdo)) {
    require_once __DIR__ . '/../app/config/connection.php';
}
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/../app/config/mail.php';

$_currentUserRole = $_SESSION['user_role'] ?? 'super_admin';
$adminNotificationTypeRestriction = $_currentUserRole === 'staff'
    ? " AND type IN ('member','wallet','ecommerce','support')"
    : '';

// AJAX: fetch unread notifications (JSON)
if (isset($_GET['ajax_fetch_notifications'])) {
    header('Content-Type: application/json');
    try {
        $_ajaxNotifications = [];
        
        // New members today
        try {
            $rows = $pdo->query("SELECT first_name, last_name FROM members WHERE DATE(Joined_Date)=CURDATE() ORDER BY id DESC LIMIT 5")->fetchAll();
            foreach ($rows as $r) {
                $_ajaxNotifications[] = ['icon'=>'&#127381;','color'=>'#1976d2','title'=>htmlspecialchars(trim($r['first_name'].' '.$r['last_name'])).' joined','sub'=>'New member registered today','type'=>'member'];
            }
        } catch(Exception $e) {}
        
        // Expiring memberships within 7 days
        try {
            $rows = $pdo->query("SELECT first_name, last_name, membership_expiry FROM members WHERE type='member' AND membership_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY membership_expiry ASC LIMIT 5")->fetchAll();
            foreach ($rows as $r) {
                $days = (int)((strtotime($r['membership_expiry']) - strtotime('today')) / 86400);
                $when = $days === 0 ? 'today' : "in $days day".($days>1?'s':'');
                $_ajaxNotifications[] = ['icon'=>'&#9203;','color'=>'#f57c00','title'=>htmlspecialchars(trim($r['first_name'].' '.$r['last_name'])),'sub'=>'Membership expires '.$when,'type'=>'member'];
            }
        } catch(Exception $e) {}
        
        // Low stock
        try {
            $rows = $pdo->query("SELECT product_name, quantity FROM products WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 5")->fetchAll();
            foreach ($rows as $r) {
                $_ajaxNotifications[] = ['icon'=>'&#128230;','color'=>'#e53935','title'=>htmlspecialchars($r['product_name']),'sub'=>'Only '.$r['quantity'].' left in stock','type'=>'ecommerce'];
            }
        } catch(Exception $e) {}
        
        // Dynamic admin actions
        try {
            $rows = $pdo->query("SELECT id, type, title, message, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM admin_notifications WHERE is_read = 0" . $adminNotificationTypeRestriction . " ORDER BY created_at DESC LIMIT 10")->fetchAll();
            foreach ($rows as $r) {
                $type = $r['type'] ?? 'system';
                $icon = '&#128276;';
                $color = '#f5c518';
                if ($type === 'wallet') { $icon = '&#128176;'; $color = '#388e3c'; }
                elseif ($type === 'ecommerce') { $icon = '&#128230;'; $color = '#e53935'; }
                elseif ($type === 'staff') { $icon = '&#128100;'; $color = '#1976d2'; }
                elseif ($type === 'backup') { $icon = '&#128190;'; $color = '#1976d2'; }
                $sub = htmlspecialchars($r['message']);
                if (!empty($r['created_at'])) $sub .= ' · ' . date('M j H:i', strtotime($r['created_at']));
                $_ajaxNotifications[] = ['id' => intval($r['id']), 'icon'=>$icon,'color'=>$color,'title'=>htmlspecialchars($r['title']),'sub'=>$sub,'type'=>$type];
            }
        } catch(Exception $e) {}
        
        if ($_currentUserRole !== 'staff') {
            // Password reset requests
            try {
                $rows = $pdo->query("SELECT username, COUNT(*) as count FROM password_reset_requests WHERE status = 'pending' GROUP BY username ORDER BY created_at DESC LIMIT 5")->fetchAll();
                foreach ($rows as $r) {
                    $_ajaxNotifications[] = ['icon'=>'&#128274;','color'=>'#d32f2f','title'=>htmlspecialchars($r['username']).' - password reset','sub'=>'Pending approval','type'=>'staff'];
                }
            } catch(Exception $e) {}
        }
        
        // Wallet transactions (cash-in, refund, correction)
        try {
            $rows = $pdo->query("SELECT username, transaction_type, amount FROM transactions WHERE transaction_type IN ('cash_in', 'refund', 'correction') ORDER BY created_at DESC LIMIT 5")->fetchAll();
            foreach ($rows as $r) {
                $typeLabel = $r['transaction_type'] === 'cash_in' ? 'Cash In' : ($r['transaction_type'] === 'refund' ? 'Refund' : 'Correction');
                $_ajaxNotifications[] = ['icon'=>'&#128176;','color'=>'#388e3c','title'=>htmlspecialchars($r['username']),'sub'=>$typeLabel.' - ₱'.number_format($r['amount'], 2),'type'=>'wallet'];
            }
        } catch(Exception $e) {}
        
        // Backup access attempts
        if ($_currentUserRole !== 'staff') {
            try {
                $rows = $pdo->query("SELECT username, accessed_at FROM backup_logs ORDER BY accessed_at DESC LIMIT 3")->fetchAll();
                foreach ($rows as $r) {
                    $_ajaxNotifications[] = ['icon'=>'&#128190;','color'=>'#1976d2','title'=>htmlspecialchars($r['username']).' accessed backup','sub'=>'Backup system accessed','type'=>'backup'];
                }
            } catch(Exception $e) {}
        }
        
        echo json_encode(['count' => count($_ajaxNotifications), 'items' => $_ajaxNotifications]);
    } catch (Exception $e) {
        echo json_encode(['count' => 0, 'items' => [], 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: mark a notification read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_mark_notification'])) {
    header('Content-Type: application/json');
    $nid = intval($_POST['id'] ?? 0);
    if ($nid > 0) {
        try {
            $updateStmt = $pdo->prepare(
                $_currentUserRole === 'staff'
                    ? "UPDATE admin_notifications SET is_read = 1 WHERE id = ? AND type IN ('member','wallet','ecommerce','support')"
                    : "UPDATE admin_notifications SET is_read = 1 WHERE id = ?"
            );
            $updateStmt->execute([$nid]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid id']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_update'])) {
    header('Content-Type: application/json');
    $userId = intval($_SESSION['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $otpCode = trim($_POST['otp_code'] ?? '');

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Session expired.']);
        exit;
    }
    if ($username === '' || $email === '') {
        echo json_encode(['success' => false, 'message' => 'Username and email are required.']);
        exit;
    }

    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    if ($password !== '' && ($confirmPassword === '' || $password !== $confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'Please confirm your new password.']);
        exit;
    }

    if ($otpCode !== '') {
        $pending = $_SESSION['profile_pending_update'] ?? null;
        if (!$pending || !isset($pending['otp_hash']) || (time() > intval($pending['otp_expiry'] ?? 0)) || !password_verify($otpCode, $pending['otp_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired verification code.']);
            exit;
        }

        try {
            $updateHash = $pending['password_hash'] ?? null;
            if ($updateHash !== null) {
                $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?")
                    ->execute([$pending['username'], $pending['email'], $updateHash, $userId]);
            } else {
                $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?")
                    ->execute([$pending['username'], $pending['email'], $userId]);
            }

            $_SESSION['user_name'] = $pending['username'];
            unset($_SESSION['profile_pending_update']);
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully.', 'username' => $pending['username'], 'email' => $pending['email']]);
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/../profile_update_error.log', date('Y-m-d H:i:s') . " UPDATE ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            echo json_encode(['success' => false, 'message' => 'Unable to update profile.']);
        }
        exit;
    }

    try {
        $check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1");
        $check->execute([$username, $email, $userId]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'That username or email is already in use.']);
            exit;
        }

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $pendingHash = password_hash($otp, PASSWORD_DEFAULT);
        $_SESSION['profile_pending_update'] = [
            'username' => $username,
            'email' => $email,
            'password_hash' => ($password !== '') ? password_hash($password, PASSWORD_DEFAULT) : null,
            'otp_hash' => $pendingHash,
            'otp_plain' => $otp,
            'otp_expiry' => time() + 600
        ];

        $mailBody = '<html><body><h2>Profile update verification</h2><p>Your verification code is:</p><h1 style="font-size:32px;letter-spacing:4px;">' . htmlspecialchars($otp) . '</h1><p>This code expires in 10 minutes.</p><p>Thank you,<br>Lingunan Fitness Gym</p></body></html>';
        $mailResult = send_gmail_smtp($email, 'Profile update verification code', $mailBody);
        if ($mailResult !== true) {
            unset($_SESSION['profile_pending_update']);
            echo json_encode(['success' => false, 'message' => 'Unable to send verification email.']);
            exit;
        }

        echo json_encode(['success' => true, 'requires_verification' => true, 'message' => 'A verification code was sent to your email.']);
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/../profile_update_error.log', date('Y-m-d H:i:s') . " PENDING SAVE ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Unable to update profile.']);
    }
    exit;
}

$_displayName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$_profileUser = null;
if (isset($pdo) && !empty($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $_profileUser = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}
$_profileUsername = htmlspecialchars($_profileUser['username'] ?? $_SESSION['user_name'] ?? 'User');
$_profileEmail = htmlspecialchars($_profileUser['email'] ?? '');
$_profileRole = htmlspecialchars($_profileUser['role'] ?? $_SESSION['user_role'] ?? 'user');

// ── Build notifications ────────────────────────────────────────────────────
$_notifications = [];
if (isset($pdo)) {
    // New members today
    try {
        $rows = $pdo->query("SELECT first_name, last_name FROM members WHERE DATE(Joined_Date)=CURDATE() ORDER BY id DESC LIMIT 5")->fetchAll();
        foreach ($rows as $r) {
            $_notifications[] = ['icon'=>'&#127381;','color'=>'#1976d2','title'=>htmlspecialchars(trim($r['first_name'].' '.$r['last_name'])).' joined','sub'=>'New member registered today','type'=>'member'];
        }
    } catch(Exception $e) {}
    // Expiring memberships within 7 days
    try {
        $rows = $pdo->query("SELECT first_name, last_name, membership_expiry FROM members WHERE type='member' AND membership_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY membership_expiry ASC LIMIT 5")->fetchAll();
        foreach ($rows as $r) {
            $days = (int)((strtotime($r['membership_expiry']) - strtotime('today')) / 86400);
            $when = $days === 0 ? 'today' : "in $days day".($days>1?'s':'');
            $_notifications[] = ['icon'=>'&#9203;','color'=>'#f57c00','title'=>htmlspecialchars(trim($r['first_name'].' '.$r['last_name'])),'sub'=>'Membership expires '.$when,'type'=>'member'];
        }
    } catch(Exception $e) {}
    // Low stock
    try {
        $rows = $pdo->query("SELECT product_name, quantity FROM products WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 5")->fetchAll();
        foreach ($rows as $r) {
            $_notifications[] = ['icon'=>'&#128230;','color'=>'#e53935','title'=>htmlspecialchars($r['product_name']),'sub'=>'Only '.$r['quantity'].' left in stock','type'=>'ecommerce'];
        }
    } catch(Exception $e) {}
    // Dynamic admin actions
    try {
        $rows = $pdo->query("SELECT id, type, title, message, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM admin_notifications WHERE is_read = 0" . $adminNotificationTypeRestriction . " ORDER BY created_at DESC LIMIT 10")->fetchAll();
        foreach ($rows as $r) {
            $type = $r['type'] ?? 'system';
            $icon = '&#128276;';
            $color = '#f5c518';
            if ($type === 'wallet') { $icon = '&#128176;'; $color = '#388e3c'; }
            elseif ($type === 'ecommerce') { $icon = '&#128230;'; $color = '#e53935'; }
            elseif ($type === 'staff') { $icon = '&#128100;'; $color = '#1976d2'; }
            elseif ($type === 'backup') { $icon = '&#128190;'; $color = '#1976d2'; }
            $sub = htmlspecialchars($r['message']);
            // append datetime to subtitle
            if (!empty($r['created_at'])) $sub .= ' · ' . date('M j H:i', strtotime($r['created_at']));
            $_notifications[] = ['id' => intval($r['id']), 'icon'=>$icon,'color'=>$color,'title'=>htmlspecialchars($r['title']),'sub'=>$sub,'type'=>$type];
        }
    } catch(Exception $e) {}
    if ($_currentUserRole !== 'staff') {
        // Password reset requests
        try {
            $rows = $pdo->query("SELECT username, COUNT(*) as count FROM password_reset_requests WHERE status = 'pending' GROUP BY username ORDER BY created_at DESC LIMIT 5")->fetchAll();
            foreach ($rows as $r) {
                $_notifications[] = ['icon'=>'&#128274;','color'=>'#d32f2f','title'=>htmlspecialchars($r['username']).' - password reset','sub'=>'Pending approval','type'=>'staff'];
            }
        } catch(Exception $e) {}
    }
    // Wallet transactions (cash-in, refund, correction)
    try {
        $rows = $pdo->query("SELECT username, transaction_type, amount FROM transactions WHERE transaction_type IN ('cash_in', 'refund', 'correction') ORDER BY created_at DESC LIMIT 5")->fetchAll();
        foreach ($rows as $r) {
            $typeLabel = $r['transaction_type'] === 'cash_in' ? 'Cash In' : ($r['transaction_type'] === 'refund' ? 'Refund' : 'Correction');
            $_notifications[] = ['icon'=>'&#128176;','color'=>'#388e3c','title'=>htmlspecialchars($r['username']),'sub'=>$typeLabel.' - ₱'.number_format($r['amount'], 2),'type'=>'wallet'];
        }
    } catch(Exception $e) {}
    // Backup access attempts
    if ($_currentUserRole !== 'staff') {
        try {
            $rows = $pdo->query("SELECT username, accessed_at FROM backup_logs ORDER BY accessed_at DESC LIMIT 3")->fetchAll();
            foreach ($rows as $r) {
                $_notifications[] = ['icon'=>'&#128190;','color'=>'#1976d2','title'=>htmlspecialchars($r['username']).' accessed backup','sub'=>'Backup system accessed','type'=>'backup'];
            }
        } catch(Exception $e) {}
    }
}

// Detect if we're in a subdirectory (management/ or system/) or root client folder
$_inSub = (strpos(str_replace('\\','/',$_SERVER['SCRIPT_FILENAME']), '/management/') !== false
        || strpos(str_replace('\\','/',$_SERVER['SCRIPT_FILENAME']), '/system/')    !== false);
$_linkMap = [
    'member'     => $_inSub ? '../management/member.php'  : 'management/member.php',
    'ecommerce'  => $_inSub ? '../system/Ecommerce.php'   : 'system/Ecommerce.php',
    'staff'      => $_inSub ? '../management/staff.php'   : 'management/staff.php',
    'wallet'     => $_inSub ? '../management/wallet.php'  : 'management/wallet.php',    'support'    => $_inSub ? '../system/support.php'    : 'system/support.php',    'backup'     => $_inSub ? '../system/backup.php'      : 'system/backup.php',
];
$_notifCount = count($_notifications);
?>
<style>
/* ── Notification bell + dropdown ─────────────────────────── */
.notif-wrap {
    position: relative;
}
.notif-badge {
    position: absolute; top: -6px; right: -8px;
    background: #e53935; color: #fff;
    font-size: 10px; font-weight: 700;
    min-width: 17px; height: 17px;
    border-radius: 9px; display: flex;
    align-items: center; justify-content: center;
    padding: 0 4px; pointer-events: none;
    border: 2px solid #181818;
}
.notif-badge.is-hidden {
    display: none !important;
}
.notif-dropdown {
    display: none;
    position: absolute; top: calc(100% + 14px); right: -10px;
    width: 320px; background: #1e1e1e;
    border-radius: 14px; border: 1px solid #2a2a2a;
    box-shadow: 0 8px 32px rgba(0,0,0,.6);
    z-index: 9999; overflow: hidden;
}
.notif-dropdown.open { display: block; }
.notif-dropdown-header {
    padding: 13px 16px 10px;
    border-bottom: 1px solid #2a2a2a;
    display: flex; align-items: center; justify-content: space-between;
}
.notif-dropdown-header span {
    font-size: 13px; font-weight: 700; color: #bbb; text-transform: uppercase; letter-spacing: .4px;
}
.notif-dropdown-header small {
    font-size: 11px; color: #555;
}
.notif-list { max-height: 340px; overflow-y: auto; }
.notif-list::-webkit-scrollbar { width: 4px; }
.notif-list::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
.notif-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 11px 16px; border-bottom: 1px solid #242424;
    cursor: pointer; transition: background .15s; text-decoration: none;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: rgba(255,255,255,.04); }
.notif-item-icon {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
}
.notif-item-text { flex: 1; min-width: 0; }
.notif-item-title {
    font-size: 13px; font-weight: 600; color: #fff;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.notif-item-sub { font-size: 11.5px; color: #777; margin-top: 2px; }
.notif-empty {
    text-align: center; padding: 30px 16px; color: #444; font-size: 13px;
}
.notif-empty span { display: block; font-size: 2rem; margin-bottom: 8px; }
.profile-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.7); display: none; align-items: center; justify-content: center; z-index: 10000; padding: 20px;
}
.profile-modal-overlay.active { display: flex; }
.profile-modal-box {
    width: min(460px, 100%); background: #1f1f1f; border: 1px solid #333; border-radius: 16px; padding: 22px; box-shadow: 0 12px 40px rgba(0,0,0,.5);
}
.profile-modal-header { display: flex; justify-content: space-between; align-items:flex-start; gap: 10px; margin-bottom: 16px; }
.profile-modal-header h3 { margin: 0; color:#fff; }
.profile-modal-header p { margin: 4px 0 0; color:#888; font-size: 13px; }
.profile-modal-close { border:none; background:#2a2a2a; color:#fff; width:34px; height:34px; border-radius:50%; cursor:pointer; font-size:22px; }
.profile-form { display: flex; flex-direction: column; gap: 10px; }
.profile-form label { font-size: 13px; color:#bbb; font-weight: 600; }
.profile-form input { padding: 10px 12px; border-radius: 10px; border:1px solid #444; background:#121212; color:#fff; }
.profile-form small { color:#777; font-weight: 500; }
.profile-hint { color:#888; font-size:12px; margin:0; }
.profile-modal-actions { display:flex; gap:10px; margin-top: 8px; }
.profile-modal-actions .btn-submit, .profile-modal-actions .btn-cancel-modal { flex:1; }
.user-info { display:flex; align-items:center; gap:8px; cursor:pointer; }
.user-info:focus { outline: 2px solid #f57f00; outline-offset: 2px; }
.user-role { font-size: 11px; color:#aaa; text-transform: uppercase; letter-spacing: .4px; }
.user-info{
    display:flex;
    align-items:center;
    gap:12px;
    cursor:pointer;
    padding:8px 14px;
    border-radius:50px;
    transition:.25s;
}

.user-info:hover{
    background:#2b2b2b;
}

.profile-avatar{
    width:46px;
    height:46px;
    border-radius:50%;
    background:#ffcc00;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 0 0 3px rgba(255,204,0,.25);
}

.profile-avatar svg{
    width:25px;
    height:25px;
    color:#fff;
}

.user-details{
    display:flex;
    flex-direction:column;
}

.user-name{
    color:#fff;
    font-size:15px;
    font-weight:700;
    line-height:1.2;
}

.user-role{
    color:#b5b5b5;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.6px;
}
/* Profile Buttons */
.profile-modal-actions{
    display:flex;
    gap:12px;
    margin-top:20px;
}

.profile-modal-actions button{
    flex:1;
    height:46px;
    border-radius:10px;
    font-size:14px;
    font-weight:600;
    font-family:inherit;
    cursor:pointer;
    border:none;
    transition:all .2s ease;
    outline:none;
}

/* Primary Button */
.btn-submit{
    background:#facc15;
    color:#111827;
    border:1px solid #eab308;
    box-shadow:0 4px 12px rgba(250,204,21,.25);
}

.btn-submit:hover{
    background:#eab308;
    border-color:#ca8a04;
    transform:translateY(-1px);
    box-shadow:0 8px 20px rgba(250,204,21,.35);
}

.btn-submit:active{
    transform:translateY(0);
    box-shadow:0 2px 8px rgba(250,204,21,.25);
}

.btn-submit:focus-visible{
    box-shadow:0 0 0 3px rgba(250,204,21,.25);
}

/* Secondary Button */
.btn-cancel-modal{
    background:#27272a;
    color:#e4e4e7;
    border:1px solid #3f3f46;
}

.btn-cancel-modal:hover{
    background:#3f3f46;
    border-color:#52525b;
    color:#fff;
    transform:translateY(-1px);
}

.btn-cancel-modal:active{
    transform:translateY(0);
}

.btn-cancel-modal:focus-visible{
    box-shadow:0 0 0 3px rgba(255,255,255,.08);
}

/* Disabled */
.profile-modal-actions button:disabled{
    opacity:.55;
    cursor:not-allowed;
    transform:none;
    box-shadow:none;
}

/* Responsive */
@media(max-width:480px){
    .profile-modal-actions{
        flex-direction:column;
    }
}
</style>
<div class="profile-modal-overlay" id="profileModal" data-username="<?= $_profileUsername ?>" data-email="<?= $_profileEmail ?>">
  <div class="profile-modal-box">
    <div class="profile-modal-header">
      <div>
        <h3>My Profile</h3>
        <p>Update your account details below.</p>
      </div>
      <button type="button" class="profile-modal-close" id="profileModalClose" aria-label="Close profile">×</button>
    </div>
    <form id="profileForm" class="profile-form">
      <input type="hidden" name="profile_update" value="1">
      <label>Username</label>
      <input type="text" name="username" id="profileUsername" required>
      <label>Email</label>
      <input type="email" name="email" id="profileEmail" required>
      <label>Password <small>(leave blank to keep current password)</small></label>
      <input type="password" name="password" id="profilePassword" placeholder="Enter new password">
      <div id="profileConfirmPasswordWrap" style="display:none; flex-direction:column; gap:8px;">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" id="profileConfirmPassword" placeholder="Re-enter new password">
      </div>
      <div id="profileOtpWrap" style="display:none; flex-direction:column; gap:8px;">
        <label>Verification Code</label>
        <input type="text" name="otp_code" id="profileOtp" inputmode="numeric" maxlength="6" pattern="\d{6}" placeholder="Enter 6-digit code">
        <p id="profileOtpHint" class="profile-hint">A one-time code will be sent to your email.</p>
      </div>
      <div class="profile-modal-actions">
        <button type="submit" class="btn-submit">Save Changes</button>
        <button type="button" class="btn-cancel-modal" id="profileCancelBtn">Cancel</button>
      </div>
    </form>
  </div>
</div>
<div class="dashboard-header">
  <div class="logo">
    <div class="logo-img"></div>
    <div class="logo-text">Lingunan<span>FitnessGym</span></div>
  </div>
  <div class="header-actions">
    <!-- Notification bellaa -->
    <div class="notif-wrap" id="notifWrap">
      <span class="notif" id="notifBell" title="Notifications">&#128276;</span>
      <?php if ($_notifCount > 0): ?>
      <span class="notif-badge"><?php echo $_notifCount; ?></span>
      <?php endif; ?>
      <!-- Dropdown -->
      <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-dropdown-header">
          <span>Notifications</span>
          <small><?php echo $_notifCount; ?> item<?php echo $_notifCount!==1?'s':''; ?></small>
        </div>
        <div class="notif-list" id="notifList">
          <?php if (empty($_notifications)): ?>
            <div class="notif-empty"><span>&#127881;</span>All clear — no new alerts.</div>
          <?php else: ?>
                        <?php foreach ($_notifications as $index => $n): ?>
                        <a class="notif-item" href="<?php echo $_linkMap[$n['type']]; ?>" data-notif-id="<?php echo intval($n['id'] ?? $index); ?>">
              <div class="notif-item-icon" style="background:<?php echo $n['color']; ?>22; color:<?php echo $n['color']; ?>;">
                <?php echo $n['icon']; ?>
              </div>
              <div class="notif-item-text">
                <div class="notif-item-title"><?php echo $n['title']; ?></div>
                <div class="notif-item-sub"><?php echo $n['sub']; ?></div>
              </div>
            </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="user-info" id="userInfoTrigger" role="button" tabindex="0">
    <div class="profile-avatar">
        <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0 2c-3.34 0-10 1.67-10 5v3h20v-3c0-3.33-6.66-5-10-5z"/>
        </svg>
    </div>

    <div class="user-details">
        <span class="user-name"><?php echo $_displayName; ?></span>
        <span class="user-role"><?php echo $_profileRole; ?></span>
    </div>
</div>
  </div>
</div>
<script>
(function(){
    var bell     = document.getElementById('notifBell');
    var dropdown = document.getElementById('notifDropdown');
    var wrap     = document.getElementById('notifWrap');
    var badge    = document.querySelector('.notif-badge');
    var list     = document.getElementById('notifList');
    var countText = dropdown ? dropdown.querySelector('.notif-dropdown-header small') : null;
    var profileTrigger = document.getElementById('userInfoTrigger');
    var profileModal = document.getElementById('profileModal');
    var profileClose = document.getElementById('profileModalClose');
    var profileCancel = document.getElementById('profileCancelBtn');
    var profileForm = document.getElementById('profileForm');
    var profileUsername = document.getElementById('profileUsername');
    var profileEmail = document.getElementById('profileEmail');
    var profilePassword = document.getElementById('profilePassword');
    var profileConfirmPasswordWrap = document.getElementById('profileConfirmPasswordWrap');
    var profileConfirmPassword = document.getElementById('profileConfirmPassword');
    var profileOtpWrap = document.getElementById('profileOtpWrap');
    var profileOtp = document.getElementById('profileOtp');
    var profileOtpHint = document.getElementById('profileOtpHint');
    var profileSubmitBtn = profileForm ? profileForm.querySelector('button[type="submit"]') : null;
    if (!bell || !dropdown || !wrap) return;

    // Notification link map from server
    var __notifLinkMap = <?php echo json_encode($_linkMap); ?>;
    var currentNotifIds = [];

    function iconForType(type){
        if (type === 'wallet') return '&#128176;';
        if (type === 'ecommerce') return '&#128230;';
        if (type === 'staff') return '&#128100;';
        if (type === 'backup') return '&#128190;';
        return '&#128276;';
    }
    function colorForType(type){
        if (type === 'wallet') return '#388e3c';
        if (type === 'ecommerce') return '#e53935';
        if (type === 'staff') return '#1976d2';
        if (type === 'backup') return '#1976d2';
        return '#f5c518';
    }

    function escapeHtml(str){
        return String(str || '').replace(/[&<>\"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]; });
    }

    function fetchNotifications(){
        fetch(window.location.pathname + '?ajax_fetch_notifications=1', { credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(data){
            var items = data.items || [];
            if (!list) return;
            list.innerHTML = '';
            if (!items.length) {
                list.innerHTML = '<div class="notif-empty"><span>🎉</span>All clear — no new alerts.</div>';
            } else {
                items.forEach(function(n){
                    var a = document.createElement('a');
                    a.className = 'notif-item';
                    var href = (__notifLinkMap && __notifLinkMap[n.type]) ? __notifLinkMap[n.type] : __notifLinkMap['member'];
                    a.href = href;
                    a.setAttribute('data-notif-id', n.id);
                    var icon = iconForType(n.type);
                    var color = colorForType(n.type);
                    var sub = escapeHtml(n.sub || n.message || '');
                    a.innerHTML = '<div class="notif-item-icon" style="background:'+color+'22; color:'+color+';">'+icon+'</div>' +
                                  '<div class="notif-item-text"><div class="notif-item-title">'+escapeHtml(n.title)+'</div><div class="notif-item-sub">'+sub+'</div></div>';
                    list.appendChild(a);
                });
            }
            updateNotifUI();
            var newIds = items.map(function(i){ return i.id; });
            var added = newIds.filter(function(id){ return currentNotifIds.indexOf(id) === -1; });
            if (added.length > 0) {
                // show visual pulse
                if (badge) { badge.classList.remove('is-hidden'); badge.style.transform = 'scale(1.2)'; setTimeout(function(){ badge.style.transform = ''; }, 350); }
            }
            currentNotifIds = newIds;
        }).catch(function(){ /* ignore fetch failures */ });
    }

    function markNotificationRead(id, follow){
        if (!id) return;
        var fd = new FormData(); fd.append('ajax_mark_notification', 1); fd.append('id', id);
        fetch(window.location.pathname, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(resp){
            var el = list.querySelector('[data-notif-id="'+id+'"]');
            if (el) el.remove();
            updateNotifUI();
            if (follow) window.location.href = follow;
        }).catch(function(){ /* ignore */ });
    }

    function updateNotifUI(){
        var items = list ? list.querySelectorAll('.notif-item') : [];
        var count = items.length;
        if (badge) {
            badge.textContent = count;
            badge.classList.toggle('is-hidden', count <= 0);
        }
        if (countText) {
            countText.textContent = count + ' item' + (count !== 1 ? 's' : '');
        }
        if (list) {
            var empty = list.querySelector('.notif-empty');
            if (count === 0) {
                if (!empty) {
                    var emptyNode = document.createElement('div');
                    emptyNode.className = 'notif-empty';
                    emptyNode.innerHTML = '<span>🎉</span>All clear — no new alerts.';
                    list.appendChild(emptyNode);
                }
            } else if (empty) {
                empty.remove();
            }
        }
    }

    

    function setOpen(open){
        dropdown.classList.toggle('open', open);
    }

    function resetProfileFormState(){
        if (profileConfirmPasswordWrap) profileConfirmPasswordWrap.style.display = 'none';
        if (profileConfirmPassword) {
            profileConfirmPassword.required = false;
            profileConfirmPassword.value = '';
        }
        if (profileOtpWrap) profileOtpWrap.style.display = 'none';
        if (profileOtp) {
            profileOtp.required = false;
            profileOtp.value = '';
        }
        if (profileOtpHint) profileOtpHint.textContent = 'A one-time code will be sent to your email.';
        if (profileSubmitBtn) profileSubmitBtn.textContent = 'Save Changes';
    }

    function updateConfirmPasswordVisibility(){
        var hasPassword = profilePassword && profilePassword.value.trim() !== '';
        if (profileConfirmPasswordWrap) {
            profileConfirmPasswordWrap.style.display = hasPassword ? 'flex' : 'none';
        }
        if (profileConfirmPassword) {
            profileConfirmPassword.required = hasPassword;
            if (!hasPassword) {
                profileConfirmPassword.value = '';
            }
        }
    }

    function openProfileModal(){
        if (!profileModal) return;
        resetProfileFormState();
        if (profileUsername) profileUsername.value = profileModal.getAttribute('data-username') || '';
        if (profileEmail) profileEmail.value = profileModal.getAttribute('data-email') || '';
        if (profilePassword) profilePassword.value = '';
        profileModal.classList.add('active');
    }

    function closeProfileModal(){
        if (profileModal) {
            resetProfileFormState();
            profileModal.classList.remove('active');
        }
    }

    function showOtpStep(message){
        if (profileOtpWrap) profileOtpWrap.style.display = 'flex';
        if (profileOtp) {
            profileOtp.required = true;
            profileOtp.disabled = false;
        }
        if (profileOtpHint) profileOtpHint.textContent = message || 'Enter the verification code we sent to your email.';
        if (profileSubmitBtn) profileSubmitBtn.textContent = 'Verify & Save';
        // Ensure OTP is focused so user can input immediately
        setTimeout(function(){
            try {
                if (profileOtp) {
                    profileOtp.focus();
                    profileOtp.scrollIntoView({block: 'center', behavior: 'smooth'});
                }
            } catch (e) {}
        }, 100);
    }

    if (profilePassword) {
        profilePassword.addEventListener('input', updateConfirmPasswordVisibility);
    }

    if (profileTrigger) {
        profileTrigger.addEventListener('click', function(e){
            e.stopPropagation();
            openProfileModal();
        });
        profileTrigger.addEventListener('keydown', function(e){
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openProfileModal();
            }
        });
    }
    if (profileClose) profileClose.addEventListener('click', closeProfileModal);
    if (profileCancel) profileCancel.addEventListener('click', closeProfileModal);
    if (profileModal) {
        profileModal.addEventListener('click', function(e){
            if (e.target === profileModal) closeProfileModal();
        });
    }
    if (profileForm) {
        profileForm.addEventListener('submit', function(e){
            e.preventDefault();
            if (profilePassword && profilePassword.value.trim() !== '' && profileConfirmPassword && profileConfirmPassword.value !== profilePassword.value) {
                alert('Passwords do not match.');
                return;
            }
            var formData = new FormData(profileForm);
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function(res){ console.log('profile: raw response', res); return res.json(); }).then(function(data){ console.log('profile: parsed json', data);
                if (data && data.requires_verification) {
                    showOtpStep(data.message || 'A verification code was sent to your email.');
                    return;
                }
                if (data && data.success) {
                    if (profileModal) {
                        profileModal.setAttribute('data-username', data.username || '');
                        profileModal.setAttribute('data-email', data.email || '');
                    }
                    if (profileUsername) profileUsername.value = data.username || '';
                    if (profileEmail) profileEmail.value = data.email || '';
                    if (profilePassword) profilePassword.value = '';
                    closeProfileModal();
                    if (document.querySelector('.user-name')) {
                        document.querySelector('.user-name').textContent = data.username || document.querySelector('.user-name').textContent;
                    }
                    window.location.reload();
                } else if (data && data.message) {
                    alert(data.message);
                }
            }).catch(function(){
                alert('Unable to update profile.');
            });
        });
    }

    bell.addEventListener('click', function(e){
        e.stopPropagation();
        setOpen(!dropdown.classList.contains('open'));
    });

    if (list) {
        list.addEventListener('click', function(e){
            var item = e.target.closest('.notif-item');
            if (!item) return;
            e.stopPropagation();
            var id = item.getAttribute('data-notif-id');
            var href = item.getAttribute('href');
            if (id) {
                markNotificationRead(id);
            }
            if (href) {
                window.location.href = href;
            }
        });
    }

    dropdown.addEventListener('click', function(e){
        e.stopPropagation();
    });

    document.addEventListener('click', function(e){
        if (!wrap.contains(e.target)) {
            setOpen(false);
        }
    });
    // initial fetch + polling for real-time behaviour
    fetchNotifications();
    setInterval(fetchNotifications, 6000);
})();
</script>
