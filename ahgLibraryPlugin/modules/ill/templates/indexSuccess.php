<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Interlibrary Loan Requests'); ?></h1>
<?php end_slot(); ?>

<?php if (!empty($notice)): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $notice; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php
  $rawDirection = $sf_data->getRaw('direction') ?? '';
  $rawQ = $sf_data->getRaw('q') ?? '';
  $rawIllStatus = $sf_data->getRaw('illStatus') ?? '';
?>

<!-- Direction tabs -->
<ul class="nav nav-tabs mb-4">
  <li class="nav-item">
    <a class="nav-link <?php echo empty($rawDirection) ? 'active' : ''; ?>"
       href="<?php echo url_for(['module' => 'ill', 'action' => 'index', 'q' => $rawQ, 'ill_status' => $rawIllStatus]); ?>">
      <?php echo __('All'); ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $rawDirection === 'borrow' ? 'active' : ''; ?>"
       href="<?php echo url_for(['module' => 'ill', 'action' => 'index', 'direction' => 'borrow', 'q' => $rawQ, 'ill_status' => $rawIllStatus]); ?>">
      <i class="fas fa-arrow-down me-1"></i><?php echo __('Borrowing'); ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $rawDirection === 'lend' ? 'active' : ''; ?>"
       href="<?php echo url_for(['module' => 'ill', 'action' => 'index', 'direction' => 'lend', 'q' => $rawQ, 'ill_status' => $rawIllStatus]); ?>">
      <i class="fas fa-arrow-up me-1"></i><?php echo __('Lending'); ?>
    </a>
  </li>
</ul>

<!-- Search / Filter bar -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form method="get" action="<?php echo url_for(['module' => 'ill', 'action' => 'index']); ?>" class="row g-2 align-items-end">
      <?php if (!empty($rawDirection)): ?>
        <input type="hidden" name="direction" value="<?php echo esc_entities($rawDirection); ?>">
      <?php endif; ?>
      <div class="col-md-5">
        <label for="ill_q" class="form-label"><?php echo __('Search'); ?></label>
        <input type="text" class="form-control" id="ill_q" name="q"
               placeholder="<?php echo __('Title, author, ISBN, or library...'); ?>"
               value="<?php echo esc_entities($rawQ); ?>">
      </div>
      <div class="col-md-3">
        <label for="ill_status_filter" class="form-label"><?php echo __('Status'); ?></label>
        <select class="form-select" id="ill_status_filter" name="ill_status">
          <option value=""><?php echo __('All statuses'); ?></option>
          <?php
            $statuses = ['submitted', 'approved', 'sent', 'received', 'returned', 'cancelled'];
            foreach ($statuses as $s):
          ?>
            <option value="<?php echo $s; ?>" <?php echo $rawIllStatus === $s ? 'selected' : ''; ?>>
              <?php echo esc_entities(ucfirst($s)); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">
          <i class="fas fa-search me-1"></i><?php echo __('Search'); ?>
        </button>
      </div>
      <div class="col-md-2">
        <a href="<?php echo url_for(['module' => 'ill', 'action' => 'edit']); ?>" class="btn btn-success w-100">
          <i class="fas fa-plus me-1"></i><?php echo __('New Request'); ?>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Results table -->
