<?php decorate_with('layout_1col'); ?>

<?php $rawPatron = $sf_data->getRaw('patron'); ?>
<?php $rawPatronTypes = $sf_data->getRaw('patronTypes'); ?>

<?php slot('title'); ?>
  <h1><?php echo $rawPatron ? __('Edit Patron: %1%', ['%1%' => esc_entities($rawPatron->first_name . ' ' . $rawPatron->last_name)]) : __('Add Patron'); ?></h1>
<?php end_slot(); ?>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<form method="post" action="<?php echo url_for(['module' => 'patron', 'action' => 'edit', 'id' => ($rawPatron->id ?? null)]); ?>">

  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-user me-2"></i><?php echo __('Patron Information'); ?></h5>
    </div>
    <div class="card-body">

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label required"><?php echo __('First Name'); ?></label>
          <input type="text" name="first_name" class="form-control" required
                 value="<?php echo esc_entities($rawPatron->first_name ?? ''); ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label required"><?php echo __('Last Name'); ?></label>
          <input type="text" name="last_name" class="form-control" required
                 value="<?php echo esc_entities($rawPatron->last_name ?? ''); ?>">
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label"><?php echo __('Email'); ?></label>
          <input type="email" name="email" class="form-control"
                 value="<?php echo esc_entities($rawPatron->email ?? ''); ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label"><?php echo __('Phone'); ?></label>
          <input type="text" name="phone" class="form-control"
                 value="<?php echo esc_entities($rawPatron->phone ?? ''); ?>">
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label"><?php echo __('Patron Type'); ?></label>
          <select name="patron_type" class="form-select">
            <?php if (!empty($rawPatronTypes)): ?>
              <?php foreach ($rawPatronTypes as $key => $label): ?>
                <option value="<?php echo esc_entities($key); ?>" <?php echo ($rawPatron->patron_type ?? 'general') === $key ? 'selected' : ''; ?>>
                  <?php echo esc_entities($label); ?>
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="general" <?php echo ($rawPatron->patron_type ?? 'general') === 'general' ? 'selected' : ''; ?>><?php echo __('General'); ?></option>
              <option value="student" <?php echo ($rawPatron->patron_type ?? '') === 'student' ? 'selected' : ''; ?>><?php echo __('Student'); ?></option>
              <option value="staff" <?php echo ($rawPatron->patron_type ?? '') === 'staff' ? 'selected' : ''; ?>><?php echo __('Staff'); ?></option>
              <option value="researcher" <?php echo ($rawPatron->patron_type ?? '') === 'researcher' ? 'selected' : ''; ?>><?php echo __('Researcher'); ?></option>
              <option value="external" <?php echo ($rawPatron->patron_type ?? '') === 'external' ? 'selected' : ''; ?>><?php echo __('External'); ?></option>
            <?php endif; ?>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label"><?php echo __('Expiry Date'); ?></label>
          <input type="date" name="expiry_date" class="form-control"
                 value="<?php echo esc_entities($rawPatron->expiry_date ?? ''); ?>">
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label"><?php echo __('Max Checkouts'); ?></label>
          <input type="number" name="max_checkouts" class="form-control" min="0" max="100"
                 value="<?php echo (int) ($rawPatron->max_checkouts ?? 5); ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label"><?php echo __('Max Holds'); ?></label>
          <input type="number" name="max_holds" class="form-control" min="0" max="100"
                 value="<?php echo (int) ($rawPatron->max_holds ?? 3); ?>">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label"><?php echo __('Notes'); ?></label>
        <textarea name="notes" class="form-control" rows="3"><?php echo esc_entities($rawPatron->notes ?? ''); ?></textarea>
      </div>

    </div>
  </div>

  <div class="d-flex justify-content-between">
    <a href="<?php echo url_for(['module' => 'patron', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-times me-1"></i><?php echo __('Cancel'); ?>
    </a>
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save me-1"></i><?php echo $rawPatron ? __('Update Patron') : __('Create Patron'); ?>
    </button>
  </div>

</form>
