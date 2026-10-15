<?php
/**
 * DreamIT Admin Console - Courses Card Grid View (NO TABLES)
 */
require_once ADMIN_PATH . '/includes/auth-guard.php';

$pageTitle = 'Courses Management';
$pageHeadline = 'Courses Management';
$activeMenu = 'courses';

$db = Database::getConnection();

// Fetch all courses
$stmt = $db->query("SELECT * FROM courses ORDER BY id DESC");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch departments for filter
$deptStmt = $db->query("SELECT slug, name FROM departments WHERE is_active = 1 ORDER BY sort_order ASC");
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

require_once ADMIN_PATH . '/includes/header.php';
?>

<!-- Filter & Control Bar -->
<div class="control-filter-bar">
  <div class="search-input-wrapper">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="adminCardSearch" class="admin-search-input" placeholder="Search courses by title, keywords..." />
  </div>

  <div class="filter-actions-group">
    <select id="adminCategoryFilter" class="admin-select" aria-label="Filter by department">
      <option value="">All Departments</option>
      <?php foreach ($departments as $d): ?>
        <option value="<?= e($d['slug']) ?>"><?= e($d['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <select id="adminStatusFilter" class="admin-select" aria-label="Filter by status">
      <option value="">All Statuses</option>
      <option value="active">Active</option>
      <option value="draft">Draft</option>
    </select>

    <a href="<?= admin_url('courses/create') ?>" class="topbar-btn btn-primary-action">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="12" y2="12"/></svg>
      <span>+ Add Course (Wizard)</span>
    </a>
  </div>
</div>

<!-- Modern Card Grid Container (NO TABLES) -->
<div class="card-capsule-grid">
  <?php if (empty($courses)): ?>
    <div style="grid-column: 1 / -1; text-align: center; padding: 48px; background: var(--admin-surface-card); border-radius: var(--admin-radius-lg); border: 1px dashed var(--admin-border);">
      <h3 style="color:var(--brand-primary);font-size:18px;margin-bottom:8px;">No courses found</h3>
      <p style="color:var(--admin-text-muted);font-size:14px;margin-bottom:18px;">Get started by creating your first course using the progressive wizard.</p>
      <a href="<?= admin_url('courses/create') ?>" class="topbar-btn btn-primary-action">+ Add Course</a>
    </div>
  <?php else: ?>
    <?php foreach ($courses as $c):
      $isActive = ($c['status'] === 'active');
      $thumb = !empty($c['thumbnail_url']) ? $c['thumbnail_url'] : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600';
      $deptSlug = $c['department'] ?? 'general';
    ?>
      <article class="entity-card-capsule" data-id="<?= $c['id'] ?>" data-category="<?= e($deptSlug) ?>" data-status="<?= $isActive ? 'active' : 'draft' ?>">

        <div class="card-cover-media">
          <img src="<?= e($thumb) ?>" alt="<?= e($c['title']) ?>" loading="lazy" />

          <div class="cover-badge-left">
            <span class="category-tag-pill"><?= ucfirst(e($deptSlug)) ?></span>
          </div>

          <div class="cover-badge-right">
            <span class="status-pill <?= $isActive ? 'status-active' : 'status-draft' ?>" id="badgeStatus<?= $c['id'] ?>">
              <?= $isActive ? 'Active' : 'Draft' ?>
            </span>
          </div>
        </div>

        <div class="card-content-body">
          <h2 class="card-entity-title"><?= e($c['title']) ?></h2>

          <div class="card-meta-chips-row">
            <span class="meta-chip">&#9201; <?= e($c['duration'] ?: '4 Months') ?></span>
            <span class="meta-chip">&#128187; <?= e($c['total_classes'] ?: '32') ?> Classes</span>
            <span class="meta-chip">&#9733; <?= e($c['total_projects'] ?: '10') ?> Projects</span>
          </div>

          <div class="card-pricing-strip">
            <?php
              $price         = (float)($c['price'] ?? 0);
              $discountPrice = array_key_exists('discount_price', $c) && $c['discount_price'] !== null && $c['discount_price'] !== ''
                               ? (float)$c['discount_price'] : null;
              $displayPrice  = ($discountPrice !== null && $discountPrice > 0) ? $discountPrice : $price;
            ?>
            <span class="price-current">&#2547; <?= number_format($displayPrice) ?></span>
            <?php if ($discountPrice !== null && $discountPrice > 0 && $discountPrice < $price): ?>
              <span class="price-original">&#2547; <?= number_format($price) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <div class="card-action-footer">
          <div class="quick-switch-wrapper">
            <span>Status:</span>
            <label class="switch">
              <input type="checkbox" <?= $isActive ? 'checked' : '' ?> onchange="toggleCourse(<?= $c['id'] ?>, this)" />
              <span class="slider"></span>
            </label>
          </div>

          <div class="card-btn-group">
            <button type="button" class="btn-card-action"
              data-course='<?= htmlspecialchars(json_encode([
                'id'             => (int)$c['id'],
                'title'          => $c['title'] ?? '',
                'slug'           => $c['slug'] ?? '',
                'department'     => $deptSlug,
                'price'          => (float)($c['price'] ?? 0),
                'discount_price' => (float)($c['discount_price'] ?? 0),
                'duration'       => $c['duration'] ?: '4 Months',
                'total_classes'  => $c['total_classes'] ?: '32',
                'total_projects' => $c['total_projects'] ?: '10',
                'status'         => $isActive ? 'active' : 'draft',
                'thumbnail_url'  => $c['thumbnail_url'] ?? '',
                'overview'       => $c['overview'] ?? ''
              ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>'
              onclick="editCourse(this)"
              title="Quick edit course details in popup">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit
            </button>
            <a href="<?= clean_course_url($c['slug'], false) ?>" target="_blank" class="btn-card-action" title="Preview live page in new tab">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              Preview
            </a>
            <button type="button" class="btn-card-action btn-card-delete" onclick="deleteCourse(<?= $c['id'] ?>, '<?= e(addslashes($c['title'])) ?>')" title="Delete course">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
          </div>
        </div>

      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Course Quick Edit Modal (Popup System) -->
<div id="courseModal" class="admin-modal-backdrop" role="dialog" aria-modal="true">
  <div class="admin-modal-box modal-lg" onclick="event.stopPropagation()">
    <div class="admin-modal-header">
      <h2 class="admin-modal-title">
        <span class="admin-modal-title-icon">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        </span>
        <span id="courseModalTitle">Quick Edit Course</span>
      </h2>
      <button type="button" class="admin-modal-close" onclick="closeAdminModal('courseModal')" aria-label="Close modal">&times;</button>
    </div>

    <form id="courseForm" onsubmit="saveQuickCourse(event)">
      <div class="admin-modal-body">
        <input type="hidden" name="id" id="courseId" />

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="courseTitleInput">Course Title <span class="required">*</span></label>
            <input type="text" name="title" id="courseTitleInput" class="form-control" required placeholder="e.g. Professional Graphic Design" />
          </div>

          <div class="form-group">
            <label class="form-label" for="courseSlugInput">URL Slug <span class="required">*</span></label>
            <input type="text" name="slug" id="courseSlugInput" class="form-control" required placeholder="e.g. professional-graphic-design" />
          </div>
        </div>

        <div class="form-grid-3">
          <div class="form-group">
            <label class="form-label" for="courseDeptInput">Department</label>
            <select name="department" id="courseDeptInput" class="admin-select" style="width:100%;">
              <?php foreach ($departments as $d): ?>
                <option value="<?= e($d['slug']) ?>"><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="coursePriceInput">Course Fee (BDT)</label>
            <input type="number" step="0.01" name="price" id="coursePriceInput" class="form-control" placeholder="15000" />
          </div>

          <div class="form-group">
            <label class="form-label" for="courseDiscountInput">Discounted Fee (Optional)</label>
            <input type="number" step="0.01" name="discount_price" id="courseDiscountInput" class="form-control" placeholder="12000" />
          </div>
        </div>

        <div class="form-grid-3">
          <div class="form-group">
            <label class="form-label" for="courseDurationInput">Course Duration</label>
            <input type="text" name="duration" id="courseDurationInput" class="form-control" placeholder="4 Months" />
          </div>

          <div class="form-group">
            <label class="form-label" for="courseClassesInput">Total Classes</label>
            <input type="text" name="total_classes" id="courseClassesInput" class="form-control" placeholder="32 Classes" />
          </div>

          <div class="form-group">
            <label class="form-label" for="courseProjectsInput">Total Projects</label>
            <input type="text" name="total_projects" id="courseProjectsInput" class="form-control" placeholder="10 Projects" />
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="courseStatusInput">Publication Status</label>
            <select name="status" id="courseStatusInput" class="admin-select" style="width:100%;">
              <option value="active">Active (Visible)</option>
              <option value="draft">Draft (Hidden)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Course Thumbnail Image</label>
            <!-- Specification Badge -->
            <div class="upload-spec-badge">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              <span><strong>Recommended:</strong> 800 &times; 500 px (16:10) &bull; WebP, JPG, PNG &bull; Max: 3 MB</span>
            </div>

            <!-- Dropzone -->
            <div class="upload-dropzone" id="courseThumbDropzone" style="padding:14px;">
              <input type="file" id="courseThumbFileInput" accept="image/webp,image/jpeg,image/png,image/gif" style="display:none;">
              <div class="upload-dropzone-icon" style="width:36px;height:36px;margin-bottom:4px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              </div>
              <div class="upload-dropzone-title" style="font-size:12.5px;">Drop thumbnail, or <span>browse</span></div>
            </div>

            <!-- Preview Card -->
            <div class="upload-preview-card" id="courseThumbPreviewCard" style="display:none;margin-top:8px;">
              <img src="" id="courseThumbPreviewImg" class="upload-preview-thumb" alt="Thumbnail Preview">
              <div class="upload-preview-meta">
                <div class="upload-preview-status">&#10003; Thumbnail Ready</div>
                <div class="upload-preview-url" id="courseThumbPreviewUrlText"></div>
              </div>
              <button type="button" class="upload-preview-remove">&times; Remove</button>
            </div>

            <div style="margin-top:8px;">
              <input type="url" name="thumbnail_url" id="courseThumbInput" class="form-control" placeholder="Or enter image URL (https://...)" oninput="updateCourseThumbPreview(this.value)" />
            </div>
          </div>
        </div>
      </div>

      <div class="admin-modal-footer" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <a href="#" id="courseWizardLink" class="topbar-btn btn-ghost" style="text-decoration:none;">
          <span>Open Full 4-Step Wizard &rarr;</span>
        </a>
        <div style="display:flex;gap:10px;">
          <button type="button" class="btn-admin-secondary" onclick="closeAdminModal('courseModal')">Cancel</button>
          <button type="submit" class="btn-admin-primary" id="btnSaveCourseQuick">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  setupAdminDropzone({
    dropzoneEl: document.getElementById('courseThumbDropzone'),
    fileInputEl: document.getElementById('courseThumbFileInput'),
    urlInputEl: document.getElementById('courseThumbInput'),
    previewCardEl: document.getElementById('courseThumbPreviewCard'),
    previewThumbEl: document.getElementById('courseThumbPreviewImg'),
    previewUrlEl: document.getElementById('courseThumbPreviewUrlText'),
    folder: 'courses'
  });
});

