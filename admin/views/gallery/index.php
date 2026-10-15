<?php
/**
 * DreamIT Admin Console - Campus Gallery Management View
 * Production-ready CRUD interface with real image uploads and category filtering
 */

require_once ADMIN_PATH . '/includes/auth-guard.php';

$pageTitle = 'Campus Gallery';
$pageHeadline = 'Campus Life & Lab Gallery';
$activeMenu = 'gallery';

$db = Database::getConnection();

// Fetch all gallery items
$items = $db->query("SELECT * FROM gallery_items ORDER BY sort_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Metrics
$totalItems  = count($items);
$activeItems = count(array_filter($items, fn($i) => (int)$i['is_active'] === 1));
$videoItems  = count(array_filter($items, fn($i) => ($i['media_type'] ?? '') === 'video'));

// Frontend standard category labels including campus
$categories = [
    'campus'       => 'Campus Life & Reception',
    'labs'         => 'Campus & Labs',
    'workshops'    => 'Seminars & Workshops',
    'achievements' => 'Student Success',
    'classroom'    => 'Classroom Activities',
    'convocation'  => 'Convocation & Gala',
    'hackathons'   => 'Hackathons',
    'clubs'        => 'Club Activities',
];

require_once ADMIN_PATH . '/includes/header.php';
?>

<!-- Metric Stats Row -->
<div class="metric-boxes-grid">
  <div class="metric-stat-box box-cyan">
    <div class="stat-info-col">
      <span class="stat-label">Total Gallery Photos</span>
      <span class="stat-value"><?= $totalItems ?></span>
      <span class="stat-meta">Across All Campus Facilities</span>
    </div>
    <div class="stat-icon-wrapper">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
    </div>
  </div>

  <div class="metric-stat-box box-emerald">
    <div class="stat-info-col">
      <span class="stat-label">Active / Live Visible</span>
      <span class="stat-value"><?= $activeItems ?></span>
      <span class="stat-meta">&#10003; Displayed on Website</span>
    </div>
    <div class="stat-icon-wrapper">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
    </div>
  </div>

  <div class="metric-stat-box box-purple">
    <div class="stat-info-col">
      <span class="stat-label">Event Categories</span>
      <span class="stat-value"><?= count($categories) ?></span>
      <span class="stat-meta">Labs, Workshops &amp; Gala</span>
    </div>
    <div class="stat-icon-wrapper">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    </div>
  </div>

  <div class="metric-stat-box box-amber">
    <div class="stat-info-col">
      <span class="stat-label">Video Spotlights</span>
      <span class="stat-value"><?= $videoItems ?></span>
      <span class="stat-meta">Interactive Campus Clips</span>
    </div>
    <div class="stat-icon-wrapper">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
    </div>
  </div>
</div>

<!-- Category Toolbar & Action Row -->
<div class="filter-action-toolbar">
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <button type="button" class="btn-filter-pill active" onclick="filterGallery('all', this)">All (<?= $totalItems ?>)</button>
    <?php foreach ($categories as $catKey => $catName):
      $countCat = count(array_filter($items, fn($i) => ($i['category'] ?? '') === $catKey));
    ?>
      <button type="button" class="btn-filter-pill" onclick="filterGallery('<?= e($catKey) ?>', this)">
        <?= e($catName) ?> (<?= $countCat ?>)
      </button>
    <?php endforeach; ?>
  </div>

  <button type="button" class="topbar-btn btn-primary-action" onclick="openNewGalleryModal()">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="12" y2="12"/></svg>
    <span>Add Campus Photo</span>
  </button>
</div>

<!-- Gallery Grid (Using standard card-capsule-grid) -->
<div class="card-capsule-grid" id="galleryGrid">
  <?php if (empty($items)): ?>
    <div class="empty-state-box">
      <div class="empty-state-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
      </div>
      <h3 class="empty-state-title">No Campus Gallery Photos Yet</h3>
      <p class="empty-state-desc">Upload high-resolution photographs of your campus labs, classrooms, workshops, and graduation ceremonies.</p>
      <button type="button" class="topbar-btn btn-primary-action" onclick="openNewGalleryModal()">+ Upload First Photo</button>
    </div>
  <?php else: ?>
    <?php foreach ($items as $item):
      $imgUrl = !empty($item['image_url']) ? $item['image_url'] : 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=600&q=80';
      $catLabel = $categories[$item['category']] ?? ucfirst($item['category']);
      $safeJson = htmlspecialchars(json_encode([
          'id'         => (int)$item['id'],
          'category'   => $item['category'] ?? 'labs',
          'title'      => $item['title'] ?? '',
          'alt_text'   => $item['alt_text'] ?? '',
          'image_url'  => $item['image_url'] ?? '',
          'media_type' => $item['media_type'] ?? 'image',
          'video_url'  => $item['video_url'] ?? '',
          'color'      => $item['color'] ?? '#1e3a5f',
          'is_active'  => (int)$item['is_active'],
          'sort_order' => (int)$item['sort_order'],
      ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
    ?>
      <article class="entity-card-capsule gallery-card" data-category="<?= e($item['category']) ?>" id="galCard_<?= (int)$item['id'] ?>">

        <!-- Photo Thumbnail / Preview with Category Badge -->
        <div class="card-cover-media" style="position:relative;height:210px;background:#0B2046;overflow:hidden;">
          <img src="<?= e($imgUrl) ?>" alt="<?= e($item['alt_text'] ?: $item['title']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;display:block;">

          <div class="cover-badge-left" style="display:flex;gap:6px;">
            <span class="category-tag-pill">
              <?= e($catLabel) ?>
            </span>
            <?php if ($item['media_type'] === 'video'): ?>
              <span class="status-pill status-unread">
                &#9658; Video
              </span>
            <?php endif; ?>
          </div>

          <div class="cover-badge-right">
            <span class="meta-chip" style="background:rgba(15,23,42,0.7);color:#fff;border-color:transparent;font-weight:600;">
              #<?= (int)$item['sort_order'] ?>
            </span>
          </div>
        </div>

        <!-- Card Body -->
        <div class="card-content-body" style="padding:16px;">
          <h2 class="card-entity-title" style="font-size:15px;font-weight:700;color:var(--brand-primary);margin-bottom:6px;line-height:1.35;">
            <?= e($item['title']) ?>
          </h2>
          <p style="font-size:12px;color:var(--admin-text-muted);margin-bottom:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?= e($item['alt_text'] ?: 'Campus infrastructure photo') ?>
          </p>
        </div>

        <div class="card-action-footer">
          <!-- Status Toggle -->
          <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;font-weight:600;color:<?= (int)$item['is_active'] ? 'var(--brand-emerald)' : 'var(--admin-text-muted)' ?>;">
            <input type="checkbox" <?= (int)$item['is_active'] ? 'checked' : '' ?> onchange="toggleGalleryStatus(<?= (int)$item['id'] ?>, this)" style="accent-color:var(--brand-accent);">
            <span class="status-text"><?= (int)$item['is_active'] ? 'Visible' : 'Hidden' ?></span>
          </label>

          <!-- Action Buttons -->
          <div class="card-btn-group">
            <button type="button" class="btn-card-action" data-gallery='<?= $safeJson ?>' onclick="editGallery(this)" title="Edit Photo Details">
              Edit
            </button>
            <button type="button" class="btn-card-action btn-card-delete" onclick="deleteGallery(<?= (int)$item['id'] ?>)" title="Delete Photo">
              Delete
            </button>
          </div>
        </div>

      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- ======================================================================
     CAMPUS GALLERY MODAL POPUP (#galleryModal)
     ====================================================================== -->
<div class="admin-modal-backdrop" id="galleryModal" role="dialog" aria-modal="true" aria-labelledby="galleryModalTitle">
  <div class="admin-modal-box modal-lg" onclick="event.stopPropagation()">

    <div class="admin-modal-header">
      <div style="display:flex;align-items:center;gap:12px;">
        <div class="modal-header-icon">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
        </div>
        <div>
          <h2 id="galleryModalTitle" class="admin-modal-title">Add Campus Photo</h2>
          <p class="admin-modal-subtitle">Upload high-res campus life, lab facility, or workshop moment</p>
        </div>
      </div>
      <button type="button" class="admin-modal-close" onclick="closeAdminModal('galleryModal')">&times;</button>
    </div>

    <form id="galleryForm" onsubmit="saveGallery(event)">
      <input type="hidden" name="id" id="galId" value="">
      <input type="hidden" name="csrf_token" value="<?= admin_csrf_token() ?>">

      <div class="admin-modal-body" style="max-height: 75vh; overflow-y: auto; padding: 22px;">

        <!-- Category & Media Type -->
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Category Event <span class="required-star">*</span></label>
            <select name="category" id="galCategory" class="form-control" required>
              <?php foreach ($categories as $catKey => $catLabel): ?>
                <option value="<?= e($catKey) ?>"><?= e($catLabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Media Type</label>
            <select name="media_type" id="galMediaType" class="form-control" onchange="handleMediaTypeChange(this.value)">
              <option value="image">Photograph (Image)</option>
              <option value="video">Interactive Video Clip</option>
            </select>
          </div>
        </div>

        <!-- Title & Alt Text -->
        <div class="form-group">
          <label class="form-label">Photo Headline / Title <span class="required-star">*</span></label>
          <input type="text" name="title" id="galTitle" class="form-control" placeholder="e.g., Computer Graphics & 3D Animation Lab" required>
        </div>

        <div class="form-group">
          <label class="form-label">Alt Text (Accessibility &amp; SEO)</label>
          <input type="text" name="alt_text" id="galAltText" class="form-control" placeholder="Descriptive label for screen readers & Google image search">
        </div>

        <!-- Real Image Upload Section -->
        <div class="form-group" id="galImageSection">
          <label class="form-label">Campus Photograph <span class="required-star">*</span></label>

          <!-- Size & Format Specification Badge -->
          <div class="upload-spec-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span><strong>Recommended:</strong> 1200 &times; 800 px (3:2 Aspect Ratio) &bull; Formats: WebP, JPG, PNG &bull; Max Size: 5 MB</span>
          </div>

          <!-- Dropzone -->
          <div class="upload-dropzone" id="galDropzone">
            <input type="file" id="galFileInput" accept="image/webp,image/jpeg,image/png,image/gif" style="display:none;">
            <div class="upload-dropzone-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div class="upload-dropzone-title">Drag &amp; drop photo here, or <span>browse file</span></div>
            <div class="upload-dropzone-hint">High quality photos increase student enrollment trust</div>
          </div>

          <!-- Live Preview Card -->
          <div class="upload-preview-card" id="galPreviewCard" style="display:none;">
            <img src="" id="galPreviewThumb" class="upload-preview-thumb" alt="Preview">
            <div class="upload-preview-meta">
              <div class="upload-preview-status">&#10003; Image Ready</div>
              <div class="upload-preview-url" id="galPreviewUrlText"></div>
            </div>
            <button type="button" class="upload-preview-remove" title="Remove photo">&times; Remove</button>
          </div>

          <!-- Fallback or Direct URL input -->
          <div style="margin-top: 10px;">
            <input type="text" name="image_url" id="galImageUrl" class="form-control" placeholder="Or paste absolute image URL (https://...)" oninput="updateGalPreviewFromUrl(this.value)">
          </div>
        </div>

        <!-- Video URL Section (if video selected) -->
        <div class="form-group" id="galVideoSection" style="display:none;">
          <label class="form-label">Video Embed URL (YouTube or Vimeo)</label>
          <input type="url" name="video_url" id="galVideoUrl" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
          <span style="font-size:11.5px;color:var(--admin-text-muted);">Paste the YouTube video link or direct MP4 link.</span>
        </div>

        <!-- Card Accent Color, Sort Order, Active Status -->
        <div class="form-grid-3">
          <div class="form-group">
            <label class="form-label">Card Accent Color</label>
            <input type="color" name="color" id="galColor" class="form-control" value="#1e3a5f" style="height:42px;padding:4px;cursor:pointer;">
          </div>

          <div class="form-group">
            <label class="form-label">Display Sort Order</label>
            <input type="number" name="sort_order" id="galSortOrder" class="form-control" value="0" min="0">
          </div>

          <div class="form-group">
            <label class="form-label">Publication Status</label>
            <select name="is_active" id="galIsActive" class="form-control">
              <option value="1">Active / Live Visible</option>
              <option value="0">Draft / Hidden</option>
            </select>
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" class="btn-admin-secondary" onclick="closeAdminModal('galleryModal')">Cancel</button>
        <button type="submit" id="btnSaveGallery" class="topbar-btn btn-primary-action">Save Photo</button>
      </div>
    </form>

  </div>
</div>

<script>
// Filter gallery items by category
function filterGallery(category, btn) {
  document.querySelectorAll('.btn-filter-pill').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');

  const cards = document.querySelectorAll('.gallery-card');
  cards.forEach(card => {
    if (category === 'all' || card.dataset.category === category) {
      card.style.display = '';
    } else {
      card.style.display = 'none';
    }
  });
}

// Media type switch image vs video
function handleMediaTypeChange(type) {
  const imgSec = document.getElementById('galImageSection');
  const vidSec = document.getElementById('galVideoSection');
  if (type === 'video') {
    vidSec.style.display = 'block';
  } else {
    vidSec.style.display = 'none';
  }
}

// Initialize Dropzone on page ready
document.addEventListener('DOMContentLoaded', () => {
  setupAdminDropzone({
    dropzoneEl: document.getElementById('galDropzone'),
    fileInputEl: document.getElementById('galFileInput'),
    urlInputEl: document.getElementById('galImageUrl'),
    previewCardEl: document.getElementById('galPreviewCard'),
    previewThumbEl: document.getElementById('galPreviewThumb'),
    previewUrlEl: document.getElementById('galPreviewUrlText'),
    folder: 'gallery'
  });
});

function updateGalPreviewFromUrl(url) {
  url = (url || '').trim();
  const card = document.getElementById('galPreviewCard');
  const thumb = document.getElementById('galPreviewThumb');
  const txt = document.getElementById('galPreviewUrlText');
  if (url) {
    thumb.src = url;
    txt.textContent = url;
    card.style.display = 'flex';
  } else {
    card.style.display = 'none';
  }
}

function openNewGalleryModal() {
  document.getElementById('galleryForm').reset();
  document.getElementById('galId').value = '';
  document.getElementById('galSortOrder').value = '0';
  document.getElementById('galColor').value = '#1e3a5f';
  document.getElementById('galIsActive').value = '1';
  document.getElementById('galleryModalTitle').textContent = 'Add Campus Photo';
  document.getElementById('galPreviewCard').style.display = 'none';
  handleMediaTypeChange('image');
  openAdminModal('galleryModal');
}

function editGallery(btnOrData) {
  const d = getAdminEntityData(btnOrData, 'gallery');
  if (!d) return;

  document.getElementById('galId').value = d.id || '';
  document.getElementById('galCategory').value = d.category || 'labs';
  document.getElementById('galMediaType').value = d.media_type || 'image';
  document.getElementById('galTitle').value = d.title || '';
  document.getElementById('galAltText').value = d.alt_text || '';
  document.getElementById('galImageUrl').value = d.image_url || '';
  document.getElementById('galVideoUrl').value = d.video_url || '';
  document.getElementById('galColor').value = d.color || '#1e3a5f';
  document.getElementById('galSortOrder').value = d.sort_order ?? 0;
  document.getElementById('galIsActive').value = d.is_active ?? 1;

  handleMediaTypeChange(d.media_type || 'image');
  updateGalPreviewFromUrl(d.image_url);

  document.getElementById('galleryModalTitle').textContent = 'Edit Campus Photo';
  openAdminModal('galleryModal');
}

async function saveGallery(e) {
  e.preventDefault();
  const form = document.getElementById('galleryForm');
  const btn = document.getElementById('btnSaveGallery');
  const formData = new FormData(form);

  const origText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<span style="display:inline-block;animation:spin 0.8s linear infinite;">&#9696;</span> Saving...`;

  try {
    const res = await fetch('<?= admin_url("api/gallery/save") ?>', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': '<?= admin_csrf_token() ?>'
      },
      body: formData
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Failed to save campus photo.');
    }

    showAdminToast('success', data.message);
    closeAdminModal('galleryModal');
    setTimeout(() => window.location.reload(), 600);
  } catch (err) {
    showAdminToast('error', err.message);
  } finally {
    btn.disabled = false;
    btn.innerHTML = origText;
  }
}

async function toggleGalleryStatus(id, checkbox) {
  try {
    const res = await fetch('<?= admin_url("api/gallery/toggle") ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': '<?= admin_csrf_token() ?>'
      },
      body: `id=${id}&csrf_token=<?= admin_csrf_token() ?>`
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Status update failed.');
    }
    const label = checkbox.closest('label');
    const textEl = label.querySelector('.status-text');
    if (data.is_active) {
      textEl.textContent = 'Visible';
      label.style.color = 'var(--brand-emerald)';
    } else {
      textEl.textContent = 'Hidden';
      label.style.color = 'var(--admin-text-muted)';
    }
    showAdminToast('success', data.message);
  } catch (err) {
    checkbox.checked = !checkbox.checked;
    showAdminToast('error', err.message);
  }
}

async function deleteGallery(id) {
  const confirmed = await confirmAdminAction({
    title: 'Delete Campus Photo?',
    message: 'Are you sure you want to delete this campus photo? It will no longer appear in the campus gallery.',
    confirmText: 'Yes, Delete',
    isDanger: true
  });
  if (!confirmed) return;

  try {
    const res = await fetch('<?= admin_url("api/gallery/delete") ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': '<?= admin_csrf_token() ?>'
      },
      body: `id=${id}&csrf_token=<?= admin_csrf_token() ?>`
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Deletion failed.');
    }
    showAdminToast('success', data.message);
    const card = document.getElementById(`galCard_${id}`);
    if (card) {
      card.style.opacity = '0';
      card.style.transform = 'scale(0.9)';
      setTimeout(() => card.remove(), 250);
    }
  } catch (err) {
    showAdminToast('error', err.message);
  }
}
</script>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
