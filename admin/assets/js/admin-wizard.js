/**
 * DreamIT Admin Console - Form Wizard JavaScript Engine
 * Step progression, validation, dynamic repeaters, and SERP preview
 */

let currentWizardStep = 1;
const totalWizardSteps = 4;

document.addEventListener('DOMContentLoaded', () => {
  initWizardNavigation();
  initSlugAutoGenerator();
  initSerpPreview();
  initRepeaters();
  initSoftwareToolSelection();
});

// 1. Wizard Step Navigation
function initWizardNavigation() {
  const nextBtns = document.querySelectorAll('.btn-wizard-next');
  const prevBtns = document.querySelectorAll('.btn-wizard-prev');

  nextBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      if (validateCurrentStep(currentWizardStep)) {
        goToWizardStep(currentWizardStep + 1);
      }
    });
  });

  prevBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      goToWizardStep(currentWizardStep - 1);
    });
  });

  // Step Node clicks
  document.querySelectorAll('.wizard-step-node').forEach(node => {
    node.addEventListener('click', () => {
      const targetStep = parseInt(node.dataset.step, 10);
      if (targetStep < currentWizardStep || validateCurrentStep(currentWizardStep)) {
        goToWizardStep(targetStep);
      }
    });
  });
}

function goToWizardStep(step) {
  if (step < 1 || step > totalWizardSteps) return;

  // Update step panels
  document.querySelectorAll('.wizard-step-panel').forEach(panel => {
    panel.classList.remove('active-panel');
  });
  const targetPanel = document.getElementById(`wizardStepPanel${step}`);
  if (targetPanel) targetPanel.classList.add('active-panel');

  // Update step nodes
  document.querySelectorAll('.wizard-step-node').forEach(node => {
    const nodeStep = parseInt(node.dataset.step, 10);
    node.classList.remove('is-active');
    if (nodeStep === step) {
      node.classList.add('is-active');
    } else if (nodeStep < step) {
      node.classList.add('is-completed');
    } else {
      node.classList.remove('is-completed');
    }
  });

  currentWizardStep = step;
  window.scrollTo({ top: 120, behavior: 'smooth' });
}

// 2. Validate Fields per Step
function validateCurrentStep(step) {
  if (step === 1) {
    const titleInput = document.getElementById('courseTitleInput');
    const deptInput = document.getElementById('courseDeptSelect');
    const priceInput = document.getElementById('coursePriceInput');

    if (!titleInput.value.trim()) {
      showAdminToast('error', 'Please enter a Course Title');
      titleInput.focus();
      return false;
    }
    if (!deptInput.value.trim()) {
      showAdminToast('error', 'Please select a Department');
      deptInput.focus();
      return false;
    }
    if (!priceInput.value.trim() || isNaN(priceInput.value)) {
      showAdminToast('error', 'Please enter a valid Course Fee');
      priceInput.focus();
      return false;
    }
  }
  return true;
}

// 3. Slug Auto Generator
function initSlugAutoGenerator() {
  const titleInput = document.getElementById('courseTitleInput');
  const slugInput = document.getElementById('courseSlugInput');

  if (titleInput && slugInput) {
    titleInput.addEventListener('input', () => {
      // Only auto-update if slug was not manually customized
      if (!slugInput.dataset.manualEdited) {
        slugInput.value = titleInput.value
          .toLowerCase()
          .trim()
          .replace(/[^a-z0-9 -]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-');

        updateSerpUrl(slugInput.value);
      }
    });

    slugInput.addEventListener('input', () => {
      slugInput.dataset.manualEdited = 'true';
      updateSerpUrl(slugInput.value);
    });
  }
}

// 4. Live Google SERP Snippet Preview
function initSerpPreview() {
  const seoTitleInput = document.getElementById('seoTitleInput');
  const seoDescInput = document.getElementById('seoDescInput');
  const previewTitle = document.getElementById('serpTitlePreview');
  const previewDesc = document.getElementById('serpDescPreview');
  const courseTitleInput = document.getElementById('courseTitleInput');

  function updatePreview() {
    const title = seoTitleInput?.value.trim() || courseTitleInput?.value.trim() || 'Course Title Preview';
    const desc = seoDescInput?.value.trim() || 'Enroll in this professional training program with hands-on labs, real projects, and placement assistance.';

    if (previewTitle) previewTitle.textContent = `${title} | Creative IT Institute`;
    if (previewDesc) previewDesc.textContent = desc;
  }

  if (seoTitleInput) seoTitleInput.addEventListener('input', updatePreview);
  if (seoDescInput) seoDescInput.addEventListener('input', updatePreview);
  if (courseTitleInput) courseTitleInput.addEventListener('input', updatePreview);
}

function updateSerpUrl(slug) {
  const previewUrl = document.getElementById('serpUrlPreview');
  if (previewUrl) {
    previewUrl.textContent = `https://dreamit.com/courses/${slug || 'course-slug'}/`;
  }
}

// 5. Dynamic Repeaters (Add/Remove rows)
function initRepeaters() {
  // Add highlight repeater
  document.querySelectorAll('[data-add-repeater]').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.addRepeater;
      const container = document.getElementById(targetId);
      if (!container) return;

      const row = document.createElement('div');
      row.className = 'repeater-item-row';
      row.innerHTML = `
        <input type="text" class="form-control" name="${container.dataset.inputName}[]" placeholder="${btn.dataset.placeholder || 'Enter item...'}" />
        <button type="button" class="btn-remove-repeater" onclick="this.parentElement.remove()" title="Remove item">&times;</button>
      `;
      container.appendChild(row);
      row.querySelector('input').focus();
    });
  });
}

// 6. Software Tools Multi-Selection
function initSoftwareToolSelection() {
  document.querySelectorAll('.tool-checkbox-card').forEach(card => {
    card.addEventListener('click', (e) => {
      if (e.target.tagName !== 'INPUT') {
        const checkbox = card.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
      }
      card.classList.toggle('is-selected', card.querySelector('input[type="checkbox"]').checked);
    });
  });
}