function updateCourseThumbPreview(url) {
  url = (url || '').trim();
  const card = document.getElementById('courseThumbPreviewCard');
  const img  = document.getElementById('courseThumbPreviewImg');
  const txt  = document.getElementById('courseThumbPreviewUrlText');
  if (url) {
    img.src = url;
    txt.textContent = url;
    card.style.display = 'flex';
  } else {
    card.style.display = 'none';
  }
}

function editCourse(btnOrData) {
  const c = getAdminEntityData(btnOrData, 'course') || btnOrData;
  if (!c) return;

  document.getElementById('courseId').value = c.id;
  document.getElementById('courseTitleInput').value = c.title || '';
  document.getElementById('courseSlugInput').value = c.slug || '';
  document.getElementById('courseDeptInput').value = c.department || '';
  document.getElementById('coursePriceInput').value = c.price || '';
  document.getElementById('courseDiscountInput').value = (c.discount_price && c.discount_price > 0) ? c.discount_price : '';
  document.getElementById('courseDurationInput').value = c.duration || '';
  document.getElementById('courseClassesInput').value = c.total_classes || '';
  document.getElementById('courseProjectsInput').value = c.total_projects || '';
  document.getElementById('courseStatusInput').value = c.status || 'active';
  document.getElementById('courseThumbInput').value = c.thumbnail_url || '';

  updateCourseThumbPreview(c.thumbnail_url);

  document.getElementById('courseModalTitle').textContent = 'Quick Edit: ' + (c.title || 'Course');

  // Update link to deep wizard
  const wizardUrl = '<?= admin_url("courses/edit?id=") ?>' + c.id;
  const wizardLink = document.getElementById('courseWizardLink');
  if (wizardLink) {
    wizardLink.href = wizardUrl;
  }

  openAdminModal('courseModal');
}

