<?php
$pageCss = 'about.css';

/**
 * About Us Page
 * Creative IT Institute - DreamIT Platform
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$siteBrand = site_brand();
$pageTitle = "About {$siteBrand} | Leading IT Training in Bangladesh";
$metaDesc  = "Learn about {$siteBrand} — empowering youth with world-class IT training, ISO 9001:2015 certified curriculum, and lifetime career support in Bangladesh.";
$activeNav = 'about';
$canonicalUrl = canonical_url('about/');

$keyStats = get_setting('key_stats', [
    'successful_students' => '50,000+',
    'expert_freelancers'  => '34,000+',
    'job_placements'      => '42,000+',
    'industry_experts'    => '600+',
    'success_rate'        => '89%',
    'partner_companies'   => '3,000+'
]);

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="container">
  <!-- About Intro -->
  <section class="about-section">
    <div class="about-intro-grid">
      <div>
        <h1 class="about-page-heading">About Us</h1>
        <p class="about-lead">Creative IT Institute, an institution where empowering the society for advanced education, is our passion. We provide international standard IT training that equips thousands of ambitious individuals with future-ready digital competencies. With dedicated mentorship, real-world project portfolios, and career placement services, we consistently nurture the next generation of global IT leaders in Bangladesh.</p>
        <p class="about-lead" style="margin-top:16px;">Creative IT Institute has been tirelessly working since 2008 to empower the young generation of Bangladesh. Through our industry-aligned curriculum, state-of-the-art computer labs, and internationally certified mentors, we transform technical novices into high-performing professionals.</p>
        <div style="margin-top:24px;">
          <a href="<?= base_url('courses.php') ?>" class="btn-hero" style="width:fit-content;display:inline-flex;">Browse Courses</a>
        </div>
      </div>
      <div>
        <img class="about-img" src="https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=800&q=80" alt="Students learning in computer classroom with mentor" loading="lazy" width="500" height="380" />
      </div>
    </div>
  </section>

  <!-- Counter Stats -->
  <section class="stats-section" aria-label="Key Statistics">
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-number" data-count="50000" data-suffix="+"><?= e($keyStats['successful_students'] ?? '50,000+') ?></div><p class="stat-label">Successful Students</p></div>
      <div class="stat-card"><div class="stat-number" data-count="34000" data-suffix="+"><?= e($keyStats['expert_freelancers'] ?? '34,000+') ?></div><p class="stat-label">Expert Freelancers</p></div>
      <div class="stat-card"><div class="stat-number" data-count="42000" data-suffix="+"><?= e($keyStats['job_placements'] ?? '42,000+') ?></div><p class="stat-label">Job Placed Students</p></div>
      <div class="stat-card"><div class="stat-number" data-count="600"   data-suffix="+"><?= e($keyStats['industry_experts'] ?? '600+') ?></div><p class="stat-label">Industry Experts</p></div>
      <div class="stat-card"><div class="stat-number"><?= e($keyStats['success_rate'] ?? '89%') ?></div><p class="stat-label">Success Rate</p></div>
      <div class="stat-card"><div class="stat-number" data-count="3000"  data-suffix="+"><?= e($keyStats['partner_companies'] ?? '3,000+') ?></div><p class="stat-label">Companies</p></div>
    </div>
  </section>

  <!-- Student Work Section -->
  <?php
    $studentWorkLimit    = 6;
    $studentWorkHeading  = 'Our Student Work';
    $studentWorkSubtitle = 'See what our students create — real projects, real skills, real impact.';
    require __DIR__ . '/../../includes/section-student-work.php';
  ?>

  <!-- Goal / Mission / Vision / Value / Asset Tabs -->
  <section class="tabs-section" aria-label="Institute Values">
    <div class="tabs-card">
      <div class="tabs-grid">
        <div class="tab-nav" role="tablist">
          <button class="tab-btn active" role="tab" aria-selected="true"  onclick="switchAboutTab(this,'goal')"   id="tab-goal">Our Goal</button>
          <button class="tab-btn"        role="tab" aria-selected="false" onclick="switchAboutTab(this,'mission')" id="tab-mission">Our Mission</button>
          <button class="tab-btn"        role="tab" aria-selected="false" onclick="switchAboutTab(this,'vision')"  id="tab-vision">Our Vision</button>
          <button class="tab-btn"        role="tab" aria-selected="false" onclick="switchAboutTab(this,'values')"  id="tab-values">Our Value</button>
          <button class="tab-btn"        role="tab" aria-selected="false" onclick="switchAboutTab(this,'asset')"   id="tab-asset">Our Asset</button>
        </div>
        <div>
          <h3 class="tab-content-title" id="tabTitle">Our Goal</h3>
          <p  class="tab-content-text"  id="tabText">We aspire to eliminate unemployment from Bangladesh through technical education and help shape up digital careers for youth in global freelancing platforms and corporate technological industries.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Prominent Initiatives -->
  <section class="initiatives-section" aria-label="Prominent Initiatives">
    <h2 style="font-size:clamp(1.5rem,3vw,2rem);font-weight:800;color:var(--color-text-primary);margin-bottom:8px;">Prominent Initiatives</h2>
    <p style="color:var(--color-text-muted);font-size:var(--font-size-sm);margin-bottom:32px;">Empowering every segment of society through comprehensive free technological training.</p>
    <div class="initiatives-grid">
      <div class="initiative-card"><span class="initiative-number">1,000,000+</span><p class="initiative-label">Awareness created nationwide</p></div>
      <div class="initiative-card"><span class="initiative-number">6,000+</span><p class="initiative-label">Women got free training under ShePower</p></div>
      <div class="initiative-card"><span class="initiative-number">5,000+</span><p class="initiative-label">Students benefited from seminar monthly</p></div>
      <div class="initiative-card"><span class="initiative-number">200+</span><p class="initiative-label">Financially challenged people received training</p></div>
      <div class="initiative-card"><span class="initiative-number">12,000+</span><p class="initiative-label">Free IT education to garments workers</p></div>
      <div class="initiative-card"><span class="initiative-number">500+</span><p class="initiative-label">People with disabilities received free training</p></div>
      <div class="initiative-card"><span class="initiative-number">6,000+</span><p class="initiative-label">Remittance got added to the economy</p></div>
      <div class="initiative-card"><span class="initiative-number">45+</span><p class="initiative-label">Free seminars held across country monthly</p></div>
    </div>
  </section>
</main>

<script>
  const tabData = {
    goal:    { title: 'Our Goal',    text: 'We aspire to eliminate unemployment from Bangladesh through technical education and help shape up digital careers for youth in global freelancing platforms and corporate technological industries.' },
    mission: { title: 'Our Mission', text: 'Our mission is to provide practical, industry-standard digital skills training that equips learners to thrive in the global marketplace, with a focus on hands-on learning, mentorship, and career support.' },
    vision:  { title: 'Our Vision',  text: 'To become South Asia\'s most impactful IT training institution — creating a generation of globally competitive digital professionals from Bangladesh.' },
    values:  { title: 'Our Value',   text: 'We believe in integrity, practical excellence, inclusivity, and lifelong learning. Every student deserves quality education regardless of their background or economic status.' },
    asset:   { title: 'Our Asset',   text: 'Our greatest assets are our students, our dedicated mentors with real-world industry experience, and our state-of-the-art lab infrastructure that enables practical skill development.' }
  };
  function switchAboutTab(btn, key) {
    document.querySelectorAll('.tab-btn').forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected','false'); });
    btn.classList.add('active');
    btn.setAttribute('aria-selected','true');
    document.getElementById('tabTitle').textContent = tabData[key].title;
    document.getElementById('tabText').textContent  = tabData[key].text;
  }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
