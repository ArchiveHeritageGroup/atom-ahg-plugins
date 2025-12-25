<?php
/**
 * My Access Requests Template.
 */
?>

<h1><i class="fas fa-list"></i> <?php echo __('My Access Requests') ?></h1>

<?php if ($clearance): ?>
<div class="alert alert-info">
  <strong><?php echo __('Your Clearance:') ?></strong>
  <span class="badge" style="background-color: <?php echo $clearance->color ?>">
    <?php echo esc_entities($clearance->name) ?>
  </span>
  <?php if ($clearance->expiry_date): ?>
    | <?php echo __('Expires:') ?> <?php echo $clearance->expiry_date ?>
    <?php if ('pending' !== $clearance->renewal_status): ?>
      <form action="/security/renewal/request" method="post" style="display:inline">
        <button type="submit" class="btn btn-sm btn-warning ms-2">
          <?php echo __('Request Renewal') ?>
        </button>
      </form>
    <?php else: ?>
      <span class="badge bg-warning ms-2"><?php echo __('Renewal Pending') ?></span>
    <?php endif ?>
  <?php endif ?>
</div>
<?php else: ?>
<div class="alert alert-warning">
  <?php echo __('You do not have a security clearance. Contact your administrator.') ?>
</div>
<?php endif ?>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><?php echo __('Request History') ?></h5>
  </div>
  <div class="card-body">
    <?php if (empty($requests)): ?>
    <p class="text-muted text-center"><?php echo __('No access requests found.') ?></p>
    <?php else: ?>
    <table class="table table-striped">
      <thead>
        <tr>
          <th><?php echo __('Date') ?></th>
          <th><?php echo __('Object') ?></th>
          <th><?php echo __('Type') ?></th>
          <th><?php echo __('Status') ?></th>
          <th><?php echo __('Reviewed By') ?></th>
          <th><?php echo __('Access Until') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $req): ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($req->created_at)) ?></td>
          <td><?php echo esc_entities($req->object_title ?? $req->classification_name ?? '-') ?></td>
          <td><?php echo ucfirst(str_replace('_', ' ', $req->request_type)) ?></td>
          <td>
            <?php
            $statusClass = [
                'pending' => 'bg-warning',
                'approved' => 'bg-success',
                'denied' => 'bg-danger',
                'expired' => 'bg-secondary',
                'cancelled' => 'bg-secondary',
            ];
            ?>
            <span class="badge <?php echo $statusClass[$req->status] ?? 'bg-secondary' ?>">
              <?php echo ucfirst($req->status) ?>
            </span>
          </td>
          <td><?php echo esc_entities($req->reviewed_by_name ?? '-') ?></td>
          <td>
            <?php if ($req->access_granted_until): ?>
              <?php 
              $until = strtotime($req->access_granted_until);
              $isExpired = $until < time();
              ?>
              <span class="<?php echo $isExpired ? 'text-muted' : '' ?>">
                <?php echo date('Y-m-d H:i', $until) ?>
                <?php if ($isExpired): ?>(<?php echo __('expired') ?>)<?php endif ?>
              </span>
            <?php else: ?>
              -
            <?php endif ?>
          </td>
        </tr>
        <?php if ($req->review_notes): ?>
        <tr>
          <td colspan="6" class="bg-light">
            <small><strong><?php echo __('Notes:') ?></strong> <?php echo esc_entities($req->review_notes) ?></small>
          </td>
        </tr>
        <?php endif ?>
        <?php endforeach ?>
      </tbody>
    </table>
    <?php endif ?>
  </div>
</div>
