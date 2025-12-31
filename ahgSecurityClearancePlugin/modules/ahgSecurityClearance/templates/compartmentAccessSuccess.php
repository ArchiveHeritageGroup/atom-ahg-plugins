<?php
/**
 * Compartment Access Management Template.
 */
?>

<h1><i class="fas fa-users"></i> <?php echo __('Compartment Access') ?>: <?php echo esc_entities($compartment->code) ?></h1>

<div class="mb-3">
  <a href="/security/compartments" class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-arrow-left"></i> <?php echo __('Back to Compartments') ?>
  </a>
</div>

<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0"><?php echo esc_entities($compartment->name) ?></h5>
  </div>
  <div class="card-body">
    <?php if ($compartment->description): ?>
    <p><?php echo esc_entities($compartment->description) ?></p>
    <?php endif ?>
    <p>
      <strong><?php echo __('Requires Briefing:') ?></strong>
      <?php echo $compartment->requires_briefing ? __('Yes') : __('No') ?>
    </p>
  </div>
</div>

<!-- Current Access -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between">
    <h5 class="mb-0"><?php echo __('Users with Access') ?></h5>
    <span class="badge bg-primary"><?php echo count($users) ?> users</span>
  </div>
  <div class="card-body">
    <?php if (empty($users)): ?>
    <p class="text-muted text-center"><?php echo __('No users have access to this compartment.') ?></p>
    <?php else: ?>
    <table class="table table-striped">
      <thead>
        <tr>
          <th><?php echo __('User') ?></th>
          <th><?php echo __('Granted Date') ?></th>
          <th><?php echo __('Expires') ?></th>
          <th><?php echo __('Briefing') ?></th>
          <th><?php echo __('Granted By') ?></th>
          <th><?php echo __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
          <td>
            <a href="<?php echo url_for('@security_clearance_view?id=' . $user->user_id); ?>">
              <?php echo esc_entities($user->username) ?>
            </a>
            <br><small class="text-muted"><?php echo esc_entities($user->email) ?></small>
          </td>
          <td><?php echo $user->granted_date ?></td>
          <td>
            <?php if ($user->expiry_date): ?>
              <?php 
              $expired = strtotime($user->expiry_date) < time();
              ?>
              <span class="<?php echo $expired ? 'text-danger' : '' ?>">
                <?php echo $user->expiry_date ?>
              </span>
            <?php else: ?>
              <span class="text-muted"><?php echo __('No expiry') ?></span>
            <?php endif ?>
          </td>
          <td>
            <?php if ($user->briefing_date): ?>
              <?php echo $user->briefing_date ?>
              <?php if ($user->briefing_reference): ?>
              <br><small><?php echo esc_entities($user->briefing_reference) ?></small>
              <?php endif ?>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif ?>
          </td>
          <td><?php echo esc_entities($user->granted_by_name ?? '-') ?></td>
          <td>
            <button class="btn btn-sm btn-outline-danger" 
                    onclick="revokeAccess(<?php echo $user->user_id ?>)">
              <i class="fas fa-ban"></i> <?php echo __('Revoke') ?>
            </button>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
    <?php endif ?>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function revokeAccess(userId) {
  if (confirm('<?php echo __('Revoke access for this user?') ?>')) {
    fetch('/security/ajax/revoke_compartment', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'user_id=' + userId + '&compartment_id=<?php echo $compartment->id ?>'
    }).then(function() { location.reload(); });
  }
}
</script>
