<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Patron Management'); ?></h1>
<?php end_slot(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <span class="text-muted"><?php echo __('%1% patrons', ['%1%' => $total]); ?></span>
  </div>
  <div>
    <a href="<?php echo url_for(['module' => 'patron', 'action' => 'edit']); ?>" class="btn btn-success">
      <i class="fas fa-plus me-2"></i><?php echo __('Add Patron'); ?>
    </a>
  </div>
</div>

<!-- Search & Filter -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="<?php echo url_for(['module' => 'patron', 'action' => 'index']); ?>">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Search'); ?></label>
          <input type="text" name="q" class="form-control" placeholder="<?php echo __('Name, email, or barcode...'); ?>"
                 value="<?php echo esc_entities($q); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label"><?php echo __('Patron Type'); ?></label>
          <select name="patron_type" class="form-select">
            <option value=""><?php echo __('All types'); ?></option>
            <?php foreach ($sf_data->getRaw('patronTypes') as $key => $label): ?>
              <option value="<?php echo esc_entities($key); ?>" <?php echo $patronType === $key ? 'selected' : ''; ?>>
                <?php echo esc_entities($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label"><?php echo __('Status'); ?></label>
          <select name="borrowing_status" class="form-select">
            <option value=""><?php echo __('All statuses'); ?></option>
            <option value="active" <?php echo $borrowingStatus === 'active' ? 'selected' : ''; ?>><?php echo __('Active'); ?></option>
            <option value="suspended" <?php echo $borrowingStatus === 'suspended' ? 'selected' : ''; ?>><?php echo __('Suspended'); ?></option>
            <option value="expired" <?php echo $borrowingStatus === 'expired' ? 'selected' : ''; ?>><?php echo __('Expired'); ?></option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-search me-1"></i><?php echo __('Search'); ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (empty($sf_data->getRaw('results'))): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('No patrons found. Click "Add Patron" to register a new patron.'); ?>
  </div>
<?php else: ?>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Barcode'); ?></th>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Email'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th class="text-center"><?php echo __('Checkouts'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php
            $rawResults = $sf_data->getRaw('results');
            $rawCounts = $sf_data->getRaw('checkoutCounts');
          ?>
          <?php foreach ($rawResults as $patron): ?>
            <tr>
              <td><code><?php echo esc_entities($patron->patron_barcode); ?></code></td>
              <td>
                <a href="<?php echo url_for(['module' => 'patron', 'action' => 'view', 'id' => $patron->id]); ?>">
                  <?php echo esc_entities($patron->last_name . ', ' . $patron->first_name); ?>
                </a>
              </td>
              <td><?php echo esc_entities($patron->email ?? ''); ?></td>
              <td><span class="badge bg-secondary"><?php echo esc_entities(ucfirst($patron->patron_type)); ?></span></td>
              <td>
                <?php if ($patron->borrowing_status === 'active'): ?>
                  <span class="badge bg-success"><?php echo __('Active'); ?></span>
                <?php elseif ($patron->borrowing_status === 'suspended'): ?>
                  <span class="badge bg-danger"><?php echo __('Suspended'); ?></span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark"><?php echo esc_entities(ucfirst($patron->borrowing_status)); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center"><?php echo (int) ($rawCounts[$patron->id] ?? 0); ?></td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="<?php echo url_for(['module' => 'patron', 'action' => 'view', 'id' => $patron->id]); ?>" class="btn btn-outline-primary" title="<?php echo __('View'); ?>">
                    <i class="fas fa-eye"></i>
                  </a>
                  <a href="<?php echo url_for(['module' => 'patron', 'action' => 'edit', 'id' => $patron->id]); ?>" class="btn btn-outline-secondary" title="<?php echo __('Edit'); ?>">
                    <i class="fas fa-edit"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
      <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'patron', 'action' => 'index', 'page' => $page - 1, 'q' => $q, 'patron_type' => $patronType, 'borrowing_status' => $borrowingStatus]); ?>">
              <i class="fas fa-chevron-left"></i>
            </a>
          </li>
        <?php endif; ?>

        <?php
          $startPage = max(1, $page - 2);
          $endPage = min($totalPages, $page + 2);
        ?>

        <?php if ($startPage > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'patron', 'action' => 'index', 'page' => 1, 'q' => $q, 'patron_type' => $patronType, 'borrowing_status' => $borrowingStatus]); ?>">1</a>
          </li>
          <?php if ($startPage > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
          <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'patron', 'action' => 'index', 'page' => $i, 'q' => $q, 'patron_type' => $patronType, 'borrowing_status' => $borrowingStatus]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($endPage < $totalPages): ?>
          <?php if ($endPage < $totalPages - 1): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'patron', 'action' => 'index', 'page' => $totalPages, 'q' => $q, 'patron_type' => $patronType, 'borrowing_status' => $borrowingStatus]); ?>"><?php echo $totalPages; ?></a>
          </li>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'patron', 'action' => 'index', 'page' => $page + 1, 'q' => $q, 'patron_type' => $patronType, 'borrowing_status' => $borrowingStatus]); ?>">
              <i class="fas fa-chevron-right"></i>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  <?php endif; ?>

<?php endif; ?>
