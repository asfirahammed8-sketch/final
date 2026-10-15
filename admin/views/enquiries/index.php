<?php
/**
 * DreamIT Admin Console - Inquiries & Leads Management View (NO TABLES)
 */
require_once ADMIN_PATH . '/includes/auth-guard.php';

$pageTitle = 'Admission Inquiries';
$pageHeadline = 'Prospective Student Inquiries';
$activeMenu = 'enquiries';

$db = Database::getConnection();

// Fetch enquiries
$stmt = $db->query("SELECT * FROM enquiries ORDER BY created_at DESC");
$enquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once ADMIN_PATH . '/includes/header.php';
?>

<!-- Filter & Control Bar -->
<div class="control-filter-bar">
  <div class="search-input-wrapper">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="adminCardSearch" class="admin-search-input" placeholder="Search leads by name, phone, course..." />
  </div>

  <div class="filter-actions-group">
    <select id="adminStatusFilter" class="admin-select" aria-label="Filter by lead status">
      <option value="">All Lead Statuses</option>
      <option value="new">New</option>
      <option value="in_progress">In Progress</option>
      <option value="contacted">Contacted</option>
      <option value="enrolled">Enrolled</option>
      <option value="closed">Closed</option>
    </select>
  </div>
</div>

<!-- Lead Cards Grid Container (NO TABLES) -->
<div class="card-capsule-grid" style="grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));">
  <?php if (empty($enquiries)): ?>
    <div style="grid-column: 1 / -1; text-align: center; padding: 48px; background: var(--admin-surface-card); border-radius: var(--admin-radius-lg); border: 1px dashed var(--admin-border);">
      <h3 style="color:var(--brand-primary);font-size:18px;margin-bottom:8px;">No inquiries received yet</h3>
      <p style="color:var(--admin-text-muted);font-size:14px;">When prospective students submit the contact form or click course CTAs, their leads appear here.</p>
    </div>
  <?php else: ?>
    <?php foreach ($enquiries as $enq):
      $status = $enq['status'] ?? 'new';
      $isNew = ($status === 'new');
      $cleanPhone = preg_replace('/[^0-9]/', '', $enq['phone'] ?? '');
    ?>
      <article class="enquiry-lead-card <?= $isNew ? 'unread-lead' : '' ?>" data-id="<?= $enq['id'] ?>" data-status="<?= e($status) ?>">

        <div class="lead-header-row">
          <div class="lead-name-col">
            <h2 style="font-size:17px;font-weight:700;color:var(--brand-primary);"><?= e($enq['name'] ?? 'Candidate') ?></h2>
            <span class="lead-time">&#9201; <?= date('M j, Y h:i A', strtotime($enq['created_at'])) ?></span>
          </div>

          <select class="admin-select" style="padding:4px 8px;font-size:11px;" onchange="updateLeadStatus(<?= $enq['id'] ?>, this.value)">
            <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>New</option>
            <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="contacted" <?= $status === 'contacted' ? 'selected' : '' ?>>Contacted</option>
            <option value="enrolled" <?= $status === 'enrolled' ? 'selected' : '' ?>>Enrolled &#127891;</option>
            <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
          </select>
        </div>

        <div class="lead-details-row">
          <span class="lead-detail-pill">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            <?= e($enq['phone']) ?>
          </span>

          <?php if (!empty($enq['email'])): ?>
            <span class="lead-detail-pill">
              <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <?= e($enq['email']) ?>
            </span>
          <?php endif; ?>

          <?php
          $courseDisplay = $enq['course_name'] ?: $enq['course_title'] ?: '';
          if (!empty($courseDisplay)): ?>
            <span class="lead-detail-pill" style="color:var(--brand-accent);">
              &#128218; <?= e($courseDisplay) ?>
            </span>
          <?php endif; ?>
        </div>

        <?php if (!empty($enq['message'])): ?>
          <div class="lead-message-box">
            <?= nl2br(e($enq['message'])) ?>
          </div>
        <?php endif; ?>

        <div class="lead-action-bar">
          <?php if (!empty($enq['phone'])): ?>
            <a href="tel:<?= e($enq['phone']) ?>" class="btn-card-action" style="color:#38bdf8;">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              Call Now
            </a>

            <a href="https://wa.me/<?= e($cleanPhone) ?>" target="_blank" class="btn-card-action" style="color:#34d399;">
              &#128172; WhatsApp
            </a>
          <?php endif; ?>

          <?php if (!empty($enq['email'])): ?>
            <a href="mailto:<?= e($enq['email']) ?>" class="btn-card-action">
              Email
            </a>
          <?php endif; ?>

          <button type="button" class="btn-card-action"
            data-enquiry='<?= htmlspecialchars(json_encode([
              'id'         => (int)$enq['id'],
              'name'       => $enq['name'] ?? 'Candidate',
              'phone'      => $enq['phone'] ?? '',
              'cleanPhone' => $cleanPhone,
              'email'      => $enq['email'] ?? '',
              'course'     => $courseDisplay,
              'message'    => $enq['message'] ?? '',
              'status'     => $status,
              'created_at' => date('M j, Y h:i A', strtotime($enq['created_at']))
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>'
            onclick="viewEnquiry(this)">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Details
          </button>

          <button type="button" class="btn-card-action btn-card-delete" style="margin-left:auto;" onclick="deleteLead(<?= $enq['id'] ?>)">
            Delete
          </button>
        </div>

      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Enquiry Details & Management Modal -->
