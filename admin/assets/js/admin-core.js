/**
 * DreamIT Admin Console - Core JavaScript Engine
 * Production-Ready Modal & Popup System, Toast Engine, AJAX API Dispatcher,
 * Real-time Live Filter & Card Search.
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Mobile Sidebar Toggle & Outside Click
  const toggleBtn = document.getElementById('mobileSidebarToggle');
  const sidebar = document.querySelector('.admin-sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      sidebar.classList.toggle('is-open');
    });

    document.addEventListener('click', (e) => {
      if (window.innerWidth <= 1024 &&
          sidebar.classList.contains('is-open') &&
          !sidebar.contains(e.target) &&
          !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('is-open');
      }
    });
  }

  // 2. Real-time Card Search & Filter
  const searchInput = document.getElementById('adminCardSearch');
  const categoryFilter = document.getElementById('adminCategoryFilter');
  const statusFilter = document.getElementById('adminStatusFilter');
  const cardGrid = document.querySelector('.card-capsule-grid');

  if (searchInput && cardGrid) {
    const cards = cardGrid.querySelectorAll('.entity-card-capsule, .enquiry-lead-card');

    function filterCards() {
      const query = searchInput.value.toLowerCase().trim();
      const cat = categoryFilter ? categoryFilter.value.toLowerCase() : '';
      const stat = statusFilter ? statusFilter.value.toLowerCase() : '';

      cards.forEach(card => {
        const title = (card.querySelector('.card-entity-title, .lead-name-col strong')?.textContent || '').toLowerCase();
        const desc = (card.querySelector('p, .lead-message-box')?.textContent || '').toLowerCase();
        const cardCat = (card.dataset.category || '').toLowerCase();
        const cardStat = (card.dataset.status || '').toLowerCase();

        const matchQuery = !query || title.includes(query) || desc.includes(query);
        const matchCat = !cat || cardCat === cat;
        const matchStat = !stat || cardStat === stat;

        if (matchQuery && matchCat && matchStat) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    }

    searchInput.addEventListener('input', filterCards);
    if (categoryFilter) categoryFilter.addEventListener('change', filterCards);
    if (statusFilter) statusFilter.addEventListener('change', filterCards);
  }

  // 3. Global Modal Backdrop Click Handler
  document.addEventListener('click', (e) => {
    if (e.target.classList && e.target.classList.contains('admin-modal-backdrop')) {
      closeAdminModal(e.target.id);
    }
  });

  // 4. Global Escape Key Listener for Open Modals
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const openModal = document.querySelector('.admin-modal-backdrop.is-open');
      if (openModal) {
        closeAdminModal(openModal.id);
      }
    }
  });
});

/* ══════════════════════════════════════════════════════════════════════════
   GLOBAL MODAL MANAGEMENT ENGINE
   ══════════════════════════════════════════════════════════════════════════ */
function openAdminModal(modalId) {
  const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
  if (!modal) {
    console.warn('[AdminModal] Modal not found:', modalId);
    return;
  }

  modal.classList.add('is-open');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';

  // Auto-focus first input or close button
  setTimeout(() => {
    const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
    if (firstInput) {
      firstInput.focus();
    } else {
      const closeBtn = modal.querySelector('.admin-modal-close');
      if (closeBtn) closeBtn.focus();
    }
  }, 50);
}

function closeAdminModal(modalId) {
  const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
  if (!modal) return;

  modal.classList.remove('is-open');
  modal.style.display = 'none';
  document.body.style.overflow = '';
}

// Safely extract entity payload from element dataset or direct object
function getAdminEntityData(el, dataKey) {
  if (!el) return null;
  if (typeof el === 'object' && !el.nodeType) {
    return el; // Already parsed JS object
  }
  const raw = el.getAttribute(`data-${dataKey}`) || (el.dataset ? el.dataset[dataKey] : null);
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch (err) {
    console.error(`[AdminModal] Failed to parse data-${dataKey}:`, err);
    return null;
  }
}

// Expose globally to window
window.openAdminModal = openAdminModal;
window.closeAdminModal = closeAdminModal;
window.getAdminEntityData = getAdminEntityData;

/* ══════════════════════════════════════════════════════════════════════════
   CUSTOM CONFIRMATION POPUP MODAL (Replaces window.confirm)
   ══════════════════════════════════════════════════════════════════════════ */
