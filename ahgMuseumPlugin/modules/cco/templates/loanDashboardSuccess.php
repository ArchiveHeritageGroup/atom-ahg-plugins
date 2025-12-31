<?php
/**
 * Loan Dashboard Template.
 *
 * Overview of all loans with workflow status, due dates, and quick actions.
 */
?>
<?php use_helper('Javascript') ?>

<h1><?php echo __('Loan Management') ?></h1>

<!-- Dashboard Stats -->
<div class="row stats-row">
  <div class="col-md-2">
    <div class="stat-box">
      <div class="stat-value"><?php echo $stats['total_loans'] ?? 0 ?></div>
      <div class="stat-label"><?php echo __('Total Loans') ?></div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="stat-box stat-info">
      <div class="stat-value"><?php echo $stats['active_loans_out'] ?? 0 ?></div>
      <div class="stat-label"><?php echo __('Active Out') ?></div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="stat-box stat-primary">
      <div class="stat-value"><?php echo $stats['active_loans_in'] ?? 0 ?></div>
      <div class="stat-label"><?php echo __('Active In') ?></div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="stat-box stat-danger">
      <div class="stat-value"><?php echo $stats['overdue'] ?? 0 ?></div>
      <div class="stat-label"><?php echo __('Overdue') ?></div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="stat-box stat-warning">
      <div class="stat-value"><?php echo $stats['due_this_month'] ?? 0 ?></div>
      <div class="stat-label"><?php echo __('Due This Month') ?></div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="stat-box stat-success">
      <div class="stat-value">R<?php echo number_format($stats['total_insurance_value'] ?? 0) ?></div>
      <div class="stat-label"><?php echo __('Total Insured') ?></div>
    </div>
  </div>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
  <a href="<?php echo url_for('museum/loanCreate') ?>?type=out" class="btn btn-primary">
    <i class="fa fa-plus"></i> <?php echo __('New Loan Out') ?>
  </a>
  <a href="<?php echo url_for('museum/loanCreate') ?>?type=in" class="btn btn-info">
    <i class="fa fa-plus"></i> <?php echo __('New Loan In') ?>
  </a>
  <a href="<?php echo url_for('museum/loanLabels') ?>" class="btn btn-default">
    <i class="fa fa-barcode"></i> <?php echo __('Print Labels') ?>
  </a>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs" role="tablist">
  <li class="active">
    <a href="#active" role="tab" data-toggle="tab"><?php echo __('Active Loans') ?></a>
  </li>
  <li>
    <a href="#overdue" role="tab" data-toggle="tab">
      <?php echo __('Overdue') ?> 
      <?php if ($stats['overdue'] > 0): ?>
        <span class="badge badge-danger"><?php echo $stats['overdue'] ?></span>
      <?php endif ?>
    </a>
  </li>
  <li>
    <a href="#due-soon" role="tab" data-toggle="tab"><?php echo __('Due Soon') ?></a>
  </li>
  <li>
    <a href="#completed" role="tab" data-toggle="tab"><?php echo __('Completed') ?></a>
  </li>
</ul>

