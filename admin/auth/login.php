<?php
/**
 * DreamIT Admin Console - Production Sign In & Two-Factor OTP Portal
 * Lightweight Blue & White Executive Theme
 * Real-time PHPMailer OTP dispatch, NO bypass, AJAX transitions, and live countdown.
 */
require_once __DIR__ . '/../config.php';

// If already authenticated, redirect to dashboard
if (current_admin()) {
    header('Location: ' . admin_url('dashboard'));
    exit;
}

$errorMessage = $_SESSION['admin_login_error'] ?? '';
unset($_SESSION['admin_login_error']);

$successMessage = $_SESSION['admin_flash_success'] ?? '';
unset($_SESSION['admin_flash_success']);

$step = $_SESSION['admin_login_step'] ?? 'credentials';
$pendingEmail = $_SESSION['admin_pending_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrative Sign In | DreamIT Console</title>

  <meta name="admin-csrf" content="<?= admin_csrf_token() ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= base_url('admin/assets/css/admin-theme.css') ?>">
  <link rel="stylesheet" href="<?= base_url('admin/assets/css/admin-components.css') ?>">

  <style>
    body.login-body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #F8FAFC;
      background-image:
        radial-gradient(circle at 10% 20%, rgba(11, 32, 70, 0.04) 0%, transparent 40%),
        radial-gradient(circle at 90% 80%, rgba(37, 99, 235, 0.05) 0%, transparent 45%),
        linear-gradient(180deg, #F8FAFC 0%, #EFF6FF 100%);
      padding: 24px 16px;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .login-container {
      width: 100%;
      max-width: 460px;
      margin: 0 auto;
    }

    .brand-header-row {
      text-align: center;
      margin-bottom: 28px;
    }

    .brand-logo-pill {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 14px;
      text-decoration: none;
    }

    .brand-logo-badge {
      width: 48px;
      height: 48px;
      border-radius: 14px;
      background: linear-gradient(135deg, #0B2046, #16366E);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 20px -4px rgba(11, 32, 70, 0.25);
      color: #FFFFFF;
    }

    .brand-logo-badge svg {
      width: 26px;
      height: 26px;
    }

    .brand-title-wrap h1 {
      font-size: 22px;
      font-weight: 800;
      color: var(--brand-primary);
      margin: 0;
      letter-spacing: -0.5px;
    }

    .brand-title-wrap h1 span {
      color: var(--brand-accent);
    }

    .brand-subtitle {
      font-size: 13.5px;
      color: var(--admin-text-muted);
      margin-top: 4px;
    }

    .auth-card {
      background: #FFFFFF;
      border: 1px solid var(--admin-border);
      border-radius: var(--admin-radius-xl);
      padding: 38px 36px;
      box-shadow: 0 16px 40px -10px rgba(11, 32, 70, 0.08), 0 4px 12px rgba(0, 0, 0, 0.03);
      position: relative;
    }

    .auth-title-row {
      text-align: center;
      margin-bottom: 24px;
    }

    .auth-main-heading {
      font-size: 21px;
      font-weight: 800;
      color: var(--brand-primary);
      letter-spacing: -0.4px;
    }

    .auth-sub-heading {
      font-size: 13px;
      color: var(--admin-text-muted);
      margin-top: 6px;
      line-height: 1.5;
    }

    .auth-alert {
      padding: 12px 16px;
      border-radius: var(--admin-radius-sm);
      font-size: 13px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      line-height: 1.5;
    }

    .auth-alert-error {
      background: #FFF1F2;
      border: 1px solid rgba(225, 29, 72, 0.25);
      color: #BE123C;
    }

    .auth-alert-success {
      background: #ECFDF5;
      border: 1px solid rgba(5, 150, 105, 0.25);
      color: #047857;
    }

    .password-wrapper {
      position: relative;
    }

    .btn-toggle-pass {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--admin-text-dim);
      cursor: pointer;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition-smooth);
    }

    .btn-toggle-pass:hover {
      color: var(--brand-primary);
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
    }

    .btn-submit-action:hover:not(:disabled) {
      background: var(--brand-primary-hover);
      border-color: var(--brand-primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(11, 32, 70, 0.22);
    }

    .btn-submit-action:disabled {
      opacity: 0.7;
      cursor: not-allowed;
    }

    .otp-code-input {
      text-align: center;
      font-size: 28px;
      letter-spacing: 10px;
      font-weight: 800;
      color: var(--brand-primary);
      padding: 12px;
      font-family: 'Courier New', monospace;
    }

    .resend-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 18px;
      padding-top: 16px;
      border-top: 1px solid var(--admin-border);
      font-size: 13px;
    }

    .btn-resend-link {
      background: none;
      border: none;
      color: var(--brand-accent);
      font-weight: 600;
      cursor: pointer;
      padding: 0;
      font-size: 13px;
    }

    .btn-resend-link:disabled {
      color: var(--admin-text-dim);
      cursor: not-allowed;
    }

    .auth-footer-nav {
      text-align: center;
      margin-top: 24px;
      font-size: 13px;
    }

    .auth-footer-nav a {
      color: var(--admin-text-muted);
      text-decoration: none;
      transition: var(--transition-smooth);
    }

    .auth-footer-nav a:hover {
      color: var(--brand-primary);
    }
  </style>
</head>
<body class="login-body">

<div class="login-container">

  <div class="brand-header-row">
    <div class="brand-logo-pill">
      <div class="brand-logo-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
          <path d="M12 2L2 7l10 5 10-5-10-5z"/>
          <path d="M2 17l10 5 10-5"/>
          <path d="M2 12l10 5 10-5"/>
        </svg>
      </div>
      <div class="brand-title-wrap">
        <h1>Dream<span>IT</span></h1>
      </div>
    </div>
    <div class="brand-subtitle">Executive Administrative Governance Portal</div>
  </div>

  <div class="auth-card">

    <div id="authDynamicAlert" style="display:none;" class="auth-alert"></div>

    <?php if (!empty($errorMessage)): ?>
      <div class="auth-alert auth-alert-error" id="serverErrorAlert">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span><?= e($errorMessage) ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
      <div class="auth-alert auth-alert-success" id="serverSuccessAlert">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        <span><?= e($successMessage) ?></span>
      </div>
    <?php endif; ?>

    <!-- STEP 1: CREDENTIALS INPUT -->
    <div id="stepCredentialsPane" style="<?= $step === 'otp' ? 'display:none;' : '' ?>">
      <div class="auth-title-row">
        <h2 class="auth-main-heading">Sign In to Console</h2>
        <p class="auth-sub-heading">Enter your verified administrator credentials</p>
      </div>

      <form id="loginCredentialsForm" onsubmit="handleCredentialsSubmit(event)">
        <input type="hidden" name="csrf_token" value="<?= admin_csrf_token() ?>">

        <div class="form-group">
          <label class="form-label" for="loginEmail">Email Address <span class="required">*</span></label>
          <input type="email" id="loginEmail" name="email" class="form-control" placeholder="admin@domain.com" required autofocus autocomplete="email" />
        </div>

        <div class="form-group">
          <label class="form-label" for="loginPassword">Password <span class="required">*</span></label>
          <div class="password-wrapper">
            <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••••••" required autocomplete="current-password" />
            <button type="button" class="btn-toggle-pass" onclick="togglePassVisibility()" title="Toggle password visibility" aria-label="Toggle password visibility">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;font-size:13px;">
          <label style="display:inline-flex;align-items:center;gap:7px;color:var(--admin-text-muted);cursor:pointer;">
            <input type="checkbox" name="remember" checked style="accent-color:var(--brand-primary);" />
            <span>Keep session active</span>
          </label>
          <a href="<?= admin_url('forgot-password') ?>" style="color:var(--brand-accent);text-decoration:none;font-weight:600;">Forgot password?</a>
        </div>

        <button type="submit" class="btn-submit-action" id="btnSubmitCredentials">
          <span>Continue to Verification</span>
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
      </form>
    </div>

    <!-- STEP 2: REAL-TIME 2FA OTP CODE VERIFICATION -->
    <div id="stepOtpPane" style="<?= $step === 'otp' ? '' : 'display:none;' ?>">
      <div class="auth-title-row">
        <h2 class="auth-main-heading">Security Verification</h2>
        <p class="auth-sub-heading">
          Enter the 6-digit OTP code sent to<br>
          <strong id="displayPendingEmail" style="color:var(--brand-primary);"><?= e($pendingEmail) ?></strong>
        </p>
      </div>

      <form id="verifyOtpForm" onsubmit="handleOtpSubmit(event)">
        <input type="hidden" name="csrf_token" value="<?= admin_csrf_token() ?>">
        <input type="hidden" name="email" id="hiddenPendingEmail" value="<?= e($pendingEmail) ?>">

        <div class="form-group">
          <label class="form-label" for="otpInput" style="text-align:center;">6-Digit OTP Code</label>
          <input type="text" id="otpInput" name="otp_code" class="form-control otp-code-input" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required placeholder="••••••" autocomplete="one-time-code" />
          <div class="form-hint" style="text-align:center;margin-top:8px;">Valid for 10 minutes &bull; Dispatched in real time</div>
        </div>

        <button type="submit" class="btn-submit-action" id="btnSubmitOtp" style="margin-top:10px;">
          <span>Verify &amp; Enter Console</span>
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        </button>

        <div class="resend-row">
          <button type="button" class="btn-resend-link" id="btnResendOtp" onclick="handleResendOtp()" disabled>
            Resend OTP (<span id="resendTimerSpan">60</span>s)
          </button>
          <button type="button" class="btn-resend-link" onclick="switchBackToCredentials()" style="color:var(--admin-text-muted);">
            &larr; Change Email
          </button>
        </div>
      </form>
    </div>

  </div>

  <div class="auth-footer-nav">
    <a href="<?= base_url('/') ?>">&larr; Return to Public Website</a>
  </div>

</div>

<!-- Admin Toast Container -->
<div class="admin-toast-container"></div>

<script>
let resendInterval = null;
let resendSeconds = 60;

function showDynamicAlert(message, type = 'error') {
  const alertEl = document.getElementById('authDynamicAlert');
  if (!alertEl) return;
  alertEl.className = 'auth-alert ' + (type === 'success' ? 'auth-alert-success' : 'auth-alert-error');
  const icon = type === 'success'
    ? `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`
    : `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
  alertEl.innerHTML = `${icon}<span>${message}</span>`;
  alertEl.style.display = 'flex';
}

function clearDynamicAlert() {
  const alertEl = document.getElementById('authDynamicAlert');
  if (alertEl) alertEl.style.display = 'none';
  const se = document.getElementById('serverErrorAlert');
  if (se) se.style.display = 'none';
  const ss = document.getElementById('serverSuccessAlert');
  if (ss) ss.style.display = 'none';
}

function togglePassVisibility() {
  const p = document.getElementById('loginPassword');
  if (p) {
    p.type = p.type === 'password' ? 'text' : 'password';
  }
}

function startResendCountdown() {
  clearInterval(resendInterval);
  resendSeconds = 60;
  const btn = document.getElementById('btnResendOtp');
  const span = document.getElementById('resendTimerSpan');
  if (!btn || !span) return;

  btn.disabled = true;
  span.textContent = resendSeconds;

  resendInterval = setInterval(() => {
    resendSeconds--;
    if (resendSeconds <= 0) {
      clearInterval(resendInterval);
      btn.disabled = false;
      btn.innerHTML = 'Resend OTP Code';
    } else {
      span.textContent = resendSeconds;
    }
  }, 1000);
}

function switchBackToCredentials() {
  clearInterval(resendInterval);
  clearDynamicAlert();
  document.getElementById('stepOtpPane').style.display = 'none';
  document.getElementById('stepCredentialsPane').style.display = 'block';
  document.getElementById('loginPassword').value = '';
  document.getElementById('loginPassword').focus();
}

// 1. STEP 1: REALTIME CREDENTIALS SUBMISSION
async function handleCredentialsSubmit(e) {
  e.preventDefault();
  clearDynamicAlert();

  const emailInput = document.getElementById('loginEmail');
  const passInput  = document.getElementById('loginPassword');
  const btn        = document.getElementById('btnSubmitCredentials');

  const email    = emailInput.value.trim();
  const password = passInput.value;

  // Client-side input validation
  if (!email || !email.includes('@') || !email.includes('.')) {
    showDynamicAlert('Please enter a valid administrative email address.');
    emailInput.focus();
    return;
  }
  if (!password || password.length < 4) {
    showDynamicAlert('Please enter your administrator password.');
    passInput.focus();
    return;
  }

  const origBtnHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<span style="display:inline-block;animation:spin 0.8s linear infinite;">&#9696;</span> Sending real-time OTP...`;

  try {
    const csrfToken = document.querySelector('meta[name="admin-csrf"]')?.getAttribute('content') || '';
    const res = await fetch('<?= admin_url("api/auth/login") ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify({ email, password, csrf_token: csrfToken })
    });

    const data = await res.json().catch(() => null);

    if (res.ok && data && data.success) {
      // Smoothly transition to OTP step
      document.getElementById('stepCredentialsPane').style.display = 'none';
      document.getElementById('stepOtpPane').style.display = 'block';
      document.getElementById('displayPendingEmail').textContent = data.email || email;
      document.getElementById('hiddenPendingEmail').value = data.email || email;
      showDynamicAlert(data.message || 'OTP code dispatched to your email.', 'success');

      startResendCountdown();

      const otpField = document.getElementById('otpInput');
      otpField.value = '';
      otpField.focus();
    } else {
      showDynamicAlert((data && data.message) ? data.message : 'Invalid credentials. Please verify your email and password.');
      passInput.value = '';
      passInput.focus();
    }
  } catch (err) {
    showDynamicAlert('Network connection error. Please try again.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = origBtnHtml;
  }
}

// 2. STEP 2: REALTIME OTP VERIFICATION
async function handleOtpSubmit(e) {
  e.preventDefault();
  clearDynamicAlert();

  const otpInput = document.getElementById('otpInput');
  const emailVal = document.getElementById('hiddenPendingEmail').value;
  const btn      = document.getElementById('btnSubmitOtp');
  const otpCode  = otpInput.value.trim();

  // Client-side numeric check
  if (!/^\d{6}$/.test(otpCode)) {
    showDynamicAlert('Please enter the exact 6-digit numeric OTP.');
    otpInput.focus();
    return;
  }

  const origBtnHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<span style="display:inline-block;animation:spin 0.8s linear infinite;">&#9696;</span> Verifying OTP...`;

  try {
    const csrfToken = document.querySelector('meta[name="admin-csrf"]')?.getAttribute('content') || '';
    const res = await fetch('<?= admin_url("api/auth/verify-otp") ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify({ email: emailVal, otp_code: otpCode, csrf_token: csrfToken })
    });

    const data = await res.json().catch(() => null);

    if (res.ok && data && data.success) {
      showDynamicAlert('OTP Verified! Redirecting to Console...', 'success');
      btn.innerHTML = `&#10003; Verified!`;
      setTimeout(() => {
        window.location.href = data.redirect || '<?= admin_url("dashboard") ?>';
      }, 500);
    } else {
      showDynamicAlert((data && data.message) ? data.message : 'Incorrect or expired OTP code.');
      otpInput.value = '';
      otpInput.focus();
      btn.disabled = false;
      btn.innerHTML = origBtnHtml;
    }
  } catch (err) {
    showDynamicAlert('Verification failed due to network error.');
    btn.disabled = false;
    btn.innerHTML = origBtnHtml;
  }
}

