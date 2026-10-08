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
      <?php if ($parentLocation): ?>
        <div class="card mb-3">
          <div class="card-header">
            <h5 class="mb-0"><?php echo __('Creating in') ?></h5>
          </div>
          <div class="card-body">
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
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-md-9">
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Create New Storage Location') ?></h5>
        </div>
        <div class="card-body">
          <form method="post" action="<?php echo url_for('storageLocation/save') ?>">
            <input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars(function_exists('csrf_token') ? csrf_token() : (class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : ''), ENT_QUOTES); ?>">
            
            <input type="hidden" name="parent_id" value="<?php echo $parentId ?>" />
            
            <div class="form-group">
              <label for="name"><?php echo __('Name') ?> *</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <div class="form-group">
              <label for="description"><?php echo __('Description') ?></label>
              <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>

            <div class="form-group">
              <label for="location_type"><?php echo __('Location Type') ?> *</label>
              <select class="form-control" id="location_type" name="location_type" required>
                <option value=""><?php echo __('Select a type') ?></option>
                <?php foreach ($types as $code => $label) { ?>
                  <option value="<?php echo htmlspecialchars((string) $code, ENT_QUOTES) ?>"><?php echo __($label) ?></option>
                <?php } ?>
              </select>
            </div>

            <div class="form-group">
              <label for="parent_id"><?php echo __('Parent Location') ?></label>
              <select class="form-control" id="parent_id" name="parent_id">
                <option value=""><?php echo __('None (Root level)') ?></option>
                <?php foreach ($allLocations as $location): ?>
                  <option value="<?php echo $location['id'] ?>" <?php echo ($parentId == $location['id'] ? 'selected' : '') ?>>
                    <?php echo str_repeat('&nbsp;&nbsp;', $location['level']) ?><?php echo $location['name'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="capacity_value"><?php echo __('Capacity Value') ?></label>
                <input type="number" step="0.01" class="form-control" id="capacity_value" name="capacity_value">
              </div>
              
              <div class="form-group col-md-6">
                <label for="capacity_unit"><?php echo __('Capacity Unit') ?></label>
                <select class="form-control" id="capacity_unit" name="capacity_unit">
                  <option value="linear_meters"><?php echo __('Linear meters') ?></option>
                  <option value="shelves"><?php echo __('Shelves') ?></option>
                  <option value="boxes"><?php echo __('Boxes') ?></option>
                  <option value="cubic_meters"><?php echo __('Cubic meters') ?></option>
                  <option value="items"><?php echo __('Items') ?></option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="notes"><?php echo __('Notes') ?></label>
              <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><?php echo __('Create Location') ?></button>
            <?php echo link_to(__('Cancel'), 'storageLocation/browse', array('class' => 'btn btn-secondary')) ?>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php end_slot(); ?>
