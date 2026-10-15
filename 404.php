<?php
/**
 * Custom 404 Not Found Page
 * Creative IT Institute - DreamIT Platform
 *
 * Referenced by .htaccess: ErrorDocument 404 /dreamit/404.php
 * Also used as a generic error fallback by the error handler.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

// Always set the correct HTTP status code.
// When served via ErrorDocument the status is already 404,
// but if included directly we set it explicitly.
if (http_response_code() === 200) {
    http_response_code(404);
}

$pageTitle   = '404 — Page Not Found | Creative IT Institute';
$metaDesc    = 'The page you are looking for could not be found. Browse our courses or return to the home page.';
$activeNav   = '';
$robotsMeta  = 'noindex, follow';
$canonicalUrl = canonical_url('/404');

require_once __DIR__ . '/includes/header.php';
?>

<main id="mainContent" role="main" style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:60px 20px;">
  <div style="text-align:center;max-width:540px;margin:auto;">

    <!-- 404 illustration -->
    <div aria-hidden="true" style="font-size:96px;font-weight:900;color:#1642A8;line-height:1;letter-spacing:-4px;margin-bottom:8px;">404</div>

    <h1 style="font-size:28px;font-weight:700;color:#0B2046;margin-bottom:12px;">Page Not Found</h1>
    <p style="font-size:16px;color:#64748b;line-height:1.7;margin-bottom:32px;">
      The page you're looking for doesn't exist or may have been moved.
      Try browsing our courses or return to the home page.
    </p>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="<?= base_url('/') ?>" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:#1642A8;color:#fff;border-radius:8px;font-size:15px;font-weight:600;text-decoration:none;transition:background 0.2s ease;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Back to Home
      </a>
      <a href="<?= base_url('courses/') ?>" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:transparent;color:#1642A8;border:2px solid #1642A8;border-radius:8px;font-size:15px;font-weight:600;text-decoration:none;transition:all 0.2s ease;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Browse Courses
      </a>
    </div>

    <p style="margin-top:40px;font-size:13px;color:#94a3b8;">
      Need help? <a href="<?= base_url('contact/') ?>" style="color:#1642A8;font-weight:500;text-decoration:none;">Contact us</a>
    </p>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
