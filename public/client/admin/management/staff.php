<?php
$page = 'staff';
require_once '../../../../app/config/connection.php';
require_once '../../../../app/config/mail.php';
include '../../../../component/admin_header.php';
include '../../../../component/admin_sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management</title>
    <link href="../../../../assets/css/toastednotif.css" rel="stylesheet">
    <link href="../../../../assets/css/admin_header.css" rel="stylesheet">
    <link href="../../../../assets/css/admin_sidebar.css" rel="stylesheet">
    <link href="../../../../assets/css/admin.css" rel="stylesheet">
</head>
<style>
    .Member-content {
        margin-left: 250px;
        margin-top: 60px;
        padding: 2rem;
        min-height: calc(100vh - 60px);
        background: #222;
    }
    @media (max-width: 900px) {
        .Member-content { margin-left: 0; padding: 1rem; }
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
        table-layout: fixed;
    }
    .admin-table thead th:first-child { border-top-left-radius: 16px; }
    .admin-table thead th:last-child { border-top-right-radius: 16px; }
    .admin-table tbody tr:last-child td:first-child { border-bottom-left-radius: 16px; }
    .admin-table tbody tr:last-child td:last-child { border-bottom-right-radius: 16px; }
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
    .admin-table tr { transition: background 0.2s; }
    .admin-table tbody tr:hover { background: #f9f9f965; color: #333; }
    .admin-table td { border-bottom: 1px solid #333; font-size: 15px; }
    .badge-status {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 12px;
        font-size: 90%;
        font-weight: 600;
        color: #fff;
    }
    .badge-staff   { background: #1976d2; }
    .badge-super_admin { background: #7b1fa2; }
    .badge-admin   { background: #e53935; }
    .action-btn {
        background: none;
        border: none;
        color: #1976d2;
        cursor: pointer;
        padding: 0 8px;
        font-size: 15px;
        transition: color 0.2s;
    }
    .action-btn.delete { color: #d32f2f; }
    .action-btn:hover { text-decoration: underline; }
    .admin-table th:nth-child(1){ width: 17%; }
    .admin-table th:nth-child(2){ width: 20%; }
    .admin-table th:nth-child(3){ width: 12%; }
    .admin-table th:nth-child(4){ width: 11%; }
    .admin-table th:nth-child(5){ width: 15%; }
    .admin-table th:nth-child(6){ width: 25%; }
    .badge-active-status   { display:inline-block; padding:2px 10px; border-radius:12px; font-size:90%; font-weight:600; color:#fff; background:#43a047; }
    .badge-inactive-status { display:inline-block; padding:2px 10px; border-radius:12px; font-size:90%; font-weight:600; color:#fff; background:#757575; }
    .action-btn.deactivate { color: #f57c00; }
    .table-wrapper { overflow-x: auto; width: 100%; }
    @media (max-width: 700px) {
        #searchUsername { width: 100% !important; box-sizing: border-box; }
        .admin-table { min-width: 600px; }
    }
    /* Mobile stacked table */
    @media (max-width: 600px) {
        .admin-table { min-width: 0; }
        .admin-table thead { display: none; }
        .admin-table tbody tr { display: block; margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.03); border-radius: 8px; padding: 8px; background: #1b1b1b; }
        .admin-table tbody td { display: flex; justify-content: space-between; padding: 8px 10px; border-bottom: none; white-space: normal; }
        .admin-table tbody td::before { content: attr(data-label); color: #f5c518; font-weight: 700; margin-right: 8px; width: 110px; flex: 0 0 110px; }
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
        max-width: 440px;
        width: 90%;
        color: #fff;
        box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    }
    .modal-box h2 { margin: 0 0 20px; font-size: 1.3rem; }
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
</style>
<body>
    <div class="Member-content">
        <h1>Staff Management</h1>
        <?php
        require_once '../../../../app/config/connection.php';

        function renderStaffRows(array $staffList): string {
            ob_start();
            if (empty($staffList)): ?>
                <tr><td colspan="6" style="text-align:center;">No staff found.</td></tr>
            <?php else: foreach ($staffList as $staff):
                $avatarColors = ['#1976d2','#e53935','#388e3c','#f57c00','#7b1fa2','#00838f','#c62828','#2e7d32','#1565c0','#6a1b9a'];
                $firstLetter  = strtoupper(substr($staff['username'], 0, 1)) ?: '?';
                $avatarColor  = $avatarColors[ord($firstLetter) % count($avatarColors)];
                $roleBadge    = match($staff['role']) {
                    'super_admin' => '<span class="badge-status badge-super_admin">Super Admin</span>',
                    'admin'       => '<span class="badge-status badge-admin">Admin</span>',
                    default       => '<span class="badge-status badge-staff">Staff</span>',
                };
                $joined = $staff['created_at'] ? date('d M Y', strtotime($staff['created_at'])) : '-';
            ?>
                <tr>
                    <td data-label="Username">
                        <div class="user-cell">
                            <div class="user-avatar" style="background:<?= $avatarColor ?>"><?= htmlspecialchars($firstLetter) ?></div>
                            <span class="user-name-text"><?= htmlspecialchars($staff['username']) ?></span>
                        </div>
                    </td>
                    <td data-label="Email"><?= htmlspecialchars($staff['email']) ?></td>
                    <td data-label="Role"><?= $roleBadge ?></td>
                    <td data-label="Status">
                        <?php $staffStatus = $staff['status'] ?? 'active'; ?>
                        <?php if ($staffStatus === 'active'): ?>
                            <span class="badge-active-status">Active</span>
                        <?php else: ?>
                            <span class="badge-inactive-status">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Joined Date"><?= htmlspecialchars($joined) ?></td>
                    <td data-label="Actions">
                        <?php if ($staff['role'] !== 'super_admin'): ?>
                            <button type="button" class="action-btn"
                                data-id="<?= $staff['id'] ?>"
                                data-username="<?= htmlspecialchars($staff['username'], ENT_QUOTES) ?>"
                                data-email="<?= htmlspecialchars($staff['email'] ?? '', ENT_QUOTES) ?>"
                                data-role="<?= htmlspecialchars($staff['role'], ENT_QUOTES) ?>"
                                onclick="openEditModal(this)">Edit</button>
                            <form method="post" action="" style="display:inline">
                                <input type="hidden" name="toggle_status_id" value="<?= $staff['id'] ?>">
                                <button type="submit" class="action-btn deactivate">
                                    <?= ($staff['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                            <?php if (!empty($staff['locked_until']) && strtotime($staff['locked_until']) > time()): ?>
                                <form method="post" action="" style="display:inline">
                                    <input type="hidden" name="unlock_account_id" value="<?= $staff['id'] ?>">
                                    <button type="submit" class="action-btn" style="color:#f57c00;">Unlock</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="" style="display:inline" onsubmit="return confirm('Archive this staff member?');">
                                <input type="hidden" name="archive_id" value="<?= $staff['id'] ?>">
                                <button type="submit" class="action-btn delete">Archive</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#666; font-size:13px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif;
            return ob_get_clean();
        }

        function renderResetRows(array $resetRequests): string {
            ob_start();
            if (empty($resetRequests)): ?>
                <tr><td colspan="5" style="text-align:center;">No password reset requests found.</td></tr>
            <?php else: foreach ($resetRequests as $request): ?>
                <tr>
                    <td data-label="Username"><?= htmlspecialchars($request['username']) ?></td>
                    <td data-label="Reason"><?= htmlspecialchars($request['reason'] ?? '-') ?></td>
                    <td data-label="Status"><?= htmlspecialchars($request['status']) ?></td>
                    <td data-label="Requested"><?= htmlspecialchars($request['created_at']) ?></td>
                    <td data-label="Action">
                        <?php if ($request['status'] === 'pending'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="handle_reset_request" value="1">
                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                <input type="hidden" name="reset_action" value="approve">
                                <button type="submit" class="action-btn" style="color:#43a047;">Approve</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="handle_reset_request" value="1">
                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                <input type="hidden" name="reset_action" value="reject">
                                <button type="submit" class="action-btn delete">Reject</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#aaa;">Handled</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif;
            return ob_get_clean();
        }

        function renderArchiveRows(array $archiveList): string {
            ob_start();
            if (empty($archiveList)): ?>
                <tr><td colspan="6" style="text-align:center;">No archived staff accounts.</td></tr>
            <?php else: foreach ($archiveList as $archive): ?>
                <tr>
                    <td data-label="Username"><?= htmlspecialchars($archive['username']) ?></td>
                    <td data-label="Email"><?= htmlspecialchars($archive['email'] ?? '-') ?></td>
                    <td data-label="Role"><?= htmlspecialchars($archive['role']) ?></td>
                    <td data-label="Archived At"><?= htmlspecialchars($archive['archived_at']) ?></td>
                    <td data-label="Archived By"><?= htmlspecialchars($archive['archived_by'] ?? '-') ?></td>
                    <td data-label="Action">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="recover_archive_id" value="<?= $archive['id'] ?>">
                            <button type="submit" class="action-btn" style="color:#43a047;">Recover</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif;
            return ob_get_clean();
        }

        // One-time migration: add status column if not exists
        try { $pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(10) NOT NULL DEFAULT 'active'"); } catch (Exception $e) {}
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS staff_archive (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            username VARCHAR(100) NOT NULL,
            email VARCHAR(255) DEFAULT NULL,
            role VARCHAR(50) NOT NULL,
            archived_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            archived_by VARCHAR(100) DEFAULT NULL,
            reason VARCHAR(255) DEFAULT 'archived'
        )"); } catch (Exception $e) {}
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            username VARCHAR(100) NOT NULL,
            email VARCHAR(255),
            status VARCHAR(20) DEFAULT 'pending',
            reason VARCHAR(255),
            requested_by INT,
            requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            approved_by INT,
            approved_at DATETIME,
            auto_login_token VARCHAR(255),
            auto_login_expiry DATETIME,
            handled_by VARCHAR(100),
            handled_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )"); } catch (Exception $e) {}

        $defaultStaffPassword = 'Staff1234';

        // Handle Add Staff
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $role     = trim($_POST['role'] ?? 'staff');
            $password = password_hash($defaultStaffPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, role, password, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
            $stmt->execute([$username, $email, $role, $password]);
            $adminName = $_SESSION['user_name'] ?? 'admin';
            add_admin_notification($pdo, 'staff', 'Staff account created', 'A new staff account was added and is ready for use.', $adminName);
            echo "<meta http-equiv='refresh' content='0'>";
            exit;
        }

        // Handle Edit Staff (staff only, super_admin protected)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_staff'])) {
            $editId      = intval($_POST['edit_id'] ?? 0);
            $username    = trim($_POST['username'] ?? '');
            $email       = trim($_POST['email'] ?? '');
            $role        = trim($_POST['role'] ?? 'staff');
            $newPassword = trim($_POST['new_password'] ?? '');
            if ($editId > 0 && $username !== '') {
                $adminName = $_SESSION['user_name'] ?? 'admin';
                if ($newPassword !== '') {
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE id = ? AND role != 'super_admin'")
                        ->execute([$username, $email, $role, $hashed, $editId]);
                } else {
                    $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ? AND role != 'super_admin'")
                        ->execute([$username, $email, $role, $editId]);
                }
                add_admin_notification($pdo, 'staff', 'Staff account updated', 'Staff account details were updated.', $adminName);
            }
            echo "<meta http-equiv='refresh' content='0'>";
            exit;
        }

        // Handle Toggle Status (staff only, super_admin protected)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status_id'])) {
            $toggleId = intval($_POST['toggle_status_id']);
            $pdo->prepare("UPDATE users SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ? AND role != 'super_admin'")
                ->execute([$toggleId]);
            $adminName = $_SESSION['user_name'] ?? 'admin';
            add_admin_notification($pdo, 'staff', 'Staff status changed', 'A staff account status was changed.', $adminName);
            echo "<meta http-equiv='refresh' content='0'>";
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['handle_reset_request'])) {
            $requestId = intval($_POST['request_id'] ?? 0);
            $action = $_POST['reset_action'] ?? 'approve';
            $adminName = $_SESSION['user_name'] ?? 'admin';

            if ($requestId > 0) {
                $request = $pdo->prepare("SELECT user_id FROM password_reset_requests WHERE id = ? LIMIT 1");
                $request->execute([$requestId]);
                $resetReq = $request->fetch(PDO::FETCH_ASSOC);

                if ($resetReq) {
                    if ($action === 'approve') {
                        $pdo->prepare("UPDATE users SET status = 'active', locked_until = NULL, failed_login_attempts = 0, password_reset_required = 0, password = ? WHERE id = ?")
                            ->execute([password_hash($defaultStaffPassword, PASSWORD_DEFAULT), $resetReq['user_id']]);
                    } else {
                        $pdo->prepare("UPDATE users SET status = 'active', password_reset_required = 0 WHERE id = ?")
                            ->execute([$resetReq['user_id']]);
                    }

                    if ($action === 'approve') {
                        // Generate a one-time auto-login token valid for 10 minutes
                        try {
                            $token = bin2hex(random_bytes(24));
                        } catch (Exception $e) {
                            $token = bin2hex(openssl_random_pseudo_bytes(24));
                        }
                        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                        $pdo->prepare("UPDATE password_reset_requests SET status = 'approved', auto_login_token = ?, auto_login_expiry = ?, handled_by = ?, handled_at = NOW() WHERE id = ?")
                            ->execute([$token, $expiry, $adminName, $requestId]);

                        // Fetch staff email to send auto-login link
                        $userStmt = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
                        $userStmt->execute([$resetReq['user_id']]);
                        $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
                        if ($userRow && filter_var($userRow['email'], FILTER_VALIDATE_EMAIL)) {
                            $email = $userRow['email'];
                            $siteUrl = get_site_login_url();
                            $link = rtrim($siteUrl, '/') . '/public/client/staff/auto_login.php?token=' . urlencode($token);
                            $mailBody = '<html><body><h2>Auto-login link</h2><p>An administrator approved your password reset request. Click the link below to automatically sign in. This link expires in 10 minutes.</p><p><a href="' . htmlspecialchars($link) . '">Sign in now</a></p><p>If you did not request this, please contact your administrator.</p></body></html>';
                            @send_gmail_smtp($email, 'Your auto-login link', $mailBody);
                        }

                        add_admin_notification($pdo, 'staff', 'Password reset approved', 'An auto-login link was sent to the staff.', $adminName);
                    } else {
                        $pdo->prepare("UPDATE password_reset_requests SET status = ?, handled_by = ?, handled_at = NOW() WHERE id = ?")
                            ->execute([$action === 'approve' ? 'approved' : 'rejected', $adminName, $requestId]);
                        add_admin_notification($pdo, 'staff', 'Password reset rejected', 'A password reset request was rejected.', $adminName);
                    }
                }
            }
            echo "<meta http-equiv='refresh' content='0'>";
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_account_id'])) {
            $unlockId = intval($_POST['unlock_account_id']);
            $pdo->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, status = 'active', password_reset_required = 0 WHERE id = ? AND role = 'staff'")
                ->execute([$unlockId]);
            $adminName = $_SESSION['user_name'] ?? 'admin';
            add_admin_notification($pdo, 'staff', 'Staff account unlocked', 'A locked staff account was unlocked.', $adminName);
            echo "<meta http-equiv='refresh' content='0'>";
            exit;
        }

        // Handle Archive (super_admin rows are protected)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_id'])) {
            $archiveId = intval($_POST['archive_id']);
            $adminName = $_SESSION['user_name'] ?? 'admin';
            $staff = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ? AND role != 'super_admin' LIMIT 1");
            $staff->execute([$archiveId]);
            $staffRow = $staff->fetch(PDO::FETCH_ASSOC);

            if ($staffRow) {
                $pdo->prepare("INSERT INTO staff_archive (user_id, username, email, role, archived_at, archived_by, reason) VALUES (?, ?, ?, ?, NOW(), ?, 'archived')")
                    ->execute([$staffRow['id'], $staffRow['username'], $staffRow['email'], $staffRow['role'], $adminName]);
                $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'")->execute([$archiveId]);
                add_admin_notification($pdo, 'staff', 'Staff archived', 'A staff account was archived.', $adminName);
            }
            echo "<meta http-equiv='refresh' content='0'>";
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recover_archive_id'])) {
            $archiveId = intval($_POST['recover_archive_id']);
            $archive = $pdo->prepare("SELECT * FROM staff_archive WHERE id = ? LIMIT 1");
            $archive->execute([$archiveId]);
            $archiveRow = $archive->fetch(PDO::FETCH_ASSOC);

            if ($archiveRow) {
                $pdo->prepare("INSERT INTO users (username, email, role, password, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())")
                    ->execute([$archiveRow['username'], $archiveRow['email'], $archiveRow['role'], password_hash($defaultStaffPassword, PASSWORD_DEFAULT)]);
                $pdo->prepare("DELETE FROM staff_archive WHERE id = ?")->execute([$archiveId]);
                $adminName = $_SESSION['user_name'] ?? 'admin';
                add_admin_notification($pdo, 'staff', 'Staff recovered', 'A previously archived staff account was recovered.', $adminName);
            }
            echo "<meta http-equiv='refresh' content='0'>";
            exit;
        }

        // Handle password reset request (only super_admin can initiate)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_staff_reset'])) {
            if (($_SESSION['user_role'] ?? '') !== 'super_admin') {
                echo "<meta http-equiv='refresh' content='0'>";
                exit;
            }

            $staffId = intval($_POST['request_staff_id'] ?? 0);
            $reason  = trim($_POST['request_reason'] ?? 'Admin requested password reset');
            $adminName = $_SESSION['user_name'] ?? 'admin';
            
            if ($staffId > 0) {
                // Get staff info
                $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ? AND role = 'staff' LIMIT 1");
                $stmt->execute([$staffId]);
                $staff = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($staff) {
                    // Check if there's already a pending request
                    $check = $pdo->prepare("SELECT id FROM password_reset_requests WHERE user_id = ? AND status = 'pending' LIMIT 1");
                    $check->execute([$staffId]);
                    
                    if (!$check->fetch()) {
                        // Create new reset request
                        $pdo->prepare("INSERT INTO password_reset_requests (user_id, username, email, status, reason, requested_by, requested_at) VALUES (?, ?, ?, 'pending', ?, ?, NOW())")
                            ->execute([$staffId, $staff['username'], $staff['email'], $reason === '' ? 'Admin requested password reset' : $reason, $_SESSION['user_id']]);
                        
                        add_admin_notification($pdo, 'staff', 'Password reset requested', 'A password reset request was created for: ' . htmlspecialchars($staff['username']), $adminName);
                    }
                }
            }
            echo "<meta http-equiv='refresh' content='0'>";
            exit;
        }

        $search       = $_GET['search'] ?? '';
        $roleFilter   = $_GET['role'] ?? '';
        $resetSearch  = $_GET['reset_search'] ?? '';
        $resetStatus  = $_GET['reset_status'] ?? '';

        try      {
            $query  = "SELECT * FROM users WHERE 1";
            $params = [];
            if ($search !== '') {
                $query   .= " AND username LIKE ?";
                $params[] = "%$search%";
            }
            if ($roleFilter !== '') {
                $query   .= " AND role = ?";
                $params[] = $roleFilter;
            }
            $query .= " ORDER BY created_at DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $staffList = $stmt->fetchAll();

            $reqQuery  = "SELECT * FROM password_reset_requests WHERE 1";
            $reqParams = [];
            if ($resetSearch !== '') {
                $reqQuery   .= " AND username LIKE ?";
                $reqParams[] = "%$resetSearch%";
            }
            if ($resetStatus !== '') {
                $reqQuery   .= " AND status = ?";
                $reqParams[] = $resetStatus;
            }
            $reqQuery .= " ORDER BY created_at DESC";
            $reqStmt = $pdo->prepare($reqQuery);
            $reqStmt->execute($reqParams);
            $resetRequests = $reqStmt->fetchAll();

            $archiveStmt = $pdo->query("SELECT * FROM staff_archive ORDER BY archived_at DESC");
            $archiveList = $archiveStmt->fetchAll();
        } catch (Exception $e) {
            echo '<div style="color:red">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $staffList = [];
            $resetRequests = [];
            $archiveList = [];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_section'])) {
            if ($_GET['ajax_section'] === 'staff') {
                echo renderStaffRows($staffList);
                exit;
            }
            if ($_GET['ajax_section'] === 'reset') {
                echo renderResetRows($resetRequests);
                exit;
            }
        }
        ?>

        <!-- Toolbar -->
        <div style="margin-bottom:15px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <input type="text" id="searchUsername" placeholder="Search staff username..."
                style="padding:8px 12px; border-radius:8px; border:1px solid #ccc; width:250px;">
            <select id="roleFilter" style="padding:8px 12px; border-radius:8px; border:1px solid #ccc;">
                <option value="">All Roles</option>
                <option value="staff">Staff</option>
                <option value="super_admin">Super Admin</option>
            </select>
            <div style="margin-left:auto;">
                <button type="button" id="addStaffBtn"
                    style="padding:8px 18px; border-radius:8px; border:none; background:#1976d2; color:#fff; font-weight:600; cursor:pointer;">
                    + Add User
                </button>
            </div>
        </div>

        <div style="margin-top:24px; background:#2b2b2b; padding:16px; border-radius:14px;" id="staffSection">
            <h3 style="margin:0 0 12px; color:#fff;">Staff List</h3>
            <div class="table-wrapper">
                <table class="admin-table" style="width:100%; background:#333;">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staffTableBody"><?= renderStaffRows($staffList) ?></tbody>
                </table>
            </div>
        </div>

        <div style="margin-top:24px;" id="dropSection">
            <?php if (($_SESSION['user_role'] ?? '') === 'super_admin'): ?>
            <div style="margin-top:24px; background:#2b2b2b; padding:16px; border-radius:14px;" id="resetSection">
                <h3 style="margin:0 0 12px; color:#fff;">Password Reset Requests</h3>
                <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:15px;">
                    <input type="text" id="resetSearch" placeholder="Search reset username..."
                        style="padding:8px 12px; border-radius:8px; border:1px solid #ccc; width:250px;">
                    <select id="resetStatus" style="padding:8px 12px; border-radius:8px; border:1px solid #ccc; width:180px;">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <form method="post" action="" style="margin-left:auto; display:flex; gap:10px; align-items:center;">
                        <select name="request_staff_id" style="padding:8px 12px; border-radius:8px; border:1px solid #ccc; min-width:220px;">
                            <option value="">Select staff to request</option>
                            <?php foreach ($staffList as $staffRequestOption): if ($staffRequestOption['role'] === 'staff'): ?>
                                <option value="<?= intval($staffRequestOption['id']) ?>"><?= htmlspecialchars($staffRequestOption['username']) ?> (<?= htmlspecialchars($staffRequestOption['email'] ?: 'no email') ?>)</option>
                            <?php endif; endforeach; ?>
                        </select>
                        <button type="submit" name="request_staff_reset" value="1" style="padding:8px 18px; border-radius:8px; border:none; background:#f57c00; color:#fff; font-weight:600; cursor:pointer;">Create Request</button>
                    </form>
                </div>
                <div class="table-wrapper">
                    <table class="admin-table" style="background:#262626;">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="resetTableBody"><?= renderResetRows($resetRequests) ?></tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div style="margin-top:24px; background:#2b2b2b; padding:16px; border-radius:14px;" id="archiveSection">
                <h3 style="margin:0 0 12px; color:#fff;">Archived Staff History</h3>
                <div class="table-wrapper">
                    <table class="admin-table" style="background:#262626;">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Archived At</th>
                                <th>Archived By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="archiveTableBody"><?= renderArchiveRows($archiveList) ?></tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Add Staff Modal -->
    <div class="modal-overlay" id="addStaffModal">
        <div class="modal-box">
            <h2>Add Staff</h2>
            <div class="modal-form">
                <form method="post">
                    <input type="hidden" name="add_staff" value="1">
                    <label>Username <span style="color:#e57373">*</span></label>
                    <input type="text" name="username" required placeholder="Enter username">
                    <label>Email <span style="color:#666">(optional)</span></label>
                    <input type="email" name="email" placeholder="example@email.com">
                    <label>Role <span style="color:#e57373">*</span></label>
                    <select name="role">
                        <option value="staff">Staff</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                    <p style="font-size:12px; color:#888; margin: -4px 0 12px;">Default password: <strong style="color:#aaa">Staff1234</strong></p>
                    <div class="modal-actions">
                        <button type="submit" class="btn-submit">Add Staff</button>
                        <button type="button" class="btn-cancel-modal" id="btnCloseModal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal-overlay" id="editStaffModal">
        <div class="modal-box">
            <h2>Edit Staff</h2>
            <div class="modal-form">
                <form method="post">
                    <input type="hidden" name="edit_staff" value="1">
                    <input type="hidden" name="edit_id" id="editId">
                    <label>Username <span style="color:#e57373">*</span></label>
                    <input type="text" name="username" id="editUsername" required placeholder="Enter username">
                    <label>Email <span style="color:#666">(optional)</span></label>
                    <input type="email" name="email" id="editEmail" placeholder="example@email.com">
                    <label>Role <span style="color:#e57373">*</span></label>
                    <select name="role" id="editRole">
                        <option value="staff">Staff</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                    <label>New Password <span style="color:#666">(leave blank to keep current)</span></label>
                    <input type="password" name="new_password" id="editPassword" placeholder="Enter new password">
                    <div class="modal-actions">
                        <button type="submit" class="btn-submit">Save Changes</button>
                        <button type="button" class="btn-cancel-modal" id="btnCloseEditModal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function openEditModal(btn) {
        $('#editId').val(btn.dataset.id);
        $('#editUsername').val(btn.dataset.username);
        $('#editEmail').val(btn.dataset.email);
        $('#editRole').val(btn.dataset.role);
        $('#editPassword').val('');
        $('#editStaffModal').addClass('active');
    }

    $(document).ready(function () {
        function fetchStaff() {
            var search = $('#searchUsername').val();
            var role   = $('#roleFilter').val();
            $.ajax({
                url: 'staff_ajax.php',
                method: 'GET',
                data: { search: search, role: role, ajax_section: 'staff' },
                success: function (data) {
                    $('#staffTableBody').html(data);
                }
            });
        }

        function fetchResetRequests() {
            var search = $('#resetSearch').val();
            var status = $('#resetStatus').val();
            $.ajax({
                url: 'staff_ajax.php',
                method: 'GET',
                data: { reset_search: search, reset_status: status, ajax_section: 'reset' },
                success: function (data) {
                    $('#resetTableBody').html(data);
                }
            });
        }

        $('#searchUsername').on('keyup', fetchStaff);
        $('#roleFilter').on('change', fetchStaff);
        $('#resetSearch').on('keyup', fetchResetRequests);
        $('#resetStatus').on('change', fetchResetRequests);

        fetchResetRequests();

        $('#addStaffBtn').on('click', function () {
            $('#addStaffModal').addClass('active');
        });
        $('#btnCloseModal').on('click', function () {
            $('#addStaffModal').removeClass('active');
        });
        $('#addStaffModal').on('click', function (e) {
            if (e.target === this) $('#addStaffModal').removeClass('active');
        });

        $('#btnCloseEditModal').on('click', function () {
            $('#editStaffModal').removeClass('active');
        });
        $('#editStaffModal').on('click', function (e) {
            if (e.target === this) $('#editStaffModal').removeClass('active');
        });
    });
    </script>
</body>
</html>
