<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Portfolio Valuation Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<?php
  $flash = $sf_user->getFlash('notice', '');
  $report = $sf_data->getRaw('report');
  $totalValue = $report['total_value'] ?? 0;
  $byCurrency = $report['by_currency'] ?? [];
  $byType = $report['by_type'] ?? [];
  $accessionCount = $report['accession_count'] ?? 0;
  $recentValuations = $sf_data->getRaw('recentValuations');
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
    <li class="breadcrumb-item active"><?php echo __('Valuation Report'); ?></li>
  </ol>
</nav>

<!-- Summary Cards -->
<div class="row mb-4">
  <div class="col-md-4">
    <div class="card border-primary h-100">
      <div class="card-body text-center">
        <div class="text-muted small text-uppercase mb-1"><?php echo __('Total Portfolio Value'); ?></div>
        <div class="display-6 fw-bold text-primary">
          <?php echo number_format($totalValue, 2); ?>
        </div>
        <p class="text-muted small mb-0"><?php echo __('Aggregate across all currencies'); ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-success h-100">
      <div class="card-body text-center">
        <div class="text-muted small text-uppercase mb-1"><?php echo __('Valued Accessions'); ?></div>
        <div class="display-6 fw-bold text-success">
          <?php echo number_format($accessionCount); ?>
        </div>
        <p class="text-muted small mb-0"><?php echo __('Accessions with at least one valuation'); ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-info h-100">
      <div class="card-body text-center">
        <div class="text-muted small text-uppercase mb-1"><?php echo __('Currencies'); ?></div>
        <div class="display-6 fw-bold text-info">
          <?php echo count($byCurrency); ?>
        </div>
        <p class="text-muted small mb-0"><?php echo __('Distinct currencies in portfolio'); ?></p>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <!-- Breakdown by Currency -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">
        <i class="fas fa-money-bill-wave me-2"></i><?php echo __('Breakdown by Currency'); ?>
      </div>
      <div class="card-body p-0">
        <?php if (count($byCurrency) > 0): ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Currency'); ?></th>
                <th class="text-end"><?php echo __('Total Value'); ?></th>
                <th class="text-end"><?php echo __('Share'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($byCurrency as $currency => $amount): ?>
              <tr>
                <td>
                  <span class="fw-bold"><?php echo htmlspecialchars($currency); ?></span>
                </td>
                <td class="text-end fw-bold"><?php echo number_format($amount, 2); ?></td>
                <td class="text-end">
                  <?php if ($totalValue > 0): ?>
                  <?php $pct = ($amount / $totalValue) * 100; ?>
                  <div class="d-flex align-items-center justify-content-end">
                    <div class="progress me-2" style="width: 60px; height: 6px;">
                      <div class="progress-bar bg-primary" style="width: <?php echo $pct; ?>%"></div>
                    </div>
                    <span class="small"><?php echo number_format($pct, 1); ?>%</span>
                  </div>
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
          <?php echo __('No valuations recorded yet.'); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Breakdown by Type -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">
        <i class="fas fa-tags me-2"></i><?php echo __('Breakdown by Valuation Type'); ?>
      </div>
      <div class="card-body p-0">
        <?php
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
        ?>
        <?php if (count($byType) > 0): ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Type'); ?></th>
                <th class="text-end"><?php echo __('Count'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($byType as $type => $count): ?>
              <tr>
                <td>
                  <span class="badge bg-<?php echo $typeBadgeColors[$type] ?? 'secondary'; ?>">
                    <?php echo $typeLabels[$type] ?? ucfirst($type); ?>
                  </span>
                </td>
                <td class="text-end"><?php echo number_format($count); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4 text-muted">
          <?php echo __('No data available.'); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Recent Valuations Table -->
<div class="card">
  <div class="card-header">
    <i class="fas fa-clock me-2"></i><?php echo __('Recent Valuations'); ?>
  </div>
  <div class="card-body p-0">
    <?php if (is_array($recentValuations) && count($recentValuations) > 0): ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Accession'); ?></th>
            <th><?php echo __('Title'); ?></th>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th class="text-end"><?php echo __('Amount'); ?></th>
            <th><?php echo __('Currency'); ?></th>
            <th><?php echo __('Valuer'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentValuations as $rv): ?>
          <tr>
            <td>
              <?php if (!empty($rv->slug)): ?>
              <a href="<?php echo url_for('@accession_view_override?slug=' . $rv->slug); ?>">
                <?php echo htmlspecialchars($rv->identifier ?? ''); ?><?php if (empty($rv->identifier)) echo '&mdash;'; ?>
              </a>
              <?php else: ?>
              <?php echo htmlspecialchars($rv->identifier ?? ''); ?><?php if (empty($rv->identifier)) echo '&mdash;'; ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($rv->accession_title)): ?>
              <?php $titleText = $rv->accession_title; $truncTitle = mb_strlen($titleText) > 50 ? mb_substr($titleText, 0, 50) . '...' : $titleText; ?>
              <?php echo htmlspecialchars($truncTitle); ?>
              <?php else: ?>
              <span class="text-muted">&mdash;</span>
              <?php endif; ?>
            </td>
            <td><?php echo date('d M Y', strtotime($rv->valuation_date)); ?></td>
            <td>
              <span class="badge bg-<?php echo $typeBadgeColors[$rv->valuation_type] ?? 'secondary'; ?>">
                <?php echo $typeLabels[$rv->valuation_type] ?? ucfirst($rv->valuation_type); ?>
              </span>
            </td>
            <td class="text-end fw-bold"><?php echo number_format($rv->monetary_value, 2); ?></td>
            <td><?php echo htmlspecialchars($rv->currency ?? 'ZAR'); ?></td>
            <td><?php echo htmlspecialchars($rv->valuer ?? ''); ?><?php if (empty($rv->valuer)) echo '&mdash;'; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="text-center py-4 text-muted">
      <i class="fas fa-coins fa-2x mb-2 d-block"></i>
      <?php echo __('No valuations recorded across the system yet.'); ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php end_slot(); ?>
