<?php
/**
 * DreamIT Admin Console - Departments Box Grid View (NO TABLES)
 */
require_once ADMIN_PATH . '/includes/auth-guard.php';

$pageTitle = 'Departments Management';
$pageHeadline = 'Course Departments';
$activeMenu = 'departments';

$db = Database::getConnection();

// Fetch departments with course counts
$stmt = $db->query("
    SELECT d.*, COUNT(c.id) as course_count
    FROM departments d
    LEFT JOIN courses c ON d.slug = c.department
    GROUP BY d.id
    ORDER BY d.sort_order ASC, d.id ASC
");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once ADMIN_PATH . '/includes/header.php';
?>

<!-- Filter & Control Bar -->
<div class="control-filter-bar">
  <div class="search-input-wrapper">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="adminCardSearch" class="admin-search-input" placeholder="Search departments..." />
  </div>

  <button type="button" class="topbar-btn btn-primary-action" onclick="openDeptModal()">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="12" y2="12"/></svg>
    <span>+ Add Department</span>
  </button>
</div>

<!-- Departments Box Grid (NO TABLES) -->
<div class="card-capsule-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
  <?php foreach ($departments as $d):
    $isActive = (bool)$d['is_active'];
    $color = !empty($d['color']) ? $d['color'] : '#e11d48';
  ?>
    <article class="entity-card-capsule" data-id="<?= $d['id'] ?>" style="border-top: 4px solid <?= e($color) ?>;">
      <div class="card-content-body">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
          <div style="width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;color:<?= e($color) ?>;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          </div>
          <span class="status-pill <?= $isActive ? 'status-active' : 'status-draft' ?>" id="deptBadge<?= $d['id'] ?>">
            <?= $isActive ? 'Active' : 'Inactive' ?>
          </span>
        </div>

        <h2 class="card-entity-title" style="font-size:17px;"><?= e($d['name']) ?></h2>
        <div style="font-size:12px;color:var(--brand-accent);font-family:monospace;margin-bottom:10px;">/courses/<?= e($d['slug']) ?>/</div>

        <?php if (!empty($d['description'])): ?>
          <p style="font-size:13px;color:var(--admin-text-muted);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:12px;">
            <?= e($d['description']) ?>
          </p>
        <?php endif; ?>

        <div class="card-meta-chips-row" style="margin-top:auto;padding-top:12px;border-top:1px solid var(--admin-border);">
          <span class="meta-chip">&#128218; <?= (int)$d['course_count'] ?> Courses</span>
          <span class="meta-chip">&#8645; Order: <?= (int)$d['sort_order'] ?></span>
        </div>
      </div>

      <div class="card-action-footer">
        <div class="quick-switch-wrapper">
          <label class="switch">
            <input type="checkbox" <?= $isActive ? 'checked' : '' ?> onchange="toggleDept(<?= $d['id'] ?>, this)" />
            <span class="slider"></span>
          </label>
        </div>

        <div class="card-btn-group">
          <button type="button" class="btn-card-action"
            data-department='<?= htmlspecialchars(json_encode([
              'id'          => (int)$d['id'],
              'name'        => $d['name'] ?? '',
              'slug'        => $d['slug'] ?? '',
              'sort_order'  => (int)$d['sort_order'],
              'color'       => $color,
              'description' => $d['description'] ?? '',
              'icon_svg'    => $d['icon_svg'] ?? ''
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>'
            onclick="editDept(this)">
            Edit
          </button>
          <a href="<?= clean_dept_url($d['slug'], false) ?>" target="_blank" class="btn-card-action">
            View
          </a>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<!-- Department Modal (Unified Accessible Popup System) -->
<div id="deptModal" class="admin-modal-backdrop" role="dialog" aria-modal="true">
  <div class="admin-modal-box" onclick="event.stopPropagation()">
    <div class="admin-modal-header">
      <h2 class="admin-modal-title">
        <span class="admin-modal-title-icon">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        </span>
        <span id="deptModalTitle">Add Department</span>
      </h2>
      <button type="button" class="admin-modal-close" onclick="closeAdminModal('deptModal')" aria-label="Close modal">&times;</button>
    </div>

    <form id="deptForm" onsubmit="saveDept(event)">
      <div class="admin-modal-body">
        <input type="hidden" name="id" id="deptId" />

        <div class="form-group">
          <label class="form-label" for="deptName">Department Name <span class="required">*</span></label>
          <input type="text" name="name" id="deptName" class="form-control" required placeholder="e.g. Graphic &amp; Multimedia" oninput="autoGenerateDeptSlug(this.value)" />
        </div>

        <div class="form-group">
          <label class="form-label" for="deptSlug">URL Slug <span class="required">*</span></label>
          <input type="text" name="slug" id="deptSlug" class="form-control" required placeholder="e.g. graphic-design" />
          <div class="form-hint">Used in URLs: /courses/[slug]/</div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="deptOrder">Display Sort Order</label>
            <input type="number" name="sort_order" id="deptOrder" class="form-control" value="0" />
          </div>

          <div class="form-group">
            <label class="form-label" for="deptColor">Theme Color</label>
            <div style="display:flex;gap:8px;align-items:center;">
              <input type="color" id="deptColorPicker" value="#0B2046" style="width:42px;height:38px;padding:2px;border:1px solid var(--admin-border);border-radius:6px;cursor:pointer;" onchange="document.getElementById('deptColor').value = this.value" />
              <input type="text" name="color" id="deptColor" class="form-control" value="#0B2046" placeholder="#0B2046" oninput="document.getElementById('deptColorPicker').value = this.value" />
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Department Icon / SVG Graphic</label>
          <div class="upload-spec-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span><strong>Recommended:</strong> 128 &times; 128 px (1:1) &bull; Formats: SVG, PNG, WebP &bull; Max: 1 MB</span>
          </div>

          <div class="upload-dropzone" id="deptIconDropzone" style="padding:14px;">
            <input type="file" id="deptIconFileInput" accept="image/svg+xml,image/png,image/webp" style="display:none;">
            <div class="upload-dropzone-icon" style="width:36px;height:36px;margin-bottom:4px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div class="upload-dropzone-title" style="font-size:12.5px;">Drop SVG icon, or <span>browse</span></div>
          </div>

          <div class="upload-preview-card" id="deptIconPreviewCard" style="display:none;margin-top:8px;">
            <img src="" id="deptIconPreviewImg" class="upload-preview-thumb" alt="Icon Preview" style="width:48px;height:48px;object-fit:contain;background:#F8FAFC;padding:4px;">
            <div class="upload-preview-meta">
              <div class="upload-preview-status">&#10003; Icon Uploaded</div>
              <div class="upload-preview-url" id="deptIconPreviewUrlText"></div>
            </div>
            <button type="button" class="upload-preview-remove">&times; Remove</button>
          </div>

          <div style="margin-top:8px;">
            <textarea name="icon_svg" id="deptIconSvg" class="form-control" rows="2" placeholder="Or paste raw <svg>...</svg> code or icon image URL"></textarea>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="deptDesc">Short Description</label>
          <textarea name="description" id="deptDesc" class="form-control" rows="2" placeholder="Brief summary of curriculum focus in this department..."></textarea>
        </div>
      </div>

      <div class="admin-modal-footer">
        <button type="button" class="btn-admin-secondary" onclick="closeAdminModal('deptModal')">Cancel</button>
        <button type="submit" class="btn-admin-primary" id="btnSaveDept">Save Department</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  setupAdminDropzone({
    dropzoneEl: document.getElementById('deptIconDropzone'),
    fileInputEl: document.getElementById('deptIconFileInput'),
    urlInputEl: document.getElementById('deptIconSvg'),
    previewCardEl: document.getElementById('deptIconPreviewCard'),
    previewThumbEl: document.getElementById('deptIconPreviewImg'),
    previewUrlEl: document.getElementById('deptIconPreviewUrlText'),
    folder: 'departments'
  });
});

