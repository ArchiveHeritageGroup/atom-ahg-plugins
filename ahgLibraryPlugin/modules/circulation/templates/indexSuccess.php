<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Circulation — Checkout Station'); ?></h1>
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

<!-- Stats bar -->
<div class="row mb-4">
  <div class="col-md-4">
    <div class="card text-center border-primary">
      <div class="card-body py-2">
        <div class="fs-4 fw-bold text-primary"><?php echo (int) $sf_data->getRaw('stats')['checkedOut']; ?></div>
        <small class="text-muted"><?php echo __('Items checked out'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center border-danger">
      <div class="card-body py-2">
        <div class="fs-4 fw-bold text-danger"><?php echo (int) $sf_data->getRaw('stats')['overdueCount']; ?></div>
        <small class="text-muted"><?php echo __('Overdue items'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center border-success">
      <div class="card-body py-2">
        <div class="fs-4 fw-bold text-success"><?php echo (int) $sf_data->getRaw('stats')['todayTransactions']; ?></div>
        <small class="text-muted"><?php echo __('Transactions today'); ?></small>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Left: Barcode scanning / actions -->
  <div class="col-lg-7 mb-4">

    <!-- Checkout form -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-primary text-white">
        <i class="fas fa-barcode me-2"></i><?php echo __('Checkout / Return'); ?>
      </div>
      <div class="card-body">

        <!-- Checkout -->
        <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'checkout']); ?>">
          <h6 class="fw-bold mb-3"><i class="fas fa-sign-out-alt me-2 text-primary"></i><?php echo __('Check Out Item'); ?></h6>
          <div class="row g-2 mb-2">
            <div class="col-md-5">
              <label for="checkout_patron_barcode" class="form-label"><?php echo __('Patron barcode'); ?></label>
              <input type="text" class="form-control" id="checkout_patron_barcode" name="patron_barcode"
                     placeholder="<?php echo __('Scan or type patron barcode'); ?>"
                     value="<?php echo esc_entities($patronBarcode ?? ''); ?>" required>
            </div>
            <div class="col-md-5">
              <label for="checkout_item_barcode" class="form-label"><?php echo __('Item barcode'); ?></label>
              <input type="text" class="form-control" id="checkout_item_barcode" name="item_barcode"
                     placeholder="<?php echo __('Scan or type item barcode'); ?>" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-out-alt me-1"></i><?php echo __('Checkout'); ?>
              </button>
            </div>
          </div>
        </form>

        <hr>

        <!-- Return -->
        <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'checkin']); ?>">
          <h6 class="fw-bold mb-3"><i class="fas fa-sign-in-alt me-2 text-success"></i><?php echo __('Return Item'); ?></h6>
          <div class="row g-2 mb-2">
            <div class="col-md-10">
              <label for="return_item_barcode" class="form-label"><?php echo __('Item barcode'); ?></label>
              <input type="text" class="form-control" id="return_item_barcode" name="item_barcode"
                     placeholder="<?php echo __('Scan or type item barcode'); ?>" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button type="submit" class="btn btn-success w-100">
                <i class="fas fa-sign-in-alt me-1"></i><?php echo __('Return'); ?>
              </button>
            </div>
          </div>
        </form>

        <hr>

        <!-- Renew -->
        <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'renew']); ?>">
          <h6 class="fw-bold mb-3"><i class="fas fa-redo me-2 text-warning"></i><?php echo __('Renew Item'); ?></h6>
          <div class="row g-2 mb-2">
            <div class="col-md-5">
              <label for="renew_patron_barcode" class="form-label"><?php echo __('Patron barcode'); ?></label>
              <input type="text" class="form-control" id="renew_patron_barcode" name="patron_barcode"
                     placeholder="<?php echo __('Patron barcode (optional)'); ?>"
                     value="<?php echo esc_entities($patronBarcode ?? ''); ?>">
            </div>
            <div class="col-md-5">
              <label for="renew_item_barcode" class="form-label"><?php echo __('Item barcode'); ?></label>
              <input type="text" class="form-control" id="renew_item_barcode" name="item_barcode"
                     placeholder="<?php echo __('Scan or type item barcode'); ?>" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button type="submit" class="btn btn-warning w-100">
                <i class="fas fa-redo me-1"></i><?php echo __('Renew'); ?>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Quick links -->
    <div class="d-flex gap-2 mb-4">
      <a href="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue']); ?>" class="btn btn-outline-danger">
        <i class="fas fa-clock me-1"></i><?php echo __('Overdue items'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'circulation', 'action' => 'loanRules']); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-cog me-1"></i><?php echo __('Loan rules'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'library', 'action' => 'browse']); ?>" class="btn btn-outline-info">
        <i class="fas fa-book me-1"></i><?php echo __('Browse catalogue'); ?>
      </a>
    </div>
  </div>

  <!-- Right: Patron info -->
  <div class="col-lg-5 mb-4">

    <!-- Patron info card -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-secondary text-white">
        <i class="fas fa-user me-2"></i><?php echo __('Patron Information'); ?>
      </div>
      <div class="card-body">
        <?php $rawPatron = $sf_data->getRaw('patronInfo'); ?>
        <?php if ($rawPatron): ?>
          <table class="table table-sm mb-0">
            <tr>
              <th class="text-muted" style="width:40%"><?php echo __('Name'); ?></th>
              <td class="fw-bold"><?php echo esc_entities($rawPatron->name); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Barcode'); ?></th>
              <td><code><?php echo esc_entities($rawPatron->barcode); ?></code></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Type'); ?></th>
              <td><?php echo esc_entities(ucfirst($rawPatron->patron_type ?? '-')); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Status'); ?></th>
              <td>
                <?php
                  $statusClass = ($rawPatron->patron_status === 'active') ? 'bg-success' : 'bg-warning';
                ?>
                <span class="badge <?php echo $statusClass; ?>">
                  <?php echo esc_entities(ucfirst($rawPatron->patron_status ?? 'unknown')); ?>
                </span>
              </td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Active checkouts'); ?></th>
              <td><?php echo (int) $rawPatron->active_checkouts; ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Overdue items'); ?></th>
              <td>
                <?php if ($rawPatron->overdue_items > 0): ?>
                  <span class="text-danger fw-bold"><?php echo (int) $rawPatron->overdue_items; ?></span>
                <?php else: ?>
                  <span class="text-success">0</span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Outstanding fines'); ?></th>
              <td>
                <?php if ($rawPatron->outstanding_fines > 0): ?>
                  <span class="text-danger fw-bold"><?php echo number_format($rawPatron->outstanding_fines, 2); ?></span>
                <?php else: ?>
                  <span class="text-success">0.00</span>
                <?php endif; ?>
              </td>
            </tr>
          </table>
        <?php else: ?>
          <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-2"></i>
            <?php echo __('Scan a patron barcode to display their information here.'); ?>
          </p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Patron lookup form -->
    <div class="card shadow-sm">
      <div class="card-header">
        <i class="fas fa-search me-2"></i><?php echo __('Look Up Patron'); ?>
      </div>
      <div class="card-body">
        <form method="get" action="<?php echo url_for(['module' => 'circulation', 'action' => 'index']); ?>">
          <div class="input-group">
            <input type="text" class="form-control" name="patron_barcode"
                   placeholder="<?php echo __('Patron barcode'); ?>"
                   value="<?php echo esc_entities($patronBarcode ?? ''); ?>">
            <button type="submit" class="btn btn-outline-secondary">
              <i class="fas fa-search"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Recent transactions -->
