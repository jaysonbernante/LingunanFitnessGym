<?php
session_start();
require_once '../../app/config/connection.php';
require_once 'session_check.php';

$memberId = intval($_SESSION['member_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ? AND type IN ('member', 'session') LIMIT 1");
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
$selectedConversationId = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$dailyRequestLimit = 3;
$dailyRequestCount = 0;
$dailyRequestRemaining = $dailyRequestLimit;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM support_conversations WHERE member_id = ? AND DATE(created_at) = CURDATE()");
$stmt->execute([$member['id']]);
$dailyRequestCount = intval($stmt->fetchColumn());
$dailyRequestRemaining = max(0, $dailyRequestLimit - $dailyRequestCount);

if (isset($_GET['ajax']) && $_GET['ajax'] === 'messages') {
    header('Content-Type: application/json');
    $conversationId = intval($_GET['conversation_id'] ?? 0);
    if ($conversationId > 0) {
        $stmt = $pdo->prepare("SELECT id, status FROM support_conversations WHERE id = ? AND member_id = ? LIMIT 1");
        $stmt->execute([$conversationId, $member['id']]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($conversation) {
            $stmt = $pdo->prepare("SELECT id, sender_type, sender_name, message, created_at FROM support_messages WHERE conversation_id = ? ORDER BY created_at ASC");
            $stmt->execute([$conversationId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $messagesHtml = '';
            foreach ($messages as $msg) {
                $messageClass = ($msg['sender_type'] === 'member') ? 'me' : 'staff';
                $messagesHtml .= '<div class="support-message ' . htmlspecialchars($messageClass, ENT_QUOTES, 'UTF-8') . '">';
                $messagesHtml .= '<div class="support-thread-meta" style="margin-bottom:6px;"><strong>' . htmlspecialchars($msg['sender_name'], ENT_QUOTES, 'UTF-8') . '</strong><span>' . htmlspecialchars(date('M d, Y h:i A', strtotime($msg['created_at'])), ENT_QUOTES, 'UTF-8') . '</span></div>';
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
    if (isset($_POST['new_ticket'])) {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($subject === '' || $message === '') {
            $errorMessage = 'Please enter both a subject and a message.';
        } elseif ($dailyRequestCount >= $dailyRequestLimit) {
            $errorMessage = 'You have reached your daily limit of 3 support requests. Please wait until tomorrow to submit another one.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO support_conversations (member_id, subject, status, created_at, updated_at) VALUES (?, ?, 'pending', NOW(), NOW())");
                $stmt->execute([$member['id'], $subject]);
                $conversationId = intval($pdo->lastInsertId());

                $stmt = $pdo->prepare("INSERT INTO support_messages (conversation_id, sender_type, sender_id, sender_name, message, created_at) VALUES (?, 'member', ?, ?, ?, NOW())");
                $stmt->execute([$conversationId, $member['id'], trim($member['first_name'] . ' ' . $member['last_name']) ?: $member['username'], $message]);
                $pdo->commit();

                add_admin_notification($pdo, 'support', 'New support request', 'A member submitted a new support ticket: ' . $subject, trim($member['first_name'].' '.$member['last_name']) ?: $member['username']);

                $successMessage = 'Your support request has been submitted. Our team will review it soon.';
                $subject = '';
                $message = '';
                $selectedConversationId = $conversationId;
                $dailyRequestCount = intval($dailyRequestCount + 1);
                $dailyRequestRemaining = max(0, $dailyRequestLimit - $dailyRequestCount);
            } catch (Exception $e) {
                $pdo->rollBack();
                $errorMessage = 'Unable to save your message. Please contact the gym directly.';
            }
        }
    } elseif (isset($_POST['reply_message']) && isset($_POST['conversation_id'])) {
        $conversationId = intval($_POST['conversation_id'] ?? 0);
        $reply = trim($_POST['reply_message'] ?? '');
        if ($conversationId > 0 && $reply !== '') {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("SELECT status FROM support_conversations WHERE id = ? AND member_id = ? LIMIT 1");
                $stmt->execute([$conversationId, $member['id']]);
                $conversationStatus = $stmt->fetchColumn();
                if ($conversationStatus === 'closed') {
                    $pdo->rollBack();
                    $errorMessage = 'This conversation is closed and no longer accepts replies.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO support_messages (conversation_id, sender_type, sender_id, sender_name, message, created_at) VALUES (?, 'member', ?, ?, ?, NOW())");
                    $stmt->execute([$conversationId, $member['id'], trim($member['first_name'] . ' ' . $member['last_name']) ?: $member['username'], $reply]);
                    $pdo->prepare("UPDATE support_conversations SET status = 'pending', updated_at = NOW() WHERE id = ?")
                        ->execute([$conversationId]);
                    add_admin_notification($pdo, 'support', 'Support reply received', 'A member replied to an existing support conversation.', trim($member['first_name'] . ' ' . $member['last_name']) ?: $member['username']);
                    $pdo->commit();
                    $successMessage = 'Your reply has been sent.';
                    $selectedConversationId = $conversationId;
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $errorMessage = 'Unable to send your reply right now.';
            }
        } else {
            $errorMessage = 'Please type a reply before sending.';
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM support_conversations WHERE member_id = ? ORDER BY updated_at DESC, created_at DESC");
$stmt->execute([$member['id']]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($selectedConversationId <= 0 && !empty($conversations)) {
    $selectedConversationId = intval($conversations[0]['id']);
}

$selectedConversation = null;
$messages = [];
if ($selectedConversationId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM support_conversations WHERE id = ? AND member_id = ? LIMIT 1");
    $stmt->execute([$selectedConversationId, $member['id']]);
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
  <style>
    .support-shell { display: grid; grid-template-columns: minmax(300px, 360px) minmax(0, 1fr); gap: 16px; align-items: start; }
    .support-card { background: #111827; color: #f8fafc; border-radius: 20px; padding: 20px; border: 1px solid rgba(148, 163, 184, .16); box-shadow: 0 18px 45px rgba(15, 23, 42, .18); }
    .support-card h2 { margin-top: 0; margin-bottom: 14px; font-size: 1.12rem; }
    .support-card .form-row { margin-bottom: 12px; }
    .support-card input,
    .support-card textarea {
      width: 100%;
      border: 1px solid rgba(148, 163, 184, .24);
      border-radius: 14px;
      padding: 12px 14px;
      background: #0f172a;
      color: #f8fafc;
      font: inherit;
      box-sizing: border-box;
    }
    .support-card textarea { min-height: 110px; resize: vertical; }
    .support-card .form-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 8px; }
    .support-card .button { width: auto; min-width: 140px; justify-content: center; }
    .support-tab-row { display: flex; gap: 8px; margin: 14px 0 10px; flex-wrap: wrap; }
    .support-tab-btn { border: 1px solid rgba(148, 163, 184, .2); background: #1f2937; color: #e5e7eb; border-radius: 999px; padding: 8px 12px; font-weight: 700; cursor: pointer; }
    .support-tab-btn.active { background: #f6c451; color: #111827; border-color: #f6c451; }
    .support-thread-list { display: grid; gap: 10px; max-height: 320px; overflow-y: auto; padding-right: 4px; }
    .support-thread-list::-webkit-scrollbar { width: 8px; }
    .support-thread-list::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 999px; }
    .support-thread-list::-webkit-scrollbar-track { background: transparent; }
    .support-thread-item { border: 1px solid rgba(148, 163, 184, .16); padding: 12px; border-radius: 12px; text-decoration: none; color: inherit; background: rgba(255,255,255,.03); transition: transform .2s ease, border-color .2s ease, background .2s ease; }
    .support-thread-item:hover { transform: translateY(-1px); border-color: #f6c451; }
    .support-thread-item.active { border-color: #f6c451; background: rgba(246, 197, 81, .14); }
    .support-thread-meta { display: flex; justify-content: space-between; gap: 10px; font-size: 0.9rem; color: #cbd5e1; align-items: center; }
    .support-chat-panel { display: flex; flex-direction: column; min-height: 560px; }
    .support-messages-list { display: flex; flex-direction: column; gap: 10px; max-height: 380px; overflow-y: auto; padding-right: 6px; margin-bottom: 12px; }
    .support-messages-list::-webkit-scrollbar { width: 8px; }
    .support-messages-list::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 999px; }
    .support-messages-list::-webkit-scrollbar-track { background: transparent; }
    .support-message { border: 1px solid rgba(148, 163, 184, .16); border-radius: 12px; padding: 12px; background: rgba(255,255,255,.04); line-height: 1.5; }
    .support-message.me { background: rgba(59, 130, 246, .12); border-color: rgba(59, 130, 246, .24); }
    .support-message.staff { background: rgba(246, 197, 81, .12); border-color: rgba(246, 197, 81, .24); }
    .support-chip { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; background: #374151; color: #f3f4f6; }
    .support-chip.open { background: #14532d; color: #bbf7d0; }
    .support-chip.pending { background: #78350f; color: #fde68a; }
    .support-chip.closed { background: #374151; color: #e5e7eb; }
    .support-empty { color: #9ca3af; font-size: 0.95rem; }
    .support-empty-state { display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; gap: 8px; min-height: 260px; color: #cbd5e1; }
    .support-status-note { display: none; margin-top: 8px; color: #fda4af; font-size: 0.92rem; }
    @media (max-width: 900px) {
      .support-shell { grid-template-columns: 1fr; }
      .support-chat-panel { min-height: auto; }
      .support-card { padding: 16px; }
    }

    @media (max-width: 600px) {
      .support-card .button { width: 100%; min-width: 0; }
      .support-card .form-actions { flex-direction: column; align-items: stretch; }
      .support-tab-row { gap: 6px; }
      .support-tab-btn { flex: 1 1 calc(50% - 6px); text-align: center; }
      .support-thread-meta { flex-direction: column; align-items: flex-start; gap: 4px; }
    }
  </style>
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

      <section class="support-shell">
        <div class="support-card">
          <h2>Start a new request</h2>
          <p class="card-small" style="margin-top:-4px; margin-bottom:12px;">
            You can submit up to 3 requests today. Remaining: <strong><?php echo intval($dailyRequestRemaining); ?></strong>
          </p>
          <form method="post" action="support.php">
            <input type="hidden" name="new_ticket" value="1">
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
            <div class="form-actions">
              <button type="submit" class="button" <?php echo ($dailyRequestRemaining <= 0) ? 'disabled' : ''; ?>>Send Message</button>
            </div>
          </form>

          <div class="support-tab-row" role="tablist" aria-label="Conversation tabs">
            <button type="button" class="support-tab-btn active" data-tab="active">Active Chats</button>
            <button type="button" class="support-tab-btn" data-tab="history">Chat History</button>
          </div>

          <?php if (empty($activeConversations) && empty($closedConversations)): ?>
            <p class="card-small">No support tickets yet. Your first ticket will appear here.</p>
          <?php else: ?>
            <div id="support-active-list" class="support-thread-list">
              <?php if (!empty($activeConversations)): ?>
                <?php foreach ($activeConversations as $conversation): ?>
                  <a class="support-thread-item <?php echo ($selectedConversation && $selectedConversation['id'] == $conversation['id']) ? 'active' : ''; ?>" href="support.php?conversation_id=<?php echo intval($conversation['id']); ?>">
                    <div class="support-thread-meta">
                      <strong><?php echo htmlspecialchars($conversation['subject']); ?></strong>
                      <span class="support-chip <?php echo htmlspecialchars($conversation['status']); ?>"><?php echo htmlspecialchars(ucfirst($conversation['status'])); ?></span>
                    </div>
                    <div class="support-thread-meta" style="margin-top:6px;">
                      <span><?php echo date('M d, Y h:i A', strtotime($conversation['updated_at'])); ?></span>
                      <span>#<?php echo intval($conversation['id']); ?></span>
                    </div>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="support-empty">No active chats yet.</div>
              <?php endif; ?>
            </div>

            <div id="support-history-list" class="support-thread-list" style="display:none;">
              <?php if (!empty($closedConversations)): ?>
                <?php foreach ($closedConversations as $conversation): ?>
                  <a class="support-thread-item <?php echo ($selectedConversation && $selectedConversation['id'] == $conversation['id']) ? 'active' : ''; ?>" href="support.php?conversation_id=<?php echo intval($conversation['id']); ?>">
                    <div class="support-thread-meta">
                      <strong><?php echo htmlspecialchars($conversation['subject']); ?></strong>
                      <span class="support-chip <?php echo htmlspecialchars($conversation['status']); ?>"><?php echo htmlspecialchars(ucfirst($conversation['status'])); ?></span>
                    </div>
                    <div class="support-thread-meta" style="margin-top:6px;">
                      <span><?php echo date('M d, Y h:i A', strtotime($conversation['updated_at'])); ?></span>
                      <span>#<?php echo intval($conversation['id']); ?></span>
                    </div>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="support-empty">No chat history yet.</div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="support-card support-chat-panel">
          <?php if ($selectedConversation): ?>
            <div class="support-thread-meta" style="margin-bottom:12px;">
              <strong><?php echo htmlspecialchars($selectedConversation['subject']); ?></strong>
              <span id="support-status-badge" class="support-chip <?php echo htmlspecialchars($selectedConversation['status']); ?>"><?php echo htmlspecialchars(ucfirst($selectedConversation['status'])); ?></span>
            </div>
            <div id="support-messages-list" class="support-messages-list">
              <?php foreach ($messages as $msg): ?>
                <?php $senderLabel = ($msg['sender_type'] === 'member') ? 'You' : (($msg['sender_type'] === 'admin') ? 'Owner' : 'Staff'); ?>
                <div class="support-message <?php echo ($msg['sender_type'] === 'member') ? 'me' : 'staff'; ?>">
                  <div class="support-thread-meta" style="margin-bottom:6px;">
                    <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>
                    <span><?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?></span>
                  </div>
                  <div style="margin-bottom:8px; color:#f6c451; font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em;"><?php echo htmlspecialchars($senderLabel); ?></div>
                  <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                </div>
              <?php endforeach; ?>
            </div>

            <div id="reply-closed-note" class="support-status-note" <?php echo ($selectedConversation['status'] === 'closed') ? 'style="display:block;"' : ''; ?>>This conversation is closed and no longer accepts replies.</div>
            <form id="reply-form" method="post" action="support.php?conversation_id=<?php echo intval($selectedConversation['id']); ?>" <?php echo ($selectedConversation['status'] === 'closed') ? 'style="display:none;"' : ''; ?>>
              <input type="hidden" name="conversation_id" value="<?php echo intval($selectedConversation['id']); ?>">
              <div class="form-row">
                <div style="grid-column:1/-1;">
                  <label for="reply_message">Reply</label>
                  <textarea id="reply_message" name="reply_message" required></textarea>
                </div>
              </div>
              <div class="form-actions">
                <button id="reply-submit" type="submit" class="button">Send Reply</button>
              </div>
            </form>
          <?php else: ?>
            <div class="support-empty-state">
              <h3>Start a new request first</h3>
              <p>Create a support request on the left and your conversation will appear here.</p>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>
  <script src="../../assets/js/member-sidebar-toggle.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var tabButtons = document.querySelectorAll('.support-tab-btn');
      var activeList = document.getElementById('support-active-list');
      var historyList = document.getElementById('support-history-list');

      function setTab(tab) {
        tabButtons.forEach(function (button) {
          var isActive = button.getAttribute('data-tab') === tab;
          button.classList.toggle('active', isActive);
        });
        if (activeList && historyList) {
          activeList.style.display = tab === 'active' ? 'grid' : 'none';
          historyList.style.display = tab === 'history' ? 'grid' : 'none';
        }
      }

      tabButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          setTab(button.getAttribute('data-tab'));
        });
      });

      if (activeList && historyList) {
        var hasActiveItems = activeList.querySelector('.support-thread-item') !== null;
        setTab(hasActiveItems ? 'active' : 'history');
      }

      var conversationId = <?php echo json_encode(intval($selectedConversation['id'] ?? 0)); ?>;
      var messagesList = document.getElementById('support-messages-list');
      var statusBadge = document.getElementById('support-status-badge');
      var replyForm = document.getElementById('reply-form');
      var replyClosedNote = document.getElementById('reply-closed-note');
      var replyTextarea = document.getElementById('reply_message');
      var replySubmit = document.getElementById('reply-submit');
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
        if (replyForm) {
          var isClosed = data.status === 'closed';
          replyForm.style.display = isClosed ? 'none' : 'block';
          if (replyClosedNote) {
            replyClosedNote.style.display = isClosed ? 'block' : 'none';
          }
          if (replyTextarea) {
            replyTextarea.disabled = isClosed;
          }
          if (replySubmit) {
            replySubmit.disabled = isClosed;
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
  <?php include "../../component/landingPage-footer.php"; ?>
</body>
</html>
