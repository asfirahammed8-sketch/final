<?php
/**
 * 404 Error Page
 * Creative IT Institute - DreamIT Platform
 */

http_response_code(404);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$siteBrand = site_brand();
$pageSlug  = '404';
$pageTitle = "404 — Page Not Found | {$siteBrand}";
$metaDesc  = "The page you are looking for does not exist or has been moved on {$siteBrand}.";
$activeNav = '';
$canonicalUrl = canonical_url('/404');

$extraCss = <<<'CSS'
<style>
  .error-page {
    min-height: calc(100vh - 68px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(48px, 8vw, 96px) 0;
    background: #EEF4FF;
    position: relative;
    overflow: hidden;
  }
  .error-page::before {
    content: '404';
    position: absolute;
    font-size: clamp(200px, 30vw, 400px);
    font-weight: 900;
    color: rgba(22, 66, 168, 0.04);
    line-height: 1;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
    user-select: none;
    letter-spacing: -0.05em;
  }
  .error-content {
    text-align: center;
    position: relative;
    z-index: 2;
    max-width: 560px;
    padding: 0 24px;
  }
  .error-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #DBEAFE;
    color: #1642A8;
    border-radius: 9999px;
    padding: 6px 16px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 24px;
  }
  .error-code {
    font-size: clamp(5rem, 15vw, 9rem);
    font-weight: 900;
    color: #1642A8;
    line-height: 1;
    letter-spacing: -0.04em;
    margin-bottom: 16px;
  }
  .error-title {
    font-size: clamp(1.4rem, 2.5vw, 2rem);
    font-weight: 800;
    color: #0B2046;
    margin-bottom: 12px;
  }
  .error-desc {
    font-size: 1rem;
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 32px;
  }
  .error-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
  }
</style>
CSS;

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="error-page">
  <div class="error-content">
    <div class="error-badge">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Page Not Found
    </div>
    <div class="error-code">404</div>
    <h1 class="error-title">Oops! Page not found</h1>
    <p class="error-desc">
      The page you were looking for doesn't exist, has been moved, or is temporarily unavailable. Let's get you back on track.
    </p>
    <div class="error-actions">
      <a href="<?= base_url('index.php') ?>" class="btn-hero">Go to Homepage</a>
      <a href="<?= base_url('courses.php') ?>" class="btn-hero" style="background:transparent;color:#1642A8;border:1.5px solid #1642A8;box-shadow:none;">Browse Courses</a>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
