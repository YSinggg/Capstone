<?php
// admin_settings.php (PDO + your admin_header.php)
session_start();
require 'db.php'; // your PDO connection ($pdo)

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$adminId = (int)$_SESSION['admin_id'];
$success = "";
$error   = "";

/** Helper: fetch admin by id (PDO) */
function getAdminById(PDO $pdo, int $adminId) {
    $stmt = $pdo->prepare("SELECT admin_id, email, password FROM admin WHERE admin_id = ?");
    $stmt->execute([$adminId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$admin = getAdminById($pdo, $adminId);
if (!$admin) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

/** Handle POST (email/password updates), logic unchanged */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $currentPasswordInput = $_POST['current_password'] ?? '';

    // Accept legacy plain text OR hashed
    $dbPassword = $admin['password'] ?? '';
    $currentOk = false;
    if ($dbPassword !== '') {
        if (password_verify($currentPasswordInput, $dbPassword)) {
            $currentOk = true;
        } elseif (hash_equals($dbPassword, $currentPasswordInput)) {
            $currentOk = true;
        }
    }

    if (!$currentOk) {
        $error = "Current password is incorrect.";
    } else {
        if ($action === 'update_email') {
            $newEmail = trim($_POST['new_email'] ?? '');
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address.";
            } else {
                // Optional uniqueness check (future multi-admin)
                $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE email = ? AND admin_id <> ? LIMIT 1");
                $stmt->execute([$newEmail, $adminId]);
                if ($stmt->fetchColumn()) {
                    $error = "That email is already in use.";
                } else {
                    $stmt = $pdo->prepare("UPDATE admin SET email = ? WHERE admin_id = ?");
                    if ($stmt->execute([$newEmail, $adminId])) {
                        $success = "Email updated successfully.";
                        $_SESSION['email'] = $newEmail;
                        $admin = getAdminById($pdo, $adminId);
                    } else {
                        $error = "Failed to update email. Please try again.";
                    }
                }
            }
        } elseif ($action === 'update_password') {
            $newPass = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if (strlen($newPass) < 8) {
                $error = "New password must be at least 8 characters.";
            } elseif ($newPass !== $confirm) {
                $error = "New password and confirmation do not match.";
            } else {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE admin_id = ?");
                if ($stmt->execute([$hash, $adminId])) {
                    $success = "Password updated successfully.";
                    $admin = getAdminById($pdo, $adminId);
                } else {
                    $error = "Failed to update password. Please try again.";
                }
            }
        } else {
            $error = "Unknown action.";
        }
    }
}

// From here on, we **include your header** (it already outputs <!DOCTYPE html>, <html>, <head>, and opens <body>)
include 'admin_header.php';
?>

<!-- Page-specific styles (allowed in body since header already closed <head>) -->
<style>
  /* ====== Layout & tokens ====== */
  .wrap{
    max-width:min(980px, 100% - 32px);
    margin:clamp(16px, 2vw, 28px) auto;
    padding:0 16px;
  }
  .grid{
    display:grid;
    grid-template-columns:1fr;
    gap:clamp(14px, 2.2vw, 22px);
  }
  @media (min-width: 900px){
    .grid{ grid-template-columns:1fr 1fr; }
  }

  .card{
    background:#fff;
    border-radius:14px;
    box-shadow:0 6px 24px rgba(0,0,0,.06);
    border:1px solid #e7ecf1;
    padding:clamp(14px, 2.2vw, 22px);
  }

  h1{ margin:8px 0 16px; font-size:clamp(20px, 3.2vw, 26px); }
  h2{ margin:0 0 10px; font-size:clamp(18px, 2.6vw, 20px); }
  p.muted{ color:#6b7280; margin:.25rem 0 0; font-size:clamp(13px, 2.2vw, 14px); }

  /* ====== Forms ====== */
  label{ display:block; font-weight:700; margin:12px 0 6px; }
  input[type="email"],
  input[type="password"]{
    width:95%; /* full width on all screens */
    padding:12px 14px;
    border:1px solid #e5e7eb;
    border-radius:10px;
    background:#fff;
    color:#111827;
    outline:none;
    transition:border-color .15s, box-shadow .15s;
  }
  input[type="email"]:focus,
  input[type="password"]:focus{
    border-color:#4a8fe7;
    box-shadow:0 0 0 3px rgba(74,143,231,.18);
  }

  .actions{
    margin-top:14px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }

  /* Buttons (safe defaults; your header may already define .btn/.btn-blue) */
  .btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 14px;
    border-radius:10px;
    border:0;
    cursor:pointer;
    font-weight:700;
    color:#fff;
    text-decoration:none;
  }
  @media (max-width:640px){
    .actions .btn{ width:100%; justify-content:center; }
  }

  /* ====== Alerts ====== */
  .alert{ padding:12px 14px; border-radius:10px; margin:16px 0; font-weight:600; }
  .ok{  background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
  .bad{ background:#fef2f2; color:#7f1d1d; border:1px solid #fecaca; }

  /* ====== Dark mode (if admin_header applies .dark on <body>) ====== */
  .dark .card{ background:#0f1b24; color:#e6eef8; border-color:#193243; }
  .dark p.muted{ color:#9fb3c8; }
  .dark input[type="email"],
  .dark input[type="password"]{
    background:#0f1b24;
    border-color:#29475b;
    color:#e6eef8;
  }
  .dark input[type="email"]::placeholder,
  .dark input[type="password"]::placeholder{ color:#9fb3c8; }
  .dark .btn{ background:#4a8fe7; color:#fff; }
  .dark .ok{  background:#0f2a22; color:#9af0c9; border-color:#1f5c4b; }
  .dark .bad{ background:#2a1212; color:#f2b5b5; border-color:#6f2a2a; }
</style>

<div class="wrap">
  <?php if(!empty($success)): ?>
    <div class="alert ok"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  <?php if(!empty($error)): ?>
    <div class="alert bad"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <div class="grid">
    <!-- Change Email -->
    <form class="card" method="post" autocomplete="off">
      <h2><i class="fa-solid fa-envelope"></i> Change Email</h2>
      <p class="muted">Current email: <strong><?php echo htmlspecialchars($admin['email']); ?></strong></p>

      <label for="new_email">New Email</label>
      <input type="email" id="new_email" name="new_email" placeholder="admin@example.com" required />

      <label for="current_password_email">Current Password</label>
      <input type="password" id="current_password_email" name="current_password" placeholder="Enter current password" required />

      <div class="actions">
        <input type="hidden" name="action" value="update_email" />
        <button class="btn" type="submit" style="background:#4a8fe7;"><i class="fa-solid fa-save"></i> Update Email</button>
      </div>
    </form>

    <!-- Change Password -->
    <form class="card" method="post" autocomplete="off">
      <h2><i class="fa-solid fa-lock"></i> Change Password</h2>
      <p class="muted">Set a strong password (min 8 characters).</p>

      <label for="current_password_pwd">Current Password</label>
      <input type="password" id="current_password_pwd" name="current_password" placeholder="Enter current password" required />

      <label for="new_password">New Password</label>
      <input type="password" id="new_password" name="new_password" placeholder="New password" minlength="8" required />

      <label for="confirm_password">Confirm New Password</label>
      <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" minlength="8" required />

      <div class="actions">
        <input type="hidden" name="action" value="update_password" />
        <button class="btn" type="submit" style="background:#4a8fe7;"><i class="fa-solid fa-key"></i> Update Password</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
