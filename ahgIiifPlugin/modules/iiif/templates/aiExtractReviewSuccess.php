<?php decorate_with('layout_2col.php') ?>
<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .iiif-white-space-pre-wrap-max-hei-568f { white-space:pre-wrap;max-height:16rem;overflow:auto; }
</style>
<?php $n = sfConfig::get('csp_nonce', ''); $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-robot me-2"></i><?php echo __('IIIF AI Extract') ?></h5>
        </div>
        <div class="card-body">
            <p class="small text-muted"><?php echo __('Review AI-generated extractions for this object. Approving writes the text to the selected description field; rejecting discards it.') ?></p>
        </div>
    </div>
    <?php if ($objectSlug): ?>
    <div class="card">
        <div class="list-group list-group-flush">
            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $objectSlug]) ?>" class="list-group-item list-group-item-action" target="_blank">
                <i class="fas fa-external-link-alt me-2"></i><?php echo __('View record') ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1><?php echo __('AI Extractions') ?>: <?php echo esc_specialchars($objectTitle) ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div id="ai-extract-review" data-approve-url="<?php echo url_for(['module' => 'iiif', 'action' => 'aiExtractApprove']) ?>" data-reject-url="<?php echo url_for(['module' => 'iiif', 'action' => 'aiExtractReject']) ?>">

<?php if (empty($extractions)): ?>
    <div class="alert alert-info"><?php echo __('No AI extractions found for this object yet. Run') ?> <code>php symfony iiif:ai-extract --object-id=<?php echo $objectId ?> --task=describe</code>.</div>
<?php else: ?>
    <?php foreach ($extractions as $ex): ?>
    <?php $badge = ['draft' => 'secondary', 'approved' => 'success', 'rejected' => 'danger'][$ex['status']] ?? 'secondary'; ?>
    <div class="card mb-3 ahg-extract-card" data-extract-id="<?php echo (int) $ex['id'] ?>">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <span class="badge bg-info text-dark me-1"><?php echo esc_specialchars($ex['task']) ?></span>
                <span class="text-muted small">canvas <?php echo (int) $ex['canvas_index'] ?> · <?php echo esc_specialchars($ex['region']) ?> · <?php echo esc_specialchars((string) $ex['model']) ?></span>
            </span>
            <span class="badge bg-<?php echo $badge ?> ahg-status"><?php echo esc_specialchars($ex['status']) ?></span>
        </div>
        <div class="card-body">
            <div class="row">
                <?php if (!empty($ex['preview_url'])): ?>
                <div class="col-md-3 mb-2">
                    <img src="<?php echo esc_specialchars($ex['preview_url']) ?>" alt="region preview" class="img-fluid border rounded" loading="lazy">
                </div>
                <?php endif; ?>
                <div class="col">
                    <pre class="ahg-extract-text mb-2 iiif-white-space-pre-wrap-max-hei-568f" ><?php echo esc_specialchars((string) $ex['output_text']) ?></pre>
                    <?php if ($ex['status'] === 'draft'): ?>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <label class="small text-muted mb-0"><?php echo __('Apply to') ?>:</label>
                        <select class="form-select form-select-sm w-auto ahg-target-field">
                            <?php if (in_array($ex['task'], ['tags', 'entities'], true)): ?>
                            <option value="subject_access_points"><?php echo __('subject access points') ?></option>
                            <?php endif; ?>
                            <?php foreach ($targetFields as $f): ?>
                            <option value="<?php echo $f ?>"><?php echo $f ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-sm btn-success ahg-approve"><i class="fas fa-check me-1"></i><?php echo __('Approve') ?></button>
                        <button type="button" class="btn btn-sm btn-outline-danger ahg-reject"><i class="fas fa-times me-1"></i><?php echo __('Reject') ?></button>
                        <span class="ahg-msg small ms-2"></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>
<?php end_slot() ?>

<script <?php echo $nonceAttr ?>>
(function () {
    var root = document.getElementById('ai-extract-review');
    if (!root) return;
    var approveUrl = root.getAttribute('data-approve-url');
    var rejectUrl = root.getAttribute('data-reject-url');

    function post(url, payload, card, msg, done) {
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
          .then(function (res) {
              if (res.ok && res.j && res.j.success) {
                  done(res.j);
              } else {
                  msg.textContent = (res.j && res.j.error) ? res.j.error : 'Failed';
                  msg.className = 'ahg-msg small ms-2 text-danger';
              }
          }).catch(function () {
              msg.textContent = 'Request failed';
              msg.className = 'ahg-msg small ms-2 text-danger';
          });
    }

    root.addEventListener('click', function (e) {
        var card = e.target.closest('.ahg-extract-card');
        if (!card) return;
        var id = parseInt(card.getAttribute('data-extract-id'), 10);
        var msg = card.querySelector('.ahg-msg');

        if (e.target.closest('.ahg-approve')) {
            var field = card.querySelector('.ahg-target-field').value;
            post(approveUrl, { extract_id: id, target_field: field }, card, msg, function (j) {
                card.querySelector('.ahg-status').textContent = 'approved';
                card.querySelector('.ahg-status').className = 'badge bg-success ahg-status';
                msg.textContent = 'Applied to ' + j.target_field;
                msg.className = 'ahg-msg small ms-2 text-success';
                var bar = e.target.closest('.d-flex');
                if (bar) bar.querySelectorAll('button, select').forEach(function (el) { el.disabled = true; });
            });
        } else if (e.target.closest('.ahg-reject')) {
            post(rejectUrl, { extract_id: id }, card, msg, function () {
                card.querySelector('.ahg-status').textContent = 'rejected';
                card.querySelector('.ahg-status').className = 'badge bg-danger ahg-status';
                var bar = e.target.closest('.d-flex');
                if (bar) bar.querySelectorAll('button, select').forEach(function (el) { el.disabled = true; });
            });
        }
    });
})();
</script>
