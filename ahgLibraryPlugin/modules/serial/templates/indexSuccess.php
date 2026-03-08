<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Serial Subscriptions'); ?></h1>
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

<?php $rawRenewals = $sf_data->getRaw('renewalsDue'); ?>
<?php if (!empty($rawRenewals)): ?>
  <div class="alert alert-warning" role="alert">
    <i class="fas fa-bell me-2"></i>
    <strong><?php echo __('%1% subscription(s) due for renewal within 30 days', ['%1%' => count($rawRenewals)]); ?></strong>
    <ul class="mb-0 mt-2">
      <?php foreach ($rawRenewals as $renewal): ?>
        <li>
          <a href="<?php echo url_for(['module' => 'serial', 'action' => 'view', 'id' => $renewal->id]); ?>">
            <?php echo esc_entities($renewal->title ?? __('Untitled')); ?>
          </a>
          <?php if (!empty($renewal->issn)): ?>
            <small class="text-muted">(ISSN: <?php echo esc_entities($renewal->issn); ?>)</small>
          <?php endif; ?>
          &mdash; <?php echo __('Renewal: %1%', ['%1%' => esc_entities($renewal->renewal_date)]); ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- Search / Filter bar -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form method="get" action="<?php echo url_for(['module' => 'serial', 'action' => 'index']); ?>" class="row g-2 align-items-end">
      <div class="col-md-5">
        <label for="serial_q" class="form-label"><?php echo __('Search'); ?></label>
        <input type="text" class="form-control" id="serial_q" name="q"
               placeholder="<?php echo __('Title, ISSN, or vendor...'); ?>"
               value="<?php echo esc_entities($sf_data->getRaw('q') ?? ''); ?>">
      </div>
      <div class="col-md-3">
        <label for="serial_status" class="form-label"><?php echo __('Status'); ?></label>
        <select class="form-select" id="serial_status" name="subscription_status">
          <option value=""><?php echo __('All statuses'); ?></option>
          <?php
            $statuses = ['active', 'suspended', 'cancelled', 'expired'];
            $currentStatus = $sf_data->getRaw('subscriptionStatus') ?? '';
            foreach ($statuses as $s):
          ?>
            <option value="<?php echo $s; ?>" <?php echo $currentStatus === $s ? 'selected' : ''; ?>>
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
        <a href="<?php echo url_for(['module' => 'serial', 'action' => 'edit']); ?>" class="btn btn-success w-100">
          <i class="fas fa-plus me-1"></i><?php echo __('New Subscription'); ?>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Subscriptions table -->
<div class="card shadow-sm">
  <div class="card-header">
    <i class="fas fa-newspaper me-2"></i><?php echo __('Subscriptions'); ?>
    <span class="badge bg-secondary ms-2"><?php echo (int) $sf_data->getRaw('total'); ?></span>
  </div>
  <div class="card-body p-0">
    <?php $rawResults = $sf_data->getRaw('results'); ?>
    <?php if (empty($rawResults)): ?>
      <div class="p-3 text-muted">
        <i class="fas fa-info-circle me-2"></i><?php echo __('No subscriptions found.'); ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Title'); ?></th>
              <th><?php echo __('ISSN'); ?></th>
              <th><?php echo __('Vendor'); ?></th>
              <th><?php echo __('Frequency'); ?></th>
              <th><?php echo __('Status'); ?></th>
              <th><?php echo __('Renewal date'); ?></th>
              <th class="text-end"><?php echo __('Cost/year'); ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rawResults as $row): ?>
              <?php
                $statusBadge = 'bg-secondary';
                if (($row->subscription_status ?? '') === 'active') {
                    $statusBadge = 'bg-success';
                } elseif (($row->subscription_status ?? '') === 'suspended') {
                    $statusBadge = 'bg-warning text-dark';
                } elseif (($row->subscription_status ?? '') === 'cancelled') {
                    $statusBadge = 'bg-danger';
                } elseif (($row->subscription_status ?? '') === 'expired') {
                    $statusBadge = 'bg-dark';
                }
              ?>
              <tr>
                <td>
                  <a href="<?php echo url_for(['module' => 'serial', 'action' => 'view', 'id' => $row->id]); ?>">
                    <?php echo esc_entities($row->title ?? __('Untitled')); ?>
                  </a>
                </td>
                <td><?php echo esc_entities($row->issn ?? '-'); ?></td>
                <td><?php echo esc_entities($row->vendor_name ?? '-'); ?></td>
                <td><?php echo esc_entities(ucfirst($row->frequency ?? '-')); ?></td>
                <td>
                  <span class="badge <?php echo $statusBadge; ?>">
                    <?php echo esc_entities(ucfirst($row->subscription_status ?? 'unknown')); ?>
                  </span>
                </td>
                <td><?php echo esc_entities($row->renewal_date ?? '-'); ?></td>
                <td class="text-end">
                  <?php if (!empty($row->cost_per_year)): ?>
                    <?php echo esc_entities($row->currency ?? 'USD'); ?> <?php echo number_format((float) $row->cost_per_year, 2); ?>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?php echo url_for(['module' => 'serial', 'action' => 'view', 'id' => $row->id]); ?>"
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
        <a class="page-link" href="<?php echo url_for(['module' => 'serial', 'action' => 'index',
          'q' => $sf_data->getRaw('q'), 'subscription_status' => $sf_data->getRaw('subscriptionStatus'),
          'page' => $rawPage - 1]); ?>">
          &laquo; <?php echo __('Previous'); ?>
        </a>
      </li>
      <?php for ($p = 1; $p <= $rawTotalPages; $p++): ?>
        <li class="page-item <?php echo $p === $rawPage ? 'active' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'serial', 'action' => 'index',
            'q' => $sf_data->getRaw('q'), 'subscription_status' => $sf_data->getRaw('subscriptionStatus'),
            'page' => $p]); ?>">
            <?php echo $p; ?>
          </a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?php echo $rawPage >= $rawTotalPages ? 'disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'serial', 'action' => 'index',
          'q' => $sf_data->getRaw('q'), 'subscription_status' => $sf_data->getRaw('subscriptionStatus'),
          'page' => $rawPage + 1]); ?>">
          <?php echo __('Next'); ?> &raquo;
        </a>
      </li>
    </ul>
  </nav>
<?php endif; ?>
