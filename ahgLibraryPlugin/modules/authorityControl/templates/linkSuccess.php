<?php decorate_with('layout_1col'); ?>

<?php $auth = $sf_data->getRaw('authority'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Link Subject Heading'); ?></h1>
<?php end_slot(); ?>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="alert alert-secondary">
  <?php echo __('Linking heading'); ?>:
  <strong><?php echo esc_entities($auth->heading ?? ''); ?></strong>
  <span class="badge bg-secondary"><?php echo esc_entities(ucfirst($auth->subject_type ?? 'topic')); ?></span>
</div>

<form method="post" action="<?php echo url_for(['module' => 'authorityControl', 'action' => 'link']); ?>">
  <input type="hidden" name="authority_id" value="<?php echo (int) $auth->id; ?>">

  <div class="card mb-4">
    <div class="card-body">
      <div class="row">
        <div class="col-md-8 mb-3">
          <label class="form-label required"><?php echo __('Library Item ID'); ?></label>
          <input type="number" name="library_item_id" class="form-control" min="1" required
                 placeholder="<?php echo __('library_item.id'); ?>">
          <div class="form-text"><?php echo __('The numeric ID of the library_item record to associate with this heading.'); ?></div>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label"><?php echo __('MARC source tag'); ?></label>
          <input type="text" name="source_tag" class="form-control" maxlength="10" value="650"
                 placeholder="650">
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-between">
    <a href="<?php echo url_for(['module' => 'authorityControl', 'action' => 'view', 'id' => $auth->id]); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-2"></i><?php echo __('Cancel'); ?>
    </a>
    <button type="submit" class="btn btn-success">
      <i class="fas fa-link me-2"></i><?php echo __('Link'); ?>
    </button>
  </div>
</form>
