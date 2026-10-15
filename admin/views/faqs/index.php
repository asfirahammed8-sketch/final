<?php
/**
 * DreamIT Admin Console - FAQ Management Box View (NO TABLES)
 */
require_once ADMIN_PATH . '/includes/auth-guard.php';

$pageTitle = 'FAQ Management';
$pageHeadline = 'Institutional & Course FAQs';
$activeMenu = 'faqs';

$db = Database::getConnection();

// Fetch FAQs with course title
$stmt = $db->query("
    SELECT f.*, c.title AS course_title
    FROM faqs f
    LEFT JOIN courses c ON f.course_id = c.id
    ORDER BY f.sort_order ASC, f.id DESC
");
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Courses for assignment dropdown
$courses = $db->query("SELECT id, title FROM courses ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

// Distinct Categories
$categoriesList = ['general', 'admission', 'career', 'facility', 'graphic', 'web', 'digital', 'animation', 'cyber', 'payment', 'support', 'labs'];
$dbCats = $db->query("SELECT DISTINCT category FROM faqs WHERE category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_COLUMN);
$allCategories = array_unique(array_merge($categoriesList, $dbCats));

require_once ADMIN_PATH . '/includes/header.php';
?>

<!-- Filter & Control Bar -->
<div class="control-filter-bar">
  <div class="search-input-wrapper">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="adminCardSearch" class="admin-search-input" placeholder="Search questions or answers..." />
  </div>

  <div class="filter-actions-group">
    <select id="adminCategoryFilter" class="admin-select" aria-label="Filter by category">
      <option value="">All Categories</option>
      <?php foreach ($allCategories as $catItem): ?>
        <option value="<?= e(strtolower($catItem)) ?>"><?= ucfirst(e($catItem)) ?></option>
      <?php endforeach; ?>
    </select>

    <button type="button" class="topbar-btn btn-primary-action" onclick="openFaqModal()">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="12" y2="12"/></svg>
      <span>+ Add FAQ</span>
    </button>
  </div>
</div>

<!-- FAQ Cards Grid (NO TABLES) -->
<div class="card-capsule-grid" style="grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));">
  <?php foreach ($faqs as $f):
    $isActive = (bool)$f['is_active'];
    $cat = strtolower($f['category'] ?? 'general');
  ?>
    <article class="entity-card-capsule" data-id="<?= $f['id'] ?>" data-category="<?= e($cat) ?>">
      <div class="card-content-body">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
          <span class="category-tag-pill"><?= ucfirst(e($cat)) ?></span>
          <?php if (!empty($f['course_title'])): ?>
            <span class="meta-chip" style="color:var(--brand-accent);">&#128218; <?= e($f['course_title']) ?></span>
          <?php endif; ?>
        </div>

        <h2 class="card-entity-title" style="font-size:15px;margin-bottom:8px;"><?= e($f['question']) ?></h2>

        <p style="font-size:13px;color:var(--admin-text-muted);display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:12px;">
          <?= e($f['answer']) ?>
        </p>

        <div class="card-meta-chips-row" style="margin-top:auto;padding-top:10px;border-top:1px solid var(--admin-border);">
          <span class="meta-chip">&#8645; Sort: <?= (int)$f['sort_order'] ?></span>
          <span class="status-pill <?= $isActive ? 'status-active' : 'status-draft' ?>" id="faqBadge<?= $f['id'] ?>" style="margin-left:auto;">
            <?= $isActive ? 'Active' : 'Inactive' ?>
          </span>
        </div>
      </div>

      <div class="card-action-footer">
        <div class="quick-switch-wrapper">
          <label class="switch">
            <input type="checkbox" <?= $isActive ? 'checked' : '' ?> onchange="toggleFaq(<?= $f['id'] ?>, this)" />
            <span class="slider"></span>
          </label>
        </div>

        <div class="card-btn-group">
          <button type="button" class="btn-card-action"
            data-faq='<?= htmlspecialchars(json_encode([
              'id'         => (int)$f['id'],
              'course_id'  => $f['course_id'] ? (int)$f['course_id'] : null,
              'category'   => $f['category'] ?? 'general',
              'question'   => $f['question'] ?? '',
              'answer'     => $f['answer'] ?? '',
              'sort_order' => (int)$f['sort_order'],
              'is_active'  => (int)$f['is_active']
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>'
            onclick="editFaq(this)">
            Edit
          </button>
          <button type="button" class="btn-card-action btn-card-delete" onclick="deleteFaq(<?= $f['id'] ?>)">
            Delete
          </button>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<!-- FAQ Modal (Unified Accessible Popup System) -->
<div id="faqModal" class="admin-modal-backdrop" role="dialog" aria-modal="true">
  <div class="admin-modal-box" onclick="event.stopPropagation()">
    <div class="admin-modal-header">
      <h2 class="admin-modal-title">
        <span class="admin-modal-title-icon">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </span>
        <span id="faqModalTitle">Add FAQ Question</span>
      </h2>
      <button type="button" class="admin-modal-close" onclick="closeAdminModal('faqModal')" aria-label="Close modal">&times;</button>
    </div>

    <form id="faqForm" onsubmit="saveFaq(event)">
      <div class="admin-modal-body">
        <input type="hidden" name="id" id="faqId" />

        <div class="form-group">
          <label class="form-label" for="faqQ">Question <span class="required">*</span></label>
          <input type="text" name="question" id="faqQ" class="form-control" required placeholder="e.g. Do you provide job placement support?" minlength="5" />
        </div>

        <div class="form-group">
          <label class="form-label" for="faqA">Answer <span class="required">*</span></label>
          <textarea name="answer" id="faqA" class="form-control" rows="4" required placeholder="Detailed professional response..." minlength="10"></textarea>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="faqCat">Category</label>
            <select name="category" id="faqCat" class="admin-select" style="width:100%;">
              <?php foreach ($allCategories as $catItem): ?>
                <option value="<?= e(strtolower($catItem)) ?>"><?= ucfirst(e($catItem)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="faqCourseId">Associate with Course (Optional)</label>
            <select name="course_id" id="faqCourseId" class="admin-select" style="width:100%;">
              <option value="">Site-wide (All Courses)</option>
              <?php foreach ($courses as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="faqOrder">Display Sort Order</label>
          <input type="number" name="sort_order" id="faqOrder" class="form-control" value="0" />
        </div>
      </div>

      <div class="admin-modal-footer">
        <button type="button" class="btn-admin-secondary" onclick="closeAdminModal('faqModal')">Cancel</button>
        <button type="submit" class="btn-admin-primary" id="btnSaveFaq">Save FAQ</button>
      </div>
    </form>
  </div>
</div>

<script>
function openFaqModal() {
  document.getElementById('faqForm').reset();
  document.getElementById('faqId').value = '';
  document.getElementById('faqModalTitle').textContent = 'Add FAQ Question';
  openAdminModal('faqModal');
}

function closeFaqModal() {
  closeAdminModal('faqModal');
}

function editFaq(btnOrData) {
  const f = getAdminEntityData(btnOrData, 'faq') || btnOrData;
  if (!f) return;

  document.getElementById('faqId').value = f.id;
  document.getElementById('faqQ').value = f.question || '';
  document.getElementById('faqA').value = f.answer || '';
  document.getElementById('faqCat').value = f.category || 'general';
  document.getElementById('faqCourseId').value = f.course_id || '';
  document.getElementById('faqOrder').value = f.sort_order || 0;
  document.getElementById('faqModalTitle').textContent = 'Edit FAQ Question';
  openAdminModal('faqModal');
}

async function saveFaq(e) {
  e.preventDefault();
  const form = document.getElementById('faqForm');
  const btn  = document.getElementById('btnSaveFaq');
  const qVal = document.getElementById('faqQ').value.trim();
  const aVal = document.getElementById('faqA').value.trim();

  if (qVal.length < 5) {
    showAdminToast('error', 'Question must be at least 5 characters.');
    return;
  }
  if (aVal.length < 10) {
    showAdminToast('error', 'Answer must be at least 10 characters.');
    return;
  }

  const payload = Object.fromEntries(new FormData(form));

  try {
    await executeAdminApi('<?= admin_url("api/faqs/save") ?>', payload, btn);
    showAdminToast('success', 'FAQ saved successfully!');
    closeAdminModal('faqModal');
    setTimeout(() => location.reload(), 600);
  } catch (err) {}
}

async function toggleFaq(id, switchEl) {
  const newActive = switchEl.checked ? 1 : 0;
  const badge = document.getElementById(`faqBadge${id}`);

  try {
    await executeAdminApi('<?= admin_url("api/faqs/toggle") ?>', { id, is_active: newActive });
    if (badge) {
      badge.textContent = newActive ? 'Active' : 'Inactive';
      badge.className = `status-pill ${newActive ? 'status-active' : 'status-draft'}`;
    }
    showAdminToast('success', 'FAQ visibility updated');
  } catch (err) {
    switchEl.checked = !switchEl.checked;
  }
}

async function deleteFaq(id) {
  const confirmed = await confirmAdminAction({
    title: 'Delete FAQ Question?',
    message: 'Are you sure you want to permanently delete this FAQ item? This action cannot be reversed.',
    confirmText: 'Yes, Delete FAQ',
    isDanger: true
  });
  if (!confirmed) return;

  try {
    await executeAdminApi('<?= admin_url("api/faqs/delete") ?>', { id });
    showAdminToast('success', 'FAQ deleted successfully.');
    const card = document.querySelector(`.entity-card-capsule[data-id="${id}"]`);
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
