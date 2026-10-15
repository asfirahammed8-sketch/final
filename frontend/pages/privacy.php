<?php
$pageCss = 'privacy.css';

/**
 * Privacy Policy Page
 * Creative IT Institute - DreamIT Platform
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$siteBrand = site_brand();
$pageTitle = "Privacy Policy | {$siteBrand}";
$metaDesc  = "{$siteBrand} privacy policy — how we collect, use, store, and protect your personal data when enrolling in our IT courses or browsing our platform.";
$activeNav = 'privacy';
$canonicalUrl = canonical_url('privacy/');

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="container" style="max-width: 1240px; margin: auto; padding: 0 20px;">
  <header class="policy-header-wrap">
    <h1 class="policy-main-title">Privacy Policy</h1>
    <p class="policy-intro-lead">
      At <strong><?= e($siteBrand) ?></strong> ("we," "us," or "our"), we respect your privacy and are committed to protecting your personal information. This Privacy Policy explains how we collect, use, store, and protect your information when you visit our website, enroll in our courses, submit an enquiry, or interact with our services.
    </p>
  </header>

  <div class="policy-layout-grid">
    <aside class="policy-sidebar-nav" aria-label="Table of contents">
      <div class="sidebar-toc-title">On This Page</div>
      <ul class="sidebar-toc-list">
        <li><a href="#info-collect" class="sidebar-toc-link">1. Information We Collect</a></li>
        <li><a href="#how-use" class="sidebar-toc-link">2. How We Use Information</a></li>
        <li><a href="#data-security" class="sidebar-toc-link">3. Data Security &amp; Storage</a></li>
        <li><a href="#third-parties" class="sidebar-toc-link">4. Third-Party Disclosures</a></li>
        <li><a href="#your-rights" class="sidebar-toc-link">5. Your Privacy Rights</a></li>
        <li><a href="#contact-privacy" class="sidebar-toc-link">6. Contact Us</a></li>
      </ul>
    </aside>

    <article class="policy-content-article">
      <section class="policy-section-card" id="info-collect">
        <h2 class="policy-section-title">1. Information We Collect</h2>
        <p class="policy-p">We collect information directly from you when you submit an enquiry form, register for a course, or communicate with our counselors:</p>
        <ul class="policy-list">
          <li><strong>Contact Details:</strong> Full name, phone number, email address, and physical campus location preference.</li>
          <li><strong>Course Information:</strong> Selected learning track, batch schedule preference, and academic interests.</li>
          <li><strong>Technical Metadata:</strong> IP address, device type, browser information, and referral sources to optimize your experience.</li>
        </ul>
      </section>

      <section class="policy-section-card" id="how-use">
        <h2 class="policy-section-title">2. How We Use Your Information</h2>
        <p class="policy-p">We use the collected information exclusively to facilitate your academic journey and deliver quality services:</p>
        <ul class="policy-list">
          <li>To contact you regarding seminar bookings, course schedules, tuition fees, and admission details.</li>
          <li>To issue official ISO 9001:2015 verifiable graduation certificates.</li>
          <li>To provide continuous career placement, mentorship access, and internship opportunities.</li>
          <li>To prevent spam, secure our systems, and comply with Bangladeshi regulations.</li>
        </ul>
      </section>

      <section class="policy-section-card" id="data-security">
        <h2 class="policy-section-title">3. Data Security &amp; Storage</h2>
        <p class="policy-p">We maintain strict technical and organizational safeguards including encrypted HTTPS communication, PDO prepared statements against SQL injection, hashed credentials, and role-based administrative access to ensure your information remains confidential.</p>
      </section>

      <section class="policy-section-card" id="third-parties">
        <h2 class="policy-section-title">4. Third-Party Disclosures</h2>
        <p class="policy-p">We do not sell, rent, or trade your personal information. Relevant data is only shared with authorized payment gateways (e.g. SSLCOMMERZ, bKash, Nagad) to safely process your tuition payments, or with recruitment partners when you request active placement assistance.</p>
      </section>

      <section class="policy-section-card" id="your-rights">
        <h2 class="policy-section-title">5. Your Privacy Rights</h2>
        <p class="policy-p">You have the right to request access to the personal data we hold about you, request corrections, or ask for deletion of your enquiry records by reaching out to our support department.</p>
      </section>

      <section class="policy-section-card" id="contact-privacy">
        <h2 class="policy-section-title">6. Contact Our Privacy Team</h2>
        <p class="policy-p">If you have any questions or concerns regarding this policy, please write to us at <a href="mailto:info@creativeitinstitute.com" style="color:#c92127;font-weight:600;">info@creativeitinstitute.com</a> or visit our Head Office at Momtaz Plaza, Dhanmondi, Dhaka.</p>
      </section>
    </article>
  </div>
</main>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
