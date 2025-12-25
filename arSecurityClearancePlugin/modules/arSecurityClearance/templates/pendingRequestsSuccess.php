<?php
/**
 * Pending Access Requests Template (Admin).
 */
?>

<h1><i class="fas fa-clock"></i> <?php echo __('Pending Access Requests') ?></h1>

<div class="mb-3">
  <a href="/security" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left"></i> <?php echo __('Back to Dashboard') ?>
  </a>
</div>

<?php if (empty($requests)): ?>
<div class="alert alert-success">
  <i class="fas fa-check-circle"></i>
  <?php echo __('No pending access requests.') ?>
</div>
<?php else: ?>

<div class="card">
  <div class="card-body">
    <table class="table table-striped">
      <thead>
        <tr>
          <th><?php echo __('ID') ?></th>
          <th><?php echo __('User') ?></th>
          <th><?php echo __('Resource') ?></th>
          <th><?php echo __('Type') ?></th>
          <th><?php echo __('Priority') ?></th>
          <th><?php echo __('Requested') ?></th>
          <th><?php echo __('Duration') ?></th>
          <th><?php echo __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $req): ?>
        <tr class="<?php echo 'immediate' === $req->priority ? 'table-danger' : ('urgent' === $req->priority ? 'table-warning' : '') ?>">
          <td>#<?php echo $req->id ?></td>
          <td>
            <strong><?php echo esc_entities($req->username) ?></strong><br>
            <small class="text-muted"><?php echo esc_entities($req->email) ?></small>
          </td>
          <td>
            <?php if ($req->object_title): ?>
              <?php echo esc_entities($req->object_title) ?>
            <?php elseif ($req->classification_name): ?>
              <span class="badge bg-secondary"><?php echo __('Clearance Upgrade:') ?></span>
              <?php echo esc_entities($req->classification_name) ?>
            <?php elseif ($req->compartment_name): ?>
              <span class="badge bg-info"><?php echo __('Compartment:') ?></span>
              <?php echo esc_entities($req->compartment_name) ?>
            <?php else: ?>
              -
            <?php endif ?>
          </td>
          <td><?php echo ucfirst(str_replace('_', ' ', $req->request_type)) ?></td>
          <td>
            <span class="badge bg-<?php echo 'immediate' === $req->priority ? 'danger' : ('urgent' === $req->priority ? 'warning' : 'secondary') ?>">
              <?php echo ucfirst($req->priority) ?>
            </span>
          </td>
          <td>
            <?php echo date('Y-m-d H:i', strtotime($req->created_at)) ?><br>
            <small class="text-muted">
              <?php 
              $hours = round((time() - strtotime($req->created_at)) / 3600);
              echo sprintf(__('%d hours ago'), $hours);
              ?>
            </small>
          </td>
          <td><?php echo $req->duration_hours ?> <?php echo __('hours') ?></td>
          <td>
            <a href="/security/request/<?php echo $req->id ?>/review" class="btn btn-sm btn-primary">
              <i class="fas fa-search"></i> <?php echo __('Review') ?>
            </a>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif ?>
