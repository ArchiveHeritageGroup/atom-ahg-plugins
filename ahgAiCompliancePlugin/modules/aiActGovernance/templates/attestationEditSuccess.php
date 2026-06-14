<?php
require_once dirname(__DIR__, 3) . '/lib/Services/AiActGovernanceService.php';
$r = $sf_data->getRaw('record');
$systemOptions = $sf_data->getRaw('systemOptions') ?: [];
$val = function ($f, $d = '') use ($r) { return htmlspecialchars((string) ($r->$f ?? $d)); };
$sel = function ($f, $opt) use ($r) { return ((string) ($r->$f ?? '') === (string) $opt) ? ' selected' : ''; };
$postUrl = url_for(['module' => 'aiActGovernance', 'action' => 'attestationEdit']);
?>
<main id="content" class="container-xxl py-4">
  <h1 class="mb-4"><i class="fas fa-file-signature me-2"></i><?php echo $r ? __('Edit Attestation') : __('New Attestation'); ?></h1>
  <form method="post" action="<?php echo $postUrl; ?>">
    <?php if ($r): ?><input type="hidden" name="id" value="<?php echo (int) $r->id; ?>"><?php endif; ?>
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Type'); ?></label>
        <select class="form-select" name="type">
          <?php foreach (AiActGovernanceService::ATTESTATION_TYPES as $o): ?><option value="<?php echo $o; ?>"<?php echo $sel('type', $o); ?>><?php echo __(ucwords(str_replace('_', ' ', $o))); ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('AI system'); ?></label>
        <select class="form-select" name="system_id"><option value=""><?php echo __('— none —'); ?></option>
          <?php foreach ($systemOptions as $id => $name): ?><option value="<?php echo (int) $id; ?>"<?php echo $sel('system_id', $id); ?>><?php echo htmlspecialchars((string) $name); ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <div class="mb-3"><label class="form-label"><?php echo __('Statement'); ?></label>
      <textarea class="form-control" name="statement" rows="4"><?php echo $val('statement'); ?></textarea></div>
    <div class="row">
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Status'); ?></label>
        <select class="form-select" name="status">
          <?php foreach (AiActGovernanceService::ATTESTATION_STATUSES as $o): ?><option value="<?php echo $o; ?>"<?php echo $sel('status', $o); ?>><?php echo __(ucfirst($o)); ?></option><?php endforeach; ?>
        </select>
        <div class="form-text"><?php echo __('Setting "attested" stamps the attested date.'); ?></div></div>
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Attested by'); ?></label>
        <input class="form-control" name="attested_by" value="<?php echo $val('attested_by'); ?>"></div>
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Next review'); ?></label>
        <input type="date" class="form-control" name="next_review_date" value="<?php echo $val('next_review_date'); ?>"></div>
    </div>
    <div class="mb-3"><label class="form-label"><?php echo __('Evidence URL'); ?></label>
      <input type="url" class="form-control" name="evidence_url" value="<?php echo $val('evidence_url'); ?>" placeholder="https://..."></div>
    <?php if ($r && $r->attested_at): ?>
      <p class="text-muted small"><?php echo __('Attested at'); ?>: <?php echo htmlspecialchars((string) $r->attested_at); ?></p>
    <?php endif; ?>
    <div class="mt-2">
      <button class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save'); ?></button>
      <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'attestations']); ?>"><?php echo __('Cancel'); ?></a>
    </div>
  </form>
</main>
