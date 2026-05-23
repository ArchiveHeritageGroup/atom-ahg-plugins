<?php /* heratio#146 PSIS port — Exhibition space show */ ?>
<div class="container-fluid px-4 py-3 show exhibition-space">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="mb-0 flex-grow-1">
      <i class="fas fa-palette me-2"></i><?php echo esc_entities($space->name) ?>
      <span class="badge bg-secondary ms-2"><?php echo esc_entities(ucwords(str_replace('_', ' ', $space->space_type))) ?></span>
    </h1>
    <?php if ($sf_user->isAuthenticated()): ?>
      <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'edit', 'slug' => $space->slug]) ?>" class="btn btn-outline-primary"><i class="fas fa-edit me-1"></i><?php echo __('Edit') ?></a>
      <?php if ($sf_user->hasCredential('administrator')): ?>
      <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'confirmDelete', 'slug' => $space->slug]) ?>" class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i><?php echo __('Delete') ?></a>
      <?php endif ?>
    <?php endif ?>
    <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'browse']) ?>" class="btn btn-outline-secondary"><i class="fas fa-list me-1"></i><?php echo __('All spaces') ?></a>
  </div>

  <?php if ($sf_user->hasFlash('notice')): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('notice') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif ?>
  <?php if ($sf_user->hasFlash('error')): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif ?>

  <div class="row">
    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header"><strong><?php echo __('Location') ?></strong></div>
        <div class="card-body">
          <p class="mb-1"><small class="text-muted"><?php echo __('Building') ?></small><br><?php echo esc_entities($space->building) ?: '—' ?></p>
          <p class="mb-1"><small class="text-muted"><?php echo __('Floor') ?></small><br><?php echo esc_entities($space->floor) ?: '—' ?></p>
        </div>
      </div>
      <div class="card mb-3">
        <div class="card-header"><strong><?php echo __('Capacity') ?></strong></div>
        <div class="card-body">
          <?php if ($space->capacity_value !== null): ?>
            <p class="mb-1"><?php echo (float) $space->capacity_value ?> <?php echo esc_entities(__($capacityUnits[$space->capacity_unit] ?? $space->capacity_unit)) ?></p>
          <?php else: ?>
            <p class="text-muted"><?php echo __('No capacity set') ?></p>
          <?php endif ?>
          <?php if ($space->lighting_lux_target !== null): ?>
            <p class="mb-0 small text-muted"><?php echo __('Lighting target: %1% lux', ['%1%' => (float) $space->lighting_lux_target]) ?></p>
          <?php endif ?>
        </div>
      </div>
      <?php if (!empty($space->notes)): ?>
        <div class="card mb-3"><div class="card-header"><strong><?php echo __('Notes') ?></strong></div><div class="card-body"><p class="mb-0"><?php echo esc_entities($space->notes) ?></p></div></div>
      <?php endif ?>
    </div>

    <div class="col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong><?php echo __('Placements') ?></strong>
          <small class="text-muted"><?php echo count($placements) ?> <?php echo __('total') ?></small>
        </div>
        <div class="card-body p-0">
          <?php if (count($placements) === 0): ?>
            <div class="p-3 text-muted small"><?php echo __('No placements yet.') ?></div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0 small">
                <thead class="table-light"><tr>
                  <th><?php echo __('Information object') ?></th><th><?php echo __('Units') ?></th>
                  <th><?php echo __('Starts') ?></th><th><?php echo __('Ends') ?></th>
                  <th><?php echo __('Notes') ?></th>
                  <?php if ($sf_user->isAuthenticated()): ?><th></th><?php endif ?>
                </tr></thead>
                <tbody>
                <?php foreach ($placements as $p): ?>
                  <tr>
                    <td>
                      <?php if (!empty($p->information_object_title)): ?>
                        <?php echo esc_entities($p->information_object_title) ?> <small class="text-muted">#<?php echo (int) $p->information_object_id ?></small>
                      <?php else: ?>
                        <span class="text-muted"><?php echo __('Object') ?> #<?php echo (int) $p->information_object_id ?></span>
                      <?php endif ?>
                    </td>
                    <td><?php echo (float) $p->size_units_used ?></td>
                    <td><?php echo esc_entities($p->starts_at) ?: '—' ?></td>
                    <td><?php echo esc_entities($p->ends_at) ?: '—' ?></td>
                    <td><small class="text-muted"><?php echo esc_entities($p->notes) ?></small></td>
                    <?php if ($sf_user->isAuthenticated()): ?>
                    <td>
                      <form method="post" action="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'removePlacement']) ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Remove this placement?') ?>');">
                        <input type="hidden" name="id" value="<?php echo (int) $p->id ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                      </form>
                    </td>
                    <?php endif ?>
                  </tr>
                <?php endforeach ?>
                </tbody>
              </table>
            </div>
          <?php endif ?>
        </div>
      </div>

      <?php if ($sf_user->isAuthenticated()): ?>
      <div class="card mt-3">
        <div class="card-header"><strong><?php echo __('Add a placement') ?></strong></div>
        <div class="card-body">
          <form method="post" action="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'place', 'slug' => $space->slug]) ?>">
            <div class="row g-2 align-items-end">
              <div class="col-md-4">
                <label for="information_object_id" class="form-label small"><?php echo __('Information object ID') ?> <span class="text-danger">*</span></label>
                <input type="number" name="information_object_id" id="information_object_id" class="form-control form-control-sm" required min="1">
              </div>
              <div class="col-md-2">
                <label for="size_units_used" class="form-label small"><?php echo __('Units used') ?></label>
                <input type="number" name="size_units_used" id="size_units_used" class="form-control form-control-sm" min="0" step="0.01" value="0">
              </div>
              <div class="col-md-2">
                <label for="starts_at" class="form-label small"><?php echo __('Starts') ?></label>
                <input type="date" name="starts_at" id="starts_at" class="form-control form-control-sm">
              </div>
              <div class="col-md-2">
                <label for="ends_at" class="form-label small"><?php echo __('Ends') ?></label>
                <input type="date" name="ends_at" id="ends_at" class="form-control form-control-sm">
              </div>
              <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-success w-100"><i class="fas fa-plus me-1"></i><?php echo __('Place') ?></button>
              </div>
            </div>
            <div class="mt-2"><input type="text" name="notes" class="form-control form-control-sm" placeholder="<?php echo __('Optional notes') ?>"></div>
          </form>
        </div>
      </div>
      <?php endif ?>
    </div>
  </div>
</div>
