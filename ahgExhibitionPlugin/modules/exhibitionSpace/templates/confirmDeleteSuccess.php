<?php /* heratio#146 PSIS port — Exhibition space delete confirmation */ ?>
<div class="container-fluid px-4 py-3 delete exhibition-space">
  <h1><?php echo __('Delete exhibition space') ?></h1>

  <div class="alert alert-warning">
    <?php echo __('You are about to delete %1%. This cannot be undone.', ['%1%' => $space->name]) ?>
  </div>

  <?php if ($placementCount > 0): ?>
    <div class="alert alert-danger">
      <strong><?php echo __('Blocked:') ?></strong>
      <?php echo __('%count% placement(s) are still attached to this space. Remove placements first.', ['%count%' => $placementCount]) ?>
      <div class="mt-2">
        <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'show', 'slug' => $space->slug]) ?>" class="btn btn-sm btn-outline-secondary">
          <?php echo __('Back to space') ?>
        </a>
      </div>
    </div>
  <?php else: ?>
    <form method="post" action="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'destroy', 'slug' => $space->slug]) ?>"
          onsubmit="return confirm('<?php echo __('Are you sure you want to delete this exhibition space?') ?>');">
      <button type="submit" class="btn btn-danger">
        <i class="fas fa-trash me-1"></i><?php echo __('Delete exhibition space') ?>
      </button>
      <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'show', 'slug' => $space->slug]) ?>" class="btn btn-outline-secondary">
        <?php echo __('Cancel') ?>
      </a>
    </form>
  <?php endif ?>
</div>
