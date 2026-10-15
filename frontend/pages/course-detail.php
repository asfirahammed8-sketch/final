<?php
$pageCss = 'course-detail.css';

/**
 * Course Detail Page
 * Creative IT Institute - DreamIT Platform
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$db = Database::getConnection();

$slug = sanitize_text($_GET['slug'] ?? '');
$id   = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

$course = null;
if (!empty($slug)) {
    $stmt = $db->prepare("SELECT * FROM courses WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $course = $stmt->fetch();
} elseif ($id) {
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $course = $stmt->fetch();
}

// Fallback to first course if none specified or not found
if (!$course) {
    $stmt = $db->query("SELECT * FROM courses WHERE status = 'active' ORDER BY id ASC LIMIT 1");
    $course = $stmt->fetch();
}

if (!$course) {
    header("Location: " . base_url('courses.php'));
    exit;
}

$siteBrand = site_brand();

$rawTitle  = !empty($course['seo_title']) ? $course['seo_title'] : $course['title'];
$pageTitle = "{$rawTitle} Course in Bangladesh | {$siteBrand}";
$metaDesc  = !empty($course['meta_description'])
    ? $course['meta_description']
    : "Enroll in {$course['title']} course at {$siteBrand}. Practical lab training, real-world projects, expert mentor guidance, and lifetime career placement in Bangladesh.";

$ogImage = !empty($course['thumbnail_url']) ? sanitize_image_url($course['thumbnail_url']) : null;

$activeNav = 'courses';
$canonicalUrl = clean_course_url($course['slug'], true);

$deptSlug = $course['department'] ?? 'web';
$deptMap = [
    'graphic'   => 'Graphic & Multimedia',
    'web'       => 'Web & Software',
    'digital'   => 'Digital Marketing',
    'animation' => '3D Animation',
    'diploma'   => '1 Year Diploma',
    'cyber'     => 'Cyber Security',
];
$deptName = $deptMap[$deptSlug] ?? ucfirst($deptSlug);
$deptUrl = clean_dept_url($deptSlug, false);
$deptCanonical = clean_dept_url($deptSlug, true);

// Schema.org Structured Data: Course & BreadcrumbList
$pageSchema = [
    [
        "@context" => "https://schema.org",
        "@type" => "Course",
        "name" => $course['title'],
        "description" => strip_tags($course['overview'] ?: $metaDesc),
        "provider" => [
            "@type" => "EducationalOrganization",
            "name" => $siteBrand,
            "sameAs" => canonical_url('/')
        ],
        "url" => $canonicalUrl,
        "educationalCredentialAwarded" => "Professional Certificate in " . $course['title'],
        "offers" => [
            "@type" => "Offer",
            "category" => "Paid",
            "price" => (float)$course['price'],
            "priceCurrency" => $course['currency'] ?: 'BDT',
            "availability" => "https://schema.org/InStock",
            "validFrom" => date('Y-m-d')
        ],
        "hasCourseInstance" => [
            "@type" => "CourseInstance",
            "courseMode" => "blended",
            "courseWorkload" => $course['duration'] ?: '4 Months'
        ]
    ],
    [
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
            ],
            [
                "@type" => "ListItem",
                "position" => 3,
                "name" => $deptName,
                "item" => $deptCanonical
            ],
            [
                "@type" => "ListItem",
                "position" => 4,
                "name" => $course['title'],
                "item" => $canonicalUrl
            ]
        ]
    ]
];

$priceFormatted = format_bdt($course['price']);
$duration = $course['duration'] ?: '';
$totalClasses = $course['total_classes'] ?: '';
$totalProjects = $course['total_projects'] ?: '';
$rawVideo = trim($course['video_url'] ?? '');
$cleanVideo = sanitize_video_url($rawVideo);
$hasVideo = !empty($cleanVideo);
$embedUrl = $hasVideo ? safe_video_embed_url($cleanVideo) : '';

$rawThumb = trim($course['thumbnail_url'] ?? '');
$thumbUrl = !empty($rawThumb) ? sanitize_image_url($rawThumb) : '';
$hasThumb = !empty($thumbUrl);

$gradStart = $course['thumbnail_gradient_start'] ?? '#0d3844';
$gradEnd   = $course['thumbnail_gradient_end'] ?? '#031720';

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="container" style="max-width: 1240px; margin: auto; padding: 0 20px;">

  <!-- Breadcrumb Navigation -->
  <nav aria-label="Breadcrumb" style="padding: 16px 0 8px; font-size: 13px; color: #64748b;">
    <ol style="display:flex; flex-wrap:wrap; gap:8px; list-style:none; padding:0; margin:0; align-items:center;">
      <li><a href="<?= base_url('index.php') ?>" style="color:#64748b; text-decoration:none;">Home</a></li>
      <li><span style="color:#cbd5e1;">/</span></li>
      <li><a href="<?= base_url('courses.php') ?>" style="color:#64748b; text-decoration:none;">Courses</a></li>
      <li><span style="color:#cbd5e1;">/</span></li>
      <li><a href="<?= $deptUrl ?>" style="color:#64748b; text-decoration:none;"><?= e($deptName) ?></a></li>
      <li><span style="color:#cbd5e1;">/</span></li>
      <li aria-current="page" style="color:var(--color-brand-red); font-weight:600;"><?= e($course['title']) ?></li>
    </ol>
  </nav>

  <!-- ============================================================
       1. COURSE HERO
  ============================================================ -->
  <section class="course-hero-section">
    <div class="hero-grid">
      <!-- Info Left -->
      <div>
        <h1 class="course-title"><?= e($course['title']) ?></h1>

        <div class="delivery-tags-wrapper">
          <span class="delivery-label">Course Delivery Mode</span>
          <?php
          // Get delivery modes - first try course-specific, then fall back to site-wide
          $deliveryModes = null;
          $rawDeliveryModes = trim($course['course_delivery_modes'] ?? '');

          if ($rawDeliveryModes !== '' && $rawDeliveryModes !== 'null') {
              $decoded = @json_decode($rawDeliveryModes, true);
              if (is_array($decoded) && !empty($decoded)) {
                  $deliveryModes = $decoded;
              }
          }

          // Fall back to site-wide settings if no course-specific modes
          if (empty($deliveryModes)) {
              $deliveryModes = get_site_setting('general.delivery_modes', [
                  'Admission Going On',
                  'Offline (Campus Labs)',
                  'Online Live Batch',
                  'Lifetime Mentor Support'
              ]);

              if (is_string($deliveryModes)) {
                  $deliveryModes = @json_decode($deliveryModes, true) ?: [$deliveryModes];
              }
          }

          // Define icons for each mode
          $modeIcons = [
              'Theory' => '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
              'Lab Based' => '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
              'Online Live Class' => '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
              'Recorded Video' => '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>',
              'Offline (Campus Labs)' => '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
              'Online Live Batch' => '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="15" rx="2"/><polyline points="17 2 12 7 7 2"/></svg>',
              'Admission Going On' => '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
              'Lifetime Mentor Support' => '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
          ];

          // Default icon for unknown modes
          $defaultIcon = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>';

          foreach ($deliveryModes as $mode):
              $icon = $modeIcons[$mode] ?? $defaultIcon;
          ?>
            <span class="delivery-pill">
              <?= $icon ?>
              <?= e($mode) ?>
            </span>
          <?php endforeach; ?>
        </div>

        <?php if (!empty($course['overview'])): ?>
        <p class="hero-description">
          <?= nl2br(e($course['overview'])) ?>
        </p>
        <?php endif; ?>

        <!-- Key Metrics -->
        <?php
        // Count available metrics
        $metrics = [];
        if (!empty($course['price'])) $metrics[] = ['label' => 'Course Fee (BDT)', 'value' => format_bdt($course['price'])];
        if (!empty($duration)) $metrics[] = ['label' => 'Duration', 'value' => e($duration)];
        if (!empty($totalClasses)) $metrics[] = ['label' => 'Lectures &amp; Labs', 'value' => e($totalClasses)];
        if (!empty($totalProjects)) $metrics[] = ['label' => 'Real Projects', 'value' => e($totalProjects)];

        $metricsCount = count($metrics);
        $metricsGridClass = 'key-metrics-grid';
        if ($metricsCount === 3) {
            $metricsGridClass = 'key-metrics-grid-3';
        } elseif ($metricsCount === 2) {
            $metricsGridClass = 'key-metrics-grid-2';
        } elseif ($metricsCount === 1) {
            $metricsGridClass = 'key-metrics-grid-1';
        }
        ?>

        <?php if (!empty($metrics)): ?>
        <div class="<?= $metricsGridClass ?>">
          <?php foreach ($metrics as $metric): ?>
          <div class="metric-item">
            <div class="metric-number"><?= $metric['value'] ?></div>
            <div class="metric-title"><?= $metric['label'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="hero-cta-group">
          <a href="<?= base_url('contact.php?course=' . urlencode($course['title'])) ?>" class="btn-hero">
            Enroll / Apply Now
          </a>
        </div>
      </div>

      <!-- Media Right -->
      <div>
        <?php if ($hasVideo): ?>
          <div class="hero-media-card" data-video-src="<?= e($embedUrl) ?>" role="button" tabindex="0" aria-label="Watch <?= e($course['title']) ?> preview video">
            <?php if ($hasThumb): ?>
              <img class="hero-media-bg-img" src="<?= e($thumbUrl) ?>" alt="<?= e($course['title']) ?> course preview" loading="eager" width="600" height="340" />
            <?php endif; ?>
            <button class="play-trigger-badge" aria-label="Watch course preview video"></button>
          </div>
        <?php else: ?>
          <div class="hero-media-card hero-media-card-static">
            <?php if ($hasThumb): ?>
              <img class="hero-media-bg-img" src="<?= e($thumbUrl) ?>" alt="<?= e($course['title']) ?> thumbnail" loading="eager" width="600" height="340" />
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Facilities Strip -->
    <div class="facilities-card">
      <div class="facilities-title">Included Student Facilities:</div>
      <ul class="facilities-list">
        <?php
        // Get course facilities - first try course-specific, then fall back to site-wide
        $courseFacilities = null;
        $rawCourseFacilities = trim($course['course_facilities'] ?? '');

        if ($rawCourseFacilities !== '' && $rawCourseFacilities !== 'null') {
            $decoded = @json_decode($rawCourseFacilities, true);
            if (is_array($decoded) && !empty($decoded)) {
                $courseFacilities = $decoded;
            }
        }

        // Fall back to site-wide settings if no course-specific facilities
        if (empty($courseFacilities)) {
            $courseFacilities = get_site_setting('general.course_facilities', [
                'High-End Lab Access',
                '24/7 Mentor Support',
                'Recorded Class Archive',
                'ISO 9001:2015 Certificate',
                'Job Placement Support'
            ]);

            if (is_string($courseFacilities)) {
                $courseFacilities = @json_decode($courseFacilities, true) ?: [$courseFacilities];
            }
        }

        foreach ($courseFacilities as $facility):
        ?>
          <li class="facility-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?= e($facility) ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- ============================================================
       2. CURRICULUM & CAREER
  ============================================================ -->
  <section class="content-breakdown-section">
    <?php
    // Parse curriculum_highlights from database
    $highlightsList = [];
    $rawHighlights = trim($course['curriculum_highlights'] ?? '');
    if ($rawHighlights !== '' && $rawHighlights !== 'null') {
        $decoded = @json_decode($rawHighlights, true);
        if (is_array($decoded)) {
            $highlightsList = $decoded;
        }
    }

    // Parse career_paths from database
    $careerList = [];
    $rawCareers = trim($course['career_paths'] ?? '');
    if ($rawCareers !== '' && $rawCareers !== 'null') {
        $decoded = @json_decode($rawCareers, true);
        if (is_array($decoded)) {
            $careerList = $decoded;
        }
    }

    // Get class structure
    $classStructure = null;
    $rawClassStructure = trim($course['course_class_structure'] ?? '');

    if ($rawClassStructure !== '' && $rawClassStructure !== 'null') {
        $decoded = @json_decode($rawClassStructure, true);
        if (is_array($decoded) && !empty($decoded)) {
            $classStructure = $decoded;
        }
    }

    // Fall back to site-wide settings if no course-specific structure
    if (empty($classStructure)) {
        $classStructure = get_site_setting('general.class_structure', [
            [
                'title' => 'Offline On-Campus Practical Training',
                'items' => [
                    ['title' => 'Theory & Concept', 'description' => 'Fundamental principles & design patterns'],
                    ['title' => 'Lab Workshops', 'description' => 'Real-time guided execution on workstation labs']
                ]
            ],
            [
                'title' => 'Online Live & Recorded Archive',
                'items' => [
                    ['title' => 'Interactive Q&A', 'description' => 'Live mentor review sessions'],
                    ['title' => 'HD Video Archive', 'description' => 'Re-watch any lesson from your student dashboard']
                ]
            ]
        ]);

        if (is_string($classStructure)) {
            $classStructure = @json_decode($classStructure, true) ?: [];
        }
    }

    // Count available sections to determine grid layout
    $hasHighlights = !empty($highlightsList);
    $hasCareers = !empty($careerList);
    $hasClassStructure = !empty($classStructure);
    $sectionCount = ($hasHighlights ? 1 : 0) + ($hasCareers ? 1 : 0) + ($hasClassStructure ? 1 : 0);

    // Determine grid class based on number of sections
    $gridClass = 'breakdown-grid-1'; // Default single column
    if ($sectionCount === 2) {
        $gridClass = 'breakdown-grid-2';
    } elseif ($sectionCount >= 3) {
        $gridClass = 'breakdown-grid';
    }
    ?>

    <?php if ($hasHighlights || $hasCareers || $hasClassStructure): ?>
    <div class="<?= $gridClass ?>">
      <!-- Left Column: Highlights & Career -->
      <?php if ($hasHighlights || $hasCareers): ?>
      <div>
        <?php if ($hasHighlights): ?>
        <h2 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:14px;">Course Highlights:</h2>
        <ul class="highlights-list">
          <?php foreach ($highlightsList as $highlight): ?>
            <li class="highlight-item"><?= e($highlight) ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if ($hasCareers): ?>
        <h2 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:14px;<?= $hasHighlights ? 'margin-top:32px;' : '' ?>">Career Opportunities:</h2>
        <ul class="career-grid">
          <?php foreach ($careerList as $career): ?>
            <li class="career-item">
              <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="7" width="18" height="14" rx="2"/>
                <path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/>
              </svg>
              <?= e($career) ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Right Column: Class Structure -->
      <?php if ($hasClassStructure): ?>
      <div>
        <h2 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:14px;">Class Structure:</h2>
        <?php foreach ($classStructure as $block):
            $blockTitle = $block['title'] ?? '';
            $items = $block['items'] ?? [];
            if (empty($blockTitle) || empty($items)) continue;
        ?>
          <div class="distribution-card">
            <div class="dist-header-purple"><span class="dist-badge-purple"><?= e($blockTitle) ?></span></div>
            <div class="dist-body-grid">
              <?php foreach ($items as $item): ?>
                <div class="dist-subbox">
                  <div class="dist-box-title"><?= e($item['title'] ?? '') ?></div>
                  <div class="dist-box-desc"><?= e($item['description'] ?? '') ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Prerequisites -->
    <!-- Prerequisites -->
    <?php
    // Parse prerequisites from database
    $prereqList = [];
    $rawPrereq = trim($course['prerequisites'] ?? '');
    if ($rawPrereq !== '' && $rawPrereq !== 'null') {
        $decoded = @json_decode($rawPrereq, true);
        if (is_array($decoded)) {
            $prereqList = $decoded;
        }
    }

    if (!empty($prereqList)):
        // Icon map for common prerequisites
        $prereqIcons = [
            'computer' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
            'laptop' => '<path d="M20 16V7a2 2 0 00-2-2H6a2 2 0 00-2 2v9m16 0H4m16 0l1.28 2.55a1 1 0 01-.9 1.45H3.62a1 1 0 01-.9-1.45L4 16"/>',
            'dedication' => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
        ];
    ?>
    <div class="prereq-strip">
      <div class="prereq-header">Course Prerequisite:</div>
      <?php
      foreach ($prereqList as $prereq):
          // Select icon based on keyword match
          $iconSvg = $prereqIcons['computer']; // default
          $lowerPrereq = strtolower($prereq);
          if (str_contains($lowerPrereq, 'laptop') || str_contains($lowerPrereq, 'desktop')) {
              $iconSvg = $prereqIcons['laptop'];
          } elseif (str_contains($lowerPrereq, 'dedication') || str_contains($lowerPrereq, 'passion')) {
              $iconSvg = $prereqIcons['dedication'];
          }
      ?>
        <div class="prereq-item">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <?= $iconSvg ?>
          </svg>
          <?= e($prereq) ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
<?php
// --- Parse target audience from DB (JSON array of objects or strings)
$rawAudience = trim($course['target_audience'] ?? '');
$audienceList = [];
if ($rawAudience !== '' && $rawAudience !== 'null') {
    $decoded = @json_decode($rawAudience, true);
    if (is_array($decoded)) $audienceList = $decoded;
}
// Clean, real-time SVG icons for audience personas (no color box backgrounds)
$audienceSvgMap = [
    'designer'     => '<path d="M12 36l4-12 16-16 8 8-16 16-12 4z" stroke="#0284c7" stroke-width="2.5" stroke-linejoin="round" fill="none"/><circle cx="32" cy="16" r="3" fill="#0284c7"/>',
    'career'       => '<path d="M12 34l8-8 6 6 12-14" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="30 18 38 18 38 26" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>',
    'student'      => '<polygon points="24,10 40,18 24,26 8,18" fill="#0f172a"/><polygon points="12,20 12,28 24,34 36,28 36,20 24,26" fill="#334155"/>',
    'freelancer'   => '<rect x="8" y="12" width="32" height="22" rx="3" stroke="#0284c7" stroke-width="2" fill="none"/><path d="M16 38h16M24 34v4" stroke="#0284c7" stroke-width="2" stroke-linecap="round"/>',
    'job'          => '<rect x="10" y="16" width="28" height="20" rx="3" stroke="#0284c7" stroke-width="2.2" fill="none"/><path d="M18 16v-4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4" stroke="#0284c7" stroke-width="2.2"/>',
    'tech'         => '<polyline points="16 18 10 24 16 30" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="32 18 38 24 32 30" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><line x1="26" y1="14" x2="22" y2="34" stroke="#0284c7" stroke-width="2" stroke-linecap="round"/>',
    'entrepreneur' => '<path d="M24 6l4 8 8 2-6 6 1.5 8.5L24 26l-7.5 4.5L18 22l-6-6 8-2z" stroke="#0284c7" stroke-width="2" stroke-linejoin="round" fill="none"/>',
    'business'     => '<line x1="12" y1="36" x2="12" y2="28" stroke="#0284c7" stroke-width="3" stroke-linecap="round"/><line x1="20" y1="36" x2="20" y2="20" stroke="#0284c7" stroke-width="3" stroke-linecap="round"/><line x1="28" y1="36" x2="28" y2="24" stroke="#0284c7" stroke-width="3" stroke-linecap="round"/><line x1="36" y1="36" x2="36" y2="14" stroke="#0284c7" stroke-width="3" stroke-linecap="round"/>',
    'creative'     => '<circle cx="24" cy="24" r="16" stroke="#0284c7" stroke-width="2" fill="none"/><circle cx="18" cy="20" r="2.5" fill="#0284c7"/><circle cx="28" cy="18" r="2.5" fill="#0284c7"/><circle cx="32" cy="26" r="2.5" fill="#0284c7"/>',
    'developer'    => '<path d="M14 18l-6 6 6 6M34 18l6 6-6 6M27 12l-6 24" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round"/>',
    'default'      => '<circle cx="24" cy="18" r="8" stroke="#0284c7" stroke-width="2" fill="none"/><path d="M10 38c0-7 6.2-12 14-12s14 5 14 12" stroke="#0284c7" stroke-width="2" fill="none"/>',
];
function getAudienceSvg(string $label, array $map): string {
    $l = strtolower($label);
    foreach ($map as $key => $svg) {
        if ($key !== 'default' && str_contains($l, $key)) return $svg;
    }
    return $map['default'];
}

// --- Parse softwares_learned from DB (JSON array of objects or strings)
$rawSoftware = trim($course['softwares_learned'] ?? '');
$softwareList = [];
if ($rawSoftware !== '' && $rawSoftware !== 'null') {
    $decoded = @json_decode($rawSoftware, true);
    if (is_array($decoded)) $softwareList = $decoded;
}
// Clean, authentic SVG icons for software tools (real-time brand vectors, no color block tiles)
$softwareSvgMap = [
    'photoshop'      => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#001e36"/><path d="M14 16h6a5 5 0 0 1 0 10h-6v6h-3V16h3zm0 7h6a2 2 0 0 0 0-4h-6v4z" fill="#31a8ff"/><path d="M28 24.5c0-1.5 1.2-2.5 3-2.5 1.5 0 2.8.7 3.5 1.8l-2.2 1.4c-.3-.5-.7-.8-1.3-.8-.5 0-.8.3-.8.6 0 .5.5.7 1.8 1.1 2.2.7 3.2 1.6 3.2 3.4 0 2.2-1.7 3.5-3.8 3.5-2 0-3.3-1-4-2.2l2.3-1.4c.4.6 1 1 1.7 1 .6 0 1-.3 1-.7 0-.5-.5-.7-1.8-1.1-2.1-.7-3-1.7-3-3.1z" fill="#31a8ff"/>',
    'illustrator'    => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#330000"/><path d="M13 32l6-16h3.2l6 16h-3.4l-1.4-4h-5.6l-1.4 4H13zm5.8-6.6h3.6l-1.8-5.4-1.8 5.4z" fill="#ff9a00"/><path d="M31.5 19.5a1.8 1.8 0 1 1 0-3.6 1.8 1.8 0 0 1 0 3.6zm-1.5 2.5h3v10h-3V22z" fill="#ff9a00"/>',
    'indesign'       => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#1a0033"/><path d="M14 16h3.5v16H14V16z" fill="#ff3366"/><path d="M21 16h5.5c4.8 0 8 3.3 8 8s-3.2 8-8 8H21V16zm3.5 13h2c2.8 0 4.5-2 4.5-5s-1.7-5-4.5-5h-2v10z" fill="#ff3366"/>',
    'after effects'  => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#00005b"/><path d="M12 32l5.5-16h3.2L26 32h-3.2l-1.2-3.8h-5.2L15.2 32H12zm5.2-6.4h3.4L19 19.8l-1.8 5.8z" fill="#9999ff"/><path d="M28 24.5c0-4.5 3.2-7.5 7.2-7.5 4 0 6.8 2.8 6.8 7.2v1.2H31.5c.3 2.2 1.8 3.8 4 3.8 1.4 0 2.5-.6 3.2-1.6l2 1.8c-1.2 1.6-3 2.6-5.2 2.6-4.5 0-7.5-3-7.5-7.5zm10.5-1.5c-.2-2-1.6-3.3-3.3-3.3s-3.1 1.3-3.3 3.3h6.6z" fill="#9999ff"/>',
    'premiere'       => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#00005b"/><path d="M14 16h6.5a5.5 5.5 0 0 1 0 11H17v5h-3V16zm3 8h3.5a2.5 2.5 0 0 0 0-5H17v5z" fill="#ea77ff"/><path d="M26 21h3v2.2c.8-1.5 2.2-2.4 3.8-2.2v3.2c-2.4-.2-3.8 1.2-3.8 3.8V32h-3V21z" fill="#ea77ff"/>',
    'xd'             => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#2b0042"/><path d="M13 16h3.8l3.6 5.8 3.6-5.8h3.8l-5.4 8.2 5.8 7.8h-3.8l-4-5.8-4 5.8H13l5.8-7.8L13 16z" fill="#ff61f6"/><path d="M27 16h4.5c4.5 0 7.5 3.2 7.5 8s-3 8-7.5 8H27V16zm3 13.2h1.5c2.8 0 4.5-2.2 4.5-5.2s-1.7-5.2-4.5-5.2H30v10.4z" fill="#ff61f6"/>',
    'figma'          => '<path d="M19 8h5a5 5 0 0 1 5 5 5 5 0 0 1-5 5h-5V8z" fill="#ff7262"/><path d="M14 18a5 5 0 0 1 5-5h5v10h-5a5 5 0 0 1-5-5z" fill="#a259ff"/><path d="M14 8a5 5 0 0 1 5-5h5v10h-5a5 5 0 0 1-5-5z" fill="#f24e1e"/><path d="M14 28a5 5 0 0 1 5-5h5v5a5 5 0 0 1-5 5 5 5 0 0 1-5-5z" fill="#0acf83"/><circle cx="29" cy="23" r="5" fill="#1abcfe"/>',
    'blender'        => '<ellipse cx="24" cy="28" rx="14" ry="10" fill="#ea7600"/><circle cx="24" cy="28" r="6" fill="#fff"/><circle cx="24" cy="28" r="3.5" fill="#255799"/><path d="M14 14l8 10M34 14l-8 10M24 8v12" stroke="#ea7600" stroke-width="3.5" stroke-linecap="round"/>',
    'davinci'        => '<circle cx="24" cy="24" r="18" fill="#1e293b"/><circle cx="20" cy="18" r="5" fill="#ef4444"/><circle cx="28" cy="18" r="5" fill="#3b82f6"/><circle cx="24" cy="28" r="5" fill="#eab308"/>',
    'cinema 4d'      => '<circle cx="24" cy="24" r="18" fill="#07192f"/><path d="M24 10a14 14 0 1 0 14 14h-5a9 9 0 1 1-9-9V10z" fill="#00b4d8"/><circle cx="24" cy="24" r="4" fill="#fff"/>',
    'vs code'        => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#001f3f"/><path d="M33 11.5l-6-3.8c-.8-.5-1.8-.2-2.3.6L16 22.5l-4.5-3.5c-.6-.5-1.5-.4-2 .2l-1.5 1.8c-.5.6-.4 1.5.2 2l4.8 3.8-4.8 3.8c-.6.5-.7 1.4-.2 2l1.5 1.8c.5.6 1.4.7 2 .2l4.5-3.5 8.7 14.2c.5.8 1.5 1.1 2.3.6l6-3.8c.6-.4 1-1.1 1-1.8V13.3c0-.7-.4-1.4-1-1.8z" fill="#007acc"/>',
    'react'          => '<ellipse cx="24" cy="24" rx="16" ry="6.5" fill="none" stroke="#61dafb" stroke-width="2" transform="rotate(0 24 24)"/><ellipse cx="24" cy="24" rx="16" ry="6.5" fill="none" stroke="#61dafb" stroke-width="2" transform="rotate(60 24 24)"/><ellipse cx="24" cy="24" rx="16" ry="6.5" fill="none" stroke="#61dafb" stroke-width="2" transform="rotate(120 24 24)"/><circle cx="24" cy="24" r="3.5" fill="#61dafb"/>',
    'node'           => '<path d="M24 8l14 8v16l-14 8-14-8V16l14-8z" fill="#22c55e"/><path d="M24 13l10 5.8v11.4L24 36l-10-5.8V18.8L24 13z" fill="#15803d"/>',
    'python'         => '<path d="M23.5 8c-6 0-5.5 2.6-5.5 2.6l.01 2.7h5.6v.8h-7.8c-3.6 0-6.3 2.1-6.3 6.2 0 4 3.1 5.9 6.3 5.9h1.7v-2.4c0-2.8 2.4-5.2 5.2-5.2h7.8c2.3 0 4.1-1.9 4.1-4.2V12.2c0-2.3-1.9-4.2-4.2-4.2h-6.9zm-2.1 2.1a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" fill="#366e9c"/><path d="M24.5 40c6 0 5.5-2.6 5.5-2.6l-.01-2.7h-5.6v-.8h7.8c3.6 0 6.3-2.1 6.3-6.2 0-4-3.1-5.9-6.3-5.9h-1.7v2.4c0 2.8-2.4 5.2-5.2 5.2h-7.8c-2.3 0-4.1 1.9-4.1 4.2v2.4c0 2.3 1.9 4.2 4.2 4.2h6.9zm2.1-2.1a1 1 0 1 1 0-2 1 1 0 0 1 0 2z" fill="#ffd43b"/>',
    'mongo'          => '<path d="M24 6s-10 8-10 17c0 8.5 6.5 15 10 19 3.5-4 10-10.5 10-19 0-9-10-17-10-17z" fill="#10aa50"/><path d="M24 6v36c3.5-4 10-10.5 10-19 0-9-10-17-10-17z" fill="#13aa52"/>',
    'php'            => '<ellipse cx="24" cy="24" rx="18" ry="11" fill="#777bb4"/><path d="M15 20h3a2.5 2.5 0 0 1 0 5h-2l-1 4h-2l2-9zm1.8 3.5h1.2a1 1 0 0 0 0-2h-1.2l0 2zM23 20h2l-.5 2.2a2.8 2.8 0 0 1 2.3-1.2c1.6 0 2.2 1 2 2.5l-.9 5.5h-2l.8-4.8c.1-.7-.2-1.2-.8-1.2a1.8 1.8 0 0 0-1.6 1.4l-.8 4.6h-2L23 20zm8 0h3a2.5 2.5 0 0 1 0 5h-2l-1 4h-2l2-9zm1.8 3.5h1.2a1 1 0 0 0 0-2h-1.2l0 2z" fill="#fff"/>',
    'github'         => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#1e293b"/><path fill-rule="evenodd" clip-rule="evenodd" d="M24 10a14 14 0 0 0-4.4 27.3c.7.1 1-.3 1-.7v-2.5c-3.9.8-4.7-1.9-4.7-1.9-.6-1.6-1.5-2.1-1.5-2.1-1.3-.9.1-.9.1-.9 1.4.1 2.2 1.5 2.2 1.5 1.3 2.1 3.3 1.5 4.1 1.2.1-.9.5-1.5 1-1.9-3.1-.4-6.4-1.6-6.4-7a5.5 5.5 0 0 1 1.5-3.8c-.2-.4-.7-1.8.1-3.7 0 0 1.2-.4 3.8 1.4a13 13 0 0 1 7 0c2.6-1.8 3.8-1.4 3.8-1.4.8 1.9.3 3.3.1 3.7a5.5 5.5 0 0 1 1.5 3.8c0 5.4-3.3 6.6-6.4 7 .5.5 1 1.3 1 2.6V36.6c0 .4.3.8 1 .7A14 14 0 0 0 24 10z" fill="#fff"/>',
    'docker'         => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#0db7ed"/><path d="M13 22h3v3h-3zm4 0h3v3h-3zm4 0h3v3h-3zm4 0h3v3h-3zm-8-4h3v3h-3zm4 0h3v3h-3zm4 0h3v3h-3zm-4-4h3v3h-3zm18 10c-.3-.2-1.8-1.2-4-1-.5-1.3-1.6-2.2-1.8-2.3l-.7-.5-.4.8c-.8 1.4-1 3-.7 4.2-1.7.9-4 1-5.7.8H10c-.5 2 0 4.8 2 6.8 2.8 2.8 7 3.2 11.2 3.2 6.8 0 12.8-3.4 14.8-11.2h.5c.8 0 1.5-.3 2-.8l.2-.2c-.8-.6-1.8-1-2.7-1z" fill="#fff"/>',
    'canva'          => '<circle cx="24" cy="24" r="18" fill="#00c4cc"/><path d="M22 17c-4.4 0-7 3.5-7 8 0 4 2.8 7 7 7 2.2 0 4-1 5-2.2l-1.8-1.6c-.8.8-1.8 1.4-3.2 1.4-2.5 0-4.2-1.8-4.2-4.6 0-3 1.8-5 4.2-5 1.5 0 2.6.7 3.4 1.7l1.8-1.6c-1.2-1.7-3-3.1-5.2-3.1z" fill="#fff"/>',
    'gemini'         => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#0f172a"/><path d="M24 10c0 7.7-6.3 14-14 14 7.7 0 14 6.3 14 14 0-7.7 6.3-14 14-14-7.7 0-14-6.3-14-14z" fill="url(#geminiGrad)"/><defs><linearGradient id="geminiGrad" x1="10" y1="10" x2="38" y2="38"><stop stop-color="#4285f4"/><stop offset="0.5" stop-color="#9b72cb"/><stop offset="1" stop-color="#d96570"/></linearGradient></defs>',
    'chatgpt'        => '<circle cx="24" cy="24" r="18" fill="#10a37f"/><path d="M28.5 21.2a4 4 0 0 0-4-3.2c-.3 0-.6 0-.8.1a4 4 0 0 0-3.7-2.6 4.3 4.3 0 0 0-3.8 2.2 4 4 0 0 0-2.4 2.2 4.3 4.3 0 0 0 .4 4.4 4 4 0 0 0-.4 4.3 4.3 4.3 0 0 0 3.2 2.1c.2.5.5.9.9 1.2a4.3 4.3 0 0 0 4.8 0 4 4 0 0 0 2.8-1.2 4.3 4.3 0 0 0 2.5-3.5 4 4 0 0 0 1.2-2.8 4.3 4.3 0 0 0-.7-3.2zm-4.5 9.2a2.3 2.3 0 0 1-2.4-.3l.3-.6 2.2-3.8a.7.7 0 0 1 .6-.4.7.7 0 0 1 .7.4l1.2 2.1a2.3 2.3 0 0 1-2.6 2.6z" fill="#fff"/>',
    'analytics'      => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#e37400"/><rect x="14" y="26" width="4" height="8" rx="2" fill="#fff"/><rect x="22" y="20" width="4" height="14" rx="2" fill="#fff"/><rect x="30" y="14" width="4" height="20" rx="2" fill="#fff"/>',
    'meta'           => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#0081fb"/><path d="M30.5 17c-2.4 0-4.2 1.7-5.5 3.4-1.3-1.7-3.1-3.4-5.5-3.4-4 0-6.5 3.3-6.5 7.4 0 4.6 3 7.6 7 7.6 2.5 0 4.3-1.4 5-2.5.7 1.1 2.5 2.5 5 2.5 4 0 7-3 7-7.6 0-4.1-2.5-7.4-6.5-7.4zm-10 12c-2.4 0-4.2-1.9-4.2-4.6 0-2.5 1.6-4.5 4.2-4.5 2.3 0 3.7 2.2 4.6 3.8-.9 2.4-2.4 5.3-4.6 5.3zm9.5 0c-2.2 0-3.7-2.9-4.6-5.3.9-1.6 2.3-3.8 4.6-3.8 2.6 0 4.2 2 4.2 4.5 0 2.7-1.8 4.6-4.2 4.6z" fill="#fff"/>',
    'default'        => '<rect x="6" y="6" width="36" height="36" rx="8" fill="#1e293b"/><path d="M16 20h16M16 24h12M16 28h8" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round"/>',
];
function getSoftwareSvg(string $name, array $map): string {
    $n = strtolower($name);
    foreach ($map as $key => $svg) {
        if ($key !== 'default' && str_contains($n, $key)) return $svg;
    }
    return $map['default'];
}
?>
<?php if (!empty($audienceList)): ?>
  <!-- ============================================================
       3. TARGET AUDIENCE
  ============================================================ -->
  <section id="audience" class="designed-for-section" aria-label="Target Audience">
    <div style="max-width:1240px;margin:auto;padding:0 20px;">
      <h2 class="section-subtitle">This Course is Designed for<span class="sub-tag">(Career Outcomes)</span></h2>
      <div class="audience-cards-grid">
        <?php foreach ($audienceList as $aud):
          $audTitle = is_array($aud) ? ($aud['title'] ?? $aud['name'] ?? 'Target Audience') : (string)$aud;
          $audDesc  = is_array($aud) ? ($aud['desc'] ?? $aud['description'] ?? '') : '';
          $customIcon = is_array($aud) ? ($aud['icon'] ?? '') : '';
        ?>
          <div class="audience-card">
            <div class="audience-icon">
              <?php if (!empty($customIcon) && (str_starts_with($customIcon, 'http://') || str_starts_with($customIcon, 'https://') || str_starts_with($customIcon, '/') || str_starts_with($customIcon, 'data:image/'))): ?>
                <img src="<?= e($customIcon) ?>" alt="<?= e($audTitle) ?>" style="width:100%;height:100%;object-fit:contain;" />
              <?php else: ?>
                <svg width="44" height="44" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                  <?= getAudienceSvg($customIcon ?: $audTitle, $audienceSvgMap) ?>
                </svg>
              <?php endif; ?>
            </div>
            <div class="audience-title"><?= e($audTitle) ?></div>
            <?php if (!empty($audDesc)): ?>
              <div class="audience-desc"><?= e($audDesc) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if (!empty($softwareList)): ?>
  <!-- ============================================================
       4. SOFTWARE CAROUSEL
  ============================================================ -->
  <section id="software-track" class="software-section" aria-label="Software Tools Learned">
    <div style="max-width:1240px;margin:auto;padding:0 20px;">
      <h2 class="section-subtitle" style="margin-bottom:0;">Tools &amp; Technologies You'll Master:<span class="sub-tag">(Industry Standard)</span></h2>
      <div class="software-slider-wrapper" style="margin-top:18px;">
        <button class="carousel-btn" id="scrollPrevBtn" aria-label="Previous Softwares" disabled>
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <div class="software-list" id="softwareSlider" tabindex="0" role="region" aria-label="Software tools list">
          <?php foreach ($softwareList as $sw):
            $swName = is_array($sw) ? ($sw['name'] ?? 'Software') : (string)$sw;
            $swCategory = is_array($sw) ? ($sw['category'] ?? '') : '';
            $customIcon = is_array($sw) ? ($sw['icon'] ?? '') : '';
            $nameSafe = e(substr(strip_tags($swName), 0, 60));
          ?>
            <div class="software-item">
              <div class="software-icon">
                <?php if (!empty($customIcon) && (str_starts_with($customIcon, 'http://') || str_starts_with($customIcon, 'https://') || str_starts_with($customIcon, '/') || str_starts_with($customIcon, 'data:image/'))): ?>
                  <img src="<?= e($customIcon) ?>" alt="<?= $nameSafe ?>" style="width:100%;height:100%;object-fit:contain;" />
                <?php else: ?>
                  <svg width="44" height="44" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                    <?= getSoftwareSvg($customIcon ?: $swName, $softwareSvgMap) ?>
                  </svg>
                <?php endif; ?>
              </div>
              <div class="software-name"><?= $nameSafe ?></div>
              <?php if (!empty($swCategory)): ?>
                <div class="software-cat"><?= e($swCategory) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <button class="carousel-btn" id="scrollNextBtn" aria-label="Next Softwares">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php
// Retrieve relational FAQs for this course (faqs.course_id = courses.id)
$courseFaqs = [];
try {
    $faqStmt = $db->prepare("
        SELECT id, question, answer, category
        FROM faqs
        WHERE is_active = 1
          AND (course_id = :cid1 OR (course_id IS NULL AND (category = :dept OR category = 'General' OR category = 'general')))
        ORDER BY (course_id = :cid2) DESC, sort_order ASC, id ASC
        LIMIT 6
    ");
    $faqStmt->execute([
        ':cid1' => (int)$course['id'],
        ':cid2' => (int)$course['id'],
        ':dept' => $deptSlug ?? 'design',
    ]);
    $courseFaqs = $faqStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $courseFaqs = [];
}
?>
<?php if (!empty($courseFaqs)):
  $leftFaqs  = [];
  $rightFaqs = [];
  foreach ($courseFaqs as $i => $f) {
      if ($i % 2 === 0) {
          $leftFaqs[] = $f;
      } else {
          $rightFaqs[] = $f;
      }
  }
?>
  <!-- ============================================================
       5. COURSE FAQS (Dual-Column Responsive UI & UX Matching FAQ Page)
  ============================================================ -->
  <section class="home-faq-section" id="course-faqs" aria-label="Frequently Asked Questions">
    <div class="c-container" style="max-width: 1240px; margin: auto; padding: 0 20px;">
      <header class="home-faq-header">
        <h2 class="home-faq-title">Frequently Asked Questions</h2>
        <p class="home-faq-subtitle">Quick answers to common queries regarding this course curriculum, hands-on projects, admissions, and career placement.</p>
      </header>

      <section class="faq-grid-container" aria-label="FAQ List">
        <!-- LEFT COLUMN -->
        <div class="faq-column">
          <?php foreach ($leftFaqs as $idx => $faq):
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
          <?php foreach ($rightFaqs as $faq): ?>
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

<!-- Video Trailer Modal -->
<div class="modal-backdrop" id="videoModal" role="dialog" aria-modal="true" aria-label="Course Video Trailer">
  <div class="modal-card modal-card-video">
    <button class="video-modal-close" id="videoModalClose" aria-label="Close video">&times;</button>
    <div class="video-responsive-frame">
      <iframe id="videoModalIframe" src="" title="Course Trailer" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
  </div>
</div>

<!-- Student Work Section (Relational to this Course) -->
<?php
  $studentWorkCourseId = (int)$course['id'];
  $studentWorkLimit    = 6;
  $studentWorkHeading  = 'Student Projects — ' . $course['title'];
  $studentWorkSubtitle = 'Real-world projects developed by students enrolled in this curriculum.';
  require __DIR__ . '/../../includes/section-student-work.php';
?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
