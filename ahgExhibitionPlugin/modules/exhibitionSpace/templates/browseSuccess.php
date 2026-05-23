<?php /* heratio#146 PSIS port — Exhibition spaces browse */ ?>
<div class="container-fluid px-4 py-3 browse exhibition-space">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="mb-0 flex-grow-1"><i class="fas fa-palette me-2"></i><?php echo __('Exhibition spaces') ?></h1>
    <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'create']) ?>" class="btn btn-primary">
      <i class="fas fa-plus me-1"></i><?php echo __('Add exhibition space') ?>
    </a>
  </div>

  <?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('notice') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif ?>
  <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif ?>

  <form method="get" action="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'browse']) ?>" class="mb-3">
    <div class="input-group input-group-sm" style="max-width: 32rem;">
      <input type="search" name="subquery" class="form-control" placeholder="<?php echo __('Search by name or building...') ?>" value="<?php echo esc_entities($search) ?>">
      <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i></button>
      <?php if ($search !== ''): ?>
        <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'browse']) ?>" class="btn btn-outline-secondary"><?php echo __('Clear') ?></a>
      <?php endif ?>
    </div>
  </form>

  <?php if ($rows->isEmpty()): ?>
    <div class="alert alert-info"><?php echo __('No exhibition spaces yet.') ?></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Name') ?></th>
            <th><?php echo __('Type') ?></th>
            <th><?php echo __('Building / floor') ?></th>
            <th><?php echo __('Capacity') ?></th>
            <th><?php echo __('Current utilisation') ?></th>
            <th><?php echo __('Current placements') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'show', 'slug' => $row->slug]) ?>"><?php echo esc_entities($row->name) ?></a></td>
              <td><span class="badge bg-secondary"><?php echo esc_entities(ucwords(str_replace('_', ' ', $row->space_type))) ?></span></td>
              <td><?php echo esc_entities(trim($row->building.($row->floor ? ' · '.$row->floor : ''))) ?: '—' ?></td>
              <td>
                <?php if ($row->capacity_value !== null): ?>
                  <?php echo (float) $row->capacity_value ?> <?php echo esc_entities(__($capacityUnits[$row->capacity_unit] ?? $row->capacity_unit)) ?>
                <?php else: ?><span class="text-muted small">—</span><?php endif ?>
              </td>
              <td>
                <?php if ($row->capacity_value !== null && (float) $row->capacity_value > 0): ?>
                  <?php $pct = min(100, ((float) $row->used_units_today / (float) $row->capacity_value) * 100); ?>
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height: 8px; min-width: 6rem;">
                      <div class="progress-bar <?php echo $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success') ?>" style="width: <?php echo $pct ?>%"></div>
                    </div>
                    <small class="text-muted"><?php echo (float) $row->used_units_today ?> / <?php echo (float) $row->capacity_value ?></small>
                  </div>
                <?php else: ?>
                  <span class="text-muted small"><?php echo (float) $row->used_units_today ?></span>
                <?php endif ?>
              </td>
              <td><?php echo (int) $row->current_placements ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
</div>