function autoGenerateDeptSlug(name) {
  const slugInput = document.getElementById('deptSlug');
  const deptId = document.getElementById('deptId').value;
  if (!deptId) { // Only auto-slug when creating new
    slugInput.value = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }
}

function openDeptModal() {
  document.getElementById('deptForm').reset();
  document.getElementById('deptId').value = '';
  document.getElementById('deptColorPicker').value = '#0B2046';
  document.getElementById('deptColor').value = '#0B2046';
  document.getElementById('deptIconSvg').value = '';
  document.getElementById('deptIconPreviewCard').style.display = 'none';
  document.getElementById('deptModalTitle').textContent = 'Add Department';
  openAdminModal('deptModal');
}

function closeDeptModal() {
  closeAdminModal('deptModal');
}

function editDept(btnOrData) {
  const d = getAdminEntityData(btnOrData, 'department') || btnOrData;
  if (!d) return;

  document.getElementById('deptId').value = d.id;
  document.getElementById('deptName').value = d.name || '';
  document.getElementById('deptSlug').value = d.slug || '';
  document.getElementById('deptOrder').value = d.sort_order || 0;
  const col = d.color || '#0B2046';
  document.getElementById('deptColor').value = col;
  try { document.getElementById('deptColorPicker').value = col; } catch(e) {}
  document.getElementById('deptDesc').value = d.description || '';
  document.getElementById('deptIconSvg').value = d.icon_svg || '';

  const previewCard = document.getElementById('deptIconPreviewCard');
  if (d.icon_svg && (d.icon_svg.startsWith('http') || d.icon_svg.includes('/uploads/'))) {
    document.getElementById('deptIconPreviewImg').src = d.icon_svg;
    document.getElementById('deptIconPreviewUrlText').textContent = d.icon_svg;
    previewCard.style.display = 'flex';
  } else {
    previewCard.style.display = 'none';
  }

  document.getElementById('deptModalTitle').textContent = 'Edit: ' + (d.name || 'Department');
  openAdminModal('deptModal');
}

async function saveDept(e) {
  e.preventDefault();
  const form = document.getElementById('deptForm');
  const btn = document.getElementById('btnSaveDept');
  const nameVal = document.getElementById('deptName').value.trim();
  const slugVal = document.getElementById('deptSlug').value.trim();

  if (!nameVal) {
    showAdminToast('error', 'Department name is required.');
    return;
  }
  if (!slugVal) {
    showAdminToast('error', 'Department URL slug is required.');
    return;
  }

  const payload = Object.fromEntries(new FormData(form));

  try {
    await executeAdminApi('<?= admin_url("api/departments/save") ?>', payload, btn);
    showAdminToast('success', 'Department saved successfully!');
    closeAdminModal('deptModal');
    setTimeout(() => location.reload(), 600);
  } catch (err) {}
}

async function toggleDept(id, switchEl) {
  const newActive = switchEl.checked ? 1 : 0;
  const badge = document.getElementById(`deptBadge${id}`);

  try {
    await executeAdminApi('<?= admin_url("api/departments/toggle") ?>', { id, is_active: newActive });
    if (badge) {
      badge.textContent = newActive ? 'Active' : 'Inactive';
      badge.className = `status-pill ${newActive ? 'status-active' : 'status-draft'}`;
    }
    showAdminToast('success', 'Department status updated');
  } catch (err) {
    switchEl.checked = !switchEl.checked;
  }
}
</script>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
