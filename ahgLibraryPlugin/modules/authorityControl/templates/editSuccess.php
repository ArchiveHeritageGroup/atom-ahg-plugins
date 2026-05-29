<?php decorate_with('layout_1col'); ?>

<?php $auth = $sf_data->getRaw('authority'); ?>

<?php slot('title'); ?>
  <h1><?php echo $auth ? __('Edit Authority Record') : __('Add Authority Record'); ?></h1>
<?php end_slot(); ?>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<form method="post" action="<?php echo url_for(['module' => 'authorityControl', 'action' => 'edit', 'id' => ($auth->id ?? null)]); ?>">

  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-tags me-2"></i><?php echo __('Authority Record'); ?></h5>
    </div>
    <div class="card-body">

      <div class="mb-3">
        <label class="form-label required"><?php echo __('Heading'); ?></label>
        <input type="text" name="heading" class="form-control" maxlength="500" required
               value="<?php echo esc_entities($auth->heading ?? ''); ?>">
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label"><?php echo __('Subject Type'); ?></label>
          <select name="subject_type" class="form-select">
            <?php $curType = $auth->subject_type ?? 'topic'; ?>
            <?php foreach (['topic' => 'Topical', 'name' => 'Name', 'geographic' => 'Geographic', 'temporal' => 'Temporal', 'genre' => 'Genre', 'title' => 'Title'] as $key => $label): ?>
              <option value="<?php echo $key; ?>" <?php echo $curType === $key ? 'selected' : ''; ?>><?php echo __($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label"><?php echo __('Source'); ?></label>
          <input type="text" name="source" class="form-control" maxlength="100" placeholder="lcsh, mesh, aat, fast, local..."
                 value="<?php echo esc_entities($auth->source ?? 'local'); ?>">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label"><?php echo __('URI'); ?></label>
        <input type="url" name="uri" class="form-control" maxlength="500" placeholder="https://id.loc.gov/authorities/subjects/..."
               value="<?php echo esc_entities($auth->uri ?? ''); ?>">
      </div>

    </div>
  </div>

  <div class="d-flex justify-content-between">
    <a href="<?php echo url_for(['module' => 'authorityControl', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-2"></i><?php echo __('Cancel'); ?>
    </a>
    <button type="submit" class="btn btn-success">
      <i class="fas fa-save me-2"></i><?php echo __('Save'); ?>
    </button>
  </div>

</form>