<div class="card shadow-sm">
  <div class="card-header">
    <i class="fas fa-history me-2"></i><?php echo __('Recent Transactions'); ?>
  </div>
  <div class="card-body p-0">
    <?php $rawTransactions = $sf_data->getRaw('recentTransactions'); ?>
    <?php if (empty($rawTransactions)): ?>
      <div class="p-3 text-muted">
        <i class="fas fa-info-circle me-2"></i>
        <?php echo __('No recent transactions found.'); ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Type'); ?></th>
              <th><?php echo __('Item'); ?></th>
              <th><?php echo __('Barcode'); ?></th>
              <th><?php echo __('Patron'); ?></th>
              <th><?php echo __('Checkout date'); ?></th>
              <th><?php echo __('Due date'); ?></th>
              <th><?php echo __('Returned'); ?></th>
              <th><?php echo __('Renewals'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rawTransactions as $tx): ?>
              <?php
                $actionBadge = 'bg-secondary';
                $actionLabel = ucfirst($tx->action_type ?? 'unknown');
                if ($tx->action_type === 'checkout') {
                    $actionBadge = 'bg-primary';
                } elseif ($tx->action_type === 'checkin') {
                    $actionBadge = 'bg-success';
                } elseif ($tx->action_type === 'renewal') {
                    $actionBadge = 'bg-warning text-dark';
                }
                $isOverdue = empty($tx->return_date) && !empty($tx->due_date) && $tx->due_date < date('Y-m-d');
              ?>
              <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                <td><span class="badge <?php echo $actionBadge; ?>"><?php echo $actionLabel; ?></span></td>
                <td><?php echo esc_entities($tx->item_title ?? '-'); ?></td>
                <td><code><?php echo esc_entities($tx->item_barcode ?? '-'); ?></code></td>
                <td><?php echo esc_entities($tx->patron_name ?? '-'); ?></td>
                <td><?php echo esc_entities($tx->checkout_date ?? '-'); ?></td>
                <td>
                  <?php echo esc_entities($tx->due_date ?? '-'); ?>
                  <?php if ($isOverdue): ?>
                    <i class="fas fa-exclamation-triangle text-danger ms-1" title="<?php echo __('Overdue'); ?>"></i>
                  <?php endif; ?>
                </td>
                <td><?php echo esc_entities($tx->return_date ?? '-'); ?></td>
                <td><?php echo (int) ($tx->renewals ?? 0); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