async function saveQuickCourse(e) {
  e.preventDefault();
  const form = document.getElementById('courseForm');
  const btn = document.getElementById('btnSaveCourseQuick');
  const payload = Object.fromEntries(new FormData(form));

  try {
    await executeAdminApi('<?= admin_url("api/courses/quick-save") ?>', payload, btn);
    showAdminToast('success', 'Course updated successfully!');
    closeAdminModal('courseModal');
    setTimeout(() => location.reload(), 600);
  } catch (err) {}
}

async function toggleCourse(courseId, switchEl) {
  const newStatus = switchEl.checked ? 'active' : 'draft';
  const badge = document.getElementById(`badgeStatus${courseId}`);

  try {
    const res = await executeAdminApi('<?= admin_url("api/courses/toggle-status") ?>', {
      id: courseId,
      status: newStatus
    });

    if (badge) {
      badge.textContent = newStatus === 'active' ? 'Active' : 'Draft';
      badge.className = `status-pill ${newStatus === 'active' ? 'status-active' : 'status-draft'}`;
    }
    showAdminToast('success', 'Course status updated to ' + newStatus);
  } catch (err) {
    switchEl.checked = !switchEl.checked;
  }
}

async function deleteCourse(courseId, title) {
  const confirmed = await confirmAdminAction({
    title: 'Delete Course?',
    message: `Are you sure you want to delete "${title}"? This will permanently remove the course and its curriculum modules.`,
    confirmText: 'Yes, Delete Course',
    isDanger: true
  });
  if (!confirmed) return;

  try {
    await executeAdminApi('<?= admin_url("api/courses/delete") ?>', { id: courseId });
    showAdminToast('success', 'Course deleted successfully');
    const card = document.querySelector(`.entity-card-capsule[data-id="${courseId}"]`);
    if (card) {
      card.style.opacity = '0';
      card.style.transform = 'scale(0.9)';
      card.style.transition = 'all 0.3s ease';
      setTimeout(() => card.remove(), 300);
    }
  } catch (err) {}
}
</script>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
