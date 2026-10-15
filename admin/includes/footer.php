<?php
/**
 * DreamIT Admin Console - Global Footer & Scripts
 */
?>
    </main><!-- /.admin-canvas -->
  </div><!-- /.admin-main-viewport -->
</div><!-- /.admin-app-wrapper -->

<!-- Toast Notifications Container -->
<div class="admin-toast-container"></div>

<!-- Admin Core Script -->
<script src="<?= base_url('admin/assets/js/admin-core.js') ?>"></script>

<?php if (!empty($includeWizardJs)): ?>
  <script src="<?= base_url('admin/assets/js/admin-wizard.js') ?>"></script>
<?php endif; ?>

<?php if (!empty($_SESSION['admin_flash_success'])): ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      showAdminToast('success', <?= json_encode($_SESSION['admin_flash_success']) ?>);
    });
  </script>
  <?php unset($_SESSION['admin_flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['admin_flash_error'])): ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      showAdminToast('error', <?= json_encode($_SESSION['admin_flash_error']) ?>);
    });
  </script>
  <?php unset($_SESSION['admin_flash_error']); ?>
<?php endif; ?>

</body>
</html>
