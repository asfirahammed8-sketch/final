<?php
/**
 * Shared Site Footer Component
 * Creative IT Institute - DreamIT Platform
 * Fully dynamic — data comes from DB settings and active courses query
 */

$contactSettings = get_setting('contact', []);
$socialSettings  = get_setting('social_media', []);
$footerSettings  = get_setting('footer', []);
$generalInfo     = get_setting('general', []);

$siteName = site_brand();
$logoUrl  = !empty($generalInfo['logo_url']) ? sanitize_image_url($generalInfo['logo_url']) : null;

// ── Contact ───────────────────────────────────────────────────────────────────
$phones = $contactSettings['phones'] ?? ['+880 1777308777', '+880 1624666000', '+880 1624888444', '+880 1966177177'];
if (is_string($phones)) {
    $phones = array_values(array_filter(array_map('trim', explode("\n", $phones))));
}
$email   = $contactSettings['email']   ?? 'info@creativeitinstitute.com';
$address = $contactSettings['address'] ?? "Momtaz Plaza (4th Floor)\nHouse # 07, Road # 04\nDhanmondi, Dhaka-1205";

// ── CTA band ─────────────────────────────────────────────────────────────────
$ctaTitle = $footerSettings['cta_title'] ?? 'Admission Is Going on';
$ctaDesc  = $footerSettings['cta_desc']  ?? 'Enroll in any online or offline course now — take one step towards a competent career.';

// ── Social links ──────────────────────────────────────────────────────────────
$socialLinks = [
    'facebook'  => ['url' => $socialSettings['facebook']  ?? 'https://www.facebook.com/CreativeITInstitute',        'label' => 'Facebook'],
    'youtube'   => ['url' => $socialSettings['youtube']   ?? 'https://www.youtube.com/@CreativeITInstitute',        'label' => 'YouTube'],
    'linkedin'  => ['url' => $socialSettings['linkedin']  ?? 'https://www.linkedin.com/school/creativeitinstitute/', 'label' => 'LinkedIn'],
    'instagram' => ['url' => $socialSettings['instagram'] ?? '', 'label' => 'Instagram'],
    'twitter'   => ['url' => $socialSettings['twitter']   ?? '', 'label' => 'X (Twitter)'],
    'tiktok'    => ['url' => $socialSettings['tiktok']    ?? '', 'label' => 'TikTok'],
];

