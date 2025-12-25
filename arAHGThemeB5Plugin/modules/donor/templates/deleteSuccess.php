<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1 class="h3 mb-0">
    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
    <?php echo __('Delete donor'); ?>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="card border-danger">
  <div class="card-header bg-danger text-white">
    <h5 class="mb-0"><i class="bi bi-trash me-2"></i><?php echo __('Confirm deletion'); ?></h5>
  </div>
  <div class="card-body">
    <p class="lead">
      <?php echo __('Are you sure you want to delete %1%?', ['%1%' => '<strong>'.esc_entities($donor->authorizedFormOfName ?: $donor->slug).'</strong>']); ?>
    </p>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle me-2"></i>
      <?php echo __('This action cannot be undone. All related contact information and relationships will also be deleted.'); ?>
    </div>

    <form method="post" action="<?php echo url_for(['module' => 'donor', 'action' => 'delete', 'slug' => $donor->slug]); ?>">
      <div class="d-flex flex-wrap gap-2 mt-4">
        <a href="<?php echo url_for(['module' => 'donor', 'action' => 'index', 'slug' => $donor->slug]); ?>" class="btn btn-outline-secondary">
          <i class="bi bi-x-lg me-1"></i><?php echo __('Cancel'); ?>
        </a>
        <button type="submit" class="btn btn-danger">
          <i class="bi bi-trash me-1"></i><?php echo __('Delete'); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<?php end_slot(); ?>
