<?php
/**
 * Authority Resolution - pending queue list (Task 5).
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */
?>
<?php decorate_with('layout_1col'); ?>

<?php
  $rows         = $sf_data->getRaw('rows');
  $total        = (int) $sf_data->getRaw('total');
  $filters      = $sf_data->getRaw('filters');
  $lastPage     = (int) $sf_data->getRaw('lastPage');
  $stateCounts  = $sf_data->getRaw('stateCounts');
  $typeCounts   = $sf_data->getRaw('typeCounts');

  $stateBadges = [
    'pending'             => 'warning',
    'linked'              => 'success',
    'parked'              => 'info',
    'rejected'            => 'secondary',
    'new_record_created'  => 'primary',
  ];

  $typeBadges = [
    'PERSON'      => 'primary',
    'ORG'         => 'info',
    'GPE'         => 'success',
    'LOC'         => 'success',
    'PLACE'       => 'success',
    'ISAD_PLACE'  => 'success',
  ];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-balance-scale me-2"></i><?php echo __('Authority Resolution Queue'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item active"><?php echo __('Authority Resolution'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <!-- State KPIs -->
  <div class="row g-2 mb-3">
    <?php foreach ($stateCounts as $sc): ?>
      <div class="col-md-2 col-sm-4">
        <div class="card text-center border-<?php echo $stateBadges[$sc->state] ?? 'secondary'; ?>">
          <div class="card-body py-2">
            <h4 class="mb-0"><?php echo number_format($sc->c); ?></h4>
            <small class="text-muted"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $sc->state))); ?></small>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <form method="get" action="<?php echo url_for('@ar_auth_res_index'); ?>" class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label small mb-1"><?php echo __('Entity type'); ?></label>
          <select name="entity_type" class="form-select form-select-sm">
            <option value=""><?php echo __('All types'); ?></option>
            <?php foreach (['PERSON', 'ORG', 'GPE', 'LOC', 'PLACE'] as $t): ?>
              <option value="<?php echo $t; ?>"<?php echo ($filters['entity_type'] ?? '') === $t ? ' selected' : ''; ?>>
                <?php echo $t; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1"><?php echo __('State'); ?></label>
          <select name="state" class="form-select form-select-sm">
            <option value="pending"<?php echo ($filters['state'] ?? '') === 'pending' ? ' selected' : ''; ?>>pending</option>
            <option value="linked"<?php echo ($filters['state'] ?? '') === 'linked' ? ' selected' : ''; ?>>linked</option>
            <option value="parked"<?php echo ($filters['state'] ?? '') === 'parked' ? ' selected' : ''; ?>>parked</option>
            <option value="rejected"<?php echo ($filters['state'] ?? '') === 'rejected' ? ' selected' : ''; ?>>rejected</option>
            <option value="new_record_created"<?php echo ($filters['state'] ?? '') === 'new_record_created' ? ' selected' : ''; ?>>new_record_created</option>
            <option value="any"<?php echo ($filters['state'] ?? '') === 'any' ? ' selected' : ''; ?>><?php echo __('Any'); ?></option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1"><?php echo __('Object ID'); ?></label>
          <input type="number" name="object_id" class="form-control form-control-sm"
                 value="<?php echo $filters['object_id'] > 0 ? (int) $filters['object_id'] : ''; ?>" min="0">
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1"><?php echo __('Per page'); ?></label>
          <input type="number" name="limit" class="form-control form-control-sm"
                 value="<?php echo (int) $filters['limit']; ?>" min="10" max="200">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="fas fa-filter me-1"></i><?php echo __('Filter'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Queue -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><?php echo __('%1% mention(s)', ['%1%' => number_format($total)]); ?></span>
      <small class="text-muted"><?php echo __('Sorted by candidate count then id'); ?></small>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover table-striped mb-0 align-middle">
        <thead>
          <tr>
            <th><?php echo __('ID'); ?></th>
            <th><?php echo __('Mention'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Source IO'); ?></th>
            <th class="text-center"><?php echo __('Candidates'); ?></th>
            <th><?php echo __('State'); ?></th>
            <th><?php echo __('Promoted'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows) || count($rows) === 0): ?>
            <tr><td colspan="8" class="text-center text-muted py-4"><?php echo __('No mentions match.'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td class="text-muted small">#<?php echo (int) $row->id; ?></td>
                <td><strong><?php echo htmlspecialchars((string) $row->entity_value); ?></strong></td>
                <td>
                  <span class="badge bg-<?php echo $typeBadges[$row->entity_type] ?? 'secondary'; ?>">
                    <?php echo htmlspecialchars($row->entity_type); ?>
                  </span>
                </td>
                <td>
                  <?php if (!empty($row->io_slug)): ?>
                    <a href="/<?php echo htmlspecialchars((string) $row->io_slug); ?>" target="_blank" rel="noopener">
                      <?php echo htmlspecialchars($row->io_title ?: ('Object #' . (int) $row->object_id)); ?>
                      <i class="fas fa-external-link-alt fa-xs ms-1 text-muted"></i>
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Object #<?php echo (int) $row->object_id; ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <span class="badge bg-<?php echo ((int) $row->candidate_count) > 0 ? 'dark' : 'light text-dark border'; ?>">
                    <?php echo (int) $row->candidate_count; ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-<?php echo $stateBadges[$row->state] ?? 'secondary'; ?>">
                    <?php echo htmlspecialchars($row->state); ?>
                  </span>
                </td>
                <td class="text-muted small"><?php echo htmlspecialchars((string) $row->promoted_at); ?></td>
                <td>
                  <a href="<?php echo url_for('@ar_auth_res_review?id=' . (int) $row->id); ?>"
                     class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-search me-1"></i><?php echo __('Review'); ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($lastPage > 1): ?>
      <div class="card-footer">
        <nav>
          <ul class="pagination pagination-sm mb-0 justify-content-center">
            <?php for ($p = 1; $p <= $lastPage; $p++): ?>
              <li class="page-item<?php echo $p === (int) $filters['page'] ? ' active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for('@ar_auth_res_index?page=' . $p
                  . '&state=' . urlencode((string) $filters['state'])
                  . '&entity_type=' . urlencode((string) $filters['entity_type'])
                  . '&object_id=' . (int) $filters['object_id']
                  . '&limit=' . (int) $filters['limit']); ?>"><?php echo $p; ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
  </div>

<?php end_slot(); ?>