<div class="tab-content">
  <!-- Active Loans Tab -->
  <div class="tab-pane active" id="active">
    <div class="loan-filters">
      <form class="form-inline">
        <select name="loan_type" class="form-control">
          <option value=""><?php echo __('All Types') ?></option>
          <option value="out"><?php echo __('Loans Out') ?></option>
          <option value="in"><?php echo __('Loans In') ?></option>
        </select>
        <input type="text" name="search" class="form-control" placeholder="<?php echo __('Search...') ?>">
        <button type="submit" class="btn btn-default"><?php echo __('Filter') ?></button>
      </form>
    </div>

    <table class="table table-striped loan-table">
      <thead>
        <tr>
          <th><?php echo __('Loan #') ?></th>
          <th><?php echo __('Type') ?></th>
          <th><?php echo __('Partner') ?></th>
          <th><?php echo __('Purpose') ?></th>
          <th><?php echo __('Objects') ?></th>
          <th><?php echo __('Dates') ?></th>
          <th><?php echo __('Status') ?></th>
          <th><?php echo __('Progress') ?></th>
          <th><?php echo __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($activeLoans as $loan): ?>
          <tr class="<?php echo $loan['is_overdue'] ? 'danger' : '' ?>">
            <td>
              <a href="<?php echo url_for('museum/loanView') ?>?id=<?php echo $loan['id'] ?>">
                <?php echo $loan['loan_number'] ?>
              </a>
            </td>
            <td>
              <span class="label label-<?php echo $loan['loan_type'] === 'out' ? 'info' : 'primary' ?>">
                <?php echo $loan['loan_type'] === 'out' ? __('OUT') : __('IN') ?>
              </span>
            </td>
            <td><?php echo esc_entities($loan['partner_institution']) ?></td>
            <td><?php echo $purposes[$loan['purpose']] ?? $loan['purpose'] ?></td>
            <td class="text-center"><?php echo count($loan['objects'] ?? []) ?></td>
            <td>
              <?php if ($loan['start_date']): ?>
                <?php echo date('d M Y', strtotime($loan['start_date'])) ?>
                -
                <?php echo date('d M Y', strtotime($loan['end_date'])) ?>
              <?php else: ?>
                <em><?php echo __('TBD') ?></em>
              <?php endif ?>
            </td>
            <td>
              <?php $stateInfo = $loan['workflow']['state_info'] ?? [] ?>
              <span class="label label-<?php echo $stateInfo['color'] ?? 'default' ?>">
                <?php echo $stateInfo['label'] ?? $loan['workflow']['current_state'] ?? __('Unknown') ?>
              </span>
            </td>
            <td>
              <div class="progress progress-small">
                <div class="progress-bar progress-bar-success" 
                     style="width: <?php echo $loan['workflow']['progress'] ?? 0 ?>%">
                </div>
              </div>
            </td>
            <td>
              <div class="btn-group">
                <a href="<?php echo url_for('museum/loanView') ?>?id=<?php echo $loan['id'] ?>" 
                   class="btn btn-sm btn-default" title="<?php echo __('View') ?>">
                  <i class="fa fa-eye"></i>
                </a>
                <a href="<?php echo url_for('museum/loanAgreement') ?>?id=<?php echo $loan['id'] ?>" 
                   class="btn btn-sm btn-default" title="<?php echo __('Agreement') ?>">
                  <i class="fa fa-file-text"></i>
                </a>
                <?php if (!empty($loan['workflow']['available_transitions'])): ?>
                  <button class="btn btn-sm btn-success dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-forward"></i>
                  </button>
                  <ul class="dropdown-menu">
                    <?php foreach ($loan['workflow']['available_transitions'] as $trans): ?>
                      <li>
                        <a href="#" class="transition-action" 
                           data-loan="<?php echo $loan['id'] ?>"
                           data-transition="<?php echo $trans['name'] ?>"
                           data-confirm="<?php echo $trans['confirm'] ? 'true' : 'false' ?>"
                           data-message="<?php echo esc_entities($trans['confirm_message'] ?? '') ?>">
                          <i class="fa fa-<?php echo $trans['icon'] ?? 'arrow-right' ?>"></i>
                          <?php echo $trans['label'] ?>
                        </a>
                      </li>
                    <?php endforeach ?>
                  </ul>
                <?php endif ?>
              </div>
            </td>
          </tr>
        <?php endforeach ?>
        <?php if (empty($activeLoans)): ?>
          <tr>
            <td colspan="9" class="text-center text-muted">
              <?php echo __('No active loans found') ?>
            </td>
          </tr>
        <?php endif ?>
      </tbody>
    </table>
  </div>

  <!-- Overdue Tab -->
  <div class="tab-pane" id="overdue">
    <?php if (!empty($overdueLoans)): ?>
      <table class="table table-striped">
        <thead>
          <tr>
            <th><?php echo __('Loan #') ?></th>
            <th><?php echo __('Partner') ?></th>
            <th><?php echo __('Due Date') ?></th>
            <th><?php echo __('Days Overdue') ?></th>
            <th><?php echo __('Actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($overdueLoans as $loan): ?>
            <tr class="danger">
              <td>
                <a href="<?php echo url_for('museum/loanView') ?>?id=<?php echo $loan['id'] ?>">
                  <?php echo $loan['loan_number'] ?>
                </a>
              </td>
              <td><?php echo esc_entities($loan['partner_institution']) ?></td>
              <td><?php echo date('d M Y', strtotime($loan['end_date'])) ?></td>
              <td>
                <span class="text-danger">
                  <?php 
                    $diff = (new DateTime())->diff(new DateTime($loan['end_date']));
                    echo $diff->days . ' ' . __('days');
                  ?>
                </span>
              </td>
              <td>
                <a href="<?php echo url_for('museum/loanExtend') ?>?id=<?php echo $loan['id'] ?>" 
                   class="btn btn-sm btn-warning">
                  <i class="fa fa-calendar-plus-o"></i> <?php echo __('Extend') ?>
                </a>
                <a href="<?php echo url_for('museum/loanReturn') ?>?id=<?php echo $loan['id'] ?>" 
                   class="btn btn-sm btn-success">
                  <i class="fa fa-check"></i> <?php echo __('Record Return') ?>
                </a>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-success text-center">
        <i class="fa fa-check-circle"></i> <?php echo __('No overdue loans!') ?>
      </p>
    <?php endif ?>
  </div>

  <!-- Due Soon Tab -->
  <div class="tab-pane" id="due-soon">
    <?php if (!empty($dueSoonLoans)): ?>
      <table class="table table-striped">
        <thead>
          <tr>
            <th><?php echo __('Loan #') ?></th>
            <th><?php echo __('Partner') ?></th>
            <th><?php echo __('Due Date') ?></th>
            <th><?php echo __('Days Remaining') ?></th>
            <th><?php echo __('Actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($dueSoonLoans as $loan): ?>
            <tr class="<?php echo $loan['days_remaining'] <= 7 ? 'warning' : '' ?>">
              <td>
                <a href="<?php echo url_for('museum/loanView') ?>?id=<?php echo $loan['id'] ?>">
                  <?php echo $loan['loan_number'] ?>
                </a>
              </td>
              <td><?php echo esc_entities($loan['partner_institution']) ?></td>
              <td><?php echo date('d M Y', strtotime($loan['end_date'])) ?></td>
              <td><?php echo $loan['days_remaining'] ?> <?php echo __('days') ?></td>
              <td>
                <a href="<?php echo url_for('museum/loanView') ?>?id=<?php echo $loan['id'] ?>" 
                   class="btn btn-sm btn-default">
                  <i class="fa fa-eye"></i> <?php echo __('View') ?>
                </a>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-muted text-center">
        <?php echo __('No loans due in the next 30 days') ?>
      </p>
    <?php endif ?>
  </div>

  <!-- Completed Tab -->
  <div class="tab-pane" id="completed">
    <p class="text-center">
      <a href="<?php echo url_for('museum/loanSearch') ?>?status=completed" class="btn btn-default">
        <?php echo __('View All Completed Loans') ?>
      </a>
    </p>
  </div>
</div>

<style>
.stats-row { margin-bottom: 20px; }
.stat-box {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 15px;
  text-align: center;
  margin-bottom: 10px;
}
.stat-box.stat-info { border-left: 4px solid #2196f3; }
.stat-box.stat-primary { border-left: 4px solid #3f51b5; }
.stat-box.stat-danger { border-left: 4px solid #f44336; }
.stat-box.stat-warning { border-left: 4px solid #ff9800; }
.stat-box.stat-success { border-left: 4px solid #4caf50; }
.stat-value { font-size: 24px; font-weight: bold; }
.stat-label { font-size: 12px; color: #666; }
.action-buttons { margin-bottom: 20px; }
.action-buttons .btn { margin-right: 10px; }
.loan-filters { margin: 15px 0; }
.loan-filters .form-control { margin-right: 10px; }
.progress-small { height: 8px; margin-bottom: 0; }
.loan-table .progress { width: 60px; }
</style>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
$(document).ready(function() {
  // Handle workflow transitions
  $('.transition-action').on('click', function(e) {
    e.preventDefault();
    
    var $this = $(this);
    var loanId = $this.data('loan');
    var transition = $this.data('transition');
    var needsConfirm = $this.data('confirm') === true || $this.data('confirm') === 'true';
    var message = $this.data('message') || 'Proceed with this action?';
    
    if (needsConfirm && !confirm(message)) {
      return;
    }
    
    $.post('/museum/loan/transition', {
      loan_id: loanId,
      transition: transition
    }, function(response) {
      if (response.success) {
        location.reload();
      } else {
        alert(response.error || 'An error occurred');
      }
    });
  });
});
</script>
