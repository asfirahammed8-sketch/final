<?php
/**
 * DreamIT Admin Console - Global Header & Topbar
 */
require_once __DIR__ . '/../config.php';
$adminUser = current_admin();
$adminSlug = get_admin_slug();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Admin Console') ?> | DreamIT Console</title>

  <!-- CSRF Meta -->
  <meta name="admin-csrf" content="<?= admin_csrf_token() ?>">

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Admin Global Config -->
  <script>
    window.ADMIN_CONFIG = {
      baseUrl: '<?= rtrim(base_url(), "/") ?>',
      adminUrl: '<?= rtrim(admin_url(), "/") ?>',
      uploadUrl: '<?= admin_url("api/upload") ?>'
    };
  </script>

  <!-- Admin Stylesheets -->
  <link rel="stylesheet" href="<?= base_url('admin/assets/css/admin-theme.css') ?>">
  <link rel="stylesheet" href="<?= base_url('admin/assets/css/admin-components.css') ?>">
  <?php if (!empty($includeWizardCss)): ?>
    <link rel="stylesheet" href="<?= base_url('admin/assets/css/admin-wizard.css') ?>">
  <?php endif; ?>
</head>
<body class="admin-body">

<div class="admin-app-wrapper">

  <!-- Sidebar Include -->
  <?php require_once __DIR__ . '/sidebar.php'; ?>

  <!-- Main Viewport -->
  <div class="admin-main-viewport">

    <!-- Topbar -->
    <header class="admin-topbar">
      <div class="topbar-left">
        <button type="button" class="mobile-menu-toggle" id="mobileSidebarToggle" aria-label="Toggle navigation menu">
          <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <h1 class="page-headline"><?= e($pageHeadline ?? 'Dashboard') ?></h1>
      </div>

      <div class="topbar-right">
        <!-- View Live Site Link -->
        <a href="<?= base_url('/') ?>" target="_blank" class="topbar-btn btn-ghost" title="Open public website in a new tab">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          <span>Live Site</span>
        </a>

        <!-- Quick Add Action Button -->
        <a href="<?= admin_url('courses/create') ?>" class="topbar-btn btn-primary-action">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="12" y2="12"/></svg>
          <span>New Course</span>
        </a>
      </div>
    </header>

    <!-- Main Canvas Area -->
    <main class="admin-canvas">
