<?php
/**
 * DreamIT Platform - Email / PHPMailer Configuration
 *
 * Uses PHPMailer with Gmail SMTP.
 * All credentials are loaded from the .env file - NEVER hardcoded.
 * Auto-detects dev vs production: on dev failures write to log and
 * optionally show OTP in session; on production always send real email.
 */

if (!defined('EMAIL_CONFIG_LOADED')) {
    define('EMAIL_CONFIG_LOADED', true);
}

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// ─── Config constants ────────────────────────────────────────────────────────
define('MAIL_HOST',     env('MAIL_HOST',    env('SMTP_HOST',    'smtp.gmail.com')));
define('MAIL_PORT',     (int) env('MAIL_PORT', env('SMTP_PORT', 587)));
define('MAIL_USERNAME', env('MAIL_USERNAME', env('SMTP_USERNAME', '')));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', env('SMTP_PASSWORD', '')));
define('MAIL_LOG',      filter_var(env('MAIL_LOG', 'false'), FILTER_VALIDATE_BOOLEAN));
define('MAIL_DEBUG',    (int) env('MAIL_DEBUG', 0));
define('MAIL_TIMEOUT',  (int) env('MAIL_TIMEOUT', 30));
define('MAIL_THROW',    filter_var(env('MAIL_THROW', 'false'), FILTER_VALIDATE_BOOLEAN));

// From address: prefer SMTP_USERNAME so Gmail actually accepts it
define('EMAIL_FROM_ADDRESS', env('EMAIL_FROM_ADDRESS', MAIL_USERNAME));
define('EMAIL_FROM_NAME',    env('EMAIL_FROM_NAME', 'Dream IT Naogaon'));

// Detect encryption from port (587 → TLS, 465 → SSL)
function _detect_smtp_secure(): string {
    $port = MAIL_PORT;
    if ($port === 465) return PHPMailer::ENCRYPTION_SMTPS;   // SSL
    if ($port === 587) return PHPMailer::ENCRYPTION_STARTTLS; // TLS
    $manual = env('MAIL_ENCRYPTION', '');
    if (strtolower($manual) === 'ssl')  return PHPMailer::ENCRYPTION_SMTPS;
    if (strtolower($manual) === 'tls')  return PHPMailer::ENCRYPTION_STARTTLS;
    return PHPMailer::ENCRYPTION_STARTTLS;
}

