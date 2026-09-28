<?php use_helper('I18N', 'Date') ?>
<?php decorate_with('layout_1col'); ?>

<?php slot('content'); ?>

<div class="container-fluid">
  <?php if ($sf_user->hasFlash('notice')) { ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
  <?php } ?>
  <?php if ($sf_user->hasFlash('error')) { ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
  <?php } ?>

    <div class="row">
    <div class="col-md-3">
      <!-- Location path -->
      <?php if ($location['parent_id']): ?>
        <div class="card mb-3">
          <div class="card-header">
            <h5 class="mb-0"><?php echo __('Deleting') ?></h5>
          </div>
          <div class="card-body">
            <ol class="breadcrumb">
              <?php foreach ($path as $index => $locationPath): ?>
                <li class="breadcrumb-item <?php echo ($index == count($path) - 1 ? 'active' : '') ?>">
                  <?php if ($index == count($path) - 1): ?>
                    <?php echo $locationPath['name'] ?>
                  <?php else: ?>
                    <?php echo link_to($locationPath['name'], 'storageLocation/view?id=' . $locationPath['id']) ?>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ol>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-md-9">
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Delete Storage Location') ?></h5>
        </div>
        <div class="card-body">
          <div class="alert alert-warning">
            <h4><?php echo __('Are you sure you want to delete this location?') ?></h4>
            <p><strong><?php echo __('Name') ?>:</strong> <?php echo $location['name'] ?></p>
            <p><strong><?php echo __('Type') ?>:</strong> <?php echo __($location['location_type']) ?></p>
            
            <?php if ($children): ?>
              <div class="alert alert-info">
                <h5><?php echo __('This location has children:') ?></h5>
                <ul>
                  <?php foreach ($children as $child): ?>
                    <li><?php echo $child['name'] ?> (<?php echo __($child['location_type']) ?>)</li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <?php if ($descendants): ?>
              <div class="alert alert-info">
                <h5><?php echo __('This location has descendants:') ?></h5>
                <ul>
                  <?php foreach ($descendants as $descendant): ?>
                    <li><?php echo str_repeat('&nbsp;&nbsp;', $descendant['level']) ?><?php echo $descendant['name'] ?> (<?php echo __($descendant['location_type']) ?>)</li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form method="post" action="<?php echo url_for('storageLocation/delete') ?>">
              <input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars(function_exists('csrf_token') ? csrf_token() : (class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : ''), ENT_QUOTES); ?>">
              <input type="hidden" name="id" value="<?php echo $location['id'] ?>" />
              
              <button type="submit" class="btn btn-danger"><?php echo __('Delete Location') ?></button>
              <?php echo link_to(__('Cancel'), 'storageLocation/view?id=' . $location['id'], array('class' => 'btn btn-secondary')) ?>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php end_slot(); ?>
