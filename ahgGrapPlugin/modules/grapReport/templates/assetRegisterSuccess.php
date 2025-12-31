<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1 class="text-success"><?php echo __('GRAP Report - Heritage Asset Register') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.grap-report .summary-stats {
  display: flex;
  gap: 30px;
  margin-bottom: 30px;
}
.grap-report .stat-item h2 {
  color: #1B5E20;
  margin: 0 0 5px 0;
  font-size: 2em;
}
.grap-report .stat-item p {
  color: #666;
  margin: 0;
}
.grap-report .filter-section {
  background: #f9f9f9;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}
.grap-report .filter-section h3 {
  color: #1B5E20;
  margin-top: 0;
}
.grap-report .filter-row {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 15px;
}
.grap-report .filter-group {
  display: flex;
  flex-direction: column;
}
.grap-report .filter-group label {
  font-size: 0.85em;
  color: #666;
  margin-bottom: 3px;
}
.grap-report .results-header {
  color: #1B5E20;
  margin: 20px 0 10px 0;
}
.grap-report .related-reports {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #ddd;
}
.grap-report .related-reports h3 {
  color: #1B5E20;
}
.grap-report .related-reports ul {
  list-style: disc;
  padding-left: 20px;
}
.grap-report .related-reports a {
  color: #1B5E20;
}
.grap-report .btn-apply {
  background: #1B5E20;
  color: #fff;
  border: none;
}
.grap-report .btn-apply:hover {
  background: #2E7D32;
  color: #fff;
}
.grap-report .btn-clear {
  background: #666;
  color: #fff;
  border: none;
}
.grap-report .btn-export {
  background: #2E7D32;
  color: #fff;
  border: none;
}
</style>

<div class="grap-report">

  <!-- Summary Statistics -->
  <h3 class="text-success"><?php echo __('Summary Statistics') ?></h3>
  <div class="summary-stats">
    <div class="stat-item">
      <h2><?php echo number_format($totals['count']) ?></h2>
      <p><?php echo __('Total Items') ?></p>
    </div>
    <div class="stat-item">
      <h2><?php echo number_format($totals['initial_value'] ?? 0, 2) ?></h2>
      <p><?php echo __('Total Initial Value') ?></p>
    </div>
    <div class="stat-item">
      <h2><?php echo number_format($totals['carrying_amount'], 2) ?></h2>
      <p><?php echo __('Total Carrying Amount') ?></p>
    </div>
  </div>

  <!-- Filter Section -->
  <div class="filter-section">
    <h3><?php echo __('Filter Results') ?></h3>
    <form method="get">
      <div class="filter-row">
        <div class="filter-group">
          <label><?php echo __('Asset_class') ?></label>
          <select name="asset_class" class="input-medium">
            <option value=""><?php echo __('All') ?></option>
            <?php foreach ($assetClasses as $key => $label): ?>
              <?php if ($key): ?>
                <option value="<?php echo $key ?>" <?php echo ($filters['asset_class'] ?? '') === $key ? 'selected' : '' ?>>
                  <?php echo $label ?>
                </option>
              <?php endif ?>
            <?php endforeach ?>
          </select>
        </div>

        <div class="filter-group">
          <label><?php echo __('Recognition_status') ?></label>
          <select name="recognition_status" class="input-medium">
            <option value=""><?php echo __('All') ?></option>
            <?php foreach ($recognitionStatuses as $key => $label): ?>
              <?php if ($key): ?>
                <option value="<?php echo $key ?>" <?php echo ($filters['recognition_status'] ?? '') === $key ? 'selected' : '' ?>>
                  <?php echo $label ?>
                </option>
              <?php endif ?>
            <?php endforeach ?>
          </select>
        </div>

        <div class="filter-group">
          <label><?php echo __('Measurement_basis') ?></label>
          <select name="measurement_basis" class="input-medium">
            <option value=""><?php echo __('All') ?></option>
            <?php foreach ($measurementBases as $key => $label): ?>
              <?php if ($key): ?>
                <option value="<?php echo $key ?>" <?php echo ($filters['measurement_basis'] ?? '') === $key ? 'selected' : '' ?>>
                  <?php echo $label ?>
                </option>
              <?php endif ?>
            <?php endforeach ?>
          </select>
        </div>

        <div class="filter-group">
          <label><?php echo __('Date_from') ?></label>
          <input type="date" name="date_from" class="input-small" value="<?php echo $filters['date_from'] ?? '' ?>">
        </div>

        <div class="filter-group">
          <label><?php echo __('Date_to') ?></label>
          <input type="date" name="date_to" class="input-small" value="<?php echo $filters['date_to'] ?? '' ?>">
        </div>
      </div>

      <div class="filter-row">
        <button type="submit" class="btn btn-apply"><?php echo __('Apply Filters') ?></button>
        <a href="<?php echo url_for('grapReport/assetRegister') ?>" class="btn btn-clear"><?php echo __('Clear Filters') ?></a>
        <a href="<?php echo url_for('grapReport/assetRegister') ?>?format=csv<?php echo $filters ? '&'.http_build_query($filters) : '' ?>" class="btn btn-export">
          <?php echo __('Export CSV') ?>
        </a>
      </div>
    </form>
  </div>

  <!-- Results -->
  <h3 class="results-header"><?php echo __('Results (%1% items)', ['%1%' => $totals['count']]) ?></h3>

  <?php if (empty($assets)): ?>
    <p class="text-muted"><?php echo __('No GRAP data found matching your criteria.') ?></p>
  <?php else: ?>
    <table class="table table-bordered table-striped table-condensed sticky-enabled">
      <thead>
        <tr>
          <th><?php echo __('Reference') ?></th>
          <th><?php echo __('Title') ?></th>
          <th><?php echo __('Class') ?></th>
          <th><?php echo __('GL Code') ?></th>
          <th><?php echo __('Status') ?></th>
          <th><?php echo __('Measurement') ?></th>
          <th class="text-right"><?php echo __('Carrying Amt') ?></th>
          <th><?php echo __('Last Valuation') ?></th>
          <th><?php echo __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($assets as $asset): ?>
          <tr>
            <td>
              <?php if ($asset['slug']): ?>
                <a href="<?php echo url_for(['slug' => $asset['slug'], 'module' => 'informationobject']) ?>">
                  <?php echo esc_entities($asset['reference_code'] ?: '-') ?>
                </a>
              <?php else: ?>
                <?php echo esc_entities($asset['reference_code'] ?: '-') ?>
              <?php endif ?>
            </td>
            <td><?php echo esc_entities($asset['title'] ?: __('Untitled')) ?></td>
            <td><?php echo esc_entities($assetClasses[$asset['asset_class']] ?? $asset['asset_class'] ?? '-') ?></td>
            <td><?php echo esc_entities($asset['gl_account_code'] ?? '-') ?></td>
            <td>
              <?php
                $statusClass = '';
                if ('recognized' === $asset['recognition_status']) {
                    $statusClass = 'label-success';
                } elseif ('not_recognized' === $asset['recognition_status']) {
                    $statusClass = 'label-warning';
                } elseif ('pending' === $asset['recognition_status']) {
                    $statusClass = 'label-info';
                }
              ?>
              <span class="label <?php echo $statusClass ?>">
                <?php echo esc_entities($recognitionStatuses[$asset['recognition_status']] ?? $asset['recognition_status'] ?? '-') ?>
              </span>
            </td>
            <td><?php echo esc_entities($measurementBases[$asset['measurement_basis']] ?? $asset['measurement_basis'] ?? '-') ?></td>
            <td class="text-right">
              <?php echo $asset['current_carrying_amount'] ? 'R '.number_format($asset['current_carrying_amount'], 2) : '-' ?>
            </td>
            <td><?php echo $asset['last_valuation_date'] ?: '-' ?></td>
            <td>
              <?php if ($asset['slug']): ?>
                <a href="<?php echo url_for(['slug' => $asset['slug'], 'module' => 'grap', 'action' => 'index']) ?>" 
                   class="btn btn-xs btn-default" title="<?php echo __('View') ?>">
                  <i class="fa fa-eye"></i>
                </a>
                <a href="<?php echo url_for(['slug' => $asset['slug'], 'module' => 'grap', 'action' => 'edit']) ?>" 
                   class="btn btn-xs btn-primary" title="<?php echo __('Edit') ?>">
                  <i class="fa fa-pencil"></i>
                </a>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php endif ?>

  <!-- Related Reports --> 
  <div class="related-reports">
    <h3><?php echo __('Related Reports111') ?></h3>
    <ul>
      <li><a href="<?php echo url_for('grapReport/index') ?>"><?php echo __('GRAP Dashboard') ?></a></li>
      <li><a href="<?php echo url_for('grapReport/compliance') ?>"><?php echo __('GRAP Compliance Check') ?></a></li>
      <li><a href="<?php echo url_for('grapReport/disclosure') ?>"><?php echo __('GRAP 103 Heritage Assets Disclosure') ?></a></li>
      <li><a href="<?php echo url_for('grapReport/valuationSchedule') ?>"><?php echo __('Valuation Schedule') ?></a></li>
      <li><a href="<?php echo url_for('grapReport/insuranceExpiry') ?>"><?php echo __('Insurance Expiry Report') ?></a></li>
    </ul>
  </div>

</div>

<?php end_slot() ?>
