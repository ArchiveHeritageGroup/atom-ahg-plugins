<?php
/**
 * User Clearances List Template.
 */
?>

<h1><i class="fas fa-users"></i> <?php echo __('User Security Clearances') ?></h1>

<div class="row mb-4">
  <div class="col-md-8">
    <p class="text-muted"><?php echo __('Manage security clearances for all users.') ?></p>
  </div>
  <div class="col-md-4 text-end">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#grantModal">
      <i class="fas fa-plus"></i> <?php echo __('Grant New Clearance') ?>
    </button>
  </div>
</div>

<!-- Clearances Table -->
<div class="card">
  <div class="card-body">
    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th><?php echo __('User') ?></th>
          <th><?php echo __('Clearance Level') ?></th>
          <th><?php echo __('Granted') ?></th>
          <th><?php echo __('Expires') ?></th>
          <th><?php echo __('2FA') ?></th>
          <th><?php echo __('Status') ?></th>
          <th><?php echo __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clearances as $c): ?>
        <tr>
          <td>
            <strong><?php echo esc_entities($c->username) ?></strong><br>
            <small class="text-muted"><?php echo esc_entities($c->email) ?></small>
          </td>
          <td>
            <span class="badge" style="background-color: <?php echo $c->color ?>">
              <?php echo esc_entities($c->clearance_name) ?>
            </span>
            <br><small>Level <?php echo $c->level ?></small>
          </td>
          <td><?php echo $c->granted_date ?></td>
          <td>
            <?php if ($c->expiry_date): ?>
              <?php 
              $daysLeft = (strtotime($c->expiry_date) - time()) / 86400;
              $class = $daysLeft <= 7 ? 'text-danger' : ($daysLeft <= 30 ? 'text-warning' : '');
              ?>
              <span class="<?php echo $class ?>"><?php echo $c->expiry_date ?></span>
            <?php else: ?>
              <span class="text-muted"><?php echo __('No expiry') ?></span>
            <?php endif ?>
          </td>
          <td>
            <?php if ($c->two_factor_verified): ?>
              <span class="badge bg-success"><i class="fas fa-check"></i></span>
            <?php else: ?>
              <span class="badge bg-secondary"><i class="fas fa-times"></i></span>
            <?php endif ?>
          </td>
          <td>
            <?php if ('pending' === $c->renewal_status): ?>
              <span class="badge bg-warning"><?php echo __('Renewal Pending') ?></span>
            <?php else: ?>
              <span class="badge bg-success"><?php echo __('Active') ?></span>
            <?php endif ?>
          </td>
          <td>
            <a href="<?php echo url_for('@security_clearance_view?id=' . $c->user_id); ?>" class="btn btn-sm btn-outline-primary">
              <i class="fas fa-edit"></i>
            </a>
            <button class="btn btn-sm btn-outline-danger" onclick="revokeClearance(<?php echo $c->user_id ?>)">
              <i class="fas fa-ban"></i>
            </button>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Grant Modal -->
<div class="modal fade" id="grantModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="<?php echo url_for('@security_clearance_grant'); ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo __('Grant Security Clearance') ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('User') ?> *</label>
                <select name="user_id" class="form-select" required>
                  <option value=""><?php echo __('-- Select User --') ?></option>
                  <?php foreach ($users as $user): ?>
                  <option value="<?php echo $user->id ?>"><?php echo esc_entities($user->username) ?> (<?php echo esc_entities($user->email) ?>)</option>
                  <?php endforeach ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Clearance Level') ?> *</label>
                <select name="classification_id" class="form-select" required>
                  <option value=""><?php echo __('-- Select Level --') ?></option>
                  <?php foreach ($classifications as $level): ?>
                  <option value="<?php echo $level->id ?>"><?php echo esc_entities($level->name) ?></option>
                  <?php endforeach ?>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Granted Date') ?></label>
                <input type="date" name="granted_date" class="form-control" value="<?php echo date('Y-m-d') ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Expiry Date') ?></label>
                <input type="date" name="expiry_date" class="form-control">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Vetting Reference') ?></label>
                <input type="text" name="vetting_reference" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Vetting Date') ?></label>
                <input type="date" name="vetting_date" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Vetting Authority') ?></label>
                <input type="text" name="vetting_authority" class="form-control">
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Notes') ?></label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
          <button type="submit" class="btn btn-primary"><?php echo __('Grant Clearance') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function revokeClearance(userId) {
  if (confirm('<?php echo __('Are you sure you want to revoke this clearance?') ?>')) {
    var form = document.createElement('form');
    form.method = 'post';
    form.action = '<?php echo url_for(\'@security_clearance_revoke?id=\'); ?>' + userId;
    
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'user_id';
    input.value = userId;
    form.appendChild(input);
    
    document.body.appendChild(form);
    form.submit();
  }
}
</script>
