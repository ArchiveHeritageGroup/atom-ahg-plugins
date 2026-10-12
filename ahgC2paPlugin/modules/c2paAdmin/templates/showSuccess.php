<?php decorate_with('layout_1col.php'); ?>
<?php
/*
 * Verify + display one provenance record's content credentials (staff).
 * PSIS twin of Heratio resources/views/provenance/show.blade.php.
 */
$record = $sf_data->getRaw('record');
$v = $sf_data->getRaw('verification');
$inferenceSteps = $sf_data->getRaw('inferenceSteps');
$capability = $sf_data->getRaw('capability');
$notice = $sf_data->getRaw('notice');
$ioId = (int) $sf_data->getRaw('informationObjectId');
$h = static fn ($x): string => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$badge = match ($v['status']) {
    'verified' => ['bg-success', 'fa-check-circle', __('Verified')],
    'unsigned' => ['bg-secondary', 'fa-minus-circle', __('Unsigned')],
    default    => ['bg-danger', 'fa-times-circle', __('Verification failed')],
};
?>
<?php slot('title'); ?>
<h1><i class="fas fa-shield-alt me-2" aria-hidden="true"></i><?php echo __('Content Credentials'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="mb-3">
  <a href="<?php echo url_for('@ahg_c2pa_admin_index?id=' . $ioId); ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1" aria-hidden="true"></i><?php echo __('Back'); ?></a>
</div>

<?php if (!empty($notice)) { ?>
  <div class="alert alert-success"><?php echo $h($notice); ?></div>
<?php } ?>

<div class="card mb-3">
  <div class="card-header bg-success text-white"><i class="fas fa-certificate me-2" aria-hidden="true"></i><?php echo __('Authenticity'); ?></div>
  <div class="card-body">
    <p class="mb-2">
      <span class="badge <?php echo $badge[0]; ?> fs-6"><i class="fas <?php echo $badge[1]; ?> me-1" aria-hidden="true"></i><?php echo $h($badge[2]); ?></span>
      <?php if (!empty($v['kid'])) { ?><span class="text-muted ms-2"><?php echo __('Signed under key'); ?> <code><?php echo $h($v['kid']); ?></code> (Ed25519)</span><?php } ?>
    </p>
    <?php if ($v['ok']) { ?>
      <p class="text-success mb-0"><i class="fas fa-lock me-1" aria-hidden="true"></i><?php echo __('Every assertion re-hashes to the value pinned in the signed claim, and the Ed25519 claim signature verifies. This content credential has not been tampered with.'); ?></p>
    <?php } elseif ($v['status'] === 'unsigned') { ?>
      <p class="text-muted mb-0"><?php echo __('This record has no signed manifest. It documents the digitisation but carries no cryptographic authenticity proof.'); ?></p>
    <?php } else { ?>
      <ul class="text-danger mb-0"><?php foreach ($v['errors'] as $err) { ?><li><?php echo $h($err); ?></li><?php } ?></ul>
    <?php } ?>
    <p class="text-muted small mt-2 mb-0"><i class="fas fa-info-circle me-1" aria-hidden="true"></i><?php echo $h($capability['summary']); ?></p>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header"><i class="fas fa-camera me-2" aria-hidden="true"></i><?php echo __('Digitisation provenance'); ?></div>
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3"><?php echo __('Captured by'); ?></dt><dd class="col-sm-9"><?php echo $h($record->captured_by ?? '-'); ?></dd>
      <dt class="col-sm-3"><?php echo __('Captured at'); ?></dt><dd class="col-sm-9"><?php echo $h($record->captured_at ?? '-'); ?></dd>
      <dt class="col-sm-3"><?php echo __('Capture device'); ?></dt><dd class="col-sm-9"><?php echo $h($record->capture_device ?? '-'); ?></dd>
      <dt class="col-sm-3"><?php echo __('Capture software'); ?></dt><dd class="col-sm-9"><?php echo $h($record->capture_software ?? '-'); ?></dd>
      <dt class="col-sm-3"><?php echo __('Digital object'); ?></dt>
      <dd class="col-sm-9">
        <?php if ($record->digital_object_id) { ?>
          <a href="<?php echo url_for('@ahg_c2pa_object?id=' . (int) $record->digital_object_id); ?>">#<?php echo (int) $record->digital_object_id; ?></a>
        <?php } else { ?>
          <?php echo __('whole record'); ?>
        <?php } ?>
      </dd>
      <dt class="col-sm-3"><?php echo __('Asset SHA-256'); ?></dt><dd class="col-sm-9"><code class="small"><?php echo $h($record->asset_sha256 ?? '-'); ?></code></dd>
      <dt class="col-sm-3"><?php echo __('Notes'); ?></dt><dd class="col-sm-9"><?php echo $h($record->notes ?? '-'); ?></dd>
      <dt class="col-sm-3"><?php echo __('Recorded'); ?></dt><dd class="col-sm-9"><?php echo $h($record->created_at); ?></dd>
    </dl>
  </div>
</div>

<?php if (!empty($inferenceSteps)) { ?>
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-robot me-2" aria-hidden="true"></i><?php echo __('AI inference steps'); ?></div>
    <div class="card-body">
      <table class="table table-sm mb-0">
        <thead><tr><th><?php echo __('Step'); ?></th><th><?php echo __('Model'); ?></th><th><?php echo __('Version'); ?></th><th><?php echo __('Output SHA-256'); ?></th></tr></thead>
        <tbody>
          <?php foreach ($inferenceSteps as $s) { ?>
            <tr><td><?php echo $h($s['step'] ?? '-'); ?></td><td><?php echo $h($s['model_id'] ?? '-'); ?></td><td><?php echo $h($s['model_version'] ?? '-'); ?></td><td><code class="small"><?php echo $h($s['output_sha256'] ?? '-'); ?></code></td></tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
<?php } ?>

<?php if ($record->manifest_id) { ?>
  <div class="mb-3">
    <a href="<?php echo url_for('@ahg_c2pa_admin_manifest?id=' . $ioId . '&pid=' . (int) $record->id); ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"><i class="fas fa-file-code me-1" aria-hidden="true"></i><?php echo __('View signed manifest JSON'); ?></a>
  </div>
<?php } ?>
<?php end_slot(); ?>
