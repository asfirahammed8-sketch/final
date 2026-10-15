<?php
$pageCss = 'courses.css';

/**
 * Course Catalog Page
 * Creative IT Institute - DreamIT Platform
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$rawDept = sanitize_text($_GET['dept'] ?? 'all');
$searchQuery  = sanitize_text($_GET['search'] ?? '');
$activeNav    = 'courses';

// Fetch departments from database (replaces hardcoded array)
$db = Database::getConnection();
$deptStmt = $db->query("SELECT slug, name, icon_svg, color FROM departments WHERE is_active = 1 ORDER BY sort_order ASC");
$departmentsFromDb = $deptStmt->fetchAll();

// Build department metadata array and slug mapping from database
$departmentMeta = [];
$slugToDept = [];
foreach ($departmentsFromDb as $dept) {
    $deptSlug = $dept['slug'];
    // Create URL-friendly slug for department pages
    $urlSlugMap = [
        'graphic' => 'graphic-design',
        'web' => 'web-development',
        'digital' => 'digital-marketing',
        'animation' => '3d-animation',
        'diploma' => 'diploma-courses',
        'cyber' => 'cyber-security',
    ];
    $urlSlug = $urlSlugMap[$deptSlug] ?? $deptSlug;

    $departmentMeta[$deptSlug] = [
        'name' => $dept['name'],
        'slug' => $urlSlug,
        'icon' => $dept['icon_svg'] ?: '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>', // fallback icon
        'color' => $dept['color'] ?? '#7c3aed'
    ];
    $slugToDept[$urlSlug] = $deptSlug;
}

$selectedDept = $slugToDept[$rawDept] ?? $rawDept;

$siteBrand = site_brand();

if ($selectedDept !== 'all' && isset($departmentMeta[$selectedDept])) {
    $deptInfo       = $departmentMeta[$selectedDept];

    // Fetch full department record for SEO
    $deptStmt = $db->prepare("SELECT * FROM departments WHERE slug = :slug LIMIT 1");
    $deptStmt->execute([':slug' => $selectedDept]);
    $department = $deptStmt->fetch();

    if ($department) {
        $pageSlug = 'department';
        $runtimeContext = ['department' => $department];
    }

    $pageTitle      = "{$deptInfo['name']} Courses in Bangladesh | {$siteBrand}";
    $metaDesc       = "Explore professional {$deptInfo['name']} courses at {$siteBrand}. Practical lab-based curriculum, industry projects, expert mentors, and job placement support.";
    $canonicalUrl   = clean_dept_url($selectedDept, true);
    $coursesHeading = "{$deptInfo['name']} Courses";
} else {
    $pageSlug = 'courses';
    $pageTitle      = "All Professional IT Courses | {$siteBrand}";
    $metaDesc       = "Browse 30+ career-focused IT courses in Graphic Design, Web Development, Digital Marketing, 3D Animation, and Cyber Security at {$siteBrand}.";
    $canonicalUrl   = canonical_url('courses/');
    $coursesHeading = 'Professional IT Courses &amp; Programs';
}

// Breadcrumbs Schema
$pageSchema = [
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [
        [
            "@type" => "ListItem",
            "position" => 1,
            "name" => "Home",
            "item" => canonical_url('/')
        ],
        [
            "@type" => "ListItem",
            "position" => 2,
            "name" => "Courses",
            "item" => canonical_url('courses/')
        ]
    ]
];
if ($selectedDept !== 'all' && isset($departmentMeta[$selectedDept])) {
    $pageSchema['itemListElement'][] = [
        "@type" => "ListItem",
        "position" => 3,
        "name" => $departmentMeta[$selectedDept]['name'],
        "item" => $canonicalUrl
    ];
}

$db = Database::getConnection();

// Fetch ALL active courses (for AJAX filtering)
$sql = "SELECT * FROM courses WHERE status = 'active'";
$params = [];

// Search functionality
if (!empty($searchQuery)) {
    $sql .= " AND (title LIKE :q OR overview LIKE :q OR description LIKE :q)";
    $params[':q'] = "%{$searchQuery}%";
}

$sql .= " ORDER BY (priority = 'top') DESC, sort_order ASC, id ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Group courses by department
$groupedCourses = [];
foreach ($courses as $c) {
    $d = $c['department'] ?? 'other';
    $groupedCourses[$d][] = $c;
}

// Get course counts for all departments (for tab display)
$countSql = "SELECT department, COUNT(*) as count FROM courses WHERE status = 'active' GROUP BY department";
$countStmt = $db->query($countSql);
$deptCounts = [];
while ($row = $countStmt->fetch()) {
    $deptCounts[$row['department']] = (int)$row['count'];
}
$totalCourses = array_sum($deptCounts);

require_once __DIR__ . '/../../includes/header.php';

// Get dynamic site settings
$promoBannerUrl = get_site_setting('general.promo_banner_url', 'https://www.creativeitinstitute.com/One%20To%20One%20Banner_Home%20Page_PC.jpg');
$courseCatalogDesc = get_site_setting('general.course_catalog_description', 'We offer high-demand courses designed with industry-standard curriculum and computer lab facilities. Build your portfolio with practical projects and expert mentor guidance throughout your learning journey at Creative IT Institute.');
?>

<main class="container" style="max-width: 1240px; margin: auto; padding: 0 20px;">

  <!-- Promo Banner -->
  <section class="promo-banner-section" aria-label="Special Promotion">
    <a href="<?= base_url('contact.php') ?>" class="promo-banner-link" title="Book your free consultation slot">
      <img src="<?= e($promoBannerUrl) ?>"
        alt="Book your slot — get free consultation from an expert mentor before admission"
        class="promo-banner-img" loading="eager" width="1280" height="180" onerror="this.style.display='none'" />
    </a>
  </section>

  <!-- Intro & Filter -->
  <section class="courses-intro" aria-labelledby="courses-heading">
    <h1 id="courses-heading" class="section-tag-red"><?= $coursesHeading ?></h1>
    <p class="section-description">
      <?= e($courseCatalogDesc) ?>
    </p>

    <!-- Department Filter Tabs -->
    <nav class="category-tabs-container" aria-label="Course Department Filter">
      <div class="category-tabs-grid" role="tablist">
        <button class="category-tab-btn <?= $selectedDept === 'all' ? 'active' : '' ?>"
                data-filter="all"
                role="tab"
                aria-selected="<?= $selectedDept === 'all' ? 'true' : 'false' ?>">
          <span class="category-icon-wrapper">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
          </span>
          <span>All Courses (<?= $totalCourses ?>)</span>
        </button>

        <?php foreach ($departmentMeta as $slug => $info):
          $deptCount = $deptCounts[$slug] ?? 0;
        ?>
          <button class="category-tab-btn <?= $selectedDept === $slug ? 'active' : '' ?>"
                  data-filter="<?= e($slug) ?>"
                  data-dept-name="<?= e($info['name']) ?>"
                  role="tab"
                  aria-selected="<?= $selectedDept === $slug ? 'true' : 'false' ?>">
            <span class="category-icon-wrapper">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <?= $info['icon'] ?>
              </svg>
            </span>
            <span><?= e($info['name']) ?> (<?= $deptCount ?>)</span>
          </button>
        <?php endforeach; ?>
      </div>
    </nav>
  </section>

  <!-- COURSES LISTINGS -->
  <?php if (empty($courses)): ?>
    <div style="text-align:center;padding:60px 20px;background:#fff;border-radius:12px;border:1px solid #e2e8f0;margin-bottom:60px;">
      <h3 style="color:#1e293b;margin-bottom:8px;">No courses found</h3>
      <p style="color:#64748b;">Try adjusting your search or view all courses.</p>
      <a href="<?= base_url('courses.php') ?>" class="btn-hero" style="margin-top:16px;display:inline-block;">View All Courses</a>
    </div>
  <?php else: ?>
    <?php foreach ($departmentMeta as $slug => $info):
      if (empty($groupedCourses[$slug])) continue;
      $displayStyle = ($selectedDept !== 'all' && $selectedDept !== $slug) ? 'style="display:none;"' : '';
    ?>
      <section class="category-group-block" id="group-<?= e($slug) ?>" data-category="<?= e($slug) ?>" <?= $displayStyle ?>>
        <h2 class="category-group-title"><?= e($info['name']) ?></h2>
        <div class="courses-grid">
          <?php foreach ($groupedCourses[$slug] as $c):
            $detailUrl = clean_course_url($c['slug']);
            $gradStart = $c['thumbnail_gradient_start'] ?? '#0b132b';
            $gradEnd   = $c['thumbnail_gradient_end'] ?? '#1c2541';
          ?>
            <article class="course-card"
                     data-title="<?= e($c['title']) ?>"
                     data-dept="<?= e($slug) ?>"
                     onclick="location.href='<?= $detailUrl ?>'"
                     role="link"
                     tabindex="0"
                     aria-label="<?= e($c['title']) ?> — View details">
              <div class="card-thumb-wrapper" style="background: linear-gradient(135deg, <?= e($gradStart) ?>, <?= e($gradEnd) ?>);">
                <?php if (!empty($c['thumbnail_url'])): ?>
                  <img class="thumb-bg"
                       src="<?= e($c['thumbnail_url']) ?>"
                       alt="<?= e($c['title']) ?> course at <?= e($siteBrand) ?>"
                       width="400"
                       height="225"
                       loading="lazy" />
                <?php else: ?>
                  <div style="height:100%;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.7);">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                      <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                      <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                    </svg>
                  </div>
                <?php endif; ?>
              </div>
              <div class="card-body">
                <span class="card-category"><?= e($info['name']) ?></span>
                <h3 class="card-title"><?= e($c['title']) ?></h3>
                <div class="card-footer-row">
                  <span class="course-fee">Course Fee <?= format_bdt($c['price']) ?> BDT</span>
                  <a href="<?= $detailUrl ?>" class="btn-view-details" tabindex="-1" aria-hidden="true">View Details</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>

</main>

<script>
// AJAX-Style Filtering Without Page Reload
document.addEventListener('DOMContentLoaded', () => {
  const tabBtns = document.querySelectorAll('.category-tab-btn[data-filter]');
  const blocks = document.querySelectorAll('.category-group-block[data-category]');
  const heading = document.getElementById('courses-heading');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();

      // Update active state
      tabBtns.forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
      });
      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');

      const filter = btn.dataset.filter;
      const deptName = btn.dataset.deptName;

      // Update page title smoothly
      if (heading) {
        heading.style.opacity = '0';
        heading.style.transform = 'translateY(-10px)';

        setTimeout(() => {
          if (filter === 'all') {
            heading.textContent = 'Professional IT Courses & Programs';
          } else {
            heading.textContent = deptName + ' Courses';
          }
          heading.style.opacity = '1';
          heading.style.transform = 'translateY(0)';
        }, 150);
      }

      // Filter blocks with smooth animation
      blocks.forEach(block => {
        const shouldShow = filter === 'all' || block.dataset.category === filter;

        if (shouldShow) {
          if (block.style.display === 'none') {
            block.style.display = 'block';
            block.style.opacity = '0';
            block.style.transform = 'translateY(20px)';

            // Trigger reflow
            block.offsetHeight;

            block.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            block.style.opacity = '1';
            block.style.transform = 'translateY(0)';
          }
        } else {
          block.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
          block.style.opacity = '0';
          block.style.transform = 'translateY(-10px)';

          setTimeout(() => {
            block.style.display = 'none';
          }, 300);
        }
      });

      // Update URL without reload (for bookmarking & back button)
      const url = new URL(window.location);
      if (filter === 'all') {
        url.searchParams.delete('dept');
      } else {
        // Map slug to URL-friendly name
        const urlSlugMap = {
          'graphic': 'graphic-design',
          'web': 'web-development',
          'digital': 'digital-marketing',
          'animation': '3d-animation',
          'diploma': 'diploma-courses',
          'cyber': 'cyber-security',
        };
        const urlSlug = urlSlugMap[filter] || filter;
        url.searchParams.set('dept', urlSlug);
      }
      window.history.pushState({}, '', url);

      // Smooth scroll to top of courses
      const coursesIntro = document.querySelector('.courses-intro');
      if (coursesIntro) {
        coursesIntro.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // Add smooth transition to heading
  if (heading) {
    heading.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
  }
});
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
