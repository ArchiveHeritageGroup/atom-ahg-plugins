<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Acquisition Budgets'); ?></h1>
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

<!-- Action bar -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to orders'); ?>
  </a>

  <!-- Fiscal year filter -->
  <form method="get" action="<?php echo url_for(['module' => 'acquisition', 'action' => 'budgets']); ?>" class="d-flex gap-2 align-items-center">
    <label for="fy_filter" class="form-label mb-0"><?php echo __('Fiscal year'); ?>:</label>
    <select class="form-select form-select-sm" id="fy_filter" name="fiscal_year" style="width:auto;" onchange="this.form.submit()">
      <?php
        $currentYear = (int) date('Y');
        $selectedYear = $sf_data->getRaw('fiscalYear') ?? $currentYear;
        for ($y = $currentYear + 1; $y >= $currentYear - 5; $y--):
      ?>
        <option value="<?php echo $y; ?>" <?php echo (string) $y === (string) $selectedYear ? 'selected' : ''; ?>>
          <?php echo $y; ?>
        </option>
      <?php endfor; ?>
    </select>
  </form>
</div>

<!-- Budgets table -->
<?php $rawBudgets = $sf_data->getRaw('budgets'); ?>
<div class="card shadow-sm mb-4">
  <div class="card-header">
    <i class="fas fa-wallet me-2"></i><?php echo __('Budgets for %1%', ['%1%' => esc_entities($selectedYear)]); ?>
    <span class="badge bg-secondary ms-2"><?php echo count($rawBudgets); ?></span>
  </div>
  <div class="card-body p-0">
    <?php if (empty($rawBudgets)): ?>
      <div class="p-3 text-muted">
        <i class="fas fa-info-circle me-2"></i>
        <?php echo __('No budgets found for this fiscal year.'); ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Name'); ?></th>
              <th><?php echo __('Code'); ?></th>
              <th><?php echo __('Fiscal year'); ?></th>
              <th class="text-end"><?php echo __('Allocated'); ?></th>
              <th class="text-end"><?php echo __('Spent'); ?></th>
              <th class="text-end"><?php echo __('Encumbered'); ?></th>
              <th class="text-end"><?php echo __('Available'); ?></th>
              <th><?php echo __('Category'); ?></th>
              <th style="width:30%"><?php echo __('Spend ratio'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rawBudgets as $budget): ?>
              <?php
                $allocated = (float) ($budget->allocated_amount ?? 0);
                $spent = (float) ($budget->spent_amount ?? 0);
                $encumbered = (float) ($budget->encumbered_amount ?? 0);
                $available = (float) ($budget->available_amount ?? ($allocated - $spent - $encumbered));
                $spendPct = $allocated > 0 ? min(100, round(($spent / $allocated) * 100)) : 0;
                $encPct = $allocated > 0 ? min(100, round(($encumbered / $allocated) * 100)) : 0;

                $barColor = 'bg-success';
                if ($spendPct >= 90) {
                    $barColor = 'bg-danger';
                } elseif ($spendPct >= 70) {
                    $barColor = 'bg-warning';
                }
              ?>
              <tr>
                <td class="fw-bold"><?php echo esc_entities($budget->budget_name ?? '-'); ?></td>
                <td><code><?php echo esc_entities($budget->budget_code ?? '-'); ?></code></td>
                <td><?php echo esc_entities($budget->fiscal_year ?? '-'); ?></td>
                <td class="text-end"><?php echo number_format($allocated, 2); ?></td>
                <td class="text-end"><?php echo number_format($spent, 2); ?></td>
                <td class="text-end"><?php echo number_format($encumbered, 2); ?></td>
                <td class="text-end fw-bold <?php echo $available < 0 ? 'text-danger' : 'text-success'; ?>">
                  <?php echo number_format($available, 2); ?>
                </td>
                <td><?php echo esc_entities(ucfirst($budget->category ?? 'general')); ?></td>
                <td>
                  <div class="progress" style="height:20px;">
                    <div class="progress-bar <?php echo $barColor; ?>" role="progressbar"
                         style="width:<?php echo $spendPct; ?>%"
                         aria-valuenow="<?php echo $spendPct; ?>" aria-valuemin="0" aria-valuemax="100">
                      <?php echo $spendPct; ?>%
                    </div>
                    <?php if ($encPct > 0): ?>
                      <div class="progress-bar bg-info" role="progressbar"
                           style="width:<?php echo $encPct; ?>%"
                           aria-valuenow="<?php echo $encPct; ?>" aria-valuemin="0" aria-valuemax="100">
                      </div>
                    <?php endif; ?>
                  </div>
                  <small class="text-muted">
                    <?php echo __('Spent: %1%% | Enc: %2%%', ['%1%' => $spendPct, '%2%' => $encPct]); ?>
                  </small>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Add budget form (collapsible) -->
<div class="card shadow-sm">
  <div class="card-header" role="button" data-bs-toggle="collapse" data-bs-target="#addBudgetForm" aria-expanded="false">
    <i class="fas fa-plus me-2"></i><?php echo __('Add New Budget'); ?>
    <i class="fas fa-chevron-down float-end mt-1"></i>
  </div>
  <div class="collapse" id="addBudgetForm">
    <div class="card-body">
      <form method="post" action="<?php echo url_for(['module' => 'acquisition', 'action' => 'budgets']); ?>">
        <div class="row g-3">
          <div class="col-md-4">
            <label for="budget_name" class="form-label"><?php echo __('Budget name'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="budget_name" name="budget_name" required>
          </div>
          <div class="col-md-2">
            <label for="budget_code" class="form-label"><?php echo __('Budget code'); ?></label>
            <input type="text" class="form-control" id="budget_code" name="budget_code">
          </div>
          <div class="col-md-2">
            <label for="budget_fy" class="form-label"><?php echo __('Fiscal year'); ?></label>
            <input type="text" class="form-control" id="budget_fy" name="fiscal_year"
                   value="<?php echo esc_entities($selectedYear); ?>">
          </div>
          <div class="col-md-3">
            <label for="allocated_amount" class="form-label"><?php echo __('Allocated amount'); ?></label>
            <input type="number" class="form-control" id="allocated_amount" name="allocated_amount"
                   step="0.01" min="0" value="0.00">
          </div>
          <div class="col-md-2">
            <label for="budget_currency" class="form-label"><?php echo __('Currency'); ?></label>
            <select class="form-select" id="budget_currency" name="currency">
              <?php foreach (['USD', 'EUR', 'GBP', 'ZAR', 'CAD', 'AUD'] as $c): ?>
                <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label for="budget_category" class="form-label"><?php echo __('Category'); ?></label>
            <select class="form-select" id="budget_category" name="category">
              <?php foreach (['general', 'monographs', 'serials', 'electronic', 'media', 'preservation'] as $cat): ?>
                <option value="<?php echo $cat; ?>"><?php echo esc_entities(ucfirst($cat)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label for="budget_notes" class="form-label"><?php echo __('Notes'); ?></label>
            <input type="text" class="form-control" id="budget_notes" name="notes">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-plus me-1"></i><?php echo __('Create'); ?>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
