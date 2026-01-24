<?php decorate_with('layout_1col') ?>

<?php slot('title') ?>
  <h1><?php echo __('Security Access Log') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row">
  <div class="col-lg-6 mb-4">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Classified Content Access') ?></h5></div>
      <div class="table-responsive" style="max-height: 400px;">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light"><tr><th><?php echo __('Time') ?></th><th><?php echo __('User') ?></th><th><?php echo __('Classification') ?></th><th><?php echo __('Entity') ?></th></tr></thead>
          <tbody>
            <?php foreach ($classifiedAccess as $access): ?>
            <tr>
              <td><small><?php echo $access->created_at->format('M j, H:i') ?></small></td>
              <td><?php echo htmlspecialchars($access->username ?? 'Anonymous') ?></td>
              <td><span class="badge bg-warning text-dark"><?php echo ucfirst($access->security_classification) ?></span></td>
              <td><?php echo htmlspecialchars(mb_substr($access->entity_title ?? '-', 0, 30)) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($classifiedAccess) === 0): ?>
            <tr><td colspan="4" class="text-center text-muted py-4"><?php echo __('No classified access records') ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6 mb-4">
    <div class="card">
      <div class="card-header bg-danger text-white"><h5 class="mb-0"><?php echo __('Denied Access Attempts') ?></h5></div>
      <div class="table-responsive" style="max-height: 400px;">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light"><tr><th><?php echo __('Time') ?></th><th><?php echo __('User') ?></th><th><?php echo __('Entity') ?></th><th><?php echo __('Reason') ?></th></tr></thead>
          <tbody>
            <?php foreach ($deniedAccess as $access): ?>
            <tr class="table-danger">
              <td><small><?php echo $access->created_at->format('M j, H:i') ?></small></td>
              <td><?php echo htmlspecialchars($access->username ?? 'Anonymous') ?></td>
              <td><?php echo htmlspecialchars(mb_substr($access->entity_title ?? '-', 0, 30)) ?></td>
              <td><small><?php echo htmlspecialchars($access->denial_reason ?? '-') ?></small></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($deniedAccess) === 0): ?>
            <tr><td colspan="4" class="text-center text-muted py-4"><?php echo __('No denied access attempts') ?></td></tr>
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