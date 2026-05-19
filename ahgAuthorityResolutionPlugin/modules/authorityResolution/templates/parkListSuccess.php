<?php
/**
 * Authority Resolution - parked-mention queue (Task 7).
 *
 * Dedicated screen for archivists to triage parked mentions, see which
 * ones now have new authority candidates available, and un-park to
 * re-review.
 *
 * Locals expected:
 *   $rows                 array of parked-row objects (from ParkQueueService::listFor)
 *   $filters              array of current filter values
 *   $userOptions          collection of {parked_by_user_id, username, c}
 *   $totalParked          int
 *   $newCandidateFlagged  int
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */
?>
<?php decorate_with('layout_1col'); ?>

<?php
  $rows                = $sf_data->getRaw('rows');
  $filters             = $sf_data->getRaw('filters');
  $userOptions         = $sf_data->getRaw('userOptions');
  $totalParked         = (int) $sf_data->getRaw('totalParked');
  $newCandidateFlagged = (int) $sf_data->getRaw('newCandidateFlagged');

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
  <h1><i class="fas fa-pause-circle me-2"></i><?php echo __('Parked Mentions'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for('@ar_auth_res_index'); ?>"><?php echo __('Authority Resolution'); ?></a></li>
      <li class="breadcrumb-item active"><?php echo __('Park queue'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <!-- KPIs -->
  <div class="row g-2 mb-3">
    <div class="col-md-3 col-sm-6">
      <div class="card text-center border-info">
        <div class="card-body py-2">
          <h4 class="mb-0"><?php echo number_format($totalParked); ?></h4>
          <small class="text-muted"><?php echo __('Total parked'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card text-center border-warning">
        <div class="card-body py-2">
          <h4 class="mb-0"><?php echo number_format($newCandidateFlagged); ?></h4>
          <small class="text-muted"><?php echo __('New candidate(s) available'); ?></small>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <form method="get" action="<?php echo url_for('@ar_auth_res_park_list'); ?>" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label small mb-1"><?php echo __('Parked by'); ?></label>
          <select name="parked_by" class="form-select form-select-sm">
            <option value=""><?php echo __('Any archivist'); ?></option>
            <?php foreach ($userOptions as $u): ?>
              <option value="<?php echo (int) $u->parked_by_user_id; ?>"
                <?php echo ((int) $filters['parked_by'] === (int) $u->parked_by_user_id) ? ' selected' : ''; ?>>
                <?php echo htmlspecialchars($u->username ?: ('User #' . (int) $u->parked_by_user_id)); ?>
                (<?php echo (int) $u->c; ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1"><?php echo __('Entity type'); ?></label>
          <select name="entity_type" class="form-select form-select-sm">
            <option value=""><?php echo __('All types'); ?></option>
            <?php foreach (['PERSON', 'ORG', 'GPE', 'LOC', 'PLACE'] as $t): ?>
              <option value="<?php echo $t; ?>"
                <?php echo ($filters['entity_type'] ?? '') === $t ? ' selected' : ''; ?>>
                <?php echo $t; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1"><?php echo __('Parked since'); ?></label>
          <input type="date" name="since_parked" class="form-control form-control-sm"
                 value="<?php echo htmlspecialchars((string) ($filters['since_parked'] ?? '')); ?>">
        </div>
        <div class="col-md-2">
          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" id="ar-park-newonly"
                   name="new_candidate_only" value="1"
                   <?php echo !empty($filters['new_candidate_only']) ? ' checked' : ''; ?>>
            <label class="form-check-label small" for="ar-park-newonly">
              <?php echo __('New candidate(s) only'); ?>
            </label>
          </div>
        </div>
        <div class="col-md-1">
          <label class="form-label small mb-1"><?php echo __('Limit'); ?></label>
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

  <!-- Parked queue list -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><?php echo __('%1% row(s) shown', ['%1%' => number_format(count($rows))]); ?></span>
      <small class="text-muted">
        <?php echo __('Sorted: new-candidate flagged first, then most-recently parked'); ?>
      </small>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover table-striped mb-0 align-middle">
        <thead>
          <tr>
            <th><?php echo __('Mention'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Source IO'); ?></th>
            <th><?php echo __('Parked by'); ?></th>
            <th><?php echo __('Parked at'); ?></th>
            <th><?php echo __('Reason'); ?></th>
            <th class="text-center"><?php echo __('Flag'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">
              <?php echo __('No parked mentions match the current filters.'); ?>
            </td></tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <?php include_partial('authorityResolution/parkRow', ['row' => $row, 'typeBadges' => $typeBadges]); ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php end_slot(); ?>
