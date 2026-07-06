<?php
session_start();
require_once 'session_check.php';
require_once '../../app/config/connection.php';

function is_valid_name($value) {
    return preg_match('/^[A-Za-z]+(?:[\' -][A-Za-z]+)*$/', $value) === 1;
}

function is_valid_email($value) {
    return preg_match('/^[A-Za-z0-9._%+-]+@gmail\.com$/i', $value) === 1;
}

function is_valid_phone($value) {
    return preg_match('/^09\d{9}$/', $value) === 1;
}

function generate_unique_username($firstName, $lastName, $pdo, $excludeId = null) {
    $base = strtolower(preg_replace('/[^a-z]/', '', $firstName . $lastName));
    $base = $base !== '' ? $base : 'member';
    $candidate = $base;
    $counter = 1;

    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE username = ? AND id != ? LIMIT 1");
        $stmt->execute([$candidate, $excludeId]);
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $base . $counter;
        $counter++;
    }
}

$memberId = $_SESSION['member_id'];
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
$validationErrors = [];
$profileData = [
    'first_name' => $member['first_name'] ?? '',
    'last_name' => $member['last_name'] ?? '',
    'username' => $member['username'] ?? '',
    'gmail' => $member['gmail'] ?? '',
    'phone' => $member['phone'] ?? '',
    'address' => $member['address'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profileData['first_name'] = trim($_POST['first_name'] ?? '');
    $profileData['last_name'] = trim($_POST['last_name'] ?? '');
    $profileData['gmail'] = trim($_POST['gmail'] ?? '');
    $profileData['phone'] = trim($_POST['phone'] ?? '');
    $profileData['address'] = trim($_POST['address'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($profileData['first_name'] === '') {
        $validationErrors['first_name'] = 'First name is required.';
    } elseif (!is_valid_name($profileData['first_name'])) {
        $validationErrors['first_name'] = 'First name can only contain letters, spaces, apostrophes, and hyphens.';
    }

    if ($profileData['last_name'] === '') {
        $validationErrors['last_name'] = 'Last name is required.';
    } elseif (!is_valid_name($profileData['last_name'])) {
        $validationErrors['last_name'] = 'Last name can only contain letters, spaces, apostrophes, and hyphens.';
    }

    if ($profileData['gmail'] === '') {
        $validationErrors['gmail'] = 'Email is required.';
    } elseif (!is_valid_email($profileData['gmail'])) {
        $validationErrors['gmail'] = 'Please enter a valid Gmail address.';
    }

    if ($profileData['phone'] === '') {
        $validationErrors['phone'] = 'Phone number is required.';
    } elseif (!is_valid_phone($profileData['phone'])) {
        $validationErrors['phone'] = 'Phone number must be 11 digits and start with 09.';
    }

    if ($password !== '') {
        if ($confirmPassword === '') {
            $validationErrors['confirm_password'] = 'Please confirm your new password.';
        } elseif ($password !== $confirmPassword) {
            $validationErrors['confirm_password'] = 'Passwords do not match.';
        }
    }

    $profileData['username'] = generate_unique_username($profileData['first_name'], $profileData['last_name'], $pdo, $memberId);

    if (empty($validationErrors)) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE members SET first_name = ?, last_name = ?, username = ?, phone = ?, address = ?, gmail = ?, password = ? WHERE id = ?";
            $params = [$profileData['first_name'], $profileData['last_name'], $profileData['username'], $profileData['phone'], $profileData['address'], $profileData['gmail'], $hash, $memberId];
        } else {
            $sql = "UPDATE members SET first_name = ?, last_name = ?, username = ?, phone = ?, address = ?, gmail = ? WHERE id = ?";
            $params = [$profileData['first_name'], $profileData['last_name'], $profileData['username'], $profileData['phone'], $profileData['address'], $profileData['gmail'], $memberId];
        }

        $updateStmt = $pdo->prepare($sql);
        if ($updateStmt->execute($params)) {
            $successMessage = 'Your profile has been updated successfully.';
            $stmt->execute([$memberId]);
            $member = $stmt->fetch();
            $profileData = [
                'first_name' => $member['first_name'] ?? '',
                'last_name' => $member['last_name'] ?? '',
                'username' => $member['username'] ?? '',
                'gmail' => $member['gmail'] ?? '',
                'phone' => $member['phone'] ?? '',
                'address' => $member['address'] ?? ''
            ];
        } else {
            $errorMessage = 'Unable to update your profile. Please try again later.';
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
  <title>Member Profile</title>
  <link href="../../assets/css/headerComponent.css" rel="stylesheet">
  <link href="../../assets/css/footerComponents.css" rel="stylesheet">
  <link href="../../assets/css/toastednotif.css" rel="stylesheet">
  <link href="../../assets/css/member.css" rel="stylesheet">
  <style>
    .field-message {
      margin-top: 6px;
      min-height: 1.1rem;
      font-size: 0.9rem;
      color: #b91c1c;
    }
    .field-message.success {
      color: #047857;
    }
    .confirm-password-wrapper {
      margin-top: 8px;
      max-height: 0;
      opacity: 0;
      overflow: hidden;
      transition: max-height 0.25s ease, opacity 0.25s ease;
    }
    .confirm-password-wrapper.visible {
      max-height: 120px;
      opacity: 1;
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
      <a class="member-logout" href="logout.php" id="memberLogoutTrigger">Logout</a>
    </aside>

    <main class="member-main">
      <div class="page-heading">
        <div>
          <h1>My Profile</h1>
          <p class="card-small">Manage your account details and update contact information.</p>
        </div>
      </div>

      <?php if ($successMessage): ?>
        <div class="alert success"><?php echo htmlspecialchars($successMessage); ?></div>
      <?php endif; ?>
      <?php if ($errorMessage): ?>
        <div class="alert error"><?php echo htmlspecialchars($errorMessage); ?></div>
      <?php endif; ?>

      <section class="member-form">
        <form method="post" action="profile.php">
          <div class="section-card">
            <h2>Account Information</h2>
            <div class="form-row">
              <div>
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($profileData['first_name']); ?>" required>
                <div class="field-message" id="first_name_message"><?php echo isset($validationErrors['first_name']) ? htmlspecialchars($validationErrors['first_name']) : ''; ?></div>
              </div>
              <div>
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($profileData['last_name']); ?>" required>
                <div class="field-message" id="last_name_message"><?php echo isset($validationErrors['last_name']) ? htmlspecialchars($validationErrors['last_name']) : ''; ?></div>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($profileData['username']); ?>" readonly>
                <div class="field-message success">Username is auto-generated from your name.</div>
              </div>
              <div>
                <label for="gmail">Email</label>
                <input type="email" id="gmail" name="gmail" value="<?php echo htmlspecialchars($profileData['gmail']); ?>">
                <div class="field-message" id="gmail_message"><?php echo isset($validationErrors['gmail']) ? htmlspecialchars($validationErrors['gmail']) : ''; ?></div>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($profileData['phone']); ?>" inputmode="numeric" maxlength="11">
                <div class="field-message" id="phone_message"><?php echo isset($validationErrors['phone']) ? htmlspecialchars($validationErrors['phone']) : ''; ?></div>
              </div>
              <div>
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($profileData['address']); ?>">
              </div>
            </div>
            <div class="form-row">
              <div style="grid-column:1/-1;">
                <label for="password">New Password <span style="font-size:0.9rem;color:#6b7280;">(leave blank to keep current password)</span></label>
                <input type="password" id="password" name="password" placeholder="Enter a new password if you want to change it">
                <div class="field-message" id="password_message"><?php echo isset($validationErrors['confirm_password']) ? htmlspecialchars($validationErrors['confirm_password']) : ''; ?></div>
                <div id="confirm_password_wrapper" class="confirm-password-wrapper">
                  <label for="confirm_password">Confirm Password</label>
                  <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password">
                  <div class="field-message" id="confirm_password_message"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="button">Save Changes</button>
            <a href="dashboard.php" class="button secondary small">Back to Dashboard</a>
          </div>
        </form>
      </section>
    </main>
  </div>
  <script src="../../assets/js/member-sidebar-toggle.js"></script>
  <?php include "../../component/landingPage-footer.php"; ?>
  <?php include "../../component/member_logout_modal.php"; ?>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const firstNameInput = document.getElementById('first_name');
      const lastNameInput = document.getElementById('last_name');
      const usernameInput = document.getElementById('username');
      const gmailInput = document.getElementById('gmail');
      const phoneInput = document.getElementById('phone');
      const passwordInput = document.getElementById('password');
      const confirmPasswordInput = document.getElementById('confirm_password');
      const confirmPasswordWrapper = document.getElementById('confirm_password_wrapper');
      const confirmPasswordMessage = document.getElementById('confirm_password_message');
      const firstNameMessage = document.getElementById('first_name_message');
      const lastNameMessage = document.getElementById('last_name_message');
      const gmailMessage = document.getElementById('gmail_message');
      const phoneMessage = document.getElementById('phone_message');

      function sanitizeNameInput(input) {
        input.value = input.value.replace(/[^A-Za-z\s'-]/g, '').replace(/\s{2,}/g, ' ').replace(/^\s+|\s+$/g, '');
      }

      function updateUsername() {
        const generatedUsername = (firstNameInput.value + lastNameInput.value).toLowerCase().replace(/[^a-z]/g, '') || 'member';
        usernameInput.value = generatedUsername;
      }

      function setMessage(element, message) {
        if (element) {
          element.textContent = message || '';
        }
      }

      function validateName(input, messageElement, label) {
        const value = input.value.trim();
        if (!value) {
          setMessage(messageElement, label + ' is required.');
          return false;
        }
        if (!/^[A-Za-z]+(?:[' -][A-Za-z]+)*$/.test(value)) {
          setMessage(messageElement, label + ' can only contain letters, spaces, apostrophes, and hyphens.');
          return false;
        }
        setMessage(messageElement, '');
        return true;
      }

      function validateEmail() {
        const value = gmailInput.value.trim();
        if (!value) {
          setMessage(gmailMessage, 'Email is required.');
          return false;
        }
        if (!/^[A-Za-z0-9._%+-]+@gmail\.com$/i.test(value)) {
          setMessage(gmailMessage, 'Please enter a valid Gmail address.');
          return false;
        }
        setMessage(gmailMessage, '');
        return true;
      }

      function validatePhone() {
        let value = phoneInput.value.replace(/\D/g, '').slice(0, 11);
        phoneInput.value = value;
        if (!value) {
          setMessage(phoneMessage, 'Phone number is required.');
          return false;
        }
        if (!/^09\d{9}$/.test(value)) {
          setMessage(phoneMessage, 'Phone number must be 11 digits and start with 09.');
          return false;
        }
        setMessage(phoneMessage, '');
        return true;
      }

      function togglePasswordConfirm() {
        if (passwordInput.value.trim()) {
          confirmPasswordWrapper.classList.add('visible');
        } else {
          confirmPasswordWrapper.classList.remove('visible');
          confirmPasswordInput.value = '';
          setMessage(confirmPasswordMessage, '');
        }
      }

      function validatePasswordMatch() {
        if (!passwordInput.value.trim()) {
          setMessage(confirmPasswordMessage, '');
          return true;
        }
        if (!confirmPasswordInput.value.trim()) {
          setMessage(confirmPasswordMessage, 'Please confirm your new password.');
          return false;
        }
        if (passwordInput.value !== confirmPasswordInput.value) {
          setMessage(confirmPasswordMessage, 'Passwords do not match.');
          return false;
        }
        setMessage(confirmPasswordMessage, 'Passwords match.');
        confirmPasswordMessage.classList.add('success');
        return true;
      }

      firstNameInput.addEventListener('input', function () {
        sanitizeNameInput(firstNameInput);
        validateName(firstNameInput, firstNameMessage, 'First name');
        updateUsername();
      });

      lastNameInput.addEventListener('input', function () {
        sanitizeNameInput(lastNameInput);
        validateName(lastNameInput, lastNameMessage, 'Last name');
        updateUsername();
      });

      gmailInput.addEventListener('input', validateEmail);
      phoneInput.addEventListener('input', validatePhone);
      passwordInput.addEventListener('input', function () {
        togglePasswordConfirm();
        validatePasswordMatch();
      });
      confirmPasswordInput.addEventListener('input', validatePasswordMatch);

      updateUsername();
      togglePasswordConfirm();
      if (passwordInput.value.trim()) {
        validatePasswordMatch();
      }
    });
  </script>
</body>
</html>
