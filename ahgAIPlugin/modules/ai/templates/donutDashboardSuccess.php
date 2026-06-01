<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('DONUT Document Understanding'); ?></h1>
<?php end_slot(); ?>

<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <?php if ($available): ?>
        <div class="alert alert-success">
          <strong><?php echo __('DONUT gateway: online'); ?></strong>
          <?php if (is_array($health) && isset($health['model'])): ?>
            &mdash; <?php echo esc_specialchars($health['model']); ?>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-warning">
          <strong><?php echo __('DONUT gateway: offline'); ?></strong>
          &mdash; <?php echo __('Document parsing is temporarily unavailable. Records can still be entered manually.'); ?>
        </div>
      <?php endif; ?>

      <div class="card mb-3">
        <div class="card-header"><?php echo __('Training status'); ?></div>
        <div class="card-body">
          <?php if (is_array($training)): ?>
            <pre class="mb-0"><?php echo esc_specialchars(json_encode($training, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
          <?php else: ?>
            <p class="text-muted mb-0"><?php echo __('Not available.'); ?></p>
          <?php endif; ?>
        </div>
      </div>

      <p class="text-muted">
        <?php echo __('Use the AI overlay on a document edit page to pre-fill fields from a scanned image, or run'); ?>
        <code>php bin/atom ai:donut-extract --file=&lt;path&gt;</code>
        <?php echo __('from the command line.'); ?>
      </p>
    </div>
  </div>
</div>
