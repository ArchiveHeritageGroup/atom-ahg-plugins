<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
<div class="d-flex align-items-center mb-3">
  <i class="fas fa-gavel fa-2x text-primary me-3"></i>
  <div>
    <h1 class="h3 mb-0"><?php echo __('Review publication request') ?></h1>
    <p class="text-muted mb-0"><?php echo __('Peer review and curator notes') ?></p>
  </div>
</div>
<?php end_slot() ?>
<?php
$req = $sf_data->getRaw('requestData');
$w = $sf_data->getRaw('workflow');
$reviews = $sf_data->getRaw('reviews') ?: [];
$rid = (int) $sf_data->getRaw('requestId');
$svc = $sf_data->getRaw('svc');
$reviewUrl = url_for(['module' => 'requestToPublish', 'action' => 'review', 'id' => $rid]);
$verdictTone = ['recommend_approve' => 'success', 'recommend_reject' => 'danger', 'needs_changes' => 'warning', 'abstain' => 'secondary'];
use ahgRequestToPublishPlugin\Services\WorkflowService;
$g = function ($o, $f) { return is_object($o) ? htmlspecialchars((string) ($o->$f ?? '')) : ''; };
?>

<p><a href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'inbox']); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i><?php echo __('Inbox'); ?></a></p>

<div class="row">
  <div class="col-md-6">
    <div class="card mb-3"><div class="card-header"><h5 class="mb-0"><?php echo __('Request details'); ?></h5></div>
      <div class="card-body">
        <?php if ($req): ?>
          <dl class="row mb-0">
            <dt class="col-sm-4"><?php echo __('Name'); ?></dt><dd class="col-sm-8"><?php echo $g($req, 'rtp_name') . ' ' . $g($req, 'rtp_surname'); ?></dd>
            <dt class="col-sm-4"><?php echo __('Email'); ?></dt><dd class="col-sm-8"><?php echo $g($req, 'rtp_email'); ?></dd>
            <dt class="col-sm-4"><?php echo __('Institution'); ?></dt><dd class="col-sm-8"><?php echo $g($req, 'rtp_institution'); ?></dd>
            <dt class="col-sm-4"><?php echo __('Planned use'); ?></dt><dd class="col-sm-8"><?php echo $g($req, 'rtp_planned_use'); ?></dd>
            <dt class="col-sm-4"><?php echo __('Motivation'); ?></dt><dd class="col-sm-8"><?php echo $g($req, 'rtp_motivation'); ?></dd>
            <dt class="col-sm-4"><?php echo __('Decision'); ?></dt><dd class="col-sm-8"><?php echo htmlspecialchars($svc ? $svc->statusLabel((int) ($req->status_id ?? 0)) : ''); ?></dd>
          </dl>
        <?php else: ?><p class="text-muted mb-0"><?php echo __('Request not found.'); ?></p><?php endif; ?>
      </div>
    </div>

    <div class="card mb-3"><div class="card-header"><h5 class="mb-0"><?php echo __('Curator notes'); ?></h5></div>
      <div class="card-body">
        <form method="post" action="<?php echo $reviewUrl; ?>">
          <input type="hidden" name="form_action" value="notes">
          <textarea class="form-control mb-2" name="internal_notes" rows="3"><?php echo $w ? htmlspecialchars((string) ($w->internal_notes ?? '')) : ''; ?></textarea>
          <button class="btn btn-outline-primary btn-sm"><?php echo __('Save notes'); ?></button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card mb-3"><div class="card-header"><h5 class="mb-0"><?php echo __('Peer reviews'); ?> <span class="badge bg-secondary"><?php echo count($reviews); ?></span></h5></div>
      <div class="card-body">
        <?php if (empty($reviews)): ?><p class="text-muted"><?php echo __('No reviews yet.'); ?></p>
        <?php else: foreach ($reviews as $rv): ?>
          <div class="border-bottom pb-2 mb-2">
            <span class="badge bg-<?php echo $verdictTone[$rv->verdict] ?? 'secondary'; ?>"><?php echo __(ucwords(str_replace('_', ' ', (string) $rv->verdict))); ?></span>
            <strong class="ms-1"><?php echo htmlspecialchars((string) ($rv->reviewer_name ?? 'Reviewer')); ?></strong>
            <span class="small text-muted float-end"><?php echo htmlspecialchars((string) $rv->created_at); ?></span>
            <?php if (!empty($rv->comments)): ?><p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars((string) $rv->comments)); ?></p><?php endif; ?>
          </div>
        <?php endforeach; endif; ?>

        <form method="post" action="<?php echo $reviewUrl; ?>" class="mt-3">
          <input type="hidden" name="form_action" value="add_review">
          <div class="mb-2"><label class="form-label"><?php echo __('Your name'); ?></label><input class="form-control form-control-sm" name="reviewer_name"></div>
          <div class="mb-2"><label class="form-label"><?php echo __('Verdict'); ?></label>
            <select class="form-select form-select-sm" name="verdict">
              <?php foreach (WorkflowService::VERDICTS as $v): ?><option value="<?php echo $v; ?>"><?php echo __(ucwords(str_replace('_', ' ', $v))); ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2"><label class="form-label"><?php echo __('Comments'); ?></label><textarea class="form-control form-control-sm" name="comments" rows="3"></textarea></div>
          <button class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i><?php echo __('Add review'); ?></button>
        </form>
      </div>
    </div>
  </div>
</div>
