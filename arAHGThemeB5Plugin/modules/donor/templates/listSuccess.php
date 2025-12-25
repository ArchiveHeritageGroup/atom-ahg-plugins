<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1 class="h3 mb-0"><?php echo __('Donor list'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php if ($donors && count($donors) > 0) { ?>
  <div class="card">
    <div class="card-header">
      <span><i class="bi bi-people me-2"></i><?php echo __('All donors'); ?></span>
    </div>
    <ul class="list-group list-group-flush">
      <?php foreach ($donors as $donor) { ?>
        <li class="list-group-item">
          <a href="<?php echo url_for(['module' => 'donor', 'slug' => $donor->slug]); ?>" class="text-decoration-none">
            <?php echo $donor->authorizedFormOfName ?: __('Untitled'); ?>
          </a>
        </li>
      <?php } ?>
    </ul>
  </div>
<?php } else { ?>
  <div class="alert alert-info" role="alert">
    <i class="bi bi-info-circle me-2"></i>
    <?php echo __('No donors have been created yet.'); ?>
  </div>
<?php } ?>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <div class="d-flex flex-wrap gap-2 mt-4">
    <?php echo link_to(
        '<i class="bi bi-plus-lg me-1"></i>'.__('Add new'),
        ['module' => 'donor', 'action' => 'add'],
        ['class' => 'btn btn-primary']
    ); ?>
  </div>
<?php end_slot(); ?>
