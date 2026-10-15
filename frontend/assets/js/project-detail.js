/**
 * =============================================================================
 * PROJECT DETAIL — Multi-Slide Carousel
 * Shows 2 slides side-by-side on desktop, 1 on mobile.
 * Arrows, thumbnail clicks, keyboard nav, swipe support.
 * =============================================================================
 */

(function () {
  'use strict';

  const track    = document.getElementById('carouselTrack');
  const prevBtn  = document.getElementById('arrowPrev');
  const nextBtn  = document.getElementById('arrowNext');
  const thumbs   = document.querySelectorAll('.sub-image-item');
  const slides   = document.querySelectorAll('.carousel-slide');

  if (!track || slides.length === 0) return;

  let currentIndex = 0;
  const totalSlides = slides.length;

  /* ── How many slides are visible at once ─────────────────────────────── */
  function getVisible() {
    return window.innerWidth <= 768 ? 1 : 2;
  }

  /* ── Maximum index we can scroll to (so last slides are never blank) ─── */
  function getMaxIndex() {
    return Math.max(0, totalSlides - getVisible());
  }

  /* ── Move carousel to given index ────────────────────────────────────── */
  function goToSlide(index) {
    const max = getMaxIndex();

    // Loop around at boundaries
    if (index < 0)    index = max;
    if (index > max)  index = 0;

    currentIndex = index;

    // Each slide occupies (100 / visible)% of the track width
    const slidePercent = 100 / getVisible();
    track.style.transform = `translateX(-${currentIndex * slidePercent}%)`;

    // Sync active thumbnail
    thumbs.forEach(function (thumb, i) {
      thumb.classList.toggle('active', i === currentIndex);
    });
  }

  /* ── Thumbnail clicks ────────────────────────────────────────────────── */
  thumbs.forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      const idx = parseInt(this.dataset.index, 10);
      if (!isNaN(idx)) goToSlide(idx);
    });
  });

  /* ── Arrow buttons ───────────────────────────────────────────────────── */
  if (prevBtn) prevBtn.addEventListener('click', function () { goToSlide(currentIndex - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { goToSlide(currentIndex + 1); });

  /* ── Keyboard navigation ─────────────────────────────────────────────── */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowLeft')  goToSlide(currentIndex - 1);
    if (e.key === 'ArrowRight') goToSlide(currentIndex + 1);
  });

  /* ── Touch / swipe support ───────────────────────────────────────────── */
  var touchStartX = 0;

  track.addEventListener('touchstart', function (e) {
    touchStartX = e.changedTouches[0].screenX;
  }, { passive: true });

  track.addEventListener('touchend', function (e) {
    var diff = touchStartX - e.changedTouches[0].screenX;
    if (Math.abs(diff) > 50) {
      goToSlide(diff > 0 ? currentIndex + 1 : currentIndex - 1);
    }
  }, { passive: true });

  /* ── Re-calculate on resize (visible count may change) ──────────────── */
  var resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      goToSlide(currentIndex);   // re-apply correct transform for new layout
    }, 100);
  });

  /* ── Init at index 0 ─────────────────────────────────────────────────── */
  goToSlide(0);

})();
