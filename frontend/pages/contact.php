<?php
$pageCss = 'contact.css';

/**
 * Contact Us Page
 * Creative IT Institute - DreamIT Platform
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$siteBrand = site_brand();
$pageTitle = "Contact {$siteBrand} | Campus Admissions & Counseling";
$metaDesc  = "Get in touch with {$siteBrand}. Contact our academic counselors, visit our campus, or book a free course counseling session.";
$activeNav = 'contact';
$canonicalUrl = canonical_url('contact/');

$prefillCourse = sanitize_text($_GET['course'] ?? '');

$contactSettings = get_setting('contact', []);

// Hero Texts
$contactHeroTitle = $contactSettings['hero_title'] ?? $contactSettings['contact_hero_title'] ?? 'Contact Us';
$contactHeroSubtitle = $contactSettings['hero_subtitle'] ?? $contactSettings['contact_hero_subtitle'] ?? 'Have questions regarding our professional IT training programs, course admissions, or campus lab facilities? Get in touch with our expert academic counselors or visit any of our physical campuses.';

// Form Texts
$contactFormTitle = $contactSettings['form_title'] ?? $contactSettings['contact_form_title'] ?? 'Send Us a Message';
$contactFormDesc = $contactSettings['form_desc'] ?? $contactSettings['contact_form_desc'] ?? 'Fill out the inquiry form below and an academic counselor will reach out to guide you within 24 hours.';

// Head Office Information
$headOfficeName = $contactSettings['head_office_name'] ?? $contactSettings['contact_head_office_name'] ?? 'Dhanmondi Campus';
$headOfficeTitle = $contactSettings['head_office_title'] ?? $contactSettings['contact_head_office_title'] ?? 'Head Office';
$headOfficeAddress = $contactSettings['head_office_address'] ?? $contactSettings['address'] ?? $contactSettings['contact_address'] ?? 'Momtaz Plaza (4th Floor), House #07, Road #04, Dhanmondi, Dhaka-1205';
$headOfficeCity = $contactSettings['head_office_city'] ?? $contactSettings['contact_head_office_city'] ?? 'Dhaka';
$headOfficeArea = $contactSettings['head_office_area'] ?? $contactSettings['contact_head_office_area'] ?? 'Dhanmondi';
$campusHours = $contactSettings['campus_hours'] ?? $contactSettings['contact_campus_hours'] ?? 'Sat – Thu: 9 AM – 8 PM';

// Contact Details
$phones = $contactSettings['phones'] ?? $contactSettings['contact_phones'] ?? ["+880 1777308777", "+880 1624666000"];
$email  = $contactSettings['email'] ?? $contactSettings['contact_email'] ?? 'info@creativeitinstitute.com';
$whatsapp = $contactSettings['whatsapp'] ?? $contactSettings['contact_whatsapp'] ?? '';

// Google Maps URLs
$mapEmbedUrl = $contactSettings['google_maps_embed_url'] ?? $contactSettings['contact_google_maps_embed_url'] ?? "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3652.2285141561085!2d90.3789069!3d23.7397734!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b8b7a55cd36f%3A0xfcc5b021f3887396!2sCreative%20IT%20Institute!5e0!3m2!1sen!2sbd!4v1710000000000!5m2!1sen!2sbd";
$mapShareUrl = $contactSettings['google_maps_share_url'] ?? $contactSettings['contact_google_maps_share_url'] ?? 'https://maps.app.goo.gl/nhDnc5QjJ9MgQ97f9';

// Ensure phones is an array
if (!is_array($phones)) {
    $phones = !empty($phones) ? explode("\n", (string)$phones) : [];
}
$phones = array_filter(array_map('trim', $phones));

// Primary phone (Admission Hotline) for quick contact card & schema
$primaryPhone = $contactSettings['hotline'] ?? $contactSettings['contact_hotline'] ?? (!empty($phones) ? reset($phones) : '+880 1777308777');

$departmentsList = [];
try {
    $db = Database::getConnection();
    $departmentsList = $db->query("SELECT id, name, slug FROM departments WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
} catch (Exception $e) {
    // Fallback if table query fails
    $departmentsList = [
        ['slug' => 'graphic', 'name' => 'Graphic & Multimedia'],
        ['slug' => 'web', 'name' => 'Web & Software'],
        ['slug' => 'digital', 'name' => 'Digital Marketing'],
        ['slug' => 'animation', 'name' => '3D Animation & VFX'],
        ['slug' => 'diploma', 'name' => '1-Year Diploma'],
        ['slug' => 'cyber', 'name' => 'Cyber Security'],
    ];
}

// ContactPage Schema.org structured data for SEO
$pageSchema = [
    "@context" => "https://schema.org",
    "@type" => "ContactPage",
    "name" => "Contact {$siteBrand}",
    "description" => $metaDesc,
    "url" => $canonicalUrl,
    "mainEntity" => [
        "@type" => "Organization",
        "name" => $siteBrand,
        "url" => canonical_url('/'),
        "contactPoint" => [
            "@type" => "ContactPoint",
            "telephone" => $primaryPhone,
            "contactType" => "Admissions",
            "email" => $email,
            "areaServed" => "BD",
            "availableLanguage" => ["en", "bn"],
            "hoursAvailable" => [
                "@type" => "OpeningHoursSpecification",
                "dayOfWeek" => ["Saturday", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday"],
                "opens" => "09:00",
                "closes" => "20:00"
            ]
        ],
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => $headOfficeAddress,
            "addressLocality" => $headOfficeCity,
            "addressRegion" => $headOfficeArea,
            "postalCode" => "1205",
            "addressCountry" => "BD"
        ],
        "geo" => !empty($mapShareUrl) ? [
            "@type" => "GeoCoordinates",
            "name" => $headOfficeName
        ] : null
    ]
];

// Remove null values from schema
if ($pageSchema['mainEntity']['geo'] === null) {
    unset($pageSchema['mainEntity']['geo']);
}

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="container" style="max-width: 1240px; margin: auto; padding: 0 20px;">
  <header class="contact-hero">
    <h1 class="contact-page-title"><?= e($contactHeroTitle) ?></h1>
    <p class="contact-page-subtitle">
      <?= e($contactHeroSubtitle) ?>
    </p>
  </header>

  <section class="contact-main-grid">
    <!-- Contact Form -->
    <div class="contact-form-card">
      <h2 class="form-card-title"><?= e($contactFormTitle) ?></h2>
      <p class="form-card-desc"><?= e($contactFormDesc) ?></p>

      <div class="form-alert" id="formSuccessAlert" role="alert">
        Thank you! Your message has been received. Our counselor will contact you shortly.
      </div>

      <form id="contactInquiryForm" method="POST" action="<?= base_url('api/enquiry-submit.php') ?>" novalidate>
        <?= csrf_field() ?>
        <!-- Anti-spam Honeypot -->
        <input type="text" name="website" style="display:none;" tabindex="-1" autocomplete="off" />

        <?php if (!empty($prefillCourse)): ?>
          <input type="hidden" name="course_title" value="<?= e($prefillCourse) ?>" />
          <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#1d4ed8;font-weight:600;">
            Selected Course: <?= e($prefillCourse) ?>
          </div>
        <?php endif; ?>

        <div class="form-row-2col" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
          <div class="form-group">
            <label for="fullName" style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">Full Name <span style="color:#c92127;">*</span></label>
            <input type="text" name="name" id="fullName" class="form-input" placeholder="e.g. Tanvir Ahmed" required autocomplete="name" style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" />
          </div>
          <div class="form-group">
            <label for="phoneNumber" style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">Phone Number <span style="color:#c92127;">*</span></label>
            <input type="tel" name="phone" id="phoneNumber" class="form-input" placeholder="017XXXXXXXX" required autocomplete="tel" style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" />
          </div>
        </div>

        <div class="form-group" style="margin-bottom:16px;">
          <label for="emailAddress" style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">Email Address</label>
          <input type="email" name="email" id="emailAddress" class="form-input" placeholder="name@example.com" autocomplete="email" style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" />
        </div>

        <div class="form-group" style="margin-bottom:16px;">
          <label for="preferredDepartment" style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">Interested Department</label>
          <select name="department" id="preferredDepartment" style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;background:#fff;">
            <option value="">Select Department (Optional)</option>
            <?php foreach ($departmentsList as $dept): ?>
              <option value="<?= e($dept['name']) ?>"><?= e($dept['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:20px;">
          <label for="messageBox" style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">Your Message / Query</label>
          <textarea name="message" id="messageBox" class="form-textarea" rows="4" placeholder="Let us know what you would like to ask or achieve with our courses..." style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;"></textarea>
        </div>

        <button type="submit" class="btn-submit">
          Submit Inquiry
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
      </form>
    </div>

    <!-- Campus Info Right -->
    <div>
      <article class="campus-card" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <span class="campus-tag" style="display:inline-block;padding:4px 10px;background:#E8EDF5;color:#0B2046;border-radius:4px;font-size:12px;font-weight:700;margin-bottom:10px;">Address</span>
        <h3 class="campus-title" style="font-size:20px;font-weight:700;color:#000000;margin-bottom:8px;"><?= e($headOfficeName) ?></h3>
        <p class="campus-address" style="font-size:14px;color:#666666;line-height:1.5;margin-bottom:16px;">
          <?= e($headOfficeAddress) ?>
        </p>
        <div class="campus-links" style="font-size:14px;line-height:1.8;color:#333333;">
          <?php if (!empty($phones)): ?>
          <div>
            Phone:
            <?php foreach ($phones as $i => $phone): ?>
              <a href="tel:<?= preg_replace('/[^0-9+]/', '', $phone) ?>" style="color:#0B2046;text-decoration:none;font-weight:600;"><?= e($phone) ?></a><?= $i < count($phones) - 1 ? ', ' : '' ?>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <div>Email: <a href="mailto:<?= e($email) ?>" style="color:#0B2046;text-decoration:none;font-weight:600;"><?= e($email) ?></a></div>
          <?php if (!empty($mapShareUrl)): ?>
          <div style="margin-top:8px;">
            <a href="<?= e($mapShareUrl) ?>" target="_blank" rel="noopener" style="color:#0B2046;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:4px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
              </svg>
              View on Google Maps
            </a>
          </div>
          <?php endif; ?>
        </div>
      </article>

      <div class="quick-contacts-grid" aria-label="Quick contact channels">
        <a href="tel:<?= preg_replace('/[^0-9+]/', '', $primaryPhone) ?>" class="quick-contact-card quick-contact-link" aria-label="Call Admission Hotline <?= e($primaryPhone) ?>">
          <div class="quick-card-top">
            <div class="quick-icon-pill" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <span class="quick-action-cue" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
            </span>
          </div>
          <div class="quick-info-meta">
            <span class="quick-info-label">Admission Hotline</span>
            <strong class="quick-info-val" title="<?= e($primaryPhone) ?>"><?= e($primaryPhone) ?></strong>
          </div>
        </a>

        <a href="mailto:<?= e($email) ?>" class="quick-contact-card quick-contact-link" aria-label="Send email to <?= e($email) ?>">
          <div class="quick-card-top">
            <div class="quick-icon-pill" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <span class="quick-action-cue" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
            </span>
          </div>
          <div class="quick-info-meta">
            <span class="quick-info-label">Official Email</span>
            <strong class="quick-info-val quick-val-email" title="<?= e($email) ?>"><?= e($email) ?></strong>
          </div>
        </a>

        <div class="quick-contact-card quick-contact-info" aria-label="Campus Hours <?= e($campusHours) ?>">
          <div class="quick-card-top">
            <div class="quick-icon-pill" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <span class="quick-status-badge" aria-hidden="true">
              <span class="status-pulse-dot"></span>
              Open
            </span>
          </div>
          <div class="quick-info-meta">
            <span class="quick-info-label">Campus Hours</span>
            <strong class="quick-info-val" title="<?= e($campusHours) ?>"><?= e($campusHours) ?></strong>
          </div>
        </div>

        <a href="<?= e(!empty($mapShareUrl) ? $mapShareUrl : '#locationMap') ?>" <?= !empty($mapShareUrl) ? 'target="_blank" rel="noopener"' : '' ?> class="quick-contact-card quick-contact-link" aria-label="Head Campus Location: <?= e($headOfficeArea) ?>, <?= e($headOfficeCity) ?>">
          <div class="quick-card-top">
            <div class="quick-icon-pill" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <span class="quick-action-cue" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
            </span>
          </div>
          <div class="quick-info-meta">
            <span class="quick-info-label">Head Campus</span>
            <strong class="quick-info-val" title="<?= e($headOfficeArea) ?>, <?= e($headOfficeCity) ?>"><?= e($headOfficeArea) ?>, <?= e($headOfficeCity) ?></strong>
          </div>
        </a>
      </div>
    </div>
  </section>

  <!-- Location Map -->
  <section id="locationMap" class="map-embed" aria-label="Head Office Location Map">
    <?php if (!empty($mapEmbedUrl)): ?>
    <iframe
      title="<?= e($headOfficeName) ?> Location Map"
      src="<?= e($mapEmbedUrl) ?>"
      loading="lazy"
      allowfullscreen
    ></iframe>
    <?php else: ?>
    <div style="display:flex;align-items:center;justify-content:center;height:100%;background:#f8fafc;color:#64748b;text-align:center;padding:40px;">
      <div>
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 16px;opacity:0.5;">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
          <circle cx="12" cy="10" r="3"/>
        </svg>
        <p style="font-size:15px;font-weight:600;color:#334155;margin-bottom:8px;">Google Maps Not Configured</p>
        <p style="font-size:13px;">Please add your location in Admin Panel → Settings → Contact Info</p>
      </div>
    </div>
    <?php endif; ?>
  </section>
</main>

<div class="toast-container" id="toastContainer" aria-live="polite"></div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
