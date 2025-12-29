<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1 class="text-success"><i class="fa fa-calculator"></i> <?php echo __('GRAP 103 Heritage Asset Management') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<?php
// Get raw arrays to avoid sfOutputEscaperArrayDecorator issues
$overdueValuationsRaw = $sf_data->getRaw('overdueValuations') ?: [];
$expiredInsuranceRaw = $sf_data->getRaw('expiredInsurance') ?: [];
$complianceIssuesRaw = $sf_data->getRaw('complianceIssues') ?: [];
$recentAssetsRaw = $sf_data->getRaw('recentAssets') ?: [];
$summaryByClassRaw = $sf_data->getRaw('summaryByClass') ?: [];
$statsRaw = $sf_data->getRaw('stats') ?: [];
?>

<style>
.grap-dashboard .stat-card {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  text-align: center;
  border-left: 4px solid #ccc;
}
.grap-dashboard .stat-card.primary { border-left-color: #1B5E20; }
.grap-dashboard .stat-card.success { border-left-color: #4CAF50; }
.grap-dashboard .stat-card.warning { border-left-color: #FF9800; }
.grap-dashboard .stat-card.danger { border-left-color: #f44336; }
.grap-dashboard .stat-card.info { border-left-color: #2196F3; }
.grap-dashboard .stat-card h2 { margin: 0 0 5px 0; font-size: 2.5em; color: #1B5E20; }
.grap-dashboard .stat-card p { margin: 0; color: #666; }
.grap-dashboard .stat-card .currency { font-size: 1.5em; font-weight: bold; color: #1B5E20; }

.grap-dashboard .quick-actions { margin-bottom: 30px; }
.grap-dashboard .quick-actions .btn { margin-right: 10px; margin-bottom: 10px; }

.grap-dashboard .section-header {
  border-bottom: 2px solid #1B5E20;
  padding-bottom: 10px;
  margin: 30px 0 20px 0;
}
.grap-dashboard .section-header h3 {
  margin: 0;
  color: #1B5E20;
}

.grap-dashboard .alert-item {
  padding: 10px 15px;
  border-left: 3px solid;
  background: #f9f9f9;
  margin-bottom: 10px;
  border-radius: 4px;
}
.grap-dashboard .alert-item.overdue { border-left-color: #FF9800; }
.grap-dashboard .alert-item.expired { border-left-color: #f44336; }
.grap-dashboard .alert-item.incomplete { border-left-color: #2196F3; }

.grap-dashboard .report-card {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 15px;
  transition: all 0.2s;
  text-align: center;
}
.grap-dashboard .report-card:hover {
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  transform: translateY(-2px);
}
.grap-dashboard .report-card h4 { margin: 10px 0; color: #1B5E20; }
.grap-dashboard .report-card p { color: #666; margin-bottom: 15px; font-size: 0.9em; }
.grap-dashboard .report-card .fa { font-size: 2em; color: #1B5E20; }

.grap-dashboard .btn-grap {
  background-color: #1B5E20;
  border-color: #1B5E20;
  color: #fff;
}
.grap-dashboard .btn-grap:hover {
  background-color: #2E7D32;
  border-color: #2E7D32;
  color: #fff;
}
</style>

<div class="grap-dashboard">

  <!-- Quick Actions -->
  <div class="quick-actions">
    <a href="<?php echo url_for('grapReport/assetRegister') ?>" class="btn btn-grap">
      <i class="fa fa-list"></i> <?php echo __('Asset Register') ?>
    </a>
    <a href="<?php echo url_for('grapReport/disclosure') ?>" class="btn btn-outline-secondary">
      <i class="fa fa-file-text-o"></i> <?php echo __('Disclosure Note') ?>
    </a>
    <a href="<?php echo url_for('grapReport/assetRegister') ?>?format=csv" class="btn btn-outline-secondary">
      <i class="fa fa-download"></i> <?php echo __('Export CSV') ?>
    </a>
    <a href="<?php echo url_for('informationobject/browse') ?>" class="btn btn-success">
      <i class="fa fa-search"></i> <?php echo __('Browse Objects') ?>
    </a>
  </div>

  <!-- Statistics Cards Row 1 -->
  <div class="row">
    <div class="col-md-3">
      <div class="stat-card primary">
        <h2><?php echo number_format($statsRaw['total_assets'] ?? 0) ?></h2>
        <p><?php echo __('Total Heritage Assets') ?></p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card success">
        <h2><?php echo number_format($statsRaw['recognized_assets'] ?? 0) ?></h2>
        <p><?php echo __('Recognised Assets') ?></p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card warning">
        <h2><?php echo number_format($statsRaw['not_recognized_assets'] ?? 0) ?></h2>
        <p><?php echo __('Not Recognised') ?></p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card info">
        <h2><?php echo number_format($statsRaw['average_compliance'] ?? 0, 1) ?>%</h2>
        <p><?php echo __('Average Compliance') ?></p>
      </div>
    </div>
  </div>

  <!-- Statistics Cards Row 2 - Financial -->
  <div class="row">
    <div class="col-md-6">
      <div class="stat-card success">
        <p><?php echo __('Total Carrying Amount') ?></p>
        <div class="currency">R <?php echo number_format($statsRaw['total_carrying_amount'] ?? 0, 2) ?></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="stat-card info">
        <p><?php echo __('Total Insurance Value') ?></p>
        <div class="currency">R <?php echo number_format($statsRaw['total_insurance_value'] ?? 0, 2) ?></div>
      </div>
    </div>
  </div>

  <!-- Alerts Row -->
  <div class="row">
    <div class="col-md-4">
      <div class="stat-card <?php echo ($statsRaw['overdue_valuations'] ?? 0) > 0 ? 'warning' : 'success' ?>">
        <h2><?php echo number_format($statsRaw['overdue_valuations'] ?? 0) ?></h2>
        <p><i class="fa fa-clock-o"></i> <?php echo __('Overdue Valuations') ?></p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card <?php echo ($statsRaw['expired_insurance'] ?? 0) > 0 ? 'danger' : 'success' ?>">
        <h2><?php echo number_format($statsRaw['expired_insurance'] ?? 0) ?></h2>
        <p><i class="fa fa-shield"></i> <?php echo __('Expired Insurance') ?></p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card <?php echo count($complianceIssuesRaw) > 0 ? 'info' : 'success' ?>">
        <h2><?php echo count($complianceIssuesRaw) ?></h2>
        <p><i class="fa fa-exclamation-triangle"></i> <?php echo __('Low Compliance Items') ?></p>
      </div>
    </div>
  </div>

  <!-- Recent GRAP Entries -->
  <div class="section-header">
    <h3><i class="fa fa-history"></i> <?php echo __('Recent GRAP Entries') ?></h3>
  </div>

  <?php if (!empty($recentAssetsRaw)): ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Reference') ?></th>
            <th><?php echo __('Title') ?></th>
            <th><?php echo __('Asset Class') ?></th>
            <th><?php echo __('Status') ?></th>
            <th class="text-end"><?php echo __('Carrying Amount') ?></th>
            <th><?php echo __('Actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentAssetsRaw as $asset): ?>
            <tr>
              <td>
                <a href="<?php echo url_for(['slug' => $asset['slug'], 'module' => 'informationobject']) ?>">
                  <?php echo esc_entities($asset['reference_code'] ?: '-') ?>
                </a>
              </td>
              <td><?php echo esc_entities($asset['title'] ?: __('Untitled')) ?></td>
              <td>
                <?php 
                  $classes = GrapHeritageAssetForm::getAssetClassChoices();
                  echo esc_entities($classes[$asset['asset_class']] ?? $asset['asset_class'] ?? '-');
                ?>
              </td>
              <td>
                <?php 
                  $statuses = GrapHeritageAssetForm::getRecognitionStatusChoices();
                  $status = $asset['recognition_status'] ?? '';
                  $statusClass = 'bg-secondary';
                  if ($status === 'recognized') $statusClass = 'bg-success';
                  elseif ($status === 'not_recognized') $statusClass = 'bg-warning text-dark';
                  elseif ($status === 'pending') $statusClass = 'bg-info';
                ?>
                <span class="badge <?php echo $statusClass ?>">
                  <?php echo esc_entities($statuses[$status] ?? $status ?: '-') ?>
                </span>
              </td>
              <td class="text-end">
                <?php if ($asset['current_carrying_amount']): ?>
                  R <?php echo number_format($asset['current_carrying_amount'], 2) ?>
                <?php else: ?>
                  -
                <?php endif ?>
              </td>
              <td>
                <a href="<?php echo url_for(['slug' => $asset['slug'], 'module' => 'grap', 'action' => 'index']) ?>" 
                   class="btn btn-sm btn-outline-secondary" title="<?php echo __('View GRAP') ?>">
                  <i class="fa fa-eye"></i>
                </a>
                <a href="<?php echo url_for(['slug' => $asset['slug'], 'module' => 'grap', 'action' => 'edit']) ?>" 
                   class="btn btn-sm btn-grap" title="<?php echo __('Edit GRAP') ?>">
                  <i class="fa fa-pencil"></i>
                </a>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <p class="text-end">
      <a href="<?php echo url_for('grapReport/assetRegister') ?>" class="btn btn-outline-secondary btn-sm">
        <?php echo __('View All') ?> <i class="fa fa-arrow-right"></i>
      </a>
    </p>
  <?php else: ?>
    <div class="alert alert-info">
      <i class="fa fa-info-circle"></i>
      <?php echo __('No GRAP data has been entered yet. Browse to an information object and click "GRAP financial data" in the More menu to add financial data.') ?>
    </div>
  <?php endif ?>

  <!-- Summary by Asset Class -->
  <?php if (!empty($summaryByClassRaw)): ?>
    <div class="section-header">
      <h3><i class="fa fa-pie-chart"></i> <?php echo __('Summary by Asset Class') ?></h3>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Asset Class') ?></th>
            <th class="text-end"><?php echo __('Count') ?></th>
            <th class="text-end"><?php echo __('Recognised') ?></th>
            <th class="text-end"><?php echo __('Carrying Amount') ?></th>
            <th class="text-end"><?php echo __('Insurance Value') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($summaryByClassRaw as $row): ?>
            <tr>
              <td>
                <?php 
                  $classes = GrapHeritageAssetForm::getAssetClassChoices();
                  echo esc_entities($classes[$row['asset_class']] ?? $row['asset_class'] ?? __('Unclassified'));
                ?>
              </td>
              <td class="text-end"><?php echo number_format($row['asset_count']) ?></td>
              <td class="text-end"><?php echo number_format($row['recognized_count']) ?></td>
              <td class="text-end">R <?php echo number_format($row['total_carrying_amount'] ?? 0, 2) ?></td>
              <td class="text-end">R <?php echo number_format($row['total_insurance_value'] ?? 0, 2) ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
        <tfoot class="table-secondary">
          <tr>
            <th><?php echo __('Total') ?></th>
            <th class="text-end"><?php echo number_format($statsRaw['total_assets'] ?? 0) ?></th>
            <th class="text-end"><?php echo number_format($statsRaw['recognized_assets'] ?? 0) ?></th>
            <th class="text-end">R <?php echo number_format($statsRaw['total_carrying_amount'] ?? 0, 2) ?></th>
            <th class="text-end">R <?php echo number_format($statsRaw['total_insurance_value'] ?? 0, 2) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  <?php endif ?>

  <!-- Attention Required -->
  <?php if (!empty($overdueValuationsRaw) || !empty($expiredInsuranceRaw) || !empty($complianceIssuesRaw)): ?>
    <div class="section-header">
      <h3><i class="fa fa-exclamation-triangle text-warning"></i> <?php echo __('Attention Required') ?></h3>
    </div>

    <div class="row">
      <?php if (!empty($overdueValuationsRaw)): ?>
        <div class="col-md-4">
          <h5><i class="fa fa-clock-o text-warning"></i> <?php echo __('Overdue Valuations') ?></h5>
          <?php foreach (array_slice($overdueValuationsRaw, 0, 5) as $item): ?>
            <div class="alert-item overdue">
              <a href="<?php echo url_for(['slug' => $item['slug'], 'module' => 'grap', 'action' => 'edit']) ?>">
                <strong><?php echo esc_entities($item['reference_code'] ?: $item['title']) ?></strong>
              </a>
              <br><small class="text-muted"><?php echo esc_entities($item['valuation_status']) ?></small>
            </div>
          <?php endforeach ?>
          <?php if (count($overdueValuationsRaw) > 5): ?>
            <p class="text-muted"><em><?php echo __('...and %1% more', ['%1%' => count($overdueValuationsRaw) - 5]) ?></em></p>
          <?php endif ?>
        </div>
      <?php endif ?>

      <?php if (!empty($expiredInsuranceRaw)): ?>
        <div class="col-md-4">
          <h5><i class="fa fa-shield text-danger"></i> <?php echo __('Insurance Issues') ?></h5>
          <?php foreach (array_slice($expiredInsuranceRaw, 0, 5) as $item): ?>
            <div class="alert-item expired">
              <a href="<?php echo url_for(['slug' => $item['slug'], 'module' => 'grap', 'action' => 'edit']) ?>">
                <strong><?php echo esc_entities($item['reference_code'] ?: $item['title']) ?></strong>
              </a>
              <br><small class="text-muted"><?php echo esc_entities($item['insurance_status']) ?></small>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>

      <?php if (!empty($complianceIssuesRaw)): ?>
        <div class="col-md-4">
          <h5><i class="fa fa-exclamation-circle text-info"></i> <?php echo __('Low Compliance') ?></h5>
          <?php foreach (array_slice($complianceIssuesRaw, 0, 5) as $item): ?>
            <div class="alert-item incomplete">
              <a href="<?php echo url_for(['slug' => $item['slug'], 'module' => 'grap', 'action' => 'edit']) ?>">
                <strong><?php echo esc_entities($item['reference_code'] ?: $item['title']) ?></strong>
              </a>
              <br><small class="text-muted"><?php echo $item['compliance_percentage'] ?>% <?php echo __('complete') ?></small>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </div>
  <?php endif ?>

  <!-- Available Reports -->
  <div class="section-header">
    <h3><i class="fa fa-bar-chart"></i> <?php echo __('Available Reports') ?></h3>
  </div>

  <div class="row">
    <div class="col-md-4">
      <div class="report-card">
        <i class="fa fa-list"></i>
        <h4><?php echo __('Asset Register') ?></h4>
        <p><?php echo __('Complete listing of all heritage assets with GRAP data, including filters and search.') ?></p>
        <a href="<?php echo url_for('grapReport/assetRegister') ?>" class="btn btn-grap w-100">
          <?php echo __('View Report') ?>
        </a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="report-card">
        <i class="fa fa-file-text-o"></i>
        <h4><?php echo __('GRAP 103 Disclosure Note') ?></h4>
        <p><?php echo __('Generate the disclosure note for financial statements as required by GRAP 103.') ?></p>
        <a href="<?php echo url_for('grapReport/disclosure') ?>" class="btn btn-grap w-100">
          <?php echo __('View Report') ?>
        </a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="report-card">
        <i class="fa fa-clock-o"></i>
        <h4><?php echo __('Valuation Schedule') ?></h4>
        <p><?php echo __('Items due for revaluation based on their revaluation frequency settings.') ?></p>
        <a href="<?php echo url_for('grapReport/valuationSchedule') ?>" class="btn btn-grap w-100">
          <?php echo __('View Report') ?>
        </a>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-4">
      <div class="report-card">
        <i class="fa fa-shield"></i>
        <h4><?php echo __('Insurance Expiry') ?></h4>
        <p><?php echo __('Track insurance policy expiry dates and identify uninsured assets.') ?></p>
        <a href="<?php echo url_for('grapReport/insuranceExpiry') ?>" class="btn btn-grap w-100">
          <?php echo __('View Report') ?>
        </a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="report-card">
        <i class="fa fa-check-square-o"></i>
        <h4><?php echo __('Compliance Check') ?></h4>
        <p><?php echo __('Identify records with incomplete GRAP data and compliance percentage.') ?></p>
        <a href="<?php echo url_for('grapReport/compliance') ?>" class="btn btn-grap w-100">
          <?php echo __('View Report') ?>
        </a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="report-card">
        <i class="fa fa-download"></i>
        <h4><?php echo __('Export to CSV') ?></h4>
        <p><?php echo __('Export the complete asset register to CSV format for Excel or other applications.') ?></p>
        <a href="<?php echo url_for('grapReport/assetRegister') ?>?format=csv" class="btn btn-success w-100">
          <?php echo __('Download CSV') ?>
        </a>
      </div>
    </div>
  </div>

</div>

<?php end_slot() ?>
