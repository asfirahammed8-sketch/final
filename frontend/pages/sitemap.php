<?php
$pageCss = 'sitemap.css';

/**
 * Dynamic Sitemap Page
 * Creative IT Institute - DreamIT Platform
 * Fully dynamic — no hardcoded labels, URLs, or department lists
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$db        = Database::getConnection();
$siteBrand = site_brand();

$pageTitle    = "Sitemap | {$siteBrand}";
$metaDesc     = "Complete website sitemap of {$siteBrand} — all pages, course departments, and individual training programs.";
$activeNav    = '';
$canonicalUrl = canonical_url('sitemap/');

// ── Courses grouped by department ────────────────────────────────────────────
$courses = $db->query(
    "SELECT title, slug, department FROM courses WHERE status = 'active' ORDER BY department ASC, title ASC"
)->fetchAll();

$groupedCourses = [];
foreach ($courses as $c) {
    $groupedCourses[$c['department']][] = $c;
}

// ── Department labels: try DB first, fallback to defaults ────────────────────
$deptNames = [];
try {
    $deptRows = $db->query("SELECT slug, name FROM departments WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll();
    foreach ($deptRows as $d) {
        $deptNames[$d['slug']] = $d['name'];
    }
} catch (Exception $e) { /* departments table may not exist */ }

// Fallback defaults for any dept not in the DB table
$deptDefaults = [
    'graphic'   => 'Graphic & Multimedia Courses',
    'web'       => 'Web & Software Development',
    'digital'   => 'Digital Marketing & Analytics',
    'animation' => '3D Animation & VFX',
    'diploma'   => '1-Year Diploma Programs',
    'cyber'     => 'Cyber Security & Networking',
];
foreach ($deptDefaults as $key => $label) {
    if (!isset($deptNames[$key])) {
        $deptNames[$key] = $label;
    }
}

// ── Department URL labels (human-readable, for the Student Resources card) ──
$deptUrlLabels = [];
foreach ($deptNames as $key => $label) {
    if (isset($groupedCourses[$key])) { // only show depts that have courses
        $deptUrlLabels[$key] = $label;
    }
}

// ── Published student projects ────────────────────────────────────────────────
$publishedProjects = [];
try {
    $pStmt = $db->query(
        "SELECT title, slug FROM projects WHERE status = 'published'
         ORDER BY featured DESC, sort_order ASC, created_at DESC LIMIT 12"
    );
    $publishedProjects = $pStmt->fetchAll();
} catch (Exception $e) { /* projects table may not exist yet */ }

require_once __DIR__ . '/../../includes/header.php';
?>

<main id="mainContent" role="main">
  <div class="container">

    <div class="sitemap-header-wrap">
      <h1 class="sitemap-main-title">Sitemap</h1>
      <p class="sitemap-subtitle">A complete index of all pages on the <?= e($siteBrand) ?> website.</p>
    </div>

    <!-- ── Top 3-card row ─────────────────────────────────────────────────── -->
    <div class="home-card-row">

      <!-- Card 1: Main Pages (static core pages — always exist) -->
      <article class="sitemap-section-block">
        <h2 class="sitemap-dept-title">Main Pages</h2>
        <ul class="sitemap-links-list">
          <li><a href="<?= base_url('/') ?>"                  class="sitemap-link">Home</a></li>
          <li><a href="<?= base_url('courses/') ?>"           class="sitemap-link">All Courses</a></li>
          <li><a href="<?= base_url('about/') ?>"             class="sitemap-link">About <?= e($siteBrand) ?></a></li>
          <li><a href="<?= base_url('success-stories/') ?>"   class="sitemap-link">Success Stories</a></li>
          <li><a href="<?= base_url('gallery/') ?>"           class="sitemap-link">Gallery</a></li>
          <li><a href="<?= base_url('projects/') ?>"       class="sitemap-link">Student Work</a></li>
          <li><a href="<?= base_url('faq/') ?>"               class="sitemap-link">FAQ</a></li>
          <li><a href="<?= base_url('contact/') ?>"           class="sitemap-link">Contact</a></li>
          <li><a href="<?= base_url('privacy/') ?>"           class="sitemap-link">Privacy Policy</a></li>
          <li><a href="<?= base_url('sitemap/') ?>"           class="sitemap-link">Sitemap</a></li>
        </ul>
      </article>

      <!-- Card 2: Course Departments — dynamic, only shows depts with active courses -->
      <article class="sitemap-section-block">
        <h2 class="sitemap-dept-title">Course Departments</h2>
        <ul class="sitemap-links-list">
          <?php foreach ($deptUrlLabels as $deptKey => $deptLabel): ?>
            <li>
              <a href="<?= clean_dept_url($deptKey) ?>" class="sitemap-link">
                <?= e($deptLabel) ?>
              </a>
            </li>
          <?php endforeach; ?>
          <?php if (empty($deptUrlLabels)): ?>
            <li><a href="<?= base_url('courses/') ?>" class="sitemap-link">Browse All Courses</a></li>
          <?php endif; ?>
        </ul>
      </article>

      <!-- Card 3: Student Work — dynamic from projects table -->
      <article class="sitemap-section-block">
        <h2 class="sitemap-dept-title">Student Work</h2>
        <ul class="sitemap-links-list">
          <li><a href="<?= base_url('projects/') ?>" class="sitemap-link">All Student Projects</a></li>
          <?php foreach ($publishedProjects as $proj): ?>
            <li>
              <a href="<?= base_url('project-detail.php?slug=' . urlencode($proj['slug'])) ?>" class="sitemap-link">
                <?= e($proj['title']) ?>
              </a>
            </li>
          <?php endforeach; ?>
          <?php if (empty($publishedProjects)): ?>
            <li style="color:#888;font-size:13px;">No published projects yet.</li>
          <?php endif; ?>
        </ul>
      </article>

    </div><!-- /.home-card-row -->

    <!-- ── All Courses by Department — fully dynamic ──────────────────────── -->
    <?php if (!empty($groupedCourses)): ?>
      <h2 class="sitemap-section-heading">All Available Courses</h2>
      <div class="sitemap-columns-grid">
        <?php foreach ($deptNames as $deptKey => $deptLabel):
          if (empty($groupedCourses[$deptKey])) continue;
        ?>
          <article class="sitemap-section-block">
            <h3 class="sitemap-dept-title"><?= e($deptLabel) ?></h3>
            <ul class="sitemap-links-list">
              <?php foreach ($groupedCourses[$deptKey] as $course): ?>
                <li>
                  <a href="<?= clean_course_url($course['slug']) ?>" class="sitemap-link">
                    <?= e($course['title']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div><!-- /.container -->
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
