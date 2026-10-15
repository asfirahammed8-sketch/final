<?php
$extraCss = '<style>
/* ==========================================================================
   Course Detail Page - Clean Modern Styling (No Plain Color Blocks)
   ========================================================================== */

/* --- Hero Section --- */
.course-hero-section {
    padding: clamp(36px, 5vw, 56px) 0 20px;
    background: #ffffff;
}
.hero-grid {
    display: grid;
    grid-template-columns: 1.18fr 0.82fr;
    gap: 40px;
    align-items: center;
}
.course-title {
    font-size: 34px;
    font-weight: 800;
    color: var(--color-text-primary, #0f172a);
    line-height: 1.25;
    letter-spacing: -0.5px;
    margin-bottom: 16px;
}
.delivery-tags-wrapper {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px 12px;
    margin-bottom: 18px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
}
.delivery-label {
    color: var(--color-text-primary, #0f172a);
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 5px;
}
.delivery-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    transition: all 0.2s ease;
}
.delivery-pill:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.delivery-pill svg {
    width: 14px;
    height: 14px;
    color: #64748b;
    flex-shrink: 0;
}
.hero-description {
    font-size: 14px;
    line-height: 1.7;
    color: #475569;
    margin-bottom: 24px;
}

/* Key Metrics */
.key-metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 28px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
}
.key-metrics-grid-3 { grid-template-columns: repeat(3, 1fr); }
.key-metrics-grid-2 { grid-template-columns: repeat(2, 1fr); }
.key-metrics-grid-1 { grid-template-columns: 1fr; }
.metric-item {
    border-right: 1px solid #e2e8f0;
    padding-right: 16px;
}
.metric-item:last-child {
    border-right: none;
    padding-right: 0;
}
.metric-number {
    font-size: 24px;
    font-weight: 800;
    color: var(--color-brand-red, #c92127);
    line-height: 1.1;
    margin-bottom: 4px;
}
.metric-title {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    line-height: 1.3;
}
.hero-cta-group {
    display: flex;
    gap: 14px;
    align-items: center;
    margin-top: 10px;
}
.btn-hero {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 28px;
    background: var(--color-brand-red, #c92127);
    color: #ffffff;
    font-size: 14px;
    font-weight: 700;
    border-radius: 8px;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(201, 33, 39, 0.3);
    transition: all 0.2s ease;
}
.btn-hero:hover {
    background: #a9181e;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(201, 33, 39, 0.4);
    color: #ffffff;
}

/* Hero Media Card (Clean Fresh Image — No Dark Blur) */
.hero-media-card {
    position: relative;
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    min-height: 340px;
    box-shadow: 0 8px 24px rgba(11, 32, 70, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.hero-media-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 36px rgba(11, 32, 70, 0.14);
}
.hero-media-bg-img {
    width: 100%;
    height: 100%;
    min-height: 340px;
    object-fit: cover;
    display: block;
    filter: none !important;
    opacity: 1 !important;
    transition: transform 0.4s ease;
}
.hero-media-card:hover .hero-media-bg-img {
    transform: scale(1.03);
}
.hero-media-overlay {
    display: none !important;
}
.play-trigger-badge {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 64px;
    height: 64px;
    background: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 0 12px rgba(255, 255, 255, 0.25);
    border: none;
    cursor: pointer;
    z-index: 2;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
.hero-media-card:hover .play-trigger-badge {
    transform: translate(-50%, -50%) scale(1.1);
    background: #ffffff;
    box-shadow: 0 0 0 16px rgba(201, 33, 39, 0.35);
}
.play-trigger-badge::after {
    content: "";
    border-left: 18px solid var(--color-brand-red, #c92127);
    border-top: 11px solid transparent;
    border-bottom: 11px solid transparent;
    margin-left: 4px;
}

/* Facilities Strip */
.facilities-card {
    margin-top: 36px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    border-radius: 12px;
    padding: 18px 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.facilities-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 12px;
}
.facilities-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px 28px;
    list-style: none;
    padding: 0;
    margin: 0;
}
.facility-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
}
.facility-item svg {
    width: 16px;
    height: 16px;
    color: #16a34a;
    flex-shrink: 0;
}

/* --- Curriculum & Breakdown Section --- */
.content-breakdown-section {
    padding: 40px 0 20px;
}
.breakdown-grid {
    display: grid;
    grid-template-columns: 1.18fr 0.82fr;
    gap: 40px;
}
.breakdown-grid-1 { grid-template-columns: 1fr; }
.breakdown-grid-2 { grid-template-columns: 1fr 1fr; }
.highlights-list {
    list-style: none;
    padding: 0;
    margin: 0 0 30px 0;
}
.highlight-item {
    position: relative;
    padding-left: 24px;
    margin-bottom: 14px;
    font-size: 14px;
    color: #334155;
    line-height: 1.6;
}
.highlight-item::before {
    content: "✓";
    position: absolute;
    left: 0;
    top: 0;
    color: #16a34a;
    font-weight: 800;
    font-size: 14px;
}
.career-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px 20px;
    list-style: none;
    padding: 0;
    margin: 0;
}
.career-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13.5px;
    font-weight: 600;
    color: #334155;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 12px 16px;
    border-radius: 8px;
}
.career-item svg {
    width: 18px;
    height: 18px;
    color: #64748b;
    flex-shrink: 0;
}

/* Distribution / Class Structure Card */
.distribution-card {
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}
.dist-header-purple {
    background: #f8fafc;
    padding: 10px 16px;
    border-bottom: 1px solid #e2e8f0;
}
.dist-badge-purple {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #e2e8f0;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 700;
    color: #1e293b;
}
.dist-badge-purple::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #0284c7;
}
.dist-body-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    padding: 20px 18px;
    gap: 18px;
}
.dist-subbox {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
}
.dist-box-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
}
.dist-box-desc {
    font-size: 12px;
    color: #64748b;
    line-height: 1.5;
}

