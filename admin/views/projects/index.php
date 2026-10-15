<?php
/**
 * DreamIT Admin Console - Student Work / Projects Master View
 * Complete portfolio management with multi-image carousel gallery, real uploads, and course links
 */

require_once ADMIN_PATH . '/includes/auth-guard.php';

$pageTitle = 'Student Projects';
$pageHeadline = 'Student Portfolio & Creative Works';
$activeMenu = 'projects';

$db = Database::getConnection();

// Fetch all projects with course name & department info
$stmt = $db->query("
    SELECT p.*, c.title AS course_title, d.name AS dept_name
    FROM projects p
    LEFT JOIN courses c ON p.course_id = c.id
    LEFT JOIN departments d ON p.department = d.slug
    ORDER BY p.sort_order ASC, p.id DESC
");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Metrics
$totalProjects    = count($projects);
$publishedCount   = count(array_filter($projects, fn($p) => ($p['status'] ?? '') === 'published'));
$featuredCount    = count(array_filter($projects, fn($p) => (int)($p['is_featured'] ?? $p['featured'] ?? 0) === 1));

// Fetch courses for dropdown
$courses = $db->query("SELECT id, title, department, category FROM courses ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch departments for dropdown
$departments = $db->query("SELECT id, name, slug FROM departments WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once ADMIN_PATH . '/includes/header.php';
?>

<!-- Metric Stats Row -->
<div class="metric-boxes-grid">
  <div class="metric-stat-box box-cyan">
    <div class="stat-info-col">
      <span class="stat-label">Total Student Works</span>
      <span class="stat-value"><?= $totalProjects ?></span>
      <span class="stat-meta">Portfolio Showcases</span>
    </div>
    <div class="stat-icon-wrapper">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
    </div>
  </div>

  <div class="metric-stat-box box-emerald">
    <div class="stat-info-col">
      <span class="stat-label">Published Live</span>
      <span class="stat-value"><?= $publishedCount ?></span>
      <span class="stat-meta">&#10003; Visible to Visitors</span>
    </div>
    <div class="stat-icon-wrapper">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
    </div>
  </div>

  <div class="metric-stat-box box-amber">
    <div class="stat-info-col">
      <span class="stat-label">Featured on Homepage</span>
      <span class="stat-value"><?= $featuredCount ?></span>
      <span class="stat-meta">&#9733; Priority Showcase</span>
    </div>
    <div class="stat-icon-wrapper">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    </div>
  </div>

  <div class="metric-stat-box box-purple">
    <div class="stat-info-col">
      <span class="stat-label">Active Departments</span>
      <span class="stat-value"><?= count($departments) ?></span>
      <span class="stat-meta">Across All IT Disciplines</span>
    </div>
    <div class="stat-icon-wrapper">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="control-filter-bar" style="margin-bottom: 24px;">
  <div class="search-input-wrapper">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="adminCardSearch" class="admin-search-input" placeholder="Search projects by title, student name, department..." oninput="filterProjectCards()" />
  </div>

  <div class="filter-actions-group">
    <select id="adminCourseFilter" class="admin-select" onchange="filterProjectCards()">
      <option value="">All Curriculums</option>
      <?php foreach ($courses as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= e($c['title']) ?></option>
      <?php endforeach; ?>
    </select>

    <select id="adminDeptFilter" class="admin-select" onchange="filterProjectCards()">
      <option value="">All Departments</option>
      <?php foreach ($departments as $d): ?>
        <option value="<?= e($d['slug']) ?>"><?= e($d['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <button type="button" class="topbar-btn btn-primary-action" onclick="openNewProjectModal()">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="12" y2="12"/></svg>
      <span>+ Add Student Project</span>
    </button>
  </div>
</div>

<!-- Projects Card Grid -->
<div class="card-capsule-grid" id="projectsGrid">
  <?php if (empty($projects)): ?>
    <div class="empty-state-box">
      <div class="empty-state-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      </div>
      <h3 class="empty-state-title">No Student Projects Yet</h3>
      <p class="empty-state-desc">Showcase student creative portfolios and real-world projects to demonstrate practical skills.</p>
      <button type="button" class="topbar-btn btn-primary-action" onclick="openNewProjectModal()">+ Add First Student Project</button>
    </div>
  <?php else: ?>
    <?php foreach ($projects as $p):
      $thumb = !empty($p['cover_image']) ? $p['cover_image'] : 'https://images.unsplash.com/photo-1542744094-3a31f272c490?w=600';
      $isFeatured = (bool)($p['is_featured'] ?? ($p['featured'] ?? false));
      $isPublished = ($p['status'] ?? 'published') === 'published';
      $cardDept = $p['dept_name'] ?? ucfirst($p['department'] ?? 'General');
      $safeJson = htmlspecialchars(json_encode([
          'id'                => (int)$p['id'],
          'title'             => $p['title'] ?? '',
          'slug'              => $p['slug'] ?? '',
          'course_id'         => (int)($p['course_id'] ?? 0),
          'department'        => $p['department'] ?? 'graphic',
          'category'          => $p['category'] ?? 'Graphic Design',
          'student_name'      => $p['student_name'] ?? '',
          'brief_description' => $p['brief_description'] ?? ($p['short_description'] ?? ''),
          'description'       => $p['description'] ?? '',
          'cover_image'       => $thumb,
          'live_url'          => $p['live_url'] ?? '',
          'github_url'        => $p['github_url'] ?? '',
          'is_featured'       => $isFeatured ? 1 : 0,
          'sort_order'        => (int)($p['sort_order'] ?? 0),
          'status'            => $p['status'] ?? 'published',
      ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
    ?>
      <article class="entity-card-capsule project-card"
               id="projCard_<?= (int)$p['id'] ?>"
               data-id="<?= (int)$p['id'] ?>"
               data-course="<?= (int)($p['course_id'] ?? 0) ?>"
               data-dept="<?= e($p['department'] ?? '') ?>"
               data-search="<?= e(strtolower(($p['title'] ?? '') . ' ' . ($p['student_name'] ?? '') . ' ' . $cardDept)) ?>">

        <div class="card-cover-media" style="position:relative;height:180px;background:#0B2046;overflow:hidden;">
          <img src="<?= e($thumb) ?>" alt="<?= e($p['title']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;" />

          <div class="cover-badge-left">
            <span class="category-tag-pill"><?= e($p['course_title'] ?? $cardDept) ?></span>
          </div>

          <div class="cover-badge-right" style="display:flex;gap:4px;">
            <?php if ($isFeatured): ?>
              <span class="status-pill status-active" style="background:#F59E0B;color:#fff;">&#9733; Featured</span>
            <?php endif; ?>
            <span class="status-pill <?= $isPublished ? 'status-active' : 'status-inactive' ?>">
              <?= $isPublished ? 'Published' : 'Draft' ?>
            </span>
          </div>
        </div>

        <div class="card-content-body" style="padding:16px;">
          <h2 class="card-entity-title" style="font-size:15px;font-weight:700;color:var(--brand-primary);margin-bottom:8px;line-height:1.35;">
            <?= e($p['title']) ?>
          </h2>

          <div class="card-meta-chips-row" style="margin-bottom:8px;">
            <span class="meta-chip">&#128100; <?= e($p['student_name'] ?? 'Alumnus') ?></span>
            <span class="meta-chip">&#127991;&#65039; <?= e($p['category'] ?? 'Design') ?></span>
          </div>

          <?php if (!empty($p['brief_description'])): ?>
            <p style="font-size:12.5px;color:var(--admin-text-muted);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
              <?= e($p['brief_description']) ?>
            </p>
          <?php endif; ?>
        </div>

        <div class="card-action-footer" style="padding:12px 16px;border-top:1px solid var(--admin-border);">
          <div class="card-btn-group" style="width:100%;justify-content:space-between;display:flex;gap:6px;">
            <button type="button" class="btn-card-action"
              data-project='<?= $safeJson ?>'
              onclick="editProject(this)">
              Edit
            </button>
            <?php if (!empty($p['live_url'])): ?>
              <a href="<?= e($p['live_url']) ?>" target="_blank" class="btn-card-action" title="Open live project in new tab">
                Live &rarr;
              </a>
            <?php endif; ?>
            <button type="button" class="btn-card-action btn-card-delete" onclick="deleteProject(<?= (int)$p['id'] ?>, '<?= e(addslashes($p['title'])) ?>')">
              Delete
            </button>
          </div>
        </div>

      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- ======================================================================
     STUDENT WORK / PROJECTS MODAL POPUP (#projectModal)
     ====================================================================== -->
<div class="admin-modal-backdrop" id="projectModal" role="dialog" aria-modal="true" aria-labelledby="projectModalTitleText">
  <div class="admin-modal-box modal-lg" style="max-width: 840px;" onclick="event.stopPropagation()">

    <div class="admin-modal-header">
      <div style="display:flex;align-items:center;gap:12px;">
        <div class="modal-header-icon">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        </div>
        <div>
          <h2 id="projectModalTitleText" class="admin-modal-title">Add Student Project</h2>
          <p class="admin-modal-subtitle">Manage project portfolio showcase, carousel images, and SEO</p>
        </div>
      </div>
      <button type="button" class="admin-modal-close" onclick="closeAdminModal('projectModal')">&times;</button>
    </div>

    <!-- Modal Tab Bar -->
    <div style="display:flex;border-bottom:1px solid var(--admin-border);background:#F8FAFC;padding:0 20px;">
      <button type="button" class="proj-tab-btn active" onclick="switchProjTab('tabBasics', this)">1. Details &amp; Course</button>
      <button type="button" class="proj-tab-btn" onclick="switchProjTab('tabCover', this)">2. Cover Image</button>
      <button type="button" class="proj-tab-btn" onclick="switchProjTab('tabGallery', this)">3. Multi-Image Carousel</button>
      <button type="button" class="proj-tab-btn" onclick="switchProjTab('tabSeo', this)">4. Case Study &amp; SEO</button>
    </div>

    <form id="projectForm" onsubmit="saveProject(event)">
      <input type="hidden" name="id" id="projId" value="" />
      <input type="hidden" name="csrf_token" value="<?= admin_csrf_token() ?>" />

      <div class="admin-modal-body" style="max-height: 70vh; overflow-y: auto; padding: 22px;">

        <!-- TAB 1: BASIC INFO & COURSE -->
        <div class="proj-tab-pane" id="tabBasics">
          <div class="form-group">
            <label class="form-label">Project Title <span class="required-star">*</span></label>
            <input type="text" name="title" id="projTitle" class="form-control" required placeholder="e.g., Nexus SaaS Dashboard & Mobile Interface" />
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Student Name / Creator <span class="required-star">*</span></label>
              <input type="text" name="student_name" id="projStudent" class="form-control" required placeholder="e.g., Tanvir Hasan" />
            </div>

            <div class="form-group">
              <label class="form-label">Academic Department <span class="required-star">*</span></label>
              <select name="department" id="projDept" class="form-control" required>
                <?php foreach ($departments as $d): ?>
                  <option value="<?= e($d['slug']) ?>"><?= e($d['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Associated Course (Curriculum)</label>
              <select name="course_id" id="projCourseId" class="form-control">
                <option value="">-- Optional / General Portfolio --</option>
                <?php foreach ($courses as $c): ?>
                  <option value="<?= (int)$c['id'] ?>"><?= e($c['title']) ?> (<?= e(ucfirst($c['department'])) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Project Category</label>
              <input type="text" name="category" id="projCategory" class="form-control" placeholder="e.g. UI/UX Design, Web Application, Branding" />
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Live Demo / Portfolio Link (Behance / Web)</label>
              <input type="url" name="live_url" id="projLiveUrl" class="form-control" placeholder="https://..." />
            </div>

            <div class="form-group">
              <label class="form-label">GitHub Source Code URL (Optional)</label>
              <input type="url" name="github_url" id="projGithubUrl" class="form-control" placeholder="https://github.com/..." />
            </div>
          </div>

          <div class="form-grid-3">
            <div class="form-group">
              <label class="form-label">Publication Status</label>
              <select name="status" id="projStatus" class="form-control">
                <option value="published">Published</option>
                <option value="draft">Draft</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Display Sort Order</label>
              <input type="number" name="sort_order" id="projSortOrder" class="form-control" value="0" min="0" />
            </div>

            <div class="form-group" style="display:flex;flex-direction:column;justify-content:center;">
              <label class="form-label">Featured Badge</label>
              <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;margin-top:6px;">
                <input type="checkbox" name="is_featured" id="projFeatured" value="1" style="width:18px;height:18px;accent-color:var(--brand-accent);" />
                <span style="font-size:13px;font-weight:600;color:var(--brand-primary);">Feature on Homepage</span>
              </label>
            </div>
          </div>
        </div>

        <!-- TAB 2: COVER IMAGE (REAL UPLOAD) -->
        <div class="proj-tab-pane" id="tabCover" style="display:none;">
          <div class="form-group">
            <label class="form-label">Primary Cover / Card Thumbnail <span class="required-star">*</span></label>

            <!-- Specification Badge -->
            <div class="upload-spec-badge">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              <span><strong>Recommended:</strong> 1200 &times; 800 px (3:2 or 16:9) &bull; Formats: WebP, JPG, PNG &bull; Max Size: 5 MB</span>
            </div>

            <!-- Dropzone -->
            <div class="upload-dropzone" id="projCoverDropzone">
              <input type="file" id="projCoverFileInput" accept="image/webp,image/jpeg,image/png,image/gif" style="display:none;">
              <div class="upload-dropzone-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              </div>
              <div class="upload-dropzone-title">Drag &amp; drop cover photo here, or <span>browse file</span></div>
              <div class="upload-dropzone-hint">Primary showcase preview seen across website grids</div>
            </div>

            <!-- Preview Card -->
            <div class="upload-preview-card" id="projCoverPreviewCard" style="display:none;">
              <img src="" id="projCoverPreviewThumb" class="upload-preview-thumb" alt="Cover Preview">
              <div class="upload-preview-meta">
                <div class="upload-preview-status">&#10003; Cover Ready</div>
                <div class="upload-preview-url" id="projCoverPreviewUrlText"></div>
              </div>
              <button type="button" class="upload-preview-remove">&times; Remove</button>
            </div>

            <div style="margin-top: 10px;">
              <input type="text" name="cover_image" id="projCoverImage" class="form-control" placeholder="Or paste absolute image URL (https://...)" oninput="updateProjCoverPreviewFromUrl(this.value)">
            </div>
          </div>
        </div>

        <!-- TAB 3: MULTI-IMAGE CAROUSEL GALLERY -->
        <div class="proj-tab-pane" id="tabGallery" style="display:none;">
          <div class="form-group">
            <label class="form-label">Project Carousel Gallery Showcase (Multi-Image)</label>

            <!-- Specification Badge -->
            <div class="upload-spec-badge">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              <span><strong>Recommended:</strong> 1920 &times; 1080 px (16:9) &bull; Formats: WebP, JPG, PNG &bull; Max Size: 5 MB per slide &bull; Batch Upload Supported</span>
            </div>

            <!-- Multi-file Dropzone -->
            <div class="upload-dropzone" id="projGalleryDropzone">
              <input type="file" id="projGalleryFileInput" accept="image/webp,image/jpeg,image/png,image/gif" multiple style="display:none;">
              <div class="upload-dropzone-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
              <div class="upload-dropzone-title">Upload Multiple Showcase Screenshots (<span>Browse Files</span>)</div>
              <div class="upload-dropzone-hint">Select multiple images at once to build an interactive carousel on the project detail page</div>
            </div>

            <!-- Gallery Slides Grid -->
            <div class="project-gallery-manager">
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;font-weight:700;color:var(--brand-primary);">Uploaded Carousel Slides (<span id="gallerySlideCount">0</span>)</span>
                <button type="button" class="btn-card-action" onclick="addManualGallerySlide()" style="font-size:12px;">+ Add by URL</button>
              </div>

              <div class="project-gallery-grid" id="projectGalleryContainer">
                <!-- Dynamically populated carousel slide cards -->
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 4: CASE STUDY & SEO -->
        <div class="proj-tab-pane" id="tabSeo" style="display:none;">
          <div class="form-group">
            <label class="form-label">Brief Summary (Short description on cards)</label>
            <textarea name="brief_description" id="projBriefDesc" class="form-control" rows="2" placeholder="One or two sentences summarizing the project..."></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Full Case Study &amp; Challenge Solution</label>
            <textarea name="description" id="projDesc" class="form-control" rows="4" placeholder="Detailed breakdown of tools used, student objectives, user journey, and results..."></textarea>
          </div>

          <div style="border-top:1px solid var(--admin-border);padding-top:14px;margin-top:14px;">
            <h3 style="font-size:14px;font-weight:700;color:var(--brand-primary);margin-bottom:10px;">Google SERP &amp; OpenGraph SEO Meta</h3>

            <div class="form-group">
              <label class="form-label">SEO Meta Title</label>
              <input type="text" name="meta_title" id="projMetaTitle" class="form-control" placeholder="e.g. Nexus UI/UX Case Study | Creative IT Student Works" />
            </div>

            <div class="form-group">
              <label class="form-label">SEO Meta Description</label>
              <textarea name="meta_description" id="projMetaDesc" class="form-control" rows="2" placeholder="Compelling 150-160 character description for search engines..."></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">SEO Meta Keywords</label>
              <input type="text" name="meta_keywords" id="projMetaKeywords" class="form-control" placeholder="ui ux, dashboard design, figma case study, mobile app" />
            </div>
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" class="btn-admin-secondary" onclick="closeAdminModal('projectModal')">Cancel</button>
        <button type="submit" id="btnSaveProj" class="topbar-btn btn-primary-action">
          Save Project
        </button>
      </div>
    </form>

  </div>
</div>

<style>
.proj-tab-btn {
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  padding: 12px 16px;
  font-size: 13px;
  font-weight: 600;
  color: var(--admin-text-muted);
  cursor: pointer;
  transition: var(--transition-smooth);
}
.proj-tab-btn:hover {
  color: var(--brand-accent);
}
.proj-tab-btn.active {
  color: var(--brand-accent);
  border-bottom-color: var(--brand-accent);
  background: #FFFFFF;
}
</style>

<script>
// Filter card capsules by search, course, or department
function filterProjectCards() {
  const search = (document.getElementById('adminCardSearch').value || '').toLowerCase().trim();
  const course = document.getElementById('adminCourseFilter').value;
  const dept   = document.getElementById('adminDeptFilter').value;

  const cards = document.querySelectorAll('.project-card');
  cards.forEach(card => {
    const cardSearch = card.dataset.search || '';
    const cardCourse = card.dataset.course || '';
    const cardDept   = card.dataset.dept || '';

    const matchSearch = !search || cardSearch.includes(search);
    const matchCourse = !course || cardCourse === course;
    const matchDept   = !dept || cardDept === dept;

    card.style.display = (matchSearch && matchCourse && matchDept) ? '' : 'none';
  });
}

// Modal Tabs Switcher
function switchProjTab(paneId, btn) {
  document.querySelectorAll('.proj-tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.proj-tab-pane').forEach(p => p.style.display = 'none');

  if (btn) btn.classList.add('active');
  const targetPane = document.getElementById(paneId);
  if (targetPane) targetPane.style.display = 'block';
}

// Global gallery slides memory for active modal
let activeGallerySlides = [];

function renderGallerySlides() {
  const container = document.getElementById('projectGalleryContainer');
  const counter = document.getElementById('gallerySlideCount');
  container.innerHTML = '';
  counter.textContent = activeGallerySlides.length;

  if (activeGallerySlides.length === 0) {
    container.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:16px;color:var(--admin-text-muted);font-size:12px;">No carousel screenshots added yet. Upload files above or add image URLs.</div>`;
    return;
  }

  activeGallerySlides.forEach((slide, idx) => {
    const card = document.createElement('div');
    card.className = 'project-gallery-card';
    card.innerHTML = `
      <img src="${slide.image_url}" alt="Slide ${idx + 1}" />
      <button type="button" class="project-gallery-del-btn" onclick="removeGallerySlide(${idx})" title="Remove slide">&times;</button>
      <div class="project-gallery-card-body">
        <input type="text" class="form-control" placeholder="Caption (optional)" value="${slide.caption || ''}" onchange="updateGalleryCaption(${idx}, this.value)" />
      </div>
    `;
    container.appendChild(card);
  });
}

function removeGallerySlide(idx) {
  activeGallerySlides.splice(idx, 1);
  renderGallerySlides();
}

function updateGalleryCaption(idx, val) {
  if (activeGallerySlides[idx]) {
    activeGallerySlides[idx].caption = val;
  }
}

function addManualGallerySlide() {
  const url = prompt('Enter the absolute URL of the project screenshot:');
  if (url && url.trim()) {
    activeGallerySlides.push({
      image_url: url.trim(),
      caption: '',
      image_order: activeGallerySlides.length
    });
    renderGallerySlides();
  }
}

function updateProjCoverPreviewFromUrl(url) {
  url = (url || '').trim();
  const card = document.getElementById('projCoverPreviewCard');
  const thumb = document.getElementById('projCoverPreviewThumb');
  const txt = document.getElementById('projCoverPreviewUrlText');
  if (url) {
    thumb.src = url;
    txt.textContent = url;
    card.style.display = 'flex';
  } else {
    card.style.display = 'none';
  }
}

// Initialize Dropzones on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  // 1. Cover Image Dropzone
  setupAdminDropzone({
    dropzoneEl: document.getElementById('projCoverDropzone'),
    fileInputEl: document.getElementById('projCoverFileInput'),
    urlInputEl: document.getElementById('projCoverImage'),
    previewCardEl: document.getElementById('projCoverPreviewCard'),
    previewThumbEl: document.getElementById('projCoverPreviewThumb'),
    previewUrlEl: document.getElementById('projCoverPreviewUrlText'),
    folder: 'projects'
  });

  // 2. Carousel Multi-Image Dropzone
  setupAdminDropzone({
    dropzoneEl: document.getElementById('projGalleryDropzone'),
    fileInputEl: document.getElementById('projGalleryFileInput'),
    folder: 'projects',
    onUploaded: (result) => {
      const items = result.items || (result.url ? [{ url: result.url }] : []);
      items.forEach(item => {
        const u = item.url || item;
        activeGallerySlides.push({
          image_url: u,
          caption: '',
          image_order: activeGallerySlides.length
        });
      });
      renderGallerySlides();
    }
  });
});

function openNewProjectModal() {
  document.getElementById('projectForm').reset();
  document.getElementById('projId').value = '';
  document.getElementById('projSortOrder').value = '0';
  document.getElementById('projStatus').value = 'published';
  document.getElementById('projFeatured').checked = false;
  document.getElementById('projCoverPreviewCard').style.display = 'none';
  document.getElementById('projectModalTitleText').textContent = 'Add Student Project';

  activeGallerySlides = [];
  renderGallerySlides();

  // Reset to first tab
  switchProjTab('tabBasics', document.querySelector('.proj-tab-btn'));
  openAdminModal('projectModal');
}

async function editProject(btnOrData) {
  const basic = getAdminEntityData(btnOrData, 'project') || btnOrData;
  if (!basic || !basic.id) return;

  // Open modal and show loading state
  openNewProjectModal();
  document.getElementById('projectModalTitleText').textContent = 'Loading: ' + (basic.title || 'Project') + '...';

  try {
    const res = await fetch(`<?= admin_url("api/projects") ?>?action=get-details&id=${basic.id}`);
    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Failed to fetch project details');
    }

    const p = data.project;
    const images = data.images || [];
    const seo = data.seo || {};

    document.getElementById('projId').value          = p.id || '';
    document.getElementById('projTitle').value       = p.title || '';
    document.getElementById('projStudent').value     = p.student_name || '';
    document.getElementById('projDept').value        = p.department || 'graphic';
    document.getElementById('projCourseId').value    = p.course_id || '';
    document.getElementById('projCategory').value    = p.category || '';
    document.getElementById('projLiveUrl').value     = p.live_url || '';
    document.getElementById('projGithubUrl').value   = p.github_url || '';
    document.getElementById('projStatus').value      = p.status || 'published';
    document.getElementById('projSortOrder').value   = p.sort_order ?? 0;
    document.getElementById('projFeatured').checked  = !!parseInt(p.is_featured || p.featured || 0);

    // Cover
    document.getElementById('projCoverImage').value  = p.cover_image || '';
    updateProjCoverPreviewFromUrl(p.cover_image);

    // Descriptions
    document.getElementById('projBriefDesc').value   = p.brief_description || p.short_description || '';
    document.getElementById('projDesc').value        = p.description || '';

    // SEO
    document.getElementById('projMetaTitle').value    = seo.meta_title || '';
    document.getElementById('projMetaDesc').value     = seo.meta_description || '';
    document.getElementById('projMetaKeywords').value = seo.meta_keywords || '';

    // Gallery slides
    activeGallerySlides = images.map(img => ({
      image_url: img.image_url,
      caption: img.caption || '',
      image_order: img.image_order ?? 0
    }));
    renderGallerySlides();

    document.getElementById('projectModalTitleText').textContent = 'Edit: ' + (p.title || 'Project');
  } catch (err) {
    showAdminToast('error', err.message);
  }
}

async function saveProject(e) {
  e.preventDefault();
  const form    = document.getElementById('projectForm');
  const btn     = document.getElementById('btnSaveProj');
  const title   = document.getElementById('projTitle').value.trim();
  const student = document.getElementById('projStudent').value.trim();

  if (!title) {
    switchProjTab('tabBasics', document.querySelectorAll('.proj-tab-btn')[0]);
    showAdminToast('error', 'Project title is required.');
    return;
  }
  if (!student) {
    switchProjTab('tabBasics', document.querySelectorAll('.proj-tab-btn')[0]);
    showAdminToast('error', 'Student name is required.');
    return;
  }

  const payload = Object.fromEntries(new FormData(form));
  // Attach carousel gallery array
  payload.gallery_images = activeGallerySlides;

  try {
    await executeAdminApi('<?= admin_url("api/projects/save") ?>', payload, btn);
    showAdminToast('success', 'Student project & gallery saved successfully!');
    closeAdminModal('projectModal');
    setTimeout(() => location.reload(), 600);
  } catch (err) {}
}

async function deleteProject(id, title) {
  const confirmed = await confirmAdminAction({
    title: 'Delete Student Project?',
    message: `Are you sure you want to remove "${title}" and all its gallery screenshots? This cannot be undone.`,
    confirmText: 'Yes, Delete',
    isDanger: true
  });
  if (!confirmed) return;

  try {
    await executeAdminApi('<?= admin_url("api/projects/delete") ?>', { id, csrf_token: '<?= admin_csrf_token() ?>' });
    showAdminToast('success', 'Project deleted successfully.');
    const card = document.getElementById(`projCard_${id}`);
    if (card) {
      card.style.transition = 'opacity 0.25s, transform 0.25s';
      card.style.opacity = '0';
      card.style.transform = 'scale(0.9)';
      setTimeout(() => card.remove(), 250);
    }
  } catch (err) {}
}
</script>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
