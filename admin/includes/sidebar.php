<?php
/**
 * DreamIT Admin Console - Sidebar Navigation
 */
require_once __DIR__ . '/../config.php';
$activeMenu = $activeMenu ?? 'dashboard';

// Fetch unread enquiries count
$unreadCount = 0;
try {
    $db = Database::getConnection();
    $unreadCount = (int)$db->query("SELECT COUNT(*) FROM enquiries WHERE status = 'unread'")->fetchColumn();
} catch (Exception $e) {
    $unreadCount = 0;
}
?>
<aside class="admin-sidebar">

  <!-- Sidebar Brand Header -->
  <div class="sidebar-brand-box">
    <a href="<?= admin_url('dashboard') ?>" class="brand-link">
      <div class="brand-icon-shield">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
          <path d="M12 2L2 7l10 5 10-5-10-5z"/>
          <path d="M2 17l10 5 10-5"/>
          <path d="M2 12l10 5 10-5"/>
        </svg>
      </div>
      <div class="brand-text-col">
        <span class="brand-name">Dream<span>IT</span></span>
        <span class="brand-tag">Admin Console</span>
      </div>
    </a>
  </div>


  <!-- Navigation Menu Scroller -->
  <nav class="sidebar-nav-scroller" aria-label="Administrative navigation">

    <div class="nav-section-title">Core Management</div>

    <a href="<?= admin_url('dashboard') ?>" class="sidebar-nav-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
      <span>Dashboard</span>
    </a>

    <a href="<?= admin_url('courses') ?>" class="sidebar-nav-link <?= $activeMenu === 'courses' ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
      <span>Courses (Cards)</span>
    </a>

    <a href="<?= admin_url('courses/create') ?>" class="sidebar-nav-link <?= $activeMenu === 'courses_create' ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      <span>Add Course (Wizard)</span>
    </a>

    <a href="<?= admin_url('projects') ?>" class="sidebar-nav-link <?= $activeMenu === 'projects' ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      <span>Student Work</span>
    </a>

    <a href="<?= admin_url('departments') ?>" class="sidebar-nav-link <?= $activeMenu === 'departments' ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      <span>Departments</span>
    </a>

    <div class="nav-section-title">Inquiries & Content</div>

    <a href="<?= admin_url('enquiries') ?>" class="sidebar-nav-link <?= $activeMenu === 'enquiries' ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
      <span>Inquiries / Leads</span>
      <?php if ($unreadCount > 0): ?>
        <span class="sidebar-counter-badge"><?= $unreadCount ?></span>
      <?php endif; ?>
    </a>

    <a href="<?= admin_url('gallery') ?>" class="sidebar-nav-link <?= $activeMenu === 'gallery' ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
      <span>Campus Gallery</span>
    </a>

    <a href="<?= admin_url('faqs') ?>" class="sidebar-nav-link <?= $activeMenu === 'faqs' ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <span>FAQs & Answers</span>
    </a>

    <div class="nav-section-title">System & SEO</div>

    <a href="<?= admin_url('settings') ?>" class="sidebar-nav-link <?= $activeMenu === 'settings' ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      <span>Site Settings</span>
    </a>

    <a href="<?= admin_url('seo') ?>" class="sidebar-nav-link <?= $activeMenu === 'seo' ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
      <span>SEO Engine</span>
    </a>

  </nav>

  <!-- Sidebar Footer: Logged in User & Logout -->
  <div class="sidebar-footer-box">
    <div class="admin-user-pill">
      <div class="admin-avatar">
        <?= strtoupper(substr($adminUser['name'] ?? 'AD', 0, 2)) ?>
      </div>
      <div class="admin-meta">
        <span class="admin-name"><?= e($adminUser['name'] ?? 'Admin') ?></span>
        <span class="admin-role"><?= e($adminUser['role'] ?? 'super_admin') ?></span>
      </div>
    </div>
    <a href="<?= admin_url('logout') ?>" class="btn-card-action btn-card-delete" title="Sign out of Admin Console">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
  </div>

</aside>
