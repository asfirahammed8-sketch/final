<?php
/**
 * DreamIT Admin Console - SEO Engine & Meta Tag Manager View (NO TABLES)
 */
require_once ADMIN_PATH . '/includes/auth-guard.php';

$pageTitle = 'SEO Engine';
$pageHeadline = 'Search Engine Optimization (SEO)';
$activeMenu = 'seo';

$db = Database::getConnection();

// Fetch all pages in page_seo
$stmt = $db->query("SELECT * FROM page_seo ORDER BY id ASC");
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once ADMIN_PATH . '/includes/header.php';
?>

<!-- Filter & Control Bar -->
<div class="control-filter-bar">
  <div class="search-input-wrapper">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="adminCardSearch" class="admin-search-input" placeholder="Search pages by title or slug..." />
  </div>
</div>

<!-- SEO Cards Grid (NO TABLES) -->
<div class="card-capsule-grid" style="grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));">
  <?php foreach ($pages as $p):
    $slug = $p['page_slug'];
    $title = !empty($p['seo_title']) ? $p['seo_title'] : $p['page_name'];
    $desc = !empty($p['meta_description']) ? $p['meta_description'] : 'Discover professional career-ready training at Creative IT Institute.';
    $url = ($slug === 'home') ? base_url('/') : base_url($slug . '/');
  ?>
    <article class="entity-card-capsule" data-id="<?= $p['id'] ?>">
      <div class="card-content-body">

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
          <h2 class="card-entity-title" style="font-size:16px;font-weight:700;color:var(--brand-primary);"><?= e($p['page_name']) ?></h2>
          <span class="category-tag-pill"><?= e($slug) ?></span>
        </div>

        <!-- Google SERP Snippet Preview Box -->
        <div class="serp-preview-box" style="margin-bottom:14px;padding:14px 16px;">
          <div class="serp-url-crumb"><?= e($url) ?></div>
          <div class="serp-title-preview" style="font-size:15px;"><?= e($title) ?></div>
          <div class="serp-desc-preview" style="font-size:12px;-webkit-line-clamp:2;display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden;">
            <?= e($desc) ?>
          </div>
        </div>

        <div class="card-meta-chips-row" style="margin-top:auto;">
          <span class="meta-chip">Robots: <?= e($p['robots'] ? substr($p['robots'], 0, 15) . '...' : 'index, follow') ?></span>
          <?php if (!empty($p['og_image'])): ?>
            <span class="meta-chip" style="color:var(--brand-emerald);">&#128247; OG Image Set</span>
          <?php endif; ?>
        </div>

      </div>

      <div class="card-action-footer">
        <div class="card-btn-group" style="width:100%;justify-content:space-between;">
          <button type="button" class="btn-card-action"
            data-seo='<?= htmlspecialchars(json_encode([
              'id'               => (int)$p['id'],
              'page_slug'        => $p['page_slug'] ?? '',
              'page_name'        => $p['page_name'] ?? '',
              'seo_title'        => $p['seo_title'] ?? '',
              'meta_description' => $p['meta_description'] ?? '',
              'og_title'         => $p['og_title'] ?? '',
              'og_image'         => $p['og_image'] ?? '',
              'robots'           => $p['robots'] ?? 'index, follow, max-image-preview:large'
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>'
            onclick="editSeo(this)">
            Edit SEO Tags
          </button>
          <a href="<?= e($url) ?>" target="_blank" class="btn-card-action">
            Inspect Page &rarr;
          </a>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<!-- SEO Edit Modal (Unified Accessible Popup System) -->
