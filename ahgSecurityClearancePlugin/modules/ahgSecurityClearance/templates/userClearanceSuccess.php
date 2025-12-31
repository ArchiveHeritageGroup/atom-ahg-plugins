<?php
/**
 * User Clearance Detail Template.
 */
?>

<h1><i class="fas fa-user-shield"></i> <?php echo __('User Clearance') ?>: <?php echo esc_entities($targetUser->username) ?></h1>

<div class="mb-3">
  <a href="<?php echo url_for('@security_clearances'); ?>" class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-arrow-left"></i> <?php echo __('Back to Clearances') ?>
  </a>
</div>

<div class="row">
  <div class="col-md-8">
    <!-- Current Clearance -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><?php echo __('Current Clearance') ?></h5>
      </div>
      <div class="card-body">
        <?php if ($clearance): ?>
        <div class="row">
          <div class="col-md-6">
            <p>
              <strong><?php echo __('Level:') ?></strong><br>
              <span class="badge fs-5" style="background-color: <?php echo $clearance->color ?>">
                <?php echo esc_entities($clearance->name) ?>
              </span>
            </p>
            <p>
              <strong><?php echo __('Granted:') ?></strong><br>
              <?php echo $clearance->granted_date ?>
            </p>
            <p>
              <strong><?php echo __('Expires:') ?></strong><br>
              <?php if ($clearance->expiry_date): ?>
                <?php 
                $daysLeft = (strtotime($clearance->expiry_date) - time()) / 86400;
                $class = $daysLeft <= 7 ? 'text-danger' : ($daysLeft <= 30 ? 'text-warning' : 'text-success');
                ?>
                <span class="<?php echo $class ?>"><?php echo $clearance->expiry_date ?></span>
                (<?php echo round($daysLeft) ?> days)
              <?php else: ?>
                <span class="text-muted"><?php echo __('No expiry') ?></span>
              <?php endif ?>
            </p>
          </div>
          <div class="col-md-6">
            <p>
              <strong><?php echo __('Vetting Reference:') ?></strong><br>
              <?php echo esc_entities($clearance->vetting_reference ?? '-') ?>
            </p>
            <p>
              <strong><?php echo __('Vetting Authority:') ?></strong><br>
              <?php echo esc_entities($clearance->vetting_authority ?? '-') ?>
            </p>
            <p>
              <strong><?php echo __('2FA Verified:') ?></strong><br>
              <?php if ($clearance->two_factor_verified): ?>
                <span class="badge bg-success"><?php echo __('Yes') ?></span>
                <small class="text-muted">(<?php echo $clearance->two_factor_verified_at ?>)</small>
              <?php else: ?>
                <span class="badge bg-warning"><?php echo __('No') ?></span>
              <?php endif ?>
            </p>
          </div>
        </div>

        <?php if ('pending' === $clearance->renewal_status): ?>
        <div class="alert alert-warning">
          <strong><?php echo __('Renewal Requested:') ?></strong> <?php echo $clearance->renewal_requested_date ?>
          <form action="/security/renewal/approve" method="post" class="mt-2">
            <input type="hidden" name="user_id" value="<?php echo $targetUser->id ?>">
            <div class="row">
              <div class="col-md-4">
                <input type="date" name="expiry_date" class="form-control" 
                       value="<?php echo date('Y-m-d', strtotime('+1 year')) ?>">
              </div>
              <div class="col-md-4">
                <button type="submit" class="btn btn-success"><?php echo __('Approve Renewal') ?></button>
              </div>
            </div>
          </form>
        </div>
        <?php endif ?>

        <?php else: ?>
        <p class="text-muted"><?php echo __('No active clearance.') ?></p>
        <?php endif ?>
      </div>
    </div>

    <!-- Grant/Update Clearance -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><?php echo $clearance ? __('Update Clearance') : __('Grant Clearance') ?></h5>
      </div>
      <div class="card-body">
        <form action="<?php echo url_for('@security_clearance_grant'); ?>" method="post">
          <input type="hidden" name="user_id" value="<?php echo $targetUser->id ?>">
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Clearance Level') ?> *</label>
                <select name="classification_id" class="form-select" required>
                  <?php foreach ($classifications as $level): ?>
                  <option value="<?php echo $level->id ?>" 
                          <?php echo ($clearance && $clearance->classification_id == $level->id) ? 'selected' : '' ?>>
                    <?php echo esc_entities($level->name) ?>
                  </option>
                  <?php endforeach ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Expiry Date') ?></label>
                <input type="date" name="expiry_date" class="form-control" 
                       value="<?php echo $clearance->expiry_date ?? '' ?>">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Vetting Reference') ?></label>
                <input type="text" name="vetting_reference" class="form-control"
                       value="<?php echo esc_entities($clearance->vetting_reference ?? '') ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Vetting Date') ?></label>
                <input type="date" name="vetting_date" class="form-control"
                       value="<?php echo $clearance->vetting_date ?? '' ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Vetting Authority') ?></label>
                <input type="text" name="vetting_authority" class="form-control"
                       value="<?php echo esc_entities($clearance->vetting_authority ?? '') ?>">
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Notes') ?></label>
            <textarea name="notes" class="form-control" rows="2"><?php echo esc_entities($clearance->notes ?? '') ?></textarea>
          </div>

          <button type="submit" class="btn btn-primary"><?php echo __('Save Clearance') ?></button>
          
          <?php if ($clearance): ?>
          <button type="button" class="btn btn-danger" onclick="revokeClearance()">
            <?php echo __('Revoke Clearance') ?>
          </button>
          <?php endif ?>
        </form>
      </div>
    </div>

    <!-- Compartment Access -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><?php echo __('Compartment Access') ?></h5>
      </div>
      <div class="card-body">
        <?php if (empty($compartments)): ?>
        <p class="text-muted"><?php echo __('No compartment access granted.') ?></p>
        <?php else: ?>
        <table class="table table-sm">
          <thead>
            <tr>
              <th><?php echo __('Compartment') ?></th>
              <th><?php echo __('Granted') ?></th>
              <th><?php echo __('Expires') ?></th>
              <th><?php echo __('Actions') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($compartments as $comp): ?>
            <tr>
              <td>
                <strong><?php echo esc_entities($comp->code) ?></strong> - 
                <?php echo esc_entities($comp->name) ?>
              </td>
              <td><?php echo $comp->granted_date ?></td>
              <td><?php echo $comp->expiry_date ?? '-' ?></td>
              <td>
                <button class="btn btn-sm btn-outline-danger" onclick="revokeCompartment(<?php echo $comp->compartment_id ?>)">
                  <?php echo __('Revoke') ?>
                </button>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
        <?php endif ?>

        <hr>
        <h6><?php echo __('Grant Compartment Access') ?></h6>
        <form action="/security/ajax/grant_compartment" method="post" class="row">
          <input type="hidden" name="user_id" value="<?php echo $targetUser->id ?>">
          <div class="col-md-6">
            <select name="compartment_id" class="form-select">
              <?php foreach ($allCompartments as $comp): ?>
              <option value="<?php echo $comp->id ?>"><?php echo esc_entities($comp->code.' - '.$comp->name) ?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="col-md-3">
            <input type="date" name="expiry_date" class="form-control" placeholder="Expiry (optional)">
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary"><?php echo __('Grant') ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <!-- User Info -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><?php echo __('User Information') ?></h5>
      </div>
      <div class="card-body">
        <p><strong><?php echo __('Username:') ?></strong><br><?php echo esc_entities($targetUser->username) ?></p>
        <p><strong><?php echo __('Email:') ?></strong><br><?php echo esc_entities($targetUser->email) ?></p>
        <a href="/security/audit/user/<?php echo $targetUser->id ?>" class="btn btn-sm btn-outline-info">
          <i class="fas fa-history"></i> <?php echo __('View Audit Log') ?>
        </a>
      </div>
    </div>

    <!-- Clearance History -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><?php echo __('Clearance History') ?></h5>
      </div>
      <div class="card-body" style="max-height: 400px; overflow-y: auto;">
        <?php if (empty($history)): ?>
        <p class="text-muted"><?php echo __('No history.') ?></p>
        <?php else: ?>
        <ul class="list-unstyled">
          <?php foreach ($history as $h): ?>
          <li class="mb-2 pb-2 border-bottom">
            <strong><?php echo ucfirst($h->action) ?></strong>
            <br>
            <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($h->created_at)) ?></small>
            <br>
            <?php if ($h->previous_name): ?>
              <?php echo esc_entities($h->previous_name) ?> → 
            <?php endif ?>
            <?php echo esc_entities($h->new_name ?? 'None') ?>
            <br>
            <small>by <?php echo esc_entities($h->changed_by_name) ?></small>
            <?php if ($h->reason): ?>
            <br><small class="text-muted"><?php echo esc_entities($h->reason) ?></small>
            <?php endif ?>
          </li>
          <?php endforeach ?>
        </ul>
        <?php endif ?>
      </div>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function revokeClearance() {
  if (confirm('<?php echo __('Are you sure you want to revoke this clearance?') ?>')) {
    var form = document.createElement('form');
    form.method = 'post';
    form.action = '<?php echo url_for(\'@security_clearance_revoke?id=\'); ?>' + userId;
    
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'user_id';
    input.value = '<?php echo $targetUser->id ?>';
    form.appendChild(input);
    
    document.body.appendChild(form);
    form.submit();
  }
}

function revokeCompartment(compartmentId) {
  if (confirm('<?php echo __('Revoke compartment access?') ?>')) {
    // AJAX call to revoke
    fetch('/security/ajax/revoke_compartment', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'user_id=<?php echo $targetUser->id ?>&compartment_id=' + compartmentId
    }).then(function() { location.reload(); });
  }
}
</script>