// ─── Logging helper ──────────────────────────────────────────────────────────
function _mail_log(string $level, string $message): void {
    if (!MAIL_LOG) return;
    $logFile = env('MAIL_LOG_FILE', '') ?: __DIR__ . '/../storage/logs/mailer.log';
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

// ─── Core send function ───────────────────────────────────────────────────────
/**
 * Send an HTML email via PHPMailer / Gmail SMTP.
 *
 * @param  string  $to         Recipient email
 * @param  string  $subject    Email subject
 * @param  string  $htmlBody   HTML body
 * @param  string  $textBody   Plain-text fallback (auto-generated if empty)
 * @param  array   $replyTo    ['email' => '', 'name' => ''] optional reply-to
 * @return bool
 */
function send_email(
    string $to,
    string $subject,
    string $htmlBody,
    string $textBody = '',
    array  $replyTo  = []
): bool {

    $mail = new PHPMailer(true); // true = throw exceptions

    try {
        // ── Server settings ──
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = _detect_smtp_secure();
        $mail->Port       = MAIL_PORT;
        $mail->Timeout    = MAIL_TIMEOUT;

        // Debug level (0 = off in production)
        $mail->SMTPDebug  = MAIL_DEBUG;

        // ── Anti-spoofing: From must match authenticated SMTP user on Gmail ──
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);

        if (!empty($replyTo['email'])) {
            $mail->addReplyTo($replyTo['email'], $replyTo['name'] ?? '');
        }

        $mail->addAddress($to);

        // ── Content ──
        $mail->isHTML(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();

        _mail_log('info', "Email sent to {$to} | Subject: {$subject}");
        return true;

    } catch (PHPMailerException $e) {
        _mail_log('error', "PHPMailer failed to {$to} | {$e->getMessage()}");

        if (MAIL_THROW) {
            throw $e;
        }
        return false;

    } catch (\Exception $e) {
        _mail_log('error', "Unexpected mailer error to {$to} | {$e->getMessage()}");
        return false;
    }
}

// ─── OTP email template ───────────────────────────────────────────────────────
/**
 * Send a 2FA OTP code to the given address.
 *
 * @param  string $to    Recipient email address
 * @param  string $otp   Plain-text 6-digit code
 * @param  string $name  Recipient display name (optional)
 * @return bool
 */
function send_otp_email(string $to, string $otp, string $name = 'Admin'): bool {
    $subject = 'Your Dream IT Admin OTP Code';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>OTP Code</title>
</head>
<body style="margin:0;padding:0;background:#0f0f1a;font-family:Inter,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr><td align="center" style="padding:40px 16px;">
  <table width="560" cellpadding="0" cellspacing="0" role="presentation"
         style="background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:16px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.5);">
    <!-- Header -->
    <tr><td style="background:linear-gradient(135deg,#6c63ff,#a855f7);padding:32px 40px;text-align:center;">
      <p style="margin:0;font-size:28px;font-weight:800;color:#fff;letter-spacing:-0.5px;">🔐 Dream IT Admin</p>
      <p style="margin:8px 0 0;font-size:14px;color:rgba(255,255,255,0.8);">Two-Factor Authentication</p>
    </td></tr>
    <!-- Body -->
    <tr><td style="padding:40px 40px 32px;">
      <p style="color:#c4c4de;font-size:15px;line-height:1.6;margin:0 0 24px;">
        Hi <strong style="color:#fff;">{$name}</strong>,<br><br>
        Use the one-time passcode below to complete your login.
        This code is valid for <strong style="color:#a855f7;">10 minutes</strong> and can only be used once.
      </p>
      <!-- OTP Box -->
      <div style="text-align:center;margin:32px 0;">
        <div style="display:inline-block;background:rgba(108,99,255,0.15);border:2px solid rgba(108,99,255,0.5);
                    border-radius:12px;padding:20px 48px;">
          <span style="font-size:42px;font-weight:900;letter-spacing:12px;color:#a855f7;
                       font-family:'Courier New',monospace;">{$otp}</span>
        </div>
      </div>
      <p style="color:#888;font-size:13px;line-height:1.6;margin:24px 0 0;border-top:1px solid rgba(255,255,255,0.05);padding-top:20px;">
        ⚠️ If you did not initiate this login, please <strong style="color:#f44">immediately change your password</strong>
        and contact your system administrator. Do <strong>not</strong> share this code with anyone.
      </p>
    </td></tr>
    <!-- Footer -->
    <tr><td style="padding:20px 40px;text-align:center;background:rgba(0,0,0,0.2);">
      <p style="margin:0;color:#555;font-size:12px;">
        © Dream IT Naogaon Institute &nbsp;|&nbsp; Automated security email — do not reply
      </p>
    </td></tr>
  </table>
</td></tr>
</table>
</body>
</html>
HTML;

    $text = "Your Dream IT Admin OTP Code: {$otp}\n\nThis code expires in 10 minutes.\nDo not share this with anyone.";

    return send_email($to, $subject, $html, $text);
}

/**
 * Send password reset link email.
 */
function send_password_reset_email(string $to, string $resetUrl, string $name = 'Admin'): bool {
    $subject = 'Password Reset Request – Dream IT Admin';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Password Reset</title></head>
<body style="margin:0;padding:0;background:#0f0f1a;font-family:Inter,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:40px 16px;">
  <table width="560" cellpadding="0" cellspacing="0"
         style="background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:16px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.5);">
    <tr><td style="background:linear-gradient(135deg,#f59e0b,#ef4444);padding:32px 40px;text-align:center;">
      <p style="margin:0;font-size:26px;font-weight:800;color:#fff;">🔑 Password Reset</p>
      <p style="margin:8px 0 0;font-size:14px;color:rgba(255,255,255,0.8);">Dream IT Admin Console</p>
    </td></tr>
    <tr><td style="padding:40px 40px 32px;">
      <p style="color:#c4c4de;font-size:15px;line-height:1.6;margin:0 0 24px;">
        Hi <strong style="color:#fff;">{$name}</strong>,<br><br>
        We received a request to reset your admin password. Click the button below to set a new password.
        This link expires in <strong style="color:#f59e0b;">1 hour</strong>.
      </p>
      <div style="text-align:center;margin:32px 0;">
        <a href="{$resetUrl}"
           style="display:inline-block;background:linear-gradient(135deg,#f59e0b,#ef4444);
                  color:#fff;text-decoration:none;font-size:16px;font-weight:700;
                  padding:14px 40px;border-radius:8px;letter-spacing:0.3px;">
          Reset My Password
        </a>
      </div>
      <p style="color:#555;font-size:12px;text-align:center;word-break:break-all;">
        Or copy this link: <br><a href="{$resetUrl}" style="color:#a855f7;">{$resetUrl}</a>
      </p>
      <p style="color:#888;font-size:13px;line-height:1.6;margin:24px 0 0;border-top:1px solid rgba(255,255,255,0.05);padding-top:20px;">
        ⚠️ If you did not request a password reset, you can safely ignore this email.
        Your password will not be changed.
      </p>
    </td></tr>
    <tr><td style="padding:20px 40px;text-align:center;background:rgba(0,0,0,0.2);">
      <p style="margin:0;color:#555;font-size:12px;">© Dream IT Naogaon Institute — Automated email, do not reply</p>
    </td></tr>
  </table>
</td></tr></table>
</body>
</html>
HTML;

    $text = "Password Reset Link for Dream IT Admin:\n{$resetUrl}\n\nThis link expires in 1 hour.\nIf you did not request this, ignore this email.";

    return send_email($to, $subject, $html, $text);
}
