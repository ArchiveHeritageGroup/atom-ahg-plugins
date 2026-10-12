<?php decorate_with('layout_1col.php'); ?>
<?php
/*
 * Public "verify authenticity" page for one record (badge target).
 * PSIS twin of Heratio resources/views/verify/show.blade.php.
 */
$object = $sf_data->getRaw('object');
$chain = $sf_data->getRaw('chain');
$summary = $sf_data->getRaw('summary');
$reference = $sf_data->getRaw('reference');
$h = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<?php slot('title'); ?>
<h1><i class="fas fa-shield-alt me-2" aria-hidden="true"></i><?php echo __('Verify authenticity'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<p class="text-muted"><?php echo __('This page checks the digitisation provenance of this record against its cryptographic signature, live, every time you load it.'); ?></p>

<?php if ($object === null) { ?>
  <div class="card mb-3">
    <div class="card-body">
      <p class="mb-1"><span class="badge bg-secondary fs-6"><i class="fas fa-question-circle me-1" aria-hidden="true"></i><?php echo __('Not found'); ?></span></p>
      <p class="mb-0"><?php echo __('We could not find a record matching'); ?> <code><?php echo $h($reference); ?></code>. <?php echo __('Check the link and try again.'); ?></p>
    </div>
  </div>
<?php } else { ?>
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-file-alt me-2" aria-hidden="true"></i><?php echo __('Record'); ?></div>
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-3"><?php echo __('Title'); ?></dt>
        <dd class="col-sm-9"><?php echo $h($object->title ?? __('(untitled)')); ?></dd>
        <dt class="col-sm-3"><?php echo __('Reference code'); ?></dt>
        <dd class="col-sm-9"><?php echo $h($object->identifier ?? '-'); ?></dd>
        <?php if (!empty($object->slug)) { ?>
          <?php $permalink = url_for('@slug?slug=' . $object->slug, true); ?>
          <dt class="col-sm-3"><?php echo __('Permalink'); ?></dt>
          <dd class="col-sm-9"><a href="<?php echo $h($permalink); ?>"><?php echo $h($permalink); ?></a></dd>
        <?php } ?>
      </dl>
      <?php if ($sf_user->hasCredential(['editor', 'administrator'], false)) { ?>
        <p class="mt-2 mb-0 small"><a href="<?php echo url_for('@ahg_c2pa_admin_index?id=' . (int) $object->id); ?>"><i class="fas fa-fingerprint me-1" aria-hidden="true"></i><?php echo __('Manage provenance records'); ?></a></p>
      <?php } ?>
    </div>
  </div>

  <?php
    $total =(int) ($summary['total'] ?? 0);
    $verified = (int) ($summary['verified'] ?? 0);
    $tampered = (int) ($summary['tampered'] ?? 0);
    $signed = (int) ($summary['signed'] ?? 0);
    if ($total === 0) {
        $overall = ['bg-secondary', 'fa-minus-circle', __('No provenance on record')];
    } elseif ($verified > 0 && $tampered === 0) {
        $overall = ['bg-success', 'fa-check-circle', __('Authenticity verified')];
    } elseif ($tampered > 0) {
        $overall = ['bg-danger', 'fa-times-circle', __('Could not be verified')];
    } else {
        $overall = ['bg-secondary', 'fa-minus-circle', __('Documented, not signed')];
    }
  ?>
  <div class="card mb-3 border-2 border-success-subtle">
    <div class="card-header bg-success text-white"><i class="fas fa-certificate me-2" aria-hidden="true"></i><?php echo __('Authenticity verdict'); ?></div>
    <div class="card-body">
      <p class="mb-2"><span class="badge <?php echo $overall[0]; ?> fs-5"><i class="fas <?php echo $overall[1]; ?> me-1" aria-hidden="true"></i><?php echo $h($overall[2]); ?></span></p>
      <?php if ($total === 0) { ?>
        <p class="mb-0"><?php echo __('This record has no digitisation provenance on file yet, so there is nothing to verify. That does not mean it is fake - only that no content credentials have been recorded for it.'); ?></p>
      <?php } elseif ($verified > 0 && $tampered === 0) { ?>
        <p class="text-success mb-0"><i class="fas fa-lock me-1" aria-hidden="true"></i><?php echo __("This record's digitisation is cryptographically verified. The signed content credentials below have not been altered since they were created."); ?></p>
      <?php } elseif ($tampered > 0) { ?>
        <p class="text-danger mb-0"><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i><?php echo __('One or more content credentials for this record could not be verified. The signature does not match the recorded content - treat the affected entries below with caution.'); ?></p>
      <?php } else { ?>
        <p class="text-muted mb-0"><?php echo __('This record has documented digitisation provenance, but it is not cryptographically signed, so authenticity cannot be proven by signature.'); ?></p>
      <?php } ?>
      <?php if ($total > 0) { ?>
        <p class="text-muted small mt-2 mb-0">
          <?php echo __('%1% provenance record(s)', ['%1%' => $total]); ?><?php if ($signed > 0) { ?> - <?php echo $verified; ?>/<?php echo $signed; ?> <?php echo __('signed credentials verify'); ?><?php } ?>.
        </p>
      <?php } ?>
    </div>
  </div>

  <?php foreach ($chain as $link) { ?>
    <?php
      $record = $link['record'];
      $v = $link['verification'];
      $badge = match ($v['status'] ?? '') {
          'verified' => ['bg-success', 'fa-check-circle', __('Verified')],
          'unsigned' => ['bg-secondary', 'fa-minus-circle', __('Unsigned')],
          default    => ['bg-danger', 'fa-times-circle', __('Could not be verified')],
      };
    ?>
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-camera me-2" aria-hidden="true"></i><?php echo __('Digitisation'); ?> #<?php echo (int) $record->id; ?></span>
        <span class="badge <?php echo $badge[0]; ?>"><i class="fas <?php echo $badge[1]; ?> me-1" aria-hidden="true"></i><?php echo $h($badge[2]); ?></span>
      </div>
      <div class="card-body">
        <?php if (($v['status'] ?? '') === 'verified') { ?>
          <p class="text-success small mb-3"><i class="fas fa-lock me-1" aria-hidden="true"></i><?php echo __('Every detail below re-computes to the value pinned in the signed claim, and the Ed25519 signature checks out.'); ?>
            <?php if (!empty($v['kid'])) { ?><span class="text-muted"><?php echo __('Signed under key'); ?> <code><?php echo $h($v['kid']); ?></code>.</span><?php } ?>
          </p>
        <?php } elseif (($v['status'] ?? '') === 'unsigned') { ?>
          <p class="text-muted small mb-3"><?php echo __('This entry documents the digitisation but carries no cryptographic signature.'); ?></p>
        <?php } else { ?>
          <p class="text-danger small mb-3"><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i><?php echo __('Verification failed for this entry:'); ?>
            <?php if (!empty($v['errors'])) { echo $h(implode('; ', $v['errors'])) . '.'; } ?>
          </p>
        <?php } ?>
        <dl class="row mb-0">
          <dt class="col-sm-3"><?php echo __('Digitised by'); ?></dt><dd class="col-sm-9"><?php echo $h($record->captured_by ?? '-'); ?></dd>
          <dt class="col-sm-3"><?php echo __('Date'); ?></dt><dd class="col-sm-9"><?php echo $h($record->captured_at ?? '-'); ?></dd>
          <dt class="col-sm-3"><?php echo __('Device'); ?></dt><dd class="col-sm-9"><?php echo $h($record->capture_device ?? '-'); ?></dd>
          <dt class="col-sm-3"><?php echo __('Software'); ?></dt><dd class="col-sm-9"><?php echo $h($record->capture_software ?? '-'); ?></dd>
          <?php if (!empty($record->asset_sha256)) { ?>
            <dt class="col-sm-3"><?php echo __('Content fingerprint'); ?></dt><dd class="col-sm-9"><code class="small"><?php echo $h($record->asset_sha256); ?></code></dd>
          <?php } ?>
          <?php if (!empty($record->digital_object_id)) { ?>
            <dt class="col-sm-3"><?php echo __('File'); ?></dt>
            <dd class="col-sm-9"><a href="<?php echo url_for('@ahg_c2pa_object?id=' . (int) $record->digital_object_id); ?>"><?php echo __('View this file\'s content credentials'); ?></a></dd>
          <?php } ?>
        </dl>
        <?php if (!empty($link['inference_steps'])) { ?>
          <h2 class="h6 mt-3"><i class="fas fa-robot me-2" aria-hidden="true"></i><?php echo __('AI processing steps'); ?></h2>
          <p class="text-muted small"><?php echo __('Automated steps that have touched this asset. Each is part of the signed chain.'); ?></p>
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
<?php } ?>

<p class="text-muted small">
  <i class="fas fa-info-circle me-1" aria-hidden="true"></i><?php echo __('Content credentials follow the C2PA open standard. Signatures are Ed25519 and re-checked live on every page load - nothing here is cached.'); ?>
  <a href="<?php echo url_for('@ahg_c2pa_check'); ?>"><?php echo __('Check a file of your own'); ?></a>.
</p>
<?php end_slot(); ?>
