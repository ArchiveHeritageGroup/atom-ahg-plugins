<?php use_helper('I18N') ?>

<!-- Contextual Help Offcanvas Panel — rendered by help-context.js -->
<div class="offcanvas offcanvas-end help-offcanvas" tabindex="-1" id="helpOffcanvas" aria-labelledby="helpOffcanvasLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="helpOffcanvasLabel"><?php echo __('Help') ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?php echo __('Close') ?>"></button>
  </div>
  <div class="offcanvas-body" id="helpOffcanvasBody">
    <div class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2 text-muted"><?php echo __('Loading help article...') ?></p>
    </div>
  </div>
  <div class="border-top p-2 text-center">
    <a href="<?php echo url_for('@help_index') ?>" class="btn btn-sm btn-outline-primary">
      <?php echo __('Open Help Center') ?>
    </a>
  </div>
</div>
