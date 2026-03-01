<?php decorate_with('layout_1col'); ?>

<?php
  $rawActor = $sf_data->getRaw('actor');
  $actor = is_object($rawActor) ? $rawActor : (object) $rawActor;
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-divide me-2"></i><?php echo __('Split Authority Record'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ahg_authority_dashboard'); ?>"><?php echo __('Authority Dashboard'); ?></a>
      </li>
      <li class="breadcrumb-item">
        <a href="/<?php echo $actor->slug ?? ''; ?>"><?php echo htmlspecialchars($actor->name ?? ''); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Split'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <div class="card mb-3">
    <div class="card-header">
      <i class="fas fa-divide me-1"></i><?php echo __('Split: %1%', ['%1%' => htmlspecialchars($actor->name ?? '')]); ?>
    </div>
    <div class="card-body">
      <p class="text-muted"><?php echo __('Select fields and relations to move to a new authority record.'); ?></p>

      <form method="post" action="<?php echo url_for('@ahg_authority_split?id=' . $actor->id); ?>">
        <div class="mb-3">
          <label class="form-label"><?php echo __('New actor name'); ?></label>
          <input type="text" name="new_name" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label"><?php echo __('Notes'); ?></label>
          <textarea name="notes" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-warning">
          <i class="fas fa-divide me-1"></i><?php echo __('Create Split Request'); ?>
        </button>
        <a href="<?php echo url_for('@ahg_authority_dashboard'); ?>" class="btn btn-outline-secondary ms-2">
          <?php echo __('Cancel'); ?>
        </a>
      </form>
    </div>
  </div>

<?php end_slot(); ?>
