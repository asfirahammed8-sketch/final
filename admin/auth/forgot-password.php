<?php
/**
 * DreamIT Admin Console - Forgot Password View
 * Lightweight Blue & White Executive Theme
 */
require_once __DIR__ . '/../config.php';

$successMsg = $_SESSION['admin_flash_success'] ?? '';
$errorMsg   = $_SESSION['admin_flash_error'] ?? '';

unset($_SESSION['admin_flash_success'], $_SESSION['admin_flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | DreamIT Console</title>

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
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
    </div>
    <h1 style="font-size:21px;font-weight:800;color:var(--brand-primary);letter-spacing:-0.4px;">Password Recovery</h1>
    <p style="font-size:13px;color:var(--admin-text-muted);margin-top:6px;">Enter your verified administrator email to receive secure recovery instructions.</p>
  </div>

  <?php if (!empty($successMsg)): ?>
    <div style="background:#ECFDF5;border:1px solid rgba(5,150,105,0.25);color:#047857;padding:13px;border-radius:8px;font-size:13px;margin-bottom:20px;line-height:1.5;">
      &#10003; <?= e($successMsg) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errorMsg)): ?>
    <div style="background:#FFF1F2;border:1px solid rgba(225,29,72,0.25);color:#BE123C;padding:13px;border-radius:8px;font-size:13px;margin-bottom:20px;line-height:1.5;">
      &times; <?= e($errorMsg) ?>
    </div>
  <?php endif; ?>

  <form action="<?= admin_url('api/auth/forgot-password') ?>" method="POST" id="forgotPassForm">
    <input type="hidden" name="csrf_token" value="<?= admin_csrf_token() ?>">

    <div class="form-group">
      <label class="form-label" for="resetEmail">Email Address <span class="required">*</span></label>
      <input type="email" id="resetEmail" name="email" class="form-control" placeholder="admin@domain.com" required autofocus autocomplete="email" />
    </div>

    <button type="submit" class="btn-submit-action" id="btnForgotSubmit">
      <span>Send Recovery Instructions</span>
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </button>
  </form>

  <div style="text-align:center;margin-top:22px;font-size:13px;">
    <a href="<?= admin_url('login') ?>" style="color:var(--brand-accent);text-decoration:none;font-weight:600;">&larr; Return to Sign In</a>
  </div>

</div>

</body>
</html>
