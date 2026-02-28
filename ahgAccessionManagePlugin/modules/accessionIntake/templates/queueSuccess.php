<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Intake queue'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <?php
    $rawStatuses  = $sf_data->getRaw('statuses');
    $rawPriorities = $sf_data->getRaw('priorities');
    $rawUsers     = $sf_data->getRaw('users');
    $rawFilters   = $sf_data->getRaw('filters');
    $rawStats     = $sf_data->getRaw('stats');
    $filtersArr   = is_array($rawFilters) ? $rawFilters : [];
    $statsArr     = is_array($rawStats) ? $rawStats : (array) $rawStats;

    $statusColors = [
        'draft'        => 'secondary',
        'submitted'    => 'primary',
        'under_review' => 'info',
        'accepted'     => 'success',
        'rejected'     => 'danger',
        'returned'     => 'warning',
    ];
  ?>

  <!-- Filter Bar -->
  <form method="get" action="<?php echo url_for('@accession_intake_queue'); ?>" class="mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label form-label-sm"><?php echo __('Status'); ?></label>
        <select name="status" class="form-select form-select-sm">
          <option value=""><?php echo __('All statuses'); ?></option>
          <?php foreach ($rawStatuses as $s): ?>
            <option value="<?php echo htmlspecialchars($s); ?>"<?php echo (($filtersArr['status'] ?? '') === $s) ? ' selected' : ''; ?>>
              <?php echo ucfirst(str_replace('_', ' ', $s)); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label form-label-sm"><?php echo __('Priority'); ?></label>
        <select name="priority" class="form-select form-select-sm">
          <option value=""><?php echo __('All priorities'); ?></option>
          <?php foreach ($rawPriorities as $p): ?>
            <option value="<?php echo htmlspecialchars($p); ?>"<?php echo (($filtersArr['priority'] ?? '') === $p) ? ' selected' : ''; ?>>
              <?php echo ucfirst($p); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label form-label-sm"><?php echo __('Assigned to'); ?></label>
        <select name="assigned_to" class="form-select form-select-sm">
          <option value=""><?php echo __('Anyone'); ?></option>
          <?php foreach ($rawUsers as $u): ?>
            <option value="<?php echo htmlspecialchars($u->id); ?>"<?php echo (($filtersArr['assigned_to'] ?? '') == $u->id) ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars($u->name); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label form-label-sm"><?php echo __('Search'); ?></label>
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="<?php echo __('Identifier, title...'); ?>"
               value="<?php echo htmlspecialchars($filtersArr['search'] ?? ''); ?>">
      </div>

      <div class="col-md-auto">
        <button type="submit" class="btn btn-sm btn-primary">
          <i class="fas fa-filter me-1"></i><?php echo __('Filter'); ?>
        </button>
        <a href="<?php echo url_for('@accession_intake_queue'); ?>" class="btn btn-sm btn-outline-secondary">
          <?php echo __('Reset'); ?>
        </a>
      </div>
    </div>
  </form>

  <!-- Stats Cards -->
  <div class="row g-2 mb-3">
    <div class="col">
      <div class="card bg-dark text-white h-100">
        <div class="card-body py-2 px-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="text-white-50"><?php echo __('Total'); ?></small>
              <h4 class="mb-0"><?php echo number_format($statsArr['total'] ?? 0); ?></h4>
            </div>
            <i class="fas fa-inbox fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>

    <?php foreach ($statusColors as $statusKey => $color): ?>
      <div class="col">
        <div class="card bg-<?php echo $color; ?> text-white h-100">
          <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <small class="text-white-50"><?php echo ucfirst(str_replace('_', ' ', $statusKey)); ?></small>
                <h4 class="mb-0"><?php echo number_format($statsArr[$statusKey] ?? 0); ?></h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="col">
      <div class="card bg-danger text-white h-100 border-danger">
        <div class="card-body py-2 px-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="text-white-50"><?php echo __('Overdue'); ?></small>
              <h4 class="mb-0"><?php echo number_format($statsArr['overdue'] ?? 0); ?></h4>
            </div>
            <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <?php
    $rawQueueData = $sf_data->getRaw('queueData');
    $rows       = $rawQueueData->rows ?? [];
    $total      = $rawQueueData->total ?? 0;
    $page       = (int) ($rawQueueData->page ?? 1);
    $limit      = (int) ($rawQueueData->limit ?? 30);
    $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

    $statusBadges = [
        'draft'        => 'secondary',
        'submitted'    => 'primary',
        'under_review' => 'info',
        'accepted'     => 'success',
        'rejected'     => 'danger',
        'returned'     => 'warning',
    ];
    $priorityBadges = [
        'low'    => 'secondary',
        'normal' => 'info',
        'high'   => 'warning',
        'urgent' => 'danger',
    ];
  ?>

  <?php if (count($rows) > 0): ?>
    <div class="table-responsive mb-3">
      <table class="table table-bordered table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Identifier'); ?></th>
            <th><?php echo __('Title'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Priority'); ?></th>
            <th><?php echo __('Assigned to'); ?></th>
            <th><?php echo __('Submitted'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php
              $rowId = $row->accession_id ?? $row->id ?? '';
              $st    = $row->status ?? 'draft';
              $pr    = $row->priority ?? 'normal';
            ?>
            <tr>
              <td>
                <a href="<?php echo url_for('@accession_intake_detail?id=' . $rowId); ?>">
                  <?php echo htmlspecialchars($row->identifier ?? '--'); ?>
                </a>
              </td>
              <td><?php echo htmlspecialchars($row->title ?? '--'); ?></td>
              <td>
                <span class="badge bg-<?php echo $statusBadges[$st] ?? 'secondary'; ?>">
                  <?php echo ucfirst(str_replace('_', ' ', $st)); ?>
                </span>
              </td>
              <td>
                <span class="badge bg-<?php echo $priorityBadges[$pr] ?? 'info'; ?>">
                  <?php echo ucfirst($pr); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($row->assigned_to_name ?? '--'); ?></td>
              <td>
                <?php if (!empty($row->submitted_at)): ?>
                  <?php echo date('d M Y H:i', strtotime($row->submitted_at)); ?>
                <?php else: ?>
                  <span class="text-muted">--</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="<?php echo url_for('@accession_intake_detail?id=' . $rowId); ?>"
                     class="btn btn-outline-primary" title="<?php echo __('View'); ?>">
                    <i class="fas fa-eye"></i>
                  </a>

                  <!-- Assign dropdown -->
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            title="<?php echo __('Assign'); ?>">
                      <i class="fas fa-user-plus"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <?php foreach ($rawUsers as $u): ?>
                        <li>
                          <form method="post" action="<?php echo url_for('@accession_intake_assign'); ?>" class="d-inline">
                            <input type="hidden" name="accession_id" value="<?php echo htmlspecialchars($rowId); ?>">
                            <input type="hidden" name="assignee_id" value="<?php echo htmlspecialchars($u->id); ?>">
                            <button type="submit" class="dropdown-item">
                              <?php echo htmlspecialchars($u->name); ?>
                            </button>
                          </form>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <?php
        $qs = http_build_query([
            'status'      => $filtersArr['status'] ?? '',
            'priority'    => $filtersArr['priority'] ?? '',
            'assigned_to' => $filtersArr['assigned_to'] ?? '',
            'search'      => $filtersArr['search'] ?? '',
        ]);
      ?>
      <nav aria-label="<?php echo __('Queue pagination'); ?>">
        <ul class="pagination justify-content-center mb-0">
          <li class="page-item<?php echo ($page <= 1) ? ' disabled' : ''; ?>">
            <a class="page-link" href="<?php echo url_for('@accession_intake_queue'); ?>?page=<?php echo $page - 1; ?>&amp;<?php echo $qs; ?>">
              &laquo;
            </a>
          </li>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item<?php echo ($i === $page) ? ' active' : ''; ?>">
              <a class="page-link" href="<?php echo url_for('@accession_intake_queue'); ?>?page=<?php echo $i; ?>&amp;<?php echo $qs; ?>">
                <?php echo $i; ?>
              </a>
            </li>
          <?php endfor; ?>
          <li class="page-item<?php echo ($page >= $totalPages) ? ' disabled' : ''; ?>">
            <a class="page-link" href="<?php echo url_for('@accession_intake_queue'); ?>?page=<?php echo $page + 1; ?>&amp;<?php echo $qs; ?>">
              &raquo;
            </a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>

  <?php else: ?>
    <div class="text-center py-5 text-muted">
      <i class="fas fa-inbox fa-3x mb-3"></i>
      <p class="mb-0"><?php echo __('No accessions found in the intake queue.'); ?></p>
    </div>
  <?php endif; ?>
<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <section class="actions mb-3">
    <a href="<?php echo url_for('@accession_intake_config'); ?>" class="btn atom-btn-outline-light">
      <i class="fas fa-cog me-1"></i><?php echo __('Configuration'); ?>
    </a>
    <a href="<?php echo url_for('@accession_intake_numbering'); ?>" class="btn atom-btn-outline-light">
      <i class="fas fa-hashtag me-1"></i><?php echo __('Numbering'); ?>
    </a>
  </section>
<?php end_slot(); ?>
