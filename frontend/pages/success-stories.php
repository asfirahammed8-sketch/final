<?php
$pageCss = 'stories.css';

/**
 * Success Stories Page
 * Creative IT Institute - DreamIT Platform
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$siteBrand = site_brand();
$pageTitle = "Graduate Success Stories & Reviews | {$siteBrand}";
$metaDesc  = "Discover inspiring success stories from {$siteBrand} graduates who built thriving tech careers and freelance businesses with industry-standard training.";
$activeNav = 'stories';
$canonicalUrl = canonical_url('success-stories/');

$db = Database::getConnection();
$stmt = $db->query("SELECT * FROM success_stories WHERE is_active = 1 ORDER BY is_featured DESC, id DESC");
$stories = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';

// Fetch popular courses (same logic as homepage)
$featuredCheckStmt = $db->query("
    SELECT COUNT(*) FROM courses
    WHERE status = 'active' AND is_featured = 1
");
$hasFeaturedCourses = (int)$featuredCheckStmt->fetchColumn() > 0;

if ($hasFeaturedCourses) {
    $coursesStmt = $db->query("
        SELECT * FROM courses
        WHERE status = 'active' AND is_featured = 1
        ORDER BY (priority = 'top') DESC, sort_order ASC, id ASC
        LIMIT 12
    ");
} else {
    $coursesStmt = $db->query("
        SELECT * FROM courses
        WHERE status = 'active'
        ORDER BY (priority = 'top') DESC, sort_order ASC, id ASC
        LIMIT 12
    ");
}
$popularCourses = $coursesStmt->fetchAll();

// Department labels for courses
$deptLabels = [
    'graphic'   => 'Graphic & Multimedia',
    'web'       => 'Web & Software',
    'digital'   => 'Digital Marketing',
    'animation' => '3D Animation',
    'diploma'   => '1 Year Diploma',
    'cyber'     => 'Cyber Security',
];

// Fetch FAQs — first 6 by sort_order
$faqsStmt = $db->query("SELECT question, answer FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 6");
$storiesFaqs = $faqsStmt->fetchAll();
?>

<!-- Add home.css for course slider and FAQ styles -->
<link rel="stylesheet" href="<?= base_url('frontend/assets/css/pages/home.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('frontend/assets/css/components.css?v=' . time()) ?>">

<main class="container" style="max-width: 1240px; margin: auto; padding: 0 20px;">
  <section class="section-success" aria-labelledby="success-heading">
    <h1 id="success-heading" class="stories-title">Graduate Success Stories</h1>
    <p class="stories-desc">
      The bright presence of our students in the marketplace inspires us and motivates us to engage more people with our vision to make a better future. During the last 17 years, hundreds of thousands of people learned and grew their expertise with us. By bringing economic stability to their own life, many students created job opportunities for others.
    </p>

    <div class="video-grid" id="videoStoriesGrid">
      <?php foreach ($stories as $idx => $story):
        $isHidden = ($idx >= 6);
        $rawVideo = !empty($story['video_url']) && !str_contains($story['video_url'], 'dQw4w9WgXcQ')
            ? $story['video_url']
            : 'https://www.youtube.com/watch?v=V1bFr2KGq1g';
        $videoSrc = str_contains($rawVideo, 'watch?v=')
          ? str_replace('watch?v=', 'embed/', $rawVideo) . '?autoplay=1'
          : $rawVideo;
        $coverImg = !empty($story['cover_image']) ? $story['cover_image'] : 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=800&q=80';
      ?>
        <article
          class="video-card <?= $isHidden ? 'story-card-hidden' : '' ?>"
          role="button"
          tabindex="0"
          aria-label="Play success story — <?= e($story['student_name']) ?>"
          data-video-src="<?= e($videoSrc) ?>"
        >
          <img src="<?= e($coverImg) ?>" alt="<?= e($story['student_name']) ?> — <?= e($story['headline'] ?: 'Student Success Story') ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;" loading="lazy" />
          <div class="video-overlay" aria-hidden="true"></div>
          <div class="play-button" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
          <div class="card-floating-text">
            <h4><?= e($story['headline']) ?></h4>
            <p><?= e($story['student_name']) ?> <?= !empty($story['subtitle']) ? '— ' . e($story['subtitle']) : '' ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if (count($stories) > 6): ?>
      <div class="stories-btn-wrap">
        <button class="citi-btn-primary" id="seeMoreStoriesBtn" aria-expanded="false" aria-controls="videoStoriesGrid">
          <span id="seeMoreBtnText">See More</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
      </div>
    <?php endif; ?>
  </section>

  <!-- ============================================================
       POPULAR COURSES SECTION
  ============================================================ -->
  <section class="courses-section" id="popular-courses" aria-label="Popular Courses">
    <div class="c-container">
      <div class="courses-outer-box">
        <header class="courses-header">
          <h2 class="courses-main-title">Popular Professional Courses</h2>
          <p class="courses-subtitle">
            We have designed our courses with the most demanding professional skills. The knowledge,
            experience, and expertise gained through the program will ensure your desired career in the global market.
          </p>
        </header>

        <div class="courses-slider-wrapper">
          <button class="slider-nav-btn slider-prev" id="coursesPrev" aria-label="Previous courses">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
          </button>

          <div class="courses-viewport" id="coursesViewport">
            <div class="courses-track" id="coursesTrack">
              <?php foreach ($popularCourses as $c):
                $detailUrl = clean_course_url($c['slug']);
                $gradStart = $c['thumbnail_gradient_start'] ?? '#0b132b';
                $gradEnd   = $c['thumbnail_gradient_end'] ?? '#1c2541';
                $deptLabel = $deptLabels[$c['department']] ?? ucfirst($c['department']);
              ?>
                <article class="course-card" onclick="location.href='<?= $detailUrl ?>'" role="link" tabindex="0" aria-label="<?= e($c['title']) ?> — View details">
                  <div class="card-thumb-wrapper" style="background: linear-gradient(135deg, <?= e($gradStart) ?>, <?= e($gradEnd) ?>);">
                    <?php if (!empty($c['thumbnail_url'])): ?>
                      <img class="thumb-bg" src="<?= e($c['thumbnail_url']) ?>" alt="<?= e($c['title']) ?> course at <?= e($siteBrand) ?>" width="400" height="225" loading="lazy" />
                    <?php else: ?>
                      <div style="height:100%;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.7);">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="card-body">
                    <span class="card-category"><?= e($deptLabel) ?></span>
                    <h3 class="card-title"><?= e($c['title']) ?></h3>
                    <div class="card-footer-row">
                      <span class="course-fee">Course Fee <?= format_bdt($c['price']) ?> BDT</span>
                      <a href="<?= $detailUrl ?>" class="btn-view-details" tabindex="-1" aria-hidden="true">View Details</a>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>

          <button class="slider-nav-btn slider-next" id="coursesNext" aria-label="Next courses">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
        <div class="slider-indicators" id="coursesDots" aria-label="Slide indicators"></div>

        <!-- Browse Courses CTA -->
        <div style="text-align: center; margin-top: 32px;">
          <a href="<?= base_url('courses.php') ?>" class="btn-see-more">Browse All Courses</a>
        </div>
      </div>
    </div>
  </section>

  <?php if (!empty($storiesFaqs)): ?>
  <!-- ============================================================
       FAQ SECTION
  ============================================================ -->
  <section class="home-faq-section" aria-label="Frequently Asked Questions">
    <div class="c-container">
      <header class="home-faq-header">
        <h2 class="home-faq-title">Frequently Asked Questions</h2>
        <p class="home-faq-subtitle">Quick answers to the most common questions about our courses, admissions, and career support.</p>
      </header>

      <?php
        $sLeftFaqs  = [];
        $sRightFaqs = [];
        foreach ($storiesFaqs as $i => $f) {
            if ($i % 2 === 0) {
                $sLeftFaqs[] = $f;
            } else {
                $sRightFaqs[] = $f;
            }
        }
      ?>
      <section class="faq-grid-container" aria-label="FAQ List">
        <!-- LEFT COLUMN -->
        <div class="faq-column">
          <?php foreach ($sLeftFaqs as $idx => $faq):
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
          <?php foreach ($sRightFaqs as $faq): ?>
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

<!-- Video Modal -->
<div class="modal-backdrop" id="videoModal" role="dialog" aria-modal="true" aria-label="Video Player">
  <div class="modal-card modal-card-video">
    <button class="video-modal-close" id="videoModalClose" aria-label="Close video">&times;</button>
    <div class="video-responsive-frame">
      <iframe id="videoModalIframe" src="" title="<?= e($siteBrand) ?> Student Success Story" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