function confirmAdminAction({
  title = 'Are you sure?',
  message = 'This operation cannot be undone.',
  confirmText = 'Yes, Proceed',
  cancelText = 'Cancel',
  isDanger = true
} = {}) {
  return new Promise((resolve) => {
    let confirmBackdrop = document.getElementById('adminGlobalConfirmModal');
    if (!confirmBackdrop) {
      confirmBackdrop = document.createElement('div');
      confirmBackdrop.id = 'adminGlobalConfirmModal';
      confirmBackdrop.className = 'admin-modal-backdrop';
      confirmBackdrop.setAttribute('role', 'dialog');
      confirmBackdrop.setAttribute('aria-modal', 'true');
      document.body.appendChild(confirmBackdrop);
    }

    const iconHtml = isDanger
      ? `<div class="modal-confirm-icon danger">
           <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
         </div>`
      : `<div class="modal-confirm-icon" style="background:var(--brand-primary-light);color:var(--brand-primary);">
           <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>
         </div>`;

    confirmBackdrop.innerHTML = `
      <div class="admin-modal-box modal-sm" onclick="event.stopPropagation()">
        <div class="admin-modal-body" style="text-align:center;padding:32px 24px 20px;">
          ${iconHtml}
          <h2 style="font-size:18px;font-weight:700;color:var(--brand-primary);margin-bottom:8px;">${title}</h2>
          <p style="font-size:13.5px;color:var(--admin-text-muted);line-height:1.55;margin:0 0 20px;">${message}</p>
          <div style="display:flex;gap:10px;justify-content:center;">
            <button type="button" class="btn-admin-secondary" id="confirmModalCancelBtn" style="flex:1;justify-content:center;">
              ${cancelText}
            </button>
            <button type="button" class="${isDanger ? 'btn-admin-danger' : 'btn-admin-primary'}" id="confirmModalApproveBtn" style="flex:1;justify-content:center;">
              ${confirmText}
            </button>
          </div>
        </div>
      </div>
    `;

    openAdminModal('adminGlobalConfirmModal');

    const cancelBtn = document.getElementById('confirmModalCancelBtn');
    const approveBtn = document.getElementById('confirmModalApproveBtn');

    function cleanUp(result) {
      closeAdminModal('adminGlobalConfirmModal');
      resolve(result);
    }

    cancelBtn.onclick = () => cleanUp(false);
    approveBtn.onclick = () => cleanUp(true);
    approveBtn.focus();
  });
}

/* ══════════════════════════════════════════════════════════════════════════
   GLOBAL TOAST NOTIFICATION ENGINE
   ══════════════════════════════════════════════════════════════════════════ */
function showAdminToast(type, message, duration = 4000) {
  let container = document.querySelector('.admin-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'admin-toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `admin-toast toast-${type}`;

  const icon = type === 'success'
    ? `<svg width="20" height="20" fill="none" stroke="#059669" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`
    : (type === 'error'
      ? `<svg width="20" height="20" fill="none" stroke="#E11D48" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`
      : `<svg width="20" height="20" fill="none" stroke="#2563EB" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`);

  toast.innerHTML = `${icon}<span style="flex:1;">${message}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.style.transition = 'all 0.25s ease';
    setTimeout(() => toast.remove(), 250);
  }, duration);
}

/* ══════════════════════════════════════════════════════════════════════════
   AJAX ACTION DISPATCHER WITH BUTTON STATE
   ══════════════════════════════════════════════════════════════════════════ */
async function executeAdminApi(endpoint, payload, buttonEl = null) {
  let originalHtml = '';
  if (buttonEl) {
    if (buttonEl.dataset.loading === 'true') return;
    buttonEl.dataset.loading = 'true';
    buttonEl.disabled = true;
    originalHtml = buttonEl.innerHTML;
    buttonEl.innerHTML = `<span style="display:inline-block;animation:spin 0.8s linear infinite;">&#9696;</span> Saving...`;
  }

  const csrfToken = document.querySelector('meta[name="admin-csrf"]')?.getAttribute('content') || '';

  try {
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Operation failed. Please try again.');
    }

    if (buttonEl) {
      buttonEl.innerHTML = `&#10003; Done!`;
      setTimeout(() => {
        buttonEl.innerHTML = originalHtml;
        buttonEl.disabled = false;
        buttonEl.dataset.loading = 'false';
      }, 1000);
    }

    return data;
  } catch (err) {
    if (buttonEl) {
      buttonEl.innerHTML = originalHtml;
      buttonEl.disabled = false;
      buttonEl.dataset.loading = 'false';
    }
    showAdminToast('error', err.message);
    throw err;
  }
}

// Inject spin keyframe
const spinStyle = document.createElement('style');
spinStyle.innerHTML = `@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`;
document.head.appendChild(spinStyle);

/* ══════════════════════════════════════════════════════════════════════════
   UNIVERSAL MEDIA UPLOADER ENGINE
   ══════════════════════════════════════════════════════════════════════════ */
