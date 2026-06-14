<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
<div class="d-flex align-items-center mb-3">
  <i class="fas fa-receipt fa-2x text-primary me-3"></i>
  <div>
    <h1 class="h3 mb-0"><?php echo __('Track a publication request') ?></h1>
    <p class="text-muted mb-0"><?php echo __('Enter the receipt code you were given when you submitted your request') ?></p>
  </div>
</div>
<?php end_slot() ?>
<?php
$token = $sf_data->getRaw('token');
$w = $sf_data->getRaw('workflow');
$svc = $sf_data->getRaw('svc');
$statusTone = [220 => 'info', 219 => 'success', 221 => 'danger'];
$triageLabels = ['new' => 'Received', 'triaged' => 'Being triaged', 'in_review' => 'Under review', 'decided' => 'Decided'];
?>

<div class="card mb-4">
  <div class="card-body">
    <form method="post" action="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'receipt']); ?>" class="row g-2">
      <div class="col-sm-9"><input class="form-control" name="token" value="<?php echo htmlspecialchars((string) $token); ?>" placeholder="<?php echo __('Receipt code'); ?>"></div>
      <div class="col-sm-3 d-grid"><button class="btn btn-primary"><i class="fas fa-search me-1"></i><?php echo __('Track'); ?></button></div>
    </form>
  </div>
</div>

<?php if ($token !== '' && !$w): ?>
  <div class="alert alert-warning"><?php echo __('No request found for that receipt code. Please check it and try again.'); ?></div>
<?php elseif ($w): ?>
  <div class="card">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Request status'); ?></h5></div>
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-3"><?php echo __('Submitted by'); ?></dt>
        <dd class="col-sm-9"><?php echo htmlspecialchars(trim((string) ($w->rtp_name ?? '') . ' ' . (string) ($w->rtp_surname ?? ''))) ?: '—'; ?></dd>
        <dt class="col-sm-3"><?php echo __('Decision status'); ?></dt>
        <dd class="col-sm-9"><span class="badge bg-<?php echo $statusTone[(int) ($w->status_id ?? 0)] ?? 'secondary'; ?>"><?php echo htmlspecialchars($svc ? $svc->statusLabel((int) ($w->status_id ?? 0)) : ''); ?></span></dd>
        <dt class="col-sm-3"><?php echo __('Progress'); ?></dt>
        <dd class="col-sm-9"><?php echo htmlspecialchars($triageLabels[$w->triage_status] ?? ucfirst((string) $w->triage_status)); ?></dd>
        <dt class="col-sm-3"><?php echo __('Submitted on'); ?></dt>
        <dd class="col-sm-9"><?php echo htmlspecialchars((string) ($w->created_at ?? '')); ?></dd>
        <dt class="col-sm-3"><?php echo __('Receipt code'); ?></dt>
        <dd class="col-sm-9"><code><?php echo htmlspecialchars((string) $w->receipt_token); ?></code></dd>
      </dl>
    </div>
  </div>
<?php endif; ?>