<div id="seoModal" class="admin-modal-backdrop" role="dialog" aria-modal="true">
  <div class="admin-modal-box modal-lg" style="max-width: 740px;" onclick="event.stopPropagation()">
    <div class="admin-modal-header">
      <h2 class="admin-modal-title">
        <span class="admin-modal-title-icon">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <span id="seoModalTitle">Edit SEO Meta</span>
      </h2>
      <button type="button" class="admin-modal-close" onclick="closeAdminModal('seoModal')" aria-label="Close modal">&times;</button>
    </div>

    <form id="seoForm" onsubmit="saveSeo(event)">
      <div class="admin-modal-body" style="max-height: 72vh; overflow-y: auto; padding: 22px;">
        <input type="hidden" name="id" id="seoId" />
        <input type="hidden" id="seoPageSlug" value="" />

        <!-- Interactive Real-Time Live Preview Switcher -->
        <div class="seo-preview-wrapper">
          <div class="seo-preview-header">
            <div style="font-size:12px;font-weight:700;color:var(--brand-primary);display:flex;align-items:center;gap:6px;">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <span>Interactive Live Preview</span>
            </div>
            <div class="seo-preview-tabs">
              <button type="button" class="seo-preview-tab-btn active" id="tabGoogleBtn" onclick="switchSeoPreviewTab('google')">
                Google Search Snippet
              </button>
              <button type="button" class="seo-preview-tab-btn" id="tabSocialBtn" onclick="switchSeoPreviewTab('social')">
                Social Share Card (OG)
              </button>
            </div>
          </div>

          <!-- 1. Google SERP Live Snippet -->
          <div id="serpPreviewBox" class="serp-preview-box">
            <div class="serp-url-crumb" id="modalSerpUrl">https://dreamit.edu.bd/</div>
            <div class="serp-title-preview" id="modalSerpTitle">Page SEO Title Appears Here</div>
            <div class="serp-desc-preview" id="modalSerpDesc">Page meta description snippet appears here...</div>
          </div>

          <!-- 2. Social OG Live Card -->
          <div id="socialOgPreviewBox" class="social-og-preview-card" style="display:none;">
            <div class="social-og-preview-img-box" id="modalOgImageBox">
              <img src="" id="modalOgImagePreview" alt="Social Card" style="display:none;">
              <span id="modalOgImagePlaceholder" style="font-size:12px;color:rgba(255,255,255,0.75);display:flex;align-items:center;gap:6px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                No OG Image uploaded yet (1200×630 recommended)
              </span>
            </div>
            <div class="social-og-preview-content">
              <div class="social-og-preview-domain" id="modalOgDomain"><?= parse_url(base_url(), PHP_URL_HOST) ?: 'localhost' ?></div>
              <div class="social-og-preview-title" id="modalOgTitle">OpenGraph Title</div>
              <div class="social-og-preview-desc" id="modalOgDesc">OpenGraph description snippet...</div>
            </div>
          </div>
        </div>

        <!-- 1. Google Meta Fields -->
        <div class="form-group">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <label class="form-label" for="seoTitle" style="margin-bottom:0;">SEO Meta Title <span class="required">*</span></label>
            <span id="seoTitleBadge" class="seo-char-badge optimal">0 / 60 chars</span>
          </div>
          <input type="text" name="seo_title" id="seoTitle" class="form-control" required oninput="updateSeoCountsAndPreview()" placeholder="e.g. Professional Web Development | Creative IT Institute" />
          <div style="font-size:11px;color:var(--admin-text-muted);margin-top:4px;">Appears as the primary clickable headline in search engine result pages (SERPs).</div>
        </div>

        <div class="form-group">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <label class="form-label" for="seoDesc" style="margin-bottom:0;">Meta Description <span class="required">*</span></label>
            <span id="seoDescBadge" class="seo-char-badge optimal">0 / 160 chars</span>
          </div>
          <textarea name="meta_description" id="seoDesc" class="form-control" rows="3" required oninput="updateSeoCountsAndPreview()" placeholder="Compelling 150-160 character description summarizing this page..."></textarea>
          <div style="font-size:11px;color:var(--admin-text-muted);margin-top:4px;">Displayed under the title snippet on search results to drive clicks and conversions.</div>
        </div>

        <!-- 2. Social Media (OpenGraph) Fields -->
        <div style="border-top:1px solid var(--admin-border);padding-top:16px;margin-top:16px;">
          <h3 style="font-size:14px;font-weight:700;color:var(--brand-primary);margin-bottom:12px;">Social Media (OpenGraph) Sharing</h3>

          <div class="form-group">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
              <label class="form-label" for="seoOgTitle" style="margin-bottom:0;">OpenGraph (OG) Title</label>
              <span style="font-size:11px;color:var(--admin-text-muted);">Defaults to SEO Title if empty</span>
            </div>
            <input type="text" name="og_title" id="seoOgTitle" class="form-control" placeholder="Leave blank to use SEO Title" oninput="updateSeoCountsAndPreview()" />
          </div>

          <div class="form-group">
            <label class="form-label">Social Share (OG) Image</label>
            <div class="upload-spec-badge">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
              <span><strong>Recommended:</strong> 1200 &times; 630 px (1.91:1) &bull; Formats: WebP, JPG, PNG &bull; Max: 2 MB</span>
            </div>

            <div class="upload-dropzone" id="seoOgDropzone" style="padding:16px;">
              <input type="file" id="seoOgFileInput" accept="image/webp,image/jpeg,image/png,image/gif" style="display:none;">
              <div class="upload-dropzone-icon" style="width:36px;height:36px;margin-bottom:4px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              </div>
              <div class="upload-dropzone-title" style="font-size:12.5px;">Drop social share card image, or <span>browse file</span></div>
              <div class="upload-dropzone-hint" style="font-size:11px;">Image rendered when link is shared on Facebook, WhatsApp, LinkedIn & Twitter</div>
            </div>

            <div class="upload-preview-card" id="seoOgPreviewCard" style="display:none;margin-top:8px;">
              <img src="" id="seoOgPreviewImg" class="upload-preview-thumb" alt="OG Preview">
              <div class="upload-preview-meta">
                <div class="upload-preview-status">&#10003; Social Card Ready</div>
                <div class="upload-preview-url" id="seoOgPreviewUrlText"></div>
              </div>
              <button type="button" class="upload-preview-remove">&times; Remove</button>
            </div>

            <div style="margin-top:8px;">
              <input type="url" name="og_image" id="seoOgImage" class="form-control" placeholder="Or enter direct image URL (https://...)" oninput="updateSeoOgPreview(this.value)" />
            </div>
          </div>
        </div>

        <!-- 3. Web Crawler Directives -->
        <div style="border-top:1px solid var(--admin-border);padding-top:16px;margin-top:16px;">
          <div class="form-group" style="margin-bottom:0;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
              <label class="form-label" for="seoRobots" style="margin-bottom:0;">Robots Meta Directive</label>
              <span style="font-size:11px;color:var(--admin-text-muted);">Instructs Googlebot and web crawlers</span>
            </div>
            <input type="text" name="robots" id="seoRobots" class="form-control" value="index, follow, max-image-preview:large" oninput="highlightRobotsPreset(this.value)" />

            <div class="robots-preset-row">
              <button type="button" class="robots-preset-pill active" id="presetIndexFollow" onclick="setRobotsPreset('index, follow, max-image-preview:large')">
                Standard (Index &amp; Follow)
              </button>
              <button type="button" class="robots-preset-pill" id="presetNoindexFollow" onclick="setRobotsPreset('noindex, follow')">
                No-Index (Follow Links)
              </button>
              <button type="button" class="robots-preset-pill" id="presetNoindexNofollow" onclick="setRobotsPreset('noindex, nofollow')">
                Private / Hidden (No-Index, No-Follow)
              </button>
            </div>
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" class="btn-admin-secondary" onclick="closeAdminModal('seoModal')">Cancel</button>
        <button type="submit" class="btn-admin-primary" id="btnSaveSeo">Save SEO Settings</button>
      </div>
    </form>
  </div>
