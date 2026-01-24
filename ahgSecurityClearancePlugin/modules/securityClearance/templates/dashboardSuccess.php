<?php
/**
 * Security Clearance Dashboard Template.
 */
?>

<h1><i class="fas fa-shield-alt"></i> <?php echo __('Security Dashboard') ?></h1>

<!-- Statistics Cards -->
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card bg-primary text-white">
      <div class="card-body">
        <h5 class="card-title"><?php echo __('Pending Requests') ?></h5>
        <h2><?php echo $statistics['pending_requests'] ?? 0 ?></h2>
        <small><?php echo __('Awaiting review') ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-warning text-dark">
      <div class="card-body">
        <h5 class="card-title"><?php echo __('Expiring Clearances') ?></h5>
        <h2><?php echo $statistics['expiring_clearances'] ?? 0 ?></h2>
        <small><?php echo __('Within 30 days') ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-danger text-white">
      <div class="card-body">
        <h5 class="card-title"><?php echo __('Recent Denials') ?></h5>
        <h2><?php echo $statistics['recent_denials'] ?? 0 ?></h2>
        <small><?php echo __('Last 7 days') ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-info text-white">
      <div class="card-body">
        <h5 class="card-title"><?php echo __('Reviews Due') ?></h5>
        <h2><?php echo $statistics['reviews_due'] ?? 0 ?></h2>
        <small><?php echo __('Declassifications') ?></small>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Clearances by Level -->
  <div class="col-md-6 mb-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-users"></i> <?php echo __('User Clearances by Level') ?></h5>
      </div>
      <div class="card-body">
        <table class="table table-sm">
          <thead>
            <tr>
              <th><?php echo __('Level') ?></th>
              <th><?php echo __('Users') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($statistics['clearances_by_level'] ?? [] as $level): ?>
            <tr>
              <td>
                <span class="badge" style="background-color: <?php echo $level->color ?>">
                  <?php echo esc_entities($level->name) ?>
                </span>
              </td>
              <td><?php echo $level->count ?></td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
        <a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'index']) ?>" class="btn btn-sm btn-outline-primary">
          <?php echo __('Manage Clearances') ?>
        </a>
      </div>
    </div>
  </div>

  <!-- Objects by Classification -->
  <div class="col-md-6 mb-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-folder-open"></i> <?php echo __('Objects by Classification') ?></h5>
      </div>
      <div class="card-body">
        <table class="table table-sm">
          <thead>
            <tr>
              <th><?php echo __('Classification') ?></th>
              <th><?php echo __('Objects') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($statistics['objects_by_level'] ?? [] as $level): ?>
            <tr>
              <td>
                <span class="badge" style="background-color: <?php echo $level->color ?>">
                  <?php echo esc_entities($level->name) ?>
                </span>
              </td>
              <td><?php echo $level->count ?></td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Pending Requests -->
<?php if (!empty($pendingRequests)): ?>
<div class="card mb-4">
  <div class="card-header bg-warning">
    <h5 class="mb-0"><i class="fas fa-clock"></i> <?php echo __('Pending Access Requests') ?></h5>
  </div>
  <div class="card-body">
    <table class="table table-striped">
      <thead>
        <tr>
          <th><?php echo __('User') ?></th>
          <th><?php echo __('Object') ?></th>
          <th><?php echo __('Type') ?></th>
          <th><?php echo __('Priority') ?></th>
          <th><?php echo __('Requested') ?></th>
          <th><?php echo __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pendingRequests as $req): ?>
        <tr class="<?php echo 'urgent' === $req->priority || 'immediate' === $req->priority ? 'table-warning' : '' ?>">
          <td><?php echo esc_entities($req->username) ?></td>
          <td><?php echo esc_entities($req->object_title ?? '-') ?></td>
          <td><?php echo ucfirst(str_replace('_', ' ', $req->request_type)) ?></td>
          <td>
            <span class="badge bg-<?php echo 'immediate' === $req->priority ? 'danger' : ('urgent' === $req->priority ? 'warning' : 'secondary') ?>">
              <?php echo ucfirst($req->priority) ?>
            </span>
          </td>
          <td><?php echo date('Y-m-d H:i', strtotime($req->created_at)) ?></td>
          <td>
            <a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'reviewRequest', 'id' => $req->request_id]) ?>" class="btn btn-sm btn-primary">
              <?php echo __('Review') ?>
            </a>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif ?>

