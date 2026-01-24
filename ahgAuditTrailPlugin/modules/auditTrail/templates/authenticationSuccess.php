<?php decorate_with('layout_1col') ?>

<?php slot('title') ?>
  <h1><?php echo __('Authentication Audit Log') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row">
  <div class="col-lg-6 mb-4">
    <div class="card">
      <div class="card-header bg-success text-white"><h5 class="mb-0"><?php echo __('Recent Logins') ?></h5></div>
      <div class="table-responsive" style="max-height: 400px;">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light"><tr><th><?php echo __('Time') ?></th><th><?php echo __('User') ?></th><th><?php echo __('IP') ?></th></tr></thead>
          <tbody>
            <?php foreach ($recentLogins as $login): ?>
            <tr>
              <td><small><?php echo $login->created_at->format('M j, H:i') ?></small></td>
              <td><?php echo htmlspecialchars($login->username) ?></td>
              <td><code class="small"><?php echo $login->ip_address ?? '-' ?></code></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($recentLogins) === 0): ?>
            <tr><td colspan="3" class="text-center text-muted py-4"><?php echo __('No recent logins') ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6 mb-4">
    <div class="card">
      <div class="card-header bg-danger text-white"><h5 class="mb-0"><?php echo __('Suspicious Activity') ?> <span class="badge bg-light text-dark"><?php echo count($suspiciousActivity) ?></span></h5></div>
      <div class="table-responsive" style="max-height: 400px;">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light"><tr><th><?php echo __('Time') ?></th><th><?php echo __('Event') ?></th><th><?php echo __('Username') ?></th><th><?php echo __('IP') ?></th></tr></thead>
          <tbody>
            <?php foreach ($suspiciousActivity as $event): ?>
            <tr class="table-warning">
              <td><small><?php echo $event->created_at->format('M j, H:i') ?></small></td>
              <td><span class="badge bg-danger"><?php echo ucfirst(str_replace('_', ' ', $event->event_type)) ?></span></td>
              <td><?php echo htmlspecialchars($event->username ?? '-') ?></td>
              <td><code class="small"><?php echo $event->ip_address ?? '-' ?></code></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($suspiciousActivity) === 0): ?>
            <tr><td colspan="4" class="text-center text-muted py-4"><?php echo __('No suspicious activity') ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="mt-4">
  <a href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'browse']) ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo __('Back to Audit Trail') ?></a>
</div>
<?php end_slot() ?>