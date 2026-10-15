<?php
$pageCss = 'gallery.css';

/**
 * Campus Gallery Page
 * Creative IT Institute - DreamIT Platform
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$siteBrand = site_brand();
$pageTitle = "Campus Gallery & Lab Life | {$siteBrand}";
$metaDesc  = "Explore {$siteBrand}'s campus life — modern computer labs, interactive lecture halls, workshops, seminars, and student hackathons in Bangladesh.";
$activeNav = 'gallery';
$canonicalUrl = canonical_url('gallery/');

$db = Database::getConnection();
$stmt = $db->query("SELECT * FROM gallery_items WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
$galleryItems = $stmt->fetchAll();

$categories = [
    'all'          => 'All Moments',
    'labs'         => 'Campus & Labs',
    'workshops'    => 'Seminars & Workshops',
    'achievements' => 'Student Success',
    'classroom'    => 'Classroom Activities',
    'convocation'  => 'Convocation & Gala',
    'hackathons'   => 'Hackathons',
    'clubs'        => 'Club Activities',
];

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="container" style="max-width: 1240px; margin: auto; padding: 0 20px;">
  <header class="gallery-hero">
    <h1 class="gallery-page-title">Campus Gallery</h1>
    <p class="gallery-page-subtitle">
      Explore our modern lab infrastructure, interactive workshops, student project showcases, convocation events, and vibrant learning atmosphere at Creative IT Institute.
    </p>
  </header>

  <div class="gallery-filter-toolbar">
    <div class="filter-tabs-group" role="tablist" aria-label="Gallery category filters">
      <?php $first = true; foreach ($categories as $slug => $label): ?>
        <button class="filter-btn <?= $first ? 'active' : '' ?>" data-filter="<?= e($slug) ?>" role="tab" aria-selected="<?= $first ? 'true' : 'false' ?>">
          <?= e($label) ?>
        </button>
      <?php $first = false; endforeach; ?>
    </div>
    <div class="gallery-counter-badge" aria-live="polite">
      Showing <strong id="counterCurrent"><?= min(6, count($galleryItems)) ?></strong> of <strong id="counterTotal"><?= count($galleryItems) ?></strong> Photos
    </div>
  </div>

  <section class="gallery-photos-grid" id="galleryGrid" aria-label="Gallery Photos">
    <?php foreach ($galleryItems as $item):
      // Sanitize image URL from DB before rendering — prevents stored XSS
      $rawImg  = trim($item['image_url'] ?? '');
      $imgUrl  = '';
      if ($rawImg !== '') {
          $san = sanitize_image_url($rawImg);
          if ($san) $imgUrl = $san;
      }
      // Safe absolute fallback
      if (!$imgUrl) {
          $imgUrl = 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=600&q=80';
      }
      $altText = !empty($item['alt_text']) ? $item['alt_text'] : (!empty($item['title']) ? $item['title'] : "{$siteBrand} Campus & Lab Life");
    ?>
      <article class="gallery-item" data-category="<?= e($item['category']) ?>" data-src="<?= e($imgUrl) ?>" data-alt="<?= e($altText) ?>" style="cursor:pointer;border-radius:10px;overflow:hidden;position:relative;">
        <img class="gallery-item-img" src="<?= e($imgUrl) ?>" alt="<?= e($altText) ?>" loading="lazy" style="width:100%;height:260px;object-fit:cover;display:block;transition:transform 0.3s;" />
        <div class="gallery-item-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.3);opacity:0;transition:opacity 0.2s;display:flex;align-items:center;justify-content:center;" aria-hidden="true">
          <div class="gallery-zoom-icon" style="width:44px;height:44px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#c92127;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>


  <div class="load-more-container">
    <button class="see-more-btn" id="galleryLoadMore" aria-label="Load more photos">
      <span>See More</span>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
  </div>
</main>

<!-- Lightbox Modal -->
<div class="lightbox-modal" id="lightboxModal" role="dialog" aria-modal="true" aria-label="Image Preview">
  <div class="lightbox-wrapper">
    <button class="lightbox-close-btn" id="lightboxClose" aria-label="Close image">&times;</button>
    <button class="lightbox-nav-btn lightbox-prev-btn" id="lightboxPrev" aria-label="Previous image">&#10094;</button>
    <div class="lightbox-img-box">
      <img id="lightboxImg" src="" alt="Gallery Image" />
    </div>
    <button class="lightbox-nav-btn lightbox-next-btn" id="lightboxNext" aria-label="Next image">&#10095;</button>
    <div class="lightbox-caption-bar">
      <div class="lightbox-counter" id="lightboxCounter">1 / <?= count($galleryItems) ?></div>
    </div>
  </div>
</div>

<style>
.gallery-item:hover .gallery-item-overlay { opacity: 1 !important; }
.gallery-item:hover .gallery-item-img { transform: scale(1.04); }
</style>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
