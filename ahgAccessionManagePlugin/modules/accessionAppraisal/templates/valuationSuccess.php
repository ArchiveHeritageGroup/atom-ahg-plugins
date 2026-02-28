<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>
    <?php echo __('Valuation History'); ?>
    <small class="text-muted">&mdash; <?php echo htmlspecialchars($accession['identifier'] ?? ''); ?></small>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<?php
  $flash = $sf_user->getFlash('notice', '');
  $accId = $accession['id'] ?? 0;
  $accTitle = $accession['title'] ?? '';

  $typeLabels = [
      'initial' => __('Initial'),
      'revaluation' => __('Revaluation'),
      'impairment' => __('Impairment'),
      'disposal' => __('Disposal'),
  ];
  $typeBadgeColors = [
      'initial' => 'primary',
      'revaluation' => 'info',
      'impairment' => 'warning',
      'disposal' => 'danger',
  ];
  $methodLabels = [
      'cost' => __('Cost'),
      'market' => __('Market'),
      'income' => __('Income'),
      'replacement' => __('Replacement'),
      'nominal' => __('Nominal'),
  ];
?>

<?php if ($flash): ?>
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
  <?php echo htmlspecialchars($flash); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'accessionManage', 'action' => 'dashboard']); ?>"><?php echo __('Accessions'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for('@accession_view_override?slug=' . ($accession['slug'] ?? '')); ?>"><?php echo htmlspecialchars($accession['identifier'] ?? ''); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Valuation History'); ?></li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h4 mb-0"><?php echo htmlspecialchars($accession['identifier'] ?? ''); ?></h2>
    <?php if (!empty($accTitle)): ?>
    <p class="text-muted mb-0"><?php echo htmlspecialchars($accTitle); ?></p>
    <?php endif; ?>
  </div>
  <div class="btn-group">
    <a href="<?php echo url_for('@accession_appraisal_form?id=' . $accId); ?>" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-clipboard-check me-1"></i><?php echo __('Appraisals'); ?>
    </a>
    <a href="<?php echo url_for('@accession_valuation_report'); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-chart-bar me-1"></i><?php echo __('Portfolio Report'); ?>
    </a>
  </div>
</div>