// 3. RESEND OTP ACTION
async function handleResendOtp() {
  clearDynamicAlert();
  const btn = document.getElementById('btnResendOtp');
  const csrfToken = document.querySelector('meta[name="admin-csrf"]')?.getAttribute('content') || '';
  const emailVal = document.getElementById('hiddenPendingEmail').value;

  btn.disabled = true;
  btn.innerHTML = 'Sending...';

  try {
    const res = await fetch('<?= admin_url("api/auth/resend-otp") ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify({ email: emailVal, csrf_token: csrfToken })
    });

    const data = await res.json().catch(() => null);

    if (res.ok && data && data.success) {
      showDynamicAlert(data.message || 'New OTP sent to your email.', 'success');
      startResendCountdown();
    } else {
      showDynamicAlert((data && data.message) ? data.message : 'Could not resend OTP. Please wait.');
      btn.disabled = false;
      btn.innerHTML = 'Resend OTP Code';
    }
  } catch (err) {
    showDynamicAlert('Network error while requesting new OTP.');
    btn.disabled = false;
    btn.innerHTML = 'Resend OTP Code';
  }
}

// Numeric input restriction on OTP
document.getElementById('otpInput')?.addEventListener('input', function() {
  this.value = this.value.replace(/[^0-9]/g, '');
  if (this.value.length === 6) {
    document.getElementById('btnSubmitOtp')?.focus();
  }
});

// Auto-start countdown if opening directly in OTP step
<?php if ($step === 'otp'): ?>
  document.addEventListener('DOMContentLoaded', () => {
    startResendCountdown();
  });
<?php endif; ?>
</script>

</body>
</html>
