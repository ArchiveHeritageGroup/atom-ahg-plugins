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

      <!-- Objects held here, and bulk relocation -->
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><?php echo __('Objects in this location') ?></h5>
          <span class="badge bg-secondary"><?php echo count($objects) ?></span>
        </div>
        <div class="card-body">
          <?php if (0 === count($objects)) { ?>
            <p class="mb-0 text-muted"><?php echo __('Nothing is stored here.') ?></p>
          <?php } elseif (!$sf_user->hasCredential('administrator')) { ?>
            <ul class="mb-0">
              <?php foreach ($objects as $object) { ?>
                <li><?php echo $object['name'] ?: __('Object %1%', ['%1%' => $object['physical_object_id']]) ?></li>
              <?php } ?>
            </ul>
          <?php } else { ?>
            <?php // One form, one POST: the whole selection moves as a single
                  // batch, so a relocation cannot half happen. ?>
            <form method="post" action="<?php echo url_for('storageLocation/moveObjects?id='.$location['id']) ?>">
              <input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars(function_exists('csrf_token') ? csrf_token() : (class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : ''), ENT_QUOTES); ?>">

              <div class="table-responsive">
                <table class="table table-striped table-bordered mb-3">
                  <thead>
                    <tr>
                      <th style="width: 3rem;"><span class="visually-hidden"><?php echo __('Select') ?></span></th>
                      <th><?php echo __('Object') ?></th>
                      <th><?php echo __('Here since') ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($objects as $object) { ?>
                      <tr>
                        <td>
                          <input class="form-check-input" type="checkbox"
                                 name="objects[]" value="<?php echo $object['physical_object_id'] ?>"
                                 id="object-<?php echo $object['physical_object_id'] ?>">
                        </td>
                        <td>
                          <label class="form-check-label" for="object-<?php echo $object['physical_object_id'] ?>">
                            <?php echo $object['name'] ?: __('Object %1%', ['%1%' => $object['physical_object_id']]) ?>
                          </label>
                        </td>
                        <td><?php echo $object['updated_at'] ? format_date($object['updated_at'], 'f') : '' ?></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>

              <div class="row g-2 align-items-end">
                <div class="col-md-5">
                  <label class="form-label" for="to_location_id"><?php echo __('Move selected to') ?></label>
                  <select class="form-select" id="to_location_id" name="to_location_id">
                    <option value=""><?php echo __('Out of storage') ?></option>
                    <?php foreach ($destinations as $destination) { ?>
                      <option value="<?php echo $destination['id'] ?>">
                        <?php echo str_repeat('&nbsp;&nbsp;', (int) $destination['level']) ?><?php echo $destination['name'] ?>
                      </option>
                    <?php } ?>
                  </select>
                </div>
                <div class="col-md-5">
                  <label class="form-label" for="move-note"><?php echo __('Reason') ?></label>
                  <input class="form-control" type="text" id="move-note" name="note"
                         placeholder="<?php echo __('Why it moved, for the record') ?>">
                </div>
                <div class="col-md-2 d-grid">
                  <button class="btn btn-primary" type="submit"><?php echo __('Move') ?></button>
                </div>
              </div>
            </form>
          <?php } ?>
        </div>
      </div>

      <!-- Movement history -->
      <?php if (count($movements) > 0) { ?>
        <div class="card mb-3">
          <div class="card-header">
            <h5 class="mb-0"><?php echo __('Recent movements') ?></h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-striped mb-0">
                <thead>
                  <tr>
                    <th><?php echo __('When') ?></th>
                    <th><?php echo __('What') ?></th>
                    <th><?php echo __('From') ?></th>
                    <th><?php echo __('To') ?></th>
                    <th><?php echo __('By') ?></th>
                    <th><?php echo __('Reason') ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($movements as $movement) { ?>
                    <tr>
                      <td><?php echo format_date($movement['moved_at'], 'f') ?></td>
                      <td><?php echo $movement['subject_name'] ?: $movement['subject_type'].' '.$movement['subject_id'] ?></td>
                      <td><?php echo $movement['from_location_name'] ?: '-' ?></td>
                      <td><?php echo $movement['to_location_name'] ?: '-' ?></td>
                      <td><?php echo $movement['username'] ?: '-' ?></td>
                      <td><?php echo $movement['note'] ?: '' ?></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php } ?>

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
