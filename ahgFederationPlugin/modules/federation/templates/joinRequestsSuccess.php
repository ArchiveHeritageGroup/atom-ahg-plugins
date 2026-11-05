<?php
$requests = $sf_data->getRaw('requests') ?: [];
$counts = $sf_data->getRaw('counts') ?: [];
$statuses = $sf_data->getRaw('statuses') ?: [];
$url = url_for(['module' => 'federation', 'action' => 'joinRequests']);
$badge = ['pending' => 'bg-warning text-dark', 'reviewing' => 'bg-info text-dark', 'approved' => 'bg-success', 'declined' => 'bg-secondary'];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-person-plus me-2"></i><?php echo __('Join requests') ?></h1>
    <div>
      <a href="<?php echo url_for(['module' => 'federation', 'action' => 'join']) ?>" class="btn btn-outline-secondary btn-sm" target="_blank"><?php echo __('Public form') ?></a>
      <a href="<?php echo url_for(['module' => 'federation', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Federation') ?></a>
    </div>
  </div>
  <p class="text-muted small"><?php echo __('Approving marks the request only. To bring an institution in, add it as a federation peer.') ?></p>

  <div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm <?php echo '' === $filter ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?php echo $url ?>"><?php echo __('All') ?> <span class="badge bg-light text-dark"><?php echo array_sum($counts) ?></span></a>
    <?php foreach ($statuses as $s): ?>
      <a class="btn btn-sm <?php echo $filter === $s ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?php echo $url . '?status=' . $s ?>"><?php echo __(ucfirst($s)) ?> <span class="badge bg-light text-dark"><?php echo (int) ($counts[$s] ?? 0) ?></span></a>
    <?php endforeach ?>
  </div>

  <?php if (empty($requests)): ?>
    <div class="card"><div class="card-body text-muted"><?php echo __('No join requests.') ?></div></div>
  <?php else: foreach ($requests as $r): ?>
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong><?php echo esc_entities($r->institution_name) ?></strong>
        <span><span class="badge <?php echo $badge[$r->status] ?? 'bg-secondary' ?>"><?php echo __(ucfirst($r->status)) ?></span> <span class="small text-muted ms-2"><?php echo esc_entities((string) $r->created_at) ?></span></span>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-lg-7">
            <dl class="row mb-0 small">
              <dt class="col-sm-4"><?php echo __('Contact') ?></dt><dd class="col-sm-8"><?php echo esc_entities(trim(($r->contact_name ?? '') . ' ' . ($r->contact_email ? '<' . $r->contact_email . '>' : '')) ?: '-') ?></dd>
              <dt class="col-sm-4"><?php echo __('Website') ?></dt><dd class="col-sm-8"><?php if ($r->base_url): ?><a href="<?php echo esc_entities($r->base_url) ?>" target="_blank" rel="noopener noreferrer nofollow"><?php echo esc_entities($r->base_url) ?></a><?php else: ?>-<?php endif ?></dd>
              <dt class="col-sm-4"><?php echo __('Would share') ?></dt><dd class="col-sm-8" style="white-space: pre-wrap"><?php echo esc_entities((string) ($r->what_they_share ?: '-')) ?></dd>
              <dt class="col-sm-4"><?php echo __('Notes') ?></dt><dd class="col-sm-8" style="white-space: pre-wrap"><?php echo esc_entities((string) ($r->notes ?: '-')) ?></dd>
              <?php if ($r->reviewed_at): ?><dt class="col-sm-4"><?php echo __('Last reviewed') ?></dt><dd class="col-sm-8"><?php echo esc_entities((string) $r->reviewed_by) ?> (<?php echo esc_entities($r->reviewed_at) ?>)</dd><?php endif ?>
            </dl>
          </div>
          <div class="col-lg-5">
            <form method="post" action="<?php echo $url ?>">
              <?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?>
              <input type="hidden" name="id" value="<?php echo (int) $r->id ?>">
              <div class="input-group input-group-sm mb-2">
                <select class="form-select" name="status" aria-label="<?php echo __('Status') ?>">
                  <?php foreach ($statuses as $s): ?><option value="<?php echo $s ?>" <?php echo $r->status === $s ? 'selected' : '' ?>><?php echo __(ucfirst($s)) ?></option><?php endforeach ?>
                </select>
                <button type="submit" class="btn btn-primary"><?php echo __('Update') ?></button>
              </div>
              <textarea class="form-control form-control-sm" name="review_note" rows="2" placeholder="<?php echo __('Review note (optional)') ?>" aria-label="<?php echo __('Review note') ?>"></textarea>
            </form>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach ?>
  <?php endif ?>
</div>
