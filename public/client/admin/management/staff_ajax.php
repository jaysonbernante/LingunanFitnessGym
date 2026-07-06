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
        <tr><td colspan="7" style="text-align:center;">No password reset requests found.</td></tr>
    <?php else: foreach ($resetRequests as $request): ?>
        <?php $status = (string) ($request['status'] ?? 'pending'); ?>
        <tr>
            <td data-label="Username"><?= htmlspecialchars($request['full_name'] ?? $request['username'] ?? '-') ?></td>
            <td data-label="Email"><?= htmlspecialchars($request['email'] ?? '-') ?></td>
            <td data-label="Role"><?= htmlspecialchars($request['role'] ?? 'Staff') ?></td>
            <td data-label="Reason"><?= htmlspecialchars($request['reason'] ?? '-') ?></td>
            <td data-label="Status"><?= htmlspecialchars(ucfirst($status)) ?></td>
            <td data-label="Requested"><?= htmlspecialchars($request['requested_at'] ?? $request['created_at'] ?? '-') ?></td>
            <td data-label="Action">
                <?php if ($status === 'pending'): ?>
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

$search      = $_GET['search'] ?? '';
$role        = $_GET['role'] ?? '';
$resetSearch = $_GET['reset_search'] ?? '';
$resetStatus = $_GET['reset_status'] ?? '';

try {
    $query  = "SELECT * FROM users WHERE 1";
    $params = [];
    if ($search !== '') {
        $query   .= " AND username LIKE ?";
        $params[] = "%$search%";
    }
    if ($role !== '') {
        $query   .= " AND role = ?";
        $params[] = $role;
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
} catch (Exception $e) {
    $staffList = [];
    $resetRequests = [];
}

if (isset($_GET['ajax_section'])) {
    if ($_GET['ajax_section'] === 'staff') {
        echo renderStaffRows($staffList);
        exit;
    }
    if ($_GET['ajax_section'] === 'reset') {
        echo renderResetRows($resetRequests);
        exit;
    }
}