<div class="row">
  <div class="col-lg-8">
    <!-- Current Value Highlight -->
    <?php if ($currentValuation): ?>
    <div class="card mb-4 border-success">
      <div class="card-header bg-success text-white">
        <i class="fas fa-coins me-2"></i><?php echo __('Current Value'); ?>
      </div>
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="display-6 fw-bold">
              <?php echo htmlspecialchars($currentValuation->currency ?? 'ZAR'); ?> <?php echo number_format($currentValuation->monetary_value, 2); ?>
            </div>
          </div>
          <div class="col-md-6">
            <table class="table table-sm table-borderless mb-0">
              <tr>
                <th class="text-muted pe-3"><?php echo __('Date'); ?></th>
                <td><?php echo date('d M Y', strtotime($currentValuation->valuation_date)); ?></td>
              </tr>
              <tr>
                <th class="text-muted pe-3"><?php echo __('Method'); ?></th>
                <td><?php echo $methodLabels[$currentValuation->method ?? ''] ?? ($currentValuation->method ? ucfirst($currentValuation->method) : '&mdash;'); ?></td>
              </tr>
              <tr>
                <th class="text-muted pe-3"><?php echo __('Valuer'); ?></th>
                <td><?php echo htmlspecialchars($currentValuation->valuer ?? ''); ?><?php if (empty($currentValuation->valuer)) echo '&mdash;'; ?></td>
              </tr>
              <tr>
                <th class="text-muted pe-3"><?php echo __('Type'); ?></th>
                <td>
                  <span class="badge bg-<?php echo $typeBadgeColors[$currentValuation->valuation_type] ?? 'secondary'; ?>">
                    <?php echo $typeLabels[$currentValuation->valuation_type] ?? ucfirst($currentValuation->valuation_type); ?>
                  </span>
                </td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Valuation History Table -->
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-history me-2"></i><?php echo __('Valuation History'); ?>
      </div>
      <div class="card-body p-0">
        <?php if (is_array($valuations) && count($valuations) > 0): ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Date'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th class="text-end"><?php echo __('Amount'); ?></th>
                <th><?php echo __('Currency'); ?></th>
                <th><?php echo __('Valuer'); ?></th>
                <th><?php echo __('Method'); ?></th>
                <th><?php echo __('Notes'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($valuations as $v): ?>
              <tr>
                <td><?php echo date('d M Y', strtotime($v->valuation_date)); ?></td>
                <td>
                  <span class="badge bg-<?php echo $typeBadgeColors[$v->valuation_type] ?? 'secondary'; ?>">
                    <?php echo $typeLabels[$v->valuation_type] ?? ucfirst($v->valuation_type); ?>
                  </span>
                </td>
                <td class="text-end fw-bold"><?php echo number_format($v->monetary_value, 2); ?></td>
                <td><?php echo htmlspecialchars($v->currency ?? 'ZAR'); ?></td>
                <td><?php echo htmlspecialchars($v->valuer ?? ''); ?><?php if (empty($v->valuer)) echo '&mdash;'; ?></td>
                <td><?php echo $methodLabels[$v->method ?? ''] ?? ($v->method ? ucfirst($v->method) : '&mdash;'); ?></td>
                <td>
                  <?php if (!empty($v->notes)): ?>
                  <?php $notesText = $v->notes; $truncated = mb_strlen($notesText) > 40 ? mb_substr($notesText, 0, 40) . '...' : $notesText; ?>
                  <span class="text-muted small" title="<?php echo htmlspecialchars($notesText); ?>"><?php echo htmlspecialchars($truncated); ?></span>
                  <?php else: ?>
                  <span class="text-muted">&mdash;</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4 text-muted">
          <i class="fas fa-coins fa-2x mb-2 d-block"></i>
          <?php echo __('No valuations recorded yet. Add one using the form.'); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <!-- Add Valuation Form -->
    <div class="card mb-4 border-primary">
      <div class="card-header bg-primary text-white">
        <i class="fas fa-plus-circle me-2"></i><?php echo __('Add Valuation'); ?>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo url_for('@accession_valuation_add?id=' . $accId); ?>">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Type'); ?> <span class="text-danger">*</span></label>
            <select name="valuation_type" class="form-select" required>
              <?php foreach ($typeLabels as $val => $label): ?>
              <option value="<?php echo htmlspecialchars($val); ?>"><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Value'); ?> <span class="text-danger">*</span></label>
            <div class="input-group">
              <select name="currency" class="form-select" style="max-width: 80px;">
                <?php foreach (['ZAR', 'USD', 'EUR', 'GBP'] as $cur): ?>
                <option value="<?php echo $cur; ?>"><?php echo $cur; ?></option>
                <?php endforeach; ?>
              </select>
              <input type="number" name="monetary_value" class="form-control" step="0.01" placeholder="0.00" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Valuation Date'); ?> <span class="text-danger">*</span></label>
            <input type="date" name="valuation_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Valuer'); ?></label>
            <input type="text" name="valuer" class="form-control" placeholder="<?php echo __('Name of valuer or organisation'); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Method'); ?></label>
            <select name="method" class="form-select">
              <option value=""><?php echo __('-- Select --'); ?></option>
              <?php foreach ($methodLabels as $val => $label): ?>
              <option value="<?php echo htmlspecialchars($val); ?>"><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Reference Document'); ?></label>
            <input type="text" name="reference_document" class="form-control" placeholder="<?php echo __('Report number, file reference, etc.'); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Notes'); ?></label>
            <textarea name="notes" class="form-control" rows="3" placeholder="<?php echo __('Additional notes about this valuation...'); ?>"></textarea>
          </div>

          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-save me-1"></i><?php echo __('Record Valuation'); ?>
          </button>
        </form>
      </div>
    </div>

    <!-- GRAP/IPSAS Info -->
    <div class="card">
      <div class="card-header">
        <i class="fas fa-info-circle me-2"></i><?php echo __('Compliance'); ?>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-2">
          <?php echo __('Valuation records support compliance with:'); ?>
        </p>
        <ul class="list-unstyled small mb-0">
          <li><i class="fas fa-check text-success me-1"></i> <?php echo __('GRAP 103 (Heritage Assets)'); ?></li>
          <li><i class="fas fa-check text-success me-1"></i> <?php echo __('IPSAS 45 (Property, Plant and Equipment)'); ?></li>
          <li><i class="fas fa-check text-success me-1"></i> <?php echo __('NARSSA Audit Requirements'); ?></li>
        </ul>
      </div>
    </div>
  </div>
</div>
<?php end_slot(); ?>
