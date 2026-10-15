<?php
/**
 * DreamIT Admin Console - Site Settings Engine View
 */
require_once ADMIN_PATH . '/includes/auth-guard.php';

$pageTitle = 'Site Settings';
$pageHeadline = 'Site Identity & Global Settings';
$activeMenu = 'settings';

$db = Database::getConnection();

// Fetch all settings as key-value map
$settings = $db->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

function getSetting(array $map, string $key, string $default = ''): string {
    return $map[$key] ?? $default;
}

require_once ADMIN_PATH . '/includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">

  <form id="settingsForm" onsubmit="saveSettings(event)">

    <!-- 1. General Branding -->
    <div class="form-card-box">
      <h2 style="font-size:18px;font-weight:700;color:var(--brand-primary);margin-bottom:6px;">Branding &amp; Identity</h2>
      <p style="font-size:13px;color:var(--admin-text-muted);margin-bottom:20px;">Control the primary institution name, slogan, logo assets, and top navigation action button.</p>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Brand Name</label>
          <input type="text" name="site_name" class="form-control" value="<?= e(getSetting($settings, 'site_name', 'Creative IT Institute - DreamIT')) ?>" />
        </div>

        <div class="form-group">
          <label class="form-label">Institution Tagline</label>
          <input type="text" name="site_tagline" class="form-control" value="<?= e(getSetting($settings, 'site_tagline')) ?>" />
        </div>
      </div>

      <!-- Site Brand Logo & Favicon Uploaders -->
      <div class="form-grid-2">
        <!-- Site Logo -->
        <div class="form-group">
          <label class="form-label">Main Header Logo</label>
          <div class="upload-spec-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <span><strong>Recommended:</strong> 240 × 60 px (Transparent) • SVG, PNG, WebP • Max: 2 MB</span>
          </div>

          <div class="upload-dropzone" id="siteLogoDropzone" style="padding:14px;">
            <input type="file" id="siteLogoFileInput" accept="image/webp,image/jpeg,image/png,image/svg+xml" style="display:none;">
            <div class="upload-dropzone-icon" style="width:36px;height:36px;margin-bottom:4px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            </div>
            <div class="upload-dropzone-title" style="font-size:12.5px;">Drop site logo, or <span>browse</span></div>
          </div>

          <?php $curLogo = getSetting($settings, 'site_logo'); ?>
          <div class="upload-preview-card" id="siteLogoPreviewCard" style="<?= !empty($curLogo) ? 'display:flex;' : 'display:none;' ?> margin-top:8px;">
            <img src="<?= e($curLogo) ?>" id="siteLogoPreviewImg" class="upload-preview-thumb" alt="Site Logo">
            <div class="upload-preview-meta">
              <div class="upload-preview-status">✓ Header Logo Ready</div>
              <div class="upload-preview-url" id="siteLogoPreviewUrlText"><?= e($curLogo) ?></div>
            </div>
            <button type="button" class="upload-preview-remove">× Remove</button>
          </div>

          <div style="margin-top:8px;">
            <input type="url" name="site_logo" id="siteLogoInput" class="form-control" value="<?= e($curLogo) ?>" placeholder="Or enter logo URL (https://...)" oninput="updateSiteLogoPreview(this.value)">
          </div>
        </div>

        <!-- Site Favicon -->
        <div class="form-group">
          <label class="form-label">Browser Favicon / App Icon</label>
          <div class="upload-spec-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <span><strong>Recommended:</strong> 64 × 64 px (Square) • ICO, PNG, SVG • Max: 512 KB</span>
          </div>

          <div class="upload-dropzone" id="siteFaviconDropzone" style="padding:14px;">
            <input type="file" id="siteFaviconFileInput" accept="image/x-icon,image/png,image/svg+xml" style="display:none;">
            <div class="upload-dropzone-icon" style="width:36px;height:36px;margin-bottom:4px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            </div>
            <div class="upload-dropzone-title" style="font-size:12.5px;">Drop favicon, or <span>browse</span></div>
          </div>

          <?php $curFav = getSetting($settings, 'site_favicon'); ?>
          <div class="upload-preview-card" id="siteFaviconPreviewCard" style="<?= !empty($curFav) ? 'display:flex;' : 'display:none;' ?> margin-top:8px;">
            <img src="<?= e($curFav) ?>" id="siteFaviconPreviewImg" class="upload-preview-thumb" alt="Favicon" style="object-fit:contain;background:#f1f5f9;">
            <div class="upload-preview-meta">
              <div class="upload-preview-status">✓ Favicon Ready</div>
              <div class="upload-preview-url" id="siteFaviconPreviewUrlText"><?= e($curFav) ?></div>
            </div>
            <button type="button" class="upload-preview-remove">× Remove</button>
          </div>

          <div style="margin-top:8px;">
            <input type="url" name="site_favicon" id="siteFaviconInput" class="form-control" value="<?= e($curFav) ?>" placeholder="Or enter favicon URL (https://...)" oninput="updateSiteFaviconPreview(this.value)">
          </div>
        </div>
      </div>

      <div class="form-grid-3" style="margin-top: 14px;">
        <div class="form-group">
          <label class="form-label">Header Action Button Text</label>
          <input type="text" name="header_cta_text" class="form-control" value="<?= e(getSetting($settings, 'header_cta_text', 'Contact Us')) ?>" />
        </div>

        <div class="form-group">
          <label class="form-label">Header Action URL</label>
          <input type="text" name="header_cta_url" class="form-control" value="<?= e(getSetting($settings, 'header_cta_url', 'contact/')) ?>" />
        </div>

        <div class="form-group">
          <label class="form-label">Max Departments in Dropdown</label>
          <input type="number" name="header_nav_courses_limit" class="form-control" value="<?= e(getSetting($settings, 'header_nav_courses_limit', '6')) ?>" />
        </div>
      </div>
    </div>

    <!-- 2. Contact Channels & Campus -->
    <div class="form-card-box">
      <h2 style="font-size:18px;font-weight:700;color:var(--brand-primary);margin-bottom:6px;">Contact Channels &amp; Head Office</h2>
      <p style="font-size:13px;color:var(--admin-text-muted);margin-bottom:20px;">Configures the hotlines, emails, and address rendered across header and footer.</p>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Admission Hotline</label>
          <input type="text" name="contact_hotline" class="form-control" value="<?= e(getSetting($settings, 'contact_hotline', '+880 1777308777')) ?>" />
        </div>

        <div class="form-group">
          <label class="form-label">Official Email</label>
          <input type="email" name="contact_email" class="form-control" value="<?= e(getSetting($settings, 'contact_email', 'info@creativeitinstitute.com')) ?>" />
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Head Office Campus Name</label>
          <input type="text" name="contact_head_office_name" class="form-control" value="<?= e(getSetting($settings, 'contact_head_office_name', 'Dhanmondi Campus')) ?>" />
        </div>

        <div class="form-group">
          <label class="form-label">Office &amp; Lab Operating Hours</label>
          <input type="text" name="contact_campus_hours" class="form-control" value="<?= e(getSetting($settings, 'contact_campus_hours', 'Sat – Thu: 9 AM – 8 PM')) ?>" />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Physical Campus Address</label>
        <textarea name="contact_address" class="form-control" rows="3"><?= e(getSetting($settings, 'contact_address')) ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Google Maps Embed URL</label>
        <input type="url" name="contact_google_maps_embed_url" class="form-control" value="<?= e(getSetting($settings, 'contact_google_maps_embed_url')) ?>" />
      </div>
    </div>

    <!-- 3. Social Media Links -->
    <div class="form-card-box">
      <h2 style="font-size:18px;font-weight:700;color:var(--brand-primary);margin-bottom:6px;">Social Media Presence</h2>
      <p style="font-size:13px;color:var(--admin-text-muted);margin-bottom:20px;">Direct links for footer icons and external validation.</p>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Facebook Page URL</label>
          <input type="url" name="social_facebook" class="form-control" value="<?= e(getSetting($settings, 'social_facebook')) ?>" />
        </div>

        <div class="form-group">
          <label class="form-label">YouTube Channel URL</label>
          <input type="url" name="social_youtube" class="form-control" value="<?= e(getSetting($settings, 'social_youtube')) ?>" />
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">LinkedIn Organization</label>
          <input type="url" name="social_linkedin" class="form-control" value="<?= e(getSetting($settings, 'social_linkedin')) ?>" />
        </div>

        <div class="form-group">
          <label class="form-label">Instagram Profile</label>
          <input type="url" name="social_instagram" class="form-control" value="<?= e(getSetting($settings, 'social_instagram')) ?>" />
        </div>
      </div>
    </div>

    <!-- Save Button -->
    <div style="display:flex;justify-content:flex-end;margin-bottom:40px;">
      <button type="submit" id="btnSaveSettings" class="topbar-btn btn-primary-action" style="padding:14px 28px;font-size:15px;">
        Save All Site Settings
      </button>
    </div>

  </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Setup Site Logo Uploader
  setupAdminDropzone({
    dropzoneId: 'siteLogoDropzone',
    fileInputId: 'siteLogoFileInput',
    targetUrlInputId: 'siteLogoInput',
    previewCardId: 'siteLogoPreviewCard',
    previewImgId: 'siteLogoPreviewImg',
    urlTextId: 'siteLogoPreviewUrlText',
    folder: 'general',
    onSuccess: (url) => updateSiteLogoPreview(url)
  });

  // Setup Site Favicon Uploader
  setupAdminDropzone({
    dropzoneId: 'siteFaviconDropzone',
    fileInputId: 'siteFaviconFileInput',
    targetUrlInputId: 'siteFaviconInput',
    previewCardId: 'siteFaviconPreviewCard',
    previewImgId: 'siteFaviconPreviewImg',
    urlTextId: 'siteFaviconPreviewUrlText',
    folder: 'general',
    onSuccess: (url) => updateSiteFaviconPreview(url)
  });
});

function updateSiteLogoPreview(url) {
  const card = document.getElementById('siteLogoPreviewCard');
  const img = document.getElementById('siteLogoPreviewImg');
  const text = document.getElementById('siteLogoPreviewUrlText');
  if (url && url.trim() !== '') {
    img.src = url;
    text.textContent = url;
    card.style.display = 'flex';
  } else {
    card.style.display = 'none';
  }
}

function updateSiteFaviconPreview(url) {
  const card = document.getElementById('siteFaviconPreviewCard');
  const img = document.getElementById('siteFaviconPreviewImg');
  const text = document.getElementById('siteFaviconPreviewUrlText');
  if (url && url.trim() !== '') {
    img.src = url;
    text.textContent = url;
    card.style.display = 'flex';
  } else {
    card.style.display = 'none';
  }
}

async function saveSettings(e) {
  e.preventDefault();
  const form = document.getElementById('settingsForm');
  const btn = document.getElementById('btnSaveSettings');
  const payload = Object.fromEntries(new FormData(form));

  try {
    await executeAdminApi('<?= admin_url("api/settings/save") ?>', payload, btn);
    showAdminToast('success', 'Site settings saved successfully!');
  } catch (err) {}
}
</script>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
