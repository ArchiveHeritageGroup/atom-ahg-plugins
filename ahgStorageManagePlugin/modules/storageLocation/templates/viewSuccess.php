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
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Location Path') ?></h5>
        </div>
        <div class="card-body">
          <?php if (count($path) > 0): ?>
            <ol class="breadcrumb">
              <?php foreach ($path as $index => $location): ?>
                <li class="breadcrumb-item <?php echo ($index == count($path) - 1 ? 'active' : '') ?>">
                  <?php if ($index == count($path) - 1): ?>
                    <?php echo $location['name'] ?>
                  <?php else: ?>
                    <?php echo link_to($location['name'], 'storageLocation/view?id=' . $location['id']) ?>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php else: ?>
            <p><?php echo __('Root level') ?></p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Location hierarchy tree -->
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Location Hierarchy') ?></h5>
        </div>
        <div class="card-body">
          <?php include_partial('storageLocation/tree', array('tree' => array($location))) ?>
        </div>
      </div>
    </div>

    <div class="col-md-9">
      <!-- Location details -->
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><?php echo __('Storage Location Details') ?></h5>
          <div>
            <?php echo link_to(__('Edit'), 'storageLocation/edit?id=' . $location['id'], array('class' => 'btn btn-sm btn-outline-primary')) ?>
            <?php echo link_to(__('Delete'), 'storageLocation/delete?id=' . $location['id'], array('class' => 'btn btn-sm btn-outline-danger')) ?>
          </div>
        </div>
        <div class="card-body">
          <dl class="row">
            <dt class="col-sm-3"><?php echo __('Name') ?>:</dt>
            <dd class="col-sm-9"><?php echo $location['name'] ?></dd>

            <dt class="col-sm-3"><?php echo __('Type') ?>:</dt>
            <dd class="col-sm-9"><?php echo __($location['location_type']) ?></dd>

            <dt class="col-sm-3"><?php echo __('Level') ?>:</dt>
            <dd class="col-sm-9"><?php echo $location['level'] ?></dd>

            <?php if ($location['description']): ?>
              <dt class="col-sm-3"><?php echo __('Description') ?>:</dt>
              <dd class="col-sm-9"><?php echo $location['description'] ?></dd>
            <?php endif; ?>

            <?php if ($location['capacity_value']): ?>
              <dt class="col-sm-3"><?php echo __('Capacity') ?>:</dt>
              <dd class="col-sm-9">
                <?php echo format_number_choice('[0]No capacity|{1}1 unit|[1,Inf]%1% units', array('%1%' => $location['capacity_value']), $location['capacity_value']) ?>
                <?php echo ' (' . __($location['capacity_unit']) . ')' ?>
              </dd>
            <?php endif; ?>

            <?php if ($location['notes']): ?>
              <dt class="col-sm-3"><?php echo __('Notes') ?>:</dt>
              <dd class="col-sm-9"><?php echo $location['notes'] ?></dd>
            <?php endif; ?>

            <dt class="col-sm-3"><?php echo __('Created') ?>:</dt>
            <dd class="col-sm-9"><?php echo format_date($location['created_at'], 'f') ?></dd>

            <dt class="col-sm-3"><?php echo __('Updated') ?>:</dt>
            <dd class="col-sm-9"><?php echo format_date($location['updated_at'], 'f') ?></dd>
          </dl>
        </div>
      </div>

      <!-- Children locations -->
      <?php if (count($children) > 0): ?>
        <div class="card mb-3">
          <div class="card-header">
            <h5 class="mb-0"><?php echo __('Child Locations') ?></h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th><?php echo __('Name') ?></th>
                    <th><?php echo __('Type') ?></th>
                    <th><?php echo __('Level') ?></th>
                    <th><?php echo __('Actions') ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($children as $child): ?>
                    <tr>
                      <td><?php echo link_to($child['name'], 'storageLocation/view?id=' . $child['id']) ?></td>
                      <td><?php echo __($child['location_type']) ?></td>
                      <td><?php echo $child['level'] ?></td>
                      <td>
                        <div class="btn-group" role="group">
                          <?php echo link_to(__('View'), 'storageLocation/view?id=' . $child['id'], array('class' => 'btn btn-sm btn-outline-primary')) ?>
                          <?php echo link_to(__('Edit'), 'storageLocation/edit?id=' . $child['id'], array('class' => 'btn btn-sm btn-outline-secondary')) ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Descendants -->
      <?php if (count($descendants) > 0): ?>
        <div class="card mb-3">
          <div class="card-header">
            <h5 class="mb-0"><?php echo __('Descendants') ?></h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th><?php echo __('Name') ?></th>
                    <th><?php echo __('Type') ?></th>
                    <th><?php echo __('Level') ?></th>
                    <th><?php echo __('Actions') ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($descendants as $descendant): ?>
                    <tr>
                      <td><?php echo str_repeat('&nbsp;&nbsp;', $descendant['level']) ?><?php echo link_to($descendant['name'], 'storageLocation/view?id=' . $descendant['id']) ?></td>
                      <td><?php echo __($descendant['location_type']) ?></td>
                      <td><?php echo $descendant['level'] ?></td>
                      <td>
                        <div class="btn-group" role="group">
                          <?php echo link_to(__('View'), 'storageLocation/view?id=' . $descendant['id'], array('class' => 'btn btn-sm btn-outline-primary')) ?>
                          <?php echo link_to(__('Edit'), 'storageLocation/edit?id=' . $descendant['id'], array('class' => 'btn btn-sm btn-outline-secondary')) ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="d-flex justify-content-between">
        <?php echo link_to(__('Back to Browse'), 'storageLocation/browse', array('class' => 'btn btn-secondary')) ?>
        <?php echo link_to(__('Create Child Location'), 'storageLocation/create?parent_id=' . $location['id'], array('class' => 'btn btn-primary')) ?>
      </div>
    </div>
  </div>
</div>

<?php end_slot(); ?>