<div id="enquiryModal" class="admin-modal-backdrop" role="dialog" aria-modal="true">
  <div class="admin-modal-box" onclick="event.stopPropagation()">
    <div class="admin-modal-header">
      <h2 class="admin-modal-title">
        <span class="admin-modal-title-icon">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        </span>
        <span id="enquiryModalTitle">Admission Lead Details</span>
      </h2>
      <button type="button" class="admin-modal-close" onclick="closeAdminModal('enquiryModal')" aria-label="Close modal">&times;</button>
    </div>

    <form id="enquiryModalForm" onsubmit="saveEnquiryModal(event)">
      <div class="admin-modal-body">
        <input type="hidden" id="modalEnqId" />

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--admin-border);">
          <div>
            <h3 id="modalEnqName" style="font-size:18px;font-weight:700;color:var(--brand-primary);margin:0 0 4px;">Candidate Name</h3>
            <span id="modalEnqTime" style="font-size:12px;color:var(--admin-text-muted);">Timestamp</span>
          </div>
          <div id="modalEnqCourseBadge" style="background:var(--brand-primary-light);color:var(--brand-primary);font-size:12px;font-weight:600;padding:4px 10px;border-radius:6px;">
            Course
          </div>
        </div>

        <div class="form-grid-2" style="margin-bottom:16px;">
          <div>
            <label class="form-label">Phone Number</label>
            <div style="display:flex;align-items:center;gap:8px;">
              <span id="modalEnqPhone" style="font-size:14px;font-weight:600;color:var(--admin-text-main);"></span>
              <a id="modalCallBtn" href="#" class="btn-card-action" style="color:#38bdf8;">Call</a>
              <a id="modalWaBtn" href="#" target="_blank" class="btn-card-action" style="color:#34d399;">WhatsApp</a>
            </div>
          </div>
          <div>
            <label class="form-label">Email Address</label>
            <div style="display:flex;align-items:center;gap:8px;">
              <span id="modalEnqEmail" style="font-size:13.5px;color:var(--admin-text-main);"></span>
              <a id="modalMailBtn" href="#" class="btn-card-action">Email</a>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Candidate Message</label>
          <div id="modalEnqMessage" style="background:var(--admin-bg-alt);border:1px solid var(--admin-border);padding:14px;border-radius:8px;font-size:13.5px;color:var(--admin-text-main);line-height:1.55;min-height:70px;white-space:pre-wrap;"></div>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="modalEnqStatusSelect">Counselor Action Status</label>
          <select id="modalEnqStatusSelect" class="admin-select" style="width:100%;">
            <option value="new">New (Uncontacted)</option>
            <option value="in_progress">In Progress (Follow-up scheduled)</option>
            <option value="contacted">Contacted (In discussion)</option>
            <option value="enrolled">Enrolled (Admission confirmed &#127891;)</option>
            <option value="closed">Closed (Not interested / archived)</option>
          </select>
        </div>
      </div>

      <div class="admin-modal-footer" style="display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn-admin-secondary" onclick="closeAdminModal('enquiryModal')">Close</button>
        <button type="submit" class="btn-admin-primary" id="btnSaveEnqModal">Update Lead Status</button>
      </div>
    </form>
  </div>
</div>

<script>
function viewEnquiry(btnOrData) {
  const e = getAdminEntityData(btnOrData, 'enquiry') || btnOrData;
  if (!e) return;

  document.getElementById('modalEnqId').value = e.id;
  document.getElementById('modalEnqName').textContent = e.name || 'Candidate';
  document.getElementById('modalEnqTime').textContent = 'Received: ' + (e.created_at || 'Recently');
  document.getElementById('modalEnqCourseBadge').textContent = e.course || 'General Enquiry';
  document.getElementById('modalEnqPhone').textContent = e.phone || 'N/A';
  document.getElementById('modalEnqEmail').textContent = e.email || 'N/A';
  document.getElementById('modalEnqMessage').textContent = e.message || 'No additional message provided.';
  document.getElementById('modalEnqStatusSelect').value = e.status || 'new';

  const callBtn = document.getElementById('modalCallBtn');
  const waBtn = document.getElementById('modalWaBtn');
  const mailBtn = document.getElementById('modalMailBtn');

  if (e.phone) {
    callBtn.href = 'tel:' + e.phone;
    callBtn.style.display = 'inline-flex';
    waBtn.href = 'https://wa.me/' + (e.cleanPhone || e.phone.replace(/[^0-9]/g, ''));
    waBtn.style.display = 'inline-flex';
  } else {
    callBtn.style.display = 'none';
    waBtn.style.display = 'none';
  }

  if (e.email) {
    mailBtn.href = 'mailto:' + e.email;
    mailBtn.style.display = 'inline-flex';
  } else {
    mailBtn.style.display = 'none';
  }

  openAdminModal('enquiryModal');
}

async function saveEnquiryModal(event) {
  event.preventDefault();
  const id = document.getElementById('modalEnqId').value;
  const status = document.getElementById('modalEnqStatusSelect').value;
  const btn = document.getElementById('btnSaveEnqModal');

  await updateLeadStatus(id, status, btn);
  closeAdminModal('enquiryModal');
}

async function updateLeadStatus(id, newStatus, buttonEl = null) {
  try {
    await executeAdminApi('<?= admin_url("api/enquiries/update-status") ?>', { id, status: newStatus }, buttonEl);
    showAdminToast('success', 'Lead status updated to ' + newStatus);
    const card = document.querySelector(`.enquiry-lead-card[data-id="${id}"]`);
    if (card) {
      card.dataset.status = newStatus;
      const selectOnCard = card.querySelector('select.admin-select');
      if (selectOnCard) selectOnCard.value = newStatus;
      if (newStatus !== 'new') {
        card.classList.remove('unread-lead');
      }
    }
  } catch (err) {}
}

async function deleteLead(id) {
  const confirmed = await confirmAdminAction({
    title: 'Delete Inquiry?',
    message: 'Are you sure you want to delete this enquiry record? This cannot be undone.',
    confirmText: 'Yes, Delete',
    isDanger: true
  });
  if (!confirmed) return;

  try {
    await executeAdminApi('<?= admin_url("api/enquiries/delete") ?>', { id });
    showAdminToast('success', 'Enquiry deleted.');
    const card = document.querySelector(`.enquiry-lead-card[data-id="${id}"]`);
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
