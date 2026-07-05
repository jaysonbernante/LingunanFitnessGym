<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../app/config/connection.php';
require_once __DIR__ . '/../../../../component/session_check.php';

$page = 'support';
$successMessage = '';
$errorMessage = '';
$selectedConversationId = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$replyMessage = '';
$statusValue = 'open';

if (isset($_GET['ajax']) && $_GET['ajax'] === 'messages') {
    header('Content-Type: application/json');
    $conversationId = intval($_GET['conversation_id'] ?? 0);
    if ($conversationId > 0) {
        $stmt = $pdo->prepare("SELECT id, status FROM support_conversations WHERE id = ? LIMIT 1");
        $stmt->execute([$conversationId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($conversation) {
            $stmt = $pdo->prepare("SELECT id, sender_type, sender_name, message, created_at FROM support_messages WHERE conversation_id = ? ORDER BY created_at ASC");
            $stmt->execute([$conversationId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $messagesHtml = '';
            foreach ($messages as $msg) {
                $messageClass = htmlspecialchars($msg['sender_type'], ENT_QUOTES, 'UTF-8');
                $messagesHtml .= '<div class="support-msg ' . $messageClass . '">';
                $messagesHtml .= '<div class="support-meta" style="margin-bottom:6px;"><strong>' . htmlspecialchars($msg['sender_name'], ENT_QUOTES, 'UTF-8') . '</strong><span>' . htmlspecialchars(date('M d, H:i', strtotime($msg['created_at'])), ENT_QUOTES, 'UTF-8') . '</span></div>';
                $messagesHtml .= '<div>' . nl2br(htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8')) . '</div></div>';
            }
            echo json_encode(['success' => true, 'status' => $conversation['status'], 'messages_html' => $messagesHtml]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'status' => null, 'messages_html' => '']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conversationId = intval($_POST['conversation_id'] ?? 0);
    $replyMessage = trim($_POST['reply_message'] ?? '');
    $statusValue = 'open';

    if (isset($_POST['close_conversation'])) {
        if ($conversationId <= 0) {
            $errorMessage = 'Please select a conversation to close.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("SELECT status FROM support_conversations WHERE id = ? LIMIT 1");
                $stmt->execute([$conversationId]);
                $currentStatus = $stmt->fetchColumn();

                if ($currentStatus === 'closed') {
                    $pdo->rollBack();
                    $errorMessage = 'This conversation is already closed.';
                } else {
                    $stmt = $pdo->prepare("UPDATE support_conversations SET status = 'closed', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$conversationId]);
                    $pdo->commit();
                    $successMessage = 'Conversation closed successfully.';
                    $selectedConversationId = $conversationId;
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $errorMessage = 'Unable to close the conversation right now.';
            }
        }
    } elseif (isset($_POST['reply_support'])) {
        if ($conversationId <= 0 || $replyMessage === '') {
            $errorMessage = 'Please select a conversation and enter a reply.';
        } else {
            try {
                $pdo->beginTransaction();
                $senderType = ($_SESSION['user_role'] ?? 'staff') === 'super_admin' ? 'admin' : 'staff';
                $senderName = trim($_SESSION['user_name'] ?? 'Staff');
                $senderId = intval($_SESSION['user_id'] ?? 0);

                $stmt = $pdo->prepare("SELECT status FROM support_conversations WHERE id = ? LIMIT 1");
                $stmt->execute([$conversationId]);
                $currentStatus = $stmt->fetchColumn();
                if ($currentStatus === 'closed') {
                    $pdo->rollBack();
                    $errorMessage = 'This conversation is already closed and no longer accepts replies.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO support_messages (conversation_id, sender_type, sender_id, sender_name, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$conversationId, $senderType, $senderId, $senderName, $replyMessage]);

                    $stmt = $pdo->prepare("UPDATE support_conversations SET status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$statusValue, $conversationId]);

                    $pdo->commit();
                    add_admin_notification($pdo, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', $senderName);
                    $successMessage = 'Reply sent successfully.';
                    $selectedConversationId = $conversationId;
                    $replyMessage = '';
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $errorMessage = 'Unable to send the reply right now.';
            }
        }
    }
}

$stmt = $pdo->query("SELECT sc.*, m.first_name, m.last_name, m.gmail FROM support_conversations sc LEFT JOIN members m ON m.id = sc.member_id ORDER BY sc.updated_at DESC, sc.created_at DESC");
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($selectedConversationId <= 0 && !empty($conversations)) {
    $selectedConversationId = intval($conversations[0]['id']);
}

$selectedConversation = null;
$messages = [];
if ($selectedConversationId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM support_conversations WHERE id = ? LIMIT 1");
    $stmt->execute([$selectedConversationId]);
    $selectedConversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($selectedConversation) {
        $stmt = $pdo->prepare("SELECT * FROM support_messages WHERE conversation_id = ? ORDER BY created_at ASC");
        $stmt->execute([$selectedConversationId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$activeConversations = [];
$closedConversations = [];
foreach ($conversations as $conversation) {
    if (($conversation['status'] ?? '') === 'closed') {
        $closedConversations[] = $conversation;
    } else {
        $activeConversations[] = $conversation;
    }
}

include '../../../../component/admin_header.php';
include '../../../../component/admin_sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support</title>
    <link href="../../../../assets/css/admin_header.css" rel="stylesheet">
    <link href="../../../../assets/css/admin_sidebar.css" rel="stylesheet">
    <link href="../../../../assets/css/admin.css" rel="stylesheet">
    <style>
        .support-page { margin-left: 250px; margin-top: 60px; padding: 1.5rem 2rem; min-height: calc(100vh - 60px); background: #1e1e1e; color: #fff; }
        @media (max-width: 900px) { .support-page { margin-left: 0; padding: 1rem; } }
        .support-grid { display: grid; grid-template-columns: 320px 1fr; gap: 16px; }
        .support-card { background: #252525; border-radius: 14px; padding: 16px; border: 1px solid #333; }
        .support-list { display: grid; gap: 10px; }
        .support-item { border: 1px solid #3a3a3a; border-radius: 10px; padding: 12px; text-decoration: none; color: #fff; background: #2b2b2b; }
        .support-item.active { border-color: #f5c518; background: #342d0d; }
        .support-meta { display: flex; justify-content: space-between; gap: 8px; font-size: 0.9rem; color: #aaa; }
        .support-chip { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; background: #3a3a3a; color: #ddd; }
        .support-chip.open { background: #1f4a2d; color: #6ee78a; }
        .support-chip.pending { background: #4a2f0f; color: #ffbf66; }
        .support-chip.closed { background: #3a3a3a; color: #ddd; }
        .support-empty { color: #aaa; font-size: 0.95rem; }
        .support-chip.closed { background: #3a3a3a; color: #ddd; }
        .support-msg { border: 1px solid #3a3a3a; border-radius: 10px; padding: 12px; margin-bottom: 10px; background: #2b2b2b; }
        .support-msg.staff, .support-msg.admin { background: #2f2a10; border-color: #6d5b15; }
        .support-msg.member { background: #1f2d36; border-color: #2f5d75; }
        #support-messages-list { max-height: 480px; overflow-y: auto; padding-right: 6px; }
        .support-label { display: block; font-size: 0.9rem; color: #aaa; margin-bottom: 6px; }
        .support-input, .support-textarea, .support-select { width: 100%; box-sizing: border-box; border: 1px solid #444; background: #1a1a1a; color: #fff; padding: 10px 12px; border-radius: 8px; }
        .support-textarea { min-height: 100px; resize: vertical; }
        .support-actions { display: flex; gap: 8px; align-items: center; margin-top: 12px; }
        .support-btn { border: none; border-radius: 8px; padding: 10px 14px; background: #f5c518; color: #111; font-weight: 700; cursor: pointer; }
        #support-messages-list::-webkit-scrollbar { width: 8px; }
        #support-messages-list::-webkit-scrollbar-thumb { background: #555; border-radius: 999px; }
        #support-messages-list::-webkit-scrollbar-track { background: transparent; }
        .support-btn.secondary { background: #2b2b2b; color: #fff; }
        @media (max-width: 1000px) { .support-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="support-page">
        <h2 style="margin-top:0;">Support Center</h2>
        <p style="color:#aaa; margin-top:-6px;">Monitor member requests and respond directly from here.</p>

        <?php if ($successMessage): ?>
            <div class="alert success" style="margin-bottom:12px;"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="alert error" style="margin-bottom:12px;"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <div class="support-grid">
            <div class="support-card">
                <h3 style="margin-top:0;">Conversations</h3>
                <?php if (empty($activeConversations) && empty($closedConversations)): ?>
                    <p style="color:#aaa;">No support conversations yet.</p>
                <?php else: ?>
                    <div class="support-list">
                        <?php if (!empty($activeConversations)): ?>
                            <strong style="color:#ddd;">Active Conversations</strong>
                            <?php foreach ($activeConversations as $conversation): ?>
                                <a class="support-item <?php echo ($selectedConversation && intval($selectedConversation['id']) === intval($conversation['id'])) ? 'active' : ''; ?>" href="support.php?conversation_id=<?php echo intval($conversation['id']); ?>">
                                    <div class="support-meta">
                                        <strong><?php echo htmlspecialchars($conversation['subject']); ?></strong>
                                        <span class="support-chip <?php echo htmlspecialchars($conversation['status']); ?>"><?php echo htmlspecialchars(ucfirst($conversation['status'])); ?></span>
                                    </div>
                                    <div class="support-meta" style="margin-top:6px;">
                                        <span><?php echo htmlspecialchars(trim(($conversation['first_name'] ?? '') . ' ' . ($conversation['last_name'] ?? '')) ?: 'Unknown member'); ?></span>
                                        <span><?php echo date('M d, H:i', strtotime($conversation['updated_at'])); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($closedConversations)): ?>
                            <strong style="color:#ddd; margin-top:8px; display:block;">Chat History</strong>
                            <?php foreach ($closedConversations as $conversation): ?>
                                <a class="support-item <?php echo ($selectedConversation && intval($selectedConversation['id']) === intval($conversation['id'])) ? 'active' : ''; ?>" href="support.php?conversation_id=<?php echo intval($conversation['id']); ?>">
                                    <div class="support-meta">
                                        <strong><?php echo htmlspecialchars($conversation['subject']); ?></strong>
                                        <span class="support-chip <?php echo htmlspecialchars($conversation['status']); ?>"><?php echo htmlspecialchars(ucfirst($conversation['status'])); ?></span>
                                    </div>
                                    <div class="support-meta" style="margin-top:6px;">
                                        <span><?php echo htmlspecialchars(trim(($conversation['first_name'] ?? '') . ' ' . ($conversation['last_name'] ?? '')) ?: 'Unknown member'); ?></span>
                                        <span><?php echo date('M d, H:i', strtotime($conversation['updated_at'])); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="support-card">
                <?php if ($selectedConversation): ?>
                    <div class="support-meta" style="margin-bottom:12px;">
                        <strong style="font-size:1.05rem;">#<?php echo intval($selectedConversation['id']); ?> - <?php echo htmlspecialchars($selectedConversation['subject']); ?></strong>
                        <span id="support-status-badge" class="support-chip <?php echo htmlspecialchars($selectedConversation['status']); ?>"><?php echo htmlspecialchars(ucfirst($selectedConversation['status'])); ?></span>
                    </div>
                    <div style="color:#aaa; margin-bottom:12px;">
                        Member: <?php echo htmlspecialchars(trim(($selectedConversation['first_name'] ?? '') . ' ' . ($selectedConversation['last_name'] ?? '')) ?: 'Unknown'); ?>
                        <?php if (!empty($selectedConversation['gmail'])): ?>
                            • <?php echo htmlspecialchars($selectedConversation['gmail']); ?>
                        <?php endif; ?>
                    </div>

                    <div id="support-messages-list">
                        <?php foreach ($messages as $msg): ?>
                            <?php $senderLabel = ($msg['sender_type'] === 'member') ? 'Member' : (($msg['sender_type'] === 'admin') ? 'Owner' : 'Staff'); ?>
                            <div class="support-msg <?php echo htmlspecialchars($msg['sender_type']); ?>">
                                <div class="support-meta" style="margin-bottom:6px;">
                                    <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>
                                    <span><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></span>
                                </div>
                                <div style="margin-bottom:8px; color:#f5c518; font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em;"><?php echo htmlspecialchars($senderLabel); ?></div>
                                <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="reply-closed-note" style="display: <?php echo ($selectedConversation['status'] === 'closed') ? 'block' : 'none'; ?>; color:#ffb4b4; margin-top:12px;">This conversation is closed and no longer accepts replies.</div>
                    <form id="reply-form" method="post" action="support.php?conversation_id=<?php echo intval($selectedConversation['id']); ?>" style="margin-top:16px; display: <?php echo ($selectedConversation['status'] === 'closed') ? 'none' : 'block'; ?>;">
                        <input type="hidden" name="reply_support" value="1">
                        <input type="hidden" name="conversation_id" value="<?php echo intval($selectedConversation['id']); ?>">
                        <label class="support-label" for="reply_message">Reply</label>
                        <textarea class="support-textarea" id="reply_message" name="reply_message" required><?php echo htmlspecialchars($replyMessage); ?></textarea>
                        <div class="support-actions">
                            <button type="submit" class="support-btn">Send Reply</button>
                            <a class="support-btn secondary" href="support.php">Refresh</a>
                        </div>
                    </form>
                    <form id="close-form" method="post" action="support.php?conversation_id=<?php echo intval($selectedConversation['id']); ?>" style="margin-top:8px; display: <?php echo ($selectedConversation['status'] === 'closed') ? 'none' : 'block'; ?>;">
                        <input type="hidden" name="close_conversation" value="1">
                        <input type="hidden" name="conversation_id" value="<?php echo intval($selectedConversation['id']); ?>">
                        <button type="submit" class="support-btn secondary" style="background:#7c2d12; color:#fff;">Close Conversation</button>
                    </form>
                <?php else: ?>
                    <p style="color:#aaa;">Select a conversation to reply.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var conversationId = <?php echo json_encode(intval($selectedConversation['id'] ?? 0)); ?>;
        var messagesList = document.getElementById('support-messages-list');
        var statusBadge = document.getElementById('support-status-badge');
        var replyForm = document.getElementById('reply-form');
        var closeForm = document.getElementById('close-form');
        var replyClosedNote = document.getElementById('reply-closed-note');
        if (!conversationId || !messagesList) return;

        function renderMessages(data) {
            if (!data || !data.success) return;
            if (messagesList) {
                messagesList.innerHTML = data.messages_html || '<div class="support-empty">No messages yet.</div>';
            }
            if (statusBadge && data.status) {
                statusBadge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                statusBadge.className = 'support-chip ' + data.status;
            }
            if (replyForm || closeForm) {
                var isClosed = data.status === 'closed';
                if (replyForm) {
                    replyForm.style.display = isClosed ? 'none' : 'block';
                }
                if (closeForm) {
                    closeForm.style.display = isClosed ? 'none' : 'block';
                }
                if (replyClosedNote) {
                    replyClosedNote.style.display = isClosed ? 'block' : 'none';
                }
            }
        }

        function loadMessages() {
            fetch('support.php?ajax=messages&conversation_id=' + conversationId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) { renderMessages(data); })
                .catch(function () {});
        }

        loadMessages();
        setInterval(loadMessages, 3000);
    });
</script>
</body>
</html>
