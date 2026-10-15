<?php
/**
 * DreamIT Admin Console - Dashboard View
 */
require_once ADMIN_PATH . '/includes/auth-guard.php';

$pageTitle = 'Executive Dashboard';
$pageHeadline = 'Executive Overview';
$activeMenu = 'dashboard';

$db = Database::getConnection();

// Aggregate Metrics
$totalCourses = (int)$db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$activeCourses = (int)$db->query("SELECT COUNT(*) FROM courses WHERE status = 'active'")->fetchColumn();
$totalProjects = (int)$db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$unreadEnquiries = (int)$db->query("SELECT COUNT(*) FROM enquiries WHERE status = 'unread'")->fetchColumn();
$totalDepartments = (int)$db->query("SELECT COUNT(*) FROM departments WHERE is_active = 1")->fetchColumn();

// Recent Inquiries
$recentEnquiries = $db->query("SELECT * FROM enquiries ORDER BY created_at DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);

// Recent Courses
$recentCourses = $db->query("SELECT * FROM courses ORDER BY id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

// Departments for edit modal
$departments = $db->query("SELECT slug, name FROM departments WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once ADMIN_PATH . '/includes/header.php';
?>

<!-- 1. Metric Stat Boxes Grid -->
<div class="metric-boxes-grid">

  <div class="metric-stat-box box-cyan">
    <div class="stat-info-col">
      <span class="stat-label">Active Courses</span>
      <span class="stat-value"><?= $activeCourses ?></span>
      <span class="stat-meta"><?= $totalCourses ?> total curriculums</span>
    </div>
    <div class="stat-icon-wrapper">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
    </div>
  </div>

  <div class="metric-stat-box box-emerald">
    <div class="stat-info-col">
      <span class="stat-label">Student Projects</span>
      <span class="stat-value"><?= $totalProjects ?></span>
      <span class="stat-meta">Portfolio items live</span>
    </div>
    <div class="stat-icon-wrapper">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
    </div>
  </div>

  <div class="metric-stat-box box-amber">
    <div class="stat-info-col">
      <span class="stat-label">New Enquiries</span>
      <span class="stat-value"><?= $unreadEnquiries ?></span>
      <span class="stat-meta" style="color:#fb7185;">Requires counselor follow-up</span>
    </div>
    <div class="stat-icon-wrapper">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
    </div>
  </div>

  <div class="metric-stat-box box-purple">
    <div class="stat-info-col">
      <span class="stat-label">Departments</span>
      <span class="stat-value"><?= $totalDepartments ?></span>
      <span class="stat-meta">Active mega-menu categories</span>
    </div>
    <div class="stat-icon-wrapper">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
    </div>
  </div>

</div>

<!-- 2. Recent Inquiries Section -->
<div style="margin-bottom: 36px;">
  <div class="section-header-row">
    <h2 class="section-title">
      <span class="section-title-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
      </span>
      Recent Admission Inquiries
    </h2>
    <a href="<?= admin_url('enquiries') ?>" class="section-view-all">View All &rarr;</a>
  </div>

  <?php if (empty($recentEnquiries)): ?>
    <div class="form-card-box" style="text-align:center;padding:32px;color:var(--admin-text-muted);">
      No recent inquiries recorded yet.
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:16px;">
      <?php foreach ($recentEnquiries as $enq):
        $isUnread = ($enq['status'] === 'unread');
      ?>
        <div class="enquiry-lead-card <?= $isUnread ? 'unread-lead' : '' ?>">
          <div class="lead-header-row">
            <div class="lead-name-col">
              <strong><?= e($enq['name'] ?? 'Prospective Student') ?></strong>
              <span class="lead-time"><?= date('M j, Y h:i A', strtotime($enq['created_at'])) ?></span>
            </div>
            <span class="status-pill <?= $isUnread ? 'status-unread' : 'status-active' ?>">
              <?= $isUnread ? 'New Lead' : 'Handled' ?>
            </span>
          </div>

          <div class="lead-details-row">
            <span class="lead-detail-pill">
              <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              <?= e($enq['phone'] ?? 'N/A') ?>
            </span>
            <?php if (!empty($enq['course_name'])): ?>
              <span class="lead-detail-pill" style="color:var(--brand-accent);">
                <?= e($enq['course_name']) ?>
              </span>
            <?php endif; ?>
          </div>

          <?php if (!empty($enq['message'])): ?>
            <div class="lead-message-box">
              <?= e($enq['message']) ?>
            </div>
          <?php endif; ?>

          <div class="lead-action-bar">
            <?php if (!empty($enq['phone'])): ?>
              <a href="tel:<?= e($enq['phone']) ?>" class="btn-card-action" style="color:#38bdf8;">
                Call Now
              </a>
              <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $enq['phone']) ?>" target="_blank" class="btn-card-action" style="color:#34d399;">
                WhatsApp
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- 3. Recent Courses Grid (Using Cards, NOT tables) -->
<div>
  <div class="section-header-row">
    <h2 class="section-title">
      <span class="section-title-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
      </span>
      Active Courses
    </h2>
    <a href="<?= admin_url('courses') ?>" class="section-view-all">View All (<?= $totalCourses ?>) &rarr;</a>
  </div>

  <div class="card-capsule-grid">
    <?php foreach ($recentCourses as $c):
      $isActive = ($c['status'] === 'active');
      $thumb = !empty($c['thumbnail_url']) ? $c['thumbnail_url'] : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600';
    ?>
      <article class="entity-card-capsule" data-id="<?= $c['id'] ?>">
        <div class="card-cover-media">
          <img src="<?= e($thumb) ?>" alt="<?= e($c['title']) ?>" loading="lazy" />
          <div class="cover-badge-left">
            <span class="category-tag-pill"><?= ucfirst(e($c['department'] ?? 'General')) ?></span>
          </div>
          <div class="cover-badge-right">
            <span class="status-pill <?= $isActive ? 'status-active' : 'status-draft' ?>">
              <?= $isActive ? 'Active' : 'Draft' ?>
            </span>
          </div>
        </div>

        <div class="card-content-body">
          <h3 class="card-entity-title"><?= e($c['title']) ?></h3>

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
              <input type="checkbox" <?= $isActive ? 'checked' : '' ?> onchange="toggleCourseStatus(<?= $c['id'] ?>, this)" />
              <span class="slider"></span>
            </label>
          </div>

          <div class="card-btn-group">
            <button type="button" class="btn-card-action"
              data-course='<?= htmlspecialchars(json_encode([
                'id'             => (int)$c['id'],
                'title'          => $c['title'] ?? '',
                'slug'           => $c['slug'] ?? '',
                'department'     => $c['department'] ?? 'general',
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
              title="Quick edit course details">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit
            </button>
            <a href="<?= clean_course_url($c['slug'], false) ?>" target="_blank" class="btn-card-action">Preview</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
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
            <div class="upload-dropzone" id="dashCourseThumbDropzone" style="padding:14px;">
              <input type="file" id="dashCourseThumbFileInput" accept="image/webp,image/jpeg,image/png,image/gif" style="display:none;">
              <div class="upload-dropzone-icon" style="width:36px;height:36px;margin-bottom:4px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              </div>
              <div class="upload-dropzone-title" style="font-size:12.5px;">Drop thumbnail, or <span>browse</span></div>
            </div>

            <!-- Preview Card -->
            <div class="upload-preview-card" id="dashCourseThumbPreviewCard" style="display:none;margin-top:8px;">
              <img src="" id="dashCourseThumbPreviewImg" class="upload-preview-thumb" alt="Thumbnail Preview">
              <div class="upload-preview-meta">
                <div class="upload-preview-status">&#10003; Thumbnail Ready</div>
                <div class="upload-preview-url" id="dashCourseThumbPreviewUrlText"></div>
              </div>
              <button type="button" class="upload-preview-remove">&times; Remove</button>
            </div>

            <div style="margin-top:8px;">
              <input type="url" name="thumbnail_url" id="courseThumbInput" class="form-control" placeholder="Or enter image URL (https://...)" oninput="updateDashCourseThumbPreview(this.value)" />
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
    dropzoneEl: document.getElementById('dashCourseThumbDropzone'),
    fileInputEl: document.getElementById('dashCourseThumbFileInput'),
    urlInputEl: document.getElementById('courseThumbInput'),
    previewCardEl: document.getElementById('dashCourseThumbPreviewCard'),
    previewThumbEl: document.getElementById('dashCourseThumbPreviewImg'),
    previewUrlEl: document.getElementById('dashCourseThumbPreviewUrlText'),
    folder: 'courses'
  });
});

function updateDashCourseThumbPreview(url) {
  url = (url || '').trim();
  const card = document.getElementById('dashCourseThumbPreviewCard');
  const img  = document.getElementById('dashCourseThumbPreviewImg');
  const txt  = document.getElementById('dashCourseThumbPreviewUrlText');
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

  updateDashCourseThumbPreview(c.thumbnail_url);

  document.getElementById('courseModalTitle').textContent = 'Quick Edit: ' + (c.title || 'Course');

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

async function toggleCourseStatus(courseId, switchEl) {
  const newStatus = switchEl.checked ? 'active' : 'draft';
  try {
    const res = await executeAdminApi('<?= admin_url("api/courses/toggle-status") ?>', {
      id: courseId,
      status: newStatus
    });
    showAdminToast('success', 'Course status updated to ' + newStatus);
  } catch (err) {
    switchEl.checked = !switchEl.checked; // Revert
  }
}
</script>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
