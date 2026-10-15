<?php
$pageCss = 'home.css';

/**
 * Landing / Home Page
 * Creative IT Institute - DreamIT Platform
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$siteBrand = site_brand();
$tagline   = site_tagline();
$pageTitle = "{$siteBrand} | {$tagline}";
$metaDesc  = "Learn Graphic Design, Web Development, Digital Marketing, 3D Animation and more at {$siteBrand}. Explore professional IT courses with practical training and expert mentors.";
$activeNav = 'home';
$canonicalUrl = canonical_url('/');

// Fetch dynamic data from database
$db = Database::getConnection();

// Popular active courses - smart featured logic
// If any course is marked as featured, show ONLY featured courses
// If no courses are featured, show all active courses (default behavior)
$featuredCheckStmt = $db->query("
    SELECT COUNT(*) FROM courses
    WHERE status = 'active' AND is_featured = 1
");
$hasFeaturedCourses = (int)$featuredCheckStmt->fetchColumn() > 0;

if ($hasFeaturedCourses) {
    // Show ONLY featured active courses
    $coursesStmt = $db->query("
        SELECT * FROM courses
        WHERE status = 'active' AND is_featured = 1
        ORDER BY (priority = 'top') DESC, sort_order ASC, id ASC
        LIMIT 12
    ");
} else {
    // Show ALL active courses (default when nothing is featured)
    $coursesStmt = $db->query("
        SELECT * FROM courses
        WHERE status = 'active'
        ORDER BY (priority = 'top') DESC, sort_order ASC, id ASC
        LIMIT 12
    ");
}
$popularCourses = $coursesStmt->fetchAll();

// Featured success stories
$storiesStmt = $db->query("
    SELECT * FROM success_stories
    WHERE is_active = 1
    ORDER BY is_featured DESC, id DESC
    LIMIT 4
");
$featuredStories = $storiesStmt->fetchAll();

// Home FAQs — first 6 by sort_order
$homeFaqsStmt = $db->query("SELECT question, answer FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 6");
$homeFaqs = $homeFaqsStmt->fetchAll();

// Dynamic department colors
$deptColors = [
    'graphic'   => '#7c3aed',
    'web'       => '#2563eb',
    'digital'   => '#0891b2',
    'animation' => '#d97706',
    'diploma'   => '#16a34a',
    'cyber'     => '#dc2626',
];

$deptLabels = [
    'graphic'   => 'Graphic & Multimedia',
    'web'       => 'Web & Software',
    'digital'   => 'Digital Marketing',
    'animation' => '3D Animation',
    'diploma'   => '1 Year Diploma',
    'cyber'     => 'Cyber Security',
];

// General site settings (hero video, etc.)
$generalSettings = get_setting('general', []);

$heroVideoRaw  = $generalSettings['video_url']     ?? 'https://www.youtube.com/watch?v=V1bFr2KGq1g';
$heroCaption   = $generalSettings['video_caption']  ?? 'দক্ষিণ এশিয়ার সেরা আইটি প্রতিষ্ঠান';

// Convert raw YouTube/Vimeo watch URL → nocookie embed URL for iframe
$heroVideoEmbed = safe_video_embed_url($heroVideoRaw) ?? 'https://www.youtube-nocookie.com/embed/V1bFr2KGq1g?autoplay=1&rel=0';
// For the data-video-src attribute (opened in the video modal), use embed URL
$heroVideoModalSrc = $heroVideoEmbed;

require_once __DIR__ . '/../../includes/header.php';
?>

<main id="mainContent" role="main">

  <!-- ============================================================
       1. HERO BANNER
  ============================================================ -->
  <section class="hero-banner" aria-label="Welcome to Creative IT Institute">
    <svg class="hero-contour-svg" viewBox="0 0 1440 600" fill="none" preserveAspectRatio="none" aria-hidden="true">
      <!-- Diagonal flowing lines from top-left to bottom-right -->
      <path d="M-50 50 Q 300 150, 600 100 T 1200 150 L 1440 180 L 1440 0 L -50 0 Z" fill="#E8EDF5" opacity="0.3" />
      <path d="M-50 120 Q 350 220, 650 170 T 1250 220 L 1440 250 L 1440 0 L -50 0 Z" fill="#E8EDF5" opacity="0.2" />

      <!-- Circular dots pattern (scattered) -->
      <circle cx="200" cy="100" r="3" fill="#0B2046" opacity="0.15" />
      <circle cx="450" cy="180" r="2.5" fill="#0B2046" opacity="0.12" />
      <circle cx="750" cy="140" r="3.5" fill="#0B2046" opacity="0.18" />
      <circle cx="1050" cy="220" r="2" fill="#0B2046" opacity="0.1" />
      <circle cx="1300" cy="160" r="3" fill="#0B2046" opacity="0.15" />

      <!-- Subtle wave lines -->
      <path d="M0 350 Q 360 320, 720 350 T 1440 350" stroke="#0B2046" stroke-width="1.5" opacity="0.08" fill="none" />
      <path d="M0 420 Q 380 390, 760 420 T 1440 420" stroke="#0B2046" stroke-width="1.5" opacity="0.06" fill="none" />

      <!-- Abstract geometric shapes (bottom-right accent) -->
      <path d="M1100 450 L1200 480 L1180 520 L1080 490 Z" fill="#E8EDF5" opacity="0.4" />
      <path d="M1250 480 L1340 510 L1320 550 L1230 520 Z" fill="#E8EDF5" opacity="0.3" />
    </svg>

    <div class="c-container">
      <div class="hero-content-grid">
        <!-- Text Column -->
        <div class="hero-text-col">
          <div class="hero-badge" aria-label="Tagline">
            <span class="badge-check" aria-hidden="true">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
              </svg>
            </span>
            <span style="font-size:clamp(15px,2vw,17px);font-weight:700;color:var(--color-text-primary,#1e293b);">Unleash Your Potential</span>
          </div>

          <h1 class="hero-heading">
            Professional IT Courses &amp;<br>
            <span class="text-gradient">Training in Bangladesh</span>
          </h1>

          <p class="hero-description">
            Become an IT Pro and build career-ready skills in Graphic Design, Web Development, Digital Marketing, 3D Animation and more with industry expert mentors at Creative IT Institute.
          </p>

          <div class="hero-cta-group">
            <a href="<?= base_url('courses.php') ?>" class="btn-hero">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
              </svg>
              Browse Courses
            </a>
            <a href="<?= base_url('contact.php') ?>" class="btn-hero" style="background:transparent;color:#1642A8;border:1.5px solid #1642A8;box-shadow:none;">
              Contact Us
            </a>
          </div>
        </div>

        <!-- Media Column -->
        <div class="hero-media-col">
          <div
            class="video-card-container"
            role="button"
            tabindex="0"
            aria-label="Play video: Creative IT Institute showcase"
            data-video-src="<?= e($heroVideoModalSrc) ?>"
          >
            <img
              class="video-poster-img"
              src="https://images.unsplash.com/photo-1577495508048-b635879837f1?auto=format&fit=crop&w=1200&q=80"
              alt="Creative IT Institute award ceremony and students"
              loading="eager"
              width="600" height="380"
            />
            <div class="video-card-overlay">
              <div class="play-button-wrapper" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
              </div>
            </div>
            <div class="card-corner-tag" aria-hidden="true">
              Concern of<br><strong>Creative</strong> Business Group
            </div>
            <div class="card-caption-banner">
              <p class="caption-sub">ক্রিয়েটিভ আইটি ইনস্টিটিউট এখন</p>
              <h2 class="caption-title"><?= e($heroCaption) ?></h2>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================
       2. MILESTONES / STATS SLIDER
  ============================================================ -->
  <section class="milestones-section" id="milestones" aria-label="Key Achievements and Statistics">
    <div class="c-container">
      <div class="milestones-slider-wrapper">
        <button class="slider-nav-btn" id="milestonePrev" aria-label="Previous milestone">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>

        <div class="milestones-viewport" id="milestoneViewport">
          <div class="milestones-track" id="milestoneTrack">
            <article class="milestone-card card-theme-green">
              <span class="milestone-number" data-count="50000" data-suffix="+">50,000+</span>
              <h3 class="milestone-heading">Students Choose Creative IT</h3>
              <p class="milestone-description">More than 50,000 passionate learners are working across global markets after completing courses from our institute.</p>
            </article>
            <article class="milestone-card card-theme-cream">
              <span class="milestone-number" data-count="42000" data-suffix="+">42,000+</span>
              <h3 class="milestone-heading">Got Career Placement</h3>
              <p class="milestone-description">Our job placement department has helped more than 42,000 skilled students find jobs in competitive global industries.</p>
            </article>
            <article class="milestone-card card-theme-cyan">
              <span class="milestone-number">89%</span>
              <h3 class="milestone-heading">Success Ratio</h3>
              <p class="milestone-description">The practical approach towards industry problem-solving puts our graduates ahead of any other competitors.</p>
            </article>
            <article class="milestone-card card-theme-green">
              <span class="milestone-number" data-count="3000" data-suffix="+">3,000+</span>
              <h3 class="milestone-heading">Corporate Industry Partners</h3>
              <p class="milestone-description">Active recruitment ties with top software houses, multinational corporations, and tech startups.</p>
            </article>
          </div>
        </div>

        <button class="slider-nav-btn" id="milestoneNext" aria-label="Next milestone">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
  </section>

  <!-- ============================================================
       3. POPULAR COURSES SLIDER (Dynamic from MySQL)
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
            </div><!-- /.courses-track -->
          </div><!-- /.courses-viewport -->

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

  <!-- ============================================================
       4. SUCCESS STORIES (Dynamic from MySQL)
  ============================================================ -->
  <section class="success-stories-section" id="success-stories" aria-label="Student Success Stories">
    <div class="c-container">
      <header class="stories-header">
        <h2 class="stories-main-title">Success Stories</h2>
        <p class="stories-subtitle">The presence of our students in the ever expanding IT Industry motivates us and drives us to guide more people towards a sustainable future.</p>
      </header>

      <div class="stories-grid">
        <?php foreach ($featuredStories as $story):
          $videoSrc = str_contains($story['video_url'], 'watch?v=')
            ? str_replace('watch?v=', 'embed/', $story['video_url']) . '?autoplay=1'
            : $story['video_url'];
          $coverImg = !empty($story['cover_image']) ? $story['cover_image'] : 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=800&q=80';
        ?>
          <div class="story-video-card" role="button" tabindex="0" aria-label="Play success story: <?= e($story['student_name']) ?>" data-video-src="<?= e($videoSrc) ?>">
            <img class="story-bg-img" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;" src="<?= e($coverImg) ?>" alt="Success Story — <?= e($story['student_name']) ?>" loading="lazy" />
            <div class="story-video-overlay"></div>
            <div class="story-play-btn" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
            <div class="card-floating-text">
              <h4><?= e($story['headline']) ?></h4>
              <p><?= e($story['student_name']) ?> <?= !empty($story['subtitle']) ? '— ' . e($story['subtitle']) : '' ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="stories-cta-wrap">
        <a href="<?= base_url('success-stories.php') ?>" class="btn-see-more">See More Stories</a>
      </div>
    </div>
  </section>

  <!-- ============================================================
       5. STUDENT WORK
  ============================================================ -->
  <?php
    $studentWorkLimit    = 6;
    $studentWorkHeading  = 'Our Student Work';
    $studentWorkSubtitle = 'Explore creative projects built by our talented students — real work, real skills, real results.';
    require __DIR__ . '/../../includes/section-student-work.php';
  ?>

  <!-- ============================================================
       6. FEATURE SHOWCASE
  ============================================================ -->
  <section class="feature-showcase-section" aria-label="Institute Features">
    <div class="c-container">
      <div class="showcase-card">
        <div class="showcase-text-col">
          <h2 class="showcase-title">Take our free counseling</h2>
          <p class="showcase-desc">Need guidelines to choose a suitable course? Join our free seminars to consult with our experts — they will guide you to pick the course that matches your interest and discuss career prospects.</p>
          <a href="<?= base_url('contact.php') ?>" class="btn-hero" style="width:fit-content;">Contact Us</a>
        </div>
        <div class="showcase-media-col">
          <div class="showcase-img-frame">
            <img class="showcase-img" src="https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=1000&q=80" alt="Mentor guiding IT students in computer training lab" loading="lazy" width="500" height="340" />
          </div>
        </div>
      </div>

      <div class="showcase-card reverse-layout">
        <div class="showcase-media-col">
          <div class="showcase-img-frame">
            <img class="showcase-img" src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=1000&q=80" alt="Instructor taking interactive class in modern lecture hall" loading="lazy" width="500" height="340" />
          </div>
        </div>
        <div class="showcase-text-col">
          <h2 class="showcase-title">Providing project-based classes is our specialty</h2>
          <p class="showcase-desc">We believe in skills gained through practical projects. Hands-on learning ensures you are completely equipped for national and international tech careers.</p>
        </div>
      </div>
    </div>
  </section>

  <?php if (!empty($homeFaqs)): ?>
  <!-- ============================================================
       7. FAQ SECTION (Homepage preview)
  ============================================================ -->
  <section class="home-faq-section" aria-label="Frequently Asked Questions">
    <div class="c-container">
      <header class="home-faq-header">
        <h2 class="home-faq-title">Frequently Asked Questions</h2>
        <p class="home-faq-subtitle">Quick answers to the most common questions about our courses, admissions, and career support.</p>
      </header>

      <?php
        $hLeftFaqs  = [];
        $hRightFaqs = [];
        foreach ($homeFaqs as $i => $f) {
            if ($i % 2 === 0) {
                $hLeftFaqs[] = $f;
            } else {
                $hRightFaqs[] = $f;
            }
        }
      ?>
      <section class="faq-grid-container" aria-label="FAQ List">
        <!-- LEFT COLUMN -->
        <div class="faq-column">
          <?php foreach ($hLeftFaqs as $idx => $hFaq):
            $isOpen = ($idx === 0);
          ?>
            <article class="faq-item <?= $isOpen ? 'active is-open' : '' ?>">
              <button class="faq-question-btn" aria-expanded="<?= $isOpen ? 'true' : 'false' ?>">
                <span class="faq-question-text"><?= e($hFaq['question']) ?></span>
                <span class="faq-icon-indicator" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </span>
              </button>
              <div class="faq-answer-collapse">
                <div class="faq-answer-content">
                  <p><?= nl2br(e($hFaq['answer'])) ?></p>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="faq-column">
          <?php foreach ($hRightFaqs as $hFaq): ?>
            <article class="faq-item">
              <button class="faq-question-btn" aria-expanded="false">
                <span class="faq-question-text"><?= e($hFaq['question']) ?></span>
                <span class="faq-icon-indicator" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </span>
              </button>
              <div class="faq-answer-collapse">
                <div class="faq-answer-content">
                  <p><?= nl2br(e($hFaq['answer'])) ?></p>
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
      <iframe id="videoModalIframe" src="" title="<?= e($siteBrand) ?> Video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
  </div>
</div>

<div class="toast-container" id="toastContainer" aria-live="polite"></div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
