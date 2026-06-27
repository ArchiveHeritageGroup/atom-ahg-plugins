<h1 class="h3 mb-4"><i class="fas fa-box-open me-2"></i><?php echo __('Spectrum — Installation Status'); ?></h1>

<?php if (!empty($installed)): ?>
  <div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i>
    <?php echo __('Spectrum tables are installed and available.'); ?>
  </div>
<?php else: ?>
  <div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?php echo __('Spectrum tables are not yet installed.'); ?>
    <?php echo __('Run the plugin install SQL (database/install.sql) to create them.'); ?>
  </div>
<?php endif; ?>

<div class="mt-3">
  <a href="/spectrum/dashboard" class="btn btn-outline-primary">
    <i class="fas fa-gauge-high me-1"></i><?php echo __('Spectrum dashboard'); ?>
  </a>
</div>
