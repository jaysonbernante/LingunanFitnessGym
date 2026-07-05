<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../../app/config/connection.php';
require_once '../../../../app/config/mail.php';

// Migrations
try { $pdo->exec("ALTER TABLE members ADD COLUMN plan_months INT DEFAULT NULL"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE members ADD COLUMN membership_expiry DATE DEFAULT NULL"); } catch(Exception $e) {}
try { $pdo->exec("CREATE TABLE IF NOT EXISTS blocked_rfids (id INT AUTO_INCREMENT PRIMARY KEY, rfid VARCHAR(100) NOT NULL, member_id INT DEFAULT NULL, blocked_at DATETIME DEFAULT NOW(), reason VARCHAR(100) DEFAULT 'lost')"); } catch(Exception $e) {}
try { $pdo->exec("CREATE TABLE IF NOT EXISTS members_archived (id INT AUTO_INCREMENT PRIMARY KEY, member_id INT NOT NULL, first_name VARCHAR(50), last_name VARCHAR(50), gmail VARCHAR(100), phone VARCHAR(20), address VARCHAR(255), type VARCHAR(50), RFID VARCHAR(50), archived_at DATETIME DEFAULT NOW(), archived_by VARCHAR(100), reason TEXT, original_data LONGTEXT)"); } catch(Exception $e) {}
try { $pdo->exec("CREATE TABLE IF NOT EXISTS member_audit (id INT AUTO_INCREMENT PRIMARY KEY, member_id INT NOT NULL, action VARCHAR(50), staff_username VARCHAR(100), reason TEXT, details LONGTEXT, created_at DATETIME DEFAULT NOW())"); } catch(Exception $e) {}
try { $pdo->exec("CREATE TABLE IF NOT EXISTS member_transactions (id INT AUTO_INCREMENT PRIMARY KEY, member_id INT DEFAULT NULL, member_name VARCHAR(150) DEFAULT NULL, transaction_type VARCHAR(50) NOT NULL, amount DECIMAL(10,2) DEFAULT 0, payment_method VARCHAR(20) DEFAULT 'cash', plan_months INT DEFAULT NULL, old_expiry DATE DEFAULT NULL, new_expiry DATE DEFAULT NULL, rfid VARCHAR(100) DEFAULT NULL, notes TEXT, created_at DATETIME DEFAULT NOW(), created_by VARCHAR(100) DEFAULT 'system')"); } catch(Exception $e) {}

// AJAX: Get archive list
if (isset($_GET['ajax_archive_list'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("SELECT * FROM members_archived ORDER BY archived_at DESC");
        $archived = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($archived);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Archive member
if (isset($_POST['ajax_archive_member'])) {
    header('Content-Type: application/json');
    $id = intval($_POST['id'] ?? 0);
    $reason = trim($_POST['reason'] ?? 'No reason provided');
    $staffUsername = $_SESSION['user_name'] ?? 'system';
    try {
        // Get member data
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$member) { echo json_encode(['success' => false, 'message' => 'Member not found.']); exit; }
        // Archive to members_archived
        $pdo->prepare("INSERT INTO members_archived (member_id, first_name, last_name, gmail, phone, address, type, RFID, archived_by, reason, original_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$id, $member['first_name'], $member['last_name'], $member['gmail'], $member['phone'], $member['address'], $member['type'], $member['RFID'], $staffUsername, $reason, json_encode($member)]);
        // Log to audit
        $pdo->prepare("INSERT INTO member_audit (member_id, action, staff_username, reason, details) VALUES (?, 'archive', ?, ?, ?)")
            ->execute([$id, $staffUsername, $reason, json_encode(['first_name' => $member['first_name'], 'last_name' => $member['last_name']])]);
        // Delete from members
        $pdo->prepare("DELETE FROM members WHERE id = ?")->execute([$id]);
        add_admin_notification($pdo, 'member', 'Member archived', 'A member account was archived from the management page.', $staffUsername);
        echo json_encode(['success' => true]);
    } catch(Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
    exit;
}

// AJAX: Recover archived member
if (isset($_POST['ajax_recover_member'])) {
    header('Content-Type: application/json');
    $memberId = intval($_POST['member_id'] ?? 0);
    $staffUsername = $_SESSION['user_name'] ?? 'system';
    try {
        $stmt = $pdo->prepare("SELECT * FROM members_archived WHERE member_id = ? ORDER BY archived_at DESC LIMIT 1");
        $stmt->execute([$memberId]);
        $archive = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$archive) { echo json_encode(['success' => false, 'message' => 'Archived member not found.']); exit; }

        $originalData = json_decode($archive['original_data'], true);
        if (!is_array($originalData)) { $originalData = []; }

        $restoreId = intval($originalData['id'] ?? $archive['member_id'] ?? $memberId);
        $firstName = trim($originalData['first_name'] ?? $archive['first_name'] ?? '');
        $lastName = trim($originalData['last_name'] ?? $archive['last_name'] ?? '');
        $username = $originalData['username'] ?? null;
        $gmail = $originalData['gmail'] ?? $archive['gmail'] ?? null;
        $phone = $originalData['phone'] ?? $archive['phone'] ?? null;
        $address = $originalData['address'] ?? $archive['address'] ?? null;
        $type = $originalData['type'] ?? $archive['type'] ?? 'session';
        $password = $originalData['password'] ?? null;
        $rfid = $originalData['RFID'] ?? $archive['RFID'] ?? null;
        $planMonths = $originalData['plan_months'] ?? null;
        $membershipExpiry = $originalData['membership_expiry'] ?? null;
        $joinedDate = $originalData['Joined_Date'] ?? date('Y-m-d');
        $credit = floatval($originalData['credit'] ?? 0);

        $exists = $pdo->prepare("SELECT id FROM members WHERE id = ? LIMIT 1");
        $exists->execute([$restoreId]);
        if ($exists->fetch()) {
            $pdo->prepare("UPDATE members SET first_name=?, last_name=?, username=?, gmail=?, phone=?, address=?, type=?, password=?, RFID=?, plan_months=?, membership_expiry=?, Joined_Date=?, credit=? WHERE id=?")
                ->execute([$firstName, $lastName, $username, $gmail, $phone, $address, $type, $password, $rfid, $planMonths, $membershipExpiry, $joinedDate, $credit, $restoreId]);
        } else {
            $pdo->prepare("INSERT INTO members (id, first_name, last_name, username, gmail, phone, address, type, password, RFID, plan_months, membership_expiry, Joined_Date, credit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$restoreId, $firstName, $lastName, $username, $gmail, $phone, $address, $type, $password, $rfid, $planMonths, $membershipExpiry, $joinedDate, $credit]);
        }

        $pdo->prepare("DELETE FROM members_archived WHERE id = ?")
            ->execute([$archive['id']]);

        $pdo->prepare("INSERT INTO member_audit (member_id, action, staff_username, details) VALUES (?, 'recover', ?, ?)")
            ->execute([$memberId, $staffUsername, json_encode(['first_name' => $firstName, 'last_name' => $lastName])]);
        add_admin_notification($pdo, 'member', 'Member recovered', 'An archived member was restored successfully.', $staffUsername);
        echo json_encode(['success' => true, 'message' => 'Member recovered successfully.']);
    } catch(Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
    exit;
}

// AJAX: Get blocked RFIDs list
if (isset($_GET['ajax_blocked_rfids'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("SELECT br.*, CONCAT(m.first_name, ' ', m.last_name) as member_name FROM blocked_rfids br LEFT JOIN members m ON br.member_id = m.id ORDER BY br.blocked_at DESC");
        $blocked = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($blocked);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Lookup member by RFID
if (isset($_GET['ajax_lookup_member_by_rfid'])) {
    header('Content-Type: application/json');
    try {
        $rfid = trim($_GET['ajax_lookup_member_by_rfid']);
        if ($rfid === '') {
            echo json_encode(['error' => 'RFID is required.']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, username, gmail, phone, address, type, RFID, Joined_Date, membership_expiry, credit FROM members WHERE RFID = ? LIMIT 1");
        $stmt->execute([$rfid]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$member) {
            echo json_encode(['error' => 'Member not found for this RFID.']);
            exit;
        }
        echo json_encode(['member' => $member]);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Add session user
if (isset($_POST['ajax_add_user'])) {
    header('Content-Type: application/json');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $gmail      = trim($_POST['gmail'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $rfid       = trim($_POST['rfid_number'] ?? '') ?: null;
    if (!$first_name || !$last_name) { echo json_encode(['success' => false, 'message' => 'First and last name are required.']); exit; }
    if (!$phone) { echo json_encode(['success' => false, 'message' => 'Phone number is required.']); exit; }
    if (!preg_match('/^[0-9]{11}$/', $phone)) { echo json_encode(['success' => false, 'message' => 'Phone number must be 11 digits with no symbols.']); exit; }
    if (!$address) { echo json_encode(['success' => false, 'message' => 'Address is required.']); exit; }
    $username = strtolower($first_name . $last_name);
    $defaultMemberPassword = 'member1234';
    $plainPassword = $defaultMemberPassword;
    $password = password_hash($plainPassword, PASSWORD_DEFAULT);
    try {
        // Duplicate username check
        $chk = $pdo->prepare("SELECT id FROM members WHERE username = ? LIMIT 1");
        $chk->execute([$username]);
        if ($chk->fetch()) { echo json_encode(['success' => false, 'message' => 'Username "' . $username . '" is already taken. Use a different name.']); exit; }
        // Duplicate gmail check
        if ($gmail) {
            $chk = $pdo->prepare("SELECT id FROM members WHERE gmail = ? LIMIT 1");
            $chk->execute([$gmail]);
            if ($chk->fetch()) { echo json_encode(['success' => false, 'message' => 'Email "' . $gmail . '" is already registered.']); exit; }
        }
        // Duplicate RFID check
        if ($rfid) {
            $chk = $pdo->prepare("SELECT id FROM members WHERE RFID = ? LIMIT 1");
            $chk->execute([$rfid]);
            if ($chk->fetch()) { echo json_encode(['success' => false, 'message' => 'RFID card is already assigned to another member.']); exit; }
        }
        $pdo->prepare("INSERT INTO members (first_name, last_name, username, gmail, phone, address, type, password, RFID, Joined_Date) VALUES (?, ?, ?, ?, ?, ?, 'session', ?, ?, CURDATE())")
            ->execute([$first_name, $last_name, $username, $gmail, $phone, $address, $password, $rfid]);
        // Get the new member ID and log to audit
        $newId = $pdo->lastInsertId();
        $staffUsername = $_SESSION['user_name'] ?? 'system';
        $pdo->prepare("INSERT INTO member_audit (member_id, action, staff_username, details) VALUES (?, 'add', ?, ?)")
            ->execute([$newId, $staffUsername, json_encode(['first_name' => $first_name, 'last_name' => $last_name, 'type' => 'session'])]);
        add_admin_notification($pdo, 'member', 'Session member created', 'A new session member was added to the system.', $staffUsername);
        $emailNote = '';
        if ($gmail) {
            $emailResult = send_registration_email($gmail, $first_name, $last_name, $username, $plainPassword);
            if ($emailResult === true) {
                $emailNote = ' Email notification sent.';
            } else {
                $emailNote = ' Email delivery failed: ' . $emailResult;
            }
        }
        echo json_encode(['success' => true, 'message' => $emailNote]);
    } catch(Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
    exit;
}

// AJAX: Add membership user
if (isset($_POST['ajax_add_membership'])) {
    header('Content-Type: application/json');
    $first_name  = trim($_POST['first_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $gmail       = trim($_POST['gmail'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $plan_months = intval($_POST['plan_months'] ?? 0);
    $rfid        = trim($_POST['rfid_number'] ?? '') ?: null;
    if (!$first_name || !$last_name) { echo json_encode(['success' => false, 'message' => 'First and last name are required.']); exit; }
    if (!$phone) { echo json_encode(['success' => false, 'message' => 'Phone number is required.']); exit; }
    if (!preg_match('/^[0-9]{11}$/', $phone)) { echo json_encode(['success' => false, 'message' => 'Phone number must be 11 digits with no symbols.']); exit; }
    if (!$address) { echo json_encode(['success' => false, 'message' => 'Address is required.']); exit; }
    if (!$plan_months) { echo json_encode(['success' => false, 'message' => 'Please select a monthly plan.']); exit; }
    if (!$rfid) { echo json_encode(['success' => false, 'message' => 'RFID card is required for membership registration.']); exit; }
    $username = strtolower($first_name . $last_name);
    $defaultMemberPassword = 'member1234';
    $plainPassword = $defaultMemberPassword;
    $password = password_hash($plainPassword, PASSWORD_DEFAULT);
    $expiry   = date('Y-m-d', strtotime("+{$plan_months} months"));
    try {
        // Duplicate username check
        $chk = $pdo->prepare("SELECT id FROM members WHERE username = ? LIMIT 1");
        $chk->execute([$username]);
        if ($chk->fetch()) { echo json_encode(['success' => false, 'message' => 'Username "' . $username . '" is already taken. Use a different name.']); exit; }
        // Duplicate gmail check
        if ($gmail) {
            $chk = $pdo->prepare("SELECT id FROM members WHERE gmail = ? LIMIT 1");
            $chk->execute([$gmail]);
            if ($chk->fetch()) { echo json_encode(['success' => false, 'message' => 'Email "' . $gmail . '" is already registered.']); exit; }
        }
        // Duplicate RFID check
        if ($rfid) {
            $chk = $pdo->prepare("SELECT id FROM members WHERE RFID = ? LIMIT 1");
            $chk->execute([$rfid]);
            if ($chk->fetch()) { echo json_encode(['success' => false, 'message' => 'RFID card is already assigned to another member.']); exit; }
        }
        $pdo->prepare("INSERT INTO members (first_name, last_name, username, gmail, phone, address, type, password, RFID, plan_months, membership_expiry, Joined_Date) VALUES (?, ?, ?, ?, ?, ?, 'member', ?, ?, ?, ?, CURDATE())")
            ->execute([$first_name, $last_name, $username, $gmail, $phone, $address, $password, $rfid, $plan_months, $expiry]);
        $newId = $pdo->lastInsertId();
        $staffUsername = $_SESSION['user_name'] ?? 'system';
        $pdo->prepare("INSERT INTO member_transactions (member_id, member_name, transaction_type, amount, payment_method, plan_months, new_expiry, rfid, notes, created_by) VALUES (?, ?, 'New Membership', 0, 'cash', ?, ?, ?, ?, ?)")
            ->execute([$newId, trim($first_name . ' ' . $last_name), $plan_months, $expiry, $rfid, 'New membership registration', $staffUsername]);
        // Get the new member ID and log to audit
        $pdo->prepare("INSERT INTO member_audit (member_id, action, staff_username, details) VALUES (?, 'add', ?, ?)")
            ->execute([$newId, $staffUsername, json_encode(['first_name' => $first_name, 'last_name' => $last_name, 'type' => 'member', 'plan_months' => $plan_months])]);
        add_admin_notification($pdo, 'member', 'Membership member created', 'A new membership member was registered.', $staffUsername);
        $emailNote = '';
        if ($gmail) {
            $emailResult = send_registration_email($gmail, $first_name, $last_name, $username, $plainPassword);
            if ($emailResult === true) {
                $emailNote = ' Email notification sent.';
            } else {
                $emailNote = ' Email delivery failed: ' . $emailResult;
            }
        }
        echo json_encode(['success' => true, 'message' => $emailNote]);
    } catch(Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
    exit;
}

// AJAX: Renew membership
if (isset($_POST['ajax_renew_membership'])) {
    header('Content-Type: application/json');
    $memberId = intval($_POST['member_id'] ?? 0);
    $rfid = trim($_POST['rfid'] ?? '');
    $planMonths = intval($_POST['plan_months'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? 'cash');
    $staffUsername = $_SESSION['user_name'] ?? 'system';
    if (!$memberId && !$rfid) { echo json_encode(['success' => false, 'message' => 'Member is required.']); exit; }
    if (!$planMonths) { echo json_encode(['success' => false, 'message' => 'Please select a monthly plan.']); exit; }
    if (!in_array($paymentMethod, ['cash', 'credit'], true)) { echo json_encode(['success' => false, 'message' => 'Invalid payment method.']); exit; }
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, type, RFID, COALESCE(credit,0) as credit, membership_expiry, plan_months FROM members WHERE (? IS NOT NULL AND id = ?) OR (RFID = ? AND ? <> '') LIMIT 1");
        $stmt->execute([$memberId ? $memberId : null, $memberId, $rfid, $rfid]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$member) { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => 'Member not found.']); exit; }
        $planPrices = [1 => 850, 3 => 1800, 5 => 2500];
        $amount = $planPrices[$planMonths] ?? 0;
        if ($amount <= 0) { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => 'Invalid plan.']); exit; }
        if ($paymentMethod === 'credit') {
            if (floatval($member['credit']) < $amount) { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => 'Insufficient member credit.']); exit; }
            $pdo->prepare("UPDATE members SET credit = credit - ? WHERE id = ?")->execute([$amount, $member['id']]);
        }
        $newExpiry = date('Y-m-d', strtotime("+{$planMonths} months"));
        $memberType = strtolower((string)($member['type'] ?? ''));
        $pdo->prepare("UPDATE members SET type='member', plan_months=?, membership_expiry=? WHERE id=?")
            ->execute([$planMonths, $newExpiry, $member['id']]);
        $transactionType = $memberType === 'session'
            ? 'New Membership'
            : ((!empty($member['membership_expiry']) && strtotime($member['membership_expiry']) > strtotime(date('Y-m-d')))
                ? 'Membership Extension'
                : 'Membership Renewal');
        $pdo->prepare("INSERT INTO member_transactions (member_id, member_name, transaction_type, amount, payment_method, plan_months, old_expiry, new_expiry, rfid, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$member['id'], trim($member['first_name'] . ' ' . $member['last_name']), $transactionType, $amount, $paymentMethod, $planMonths, $member['membership_expiry'], $newExpiry, $member['RFID'], $transactionType, $staffUsername]);
        try {
            $pdo->prepare("INSERT INTO entry_logs (member_id, member_name, entry_type, amount_charged, payment_method) VALUES (?, ?, 'membership_renewal', ?, ?)")
                ->execute([$member['id'], trim($member['first_name'] . ' ' . $member['last_name']), $amount, $paymentMethod]);
        } catch (Exception $e) {}
        add_admin_notification($pdo, 'member', 'Membership updated', 'A membership transaction was recorded for a member.', $staffUsername);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Membership renewed successfully.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Edit member
if (isset($_POST['ajax_edit_member'])) {
    header('Content-Type: application/json');
    $id         = intval($_POST['id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $gmail      = trim($_POST['gmail'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $new_rfid   = trim($_POST['new_rfid'] ?? '') ?: null;
    $old_rfid   = trim($_POST['old_rfid'] ?? '') ?: null;
    $block_old  = ($_POST['block_old'] ?? '0') === '1';
    $staffUsername = $_SESSION['user_name'] ?? 'system';
    if (!$first_name || !$last_name || !$id) { echo json_encode(['success' => false, 'message' => 'Required fields missing.']); exit; }
    try {
        $existing = $pdo->prepare("SELECT type, RFID FROM members WHERE id = ? LIMIT 1");
        $existing->execute([$id]);
        $currentMember = $existing->fetch(PDO::FETCH_ASSOC);
        // Duplicate gmail check (exclude self)
        if ($gmail) {
            $chk = $pdo->prepare("SELECT id FROM members WHERE gmail = ? AND id != ? LIMIT 1");
            $chk->execute([$gmail, $id]);
            if ($chk->fetch()) { echo json_encode(['success' => false, 'message' => 'Email "' . $gmail . '" is already registered to another member.']); exit; }
        }
        // Duplicate RFID check (exclude self)
        if ($new_rfid) {
            $chk = $pdo->prepare("SELECT id FROM members WHERE RFID = ? AND id != ? LIMIT 1");
            $chk->execute([$new_rfid, $id]);
            if ($chk->fetch()) { echo json_encode(['success' => false, 'message' => 'RFID card is already assigned to another member.']); exit; }
        }
        if ($new_rfid && $old_rfid && $block_old) {
            $pdo->prepare("INSERT INTO blocked_rfids (rfid, member_id, reason) VALUES (?, ?, 'lost')")->execute([$old_rfid, $id]);
        }
        if ($new_rfid) {
            $pdo->prepare("UPDATE members SET first_name=?, last_name=?, gmail=?, phone=?, address=?, RFID=? WHERE id=?")
                ->execute([$first_name, $last_name, $gmail, $phone, $address, $new_rfid, $id]);
        } else {
            $pdo->prepare("UPDATE members SET first_name=?, last_name=?, gmail=?, phone=?, address=? WHERE id=?")
                ->execute([$first_name, $last_name, $gmail, $phone, $address, $id]);
        }
        if ($new_rfid && $currentMember && $currentMember['type'] === 'session' && empty($currentMember['RFID'])) {
            $pdo->prepare("INSERT INTO member_transactions (member_id, member_name, transaction_type, amount, payment_method, rfid, notes, created_by) VALUES (?, ?, 'rfid_assignment', 0, 'cash', ?, ?, ?)")
                ->execute([$id, trim($first_name . ' ' . $last_name), $new_rfid, 'RFID assignment for session member', $staffUsername]);
        }
        // Log to audit
        $pdo->prepare("INSERT INTO member_audit (member_id, action, staff_username, details) VALUES (?, 'edit', ?, ?)")
            ->execute([$id, $staffUsername, json_encode(['first_name' => $first_name, 'last_name' => $last_name, 'gmail' => $gmail, 'phone' => $phone, 'rfid_changed' => $new_rfid !== $old_rfid])]);
        add_admin_notification($pdo, 'member', 'Member updated', 'Member details were updated from the management page.', $staffUsername);
        echo json_encode(['success' => true]);
    } catch(Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
    exit;
}

$page = 'member';
include '../../../../component/admin_header.php';
include '../../../../component/admin_sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Management</title>
    <link href="../../../../assets/css/toastednotif.css" rel="stylesheet">
    <link href="../../../../assets/css/admin_header.css" rel="stylesheet">
    <link href="../../../../assets/css/admin_sidebar.css" rel="stylesheet">
    <link href="../../../../assets/css/admin.css" rel="stylesheet">
</head>
<style>
</style>
<style>
    .Member-content{
        margin-left: 250px;
        margin-top: 60px;
        padding: 2rem;
        min-height: calc(100vh - 60px);
        background: #222;
    }
    @media (max-width: 900px) {
        .Member-content {
            margin-left: 0;
            padding: 1rem;
        }
    }
    .admin-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        background: rgba(0,0,0,0.04);
        font-family: 'Inter', Arial, sans-serif;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border-radius: 16px;
        overflow: hidden;
    }
    .admin-table thead th:first-child {
        border-top-left-radius: 16px;
    }
    .admin-table thead th:last-child {
        border-top-right-radius: 16px;
    }
    .admin-table tbody tr:last-child td:first-child {
        border-bottom-left-radius: 16px;
    }
    .admin-table tbody tr:last-child td:last-child {
        border-bottom-right-radius: 16px;
    }
    .admin-table th, .admin-table td {
        padding: 12px 10px;
    text-align: left;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    }
    .admin-table th {
        background: rgba(0,0,0,0.04);
        font-weight: 700;
        color: #fff;
        border-bottom: 2px solid #333;
    }
    .admin-table tr {
        transition: background 0.2s;
    }
    .admin-table tbody tr:hover {
        background: #f9f9f965;
        color: #333;
    }
    .admin-table td {
        border-bottom: 1px solid #333;
        font-size: 15px;
    }
    .badge-status {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 12px;
        font-size: 90%;
        font-weight: 600;
        color: #fff;
    }
    .badge-active {
        background: #43a047;
    }
    .badge-inactive {
        background: #e53935;
    }
    .badge-auth {
        background: #ffe082;
        color: #795548;
        border-radius: 8px;
        padding: 2px 10px;
        font-weight: 600;
    }
    .badge-type {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }
    .badge-type.session {
        background: #1976d2;
        color: #fff;
    }
    .badge-type.membership {
        background: #f5c518;
        color: #1f1f1f;
    }
    .action-btn {
        background: none;
        border: none;
        color: #1976d2;
        cursor: pointer;
        padding: 0 8px;
        font-size: 15px;
        transition: color 0.2s;
    }
    .action-btn.delete {
        color: #d32f2f;
    }
    .action-btn:hover {
        text-decoration: underline;
    }
    .action-group {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 62px;
        padding: 6px 10px;
    }
    .admin-table th:nth-child(1){width:18%;}
    .admin-table th:nth-child(2){width:20%;}
    .admin-table th:nth-child(3){width:9%;}
    .admin-table th:nth-child(4){width:10%;}
    .admin-table th:nth-child(5){width:12%;}
    .admin-table th:nth-child(6){width:12%;}
    .admin-table th:nth-child(7){width:auto;}
    .admin-table th:nth-child(6){width:15%;}

    /* Responsive table - horizontal scroll on small screens */
    .table-wrapper {
        overflow-x: auto;
        width: 100%;
        -webkit-overflow-scrolling: touch;
    }
    .admin-table {
        
        word-break: break-word;
    }
    .admin-table th,
    .admin-table td {
        min-width: 0;
    }

    .tab-btn {
        transition: transform 0.15s ease, background 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }
    .tab-btn:hover {
        transform: translateY(-1px);
        opacity: 0.95;
    }
    .tab-btn.active {
        box-shadow: 0 0 0 2px rgba(255,255,255,0.12);
    }
    #btnArchiveHistory.active {
        background: #ef6c00 !important;
    }
    #btnBlockList.active {
        background: #b71c1c !important;
    }
    #addUserBtn.active {
        background: #1565c0 !important;
    }
    #btnArchiveHistory:hover {
        background: #ff9800 !important;
    }
    #btnBlockList:hover {
        background: #e53935 !important;
    }
    #addUserBtn:hover {
        background: #1976d2 !important;
    }

    @media (max-width: 900px) {
        #searchUsername { width: 100% !important; box-sizing: border-box; }
        .admin-table th,
        .admin-table td { padding: 10px 8px; font-size: 14px; }
    }
    @media (max-width: 760px) {
        .admin-table { min-width: 100%; }
    }
    /* Mobile stacked table */
    @media (max-width: 600px) {
        .table-wrapper { overflow-x: hidden; }
        .admin-table { min-width: 0; }
        .admin-table thead { display: none; }
        .admin-table tbody tr {
            display: grid;
            grid-template-columns: 1fr;
            gap: 6px;
            margin-bottom: 12px;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 10px;
            background: #1b1b1b;
        }
        .admin-table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            padding: 10px 8px;
            border: none;
            white-space: normal;
            flex-wrap: wrap;
        }
        .admin-table tbody td::before {
            content: attr(data-label);
            color: #f5c518;
            font-weight: 700;
            margin-right: 8px;
            width: auto;
            min-width: 80px;
        }
        .admin-table tbody td:last-child { justify-content: flex-end; }
        .admin-table tbody td span,
        .admin-table tbody td button,
        .admin-table tbody td .badge-status,
        .admin-table tbody td .action-btn { max-width: 100%; }
        .admin-table tbody td[data-label="Actions"] {
            display: block;
            padding: 10px 8px 6px;
            display: flex;
            justify-content: space-between;
        }
        .admin-table tbody td[data-label="Actions"]::before {
            display: block;
            margin-bottom: 6px;
        }
        .admin-table tbody td[data-label="Actions"] .action-group {
            justify-content: flex-end;
            gap: 8px;
        }
        .expired-membership {
            background: rgba(211, 47, 47, 0.12);
        }
        .expired-membership td {
            color: #ffb3b3;
        }
        .expired-membership td[data-label="Expiry Date"] {
            font-weight: 700;
            color: #ff8a65;
        }
       
    }
    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.65);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: #2c2c2c;
        border-radius: 16px;
        padding: 32px;
        max-width: 480px;
        width: 90%;
        color: #fff;
        box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        max-height: 90vh;
        overflow-y: auto;
    }
    .modal-box h2 { margin: 0 0 8px; font-size: 1.3rem; }
    .modal-subtitle { color: #aaa; font-size: 14px; margin: 0 0 20px; }
    .type-cards { display: flex; gap: 16px; }
    .type-card {
        flex: 1;
        padding: 24px 16px;
        border-radius: 12px;
        border: 2px solid #444;
        text-align: center;
        cursor: pointer;
        background: #333;
        font-weight: 600;
        font-size: 1rem;
        color: #fff;
        transition: border-color 0.2s, background 0.2s;
    }
    .type-card.session:hover { border-color: #1976d2; background: rgba(25,118,210,0.12); }
    .type-card.membership:hover { border-color: #f5c518; background: rgba(245,197,24,0.12); }
    .type-card .card-icon { font-size: 1.8rem; margin-bottom: 8px; }
    /* Plan selection cards */
    .plan-cards { display:flex; gap:12px; margin-bottom:16px; }
    .plan-card {
        flex:1; padding:16px 8px; border-radius:12px; border:2px solid #444;
        text-align:center; cursor:pointer; background:#333;
        transition:border-color 0.2s, background 0.2s;
    }
    .plan-card:hover { border-color:#1976d2; background:rgba(25,118,210,0.12); }
    .plan-card.selected { border-color:#f5c518; background:rgba(245,197,24,0.1); }
    .plan-duration { font-weight:700; font-size:0.9rem; color:#fff; margin-bottom:6px; }
    .plan-price { font-size:1.15rem; font-weight:800; color:#f5c518; }
    /* RFID toggle */
    .avail-card-toggle { display:flex; align-items:center; gap:8px; margin-bottom:12px; cursor:pointer; font-size:14px; color:#bbb; }
    .avail-card-toggle input[type=checkbox] { width:auto !important; margin:0; }
    .modal-form { display: none; }
    .modal-form.active { display: block; }
    .btn-back {
        background: none;
        border: none;
        color: #f5c518;
        cursor: pointer;
        font-size: 13px;
        padding: 0;
        margin-bottom: 16px;
       
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .btn-back:hover { color: #fff; }
    .modal-form label { display: block; margin-bottom: 4px; font-size: 13px; color: #bbb; }
    .modal-form input, .modal-form select {
        width: 100%;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid #444;
        background: #1a1a1a;
        color: #fff;
        margin-bottom: 12px;
        box-sizing: border-box;
        font-size: 14px;
    }
    .modal-form .form-row { display: flex; gap: 10px; }
    .modal-form .form-row > div { flex: 1; }
    .modal-actions { display: flex; gap: 10px; margin-top: 4px; }
    .btn-submit { padding: 9px 22px; border-radius: 8px; border: none; background: #1976d2; color: #fff; font-weight: 600; cursor: pointer; }
    .btn-submit:hover { background: #1565c0; }
    .btn-cancel-modal { padding: 9px 22px; border-radius: 8px; border: none; background: #444; color: #fff; font-weight: 600; cursor: pointer; }
    .user-cell { display: flex; align-items: center; gap: 10px; }
    .user-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; font-weight: 700; color: #fff;
        flex-shrink: 0; text-transform: uppercase;
    }
    .user-name-text { font-weight: 500; color: #fff; }
    /* Member stat chips */
    .member-stats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
    .ms-chip {
        display:flex; gap:8px;
        background:#1e1e1e; border-radius:8px; 
        font-size:13px; font-weight:600; border:1px solid #2a2a2a;
    }
    .ms-dot { width:10px; height:10px; border-radius:50%; display:inline-block; flex-shrink:0; }
    .ms-dot.total      { background:#aaa; }
    .ms-dot.session    { background:#1976d2; }
    .ms-dot.membership { background:#f5c518; }
    .ms-dot.blocked    { background:#d32f2f; }
    .ms-dot.archived   { background:#ef6c00; }
    .ms-num  { color:#fff; }
    .ms-lbl  { color:#777; font-weight:500; }
    /* Confirm modal */
    .confirm-box { background:#2c2c2c; border-radius:16px; padding:28px 32px; max-width:360px; width:90%; color:#fff; box-shadow:0 8px 32px rgba(0,0,0,0.5); }
    .confirm-box h3 { margin:0 0 8px; font-size:1.1rem; }
    .confirm-box p { color:#aaa; font-size:14px; margin:0 0 20px; }
    .btn-danger { padding:9px 22px; border-radius:8px; border:none; background:#d32f2f; color:#fff; font-weight:600; cursor:pointer; }
    .btn-danger:hover { background:#b71c1c; }
    /* RFID Tap scanner */
    .rfid-tap-btn { display:flex; align-items:center; gap:8px; width:100%; padding:12px 16px; margin-bottom:12px; border-radius:10px; border:2px dashed #444; background:#1a1a1a; color:#bbb; font-size:14px; font-weight:600; cursor:pointer; transition:border-color 0.2s,color 0.2s; box-sizing:border-box; }
    .rfid-tap-btn:hover { border-color:#1976d2; color:#fff; }
    .rfid-tap-btn.scanning { border-color:#f5c518; color:#f5c518; animation:rfid-pulse 1s infinite; }
    .rfid-tap-btn .rfid-icon { font-size:1.4rem; flex-shrink:0; }
    .rfid-captured { display:none; align-items:center; gap:10px; padding:10px 14px; border-radius:8px; background:rgba(67,160,71,0.15); border:1px solid #43a047; margin-bottom:12px; font-size:14px; color:#81c784; }
    .rfid-captured .rfid-val { font-weight:700; flex:1; }
    .rfid-captured .rfid-clear { background:none; border:none; color:#e57373; cursor:pointer; font-size:16px; padding:0 4px; }
    .rfid-hidden-input { position:absolute; opacity:0; width:0; height:0; pointer-events:none; }
    @keyframes rfid-pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }
    </style>
<body>
    <div class="Member-content">
        <h1>Member Management</h1>
        <?php
// Member type counts (always unfiltered)
try {
    $countStmt = $pdo->query("SELECT type, COUNT(*) as cnt FROM members GROUP BY type");
    $countRows = $countStmt->fetchAll();
    $countTotal = 0; $countSession = 0; $countMembership = 0;
    foreach ($countRows as $cr) {
        $countTotal += intval($cr['cnt']);
        if ($cr['type'] === 'session') $countSession = intval($cr['cnt']);
        if ($cr['type'] === 'member')  $countMembership = intval($cr['cnt']);
    }
    try {
        $countBlocked = intval($pdo->query("SELECT COUNT(*) as cnt FROM blocked_rfids")->fetchColumn());
    } catch(Exception $e) {
        $countBlocked = 0;
    }
    try {
        $countArchived = intval($pdo->query("SELECT COUNT(*) as cnt FROM members_archived")->fetchColumn());
    } catch(Exception $e) {
        $countArchived = 0;
    }
} catch(Exception $e) { $countTotal = $countSession = $countMembership = 0; $countBlocked = $countArchived = 0; }
?>
        <div class="member-stats" id="memberStats">
            <div class="ms-chip"><span class="ms-dot total"></span><span class="ms-num" id="statTotal"><?= $countTotal ?></span><span class="ms-lbl">&nbsp;Total</span></div>
            <div class="ms-chip"><span class="ms-dot session"></span><span class="ms-num" id="statSession"><?= $countSession ?></span><span class="ms-lbl">&nbsp;Session</span></div>
            <div class="ms-chip"><span class="ms-dot membership"></span><span class="ms-num" id="statMembership"><?= $countMembership ?></span><span class="ms-lbl">&nbsp;Membership</span></div>
            <div class="ms-chip"><span class="ms-dot blocked"></span><span class="ms-num" id="statBlocked"><?= $countBlocked ?></span><span class="ms-lbl">&nbsp;Blocked</span></div>
            <div class="ms-chip"><span class="ms-dot archived"></span><span class="ms-num" id="statArchived"><?= $countArchived ?></span><span class="ms-lbl">&nbsp;Archived</span></div>
        </div>
        <?php
$search = $_GET['search'] ?? '';

$typeFilter = $_GET['type'] ?? '';

try {
    $query = "SELECT * FROM members WHERE 1";
    $params = [];
    if ($search !== '') {
        $query .= " AND username LIKE ?";
        $params[] = "%$search%";
    }
    if ($typeFilter !== '') {
        $query .= " AND type = ?";
        $params[] = $typeFilter;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $members = $stmt->fetchAll();

} catch (Exception $e) {
    echo '<div style="color:red">Error fetching members: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $members = [];
}
?>

        <div style="margin-bottom:15px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
    <input type="text" id="searchUsername" placeholder="Search username..."
        style="padding:8px 12px;border-radius:8px;border:1px solid #ccc;width:250px;">
    <select id="typeFilter" style="padding:8px 12px;border-radius:8px;border:1px solid #ccc;">
        <option value="">All Types</option>
        <option value="session">Session</option>
        <option value="member">Member</option>
    </select>
    <div style="margin-left:auto; display:flex; gap:8px;">
        <button type="button" id="btnArchiveHistory" class="tab-btn" style="padding:8px 18px; border-radius:8px; border:none; background:#f57c00; color:#fff; font-weight:600; cursor:pointer;">📋 Archive History</button>
        <button type="button" id="btnBlockList" class="tab-btn" style="padding:8px 18px; border-radius:8px; border:none; background:#d32f2f; color:#fff; font-weight:600; cursor:pointer;">🚫 Block List</button>
        <button type="button" id="btnLookupRfid" class="tab-btn" style="padding:8px 18px; border-radius:8px; border:none; background:#1976d2; color:#fff; font-weight:600; cursor:pointer;">🔍 RFID Scan</button>
        <button type="button" id="addUserBtn" class="tab-btn" style="padding:8px 18px; border-radius:8px; border:none; background:#1976d2; color:#fff; font-weight:600; cursor:pointer;">+ Add Member</button>
    </div>
</div>
        <div style="margin-top:24px; background:#2b2b2b; padding:16px; border-radius:14px;" id="memberSection">
            <h3 style="margin:0 0 12px; color:#fff;">Member List</h3>
            <div class="table-wrapper" id="memberTableWrapper" style="margin-top: 0;">
                <table class="admin-table" border="0" cellpadding="8" cellspacing="0" style="width:100%; background:transparent;">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>RFID</th>
                            <th>Joined Date</th>
                            <th>Expiry Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                <tbody>
                <?php if (empty($members)): ?>
                    <tr><td colspan="6" style="text-align: center;" >No members found.</td></tr>
                <?php else: ?>
                    <?php foreach ($members as $member): ?>
                        <?php
                            $username = trim($member['first_name'] . ' ' . $member['last_name']);
                            $joined = $member['Joined_Date'] ? date('d M Y', strtotime($member['Joined_Date'])) : '-';
                            $expiryDate = (!empty($member['membership_expiry']) && $member['type'] === 'member')
                                ? date('d M Y', strtotime($member['membership_expiry']))
                                : '-';
                            $isExpired = false;
                            if ($member['type'] === 'member' && !empty($member['membership_expiry'])) {
                                $isExpired = strtotime($member['membership_expiry']) < strtotime(date('Y-m-d'));
                            }
                            $rowClass = $isExpired ? 'expired-membership' : '';
                            $typeLabel = $member['type'] === 'session'
                                ? '<span class="badge-type session">Session</span>'
                                : '<span class="badge-type membership">Membership</span>';
                            $avatarColors = ['#1976d2','#e53935','#388e3c','#f57c00','#7b1fa2','#00838f','#c62828','#2e7d32','#1565c0','#6a1b9a'];
                            $firstLetter = strtoupper(substr($member['first_name'], 0, 1)) ?: '?';
                            $avatarColor = $avatarColors[ord($firstLetter) % count($avatarColors)];
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td data-label="Username">
                                <div class="user-cell">
                                    <div class="user-avatar" style="background:<?= $avatarColor ?>"><?= htmlspecialchars($firstLetter) ?></div>
                                    <span class="user-name-text"><?= htmlspecialchars($username) ?></span>
                                </div>
                            </td>
                            <td data-label="Email"><?= htmlspecialchars($member['gmail']) ?></td>
                            <td data-label="Type"><?= $typeLabel ?></td>
                            <td data-label="RFID"><?= htmlspecialchars($member['RFID'] ?? '-') ?></td>
                            <td data-label="Joined Date"><?= htmlspecialchars($joined) ?></td>
                            <td data-label="Expiry Date"><?= htmlspecialchars($expiryDate) ?></td>
                            <td data-label="Actions">
                                <div class="action-group">
                                    <button type="button" class="action-btn btn-edit-member"
                                        data-id="<?= $member['id'] ?>"
                                        data-first="<?= htmlspecialchars($member['first_name'], ENT_QUOTES) ?>"
                                        data-last="<?= htmlspecialchars($member['last_name'], ENT_QUOTES) ?>"
                                        data-gmail="<?= htmlspecialchars($member['gmail'] ?? '', ENT_QUOTES) ?>"
                                        data-phone="<?= htmlspecialchars($member['phone'] ?? '', ENT_QUOTES) ?>"
                                        data-address="<?= htmlspecialchars($member['address'] ?? '', ENT_QUOTES) ?>"
                                        data-rfid="<?= htmlspecialchars($member['RFID'] ?? '', ENT_QUOTES) ?>"
                                        data-type="<?= htmlspecialchars($member['type'], ENT_QUOTES) ?>">Edit</button>
                                    <button type="button" class="action-btn delete btn-delete-member"
                                        data-id="<?= $member['id'] ?>"
                                        data-name="<?= htmlspecialchars(trim($member['first_name'].' '.$member['last_name']), ENT_QUOTES) ?>">Archive</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                </table>
            </div>
        </div>

        <!-- Blocked RFID Table -->
        <div style="margin-top:24px; background:#2b2b2b; padding:16px; border-radius:14px; display:none;" id="blockedRfidSection">
            <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin:0; color:#fff;">Blocked RFID List</h3>
                <button type="button" id="btnBackFromBlockList" style="padding:8px 18px; border-radius:8px; border:none; background:#666; color:#fff; font-weight:600; cursor:pointer;">← Back to Members</button>
            </div>
            <div class="table-wrapper" style="margin-top:0;" id="blockedRfidWrapper">
                <table class="admin-table" border="0" cellpadding="8" cellspacing="0" style="width:100%; background:transparent;">
                    <thead>
                        <tr>
                            <th>RFID</th>
                            <th>Member Name</th>
                            <th>Blocked Date</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody id="blockedRfidTableBody">
                        <tr><td colspan="4" style="text-align: center;">Loading blocked RFIDs...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Archive Table -->
        <div style="margin-top:24px; background:#2b2b2b; padding:16px; border-radius:14px; display:none;" id="archiveSection">
            <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin:0; color:#fff;">Archive History List</h3>
                <button type="button" id="btnBackToMembers" style="padding:8px 18px; border-radius:8px; border:none; background:#666; color:#fff; font-weight:600; cursor:pointer;">← Back to Members</button>
            </div>
            <div class="table-wrapper" style="margin-top:0;" id="archiveTableWrapper">
                <table class="admin-table" border="0" cellpadding="8" cellspacing="0" style="width:100%; background:transparent;">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>RFID</th>
                            <th>Archived At</th>
                            <th>Archived By</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                <tbody id="archiveTableBody">
                    <tr><td colspan="9" style="text-align: center;">Loading archived members...</td></tr>
                </tbody>
            </table>
        </div>
        
    </div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal-box">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px;">
            <div>
                <h2 id="addUserModalTitle" style="margin: 0;">Add User</h2>
                <p id="addUserModalSubtitle" class="modal-subtitle" style="margin: 4px 0 0;">Select registration type</p>
            </div>
            <button type="button" class="btn-back" id="btnBackMembership" style="margin: 0; display: none;">&#8592; Back</button>
        </div>

        <!-- Type selection cards -->
        <div id="typeSelection">
            <div class="type-cards">
                <div class="type-card session" id="btnSessionCard">
                    <div class="card-icon">&#127939;</div>
                    Session
                </div>
                <div class="type-card membership" id="btnMembershipCard">
                    <div class="card-icon">&#127942;</div>
                    Membership
                </div>
            </div>
        </div>

        <!-- Session Registration Form -->
        <div class="modal-form" id="sessionForm">
            
            <form id="sessionFormEl">
                <input type="hidden" name="add_user" value="1">
                <div class="form-row">
                    <div>
                        <label>First Name <span style="color:#e57373">*</span></label>
                        <input type="text" name="first_name" required placeholder="First name">
                    </div>
                    <div>
                        <label>Last Name <span style="color:#e57373">*</span></label>
                        <input type="text" name="last_name" required placeholder="Last name">
                    </div>
                </div>
                <label>Gmail <span style="color:#666"></span></label>
                <input type="email" name="gmail" required placeholder="example@gmail.com">
                <label>Phone <span style="color:#e57373">*</span></label>
                <input type="text" name="phone" required maxlength="11" pattern="\d{11}" title="11 digits only" placeholder="Phone number">
                <label>Address <span style="color:#e57373">*</span></label>
                <input type="text" name="address" required placeholder="Address">
                <label class="avail-card-toggle">
                    <input type="checkbox" id="availCardCheck" name="avail_card" value="1">
                    <span>Avail RFID Card?</span>
                </label>
                <div id="rfidFieldWrap" style="display:none;">
                    <input type="hidden" name="rfid_number" id="rfidNumber">
                    <input class="rfid-hidden-input" id="rfidScanInput" autocomplete="off" tabindex="-1">
                    <button type="button" class="rfid-tap-btn" id="btnTapRfidSession">
                        <span class="rfid-icon">&#128276;</span>
                        <span id="rfidSessionBtnTxt">Tap RFID Card to Scan</span>
                    </button>
                    <div class="rfid-captured" id="rfidSessionCaptured">
                        <span>&#10003;</span>
                        <span class="rfid-val" id="rfidSessionVal"></span>
                        <button type="button" class="rfid-clear" id="rfidSessionClear" title="Remove">&#10005;</button>
                    </div>
                </div>
              
                <div class="modal-actions">
                    <button type="submit" class="btn-submit">Register</button>
                    <button type="button" class="btn-cancel-modal" id="btnCloseModal">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Membership Registration Form -->
        <div class="modal-form" id="membershipForm">
            
            <form method="post" id="membershipFormEl">
                <input type="hidden" name="add_membership_user" value="1">
                <input type="hidden" name="plan_months" id="selectedPlanMonths" value="">
                <div class="form-row">
                    <div>
                        <label>First Name <span style="color:#e57373">*</span></label>
                        <input type="text" name="first_name" required placeholder="First name">
                    </div>
                    <div>
                        <label>Last Name <span style="color:#e57373">*</span></label>
                        <input type="text" name="last_name" required placeholder="Last name">
                    </div>
                </div>
                <label>Gmail <span style="color:#666"></span></label>
                <input type="email" name="gmail" required placeholder="example@gmail.com">
                <label>Phone <span style="color:#e57373">*</span></label>
                <input type="text" name="phone" required maxlength="11" pattern="\d{11}" title="11 digits only" placeholder="Phone number">
                <label>Address <span style="color:#e57373">*</span></label>
                <input type="text" name="address" required placeholder="Address">
                <label>RFID Card <span style="color:#e57373">*</span></label>
                <input type="hidden" name="rfid_number" id="membershipRfidNumber">
                <input class="rfid-hidden-input" id="membershipRfidScanInput" autocomplete="off" tabindex="-1">
                <button type="button" class="rfid-tap-btn" id="btnTapRfidMembership">
                    <span class="rfid-icon">&#128276;</span>
                    <span id="rfidMembershipBtnTxt">Tap RFID Card to Scan</span>
                </button>
                <div class="rfid-captured" id="rfidMembershipCaptured">
                    <span>&#10003;</span>
                    <span class="rfid-val" id="rfidMembershipVal"></span>
                    <button type="button" class="rfid-clear" id="rfidMembershipClear" title="Remove">&#10005;</button>
                </div>
                <label>Monthly Plan <span style="color:#e57373">*</span></label>
                <div class="plan-cards">
                    <div class="plan-card" data-months="1" data-price="850">
                        <div class="plan-duration">1 Month</div>
                        <div class="plan-price">&#8369;850</div>
                    </div>
                    <div class="plan-card" data-months="3" data-price="1800">
                        <div class="plan-duration">3 Months</div>
                        <div class="plan-price">&#8369;1,800</div>
                    </div>
                    <div class="plan-card" data-months="5" data-price="2500">
                        <div class="plan-duration">5 Months</div>
                        <div class="plan-price">&#8369;2,500</div>
                    </div>
                </div>
               
                <div class="modal-actions">
                    <button type="submit" class="btn-submit" id="btnMembershipSubmit">Register</button>
                    <button type="button" class="btn-cancel-modal" id="btnCloseMembershipModal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- RFID Lookup Modal -->
<div class="modal-overlay" id="rfidLookupModal">
    <div class="modal-box">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px;">
            <div>
                <h2 style="margin: 0;">RFID Member Lookup</h2>
                <p class="modal-subtitle" style="margin: 4px 0 0;">Scan an RFID card to show member details.</p>
            </div>
            <button type="button" class="btn-back" id="btnCloseRfidLookupModal" style="margin: 0;">&#10005;</button>
        </div>
        <input type="hidden" id="rfidLookupNumber">
        <input class="rfid-hidden-input" id="rfidLookupScanInput" autocomplete="off" tabindex="-1">
        <button type="button" class="rfid-tap-btn" id="btnTapRfidLookup">
            <span class="rfid-icon">&#128276;</span>
            <span id="rfidLookupBtnTxt">Tap RFID Card to Scan</span>
        </button>
        <div class="rfid-captured" id="rfidLookupCaptured" style="display: none;">
            <span>&#10003;</span>
            <span class="rfid-val" id="rfidLookupVal"></span>
            <button type="button" class="rfid-clear" id="rfidLookupClear" title="Remove">&#10005;</button>
        </div>
        <p id="rfidLookupStatus" style="margin: 16px 0 12px; color: #bbb;">Ready to scan RFID card.</p>
        <div id="rfidLookupMemberDetails" style="display: none;">
            <button type="button" id="btnRenewFromLookup" style="display:none; margin-bottom:12px; padding:8px 14px; border:none; border-radius:8px; background:#f57c00; color:#fff; font-weight:600; cursor:pointer;">Select Action</button>
            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;">
                <div><strong>Name</strong><div id="rfidLookupMemberName" style="margin-top:4px;color:#fff;">-</div></div>
                <div><strong>Username</strong><div id="rfidLookupMemberUsername" style="margin-top:4px;color:#fff;">-</div></div>
                <div><strong>Email</strong><div id="rfidLookupMemberEmail" style="margin-top:4px;color:#fff;">-</div></div>
                <div><strong>Phone</strong><div id="rfidLookupMemberPhone" style="margin-top:4px;color:#fff;">-</div></div>
                <div><strong>Type</strong><div id="rfidLookupMemberType" style="margin-top:4px;color:#fff;">-</div></div>
                <div><strong>RFID</strong><div id="rfidLookupMemberRfid" style="margin-top:4px;color:#fff;">-</div></div>
                <div><strong>Joined</strong><div id="rfidLookupMemberJoinDate" style="margin-top:4px;color:#fff;">-</div></div>
                <div><strong>Expiry</strong><div id="rfidLookupMemberExpiry" style="margin-top:4px;color:#fff;">-</div></div>
                <div style="grid-column: span 2;"><strong>Credit</strong><div id="rfidLookupMemberCredit" style="margin-top:4px;color:#fff;">-</div></div>
            </div>
        </div>
    </div>
</div>

<!-- Renew Membership Modal -->
<div class="modal-overlay" id="renewMembershipModal">
    <div class="modal-box">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px;">
            <div>
                <h2 style="margin:0;">Renew Membership</h2>
                <p id="renewMemberLabel" class="modal-subtitle" style="margin:4px 0 0;">Choose a plan and payment method.</p>
            </div>
            <button type="button" class="btn-back" id="btnCloseRenewModal" style="margin:0;">&#10005;</button>
        </div>
        <input type="hidden" id="renewMemberId">
        <input type="hidden" id="renewMemberRfid">
        <input type="hidden" id="renewMemberCreditValue" value="0">
        <div style="margin-bottom:12px; color:#bbb; font-size:14px;">
            <div><strong>Member:</strong> <span id="renewMemberName">-</span></div>
            <div><strong>Current Credit:</strong> <span id="renewMemberCredit">₱0.00</span></div>
        </div>
        <label style="font-size:13px; color:#bbb; display:block; margin-bottom:8px;">Monthly Plan</label>
        <div class="plan-cards">
            <div class="plan-card renew-plan-card" data-months="1" data-price="850">
                <div class="plan-duration">1 Month</div>
                <div class="plan-price">₱850</div>
            </div>
            <div class="plan-card renew-plan-card" data-months="3" data-price="1800">
                <div class="plan-duration">3 Months</div>
                <div class="plan-price">₱1,800</div>
            </div>
            <div class="plan-card renew-plan-card" data-months="5" data-price="2500">
                <div class="plan-duration">5 Months</div>
                <div class="plan-price">₱2,500</div>
            </div>
        </div>
        <label style="font-size:13px; color:#bbb; display:block; margin-bottom:8px;">Payment Method</label>
        <div style="display:flex; gap:10px; margin-bottom:10px;">
            <button type="button" class="btn-submit" id="renewPayCashBtn" style="flex:1; background:#388e3c;">Cash</button>
            <button type="button" class="btn-submit" id="renewPayCreditBtn" style="flex:1; background:#f57c00;">Use Credit</button>
        </div>
        <div id="renewValidationMessage" style="display:none; margin-bottom:12px; color:#ff8a80; font-size:13px;"></div>
        <div class="modal-actions">
            <button type="button" class="btn-submit" id="btnConfirmRenew">Renew Membership</button>
            <button type="button" class="btn-cancel-modal" id="btnCancelRenew">Cancel</button>
        </div>
    </div>
</div>

<!-- Renewal Success Modal -->
<div class="modal-overlay" id="renewSuccessModal">
    <div class="modal-box" style="max-width:360px; text-align:center;">
        <div style="font-size:42px; margin-bottom:10px;">✅</div>
        <h3 style="margin:0 0 8px; color:#fff;">Renewal Successful</h3>
        <p id="renewSuccessMessage" style="margin:0; color:#bbb;">Membership renewed successfully.</p>
    </div>
</div>

<!-- Confirm Modal -->
<div class="modal-overlay" id="confirmModal">
    <div class="confirm-box">
        <h3 id="confirmTitle">Confirm</h3>
        <p id="confirmMessage">Are you sure?</p>
        <div class="modal-actions">
            <button type="button" class="btn-danger" id="btnConfirmYes">Yes, Proceed</button>
            <button type="button" class="btn-cancel-modal" id="btnConfirmNo">Cancel</button>
        </div>
    </div>
</div>

<!-- Outcome Modal -->
<div class="modal-overlay" id="actionOutcomeModal">
    <div class="modal-box" style="max-width: 360px; text-align: center;">
        <div id="actionOutcomeIcon" style="font-size: 42px; margin-bottom: 10px;">✅</div>
        <h3 id="actionOutcomeTitle" style="margin: 0 0 8px; color: #fff;">Success</h3>
        <p id="actionOutcomeMessage" style="margin: 0; color: #bbb;">Action completed successfully.</p>
        <div class="modal-actions" style="margin-top: 16px;">
            <button type="button" class="btn-submit" id="btnCloseActionOutcome">Close</button>
        </div>
    </div>
</div>

<!-- Edit Member Modal -->
<div class="modal-overlay" id="editMemberModal">
    <div class="modal-box">
        <h2>Edit Member</h2>
        <input type="hidden" id="editMemberId">
        <input type="hidden" id="editOldRfid">
        <div class="modal-form active">
            <div class="form-row">
                <div>
                    <label>First Name <span style="color:#e57373">*</span></label>
                    <input type="text" id="editMemberFirst" placeholder="First name">
                </div>
                <div>
                    <label>Last Name <span style="color:#e57373">*</span></label>
                    <input type="text" id="editMemberLast" placeholder="Last name">
                </div>
            </div>
            <label>Gmail</label>
            <input type="email" id="editMemberGmail" placeholder="example@gmail.com">
            <label>Phone</label>
            <input type="text" id="editMemberPhone" placeholder="Phone number">
            <label>Address</label>
            <input type="text" id="editMemberAddress" placeholder="Address">
            <label id="editRfidLabel" style="display:none;">RFID Card</label>
            <!-- Current RFID row (shown when member already has RFID) -->
            <div id="editCurrentRfidRow" style="display:none; align-items:center; gap:10px; margin-bottom:12px;">
                <div class="rfid-captured" style="display:flex; flex:1; margin-bottom:0;">
                    <span>&#10003;</span>
                    <span class="rfid-val" id="editCurrentRfidVal" style="margin-left:6px;"></span>
                </div>
                <button type="button" id="btnChangeRfid" style="padding:6px 14px; border-radius:8px; border:1px solid #f57c00; background:none; color:#f57c00; cursor:pointer; font-size:13px; font-weight:600; white-space:nowrap;">Change Card</button>
            </div>
            <!-- New RFID tap scanner -->
            <div id="editRfidTapWrap" style="display:none;">
                <input type="hidden" id="editNewRfid">
                <input class="rfid-hidden-input" id="editRfidScanInput" autocomplete="off" tabindex="-1">
                <button type="button" class="rfid-tap-btn" id="btnTapEditRfid">
                    <span class="rfid-icon">&#128276;</span>
                    <span id="editRfidBtnTxt">Tap RFID Card</span>
                </button>
                <div class="rfid-captured" id="editNewRfidCaptured">
                    <span>&#10003;</span>
                    <span class="rfid-val" id="editNewRfidVal" style="margin-left:6px;"></span>
                    <button type="button" class="rfid-clear" id="editNewRfidClear" title="Remove">&#10005;</button>
                </div>
                <!-- Block lost card (only when replacing existing RFID) -->
                <div id="blockLostCardRow" style="display:none;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; color:#bbb; margin-bottom:12px;">
                        <input type="checkbox" id="blockOldRfidCheck" checked style="width:auto!important; margin:0;">
                        <span>Block lost card <span id="blockOldRfidLabel" style="color:#f5c518; font-weight:600;"></span></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-actions" style="margin-top:16px;">
            <button type="button" class="btn-submit" id="btnSaveMember">Save Changes</button>
            <button type="button" class="btn-cancel-modal" id="btnCloseEditMember">Cancel</button>
        </div>
    </div>
</div>

<!-- Archive Member Modal -->
<div class="modal-overlay" id="archiveModal">
    <div class="modal-box">
        <input type="hidden" id="archiveMemberId">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px;">
            <div>
                <h2 style="margin: 0;">Archive Member</h2>
                <p style="margin: 4px 0 0; color: #888; font-size: 13px;">Archive <strong id="archiveMemberName"></strong></p>
            </div>
            <button type="button" style="border: none; background: #2a2a2a; color: #fff; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; font-size: 22px;" id="btnCloseArchiveModal">&times;</button>
        </div>
        <div style="margin: 16px 0;">
            <label style="font-size: 13px; color: #bbb; font-weight: 600; display: block; margin-bottom: 8px;">Reason for archiving</label>
            <textarea id="archiveReason" placeholder="e.g., Moved away, Inactive, etc." style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #444; background: #1a1a1a; color: #fff; resize: vertical; min-height: 80px; font-family: inherit; font-size: 13px;"></textarea>
        </div>
        <div class="modal-actions" style="margin-top: 16px;">
            <button type="button" class="btn-submit" id="btnConfirmArchive">Archive Member</button>
            <button type="button" class="btn-cancel-modal" id="btnCancelArchive">Cancel</button>
        </div>
    </div>
</div>

<!-- Archived Members History Modal -->
<div class="modal-overlay" id="archiveHistoryModal">
    <div class="modal-box" style="max-width: 600px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
            <h2 style="margin: 0;">Archived Members History</h2>
            <button type="button" style="border: none; background: #2a2a2a; color: #fff; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; font-size: 22px;" id="btnCloseArchiveHistory">&times;</button>
        </div>
        <div style="max-height: 400px; overflow-y: auto; border: 1px solid #333; border-radius: 8px; background: rgba(0,0,0,0.2);">
            <table id="archiveHistoryTable" style="width: 100%; border-collapse: collapse;">
                <thead style="position: sticky; top: 0; background: #2a2a2a;">
                    <tr>
                        <th style="padding: 10px; text-align: left; border-bottom: 1px solid #444; font-size: 13px; color: #aaa;">Name</th>
                        <th style="padding: 10px; text-align: left; border-bottom: 1px solid #444; font-size: 13px; color: #aaa;">Type</th>
                        <th style="padding: 10px; text-align: left; border-bottom: 1px solid #444; font-size: 13px; color: #aaa;">Archived By</th>
                        <th style="padding: 10px; text-align: left; border-bottom: 1px solid #444; font-size: 13px; color: #aaa;">Date</th>
                        <th style="padding: 10px; text-align: center; border-bottom: 1px solid #444; font-size: 13px; color: #aaa;">Action</th>
                    </tr>
                </thead>
                <tbody id="archiveHistoryTableBody">
                    <tr><td colspan="5" style="text-align: center; padding: 20px; color: #777;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function(){

    // ── Toast ─────────────────────────────────────────────────────────
    var toastTimer;
    function showToast(message, type) {
        type = type || 'error';
        var toast = document.getElementById('toastNotif');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toastNotif';
            toast.className = 'toast';
            document.body.appendChild(toast);
        }
        toast.className = 'toast' + (type === 'success' ? ' success' : '');
        toast.textContent = message;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function() { toast.classList.remove('show'); }, 3500);
    }

    // ── Confirm Modal ─────────────────────────────────────────────────
    var confirmCallback = null;
    function showConfirm(title, message, callback) {
        $('#confirmTitle').text(title);
        $('#confirmMessage').text(message);
        confirmCallback = callback;
        $('#confirmModal').addClass('active');
    }

    function showOutcomeModal(success, title, message) {
        $('#actionOutcomeTitle').text(title || (success ? 'Success' : 'Failed'));
        $('#actionOutcomeMessage').text(message || (success ? 'Action completed successfully.' : 'Action could not be completed.'));
        $('#actionOutcomeIcon').text(success ? '✅' : '⚠️');
        $('#actionOutcomeModal').addClass('active');
    }

    $('#btnCloseActionOutcome').on('click', function() {
        $('#actionOutcomeModal').removeClass('active');
    });
    $('#actionOutcomeModal').on('click', function(e) {
        if (e.target === this) { $(this).removeClass('active'); }
    });
    $('#btnConfirmYes').on('click', function() {
        $('#confirmModal').removeClass('active');
        if (confirmCallback) { confirmCallback(); confirmCallback = null; }
    });
    $('#btnConfirmNo').on('click', function() {
        $('#confirmModal').removeClass('active');
        confirmCallback = null;
    });
    $('#confirmModal').on('click', function(e) {
        if (e.target === this) { $(this).removeClass('active'); confirmCallback = null; }
    });

    // ── Fetch / reload table ──────────────────────────────────────────
    function bindMemberActionButtons() {
        $(document).off('click.memberActions', '.btn-delete-member')
            .on('click.memberActions', '.btn-delete-member', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var id   = $(this).data('id');
                var name = $(this).data('name');
                $('#archiveMemberId').val(id);
                $('#archiveMemberName').text(name);
                $('#archiveReason').val('');
                $('#archiveModal').addClass('active');
            });

        $(document).off('click.memberActions', '.btn-edit-member')
            .on('click.memberActions', '.btn-edit-member', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var d = $(this).data();
                $('#editMemberId').val(d.id);
                $('#editOldRfid').val(d.rfid || '');
                $('#editMemberFirst').val(d.first);
                $('#editMemberLast').val(d.last);
                $('#editMemberGmail').val(d.gmail);
                $('#editMemberPhone').val(d.phone);
                $('#editMemberAddress').val(d.address);
                resetRfidScanner('edit');
                var isSessionWithoutRfid = (d.type || '').toLowerCase() === 'session' && !d.rfid;
                if (d.rfid) {
                    $('#editRfidLabel').show();
                    $('#editCurrentRfidVal').text(d.rfid);
                    $('#editCurrentRfidRow').css('display', 'flex');
                    $('#editRfidTapWrap').hide();
                    $('#blockLostCardRow').hide();
                } else if (isSessionWithoutRfid) {
                    $('#editRfidLabel').hide();
                    $('#editCurrentRfidRow').hide();
                    $('#editRfidTapWrap').hide();
                    $('#blockLostCardRow').hide();
                } else {
                    $('#editRfidLabel').show();
                    $('#editCurrentRfidRow').hide();
                    $('#editRfidTapWrap').show();
                    $('#blockLostCardRow').hide();
                }
                $('#editMemberModal').addClass('active');
            });
    }

    function fetchMembers() {
        $.ajax({
            url: 'member.php',
            method: 'GET',
            data: { search: $('#searchUsername').val(), type: $('#typeFilter').val() },
            success: function(data) {
                var memberBody = $(data).find('#memberTableWrapper tbody').html();
                if (memberBody !== undefined) {
                    $('#memberTableWrapper tbody').html(memberBody);
                }
                bindMemberActionButtons();
                var chips = $(data).find('#memberStats');
                if (chips.length) {
                    $('#statTotal').text(chips.find('#statTotal').text());
                    $('#statSession').text(chips.find('#statSession').text());
                    $('#statMembership').text(chips.find('#statMembership').text());
                }
            }
        });
    }
    $('#searchUsername').on('keyup', fetchMembers);
    $('#typeFilter').on('change', fetchMembers);
    bindMemberActionButtons();

    // ── Archive member ────────────────────────────────────────────────

    $('#btnConfirmArchive').on('click', function() {
        var id = $('#archiveMemberId').val();
        var reason = $('#archiveReason').val().trim() || 'No reason provided';
        $.ajax({
            url: 'member.php', method: 'POST',
            data: { ajax_archive_member: 1, id: id, reason: reason },
            dataType: 'json',
            success: function(res) {
                $('#archiveModal').removeClass('active');
                if (res.success) {
                    showOutcomeModal(true, 'Member Archived', res.message || 'Member archived successfully.');
                    fetchMembers();
                } else {
                    showOutcomeModal(false, 'Archive Failed', res.message || 'Unable to archive the member.');
                }
            },
            error: function() { showOutcomeModal(false, 'Archive Failed', 'Server error while archiving the member.'); }
        });
    });

    $('#btnCloseArchiveModal, #btnCancelArchive').on('click', function() {
        $('#archiveModal').removeClass('active');
    });

    $('#archiveModal').on('click', function(e) {
        if (e.target === this) { $(this).removeClass('active'); }
    });

    // "Change Card" in edit modal
    $('#btnChangeRfid').on('click', function() {
        $('#editCurrentRfidRow').hide();
        $('#editRfidTapWrap').show();
        var oldRfid = $('#editOldRfid').val();
        if (oldRfid) {
            $('#blockOldRfidLabel').text('(' + oldRfid + ')');
            $('#blockLostCardRow').show();
        }
    });

    $('#btnSaveMember').on('click', function() {
        var firstName = $('#editMemberFirst').val().trim();
        var lastName  = $('#editMemberLast').val().trim();
        if (!firstName || !lastName) { showToast('First and last name are required.'); return; }
        var newRfid  = $('#editNewRfid').val();
        var oldRfid  = $('#editOldRfid').val();
        var blockOld = (newRfid && oldRfid && $('#blockOldRfidCheck').is(':checked')) ? '1' : '0';
        $.ajax({
            url: 'member.php', method: 'POST', dataType: 'json',
            data: {
                ajax_edit_member: 1,
                id:         $('#editMemberId').val(),
                first_name: firstName,
                last_name:  lastName,
                gmail:      $('#editMemberGmail').val().trim(),
                phone:      $('#editMemberPhone').val().trim(),
                address:    $('#editMemberAddress').val().trim(),
                new_rfid:   newRfid,
                old_rfid:   oldRfid,
                block_old:  blockOld
            },
            success: function(res) {
                if (res.success) {
                    $('#editMemberModal').removeClass('active');
                    showOutcomeModal(true, 'Member Updated', res.message || 'Member information updated successfully.');
                    fetchMembers();
                } else {
                    showOutcomeModal(false, 'Update Failed', res.message || 'Unable to update the member.');
                }
            },
            error: function() { showOutcomeModal(false, 'Update Failed', 'Server error while updating the member.'); }
        });
    });

    $('#btnCloseEditMember').on('click', function() {
        $('#editMemberModal').removeClass('active');
        resetRfidScanner('edit');
    });
    $('#editMemberModal').on('click', function(e) {
        if (e.target === this) { $(this).removeClass('active'); resetRfidScanner('edit'); }
    });

    // ── Renew membership ─────────────────────────────────────────────
    var selectedRenewPlan = null;
    var selectedRenewPayment = 'cash';
    var renewSuccessTimer = null;
    var currentRenewAction = 'membership_renewal';

    function setRenewActionContext(actionType, memberName) {
        currentRenewAction = actionType || 'membership_renewal';
        var title = 'Membership Renewal';
        var confirmLabel = 'Renew Membership';
        var successText = 'Membership renewed successfully.';
        var subtitle = 'Choose a plan and payment method.';
        if (currentRenewAction === 'new_membership') {
            title = 'New Membership';
            confirmLabel = 'Create Membership';
            successText = 'New membership created successfully.';
            subtitle = 'Create a new membership for this session user.';
        } else if (currentRenewAction === 'membership_extension') {
            title = 'Membership Extension';
            confirmLabel = 'Extend Membership';
            successText = 'Membership extended successfully.';
            subtitle = 'Extend the current membership for this member.';
        }
        $('#renewMembershipModal h2').text(title);
        $('#renewMemberLabel').text(subtitle);
        $('#btnConfirmRenew').text(confirmLabel);
        $('#renewSuccessMessage').text(successText);
        if (memberName) {
            $('#renewMemberName').text(memberName);
        }
    }
    function resetRenewSelection() {
        selectedRenewPlan = null;
        selectedRenewPayment = 'cash';
        $('.renew-plan-card').removeClass('selected');
        $('#renewPayCashBtn').css('opacity', '1');
        $('#renewPayCreditBtn').css('opacity', '0.7');
        $('#renewValidationMessage').hide().text('');
    }
    function getSelectedRenewPlanPrice() {
        return parseFloat($('.renew-plan-card.selected').data('price') || 0);
    }
    function validateRenewSelection() {
        $('#renewValidationMessage').hide().text('');
        if (selectedRenewPayment !== 'credit' || !selectedRenewPlan) {
            return true;
        }
        var credit = parseFloat($('#renewMemberCreditValue').val() || 0);
        var price = getSelectedRenewPlanPrice();
        if (credit < price) {
            $('#renewValidationMessage').text('Insufficient credit. Choose Cash or add more credit.').show();
            return false;
        }
        return true;
    }
    function showRenewSuccess(message) {
        $('#renewSuccessMessage').text(message || 'Membership renewed successfully.');
        $('#renewSuccessModal').addClass('active');
        if (renewSuccessTimer) clearTimeout(renewSuccessTimer);
        renewSuccessTimer = setTimeout(function() {
            $('#renewSuccessModal').removeClass('active');
        }, 5000);
    }
    $('.renew-plan-card').on('click', function() {
        $('.renew-plan-card').removeClass('selected');
        $(this).addClass('selected');
        selectedRenewPlan = $(this).data('months');
        validateRenewSelection();
    });
    $('#renewPayCashBtn').on('click', function() {
        selectedRenewPayment = 'cash';
        $('#renewPayCashBtn').css('opacity', '1');
        $('#renewPayCreditBtn').css('opacity', '0.7');
        $('#renewValidationMessage').hide().text('');
    });
    $('#renewPayCreditBtn').on('click', function() {
        selectedRenewPayment = 'credit';
        $('#renewPayCashBtn').css('opacity', '0.7');
        $('#renewPayCreditBtn').css('opacity', '1');
        validateRenewSelection();
    });
    $('#btnCloseRenewModal, #btnCancelRenew').on('click', function() {
        $('#renewMembershipModal').removeClass('active');
        $('#renewSuccessModal').removeClass('active');
        resetRenewSelection();
        if (renewSuccessTimer) clearTimeout(renewSuccessTimer);
    });
    $('#btnConfirmRenew').on('click', function() {
        if (!selectedRenewPlan) { showToast('Please select a monthly plan.'); return; }
        if (!validateRenewSelection()) { return; }
        var memberId = $('#renewMemberId').val();
        var rfid = $('#renewMemberRfid').val();
        $.ajax({
            url: 'member.php',
            method: 'POST',
            dataType: 'json',
            data: {
                ajax_renew_membership: 1,
                member_id: memberId,
                rfid: rfid,
                plan_months: selectedRenewPlan,
                payment_method: selectedRenewPayment
            },
            success: function(res) {
                $('#renewMembershipModal').removeClass('active');
                if (res.success) {
                    showOutcomeModal(true, 'Membership Updated', res.message || 'Membership updated successfully.');
                    fetchMembers();
                } else {
                    showOutcomeModal(false, 'Action Failed', res.message || 'Unable to complete the membership action.');
                }
            },
            error: function() { showOutcomeModal(false, 'Action Failed', 'Server error while processing the membership action.'); }
        });
    });

    // ── Add User modal ────────────────────────────────────────────────
    function showAddUserSelection() {
        $('#typeSelection').show();
        $('#sessionForm').removeClass('active');
        $('#membershipForm').removeClass('active');
        $('#addUserModalTitle').text('Add User');
        $('#addUserModalSubtitle').text('Select registration type');
        $('#btnBackMembership').show();
    }
    function showAddUserForm(type) {
        $('#typeSelection').hide();
        $('#sessionForm').toggleClass('active', type === 'session');
        $('#membershipForm').toggleClass('active', type === 'membership');
        $('#addUserModalTitle').text(type === 'session' ? 'Session Registration' : 'Membership Registration');
        $('#addUserModalSubtitle').text('');
        $('#btnBackMembership').show();
    }
    $('#addUserBtn').on('click', function() {
        $('#addUserModal').addClass('active');
        showAddUserSelection();
    });
    $('#btnSessionCard').on('click', function() { showAddUserForm('session'); });
    $('#btnMembershipCard').on('click', function() { showAddUserForm('membership'); });
    $('#btnBack').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        showAddUserSelection();
    });
    $('#btnBackMembership').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if ($('#typeSelection').is(':visible')) {
            closeAddModal(true);
            return;
        }
        showAddUserSelection();
        resetRfidScanner('session');
        resetRfidScanner('membership');
        $('#rfidFieldWrap').hide();
        $('#availCardCheck').prop('checked', false);
        $('.plan-card').removeClass('selected');
        $('#selectedPlanMonths').val('');
    });
    function closeAddModal(reloadPage = false) {
        $('#addUserModal').removeClass('active');
        resetRfidScanner('session');
        resetRfidScanner('membership');
        $('#rfidFieldWrap').hide();
        $('#availCardCheck').prop('checked', false);
        $('.plan-card').removeClass('selected');
        $('#selectedPlanMonths').val('');
        showAddUserSelection();
        if (reloadPage) { location.reload(); }
    }
    $('#btnCloseModal').on('click', function() { closeAddModal(true); });
    $('#btnCloseMembershipModal').on('click', function() { closeAddModal(true); });
    $('#addUserModal').on('click', function(e) { if (e.target === this) closeAddModal(true); });

    // Session form — AJAX submit
    $('#sessionFormEl').on('submit', function(e) {
        e.preventDefault();
        var firstName = $(this).find('[name=first_name]').val().trim();
        var lastName = $(this).find('[name=last_name]').val().trim();
        var phone = $(this).find('[name=phone]').val().trim();
        var address = $(this).find('[name=address]').val().trim();
        var rfid = $('#rfidNumber').val().trim();
        if (!firstName || !lastName) { showToast('First and last name are required.'); return; }
        if (!phone) { showToast('Phone number is required.'); return; }
        if (!/^[0-9]{11}$/.test(phone)) { showToast('Phone number must be 11 digits with no symbols.'); return; }
        if (!address) { showToast('Address is required.'); return; }
        if ($('#availCardCheck').is(':checked') && !rfid) { showToast('Please scan the RFID card or uncheck Avail RFID Card.'); return; }
        $.ajax({
            url: 'member.php', method: 'POST', dataType: 'json',
            data: {
                ajax_add_user: 1,
                first_name:  firstName,
                last_name:   lastName,
                gmail:       $(this).find('[name=gmail]').val().trim(),
                phone:       phone,
                address:     address,
                rfid_number: rfid
            },
            success: function(res) {
                if (res.success) {
                    closeAddModal();
                    $('#sessionFormEl')[0].reset();
                    var msg = 'Session user ' + firstName + ' ' + lastName + ' registered successfully!';
                    if (rfid) msg += ' RFID access success.';
                    else msg += ' No RFID assigned.';
                    if (res.message) msg += ' ' + res.message;
                    showOutcomeModal(true, 'Session Registered', msg);
                    fetchMembers();
                } else {
                    showOutcomeModal(false, 'Registration Failed', res.message || 'Unable to register the session user.');
                }
            },
            error: function() { showOutcomeModal(false, 'Registration Failed', 'Server error while registering the session user.'); }
        });
    });

    // ── Membership modal ──────────────────────────────────────────────
    $('#btnMembershipCard').on('click', function() { showAddUserForm('membership'); });

    $(document).on('click', '.plan-card', function() {
        $('.plan-card').removeClass('selected');
        $(this).addClass('selected');
        $('#selectedPlanMonths').val($(this).data('months'));
    });

    $('#membershipFormEl').on('submit', function(e) {
        e.preventDefault();
        var firstName = $(this).find('[name=first_name]').val().trim();
        var lastName = $(this).find('[name=last_name]').val().trim();
        var phone = $(this).find('[name=phone]').val().trim();
        var address = $(this).find('[name=address]').val().trim();
        var planMonths = $('#selectedPlanMonths').val();
        var rfid = $('#membershipRfidNumber').val().trim();
        if (!firstName || !lastName) { showToast('First and last name are required.'); return; }
        if (!phone) { showToast('Phone number is required.'); return; }
        if (!/^[0-9]{11}$/.test(phone)) { showToast('Phone number must be 11 digits with no symbols.'); return; }
        if (!address) { showToast('Address is required.'); return; }
        if (!planMonths) { showToast('Please select a monthly plan.'); return; }
        if (!rfid) { showToast('Please scan the RFID card.'); return; }
        $.ajax({
            url: 'member.php', method: 'POST', dataType: 'json',
            data: {
                ajax_add_membership: 1,
                first_name:  firstName,
                last_name:   lastName,
                gmail:       $(this).find('[name=gmail]').val().trim(),
                phone:       phone,
                address:     address,
                rfid_number: rfid,
                plan_months: planMonths
            },
            success: function(res) {
                if (res.success) {
                    closeAddModal();
                    $('#membershipFormEl')[0].reset();
                    var msg = 'Member ' + firstName + ' ' + lastName + ' registered successfully! RFID access success.';
                    if (res.message) msg += ' ' + res.message;
                    showOutcomeModal(true, 'Membership Registered', msg);
                    fetchMembers();
                } else {
                    showOutcomeModal(false, 'Registration Failed', res.message || 'Unable to register the member.');
                }
            },
            error: function() { showOutcomeModal(false, 'Registration Failed', 'Server error while registering the member.'); }
        });
    });

    // ── RFID toggle (session) ─────────────────────────────────────────
    $('#availCardCheck').on('change', function() {
        if ($(this).is(':checked')) { $('#rfidFieldWrap').show(); }
        else { $('#rfidFieldWrap').hide(); $('#rfidNumber').val(''); resetRfidScanner('session'); }
    });

    // ── RFID Scanner engine ───────────────────────────────────────────
    function setupRfidScanner(triggerBtn, hiddenInput, scanInput, capturedDiv, valSpan, clearBtn, btnTxtSpan, captureCallback) {
        var scanning = false, rfidBuffer = '', rfidTimer = null;
        $(triggerBtn).on('click', function() {
            if (scanning) return;
            scanning = true; rfidBuffer = '';
            $(triggerBtn).addClass('scanning');
            $(btnTxtSpan).text('Scanning… Tap card now');
            $(scanInput).css({position:'fixed', top:'-9999px', left:'-9999px', opacity:0, width:'1px', height:'1px'}).focus();
        });
        $(scanInput).on('keydown', function(e) {
            if (!scanning) return;
            if (e.key === 'Enter') { e.preventDefault(); rfidBuffer.length > 0 ? captureRfid(rfidBuffer) : cancelScan(); return; }
            if (rfidTimer) clearTimeout(rfidTimer);
            rfidTimer = setTimeout(function() { if (rfidBuffer.length > 0) captureRfid(rfidBuffer); }, 500);
        });
        $(scanInput).on('input', function() { if (!scanning) return; rfidBuffer += $(this).val(); $(this).val(''); });
        function captureRfid(val) {
            scanning = false; rfidBuffer = ''; clearTimeout(rfidTimer);
            $(scanInput).val('').blur();
            $(triggerBtn).removeClass('scanning').hide();
            $(hiddenInput).val(val); $(valSpan).text(val);
            $(capturedDiv).css('display','flex');
            $(btnTxtSpan).text('Tap RFID Card to Scan');
            if (typeof captureCallback === 'function') {
                captureCallback(val);
            }
        }
        function cancelScan() {
            scanning = false; rfidBuffer = ''; clearTimeout(rfidTimer);
            $(scanInput).val('').blur(); $(triggerBtn).removeClass('scanning');
            $(btnTxtSpan).text('Tap RFID Card to Scan');
        }
        $(clearBtn).on('click', function() {
            $(hiddenInput).val(''); $(capturedDiv).hide(); $(triggerBtn).show();
            rfidBuffer = ''; scanning = false;
        });
    }

    function resetRfidScanner(type) {
        if (type === 'session') {
            $('#rfidNumber').val(''); $('#rfidSessionCaptured').hide();
            $('#btnTapRfidSession').show().removeClass('scanning');
            $('#rfidSessionBtnTxt').text('Tap RFID Card to Scan');
        } else if (type === 'membership') {
            $('#membershipRfidNumber').val(''); $('#rfidMembershipCaptured').hide();
            $('#btnTapRfidMembership').show().removeClass('scanning');
            $('#rfidMembershipBtnTxt').text('Tap RFID Card to Scan');
        } else if (type === 'edit') {
            $('#editNewRfid').val(''); $('#editNewRfidCaptured').hide();
            $('#btnTapEditRfid').show().removeClass('scanning');
            $('#editRfidBtnTxt').text('Tap RFID Card');
            $('#editRfidTapWrap').hide(); $('#editCurrentRfidRow').hide();
            $('#blockLostCardRow').hide(); $('#blockOldRfidCheck').prop('checked', true);
        } else if (type === 'lookup') {
            $('#rfidLookupNumber').val(''); $('#rfidLookupCaptured').hide();
            $('#btnTapRfidLookup').show().removeClass('scanning');
            $('#rfidLookupBtnTxt').text('Tap RFID Card to Scan');
            $('#rfidLookupStatus').text('Ready to scan RFID card.');
            $('#rfidLookupMemberDetails').hide();
        }
    }

    setupRfidScanner('#btnTapRfidSession', '#rfidNumber', '#rfidScanInput',
        '#rfidSessionCaptured', '#rfidSessionVal', '#rfidSessionClear', '#rfidSessionBtnTxt', null);
    setupRfidScanner('#btnTapRfidMembership', '#membershipRfidNumber', '#membershipRfidScanInput',
        '#rfidMembershipCaptured', '#rfidMembershipVal', '#rfidMembershipClear', '#rfidMembershipBtnTxt', null);
    setupRfidScanner('#btnTapEditRfid', '#editNewRfid', '#editRfidScanInput',
        '#editNewRfidCaptured', '#editNewRfidVal', '#editNewRfidClear', '#editRfidBtnTxt', null);
    setupRfidScanner('#btnTapRfidLookup', '#rfidLookupNumber', '#rfidLookupScanInput',
        '#rfidLookupCaptured', '#rfidLookupVal', '#rfidLookupClear', '#rfidLookupBtnTxt', handleLookupRfidScan);

    function setActiveTab(buttonId) {
        $('#btnArchiveHistory, #btnBlockList, #addUserBtn').removeClass('active');
        if (buttonId) {
            $('#' + buttonId).addClass('active');
        }
    }

    // ── Archive History ────────────────────────────────────────────────
    $('#btnArchiveHistory').on('click', function() {
        $('#memberSection').hide();
        $('#blockedRfidSection').hide();
        $('#archiveSection').show();
        setActiveTab('btnArchiveHistory');
        loadArchiveHistory();
    });

    $('#btnBackToMembers').on('click', function() {
        $('#archiveSection').hide();
        $('#memberSection').show();
        setActiveTab();
    });

    // ── Blocked RFIDs List ─────────────────────────────────────────────
    $('#btnBlockList').on('click', function() {
        $('#memberSection').hide();
        $('#archiveSection').hide();
        $('#blockedRfidSection').show();
        setActiveTab('btnBlockList');
        loadBlockedRfids();
    });

    $('#btnBackFromBlockList').on('click', function() {
        $('#blockedRfidSection').hide();
        $('#memberSection').show();
        setActiveTab();
    });

    function resetLookupHighlights() {
        $('#rfidLookupMemberType, #rfidLookupMemberCredit, #rfidLookupMemberExpiry').css({
            color: '#fff',
            fontWeight: '600'
        });
    }

    $('#btnLookupRfid').on('click', function() {
        resetRfidScanner('lookup');
        $('#rfidLookupStatus').text('Ready to scan RFID card.');
        $('#rfidLookupMemberDetails').hide();
        resetLookupHighlights();
        $('#rfidLookupModal').addClass('active');
    });

    $('#btnCloseRfidLookupModal').on('click', function() {
        $('#rfidLookupModal').removeClass('active');
        $('#btnRenewFromLookup').hide();
    });

    $('#btnRenewFromLookup').on('click', function() {
        var d = $(this).data();
        $('#renewMemberId').val(d.id);
        $('#renewMemberRfid').val(d.rfid || '');
        $('#renewMemberName').text(d.name || '-');
        $('#renewMemberCredit').text('₱' + parseFloat(d.credit || 0).toFixed(2));
        $('#renewMemberCreditValue').val(parseFloat(d.credit || 0));
        setRenewActionContext(d.actionType || 'membership_renewal', d.name || '-');
        resetRenewSelection();
        $('#rfidLookupModal').removeClass('active');
        $('#renewMembershipModal').addClass('active');
    });

    function handleLookupRfidScan(rfid) {
        $('#rfidLookupStatus').text('Looking up member…');
        $('#rfidLookupMemberDetails').hide();
        $.ajax({
            url: 'member.php',
            method: 'GET',
            data: { ajax_lookup_member_by_rfid: rfid },
            dataType: 'json',
            success: function(res) {
                if (res && res.member) {
                    var m = res.member;
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    var expiryDate = m.membership_expiry ? new Date(m.membership_expiry + 'T00:00:00') : null;
                    var isExpired = expiryDate && expiryDate < today;
                    var creditValue = parseFloat(m.credit || 0);

                    $('#rfidLookupStatus').text('Member found.');
                    $('#rfidLookupMemberName').text(m.first_name + ' ' + m.last_name);
                    $('#rfidLookupMemberUsername').text(m.username || '-');
                    $('#rfidLookupMemberEmail').text(m.gmail || '-');
                    $('#rfidLookupMemberPhone').text(m.phone || '-');
                    $('#rfidLookupMemberType').text(m.type || '-').css({
                        color: (m.type === 'member' ? '#f5c518 ' : '#1976d2')
                    });
                    $('#rfidLookupMemberRfid').text(m.RFID || '-');
                    $('#rfidLookupMemberJoinDate').text(m.Joined_Date ? new Date(m.Joined_Date).toLocaleDateString() : '-');
                    $('#rfidLookupMemberExpiry').text(m.membership_expiry ? new Date(m.membership_expiry).toLocaleDateString() : '-').css({
                        color: isExpired ? '#ef5350' : '#4caf50'
                    });
                    $('#rfidLookupMemberCredit').text(m.credit !== null ? m.credit : '-').css({
                        color: creditValue > 0 ? '#f5c518 ' : '#ef5350'
                    });
                    var actionType = 'membership_renewal';
                    var actionLabel = 'Membership Renewal';
                    if (m.type === 'session') {
                        actionType = 'new_membership';
                        actionLabel = 'New Membership';
                    } else if (!isExpired) {
                        actionType = 'membership_extension';
                        actionLabel = 'Membership Extension';
                    }
                    $('#btnRenewFromLookup').text(actionLabel).show().data({
                        id: m.id,
                        name: (m.first_name + ' ' + m.last_name),
                        rfid: m.RFID || '',
                        credit: m.credit || 0,
                        actionType: actionType
                    });
                    $('#rfidLookupMemberDetails').show();
                } else {
                    $('#rfidLookupStatus').text('Member not found for this RFID.');
                    showToast(res.error || 'Member not found.');
                }
            },
            error: function() {
                $('#rfidLookupStatus').text('Lookup failed.');
                showToast('Server error while looking up RFID.');
            }
        });
    }

    function loadArchiveHistory() {
        $.ajax({
            url: 'member.php',
            method: 'GET',
            data: { ajax_archive_list: 1 },
            dataType: 'json',
            success: function(data) {
                var html = '';
                if (data.length === 0) {
                    html = '<tr><td colspan="9" style="text-align: center;">No archived members.</td></tr>';
                } else {
                    $.each(data, function(i, member) {
                        var avatarColors = ['#1976d2','#e53935','#388e3c','#f57c00','#7b1fa2','#00838f','#c62828','#2e7d32','#1565c0','#6a1b9a'];
                        var firstLetter = member.first_name.charAt(0).toUpperCase() || '?';
                        var avatarColor = avatarColors[firstLetter.charCodeAt(0) % avatarColors.length];
                        var archivedAt = new Date(member.archived_at).toLocaleString();
                        html += '<tr>' +
                            '<td data-label="Name"><div class="user-cell"><div class="user-avatar" style="background:' + avatarColor + '">' + escapeHtml(firstLetter) + '</div><span class="user-name-text">' + escapeHtml(member.first_name + ' ' + member.last_name) + '</span></div></td>' +
                            '<td data-label="Email">' + escapeHtml(member.gmail || '-') + '</td>' +
                            '<td data-label="Phone">' + escapeHtml(member.phone || '-') + '</td>' +
                            '<td data-label="Type"><span class="badge-type ' + (member.type === 'member' ? 'membership' : 'session') + '">' + member.type + '</span></td>' +
                            '<td data-label="RFID">' + escapeHtml(member.RFID || '-') + '</td>' +
                            '<td data-label="Archived At">' + archivedAt + '</td>' +
                            '<td data-label="Archived By">' + escapeHtml(member.archived_by || '-') + '</td>' +
                            '<td data-label="Reason">' + escapeHtml(member.reason || '-') + '</td>' +
                            '<td data-label="Actions"><button type="button" class="action-btn btn-recover-member" data-member-id="' + member.member_id + '" data-name="' + escapeHtml(member.first_name + ' ' + member.last_name) + '">Recover</button></td>' +
                            '</tr>';
                    });
                }
                $('#archiveTableBody').html(html);
            },
            error: function() {
                $('#archiveTableBody').html('<tr><td colspan="9" style="text-align: center; color: red;">Error loading archive.</td></tr>');
            }
        });
    }

    $(document).on('click', '.btn-recover-member', function() {
        var memberId = $(this).data('member-id');
        var name = $(this).data('name');
        showConfirm('Recover Member', 'Are you sure you want to recover ' + name + '?', function() {
            $.ajax({
                url: 'member.php',
                method: 'POST',
                data: { ajax_recover_member: 1, member_id: memberId },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        showOutcomeModal(true, 'Member Recovered', res.message || 'Member recovered successfully.');
                        loadArchiveHistory();
                        fetchMembers();
                    } else {
                        showOutcomeModal(false, 'Recovery Failed', res.message || 'Unable to recover the member.');
                    }
                },
                error: function() {
                    showOutcomeModal(false, 'Recovery Failed', 'Server error while recovering the member.');
                }
            });
        });
    });

    function loadBlockedRfids() {
        $.ajax({
            url: 'member.php',
            method: 'GET',
            data: { ajax_blocked_rfids: 1 },
            dataType: 'json',
            success: function(data) {
                var html = '';
                if (!data || data.length === 0) {
                    html = '<tr><td colspan="5" style="text-align: center;">No blocked RFIDs.</td></tr>';
                } else if (data.error) {
                    html = '<tr><td colspan="5" style="text-align: center; color: red;">' + escapeHtml(data.error) + '</td></tr>';
                } else {
                    $.each(data, function(i, blocked) {
                        var blockedDate = blocked.blocked_at ? new Date(blocked.blocked_at).toLocaleString() : '-';
                        html += '<tr>' +
                            '<td data-label="RFID">' + escapeHtml(blocked.rfid || '-') + '</td>' +
                            '<td data-label="Member Name">' + escapeHtml(blocked.member_name || 'Unknown') + '</td>' +
                            '<td data-label="Blocked Date">' + escapeHtml(blockedDate) + '</td>' +
                            '<td data-label="Reason">' + escapeHtml(blocked.reason || '-') + '</td>' +
                            '</tr>';
                    });
                }
                $('#blockedRfidTableBody').html(html);
            },
            error: function() {
                $('#blockedRfidTableBody').html('<tr><td colspan="5" style="text-align: center; color: red;">Error loading blocked RFIDs.</td></tr>');
            }
        });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

});
</script>
</body>
</html>