/* Prerequisites Strip */
.prereq-strip {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px 24px;
    margin-top: 24px;
}
.prereq-header {
    font-size: 13.5px;
    font-weight: 800;
    color: #0f172a;
}
.prereq-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
}
.prereq-item svg {
    color: #64748b;
    flex-shrink: 0;
}

/* --- Section Headings --- */
.section-subtitle {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.3px;
    margin-bottom: 20px;
}
.sub-tag {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-brand-red, #c92127);
    margin-left: 8px;
}

/* ==========================================================================
   WHO IS THIS COURSE FOR (Clean, No Solid Colored Backgrounds)
   ========================================================================== */
.designed-for-section {
    padding: 48px 0 24px;
    background: #ffffff;
}
.audience-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-top: 20px;
}
.audience-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 24px 20px;
    text-align: left;
    transition: all 0.25s ease;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
}
.audience-card:hover {
    background: #ffffff;
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.07);
    border-color: #cbd5e1;
}
.audience-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    margin-bottom: 14px;
    background: transparent;
}
.audience-icon svg {
    width: 38px;
    height: 38px;
}
.audience-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.35;
    margin-bottom: 6px;
}
.audience-desc {
    font-size: 12.5px;
    color: #64748b;
    line-height: 1.5;
}

/* ==========================================================================
   TOOLS & SOFTWARE LEARNED (Clean Brand Logos, No Saturated Color Boxes)
   ========================================================================== */
.software-section {
    padding: 48px 0 32px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    margin-top: 30px;
}
.software-slider-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-top: 22px;
}
.software-list {
    display: flex;
    align-items: stretch;
    gap: 16px;
    overflow-x: auto;
    scroll-behavior: smooth;
    padding: 10px 4px;
    scrollbar-width: none;
    -ms-overflow-style: none;
    width: 100%;
    cursor: grab;
    user-select: none;
}
.software-list::-webkit-scrollbar { display: none; }
.software-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 150px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px 16px;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    transition: all 0.25s ease;
}
.software-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 22px rgba(0, 0, 0, 0.07);
    border-color: #cbd5e1;
}
.software-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    background: transparent;
}
.software-icon svg {
    width: 42px;
    height: 42px;
}
.software-name {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    text-align: center;
    margin-bottom: 2px;
}
.software-cat {
    font-size: 11.5px;
    color: #64748b;
    text-align: center;
}
.carousel-btn {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.carousel-btn:hover:not(:disabled) {
    background: var(--color-brand-red, #c92127);
    color: #ffffff;
    border-color: var(--color-brand-red, #c92127);
    transform: scale(1.08);
}
.carousel-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
    background: #f1f5f9;
}

/* ==========================================================================
   COURSE FAQ ACCORDION
   ========================================================================== */
.course-faq-section {
    padding: 48px 0 24px;
    background: #ffffff;
}
.course-faq-accordion {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-top: 22px;
}
.faq-accordion-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
/* =============================================================================
   Same-to-Same FAQ Section UI/UX (Matching DreamIT Home & Success Stories)
============================================================================= */
.home-faq-section {
    padding: clamp(48px, 6vw, 72px) 0;
    background: #ffffff;
}
.home-faq-header {
    text-align: center;
    max-width: 700px;
    margin: 0 auto clamp(28px, 4vw, 44px);
}
.home-faq-title {
    font-size: clamp(1.75rem, 3vw, 2.3rem);
    font-weight: 800;
    color: var(--primary, #0B2046);
    letter-spacing: -0.02em;
    margin-bottom: 10px;
}
.home-faq-subtitle {
    font-size: var(--font-size-base, 15px);
    color: var(--color-text-secondary, #64748b);
    line-height: 1.65;
}
.home-faq-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-bottom: clamp(28px, 4vw, 44px);
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
}
.home-faq-cta {
    text-align: center;
}

/* ==========================================================================
   Responsive Adaptations
   ========================================================================== */
@media (max-width: 1024px) {
    .hero-grid, .breakdown-grid { grid-template-columns: 1fr; }
    .audience-cards-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .course-title { font-size: 26px; }
    .key-metrics-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .audience-cards-grid { grid-template-columns: 1fr; }
    .career-grid { grid-template-columns: 1fr; }
    .dist-body-grid { grid-template-columns: 1fr; }
}
</style>';
?>
