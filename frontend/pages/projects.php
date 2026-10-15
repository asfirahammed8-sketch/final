<?php
/**
 * Student Projects - Listing Page
 * Creative IT Institute - DreamIT Platform
 * Shows all published student projects in large card grid
 */

$pageCss = 'projects.css';

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Page Meta
$siteBrand = site_brand();
$pageTitle = "Student Works - Featured Creative Projects | {$siteBrand}";
$metaDesc = "Explore amazing student projects from Creative IT Institute. View portfolios, design work, and creative projects from our talented graduates.";
$activeNav = 'gallery';
$canonicalUrl = canonical_url('/projects/');

// Database Connection
$db = Database::getConnection();

try {
    // SECURE: Fetch all published projects using prepared statement
    // Order: Featured first, then by sort order, then newest first
    $stmt = $db->prepare("
        SELECT
            id,
            title,
            slug,
            category,
            student_name,
            cover_image,
            brief_description,
            view_count,
            featured,
            created_at
        FROM projects
        WHERE status = :status
        ORDER BY featured DESC, sort_order ASC, created_at DESC
    ");

    $stmt->execute(['status' => 'published']);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Projects fetch error: " . $e->getMessage());
    $projects = [];
}

require_once __DIR__ . '/../../includes/header.php';
?>

<main id="mainContent" role="main">

  <div class="container">

    <!-- Page Intro -->
    <div class="gallery-intro">
      <span class="intro-badge">Student Portfolio</span>
      <h1 class="intro-title">Featured Creative Works</h1>
      <p class="intro-sub">Hover over any large artwork card to reveal the designer.</p>
    </div>

    <!-- Big Cards Gallery -->
    <div class="big-gallery-grid">

      <?php if (empty($projects)): ?>
        <!-- No Projects Found -->
        <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
          <h2 style="color: var(--text-heading); margin-bottom: 12px;">No Projects Yet</h2>
          <p style="color: var(--text-muted);">Student projects will appear here soon.</p>
        </div>
      <?php else: ?>

        <?php foreach ($projects as $project):
          // Generate clean detail page URL
          $detailUrl = base_url('project-detail.php?slug=' . urlencode($project['slug']));

          // Security: Escape all output
          $title = e($project['title']);
          $category = e($project['category']);
          $studentName = e($project['student_name']);
          $coverImage = e($project['cover_image']);
          $description = e($project['brief_description'] ?? '');
        ?>

          <!-- Project Card -->
          <a href="<?= $detailUrl ?>" class="large-work-card" aria-label="View project: <?= $title ?>">
            <img
              src="<?= $coverImage ?>"
              alt="<?= $title ?> by <?= $studentName ?>"
              loading="lazy"
              onerror="this.src='https://images.unsplash.com/photo-1626785774573-4b799315345d?w=1400&auto=format&fit=crop&q=85'"
            >
            <div class="card-hover-overlay">
              <span class="overlay-category"><?= $category ?></span>
              <h2 class="overlay-student-name"><?= $studentName ?></h2>
              <p class="overlay-project-title"><?= $title ?> &rarr;</p>
            </div>
          </a>

        <?php endforeach; ?>

      <?php endif; ?>

    </div><!-- /.big-gallery-grid -->

  </div><!-- /.container -->

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