</div>

<script>
const BASE_PAGE_URL = '<?= base_url() ?>';

document.addEventListener('DOMContentLoaded', () => {
  setupAdminDropzone({
    dropzoneId: 'seoOgDropzone',
    fileInputId: 'seoOgFileInput',
    targetUrlInputId: 'seoOgImage',
    previewCardId: 'seoOgPreviewCard',
    previewThumbId: 'seoOgPreviewImg',
    urlTextId: 'seoOgPreviewUrlText',
    folder: 'general',
    onSuccess: (url) => {
      updateSeoOgPreview(url);
    },
    onRemoved: () => {
      updateSeoOgPreview('');
    }
  });
});

function switchSeoPreviewTab(tab) {
  const gTab = document.getElementById('tabGoogleBtn');
  const sTab = document.getElementById('tabSocialBtn');
  const gBox = document.getElementById('serpPreviewBox');
  const sBox = document.getElementById('socialOgPreviewBox');

  if (tab === 'google') {
    gTab.classList.add('active');
    sTab.classList.remove('active');
    gBox.style.display = 'block';
    sBox.style.display = 'none';
  } else {
    sTab.classList.add('active');
    gTab.classList.remove('active');
    gBox.style.display = 'none';
    sBox.style.display = 'block';
  }
}

function updateSeoOgPreview(url) {
  url = (url || '').trim();
  const card = document.getElementById('seoOgPreviewCard');
  const img  = document.getElementById('seoOgPreviewImg');
  const txt  = document.getElementById('seoOgPreviewUrlText');

  const modalImg = document.getElementById('modalOgImagePreview');
  const modalPlaceholder = document.getElementById('modalOgImagePlaceholder');

  if (url) {
    img.src = url;
    txt.textContent = url;
    card.style.display = 'flex';

    modalImg.src = url;
    modalImg.style.display = 'block';
    modalPlaceholder.style.display = 'none';
  } else {
    card.style.display = 'none';
    modalImg.src = '';
    modalImg.style.display = 'none';
    modalPlaceholder.style.display = 'flex';
  }
  updateSeoCountsAndPreview();
}

