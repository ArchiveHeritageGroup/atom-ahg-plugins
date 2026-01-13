<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
<div class="d-flex align-items-center mb-3">
  <i class="fas fa-trash fa-2x text-danger me-3"></i>
  <div>
    <h1 class="h3 mb-0"><?php echo __('Delete Request') ?></h1>
    <p class="text-muted mb-0"><?php echo __('Confirm deletion of publication request') ?></p>
  </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card shadow-sm border-danger">
      <div class="card-header bg-danger text-white">
        <h5 class="card-title mb-0">
          <i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Confirm Deletion') ?>
        </h5>
      </div>
      <div class="card-body">
        <p class="mb-3"><?php echo __('Are you sure you want to delete this publication request?') ?></p>
        
        <div class="alert alert-light border mb-4">
          <strong><?php echo __('Request from') ?>:</strong> 
          <?php echo esc_entities(($resource->rtp_name ?? '') . ' ' . ($resource->rtp_surname ?? '')) ?>
          <br>
          <strong><?php echo __('Submitted') ?>:</strong> 
          <?php echo date('d M Y H:i', strtotime($resource->created_at)) ?>
        </div>
        
        <p class="text-danger mb-4">
          <i class="fas fa-warning me-1"></i>
          <strong><?php echo __('This action cannot be undone.') ?></strong>
        </p>
        
        <form method="post" action="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'delete', 'slug' => $resource->slug]) ?>">
          <input type="hidden" name="confirm" value="yes">
          <div class="d-flex justify-content-between">
            <a href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'browse']) ?>" class="btn btn-secondary">
              <i class="fas fa-times me-1"></i><?php echo __('Cancel') ?>
            </a>
            <button type="submit" class="btn btn-danger">
              <i class="fas fa-trash me-1"></i><?php echo __('Yes, Delete Request') ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php end_slot() ?>