async function uploadAdminMedia(fileOrFiles, folder = 'general') {
  const formData = new FormData();
  if (Array.isArray(fileOrFiles)) {
    fileOrFiles.forEach(f => formData.append('files[]', f));
  } else if (fileOrFiles instanceof FileList) {
    Array.from(fileOrFiles).forEach(f => formData.append('files[]', f));
  } else {
    formData.append('file', fileOrFiles);
  }
  formData.append('folder', folder);

  const csrfToken = document.querySelector('meta[name="admin-csrf"]')?.getAttribute('content') || '';
  formData.append('csrf_token', csrfToken);

  const endpoint = window.ADMIN_CONFIG?.uploadUrl || 'api/upload';

  const res = await fetch(endpoint, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-Token': csrfToken
    },
    body: formData
  });

  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.message || 'File upload failed. Please verify format & size.');
  }
  return data;
}

function setupAdminDropzone(config) {
  const dropzoneEl = config.dropzoneEl || (config.dropzoneId ? document.getElementById(config.dropzoneId) : null);
  const fileInputEl = config.fileInputEl || (config.fileInputId ? document.getElementById(config.fileInputId) : null);
  const urlInputEl = config.urlInputEl || (config.targetUrlInputId ? document.getElementById(config.targetUrlInputId) : (config.urlInputId ? document.getElementById(config.urlInputId) : null));
  const previewCardEl = config.previewCardEl || (config.previewCardId ? document.getElementById(config.previewCardId) : null);
  const previewThumbEl = config.previewThumbEl || (config.previewThumbId ? document.getElementById(config.previewThumbId) : (config.previewImgId ? document.getElementById(config.previewImgId) : null));
  const previewUrlEl = config.previewUrlEl || (config.previewUrlId ? document.getElementById(config.previewUrlId) : (config.urlTextId ? document.getElementById(config.urlTextId) : null));
  const folder = config.folder || 'general';
  const onUploaded = config.onUploaded || null;
  const onRemoved = config.onRemoved || null;

  if (!dropzoneEl || !fileInputEl) return;

  // Click dropzone triggers file picker
  dropzoneEl.addEventListener('click', (e) => {
    if (e.target !== fileInputEl) {
      fileInputEl.click();
    }
  });

  // Drag & drop highlight
  ['dragenter', 'dragover'].forEach(evt => {
    dropzoneEl.addEventListener(evt, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropzoneEl.classList.add('dragover');
    });
  });

  ['dragleave', 'drop'].forEach(evt => {
    dropzoneEl.addEventListener(evt, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropzoneEl.classList.remove('dragover');
    });
  });

  // Handle dropped files
  dropzoneEl.addEventListener('drop', async (e) => {
    const files = e.dataTransfer.files;
    if (files && files.length > 0) {
      await handleFileSelection(files);
    }
  });

  // Handle selected files
  fileInputEl.addEventListener('change', async () => {
    if (fileInputEl.files && fileInputEl.files.length > 0) {
      await handleFileSelection(fileInputEl.files);
    }
  });

  async function handleFileSelection(files) {
    const origTitle = dropzoneEl.querySelector('.upload-dropzone-title');
    const origText = origTitle ? origTitle.innerHTML : '';
    if (origTitle) {
      origTitle.innerHTML = `<span style="display:inline-block;animation:spin 0.8s linear infinite;">&#9696;</span> Uploading image...`;
    }

    try {
      if (fileInputEl.multiple && files.length > 1) {
        // Multi upload
        const result = await uploadAdminMedia(files, folder);
        showAdminToast('success', result.message || `${files.length} images uploaded!`);
        if (onUploaded) onUploaded(result);
      } else {
        // Single upload
        const result = await uploadAdminMedia(files[0], folder);
        const uploadedUrl = result.url || result.data?.url;
        if (urlInputEl) urlInputEl.value = uploadedUrl;

        if (previewCardEl) previewCardEl.style.display = 'flex';
        if (previewThumbEl) previewThumbEl.src = uploadedUrl;
        if (previewUrlEl) previewUrlEl.textContent = uploadedUrl;

        showAdminToast('success', 'Image uploaded successfully!');
        if (onUploaded) onUploaded(result);
      }
    } catch (err) {
      showAdminToast('error', err.message);
    } finally {
      if (origTitle) origTitle.innerHTML = origText;
      fileInputEl.value = '';
    }
  }

  // Handle remove preview
  if (previewCardEl) {
    const removeBtn = previewCardEl.querySelector('.upload-preview-remove');
    if (removeBtn) {
      removeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (urlInputEl) {
          urlInputEl.value = '';
          urlInputEl.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (previewThumbEl) previewThumbEl.src = '';
        if (previewUrlEl) previewUrlEl.textContent = '';
        previewCardEl.style.display = 'none';
        if (onRemoved) onRemoved();
      });
    }
  }
}

window.confirmAdminAction = confirmAdminAction;
window.showAdminToast = showAdminToast;
window.executeAdminApi = executeAdminApi;
window.uploadAdminMedia = uploadAdminMedia;
window.setupAdminDropzone = setupAdminDropzone;
