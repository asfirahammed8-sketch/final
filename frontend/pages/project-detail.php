<?php
/**
 * Project Detail Page
 * Creative IT Institute - DreamIT Platform
 * Shows single project with carousel and full details
 */

$pageCss = 'project-detail.css';

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Database Connection
$db = Database::getConnection();

// SECURITY: Get and validate slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . base_url('projects/'));
    exit;
}

try {
    // SECURE: Fetch project with SEO data using prepared statement
    $stmt = $db->prepare("
        SELECT
            p.*,
            ps.meta_title,
            ps.meta_description,
            ps.meta_keywords,
            ps.og_image,
            ps.og_title,
            ps.og_description,
            ps.robots_index,
            ps.robots_follow
        FROM projects p
        LEFT JOIN project_seo ps ON p.id = ps.project_id
        WHERE p.slug = :slug AND p.status = :status
        LIMIT 1
    ");

    $stmt->execute([
        'slug' => $slug,
        'status' => 'published'
    ]);

    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    // If project not found, redirect to 404
    if (!$project) {
        header('Location: ' . base_url('404.php'));
        exit;
    }

    // SECURE: Fetch carousel images for this project
    $stmtImages = $db->prepare("
        SELECT
            id,
            image_url,
            image_order,
            caption
        FROM project_images
        WHERE project_id = :project_id
        ORDER BY image_order ASC
    ");

    $stmtImages->execute(['project_id' => $project['id']]);
    $carouselImages = $stmtImages->fetchAll(PDO::FETCH_ASSOC);

    // If no carousel images, use cover image as fallback
    if (empty($carouselImages)) {
        $carouselImages = [[
            'image_url' => $project['cover_image'],
            'caption' => $project['title']
        ]];
    }

    // SECURE: Increment view count (prepared statement)
    $stmtView = $db->prepare("
        UPDATE projects
        SET view_count = view_count + 1
        WHERE id = :id
    ");
    $stmtView->execute(['id' => $project['id']]);

    // SECURE: Fetch relevant FAQs (by course_id, category, or fallback)
    $projectFaqs = [];
    $pCourseId = !empty($project['course_id']) ? (int)$project['course_id'] : 0;
    $pCategory = !empty($project['category']) ? trim($project['category']) : '';

    if ($pCourseId > 0) {
        $stmtFaq = $db->prepare("
            SELECT id, question, answer, category
            FROM faqs
            WHERE is_active = 1
              AND (course_id = :cid OR category = :cat OR category = 'general' OR category = 'General')
            ORDER BY (course_id = :cid2) DESC, sort_order ASC, id ASC
            LIMIT 6
        ");
        $stmtFaq->execute([
            ':cid'  => $pCourseId,
            ':cat'  => $pCategory,
            ':cid2' => $pCourseId,
        ]);
        $projectFaqs = $stmtFaq->fetchAll(PDO::FETCH_ASSOC);
    } elseif (!empty($pCategory)) {
        $stmtFaq = $db->prepare("
            SELECT id, question, answer, category
            FROM faqs
            WHERE is_active = 1
              AND (category = :cat OR category = 'general' OR category = 'General')
            ORDER BY (category = :cat2) DESC, sort_order ASC, id ASC
            LIMIT 6
        ");
        $stmtFaq->execute([
            ':cat'  => $pCategory,
            ':cat2' => $pCategory,
        ]);
        $projectFaqs = $stmtFaq->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($projectFaqs)) {
        $fbStmt = $db->query("
            SELECT id, question, answer, category
            FROM faqs
            WHERE is_active = 1
            ORDER BY sort_order ASC, id ASC
            LIMIT 6
        ");
        $projectFaqs = $fbStmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Project detail fetch error: " . $e->getMessage());
    header('Location: ' . base_url('404.php'));
    exit;
}

// SEO: Auto-fill from project data if SEO fields are empty
$seoTitle = $project['meta_title'] ?? $project['title'];
$seoDescription = $project['meta_description'] ?? substr($project['brief_description'] ?? '', 0, 160);
$seoImage = $project['og_image'] ?? $project['cover_image'];
$ogTitle = $project['og_title'] ?? $project['title'];
$ogDescription = $project['og_description'] ?? $project['brief_description'];
$robotsIndex = $project['robots_index'] ?? 'index';
$robotsFollow = $project['robots_follow'] ?? 'follow';

// Page Meta (for header.php to use directly without get_page_seo modification)
$siteBrand = site_brand();
$pageTitle = $seoTitle . " | {$siteBrand}";  // This will be used as-is by header
$metaDesc = $seoDescription;
$activeNav = 'projects';
$ogType = 'article';  // Set proper type for project detail
$canonicalUrl = canonical_url('/project-detail.php?slug=' . urlencode($project['slug']));
$pageSlug = 'project-detail-skip-seo';  // Skip get_page_seo() processing in header

// Security: Escape all output variables
$title = e($project['title']);
$category = e($project['category']);
$studentName = e($project['student_name']);
$description = e($project['brief_description'] ?? 'No description available.');

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Additional SEO Meta Tags -->
<meta name="description" content="<?= e($seoDescription) ?>">
<meta name="keywords" content="<?= e($project['meta_keywords'] ?? 'student project, creative work, portfolio') ?>">
<meta name="robots" content="<?= e($robotsIndex) ?>, <?= e($robotsFollow) ?>">
<link rel="canonical" href="<?= $canonicalUrl ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="article">
<meta property="og:url" content="<?= $canonicalUrl ?>">
<meta property="og:title" content="<?= e($ogTitle) ?>">
<meta property="og:description" content="<?= e($ogDescription) ?>">
<meta property="og:image" content="<?= e($seoImage) ?>">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?= $canonicalUrl ?>">
<meta name="twitter:title" content="<?= e($ogTitle) ?>">
<meta name="twitter:description" content="<?= e($ogDescription) ?>">
<meta name="twitter:image" content="<?= e($seoImage) ?>">

<main id="mainContent" role="main">

  <div class="container">

    <!-- Top Action Bar -->
    <div class="top-bar">
      <a href="<?= base_url('projects/') ?>" class="back-btn">&larr; Back to Student Works</a>
      <div class="student-creator-tag">
        Student: <strong><?= $studentName ?></strong>
      </div>
    </div>

    <!-- Big Product-Style Carousel -->
    <section class="product-carousel">

      <!-- Big Hero Screen -->
      <div class="carousel-main-viewport">
        <div class="carousel-track" id="carouselTrack">

          <?php foreach ($carouselImages as $index => $image):
            $imageUrl = e($image['image_url']);
            $imageCaption = e($image['caption'] ?? $title);
          ?>
            <!-- Slide <?= $index ?> -->
            <div class="carousel-slide">
              <img
                src="<?= $imageUrl ?>"
                alt="<?= $imageCaption ?>"
                loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                onerror="this.src='https://images.unsplash.com/photo-1626785774573-4b799315345d?w=1600&auto=format&fit=crop&q=85'"
              >
            </div>
          <?php endforeach; ?>

        </div>

        <!-- Navigation Arrows (only show if more than 1 image) -->
        <?php if (count($carouselImages) > 1): ?>
          <button class="nav-arrow prev" id="arrowPrev" aria-label="Previous Image">&#10094;</button>
          <button class="nav-arrow next" id="arrowNext" aria-label="Next Image">&#10095;</button>
        <?php endif; ?>
      </div>

      <!-- Clickable Sub-Images (Thumbnails) -->
      <?php if (count($carouselImages) > 1): ?>
        <div class="sub-images-strip" id="thumbnailsStrip">

          <?php foreach ($carouselImages as $index => $image):
            $thumbUrl = e($image['image_url']);
            $thumbCaption = e($image['caption'] ?? "Image " . ($index + 1));
          ?>
            <button
              class="sub-image-item <?= $index === 0 ? 'active' : '' ?>"
              data-index="<?= $index ?>"
              aria-label="View Image <?= $index + 1 ?>"
            >
              <img
                src="<?= $thumbUrl ?>"
                alt="<?= $thumbCaption ?>"
                loading="lazy"
              >
            </button>
          <?php endforeach; ?>

        </div>
      <?php endif; ?>

    </section>

    <!-- Brief Description Block -->
    <div class="project-info-box">

      <div class="info-header">
        <h1 class="project-heading"><?= $title ?></h1>
        <span class="category-badge"><?= $category ?></span>
      </div>

      <!-- Single Brief Description -->
      <p class="brief-description">
        <?= nl2br($description) ?>
      </p>

      <!-- Creator & Metadata Row -->
      <div class="meta-footer-row">
        <div class="student-badge-wrap">
          <div class="avatar-circle">
            <?= strtoupper(substr($studentName, 0, 2)) ?>
          </div>
          <div>
            <div class="student-title-sub"><?= $studentName ?></div>
            <div class="student-course-sub"><?= $category ?></div>
          </div>
        </div>
        <div class="tools-used">
          <strong>Views:</strong> <?= number_format($project['view_count']) ?>
        </div>
      </div>

    </div><!-- /.project-info-box -->

  </div><!-- /.container -->

  <!-- Related Student Work Section (excluding current project) -->
  <?php
    $studentWorkLimit        = 6;
    $studentWorkHeading      = 'More Student Work';
    $studentWorkSubtitle     = 'Discover other amazing projects created by our talented students.';
    $studentWorkExcludeSlug  = $project['slug']; // Exclude current project
    require __DIR__ . '/../../includes/section-student-work.php';
  ?>

  <!-- Project FAQs (Dual-Column Responsive UI & UX Matching FAQ Page) -->
  <?php if (!empty($projectFaqs)):
    $pLeftFaqs  = [];
    $pRightFaqs = [];
    foreach ($projectFaqs as $i => $f) {
        if ($i % 2 === 0) {
            $pLeftFaqs[] = $f;
        } else {
            $pRightFaqs[] = $f;
        }
    }
  ?>
  <section class="home-faq-section" id="project-faqs" aria-label="Frequently Asked Questions">
    <div class="c-container" style="max-width: 1240px; margin: auto; padding: 0 20px;">
      <header class="home-faq-header">
        <h2 class="home-faq-title">Frequently Asked Questions</h2>
        <p class="home-faq-subtitle">Quick answers to common questions about our course projects, professional workflows, admissions, and career placement.</p>
      </header>

      <section class="faq-grid-container" aria-label="FAQ List">
        <!-- LEFT COLUMN -->
        <div class="faq-column">
          <?php foreach ($pLeftFaqs as $idx => $faq):
            $isOpen = ($idx === 0);
          ?>
            <article class="faq-item <?= $isOpen ? 'active is-open' : '' ?>">
              <button class="faq-question-btn" aria-expanded="<?= $isOpen ? 'true' : 'false' ?>">
                <span class="faq-question-text"><?= e($faq['question']) ?></span>
                <span class="faq-icon-indicator" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </span>
              </button>
              <div class="faq-answer-collapse">
                <div class="faq-answer-content">
                  <p><?= nl2br(e($faq['answer'])) ?></p>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="faq-column">
          <?php foreach ($pRightFaqs as $faq): ?>
            <article class="faq-item">
              <button class="faq-question-btn" aria-expanded="false">
                <span class="faq-question-text"><?= e($faq['question']) ?></span>
                <span class="faq-icon-indicator" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </span>
              </button>
              <div class="faq-answer-collapse">
                <div class="faq-answer-content">
                  <p><?= nl2br(e($faq['answer'])) ?></p>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <div class="home-faq-cta">
        <a href="<?= base_url('faq/') ?>" class="btn-see-more">See More Questions</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

</main>

<?php
// Pass carousel images count to JS
$extraJs = "
<script>
  window.carouselImageCount = " . count($carouselImages) . ";
</script>
<script src=\"" . base_url('frontend/assets/js/project-detail.js?v=' . time()) . "\"></script>
";

require_once __DIR__ . '/../../includes/footer.php';
?>
