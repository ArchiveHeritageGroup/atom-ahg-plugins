<?php /* Spectrum Phase C2 — cross-procedure chain rules admin (PSIS port) */ ?>
<div class="container-fluid px-4 py-3 spectrum chain-rules">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="mb-0 flex-grow-1"><i class="fas fa-link me-2"></i><?php echo __('Spectrum chain rules') ?></h1>
    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'spectrumDashboard']) ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Compliance dashboard') ?>
    </a>
  </div>

  <p class="text-muted"><?php echo __('When a procedure completes, automatically spawn a task on a downstream procedure for the same object.') ?></p>

  <?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('notice') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif ?>
  <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif ?>

  <div class="card mb-4">
    <div class="card-header"><strong><?php echo __('Add a chain rule') ?></strong></div>
    <div class="card-body">
      <form method="post" action="<?php echo url_for(['module' => 'workflow', 'action' => 'spectrumChainSave']) ?>" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label for="from_procedure" class="form-label small"><?php echo __('When THIS completes') ?></label>
          <select name="from_procedure" id="from_procedure" class="form-select form-select-sm" required>
            <?php foreach ($procedures as $code => $label): ?>
              <option value="<?php echo esc_entities($code) ?>"><?php echo esc_entities(__($label)) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="to_procedure" class="form-label small"><?php echo __('Spawn a task on THIS') ?></label>
          <select name="to_procedure" id="to_procedure" class="form-select form-select-sm" required>
            <?php foreach ($procedures as $code => $label): ?>
              <option value="<?php echo esc_entities($code) ?>"><?php echo esc_entities(__($label)) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-2">
          <label for="trigger_event" class="form-label small"><?php echo __('Trigger') ?></label>
          <select name="trigger_event" id="trigger_event" class="form-select form-select-sm">
            <option value="on_complete" selected><?php echo __('On complete') ?></option>
            <option value="on_approve"><?php echo __('On approve') ?></option>
            <option value="on_first_step"><?php echo __('On first step') ?></option>
          </select>
        </div>
        <div class="col-md-1">
          <div class="form-check"><input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
            <label class="form-check-label small" for="is_active"><?php echo __('Active') ?></label></div>
        </div>
        <div class="col-md-1">
          <button type="submit" class="btn btn-sm btn-success w-100"><i class="fas fa-plus me-1"></i><?php echo __('Add') ?></button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><strong><?php echo __('Existing rules') ?></strong></div>
    <div class="card-body p-0">
      <?php if ($rules->isEmpty()): ?>
        <div class="p-3 text-muted small"><?php echo __('No chain rules defined yet.') ?></div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light"><tr>
              <th><?php echo __('When THIS completes') ?></th>
              <th><?php echo __('Spawn a task on THIS') ?></th>
              <th><?php echo __('Trigger') ?></th>
              <th><?php echo __('Active') ?></th>
              <th><?php echo __('Notes') ?></th>
              <th><?php echo __('Action') ?></th>
            </tr></thead>
            <tbody>
              <?php foreach ($rules as $rule): ?>
                <tr>
                  <td><strong><?php echo esc_entities($procedures[$rule->from_procedure] ?? $rule->from_procedure) ?></strong></td>
                  <td>→ <strong><?php echo esc_entities($procedures[$rule->to_procedure] ?? $rule->to_procedure) ?></strong></td>
                  <td><span class="badge bg-secondary"><?php echo esc_entities(str_replace('_', ' ', $rule->trigger_event)) ?></span></td>
                  <td>
                    <?php if ($rule->is_active): ?><span class="badge bg-success"><?php echo __('Active') ?></span>
                    <?php else: ?><span class="badge bg-secondary"><?php echo __('Inactive') ?></span><?php endif ?>
                  </td>
                  <td><small class="text-muted"><?php echo esc_entities($rule->notes) ?></small></td>
                  <td>
                    <form method="post" action="<?php echo url_for(['module' => 'workflow', 'action' => 'spectrumChainDelete']) ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this chain rule?') ?>');">
                      <input type="hidden" name="id" value="<?php echo (int) $rule->id ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endif ?>
    </div>
  </div>
</div>
