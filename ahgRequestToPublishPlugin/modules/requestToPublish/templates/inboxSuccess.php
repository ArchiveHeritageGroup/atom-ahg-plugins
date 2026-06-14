<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
<div class="d-flex align-items-center mb-3">
  <i class="fas fa-inbox fa-2x text-primary me-3"></i>
  <div>
    <h1 class="h3 mb-0"><?php echo __('Curator inbox') ?></h1>
    <p class="text-muted mb-0"><?php echo __('Triage, assign and review publication requests') ?></p>
  </div>
</div>
<?php end_slot() ?>
<?php
$items = $sf_data->getRaw('items') ?: [];
$counts = $sf_data->getRaw('counts') ?: [];
$svc = $sf_data->getRaw('svc');
$filters = $sf_data->getRaw('filters') ?: [];
$inboxUrl = url_for(['module' => 'requestToPublish', 'action' => 'inbox']);
$statusTone = [220 => 'info', 219 => 'success', 221 => 'danger'];
$prioTone = ['high' => 'danger', 'normal' => 'secondary', 'low' => 'light'];
use ahgRequestToPublishPlugin\Services\WorkflowService;
?>

<div class="mb-3">
  <?php foreach (['new' => 'New', 'triaged' => 'Triaged', 'in_review' => 'In review', 'decided' => 'Decided'] as $k => $lbl): ?>
    <a href="<?php echo $inboxUrl . '?triage_status=' . $k; ?>" class="btn btn-sm <?php echo ($filters['triage_status'] ?? '') === $k ? 'btn-primary' : 'btn-outline-secondary'; ?>">
      <?php echo __($lbl); ?> <span class="badge bg-light text-dark"><?php echo (int) ($counts[$k] ?? 0); ?></span>
    </a>
  <?php endforeach; ?>
  <a href="<?php echo $inboxUrl; ?>" class="btn btn-sm btn-link"><?php echo __('All'); ?></a>
</div>

<div class="card"><div class="card-body p-0">
  <table class="table table-hover mb-0 align-middle">
    <thead><tr><th><?php echo __('Requester'); ?></th><th><?php echo __('Object'); ?></th><th><?php echo __('Decision'); ?></th><th><?php echo __('Triage'); ?></th><th><?php echo __('Priority'); ?></th><th><?php echo __('Assigned'); ?></th><th></th></tr></thead>
    <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="7" class="text-muted p-3"><?php echo __('No requests in this view.'); ?></td></tr>
    <?php else: foreach ($items as $it): ?>
      <tr>
        <td>
          <?php echo htmlspecialchars(trim((string) ($it['rtp_name'] ?? '') . ' ' . (string) ($it['rtp_surname'] ?? ''))) ?: '—'; ?>
          <?php if (!empty($it['is_anonymous'])): ?><span class="badge bg-light text-dark border"><?php echo __('anon'); ?></span><?php endif; ?>
          <div class="small text-muted"><?php echo htmlspecialchars((string) ($it['rtp_email'] ?? '')); ?></div>
        </td>
        <td><?php if (!empty($it['slug'])): ?><a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $it['slug']]); ?>" target="_blank">#<?php echo (int) ($it['object_id'] ?? 0); ?></a><?php else: ?>#<?php echo (int) ($it['object_id'] ?? 0); ?><?php endif; ?></td>
        <td><span class="badge bg-<?php echo $statusTone[(int) ($it['status_id'] ?? 0)] ?? 'secondary'; ?>"><?php echo htmlspecialchars($svc ? $svc->statusLabel((int) ($it['status_id'] ?? 0)) : ''); ?></span></td>
        <td>
          <form method="post" action="<?php echo $inboxUrl; ?>" class="d-flex gap-1">
            <input type="hidden" name="form_action" value="triage"><input type="hidden" name="request_id" value="<?php echo (int) $it['request_id']; ?>">
            <select name="triage_status" class="form-select form-select-sm" onchange="this.form.submit()">
              <?php foreach (WorkflowService::TRIAGE as $t): ?><option value="<?php echo $t; ?>"<?php echo ($it['triage_status'] ?? '') === $t ? ' selected' : ''; ?>><?php echo __(ucwords(str_replace('_', ' ', $t))); ?></option><?php endforeach; ?>
            </select>
          </form>
        </td>
        <td>
          <form method="post" action="<?php echo $inboxUrl; ?>">
            <input type="hidden" name="form_action" value="priority"><input type="hidden" name="request_id" value="<?php echo (int) $it['request_id']; ?>">
            <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
              <?php foreach (WorkflowService::PRIORITIES as $p): ?><option value="<?php echo $p; ?>"<?php echo ($it['priority'] ?? '') === $p ? ' selected' : ''; ?>><?php echo __(ucfirst($p)); ?></option><?php endforeach; ?>
            </select>
          </form>
        </td>
        <td>
          <?php if (!empty($it['assigned_name'])): ?><?php echo htmlspecialchars((string) $it['assigned_name']); ?>
          <?php else: ?>
            <form method="post" action="<?php echo $inboxUrl; ?>" class="d-inline">
              <input type="hidden" name="form_action" value="assign"><input type="hidden" name="request_id" value="<?php echo (int) $it['request_id']; ?>">
              <button class="btn btn-outline-secondary btn-sm"><?php echo __('Assign me'); ?></button>
            </form>
          <?php endif; ?>
        </td>
        <td class="text-end"><a class="btn btn-primary btn-sm" href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'review', 'id' => $it['request_id']]); ?>"><i class="fas fa-gavel me-1"></i><?php echo __('Review'); ?></a></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div></div>
