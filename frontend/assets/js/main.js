/**
 * =============================================================================
 * main.js — Creative IT Institute
 *
 * Shared JavaScript for all pages. Extracted and consolidated from all 10
 * source HTML files. No external dependencies — vanilla JS only.
 *
 * Modules:
 *   1. Header mobile navigation
 *   2. Carousel / Slider
 *   3. FAQ Accordion
 *   4. Video modal
 *   5. Gallery lightbox
 *   6. Contact / enquiry form
 *   7. Animated counters (IntersectionObserver)
 *   8. Gallery filter + load more
 *   9. Toast notifications
 *  10. Page-specific init router
 * =============================================================================
 */

(function () {
  'use strict';

  /* ===========================================================================
     1. HEADER — MOBILE NAVIGATION
  =========================================================================== */
  function initMobileNav() {
    const toggle = document.getElementById('mobileMenuToggle');
    const drawer = document.getElementById('mobileNavDrawer');
    if (!toggle || !drawer) return;

    toggle.addEventListener('click', () => {
      const isOpen = toggle.classList.toggle('is-open');
      drawer.classList.toggle('is-open', isOpen);
      toggle.setAttribute('aria-expanded', isOpen);
      drawer.setAttribute('aria-hidden', !isOpen);
    });

    // Mobile Courses Submenu Accordion
    const courseToggle = document.getElementById('mobileCoursesToggle');
    const courseSubmenu = document.getElementById('mobileCoursesSubmenu');
    if (courseToggle && courseSubmenu) {
      courseToggle.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = courseSubmenu.classList.toggle('is-open');
        courseToggle.classList.toggle('is-active', isOpen);
        courseToggle.setAttribute('aria-expanded', isOpen);
      });
    }

    // Close drawer when a navigation link inside it is clicked
    drawer.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        toggle.classList.remove('is-open');
        drawer.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        drawer.setAttribute('aria-hidden', 'true');
      });
    });

    // Close drawer when clicking outside
    document.addEventListener('click', (e) => {
      if (drawer.classList.contains('is-open') && !drawer.contains(e.target) && !toggle.contains(e.target)) {
        toggle.classList.remove('is-open');
        drawer.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        drawer.setAttribute('aria-hidden', 'true');
      }
    });

    // Mark active nav link based on current page filename
    const current = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.header-nav-link, .mobile-nav-list a, .mobile-chip').forEach(link => {
      const href = link.getAttribute('href') || '';
      if (href === current || href.endsWith('/' + current)) {
        link.classList.add('active');
        link.setAttribute('aria-current', 'page');
      }
    });
  }

  /* ===========================================================================
     2. CAROUSEL / SLIDER
  =========================================================================== */
  /**
   * @param {Object} config
   * @param {string} config.viewportId   — ID of the overflow:hidden wrapper
   * @param {string} config.trackId      — ID of the flex-row track
   * @param {string} config.prevBtnId    — ID of prev button
   * @param {string} config.nextBtnId    — ID of next button
   * @param {string} [config.dotsId]     — ID of dots container (optional)
   */
  function initCarousel({ viewportId, trackId, prevBtnId, nextBtnId, dotsId }) {
    const viewport  = document.getElementById(viewportId);
    const track     = document.getElementById(trackId);
    const prevBtn   = document.getElementById(prevBtnId);
    const nextBtn   = document.getElementById(nextBtnId);
    const dotsWrap  = dotsId ? document.getElementById(dotsId) : null;
    if (!track || !viewport) return;

    const cards = Array.from(track.children);
    let currentIndex = 0;

    function getItemsPerView() {
      const w = window.innerWidth;
      if (w <= 680)  return 1;
      if (w <= 1120) return 2;
      return 3;
    }

    function getMaxIndex() {
      return Math.max(0, cards.length - getItemsPerView());
    }

    function buildDots() {
      if (!dotsWrap) return;
      dotsWrap.innerHTML = '';
      const max = getMaxIndex();
      for (let i = 0; i <= max; i++) {
        const dot = document.createElement('button');
        dot.className = 'slider-dot' + (i === currentIndex ? ' is-active' : '');
        dot.setAttribute('aria-label', 'Slide ' + (i + 1));
        dot.addEventListener('click', () => { currentIndex = i; update(); });
        dotsWrap.appendChild(dot);
      }
    }

    function update() {
      const max = getMaxIndex();
      if (currentIndex > max) currentIndex = max;

      const cardEl = cards[0];
      if (!cardEl) return;
      const cardW = cardEl.getBoundingClientRect().width;
      const gap   = parseFloat(window.getComputedStyle(track).gap) || 28;
      track.style.transform = `translateX(-${currentIndex * (cardW + gap)}px)`;

      if (prevBtn) prevBtn.disabled = currentIndex === 0;
      if (nextBtn) nextBtn.disabled = currentIndex >= max;

      if (dotsWrap) {
        dotsWrap.querySelectorAll('.slider-dot').forEach((d, i) => {
          d.classList.toggle('is-active', i === currentIndex);
        });
      }
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        currentIndex = currentIndex < getMaxIndex() ? currentIndex + 1 : 0;
        update();
      });
    }
    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        const max = getMaxIndex();
        currentIndex = currentIndex > 0 ? currentIndex - 1 : max;
        update();
      });
    }

    // Touch / swipe support
    let startX = 0;
    viewport.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
    viewport.addEventListener('touchend', e => {
      const diff = startX - e.changedTouches[0].clientX;
      const max  = getMaxIndex();
      if (diff > 40  && currentIndex < max) { currentIndex++; update(); }
      if (diff < -40 && currentIndex > 0)   { currentIndex--; update(); }
    });

    window.addEventListener('resize', () => { buildDots(); update(); });
    buildDots();
    update();
  }

  /* ===========================================================================
     3. FAQ ACCORDION
  =========================================================================== */
  function initFaqAccordion() {
    document.querySelectorAll('.faq-question-btn, .faq-trigger').forEach(btn => {
      btn.addEventListener('click', () => {
        const item = btn.closest('.faq-item');
        if (!item) return;
        const isActive = item.classList.contains('active') || item.classList.contains('is-open');

        // Toggle both state classes for universal styling support
        item.classList.toggle('active', !isActive);
        item.classList.toggle('is-open', !isActive);
        btn.setAttribute('aria-expanded', !isActive);
      });
    });
  }

  /* ===========================================================================
     4. VIDEO MODAL
  =========================================================================== */
  function initVideoModal() {
    const modal     = document.getElementById('videoModal');
    const closeBtn  = document.getElementById('videoModalClose');
    const iframe    = document.getElementById('videoModalIframe');
    if (!modal) return;

    let lastTrigger = null;

    function openModal(src) {
      if (iframe) iframe.src = src;
      modal.classList.add('is-open');
      document.body.style.overflow = 'hidden';
      if (closeBtn) closeBtn.focus();
    }

    function closeModal() {
      if (iframe) iframe.src = '';
      modal.classList.remove('is-open');
      document.body.style.overflow = '';
      if (lastTrigger) lastTrigger.focus();
    }

    document.querySelectorAll('[data-video-src]').forEach(trigger => {
      const src = (trigger.dataset.videoSrc || '').trim();
      if (!src) return;
      trigger.addEventListener('click', () => {
        lastTrigger = trigger;
        openModal(src);
      });
      trigger.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); lastTrigger = trigger; openModal(src); }
      });
    });

    if (closeBtn)  closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    window.addEventListener('keydown', e => {
      if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
  }

  /* ===========================================================================
     5. GALLERY LIGHTBOX
  =========================================================================== */
  function initGalleryLightbox() {
    const lightbox  = document.getElementById('lightboxModal');
    const lbImg     = document.getElementById('lightboxImg');
    const lbCounter = document.getElementById('lightboxCounter');
    const lbClose   = document.getElementById('lightboxClose');
    const lbPrev    = document.getElementById('lightboxPrev');
    const lbNext    = document.getElementById('lightboxNext');
    if (!lightbox || !lbImg) return;

    const items   = Array.from(document.querySelectorAll('.gallery-item[data-src]'));
    let current   = 0;

    function openLightbox(idx) {
      current = idx;
      lbImg.src = items[idx].dataset.src;
      lbImg.alt = items[idx].dataset.alt || '';
      if (lbCounter) lbCounter.textContent = `${idx + 1} / ${items.length}`;
      lightbox.classList.add('active');
      document.body.style.overflow = 'hidden';
      if (lbClose) lbClose.focus();
    }

    function closeLightbox() {
      lightbox.classList.remove('active');
      document.body.style.overflow = '';
    }

    function goNext() { openLightbox((current + 1) % items.length); }
    function goPrev() { openLightbox((current - 1 + items.length) % items.length); }

    items.forEach((item, idx) => {
      item.addEventListener('click', () => openLightbox(idx));
      item.addEventListener('keydown', e => { if (e.key === 'Enter') openLightbox(idx); });
    });

    if (lbClose) lbClose.addEventListener('click', closeLightbox);
    if (lbNext)  lbNext.addEventListener('click', goNext);
    if (lbPrev)  lbPrev.addEventListener('click', goPrev);

    lightbox.addEventListener('click', e => { if (e.target === lightbox) closeLightbox(); });
    window.addEventListener('keydown', e => {
      if (!lightbox.classList.contains('active')) return;
      if (e.key === 'Escape')     closeLightbox();
      if (e.key === 'ArrowRight') goNext();
      if (e.key === 'ArrowLeft')  goPrev();
    });
  }

  /* ===========================================================================
     6. GALLERY FILTER + LOAD MORE
  =========================================================================== */
  function initGalleryFilter() {
    const filterBtns  = document.querySelectorAll('.filter-btn[data-filter]');
    const items       = Array.from(document.querySelectorAll('.gallery-item'));
    const loadMoreBtn = document.getElementById('galleryLoadMore');
    const counterEl   = document.getElementById('counterCurrent');
    const totalEl     = document.getElementById('counterTotal');

    if (!filterBtns.length) return;

    const PAGE_SIZE = 6;
    let activeFilter = 'all';
    let visibleCount = PAGE_SIZE;

    function getFiltered() {
      return activeFilter === 'all'
        ? items
        : items.filter(i => i.dataset.category === activeFilter);
    }

    function render() {
      const filtered = getFiltered();
      items.forEach(i => { i.style.display = 'none'; i.classList.remove('show'); });
      const toShow = filtered.slice(0, visibleCount);
      toShow.forEach(i => { i.style.display = 'block'; i.classList.add('show'); });

      if (counterEl) counterEl.textContent = toShow.length;
      if (totalEl)   totalEl.textContent   = filtered.length;
      if (loadMoreBtn) {
        loadMoreBtn.style.display = visibleCount >= filtered.length ? 'none' : '';
      }
    }

    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterBtns.forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected', 'false'); });
        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');
        activeFilter  = btn.dataset.filter;
        visibleCount  = PAGE_SIZE;
        render();
      });
    });

    if (loadMoreBtn) {
      loadMoreBtn.addEventListener('click', () => {
        visibleCount += PAGE_SIZE;
        render();
      });
    }

    if (totalEl) totalEl.textContent = items.length;
    render();
  }

  /* ===========================================================================
     7. COURSE LISTING FILTER TABS
  =========================================================================== */
  function initCourseFilter() {
    const tabBtns    = document.querySelectorAll('.category-tab-btn[data-filter]');
    const sections   = document.querySelectorAll('.category-group-block[data-category]');
    const allCards   = document.querySelectorAll('.course-card[data-dept]');
    if (!tabBtns.length) return;

    let current = 'all';

    function applyFilter() {
      allCards.forEach(card => {
        const dept = card.getAttribute('data-dept');
        card.style.display = (current === 'all' || current === dept) ? 'flex' : 'none';
      });
      sections.forEach(sec => {
        const cat = sec.getAttribute('data-category');
        sec.style.display = (current === 'all' || current === cat) ? 'block' : 'none';
      });
    }

    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        tabBtns.forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected', 'false'); });
        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');
        current = btn.getAttribute('data-filter');
        applyFilter();
      });
    });

    applyFilter();
  }

  /* ===========================================================================
     8. ANIMATED COUNTERS (IntersectionObserver)
  =========================================================================== */
  function initCounters() {
    const counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;

    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el       = entry.target;
        const target   = parseInt(el.dataset.count, 10);
        const suffix   = el.dataset.suffix || '';
        const duration = 1200;
        const step     = 30;
        const inc      = target / (duration / step);
        let val        = 0;

        const timer = setInterval(() => {
          val += inc;
          if (val >= target) {
            el.textContent = target + suffix;
            clearInterval(timer);
          } else {
            el.textContent = Math.floor(val) + suffix;
          }
        }, step);
        observer.unobserve(el);
      });
    }, { threshold: 0.5 });

    counters.forEach(c => observer.observe(c));
  }

  /* ===========================================================================
     9. TOAST NOTIFICATION
  =========================================================================== */
  function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toastContainer';
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'toast-pill' + (type === 'success' ? ' success' : (type === 'error' ? ' error' : ''));
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
      toast.style.opacity    = '0';
      toast.style.transform  = 'translateY(10px)';
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  }
  window.showToast = showToast; // expose globally for inline handlers

  /* ===========================================================================
     10. CONTACT & ENQUIRY FORM (With Real-Time Validation)
  =========================================================================== */
  function initContactForm() {
    const form          = document.getElementById('contactInquiryForm');
    const successBanner = document.getElementById('formSuccessAlert');
    if (!form) return;

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn ? submitBtn.textContent : '';

      // Real-time client-side input validation
      const nameInput  = form.querySelector('input[name="name"], input[name="full_name"]');
      const phoneInput = form.querySelector('input[name="phone"], input[name="phone_number"]');
      const emailInput = form.querySelector('input[name="email"]');

      if (nameInput && nameInput.value.trim().length < 2) {
        showToast('Please enter your full name (at least 2 characters).', 'error');
        nameInput.focus();
        return;
      }

      if (phoneInput) {
        const cleanPhone = phoneInput.value.replace(/[\s\-\(\)]/g, '');
        if (cleanPhone.length < 7 || !/^[0-9\+]+$/.test(cleanPhone)) {
          showToast('Please enter a valid phone or mobile number.', 'error');
          phoneInput.focus();
          return;
        }
      }

      if (emailInput && emailInput.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
        showToast('Please enter a valid email address.', 'error');
        emailInput.focus();
        return;
      }

      try {
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.textContent = 'Submitting…';
        }

        const formData = new FormData(form);
        const metaCsrf = document.querySelector('meta[name="csrf-token"]');
        if (metaCsrf && !formData.has('csrf_token')) {
          formData.append('csrf_token', metaCsrf.content);
        }

        // Determine submit URL dynamically (use form action or meta api-base)
        let endpoint = form.getAttribute('action');
        if (!endpoint || endpoint.startsWith('#')) {
          const metaApi = document.querySelector('meta[name="api-base"]');
          if (metaApi && metaApi.content) {
            endpoint = metaApi.content.replace(/\/+$/, '') + '/enquiry-submit.php';
          } else {
            const rootMatch = window.location.pathname.match(/^(.*?\/(?:dreamit)?)(?:\/.*)?$/);
            const projectRoot = rootMatch && rootMatch[1] ? rootMatch[1].replace(/\/+$/, '') : '';
            endpoint = (projectRoot || '') + '/api/enquiry-submit.php';
          }
        }

        const res = await fetch(endpoint, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json().catch(() => null);

        if (res.ok && data && data.success) {
          if (successBanner) {
            successBanner.style.display = 'block';
            successBanner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }
          showToast(data.message || 'Your inquiry has been submitted! Our team will contact you shortly.', 'success');
          form.reset();
        } else {
          const errMsg = (data && data.message) ? data.message : 'Could not submit inquiry. Please check your inputs.';
          showToast(errMsg, 'error');
        }
      } catch (err) {
        console.error('Submission error:', err);
        showToast('Network error. Please try again or call us directly.', 'error');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = originalBtnText;
        }
      }
    });
  }

  /* ===========================================================================
     11. SUCCESS STORY — SEE MORE TOGGLE
  =========================================================================== */
  function initSuccessStoryToggle() {
    const btn      = document.getElementById('seeMoreStoriesBtn');
    const btnText  = document.getElementById('seeMoreBtnText');
    const hidden   = document.querySelectorAll('.story-card-hidden');
    if (!btn || !hidden.length) return;

    let expanded = false;
    btn.addEventListener('click', () => {
      expanded = !expanded;
      hidden.forEach(card => {
        if (expanded) {
          card.classList.remove('story-card-hidden');
          card.classList.add('card-revealed');
        } else {
          card.classList.add('story-card-hidden');
          card.classList.remove('card-revealed');
        }
      });
      if (btnText) btnText.textContent = expanded ? 'Show Less' : 'See More';
      btn.setAttribute('aria-expanded', expanded);
      btn.classList.toggle('expanded', expanded);
    });
  }

  /* ===========================================================================
     12. SOFTWARE CAROUSEL (drag-to-scroll horizontal list)
  =========================================================================== */
  function initSoftwareCarousel() {
    const slider  = document.getElementById('softwareSlider');
    const prevBtn = document.getElementById('scrollPrevBtn');
    const nextBtn = document.getElementById('scrollNextBtn');
    if (!slider) return;

    const getStep = () => {
      const first = slider.querySelector('.software-item');
      if (!first) return 240;
      const gap   = parseFloat(window.getComputedStyle(slider).gap) || 28;
      const factor = window.innerWidth < 640 ? 1 : 2;
      return (first.offsetWidth + gap) * factor;
    };

    const updateBtns = () => {
      const maxScroll = slider.scrollWidth - slider.clientWidth;
      if (prevBtn) prevBtn.disabled = Math.ceil(slider.scrollLeft) <= 2;
      if (nextBtn) nextBtn.disabled = Math.ceil(slider.scrollLeft) >= maxScroll - 2;
    };

    if (nextBtn) nextBtn.addEventListener('click', () => { slider.scrollBy({ left:  getStep(), behavior: 'smooth' }); });
    if (prevBtn) prevBtn.addEventListener('click', () => { slider.scrollBy({ left: -getStep(), behavior: 'smooth' }); });
    slider.addEventListener('scroll', () => requestAnimationFrame(updateBtns));
    window.addEventListener('resize', updateBtns);

    // Mouse drag
    let down = false, startX = 0, scrollStart = 0;
    slider.addEventListener('mousedown', e => { down = true; slider.classList.add('is-dragging'); startX = e.pageX - slider.offsetLeft; scrollStart = slider.scrollLeft; });
    window.addEventListener('mouseup', () => { if (!down) return; down = false; slider.classList.remove('is-dragging'); });
    slider.addEventListener('mousemove', e => { if (!down) return; e.preventDefault(); slider.scrollLeft = scrollStart - (e.pageX - slider.offsetLeft - startX) * 1.5; });

    updateBtns();
  }

  /* ===========================================================================
     13. SCROLL-TRIGGERED METRIC COUNTERS (course detail)
  =========================================================================== */
  function initMetricCounters() {
    const metrics = document.querySelectorAll('.metric-number[data-count]');
    if (!metrics.length) return;

    const obs = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const target = parseInt(el.getAttribute('data-count'), 10);
        const suffix = el.getAttribute('data-suffix') || '';
        let val = 0;
        const inc = target / (1200 / 30);
        const t = setInterval(() => {
          val += inc;
          if (val >= target) { el.textContent = target + suffix; clearInterval(t); }
          else                { el.textContent = Math.floor(val) + suffix; }
        }, 30);
        obs.unobserve(el);
      });
    }, { threshold: 0.5 });

    metrics.forEach(m => obs.observe(m));
  }

  /* ===========================================================================
     INIT ROUTER — Run on DOMContentLoaded
  =========================================================================== */
  document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
    initFaqAccordion();
    initVideoModal();
    initGalleryLightbox();
    initGalleryFilter();
    initCourseFilter();
    initCounters();
    initContactForm();
    initSuccessStoryToggle();
    initSoftwareCarousel();
    initMetricCounters();

    // Carousels — only initialise if the required elements exist
    if (document.getElementById('coursesTrack')) {
      initCarousel({ viewportId: 'coursesViewport', trackId: 'coursesTrack', prevBtnId: 'coursesPrev', nextBtnId: 'coursesNext', dotsId: 'coursesDots' });
    }
    if (document.getElementById('milestoneTrack')) {
      initCarousel({ viewportId: 'milestoneViewport', trackId: 'milestoneTrack', prevBtnId: 'milestonePrev', nextBtnId: 'milestoneNext' });
    }
  });

})();