<div class="card shadow-sm">
  <div class="card-header">
    <i class="fas fa-exchange-alt me-2"></i><?php echo __('ILL Requests'); ?>
    <span class="badge bg-secondary ms-2"><?php echo (int) $sf_data->getRaw('total'); ?></span>
  </div>
  <div class="card-body p-0">
    <?php $rawResults = $sf_data->getRaw('results'); ?>
    <?php if (empty($rawResults)): ?>
      <div class="p-3 text-muted">
        <i class="fas fa-info-circle me-2"></i><?php echo __('No ILL requests found.'); ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Title'); ?></th>
              <th><?php echo __('Author'); ?></th>
              <th><?php echo __('Direction'); ?></th>
              <th><?php echo __('Status'); ?></th>
              <th><?php echo __('Requesting library'); ?></th>
              <th><?php echo __('Lending library'); ?></th>
              <th><?php echo __('Request date'); ?></th>
              <th><?php echo __('Needed by'); ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rawResults as $row): ?>
              <?php
                // Direction badge
                $dirBadge = 'bg-secondary';
                $dir = $row->direction ?? '';
                if ($dir === 'borrow') { $dirBadge = 'bg-info'; }
                elseif ($dir === 'lend') { $dirBadge = 'bg-primary'; }

                // Status badge
                $stBadge = 'bg-secondary';
                $st = $row->ill_status ?? '';
                if ($st === 'submitted') { $stBadge = 'bg-warning text-dark'; }
                elseif ($st === 'approved') { $stBadge = 'bg-info'; }
                elseif ($st === 'sent') { $stBadge = 'bg-primary'; }
                elseif ($st === 'received') { $stBadge = 'bg-success'; }
                elseif ($st === 'returned') { $stBadge = 'bg-dark'; }
                elseif ($st === 'cancelled') { $stBadge = 'bg-danger'; }
              ?>
              <tr>
                <td>
                  <a href="<?php echo url_for(['module' => 'ill', 'action' => 'view', 'id' => $row->id]); ?>">
                    <?php echo esc_entities($row->title ?? __('Untitled')); ?>
                  </a>
                </td>
                <td><?php echo esc_entities($row->author ?? '-'); ?></td>
                <td>
                  <span class="badge <?php echo $dirBadge; ?>">
                    <?php echo esc_entities(ucfirst($dir ?: '-')); ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?php echo $stBadge; ?>">
                    <?php echo esc_entities(ucfirst($st ?: 'unknown')); ?>
                  </span>
                </td>
                <td><?php echo esc_entities($row->requesting_library ?? '-'); ?></td>
                <td><?php echo esc_entities($row->lending_library ?? '-'); ?></td>
                <td><?php echo esc_entities($row->request_date ?? '-'); ?></td>
                <td><?php echo esc_entities($row->needed_by_date ?? '-'); ?></td>
                <td>
                  <a href="<?php echo url_for(['module' => 'ill', 'action' => 'view', 'id' => $row->id]); ?>"
                     class="btn btn-sm btn-outline-primary" title="<?php echo __('View'); ?>">
                    <i class="fas fa-eye"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Pagination -->
<?php
  $rawPage = (int) $sf_data->getRaw('page');
  $rawTotalPages = (int) $sf_data->getRaw('totalPages');
?>
<?php if ($rawTotalPages > 1): ?>
  <nav class="mt-3" aria-label="<?php echo __('Pagination'); ?>">
    <ul class="pagination justify-content-center">
      <li class="page-item <?php echo $rawPage <= 1 ? 'disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'ill', 'action' => 'index',
          'q' => $rawQ, 'direction' => $rawDirection, 'ill_status' => $rawIllStatus,
          'page' => $rawPage - 1]); ?>">
          &laquo; <?php echo __('Previous'); ?>
        </a>
      </li>
      <?php for ($p = 1; $p <= $rawTotalPages; $p++): ?>
        <li class="page-item <?php echo $p === $rawPage ? 'active' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'ill', 'action' => 'index',
            'q' => $rawQ, 'direction' => $rawDirection, 'ill_status' => $rawIllStatus,
            'page' => $p]); ?>">
            <?php echo $p; ?>
          </a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?php echo $rawPage >= $rawTotalPages ? 'disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'ill', 'action' => 'index',
          'q' => $rawQ, 'direction' => $rawDirection, 'ill_status' => $rawIllStatus,
          'page' => $rawPage + 1]); ?>">
          <?php echo __('Next'); ?> &raquo;
        </a>
      </li>
    </ul>
  </nav>
<?php endif; ?>
