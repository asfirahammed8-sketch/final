<?php
/**
 * DreamIT Admin Console - Progressive Multi-Step Course Wizard (Production Grade)
 * 4-Step Guided Form: Core & Hero, Curriculum, Facilities & Audience, Tools & SEO
 */
require_once ADMIN_PATH . '/includes/auth-guard.php';

$db = Database::getConnection();

$courseId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$isEditing = ($courseId !== null);

$course = null;
if ($isEditing) {
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $courseId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$course) {
        header('Location: ' . admin_url('courses'));
        exit;
    }
}

$pageTitle = $isEditing ? 'Edit Course: ' . $course['title'] : 'Add New Course';
$pageHeadline = $isEditing ? 'Edit Course Curriculum' : 'Course Creation Wizard';
$activeMenu = $isEditing ? 'courses' : 'courses_create';

$includeWizardCss = true;
$includeWizardJs = true;

// Departments for dropdown
$departments = $db->query("SELECT slug, name FROM departments WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

// Parse JSON fields
$deliveryModes = !empty($course['course_delivery_modes']) ? @json_decode($course['course_delivery_modes'], true) : ['Offline (Campus Labs)', 'Online Live Batch', 'Lifetime Mentor Support'];
$facilities = !empty($course['course_facilities']) ? @json_decode($course['course_facilities'], true) : ['High-End Lab Access', '24/7 Mentor Support', 'Recorded Class Archive', 'Job Placement Support'];
$highlights = !empty($course['curriculum_highlights']) ? @json_decode($course['curriculum_highlights'], true) : ['Industry-aligned practical projects', '1-on-1 mentor code & portfolio review', 'Freelancing & marketplace onboarding'];
$careerPaths = !empty($course['career_paths']) ? @json_decode($course['career_paths'], true) : ['Professional Specialist', 'Freelancer / Remote Contractor', 'Agency Creative Lead'];
$prerequisites = !empty($course['prerequisites']) ? @json_decode($course['prerequisites'], true) : ['Basic Computer Operating Knowledge', 'Dedication & enthusiasm to learn'];
$softwaresLearned = !empty($course['softwares_learned']) ? @json_decode($course['softwares_learned'], true) : [];

// Extract software names for checkbox selection
$selectedSoftwareNames = [];
if (is_array($softwaresLearned)) {
    foreach ($softwaresLearned as $sw) {
        $selectedSoftwareNames[] = is_array($sw) ? ($sw['name'] ?? '') : (string)$sw;
    }
}

// Master Tools Catalog with Category & Color
$masterTools = [
    'Photoshop'     => ['cat' => 'Graphics', 'color' => '#31a8ff'],
    'Illustrator'   => ['cat' => 'Vector',   'color' => '#ff9a00'],
    'InDesign'      => ['cat' => 'Print',    'color' => '#ff3366'],
    'Figma'         => ['cat' => 'UI/UX',    'color' => '#a259ff'],
    'XD'            => ['cat' => 'UI/UX',    'color' => '#ff61f6'],
    'After Effects' => ['cat' => 'Motion',   'color' => '#9999ff'],
    'Premiere'      => ['cat' => 'Video',    'color' => '#ea77ff'],
    'Blender'       => ['cat' => '3D',       'color' => '#ea7600'],
    'Cinema 4D'     => ['cat' => '3D',       'color' => '#00b4d8'],
    'VS Code'       => ['cat' => 'Editor',   'color' => '#007acc'],
    'React'         => ['cat' => 'Frontend', 'color' => '#61dafb'],
    'Node'          => ['cat' => 'Backend',  'color' => '#22c55e'],
    'PHP'           => ['cat' => 'Backend',  'color' => '#777bb4'],
    'Python'        => ['cat' => 'AI / ML',  'color' => '#ffd43b'],
    'MongoDB'       => ['cat' => 'Database', 'color' => '#13aa52'],
    'GitHub'        => ['cat' => 'DevOps',   'color' => '#ffffff'],
    'Docker'        => ['cat' => 'DevOps',   'color' => '#0db7ed'],
    'Canva'         => ['cat' => 'Design',   'color' => '#00c4cc'],
];

require_once ADMIN_PATH . '/includes/header.php';
?>

<div class="wizard-container">

  <!-- 1. Step Progress Tracker Bar -->
  <div class="wizard-progress-bar">
    <div class="wizard-step-node is-active" data-step="1">
      <div class="step-circle">1</div>
      <span class="step-label">Basic &amp; Hero Media</span>
    </div>

    <div class="wizard-step-node" data-step="2">
      <div class="step-circle">2</div>
      <span class="step-label">Curriculum &amp; Career</span>
    </div>

    <div class="wizard-step-node" data-step="3">
      <div class="step-circle">3</div>
      <span class="step-label">Facilities &amp; Formats</span>
    </div>

    <div class="wizard-step-node" data-step="4">
      <div class="step-circle">4</div>
      <span class="step-label">Software Tools &amp; SEO</span>
    </div>
  </div>

  <form id="courseWizardForm" onsubmit="submitCourseWizard(event)">
    <input type="hidden" name="id" value="<?= $isEditing ? $course['id'] : '' ?>">

    <!-- ============================================================
         STEP 1: BASIC INFO & HERO MEDIA
    ============================================================ -->
    <div class="wizard-step-panel active-panel" id="wizardStepPanel1">
      <div class="wizard-panel-card">
        <h2 class="panel-header-title">Step 1: Course Identity &amp; Visual Hero</h2>
        <p class="panel-header-subtitle">Set the title, clean URL slug, department category, pricing breakdown, and media preview.</p>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="courseTitleInput">Course Title <span style="color:var(--brand-primary)">*</span></label>
            <input type="text" id="courseTitleInput" name="title" class="form-control" placeholder="e.g. Professional Graphic Design" required value="<?= e($course['title'] ?? '') ?>" />
          </div>

          <div class="form-group">
            <label class="form-label" for="courseSlugInput">URL Slug <span style="color:var(--brand-primary)">*</span></label>
            <input type="text" id="courseSlugInput" name="slug" class="form-control" placeholder="e.g. professional-graphic-design" required value="<?= e($course['slug'] ?? '') ?>" />
            <div class="form-hint">Public URL: <code>/courses/<span id="slugHintPreview"><?= e($course['slug'] ?? 'your-slug') ?></span>/</code></div>
          </div>
        </div>

        <div class="form-grid-3">
          <div class="form-group">
            <label class="form-label" for="courseDeptSelect">Department Category <span style="color:var(--brand-primary)">*</span></label>
            <select id="courseDeptSelect" name="department" class="form-control" required>
              <option value="">Select Department...</option>
              <?php foreach ($departments as $d):
                $isSelected = ($course['department'] ?? '') === $d['slug'];
              ?>
                <option value="<?= e($d['slug']) ?>" <?= $isSelected ? 'selected' : '' ?>><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="coursePriceInput">Regular Tuition Fee (BDT) <span style="color:var(--brand-primary)">*</span></label>
            <input type="number" id="coursePriceInput" name="price" class="form-control" placeholder="30000" required value="<?= e($course['price'] ?? '') ?>" oninput="calcDiscountBadge()" />
          </div>

          <div class="form-group">
            <label class="form-label" for="courseDiscountPriceInput">Discounted Live Fee (BDT)</label>
            <input type="number" id="courseDiscountPriceInput" name="discount_price" class="form-control" placeholder="25000" value="<?= e($course['discount_price'] ?? '') ?>" oninput="calcDiscountBadge()" />
            <div id="discountCalculationChip" style="font-size:11.5px;color:var(--brand-emerald);font-weight:700;margin-top:4px;"></div>
          </div>
        </div>

        <div class="form-grid-3">
          <div class="form-group">
            <label class="form-label">Course Duration</label>
            <input type="text" name="duration" class="form-control" placeholder="4 Months" value="<?= e($course['duration'] ?? '4 Months') ?>" />
          </div>

          <div class="form-group">
            <label class="form-label">Lectures &amp; Lab Sessions</label>
            <input type="number" name="total_classes" class="form-control" placeholder="32" value="<?= e($course['total_classes'] ?? '32') ?>" />
          </div>

          <div class="form-group">
            <label class="form-label">Real Client Projects</label>
            <input type="number" name="total_projects" class="form-control" placeholder="10" value="<?= e($course['total_projects'] ?? '10') ?>" />
          </div>
        </div>

        <div class="form-grid-2">
          <!-- Thumbnail with Real Upload & Live Preview -->
          <div class="form-group">
            <label class="form-label">Course Thumbnail Image</label>
            <div class="upload-spec-badge">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              <span><strong>Recommended:</strong> 800 &times; 500 px (16:10) &bull; Formats: WebP, JPG, PNG &bull; Max Size: 3 MB</span>
            </div>

            <!-- Dropzone -->
            <div class="upload-dropzone" id="wizardThumbDropzone" style="padding:16px;">
              <input type="file" id="wizardThumbFileInput" accept="image/webp,image/jpeg,image/png,image/gif" style="display:none;">
              <div class="upload-dropzone-icon" style="width:38px;height:38px;margin-bottom:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              </div>
              <div class="upload-dropzone-title" style="font-size:13px;">Drop course thumbnail, or <span>browse file</span></div>
              <div class="upload-dropzone-hint">Primary card image visible on homepage and courses catalog</div>
            </div>

            <!-- Preview Card -->
            <div class="upload-preview-card" id="wizardThumbPreviewCard" style="<?= !empty($course['thumbnail_url']) ? 'display:flex;' : 'display:none;' ?>margin-top:10px;">
              <img src="<?= e($course['thumbnail_url'] ?? '') ?>" id="wizardThumbPreviewImg" class="upload-preview-thumb" alt="Thumbnail Preview" style="width:84px;height:56px;object-fit:cover;">
              <div class="upload-preview-meta">
                <div class="upload-preview-status">&#10003; Thumbnail Selected</div>
                <div class="upload-preview-url" id="wizardThumbPreviewUrlText"><?= e($course['thumbnail_url'] ?? '') ?></div>
              </div>
              <button type="button" class="upload-preview-remove">&times; Remove</button>
            </div>

            <div style="margin-top:8px;">
              <input type="url" id="thumbUrlInput" name="thumbnail_url" class="form-control" placeholder="Or enter direct image URL (https://...)" value="<?= e($course['thumbnail_url'] ?? '') ?>" oninput="updateMediaThumbnail(this.value)" />
            </div>
          </div>

          <!-- Video URL with Live Frame -->
          <div class="form-group">
            <label class="form-label">Course Video Trailer URL (YouTube / Vimeo)</label>
            <input type="url" id="videoUrlInput" name="video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=..." value="<?= e($course['video_url'] ?? '') ?>" oninput="updateVideoTrailerPreview(this.value)" />
            <div class="media-preview-box" id="videoPreviewBox">
              <div class="media-preview-placeholder" id="videoPlaceholder">
                <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                <span>Interactive Video Trailer Preview</span>
              </div>
              <iframe id="videoPreviewIframe" src="" style="width:100%;height:100%;border:none;display:none;" allowfullscreen></iframe>
            </div>
          </div>
        </div>

      </div>

      <div class="wizard-footer-nav">
        <a href="<?= admin_url('courses') ?>" class="btn-wizard-nav btn-wizard-prev">Cancel &amp; Return</a>
        <button type="button" class="btn-wizard-nav btn-wizard-next">Next: Curriculum &amp; Career &rarr;</button>
      </div>
    </div>

    <!-- ============================================================
         STEP 2: CURRICULUM & CAREER
    ============================================================ -->
    <div class="wizard-step-panel" id="wizardStepPanel2">
      <div class="wizard-panel-card">
        <h2 class="panel-header-title">Step 2: Course Curriculum &amp; Career Opportunities</h2>
        <p class="panel-header-subtitle">Explain the depth of the syllabus, hands-on methodology, and post-graduation career opportunities.</p>

        <div class="form-group">
          <label class="form-label">Comprehensive Course Overview</label>
          <textarea name="overview" class="form-control" rows="5" placeholder="Detail the technical depth, practical assignments, and industry relevance of this course..."><?= e($course['overview'] ?? '') ?></textarea>
        </div>

        <div class="form-grid-2">
          <!-- Highlights Repeater -->
          <div class="form-group">
            <label class="form-label">Curriculum Highlights (Bullet Points)</label>
            <div class="repeater-container" id="highlightsContainer" data-input-name="curriculum_highlights">
              <?php if (!empty($highlights)): ?>
                <?php foreach ($highlights as $hl): ?>
                  <div class="repeater-item-row">
                    <input type="text" class="form-control" name="curriculum_highlights[]" value="<?= e($hl) ?>" />
                    <button type="button" class="btn-remove-repeater" onclick="this.parentElement.remove()" title="Delete item">&times;</button>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <button type="button" class="btn-add-repeater" data-add-repeater="highlightsContainer" data-placeholder="e.g. Industry standard UI/UX workflow">+ Add Highlight Point</button>
          </div>

          <!-- Career Paths Repeater -->
          <div class="form-group">
            <label class="form-label">Career Opportunities (Job Titles)</label>
            <div class="repeater-container" id="careerContainer" data-input-name="career_paths">
              <?php if (!empty($careerPaths)): ?>
                <?php foreach ($careerPaths as $cp): ?>
                  <div class="repeater-item-row">
                    <input type="text" class="form-control" name="career_paths[]" value="<?= e($cp) ?>" />
                    <button type="button" class="btn-remove-repeater" onclick="this.parentElement.remove()" title="Delete item">&times;</button>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <button type="button" class="btn-add-repeater" data-add-repeater="careerContainer" data-placeholder="e.g. Senior Frontend Engineer">+ Add Career Role</button>
          </div>
        </div>

      </div>

      <div class="wizard-footer-nav">
        <button type="button" class="btn-wizard-nav btn-wizard-prev">&larr; Back to Step 1</button>
        <button type="button" class="btn-wizard-nav btn-wizard-next">Next: Facilities &amp; Formats &rarr;</button>
      </div>
    </div>

    <!-- ============================================================
         STEP 3: FACILITIES & FORMATS
    ============================================================ -->
    <div class="wizard-step-panel" id="wizardStepPanel3">
      <div class="wizard-panel-card">
        <h2 class="panel-header-title">Step 3: Learning Formats, Facilities &amp; Prerequisites</h2>
        <p class="panel-header-subtitle">Declare attendance modalities, student computer lab access, and entry prerequisites.</p>

        <div class="form-group">
          <label class="form-label">Course Delivery Modalities</label>
          <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:8px;">
            <?php
            $availableModes = [
                'Offline (Campus Labs)', 'Online Live Batch', 'Lifetime Mentor Support',
                'Admission Going On', 'Lab Based', 'Theory'
            ];
            foreach ($availableModes as $m):
              $isChecked = in_array($m, $deliveryModes);
            ?>
              <label style="display:inline-flex;align-items:center;gap:8px;font-size:13.5px;color:var(--admin-text-main);background:var(--admin-bg);padding:10px 16px;border-radius:8px;border:1px solid var(--admin-border);cursor:pointer;transition:var(--transition-smooth);">
                <input type="checkbox" name="course_delivery_modes[]" value="<?= e($m) ?>" <?= $isChecked ? 'checked' : '' ?> style="accent-color:var(--brand-primary);transform:scale(1.15);" />
                <span><?= e($m) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-grid-2">
          <!-- Facilities Repeater -->
          <div class="form-group">
            <label class="form-label">Included Facilities &amp; Perks</label>
            <div class="repeater-container" id="facilitiesContainer" data-input-name="course_facilities">
              <?php foreach ($facilities as $fac): ?>
                <div class="repeater-item-row">
                  <input type="text" class="form-control" name="course_facilities[]" value="<?= e($fac) ?>" />
                  <button type="button" class="btn-remove-repeater" onclick="this.parentElement.remove()">&times;</button>
                </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="btn-add-repeater" data-add-repeater="facilitiesContainer" data-placeholder="e.g. 24/7 Mentor Review Lab">+ Add Facility</button>
          </div>

          <!-- Prerequisites Repeater -->
          <div class="form-group">
            <label class="form-label">Student Prerequisites</label>
            <div class="repeater-container" id="prereqContainer" data-input-name="prerequisites">
              <?php foreach ($prerequisites as $pr): ?>
                <div class="repeater-item-row">
                  <input type="text" class="form-control" name="prerequisites[]" value="<?= e($pr) ?>" />
                  <button type="button" class="btn-remove-repeater" onclick="this.parentElement.remove()">&times;</button>
                </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="btn-add-repeater" data-add-repeater="prereqContainer" data-placeholder="e.g. Basic Computer Navigation">+ Add Prerequisite</button>
          </div>
        </div>

      </div>

      <div class="wizard-footer-nav">
        <button type="button" class="btn-wizard-nav btn-wizard-prev">&larr; Back to Step 2</button>
        <button type="button" class="btn-wizard-nav btn-wizard-next">Next: Software Tools &amp; SEO &rarr;</button>
      </div>
    </div>

    <!-- ============================================================
         STEP 4: SOFTWARE TOOLS & SEO ENGINE
    ============================================================ -->
    <div class="wizard-step-panel" id="wizardStepPanel4">
      <div class="wizard-panel-card">
        <h2 class="panel-header-title">Step 4: Software Tools Master &amp; SEO Engine</h2>
        <p class="panel-header-subtitle">Select industry software tools and optimize meta descriptions for search engines.</p>

        <!-- Software Selection with authentic vector illumination -->
        <div class="form-group">
          <label class="form-label">Industry Software Mastered (Click to Select / Deselect)</label>
          <div class="software-tools-selector">
            <?php foreach ($masterTools as $toolName => $meta):
              $isToolSelected = in_array($toolName, $selectedSoftwareNames);
            ?>
              <div class="tool-checkbox-card <?= $isToolSelected ? 'is-selected' : '' ?>">
                <input type="checkbox" name="softwares_learned[]" value="<?= e($toolName) ?>" <?= $isToolSelected ? 'checked' : '' ?> />
                <div class="tool-icon-wrapper" style="color:<?= $meta['color'] ?>;">
                  <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                </div>
                <span class="tool-name-label"><?= e($toolName) ?></span>
                <span class="tool-cat-label"><?= e($meta['cat']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <hr style="border:0;border-top:1px solid var(--admin-border);margin:30px 0;" />

        <!-- SEO Fields with Character Counter -->
        <div class="form-group">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <label class="form-label" for="seoTitleInput" style="margin-bottom:0;">SEO Meta Title</label>
            <span id="seoTitleCounter" style="font-size:12px;color:var(--admin-text-dim);">0 / 60 characters</span>
          </div>
          <input type="text" id="seoTitleInput" name="seo_title" class="form-control" placeholder="e.g. Professional Graphic Design Course in Bangladesh" value="<?= e($course['seo_title'] ?? '') ?>" oninput="updateSeoCounters()" />
        </div>

        <div class="form-group">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <label class="form-label" for="seoDescInput" style="margin-bottom:0;">SEO Meta Description</label>
            <span id="seoDescCounter" style="font-size:12px;color:var(--admin-text-dim);">0 / 160 characters</span>
          </div>
          <textarea id="seoDescInput" name="meta_description" class="form-control" rows="3" placeholder="Enroll in this course with practical lab training, expert mentor guidance, real-world portfolio projects, and lifetime placement assistance." oninput="updateSeoCounters()"><?= e($course['meta_description'] ?? '') ?></textarea>
        </div>

        <!-- Live Google SERP Simulation -->
        <div class="form-group">
          <label class="form-label">Live Google Search Snippet Preview</label>
          <div class="serp-preview-box">
            <div class="serp-url-crumb" id="serpUrlPreview">https://dreamit.com/courses/<?= e($course['slug'] ?? 'course-slug') ?>/</div>
            <div class="serp-title-preview" id="serpTitlePreview"><?= e($course['seo_title'] ?? $course['title'] ?? 'Course Title Preview') ?> | Creative IT Institute</div>
            <div class="serp-desc-preview" id="serpDescPreview"><?= e($course['meta_description'] ?? 'Enroll in this course with practical lab training, expert mentor guidance, real-world portfolio projects, and lifetime placement assistance.') ?></div>
          </div>
        </div>

        <!-- Publish Status Dropdown -->
        <div class="form-group" style="margin-top:24px;">
          <label class="form-label">Publishing Status</label>
          <select name="status" class="admin-select" style="min-width:240px;">
            <option value="active" <?= ($course['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Published Live on Website)</option>
            <option value="draft" <?= ($course['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft (Hidden in CMS)</option>
          </select>
        </div>

      </div>

      <div class="wizard-footer-nav">
        <button type="button" class="btn-wizard-nav btn-wizard-prev">&larr; Back to Step 3</button>
        <button type="submit" id="btnSubmitWizard" class="btn-wizard-nav btn-wizard-submit">
          <?= $isEditing ? 'Save &amp; Update Course' : 'Publish Course Live' ?>
        </button>
      </div>
    </div>

  </form>

</div>

<script>
function calcDiscountBadge() {
  const regPrice = parseFloat(document.getElementById('coursePriceInput')?.value || 0);
  const discPrice = parseFloat(document.getElementById('courseDiscountPriceInput')?.value || 0);
  const chip = document.getElementById('discountCalculationChip');

  if (chip && regPrice > 0 && discPrice > 0 && discPrice < regPrice) {
    const diff = regPrice - discPrice;
    const pct = Math.round((diff / regPrice) * 100);
    chip.textContent = `⚡ Saves ${pct}% (৳ ${diff.toLocaleString()} off regular tuition)`;
  } else if (chip) {
    chip.textContent = '';
  }
}

function updateMediaThumbnail(url) {
  const img = document.getElementById('thumbPreviewImg');
  const ph = document.getElementById('thumbPlaceholder');
  if (url && url.startsWith('http')) {
    if (img) {
      img.src = url;
      img.style.display = 'block';
    }
    if (ph) ph.style.display = 'none';
  } else {
    if (img) img.style.display = 'none';
    if (ph) ph.style.display = 'flex';
  }
}

function updateVideoTrailerPreview(url) {
  const iframe = document.getElementById('videoPreviewIframe');
  const ph = document.getElementById('videoPlaceholder');

  if (url && (url.includes('youtube.com') || url.includes('youtu.be') || url.includes('vimeo.com'))) {
    let embedUrl = '';
    if (url.includes('youtube.com/watch?v=')) {
      embedUrl = url.replace('watch?v=', 'embed/').split('&')[0];
    } else if (url.includes('youtu.be/')) {
      embedUrl = 'https://www.youtube.com/embed/' + url.split('youtu.be/')[1].split('?')[0];
    }
    if (iframe && embedUrl) {
      iframe.src = embedUrl;
      iframe.style.display = 'block';
      if (ph) ph.style.display = 'none';
      return;
    }
  }
  if (iframe) iframe.style.display = 'none';
  if (ph) ph.style.display = 'flex';
}

function updateSeoCounters() {
  const t = document.getElementById('seoTitleInput')?.value || '';
  const d = document.getElementById('seoDescInput')?.value || '';
  const tc = document.getElementById('seoTitleCounter');
  const dc = document.getElementById('seoDescCounter');

  if (tc) {
    tc.textContent = `${t.length} / 60 characters`;
    tc.style.color = t.length > 60 ? '#fb7185' : 'var(--admin-text-dim)';
  }
  if (dc) {
    dc.textContent = `${d.length} / 160 characters`;
    dc.style.color = d.length > 160 ? '#fb7185' : 'var(--admin-text-dim)';
  }
}

async function submitCourseWizard(e) {
  e.preventDefault();
  const form = document.getElementById('courseWizardForm');
  const btn = document.getElementById('btnSubmitWizard');

  // Collect FormData
  const formData = new FormData(form);

  // Transform to JSON
  const payload = {};
  formData.forEach((val, key) => {
    if (key.endsWith('[]')) {
      const cleanKey = key.slice(0, -2);
      if (!payload[cleanKey]) payload[cleanKey] = [];
      payload[cleanKey].push(val);
    } else {
      payload[key] = val;
    }
  });

  try {
    const res = await executeAdminApi('<?= admin_url("api/courses/save") ?>', payload, btn);
    showAdminToast('success', res.message || 'Course saved successfully!');
    setTimeout(() => {
      window.location.href = '<?= admin_url("courses") ?>';
    }, 1000);
  } catch (err) {}
}

document.addEventListener('DOMContentLoaded', () => {
  calcDiscountBadge();
  updateSeoCounters();

  setupAdminDropzone({
    dropzoneEl: document.getElementById('wizardThumbDropzone'),
    fileInputEl: document.getElementById('wizardThumbFileInput'),
    urlInputEl: document.getElementById('thumbUrlInput'),
    previewCardEl: document.getElementById('wizardThumbPreviewCard'),
    previewThumbEl: document.getElementById('wizardThumbPreviewImg'),
    previewUrlEl: document.getElementById('wizardThumbPreviewUrlText'),
    folder: 'courses'
  });

  const thumbInput = document.getElementById('thumbUrlInput');
  if (thumbInput && thumbInput.value) {
    updateMediaThumbnail(thumbInput.value);
  }
});
</script>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
