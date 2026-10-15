<?php
$pageCss = 'faq.css';

/**
 * FAQ Page
 * Creative IT Institute - DreamIT Platform
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$siteBrand = site_brand();
$pageTitle = "FAQ — Frequently Asked Questions | {$siteBrand}";
$metaDesc  = "Answers to common questions about {$siteBrand} courses, admissions, lab facilities, batch schedules, certifications, and career placement support.";
$activeNav = 'faq';
$canonicalUrl = canonical_url('faq/');

$db = Database::getConnection();
$stmt = $db->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
$faqs = $stmt->fetchAll();

// Dynamic FAQPage JSON-LD Schema
$faqEntities = [];
foreach ($faqs as $faq) {
    $faqEntities[] = [
        "@type" => "Question",
        "name" => $faq['question'],
        "acceptedAnswer" => [
            "@type" => "Answer",
            "text" => strip_tags($faq['answer'])
        ]
    ];
}
$pageSchema = [
    "@context" => "https://schema.org",
    "@type" => "FAQPage",
    "mainEntity" => $faqEntities
];

// Split into two columns for the dual-column layout
$leftFaqs  = [];
$rightFaqs = [];
foreach ($faqs as $i => $f) {
    if ($i % 2 === 0) {
        $leftFaqs[] = $f;
    } else {
        $rightFaqs[] = $f;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="container" style="max-width: 1240px; margin: auto; padding: 0 20px;">
  <section class="faq-hero-section">
    <h1 class="faq-page-title">Frequently Asked Questions</h1>
    <p class="faq-page-subtitle">
      Find quick answers to common queries regarding our professional courses, admission procedures, computer lab facilities, batch schedules, certifications, and lifetime career support.
    </p>
  </section>

  <section class="faq-grid-container" aria-label="FAQ List">
    <!-- LEFT COLUMN -->
    <div class="faq-column">
      <?php foreach ($leftFaqs as $idx => $faq):
        $isOpen = ($idx === 0);
      ?>
        <article class="faq-item <?= $isOpen ? 'active' : '' ?>">
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
</main>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
