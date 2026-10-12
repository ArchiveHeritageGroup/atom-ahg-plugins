<?php decorate_with('layout_1col.php'); ?>
<?php
/*
 * Public per-digital-object content-credentials page.
 * PSIS twin of Heratio resources/views/verify/object.blade.php.
 */
$object = $sf_data->getRaw('object');
$state = (string) $sf_data->getRaw('state');
$chain = $sf_data->getRaw('chain');
$signer = $sf_data->getRaw('signer');
$signedAt = $sf_data->getRaw('signedAt');
$counts = $sf_data->getRaw('counts');
$downloadable = (bool) $sf_data->getRaw('downloadable');
$h = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$stateBadge = match ($state) {
    'verified' => ['bg-success', 'fa-check-circle', __('Content credentials verified')],
    'invalid'  => ['bg-danger', 'fa-times-circle', __('Could not be verified')],
    default    => ['bg-secondary', 'fa-minus-circle', __('No content credentials')],
};
$verifyUrl = url_for('@ahg_c2pa_object?id=' . (int) $object->id, true);
$badgeSvg = url_for('@ahg_c2pa_object_badge?id=' . (int) $object->id, true);
$badgeJson = url_for('@ahg_c2pa_object_badge_json?id=' . (int) $object->id, true);
$imgTag = '<a href="' . $h($verifyUrl) . '"><img src="' . $h($badgeSvg) . '" alt="' . $h(__('Content Credentials')) . '"></a>';
?>
<?php slot('title'); ?>
<h1><i class="fas fa-shield-alt me-2" aria-hidden="true"></i><?php echo __('Content credentials'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<p class="text-muted"><?php echo __('This page checks one file\'s content credentials against its cryptographic signature, live, every time you load it. It follows the C2PA open standard so the result is independently verifiable.'); ?></p>

<div class="card mb-3">
  <div class="card-header"><i class="fas fa-file-alt me-2" aria-hidden="true"></i><?php echo __('File'); ?></div>
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3"><?php echo __('File'); ?></dt><dd class="col-sm-9"><?php echo $h($object->name ?? __('(unnamed file)')); ?></dd>
      <?php if (!empty($object->mime_type)) { ?>
        <dt class="col-sm-3"><?php echo __('Format'); ?></dt><dd class="col-sm-9"><code><?php echo $h($object->mime_type); ?></code></dd>
      <?php } ?>
      <?php if (!empty($object->io_title)) { ?>
        <dt class="col-sm-3"><?php echo __('Record'); ?></dt><dd class="col-sm-9"><?php echo $h($object->io_title); ?></dd>
      <?php } ?>
      <?php if (!empty($object->io_ref)) { ?>
        <dt class="col-sm-3"><?php echo __('Reference code'); ?></dt><dd class="col-sm-9"><?php echo $h($object->io_ref); ?></dd>
      <?php } ?>
      <?php if (!empty($object->io_slug)) { ?>
        <?php $permalink = url_for('@slug?slug=' . $object->io_slug, true); ?>
        <dt class="col-sm-3"><?php echo __('Permalink'); ?></dt><dd class="col-sm-9"><a href="<?php echo $h($permalink); ?>"><?php echo $h($permalink); ?></a></dd>
      <?php } ?>
    </dl>
  </div>
</div>

<div class="card mb-3 border-2 border-success-subtle">
  <div class="card-header bg-success text-white"><i class="fas fa-certificate me-2" aria-hidden="true"></i><?php echo __('Verdict'); ?></div>
  <div class="card-body">
    <p class="mb-2"><span class="badge <?php echo $stateBadge[0]; ?> fs-5"><i class="fas <?php echo $stateBadge[1]; ?> me-1" aria-hidden="true"></i><?php echo $h($stateBadge[2]); ?></span></p>
    <?php if ($state === 'verified') { ?>
      <p class="text-success mb-0"><i class="fas fa-lock me-1" aria-hidden="true"></i><?php echo __('This file carries signed content credentials, and they have not been altered since they were created. Every step below re-computes to the value pinned in the signed claim, and the Ed25519 signature checks out.'); ?></p>
      <?php if (!empty($signer)) { ?>
        <p class="text-muted small mt-2 mb-0"><?php echo __('Signed under key'); ?> <code><?php echo $h($signer); ?></code><?php if (!empty($signedAt)) { ?> - <?php echo __('most recently on'); ?> <?php echo $h($signedAt); ?><?php } ?>.</p>
      <?php } ?>
    <?php } elseif ($state === 'invalid') { ?>
      <p class="text-danger mb-0"><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i><?php echo __('This file has content credentials, but one or more of them could not be verified. The signature does not match the recorded content - treat the affected steps below with caution.'); ?></p>
    <?php } else { ?>
      <p class="text-muted mb-0"><?php echo __('This file has no content credentials on record. That does not mean it is fake - only that no signed provenance has been recorded for it yet.'); ?></p>
    <?php } ?>
    <?php if (($counts['records'] ?? 0) > 0) { ?>
      <p class="text-muted small mt-2 mb-0">
        <?php echo __('%1% provenance record(s)', ['%1%' => (int) $counts['records']]); ?><?php if (($counts['signed'] ?? 0) > 0) { ?> - <?php echo (int) $counts['verified']; ?>/<?php echo (int) $counts['signed']; ?> <?php echo __('signed verify'); ?><?php } ?>.
      </p>
    <?php } ?>
  </div>
</div>

<?php if ($downloadable) { ?>
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-download me-2" aria-hidden="true"></i><?php echo __('Take the credentials with you'); ?></div>
    <div class="card-body">
      <p class="text-muted small mb-2"><?php echo __('Download a copy of this file with its content credentials attached, so its provenance can be verified anywhere - not just here. Where the file format supports it, the credentials are embedded into the file itself; otherwise they travel alongside it as a signed manifest.'); ?></p>
      <a href="<?php echo url_for('@ahg_c2pa_object_download?id=' . (int) $object->id); ?>" class="btn btn-primary btn-sm" rel="nofollow"><i class="fas fa-shield-alt me-1" aria-hidden="true"></i><?php echo __('Download with content credentials'); ?></a>
      <a href="<?php echo url_for('@ahg_c2pa_object_credentials?id=' . (int) $object->id); ?>" class="btn btn-outline-secondary btn-sm" rel="nofollow"><i class="fas fa-file-code me-1" aria-hidden="true"></i><?php echo __('Signed manifest only'); ?></a>
    </div>
  </div>
<?php } ?>

<?php foreach ($chain as $link) { ?>
  <?php
    $record = $link['record'];
    $v = $link['verification'] ?? [];
    $entryBadge = match ($link['entry_state'] ?? 'absent') {
        'verified' => ['bg-success', 'fa-check-circle', __('Verified')],
        'invalid'  => ['bg-danger', 'fa-times-circle', __('Could not be verified')],
        default    => ['bg-secondary', 'fa-minus-circle', __('Unsigned')],
    };
  ?>
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="fas fa-camera me-2" aria-hidden="true"></i><?php echo __('Provenance entry'); ?> #<?php echo (int) $record->id; ?></span>
      <span class="badge <?php echo $entryBadge[0]; ?>"><i class="fas <?php echo $entryBadge[1]; ?> me-1" aria-hidden="true"></i><?php echo $h($entryBadge[2]); ?></span>
    </div>
    <div class="card-body">
      <?php if (($link['entry_state'] ?? '') === 'invalid' && !empty($v['errors'])) { ?>
        <p class="text-danger small mb-3"><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i><?php echo __('Verification failed:'); ?> <?php echo $h(implode('; ', $v['errors'])); ?>.</p>
      <?php } ?>
      <dl class="row mb-3">
        <dt class="col-sm-3"><?php echo __('Digitised by'); ?></dt><dd class="col-sm-9"><?php echo $h($record->captured_by ?? '-'); ?></dd>
        <dt class="col-sm-3"><?php echo __('Date'); ?></dt><dd class="col-sm-9"><?php echo $h($record->captured_at ?? '-'); ?></dd>
        <dt class="col-sm-3"><?php echo __('Device'); ?></dt><dd class="col-sm-9"><?php echo $h($record->capture_device ?? '-'); ?></dd>
        <dt class="col-sm-3"><?php echo __('Software'); ?></dt><dd class="col-sm-9"><?php echo $h($record->capture_software ?? '-'); ?></dd>
        <?php if (!empty($record->asset_sha256)) { ?>
          <dt class="col-sm-3"><?php echo __('Content fingerprint'); ?></dt><dd class="col-sm-9"><code class="small"><?php echo $h($record->asset_sha256); ?></code></dd>
        <?php } ?>
      </dl>
      <?php if (!empty($link['assertions'])) { ?>
        <h2 class="h6"><i class="fas fa-list-ol me-2" aria-hidden="true"></i><?php echo __('Signed steps'); ?></h2>
        <p class="text-muted small"><?php echo __('The actions and declarations recorded in the signed manifest, in order.'); ?></p>
        <?php include_partial('c2paVerify/steps', ['steps' => $link['assertions']]); ?>
      <?php } ?>
      <?php if (!empty($link['inference_steps'])) { ?>
        <h2 class="h6 mt-3"><i class="fas fa-robot me-2" aria-hidden="true"></i><?php echo __('AI processing steps'); ?></h2>
        <table class="table table-sm mb-0">
          <thead><tr><th><?php echo __('Step'); ?></th><th><?php echo __('Model'); ?></th><th><?php echo __('Version'); ?></th></tr></thead>
          <tbody>
            <?php foreach ($link['inference_steps'] as $s) { ?>
              <tr><td><?php echo $h($s['step'] ?? '-'); ?></td><td><?php echo $h($s['model_id'] ?? '-'); ?></td><td><?php echo $h($s['model_version'] ?? '-'); ?></td></tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } ?>
    </div>
  </div>
<?php } ?>

<div class="card mb-3">
  <div class="card-header"><i class="fas fa-code me-2" aria-hidden="true"></i><?php echo __('Embed this badge'); ?></div>
  <div class="card-body">
    <p class="text-muted small"><?php echo __('Add this badge to any page so the authenticity of this file can be checked from there. The badge reflects the live status and links back here.'); ?></p>
    <p class="mb-2"><?php echo $imgTag; ?></p>
    <label class="form-label small mb-1" for="c2pa-embed-html"><?php echo __('HTML'); ?></label>
    <textarea id="c2pa-embed-html" class="form-control form-control-sm font-monospace mb-3" rows="2" readonly><?php echo $h($imgTag); ?></textarea>
    <p class="small mb-1">
      <a href="<?php echo $h($badgeSvg); ?>" rel="noopener"><i class="fas fa-image me-1" aria-hidden="true"></i><?php echo __('SVG badge'); ?></a>
      &middot;
      <a href="<?php echo $h($badgeJson); ?>" rel="noopener"><i class="fas fa-database me-1" aria-hidden="true"></i><?php echo __('JSON status'); ?></a>
    </p>
    <p class="text-muted small mb-0"><?php echo __('The JSON endpoint is CORS-open and read-only, for programmatic checks.'); ?></p>
  </div>
</div>

<p class="text-muted small"><i class="fas fa-info-circle me-1" aria-hidden="true"></i><?php echo __('Content credentials follow the C2PA open standard. Signatures are Ed25519 and re-checked live on every page load - nothing here is cached.'); ?></p>
<?php end_slot(); ?>
