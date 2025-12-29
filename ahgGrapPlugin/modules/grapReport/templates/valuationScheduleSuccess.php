<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1><i class="fa fa-clock-o"></i> <?php echo __('Valuation Schedule') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<div class="grap-report">

  <div class="btn-group" style="margin-bottom: 20px;">
    <a href="<?php echo url_for('grapReport/index') ?>" class="btn btn-default">
      <i class="fa fa-arrow-left"></i> <?php echo __('Back to Dashboard') ?>
    </a>
  </div>

  <div class="alert alert-info">
    <i class="fa fa-info-circle"></i>
    <?php echo __('This report shows heritage assets based on their valuation status. GRAP 103 recommends regular revaluation of heritage assets to ensure carrying amounts reflect fair value.') ?>
  </div>

  <?php if (!empty($items)): ?>
    <table class="table table-bordered table-striped sticky-enabled">
      <thead>
        <tr>
          <th><?php echo __('Reference') ?></th>
          <th><?php echo __('Title') ?></th>
          <th><?php echo __('Status') ?></th>
          <th><?php echo __('Last Valuation') ?></th>
          <th class="text-right"><?php echo __('Last Amount') ?></th>
          <th class="text-right"><?php echo __('Current Carrying') ?></th>
          <th><?php echo __('Frequency') ?></th>
          <th><?php echo __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <?php
            $statusClass = '';
            $status = $item['valuation_status'] ?? '';
            if (in_array($status, ['Overdue', 'Never valued'])) {
                $statusClass = 'warning';
            } elseif ($status === 'Due soon') {
                $statusClass = 'info';
            }
          ?>
          <tr class="<?php echo $statusClass ?>">
            <td>
              <a href="<?php echo url_for(['slug' => $item['slug'], 'module' => 'informationobject']) ?>">
                <?php echo esc_entities($item['reference_code'] ?: '-') ?>
              </a>
            </td>
            <td><?php echo esc_entities($item['title'] ?: __('Untitled')) ?></td>
            <td>
              <span class="label <?php echo $statusClass ? 'label-'.$statusClass : '' ?>">
                <?php echo esc_entities($status ?: '-') ?>
              </span>
            </td>
            <td>
              <?php echo $item['last_valuation_date'] ? Qubit::renderDate($item['last_valuation_date']) : __('Never') ?>
            </td>
            <td class="text-right">
              <?php echo $item['last_valuation_amount'] ? 'R '.number_format($item['last_valuation_amount'], 2) : '-' ?>
            </td>
            <td class="text-right">
              <?php echo $item['current_carrying_amount'] ? 'R '.number_format($item['current_carrying_amount'], 2) : '-' ?>
            </td>
            <td>
              <?php 
                $frequencies = GrapHeritageAssetForm::getRevaluationFrequencyChoices();
                echo esc_entities($frequencies[$item['revaluation_frequency'] ?? ''] ?? '-');
              ?>
            </td>
            <td>
              <a href="<?php echo url_for(['slug' => $item['slug'], 'module' => 'grap', 'action' => 'edit']) ?>" 
                 class="btn btn-xs btn-primary" title="<?php echo __('Edit GRAP') ?>">
                <i class="fa fa-pencil"></i> <?php echo __('Edit') ?>
              </a>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-success">
      <i class="fa fa-check-circle"></i>
      <?php echo __('No valuation issues found. All assets are up to date.') ?>
    </div>
  <?php endif ?>

</div>

<?php end_slot() ?>
