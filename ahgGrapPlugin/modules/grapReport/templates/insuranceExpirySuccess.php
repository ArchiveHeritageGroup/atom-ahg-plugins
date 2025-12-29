<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1><i class="fa fa-shield"></i> <?php echo __('Insurance Expiry Report') ?></h1>
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
    <?php echo __('This report tracks insurance coverage for heritage assets. Ensure all valuable assets have current insurance policies.') ?>
  </div>

  <?php if (!empty($items)): ?>
    <table class="table table-bordered table-striped sticky-enabled">
      <thead>
        <tr>
          <th><?php echo __('Reference') ?></th>
          <th><?php echo __('Title') ?></th>
          <th><?php echo __('Status') ?></th>
          <th><?php echo __('Expiry Date') ?></th>
          <th class="text-right"><?php echo __('Insurance Value') ?></th>
          <th class="text-right"><?php echo __('Carrying Amount') ?></th>
          <th><?php echo __('Policy #') ?></th>
          <th><?php echo __('Provider') ?></th>
          <th><?php echo __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <?php
            $statusClass = '';
            $status = $item['insurance_status'] ?? '';
            if (in_array($status, ['Expired', 'No insurance'])) {
                $statusClass = 'error';
            } elseif ($status === 'Expiring soon') {
                $statusClass = 'warning';
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
              <?php
                $labelClass = '';
                if (in_array($status, ['Expired', 'No insurance'])) {
                    $labelClass = 'label-important';
                } elseif ($status === 'Expiring soon') {
                    $labelClass = 'label-warning';
                } elseif ($status === 'Current') {
                    $labelClass = 'label-success';
                }
              ?>
              <span class="label <?php echo $labelClass ?>">
                <?php echo esc_entities($status ?: '-') ?>
              </span>
            </td>
            <td>
              <?php echo $item['insurance_expiry_date'] ? Qubit::renderDate($item['insurance_expiry_date']) : __('Not set') ?>
            </td>
            <td class="text-right">
              <?php echo $item['insurance_value'] ? 'R '.number_format($item['insurance_value'], 2) : '-' ?>
            </td>
            <td class="text-right">
              <?php echo $item['current_carrying_amount'] ? 'R '.number_format($item['current_carrying_amount'], 2) : '-' ?>
            </td>
            <td><?php echo esc_entities($item['insurance_policy_number'] ?? '-') ?></td>
            <td><?php echo esc_entities($item['insurance_provider'] ?? '-') ?></td>
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
      <?php echo __('No insurance issues found. All assets have current insurance coverage.') ?>
    </div>
  <?php endif ?>

</div>

<?php end_slot() ?>