<!-- Expiring Clearances -->
<?php if (!empty($expiringClearances)): ?>
<div class="card mb-4">
  <div class="card-header bg-warning">
    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> <?php echo __('Expiring Clearances') ?></h5>
  </div>
  <div class="card-body">
    <table class="table table-striped">
      <thead>
        <tr>
          <th><?php echo __('User') ?></th>
          <th><?php echo __('Clearance') ?></th>
          <th><?php echo __('Expires') ?></th>
          <th><?php echo __('Days Left') ?></th>
          <th><?php echo __('Renewal Status') ?></th>
          <th><?php echo __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($expiringClearances as $exp): ?>
        <tr class="<?php echo $exp->days_remaining <= 7 ? 'table-danger' : ($exp->days_remaining <= 14 ? 'table-warning' : '') ?>">
          <td><?php echo esc_entities($exp->username) ?></td>
          <td>
            <span class="badge" style="background-color: <?php echo $exp->color ?? '#666' ?>">
              <?php echo esc_entities($exp->clearance_name) ?>
            </span>
          </td>
          <td><?php echo $exp->expires_at ?></td>
          <td><?php echo $exp->days_remaining ?></td>
          <td>
            <?php if ('pending' === $exp->renewal_status): ?>
              <span class="badge bg-info"><?php echo __('Pending') ?></span>
            <?php elseif ('none' === $exp->renewal_status): ?>
              <span class="badge bg-secondary"><?php echo __('Not Requested') ?></span>
            <?php endif ?>
          </td>
          <td>
            <a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'view', 'id' => $exp->user_id]) ?>" class="btn btn-sm btn-outline-primary">
              <?php echo __('Manage') ?>
            </a>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif ?>

<!-- Due Declassifications -->
<?php if (!empty($dueDeclassifications)): ?>
<div class="card mb-4">
  <div class="card-header bg-info text-white">
    <h5 class="mb-0"><i class="fas fa-unlock"></i> <?php echo __('Due for Declassification') ?></h5>
  </div>
  <div class="card-body">
    <table class="table table-striped">
      <thead>
        <tr>
          <th><?php echo __('Object') ?></th>
          <th><?php echo __('Current') ?></th>
          <th><?php echo __('Downgrade To') ?></th>
          <th><?php echo __('Scheduled Date') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($dueDeclassifications as $dec): ?>
        <tr>
          <td><?php echo esc_entities($dec->title ?? $dec->identifier ?? 'ID: '.$dec->object_id) ?></td>
          <td><?php echo esc_entities($dec->from_classification) ?></td>
          <td><?php echo esc_entities($dec->to_classification ?? 'Public') ?></td>
          <td><?php echo $dec->scheduled_date ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif ?>

<!-- Quick Links -->
<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-link"></i> <?php echo __('Quick Links') ?></h5>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3">
        <a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'index']) ?>" class="btn btn-outline-primary btn-block mb-2 w-100">
          <i class="fas fa-users"></i> <?php echo __('Manage Clearances') ?>
        </a>
      </div>
      <div class="col-md-3">
        <a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'compartments']) ?>" class="btn btn-outline-secondary btn-block mb-2 w-100">
          <i class="fas fa-project-diagram"></i> <?php echo __('Compartments') ?>
        </a>
      </div>
      <div class="col-md-3">
        <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'dashboard']) ?>" class="btn btn-outline-info btn-block mb-2 w-100">
          <i class="fas fa-history"></i> <?php echo __('Audit Log') ?>
        </a>
      </div>
      <div class="col-md-3">
        <a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'report']) ?>" class="btn btn-outline-success btn-block mb-2 w-100">
          <i class="fas fa-chart-bar"></i> <?php echo __('Reports') ?>
        </a>
      </div>
    </div>
  </div>
</div>
