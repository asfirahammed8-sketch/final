<?php
/**
 * Shared Site Header Component
 * Creative IT Institute - DreamIT Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

$contactInfo = get_setting('contact', []);
$generalInfo = get_setting('general', []);

$siteName    = site_brand();
$tagline     = site_tagline();
$logoUrl     = !empty($generalInfo['logo_url']) ? sanitize_image_url($generalInfo['logo_url']) : null;
$headerCtaText = !empty($generalInfo['header_cta_text']) ? $generalInfo['header_cta_text'] : 'Contact Us';
$headerCtaUrl  = !empty($generalInfo['header_cta_url']) ? base_url($generalInfo['header_cta_url']) : base_url('contact/');
$navCoursesLimit = !empty($generalInfo['header_nav_courses_limit']) ? (int)$generalInfo['header_nav_courses_limit'] : 6;
if ($navCoursesLimit < 1) {
    $navCoursesLimit = 6;
}

// Dynamically fetch active departments for header navigation
$navDepartments = [];
try {
    $db = Database::getConnection();
    $deptStmt = $db->prepare("SELECT id, name, slug FROM departments WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT :lim");
    $deptStmt->bindValue(':lim', $navCoursesLimit, PDO::PARAM_INT);
    $deptStmt->execute();
    $navDepartments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $navDepartments = [
        ['name' => 'Graphic & Multimedia', 'slug' => 'graphic'],
        ['name' => 'Web & Software', 'slug' => 'web'],
        ['name' => 'Digital Marketing', 'slug' => 'digital'],
        ['name' => '3D Animation & VFX', 'slug' => 'animation'],
        ['name' => '1 Year Diploma', 'slug' => 'diploma'],
        ['name' => 'Cyber Security', 'slug' => 'cyber'],
    ];
}

// Resolve dynamic SEO metadata with smart automated fallbacks
if (!isset($pageSlug)) {
    if (http_response_code() === 404) {
        $pageSlug = '404';
    } elseif (!empty($course) && is_array($course) && !empty($course['title'])) {
        $pageSlug = 'course-detail';
        $courseSeoEntity = $courseSeoEntity ?? $course;
    } elseif (!empty($activeNav)) {
        $pageSlug = ($activeNav === 'stories') ? 'success-stories' : $activeNav;
    } else {
        $pageSlug = 'home';
    }
}
if ($pageSlug === 'course-detail' && empty($courseSeoEntity) && !empty($course)) {
    $courseSeoEntity = $course;
}

$runtimeContext = [
    'title'         => $pageTitle ?? null,
    'description'   => $metaDesc ?? null,
    'canonical_url' => $canonicalUrl ?? null,
    'og_image'      => $ogImage ?? null,
    'course'        => $courseSeoEntity ?? null
];

// Skip get_page_seo() if page has manually set all SEO data (like project-detail)
if ($pageSlug === 'project-detail-skip-seo') {
    // Use already-set variables from the page, don't call get_page_seo()
    $ogTitle = $ogTitle ?? $pageTitle;
    $ogDesc = $ogDesc ?? $metaDesc;
    $twTitle = $twTitle ?? $pageTitle;
    $twDesc = $twDesc ?? $metaDesc;
    $twImage = $twImage ?? ($ogImage ?? base_url('frontend/assets/images/og-home.jpg'));
} else {
    $pageSeo = get_page_seo($pageSlug, $runtimeContext);

    $pageTitle    = $pageSeo['title'];
    $metaDesc     = $pageSeo['meta_description'];
    $canonicalUrl = $pageSeo['canonical_url'];
    // Automatically enforce noindex on 404 pages, otherwise use Google modern rich results standard
    $robotsMeta   = (http_response_code() === 404) ? 'noindex, nofollow' : ($robotsMeta ?? (!empty($pageSeo['robots']) ? $pageSeo['robots'] : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'));
    $ogTitle      = $pageSeo['og_title'];
    $ogDesc       = $pageSeo['og_description'];
    $ogImage      = $pageSeo['og_image'];
    $twTitle      = $pageSeo['twitter_title'];
    $twDesc       = $pageSeo['twitter_description'];
    $twImage      = $pageSeo['twitter_image'];
}

$activeNav   = $activeNav ?? 'home';
$assetPrefix = base_url('frontend/assets');
$ogType      = $ogType ?? 'website';
$canonicalOrgUrl = get_canonical_base() . '/';

// Build Dynamic Social Links for Schema.org sameAs
$socialInfo = get_setting('social', []);
$sameAsLinks = [];
if (is_array($socialInfo)) {
    foreach ($socialInfo as $sLink) {
        if (is_string($sLink) && filter_var($sLink, FILTER_VALIDATE_URL)) {
            $sameAsLinks[] = $sLink;
        }
    }
}
if (empty($sameAsLinks)) {
    $sameAsLinks = [
        "https://www.facebook.com/CreativeITInstitute",
        "https://www.youtube.com/@CreativeITInstitute",
        "https://www.linkedin.com/school/creativeitinstitute/"
    ];
}

$phoneList = !empty($contactInfo['phones'])
    ? (is_array($contactInfo['phones']) ? $contactInfo['phones'] : explode("\n", (string)$contactInfo['phones']))
    : ['+880 1777308777'];
$primaryPhone = trim($phoneList[0] ?? '+880 1777308777');

// Use new head_office_address if available, fallback to old address field
$headOfficeAddress = $contactInfo['head_office_address'] ?? $contactInfo['address'] ?? '';
$headOfficeCity = $contactInfo['head_office_city'] ?? 'Dhaka';
$headOfficeArea = $contactInfo['head_office_area'] ?? 'Dhanmondi';

$cleanAddress = !empty($headOfficeAddress)
    ? str_replace(["\r\n", "\r", "\n"], ', ', trim($headOfficeAddress))
    : 'Momtaz Plaza, House #07, Road #04, Dhanmondi, Dhaka 1205, Bangladesh';

// Dynamic EducationalOrganization Schema
$orgSchema = [
    "@context" => "https://schema.org",
    "@type" => "EducationalOrganization",
    "name" => $siteName,
    "url" => $canonicalOrgUrl,
    "logo" => $logoUrl ?: ($canonicalOrgUrl . 'frontend/assets/images/favicon.svg'),
    "description" => !empty($tagline) ? $tagline : "Professional IT training and career-focused courses in Bangladesh.",
    "telephone" => $primaryPhone,
    "email" => !empty($contactInfo['email']) ? $contactInfo['email'] : 'info@creativeitinstitute.com',
    "address" => [
        "@type" => "PostalAddress",
        "streetAddress" => $cleanAddress,
        "addressLocality" => $headOfficeCity,
        "addressRegion" => $headOfficeArea,
        "postalCode" => "1205",
        "addressCountry" => "BD"
    ],
    "sameAs" => array_values(array_unique($sameAsLinks))
];

// Dynamic WebSite Schema with Sitelinks Searchbox
$websiteSchema = [
    "@context" => "https://schema.org",
    "@type" => "WebSite",
    "name" => $siteName,
    "url" => $canonicalOrgUrl,
    "potentialAction" => [
        "@type" => "SearchAction",
        "target" => [
            "@type" => "EntryPoint",
            "urlTemplate" => $canonicalOrgUrl . "courses/?search={search_term_string}"
        ],
        "query-input" => "required name=search_term_string"
    ]
];

// Dynamic BreadcrumbList Schema for Inner Pages
if ($pageSlug !== 'home' && $pageSlug !== '404') {
    $hasBreadcrumb = false;
    if (!empty($pageSchema) && is_array($pageSchema)) {
        $checkList = isset($pageSchema['@type']) ? [$pageSchema] : $pageSchema;
        foreach ($checkList as $item) {
            if (($item['@type'] ?? '') === 'BreadcrumbList') {
                $hasBreadcrumb = true;
                break;
            }
        }
    }
    if (!$hasBreadcrumb) {
        $autoBreadcrumb = [
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
                    "name" => $pageTitle,
                    "item" => $canonicalUrl
                ]
            ]
        ];
        if (empty($pageSchema)) {
            $pageSchema = $autoBreadcrumb;
        } elseif (is_array($pageSchema)) {
            if (isset($pageSchema['@context'])) {
                $pageSchema = [$pageSchema, $autoBreadcrumb];
            } else {
                $pageSchema[] = $autoBreadcrumb;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#0b132b" />
  <meta name="format-detection" content="telephone=no" />
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($metaDesc) ?>" />
  <meta name="robots" content="<?= e($robotsMeta) ?>" />

  <!-- Canonical URL -->
  <link rel="canonical" href="<?= e($canonicalUrl) ?>" />

  <!-- Dynamic Favicons / Site Icons (SEO Optimized) -->
  <?php
    // Get dynamic favicon URLs from settings with secure fallbacks
    $faviconIco = !empty($generalInfo['favicon_ico']) ? sanitize_image_url($generalInfo['favicon_ico']) : base_url('favicon.ico');
    $faviconSvg = !empty($generalInfo['favicon_svg']) ? sanitize_image_url($generalInfo['favicon_svg']) : base_url('favicon.svg');
    $appleTouchIcon = !empty($generalInfo['apple_touch_icon']) ? sanitize_image_url($generalInfo['apple_touch_icon']) : base_url('apple-touch-icon.png');
    $androidIcon = !empty($generalInfo['android_icon_192']) ? sanitize_image_url($generalInfo['android_icon_192']) : base_url('android-chrome-192x192.png');

    // Cache-busting version based on settings update time (forces browser reload when icons change)
    try {
      $db = Database::getConnection();
      $stmt = $db->query("SELECT updated_at FROM site_settings WHERE setting_key = 'general' LIMIT 1");
      $row = $stmt->fetch();
      $iconVersion = $row ? strtotime($row['updated_at']) : time();
    } catch (Exception $e) {
      $iconVersion = time();
    }
  ?>
  <link rel="icon" href="<?= e($faviconIco) ?>?v=<?= $iconVersion ?>" sizes="any" />
  <link rel="icon" href="<?= e($faviconSvg) ?>?v=<?= $iconVersion ?>" type="image/svg+xml" />
  <link rel="apple-touch-icon" href="<?= e($appleTouchIcon) ?>?v=<?= $iconVersion ?>" />
  <link rel="manifest" href="<?= base_url('site.webmanifest') ?>" />
  <meta name="msapplication-TileImage" content="<?= e($androidIcon) ?>?v=<?= $iconVersion ?>" />
  <meta name="msapplication-TileColor" content="#c92127" />

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="<?= e($ogType) ?>" />
  <meta property="og:title" content="<?= e($ogTitle) ?>" />
  <meta property="og:description" content="<?= e($ogDesc) ?>" />
  <meta property="og:url" content="<?= e($canonicalUrl) ?>" />
  <meta property="og:site_name" content="<?= e($siteName) ?>" />
  <meta property="og:image" content="<?= e($ogImage) ?>" />
  <meta property="og:image:alt" content="<?= e($pageTitle) ?>" />
  <meta property="og:image:width" content="1200" />
  <meta property="og:image:height" content="630" />
  <meta property="og:image:type" content="image/jpeg" />
  <meta property="og:locale" content="en_US" />

  <!-- Twitter / X Cards -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= e($twTitle) ?>" />
  <meta name="twitter:description" content="<?= e($twDesc) ?>" />
  <meta name="twitter:image" content="<?= e($twImage) ?>" />

  <meta name="csrf-token" content="<?= e(csrf_token()) ?>" />
  <meta name="base-url" content="<?= e(base_url()) ?>" />
  <meta name="api-base" content="<?= e(base_url('api')) ?>" />

  <!-- Preconnect and Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <!-- Global Core Styles -->
  <link rel="stylesheet" href="<?= $assetPrefix ?>/css/design-tokens.css" />
  <link rel="stylesheet" href="<?= $assetPrefix ?>/css/header.css" />
  <link rel="stylesheet" href="<?= $assetPrefix ?>/css/components.css" />
  <link rel="stylesheet" href="<?= $assetPrefix ?>/css/footer.css" />
  <link rel="stylesheet" href="<?= $assetPrefix ?>/css/style.css" />

  <?php if (!empty($pageCss)): ?>
    <link rel="stylesheet" href="<?= $assetPrefix ?>/css/pages/<?= e($pageCss) ?>" />
  <?php endif; ?>

  <?php if (isset($extraCss)): ?>
    <?= $extraCss ?>
  <?php endif; ?>

  <!-- Dynamic Educational Organization JSON-LD Schema -->
  <script type="application/ld+json">
  <?= json_encode($orgSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
  </script>

  <!-- Dynamic WebSite Sitelinks Searchbox JSON-LD Schema -->
  <script type="application/ld+json">
  <?= json_encode($websiteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
  </script>

  <?php if (!empty($pageSchema)): ?>
    <script type="application/ld+json">
    <?= is_array($pageSchema) ? json_encode($pageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $pageSchema ?>
    </script>
  <?php endif; ?>
</head>
<body>

<!-- Skip to main content for keyboard/screen reader users -->
<a href="#mainContent" class="skip-to-main" style="position:absolute;left:-9999px;top:4px;background:#c92127;color:#fff;padding:8px 16px;border-radius:4px;font-size:14px;font-weight:600;z-index:9999;text-decoration:none;" onblur="this.style.left='-9999px'" onfocus="this.style.left='4px'">Skip to main content</a>

<header class="site-header" role="banner">
  <div class="container">
    <div class="header-inner">

      <!-- LOGO -->
      <a href="<?= base_url('/') ?>" class="header-logo" aria-label="<?= e($siteName) ?> — Home">
        <?php if ($logoUrl): ?>
          <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" class="header-logo-img" style="height:40px;max-width:180px;object-fit:contain;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
          <div class="header-logo-fallback" style="display:none;align-items:center;gap:8px;">
            <svg viewBox="0 0 40 40" fill="none" aria-hidden="true" focusable="false">
              <path d="M8 8C4 12 4 28 8 32C12 36 28 36 32 30" stroke="#0B2046" stroke-width="4" stroke-linecap="round"/>
              <circle cx="20" cy="20" r="5" fill="#0B2046"/>
            </svg>
            <div class="header-logo-text">
              <?php
                $siteWords = explode(' ', $siteName);
                $firstWord = array_shift($siteWords);
                $restWords = implode(' ', $siteWords);
              ?>
              <strong><?= e($firstWord ?: 'CREATIVE') ?></strong>
              <span><?= e($restWords ?: 'IT INSTITUTE') ?></span>
            </div>
          </div>
        <?php else: ?>
          <svg viewBox="0 0 40 40" fill="none" aria-hidden="true" focusable="false">
            <path d="M8 8C4 12 4 28 8 32C12 36 28 36 32 30" stroke="#0B2046" stroke-width="4" stroke-linecap="round"/>
            <circle cx="20" cy="20" r="5" fill="#0B2046"/>
          </svg>
          <div class="header-logo-text">
            <?php
              $siteWords = explode(' ', $siteName);
              $firstWord = array_shift($siteWords);
              $restWords = implode(' ', $siteWords);
            ?>
            <strong><?= e($firstWord ?: 'CREATIVE') ?></strong>
            <span><?= e($restWords ?: 'IT INSTITUTE') ?></span>
          </div>
        <?php endif; ?>
      </a>

      <!-- DESKTOP NAVIGATION -->
      <nav aria-label="Primary navigation">
        <ul class="header-nav" role="list">
          <li class="header-nav-item">
            <a href="<?= base_url('/') ?>" class="header-nav-link <?= $activeNav === 'home' ? 'active' : '' ?>">Home</a>
          </li>

          <li class="header-nav-item has-dropdown">
            <a href="<?= base_url('courses/') ?>" class="header-nav-link <?= $activeNav === 'courses' ? 'active' : '' ?>" aria-haspopup="true" aria-expanded="false">
              Courses
              <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </a>
            <ul class="dropdown-menu" role="list">
              <?php foreach ($navDepartments as $navDept): ?>
                <li><a href="<?= clean_dept_url($navDept['slug']) ?>"><?= e($navDept['name']) ?></a></li>
              <?php endforeach; ?>
              <li class="dropdown-divider" role="separator"></li>
              <li><a href="<?= base_url('courses/') ?>" class="dropdown-view-all">All Courses &rarr;</a></li>
            </ul>
          </li>

          <li class="header-nav-item">
            <a href="<?= base_url('about/') ?>" class="header-nav-link <?= $activeNav === 'about' ? 'active' : '' ?>">About Us</a>
          </li>

          <li class="header-nav-item">
            <a href="<?= base_url('success-stories/') ?>" class="header-nav-link <?= $activeNav === 'stories' ? 'active' : '' ?>">Success Stories</a>
          </li>

          <li class="header-nav-item">
            <a href="<?= base_url('gallery/') ?>" class="header-nav-link <?= $activeNav === 'gallery' ? 'active' : '' ?>">Gallery</a>
          </li>

          <li class="header-nav-item">
            <a href="<?= base_url('projects/') ?>" class="header-nav-link <?= $activeNav === 'projects' ? 'active' : '' ?>">Student Work</a>
          </li>

          <li class="header-nav-item">
            <a href="<?= base_url('faq/') ?>" class="header-nav-link <?= $activeNav === 'faq' ? 'active' : '' ?>">FAQ</a>
          </li>
        </ul>
      </nav>

      <!-- DESKTOP CTA -->
      <div class="header-cta-group">
        <a href="<?= e($headerCtaUrl) ?>" class="header-cta-btn" aria-label="<?= e($headerCtaText) ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
          </svg>
          <?= e($headerCtaText) ?>
        </a>
      </div>

      <!-- MOBILE HAMBURGER -->
      <button id="mobileMenuToggle" class="mobile-menu-toggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobileNavDrawer">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
      </button>

    </div><!-- /.header-inner -->
  </div><!-- /.container -->

  <!-- MOBILE NAVIGATION DRAWER -->
  <div id="mobileNavDrawer" class="mobile-nav-drawer" aria-hidden="true">
    <nav class="mobile-nav-container" aria-label="Mobile navigation">

      <!-- Primary Nav Items -->
      <ul class="mobile-nav-list" role="list">
        <li>
          <a href="<?= base_url('/') ?>" class="<?= $activeNav === 'home' ? 'active' : '' ?>">Home</a>
        </li>

        <!-- Collapsible Courses Accordion -->
        <li class="mobile-nav-accordion">
          <button type="button" id="mobileCoursesToggle" class="mobile-accordion-toggle <?= $activeNav === 'courses' ? 'is-active' : '' ?>" aria-expanded="false" aria-controls="mobileCoursesSubmenu">
            <span>Courses</span>
            <span class="mobile-accordion-badge">
              <span><?= count($navDepartments) ?> Depts</span>
              <svg class="mobile-accordion-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </span>
          </button>
          <ul id="mobileCoursesSubmenu" class="mobile-submenu" role="list">
            <li><a href="<?= base_url('courses/') ?>" class="mobile-submenu-all">Browse All Courses &rarr;</a></li>
            <?php foreach ($navDepartments as $navDept): ?>
              <li><a href="<?= clean_dept_url($navDept['slug']) ?>"><?= e($navDept['name']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>

        <li>
          <a href="<?= base_url('about/') ?>" class="<?= $activeNav === 'about' ? 'active' : '' ?>">About Us</a>
        </li>
        <li>
          <a href="<?= base_url('success-stories/') ?>" class="<?= $activeNav === 'stories' ? 'active' : '' ?>">Success Stories</a>
        </li>
      </ul>

      <!-- Quick Explore Chips (Student Work, Gallery, FAQ) -->
      <div class="mobile-quick-chips-wrap">
        <span class="mobile-chips-title">Explore</span>
        <div class="mobile-quick-chips">
          <a href="<?= base_url('projects/') ?>" class="mobile-chip <?= $activeNav === 'projects' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            Student Work
          </a>
          <a href="<?= base_url('gallery/') ?>" class="mobile-chip <?= $activeNav === 'gallery' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            Gallery
          </a>
          <a href="<?= base_url('faq/') ?>" class="mobile-chip <?= $activeNav === 'faq' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            FAQ
          </a>
        </div>
      </div>

      <!-- Quick Action Buttons -->
      <div class="mobile-nav-cta-wrap">
        <a href="<?= e($headerCtaUrl) ?>" class="header-cta-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
          </svg>
          <?= e($headerCtaText) ?>
        </a>
        <?php if (!empty($primaryPhone)): ?>
          <a href="tel:<?= preg_replace('/[^0-9+]/', '', $primaryPhone) ?>" class="mobile-hotline-btn" title="Call Hotline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            <span><?= e($primaryPhone) ?></span>
          </a>
        <?php endif; ?>
      </div>

      <!-- Legal / Meta Links -->
      <div class="mobile-nav-meta-links">
        <a href="<?= base_url('privacy/') ?>">Privacy Policy</a>
        <span aria-hidden="true">&bull;</span>
        <a href="<?= base_url('sitemap/') ?>">Sitemap</a>
      </div>

    </nav>
  </div>
</header>
