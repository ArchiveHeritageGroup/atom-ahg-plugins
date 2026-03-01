<?php decorate_with('layout_1col'); ?>

<?php
  $rawWorkqueue = $sf_data->getRaw('workqueue');
  $rawFilters   = $sf_data->getRaw('filters');
  $rawLevels    = $sf_data->getRaw('levels');
  $rawUsers     = $sf_data->getRaw('users');

  $workqueue = is_array($rawWorkqueue) ? $rawWorkqueue : (array) $rawWorkqueue;
  $filters   = is_array($rawFilters) ? $rawFilters : [];
  $levels    = is_array($rawLevels) ? $rawLevels : [];
  $users     = is_array($rawUsers) ? $rawUsers : [];

  $items     = $workqueue['data'] ?? [];
  $total     = $workqueue['total'] ?? 0;
  $lastPage  = $workqueue['last_page'] ?? 1;
  $curPage   = $workqueue['current_page'] ?? 1;

  $levelColors = ['stub' => 'danger', 'minimal' => 'warning', 'partial' => 'info', 'full' => 'success'];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-tasks me-2"></i><?php echo __('Authority Workqueue'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ahg_authority_dashboard'); ?>"><?php echo __('Authority Dashboard'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Workqueue'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <form method="get" action="<?php echo url_for('@ahg_authority_workqueue'); ?>" class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label"><?php echo __('Level'); ?></label>
          <select name="level" class="form-select form-select-sm">
            <option value=""><?php echo __('All'); ?></option>
            <?php foreach ($levels as $lvl): ?>
              <option value="<?php echo $lvl; ?>"<?php echo ($filters['level'] ?? '') === $lvl ? ' selected' : ''; ?>>
                <?php echo ucfirst($lvl); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label"><?php echo __('Assigned to'); ?></label>
          <select name="assigned_to" class="form-select form-select-sm">
            <option value=""><?php echo __('All'); ?></option>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo $u->id; ?>"<?php echo ($filters['assigned_to'] ?? '') == $u->id ? ' selected' : ''; ?>>
                <?php echo htmlspecialchars($u->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label"><?php echo __('Max score'); ?></label>
          <input type="number" name="max_score" class="form-control form-control-sm"
                 value="<?php echo htmlspecialchars($filters['max_score'] ?? ''); ?>" min="0" max="100">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="fas fa-filter me-1"></i><?php echo __('Filter'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Results -->
  <div class="card">
    <div class="card-header d-flex justify-content-between">
      <span><?php echo __('%1% record(s)', ['%1%' => number_format($total)]); ?></span>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover table-striped mb-0">
        <thead>
          <tr>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Level'); ?></th>
            <th class="text-center"><?php echo __('Score'); ?></th>
            <th class="text-center"><?php echo __('IDs'); ?></th>
            <th class="text-center"><?php echo __('Rels'); ?></th>
            <th><?php echo __('Assigned to'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4"><?php echo __('No records found.'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($items as $item): ?>
              <?php $item = (object) $item; ?>
              <tr>
                <td>
                  <a href="<?php echo url_for('@ahg_authority_identifiers?actorId=' . $item->actor_id); ?>">
                    <?php echo htmlspecialchars($item->name ?? 'Actor #' . $item->actor_id); ?>
                  </a>
                </td>
                <td>
                  <span class="badge bg-<?php echo $levelColors[$item->completeness_level] ?? 'secondary'; ?>">
                    <?php echo ucfirst($item->completeness_level); ?>
                  </span>
                </td>
                <td class="text-center"><?php echo $item->completeness_score; ?>%</td>
                <td class="text-center">
                  <?php echo $item->has_external_ids ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?>
                </td>
                <td class="text-center">
                  <?php echo $item->has_relations ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?>
                </td>
                <td>
                  <?php if ($item->assigned_to): ?>
                    <small class="text-muted">#<?php echo $item->assigned_to; ?></small>
                  <?php else: ?>
                    <small class="text-muted"><?php echo __('Unassigned'); ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($item->slug): ?>
                    <a href="/<?php echo $item->slug; ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('View'); ?>">
                      <i class="fas fa-eye"></i>
                    </a>
                  <?php endif; ?>
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
              <li class="page-item<?php echo $p == $curPage ? ' active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for('@ahg_authority_workqueue?page=' . $p); ?>"><?php echo $p; ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
  </div>

<?php end_slot(); ?>
