<?php
$s = $sf_data->getRaw('schedule');
$isNew = $sf_data->getRaw('isNew');
$repositories = $sf_data->getRaw('repositories') ?: [];
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-edit me-2"></i><?php echo $isNew ? __('New Schedule') : __('Edit Schedule'); ?></h1>
    <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'schedules']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Schedules'); ?>
    </a>
  </div>

  <form method="post" action="<?php echo url_for(['module' => 'integrity', 'action' => 'scheduleEdit', 'id' => $s->id ?? '']); ?>">
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><?php echo __('General'); ?></h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="name"><?php echo __('Schedule Name'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($s->name ?? ''); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="description"><?php echo __('Description'); ?></label>
            <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($s->description ?? ''); ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Scope & Algorithm'); ?></h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="scope_type"><?php echo __('Scope'); ?></label>
            <select class="form-select" id="scope_type" name="scope_type">
              <option value="global" <?php echo ($s->scope_type ?? 'global') === 'global' ? 'selected' : ''; ?>><?php echo __('Global'); ?></option>
              <option value="repository" <?php echo ($s->scope_type ?? '') === 'repository' ? 'selected' : ''; ?>><?php echo __('Repository'); ?></option>
              <option value="hierarchy" <?php echo ($s->scope_type ?? '') === 'hierarchy' ? 'selected' : ''; ?>><?php echo __('Hierarchy'); ?></option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="algorithm"><?php echo __('Hash Algorithm'); ?></label>
            <select class="form-select" id="algorithm" name="algorithm">
              <option value="sha256" <?php echo ($s->algorithm ?? 'sha256') === 'sha256' ? 'selected' : ''; ?>>SHA-256</option>
              <option value="sha512" <?php echo ($s->algorithm ?? '') === 'sha512' ? 'selected' : ''; ?>>SHA-512</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="frequency"><?php echo __('Frequency'); ?></label>
            <select class="form-select" id="frequency" name="frequency">
              <option value="daily" <?php echo ($s->frequency ?? '') === 'daily' ? 'selected' : ''; ?>><?php echo __('Daily'); ?></option>
              <option value="weekly" <?php echo ($s->frequency ?? 'weekly') === 'weekly' ? 'selected' : ''; ?>><?php echo __('Weekly'); ?></option>
              <option value="monthly" <?php echo ($s->frequency ?? '') === 'monthly' ? 'selected' : ''; ?>><?php echo __('Monthly'); ?></option>
              <option value="ad_hoc" <?php echo ($s->frequency ?? '') === 'ad_hoc' ? 'selected' : ''; ?>><?php echo __('Ad hoc'); ?></option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'schedules']); ?>" class="btn btn-secondary"><?php echo __('Cancel'); ?></a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save Schedule'); ?></button>
    </div>
  </form>
</main>