function updateSeoCountsAndPreview() {
  const tVal = (document.getElementById('seoTitle')?.value || '').trim();
  const dVal = (document.getElementById('seoDesc')?.value || '').trim();
  const ogTVal = (document.getElementById('seoOgTitle')?.value || '').trim();

  const tBadge = document.getElementById('seoTitleBadge');
  const dBadge = document.getElementById('seoDescBadge');

  // Title counter badge
  if (tBadge) {
    const len = tVal.length;
    if (len === 0) {
      tBadge.textContent = '0 / 60 chars';
      tBadge.className = 'seo-char-badge short';
    } else if (len < 40) {
      tBadge.textContent = `${len} / 60 chars • Short`;
      tBadge.className = 'seo-char-badge short';
    } else if (len <= 60) {
      tBadge.textContent = `${len} / 60 chars • Optimal ✓`;
      tBadge.className = 'seo-char-badge optimal';
    } else {
      tBadge.textContent = `${len} / 60 chars • Truncated (>60)`;
      tBadge.className = 'seo-char-badge warning';
    }
  }

  // Description counter badge
  if (dBadge) {
    const len = dVal.length;
    if (len === 0) {
      dBadge.textContent = '0 / 160 chars';
      dBadge.className = 'seo-char-badge short';
    } else if (len < 120) {
      dBadge.textContent = `${len} / 160 chars • Short`;
      dBadge.className = 'seo-char-badge short';
    } else if (len <= 160) {
      dBadge.textContent = `${len} / 160 chars • Optimal ✓`;
      dBadge.className = 'seo-char-badge optimal';
    } else {
      dBadge.textContent = `${len} / 160 chars • Truncated (>160)`;
      dBadge.className = 'seo-char-badge warning';
    }
  }

  // Live SERP Preview box
  const serpTitle = document.getElementById('modalSerpTitle');
  const serpDesc = document.getElementById('modalSerpDesc');
  if (serpTitle) {
    serpTitle.textContent = tVal || 'Page SEO Title Appears Here';
  }
  if (serpDesc) {
    serpDesc.textContent = dVal || 'Page meta description snippet appears here...';
  }

  // Live Social Card Preview box
  const ogTitle = document.getElementById('modalOgTitle');
  const ogDesc = document.getElementById('modalOgDesc');
  if (ogTitle) {
    ogTitle.textContent = ogTVal || tVal || 'OpenGraph Title Appears Here';
  }
  if (ogDesc) {
    ogDesc.textContent = dVal || 'OpenGraph description snippet appears here...';
  }
}

function setRobotsPreset(directive) {
  const robotsInput = document.getElementById('seoRobots');
  if (robotsInput) {
    robotsInput.value = directive;
    highlightRobotsPreset(directive);
  }
}

function highlightRobotsPreset(val) {
  val = (val || '').trim().toLowerCase();
  const p1 = document.getElementById('presetIndexFollow');
  const p2 = document.getElementById('presetNoindexFollow');
  const p3 = document.getElementById('presetNoindexNofollow');

  [p1, p2, p3].forEach(el => el?.classList.remove('active'));

  if (val.includes('noindex') && val.includes('nofollow')) {
    p3?.classList.add('active');
  } else if (val.includes('noindex')) {
    p2?.classList.add('active');
  } else if (val.includes('index') && val.includes('follow')) {
    p1?.classList.add('active');
  }
}

function editSeo(btnOrData) {
  const p = getAdminEntityData(btnOrData, 'seo') || btnOrData;
  if (!p) return;

  const slug = p.page_slug || '';
  const pageUrl = (slug === 'home') ? BASE_PAGE_URL : (BASE_PAGE_URL + slug + '/');

  document.getElementById('seoId').value = p.id;
  document.getElementById('seoPageSlug').value = slug;
  document.getElementById('modalSerpUrl').textContent = pageUrl;

  document.getElementById('seoTitle').value = p.seo_title || p.page_name || '';
  document.getElementById('seoDesc').value = p.meta_description || '';
  document.getElementById('seoOgTitle').value = p.og_title || '';
  document.getElementById('seoOgImage').value = p.og_image || '';

  const robotsVal = p.robots || 'index, follow, max-image-preview:large';
  document.getElementById('seoRobots').value = robotsVal;
  highlightRobotsPreset(robotsVal);

  updateSeoOgPreview(p.og_image);
  document.getElementById('seoModalTitle').textContent = 'Edit SEO: ' + (p.page_name || 'Page');

  // Default to Google snippet tab
  switchSeoPreviewTab('google');
  updateSeoCountsAndPreview();

  openAdminModal('seoModal');
}

async function saveSeo(e) {
  e.preventDefault();
  const form = document.getElementById('seoForm');
  const btn = document.getElementById('btnSaveSeo');
  const titleVal = document.getElementById('seoTitle').value.trim();
  const descVal = document.getElementById('seoDesc').value.trim();

  if (!titleVal) {
    showAdminToast('error', 'SEO title cannot be empty.');
    return;
  }
  if (!descVal) {
    showAdminToast('error', 'Meta description cannot be empty.');
    return;
  }

  const payload = Object.fromEntries(new FormData(form));

  try {
    await executeAdminApi('<?= admin_url("api/seo/save") ?>', payload, btn);
    showAdminToast('success', 'SEO metadata saved successfully!');
    closeAdminModal('seoModal');
    setTimeout(() => location.reload(), 600);
  } catch (err) {}
}
</script>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
