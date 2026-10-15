<?php
/**
 * DreamIT Admin Console - Reset Password View
 * Lightweight Blue & White Executive Theme
 */
require_once __DIR__ . '/../config.php';

$rawToken = trim($_GET['token'] ?? '');
$db = Database::getConnection();

$isValid = false;
if (!empty($rawToken)) {
    $tokenHash = hash('sha256', $rawToken);
    $stmt = $db->prepare("SELECT id, password_reset_expires_at FROM admins WHERE password_reset_token = :tok LIMIT 1");
    $stmt->execute([':tok' => $tokenHash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['password_reset_expires_at'])) {
        $curNow = $db->query("SELECT NOW()")->fetchColumn();
        $isNotExpiredInDb = strtotime($row['password_reset_expires_at']) >= strtotime($curNow);
        $isNotExpiredInPhp = (strtotime($row['password_reset_expires_at']) >= time());
        $isValid = $isNotExpiredInDb || $isNotExpiredInPhp;
    }
}

$errorMsg = $_SESSION['admin_flash_error'] ?? '';
unset($_SESSION['admin_flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set New Password | DreamIT Console</title>

  <meta name="admin-csrf" content="<?= admin_csrf_token() ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= base_url('admin/assets/css/admin-theme.css') ?>">
  <link rel="stylesheet" href="<?= base_url('admin/assets/css/admin-components.css') ?>">

  <style>
    body.auth-page-body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #F8FAFC;
      background-image:
        radial-gradient(circle at 10% 20%, rgba(11, 32, 70, 0.04) 0%, transparent 40%),
        radial-gradient(circle at 90% 80%, rgba(37, 99, 235, 0.05) 0%, transparent 45%);
      padding: 24px 16px;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .auth-card-box {
      width: 100%;
      max-width: 440px;
      background: #FFFFFF;
      border: 1px solid var(--admin-border);
      border-radius: var(--admin-radius-xl);
      padding: 38px 36px;
      box-shadow: 0 16px 40px -10px rgba(11, 32, 70, 0.08), 0 4px 12px rgba(0, 0, 0, 0.03);
    }

    .brand-icon-wrap {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      background: var(--brand-primary-light);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--brand-primary);
      margin: 0 auto 16px;
    }

    .brand-icon-wrap svg {
      width: 24px;
      height: 24px;
    }

    .btn-submit-action {
      width: 100%;
      background: var(--brand-primary);
      color: #FFFFFF;
      border: 1px solid var(--brand-primary);
      padding: 13px;
      font-size: 14.5px;
      font-weight: 700;
      border-radius: var(--admin-radius-sm);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: var(--transition-smooth);
      box-shadow: 0 4px 12px rgba(11, 32, 70, 0.15);
      margin-top: 10px;
    }

    .btn-submit-action:hover:not(:disabled) {
      background: var(--brand-primary-hover);
      border-color: var(--brand-primary-hover);
      transform: translateY(-1px);
    }
  </style>
</head>
<body class="auth-page-body">

<div class="auth-card-box">

  <div style="text-align:center;margin-bottom:24px;">
    <div class="brand-icon-wrap">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    </div>
    <h1 style="font-size:21px;font-weight:800;color:var(--brand-primary);letter-spacing:-0.4px;">Set New Password</h1>
    <p style="font-size:13px;color:var(--admin-text-muted);margin-top:6px;">Create a secure password with at least 8 characters</p>
  </div>

  <?php if (!$isValid): ?>
    <div style="background:#FFF1F2;border:1px solid rgba(225,29,72,0.25);color:#BE123C;padding:16px;border-radius:8px;font-size:13.5px;margin-bottom:20px;text-align:center;line-height:1.5;">
      ⚠️ This password reset link is invalid or has expired (1-hour validity).
    </div>
    <div style="text-align:center;">
      <a href="<?= admin_url('forgot-password') ?>" class="topbar-btn btn-ghost" style="display:inline-flex;">Request a New Link &rarr;</a>
    </div>
  <?php else: ?>
    <?php if (!empty($errorMsg)): ?>
      <div style="background:#FFF1F2;border:1px solid rgba(225,29,72,0.25);color:#BE123C;padding:12px;border-radius:8px;font-size:13px;margin-bottom:20px;">
        &times; <?= e($errorMsg) ?>
      </div>
    <?php endif; ?>

    <form action="<?= admin_url('api/auth/reset-password') ?>" method="POST" onsubmit="return validatePasswordMatch()">
      <input type="hidden" name="csrf_token" value="<?= admin_csrf_token() ?>">
      <input type="hidden" name="token" value="<?= e($rawToken) ?>">

      <div class="form-group">
        <label class="form-label" for="newPassword">New Password <span class="required">*</span></label>
        <input type="password" id="newPassword" name="new_password" class="form-control" placeholder="Minimum 8 characters" required minlength="8" autofocus />
      </div>

      <div class="form-group">
        <label class="form-label" for="confirmPassword">Confirm Password <span class="required">*</span></label>
        <input type="password" id="confirmPassword" name="confirm_password" class="form-control" placeholder="Re-enter new password" required minlength="8" />
        <div id="passMatchMsg" style="font-size:12px;margin-top:6px;display:none;"></div>
      </div>

      <button type="submit" class="btn-submit-action">
        Update Password &amp; Enter Console
      </button>
    </form>
  <?php endif; ?>

  <div style="text-align:center;margin-top:22px;font-size:13px;">
    <a href="<?= admin_url('login') ?>" style="color:var(--brand-accent);text-decoration:none;font-weight:600;">&larr; Back to Sign In</a>
  </div>

</div>

<script>
function validatePasswordMatch() {
  const p1 = document.getElementById('newPassword')?.value || '';
  const p2 = document.getElementById('confirmPassword')?.value || '';
  const msg = document.getElementById('passMatchMsg');

  if (p1.length < 8) {
    if (msg) {
      msg.style.display = 'block';
      msg.style.color = '#BE123C';
      msg.textContent = 'Password must be at least 8 characters long.';
    }
    return false;
  }

  if (p1 !== p2) {
    if (msg) {
      msg.style.display = 'block';
      msg.style.color = '#BE123C';
      msg.textContent = 'Passwords do not match.';
    }
    return false;
  }

  return true;
}

document.getElementById('confirmPassword')?.addEventListener('input', function() {
  const p1 = document.getElementById('newPassword')?.value || '';
  const msg = document.getElementById('passMatchMsg');
  if (!msg) return;

  if (this.value.length > 0) {
    msg.style.display = 'block';
    if (this.value === p1) {
      msg.style.color = '#059669';
      msg.textContent = '✓ Passwords match';
    } else {
      msg.style.color = '#BE123C';
      msg.textContent = '✗ Passwords do not match';
    }
  } else {
    msg.style.display = 'none';
  }
});
</script>

</body>
</html>