// ── Popular Courses ───────────────────────────────────────────────────────────
// Priority 1: deduplicated active courses marked priority='top' or is_featured=1
// Priority 2: fallback to active courses
$popularCourses = [];
try {
    $db = Database::getConnection();

    // Deduplicated homepage top/featured courses (picks cleanest/latest entry per title)
    $popularCourses = $db->query(
        "SELECT c1.title, c1.slug
         FROM courses c1
         INNER JOIN (
             SELECT MAX(id) AS max_id
             FROM courses
             WHERE status = 'active' AND (priority = 'top' OR is_featured = 1)
             GROUP BY title
         ) c2 ON c1.id = c2.max_id
         ORDER BY c1.sort_order ASC, c1.id DESC
         LIMIT 6"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Fallback if no featured courses
    if (empty($popularCourses)) {
        $popularCourses = $db->query(
            "SELECT c1.title, c1.slug
             FROM courses c1
             INNER JOIN (
                 SELECT MAX(id) AS max_id
                 FROM courses
                 WHERE status = 'active'
                 GROUP BY title
             ) c2 ON c1.id = c2.max_id
             ORDER BY c1.sort_order ASC, c1.id DESC
             LIMIT 6"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { /* graceful fallback */ }

// ── Others: last 4 student projects + last 3 success stories ─────────────────
$footerProjects = [];
$footerStories  = [];
try {
    $db = $db ?? Database::getConnection();

    $footerProjects = $db->query(
        "SELECT title, slug FROM projects
         WHERE status = 'published'
         ORDER BY created_at DESC
         LIMIT 4"
    )->fetchAll();

    $footerStories = $db->query(
        "SELECT student_name, headline FROM success_stories
         WHERE is_active = 1
         ORDER BY id DESC
         LIMIT 3"
    )->fetchAll();
} catch (Exception $e) { /* graceful fallback */ }

$copyrightYear = date('Y');
?>
<footer class="site-footer-wrapper" role="contentinfo">

  <!-- 1. ADMISSION CTA BAND -->
  <div class="admission-cta-box">
    <div class="c-container">
      <div class="admission-cta-content">
        <div class="admission-text-block">
          <h2 class="admission-title"><?= e($ctaTitle) ?></h2>
          <p class="admission-desc"><?= e($ctaDesc) ?></p>
        </div>
        <div class="admission-btn-group">
          <a href="<?= base_url('courses/') ?>"  class="btn-footer-outline">Browse Courses</a>
          <a href="<?= base_url('contact/') ?>"  class="btn-footer-outline">Contact Us</a>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. 4-COLUMN NAVIGATION LINKS -->
  <div class="c-container">
    <div class="footer-nav-grid">

      <!-- Col 1: Contact Info -->
      <div>
        <h3 class="footer-col-title">Contact</h3>

        <div class="contact-item-row">
          <div class="contact-icon-pill" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
            </svg>
          </div>
          <div>
            <strong style="color:#fff;display:block;margin-bottom:2px;">Head Office:</strong>
            <?= nl2br(e((string)$address)) ?>
          </div>
        </div>

        <?php if (!empty($phones)): ?>
        <div class="contact-item-row">
          <div class="contact-icon-pill" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.21 15a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.14 4.11h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
          </div>
          <div>
            <?php foreach ($phones as $p): ?>
              <a href="tel:<?= preg_replace('/\s+/', '', e($p)) ?>" class="footer-link"><?= e($p) ?></a><br>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($email)): ?>
        <div class="contact-item-row">
          <div class="contact-icon-pill" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>
          <div>
            <a href="mailto:<?= e($email) ?>" class="footer-link"><?= e($email) ?></a>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Col 2: Quick Links -->
      <div>
        <h3 class="footer-col-title">Quick Links</h3>
        <ul class="footer-links-list">
          <li><a href="<?= base_url('success-stories/') ?>" class="footer-link">Success Stories</a></li>
          <li><a href="<?= base_url('gallery/') ?>"         class="footer-link">Gallery</a></li>
          <li><a href="<?= base_url('projects/') ?>"     class="footer-link">Student Work</a></li>
          <li><a href="<?= base_url('faq/') ?>"             class="footer-link">FAQ</a></li>
          <li><a href="<?= base_url('about/') ?>"           class="footer-link">About Us</a></li>
          <li><a href="<?= base_url('contact/') ?>"         class="footer-link">Contact</a></li>
          <li><a href="<?= base_url('privacy/') ?>"         class="footer-link">Privacy Policy</a></li>
          <li><a href="<?= base_url('sitemap/') ?>"         class="footer-link">Sitemap</a></li>
        </ul>
      </div>

      <!-- Col 3: Popular Courses — homepage courses first, fallback to latest -->
      <div>
        <h3 class="footer-col-title">Popular Courses</h3>
        <ul class="footer-links-list">
          <?php if (!empty($popularCourses)): ?>
            <?php foreach ($popularCourses as $course): ?>
              <li>
                <a href="<?= clean_course_url($course['slug']) ?>" class="footer-link">
                  <?= e($course['title']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li><a href="<?= base_url('courses/') ?>" class="footer-link">Browse All Courses</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Col 4: Others — latest student projects + latest success stories -->
      <div>
        <h3 class="footer-col-title">Others</h3>
        <ul class="footer-links-list">

          <?php foreach ($footerProjects as $proj): ?>
            <li>
              <a href="<?= base_url('project-detail.php?slug=' . urlencode($proj['slug'])) ?>" class="footer-link">
                <?= e($proj['title']) ?>
              </a>
            </li>
          <?php endforeach; ?>

          <?php foreach ($footerStories as $story): ?>
            <li>
              <a href="<?= base_url('success-stories/') ?>" class="footer-link">
                <?= e($story['student_name']) ?><?= !empty($story['headline']) ? ' — ' . e(mb_substr($story['headline'], 0, 40)) . (mb_strlen($story['headline']) > 40 ? '…' : '') : '' ?>
              </a>
            </li>
          <?php endforeach; ?>

          <?php if (empty($footerProjects) && empty($footerStories)): ?>
            <li><a href="<?= base_url('about/') ?>"           class="footer-link">About Us</a></li>
            <li><a href="<?= base_url('success-stories/') ?>" class="footer-link">Career Placement</a></li>
            <li><a href="<?= base_url('contact/') ?>"         class="footer-link">Contact</a></li>
            <li><a href="<?= base_url('gallery/') ?>"         class="footer-link">Gallery</a></li>
            <li><a href="<?= base_url('faq/') ?>"             class="footer-link">FAQ</a></li>
          <?php endif; ?>

        </ul>
      </div>

    </div><!-- /.footer-nav-grid -->
  </div>

  <!-- 3. SUB-FOOTER -->
  <div class="sub-footer-bar">
    <div class="c-container">
      <div class="sub-footer-inner">

        <!-- Logo -->
        <a href="<?= base_url('/') ?>" class="sub-footer-logo" aria-label="<?= e($siteName) ?> — Back to top">
          <?php if ($logoUrl): ?>
            <img src="<?= e($logoUrl) ?>"
                 alt="<?= e($siteName) ?>"
                 style="height:36px;max-width:180px;object-fit:contain;"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
            <div class="sub-footer-logo-fallback" style="display:none;">
              <svg viewBox="0 0 40 40" fill="none" aria-hidden="true">
                <path d="M8 8C4 12 4 28 8 32C12 36 28 36 32 30" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                <circle cx="20" cy="20" r="5" fill="currentColor"/>
              </svg>
              <div class="sub-footer-logo-text">
                <strong><?= e($siteName) ?></strong>
              </div>
            </div>
          <?php else: ?>
            <svg viewBox="0 0 40 40" fill="none" aria-hidden="true">
              <path d="M8 8C4 12 4 28 8 32C12 36 28 36 32 30" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
              <circle cx="20" cy="20" r="5" fill="currentColor"/>
            </svg>
            <div class="sub-footer-logo-text">
              <strong><?= e($siteName) ?></strong>
            </div>
          <?php endif; ?>
        </a>

        <!-- Legal copy -->
        <p class="sub-footer-copy">
          An ISO 9001:2015 Certified Institute &copy; <?= $copyrightYear ?> <?= e($siteName) ?>. All rights reserved.<br>
          <a href="<?= base_url('sitemap/') ?>">Sitemap</a>
        </p>

        <!-- Social icons — only rendered when URL is set in DB -->
        <div class="social-icons-group">

          <?php if (!empty($socialLinks['facebook']['url'])): ?>
          <a href="<?= e($socialLinks['facebook']['url']) ?>" class="social-icon-btn"
             aria-label="<?= e($socialLinks['facebook']['label']) ?>" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          </a>
          <?php endif; ?>

          <?php if (!empty($socialLinks['youtube']['url'])): ?>
          <a href="<?= e($socialLinks['youtube']['url']) ?>" class="social-icon-btn"
             aria-label="<?= e($socialLinks['youtube']['label']) ?>" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
          </a>
          <?php endif; ?>

          <?php if (!empty($socialLinks['linkedin']['url'])): ?>
          <a href="<?= e($socialLinks['linkedin']['url']) ?>" class="social-icon-btn"
             aria-label="<?= e($socialLinks['linkedin']['label']) ?>" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
          </a>
          <?php endif; ?>

          <?php if (!empty($socialLinks['instagram']['url'])): ?>
          <a href="<?= e($socialLinks['instagram']['url']) ?>" class="social-icon-btn"
             aria-label="<?= e($socialLinks['instagram']['label']) ?>" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
          </a>
          <?php endif; ?>

          <?php if (!empty($socialLinks['twitter']['url'])): ?>
          <a href="<?= e($socialLinks['twitter']['url']) ?>" class="social-icon-btn"
             aria-label="<?= e($socialLinks['twitter']['label']) ?>" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
          <?php endif; ?>

          <?php if (!empty($socialLinks['tiktok']['url'])): ?>
          <a href="<?= e($socialLinks['tiktok']['url']) ?>" class="social-icon-btn"
             aria-label="<?= e($socialLinks['tiktok']['label']) ?>" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.97a8.21 8.21 0 0 0 4.79 1.52V7.05a4.85 4.85 0 0 1-1.02-.36z"/></svg>
          </a>
          <?php endif; ?>

        </div>

      </div>
    </div>
  </div>
</footer>

<!-- Public Site Toast Notifications Container -->
<div id="toastContainer" class="toast-container" aria-live="polite"></div>

<script src="<?= base_url('frontend/assets/js/main.js?v=' . filemtime(__DIR__ . '/../frontend/assets/js/main.js')) ?>"></script>

<?php if (isset($extraJs)): ?>
  <?= $extraJs ?>
<?php endif; ?>

</body>
</html>
