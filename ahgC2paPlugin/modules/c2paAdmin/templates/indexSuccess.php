<?php decorate_with('layout_1col.php'); ?>
<?php
/*
 * Provenance records + stored manifests for one record (staff).
 * PSIS twin of Heratio resources/views/provenance/index.blade.php.
 */
$object = $sf_data->getRaw('object');
$records = $sf_data->getRaw('records');
$manifests = $sf_data->getRaw('manifests');
$capability = $sf_data->getRaw('capability');
$notice = $sf_data->getRaw('notice');
$ioId = (int) $sf_data->getRaw('informationObjectId');
$h = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<?php slot('title'); ?>
<h1><i class="fas fa-fingerprint me-2" aria-hidden="true"></i><?php echo __('Content Credentials and Provenance'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0">
    <?php echo __('Information object'); ?>:
    <strong><?php echo $h($object->title ?? $object->identifier ?? ('#' . $object->id)); ?></strong>
    <span class="badge bg-secondary ms-1">IO #<?php echo $ioId; ?></span>
  </p>
  <div class="d-flex gap-1">
    <a href="<?php echo url_for('@ahg_c2pa_admin_record?id=' . $ioId); ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1" aria-hidden="true"></i><?php echo __('Record digitisation'); ?></a>
    <a href="<?php echo url_for('@ahg_c2pa_record?id=' . $ioId); ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-shield-alt me-1" aria-hidden="true"></i><?php echo __('Public verification page'); ?></a>
    <?php if (!empty($object->slug)) { ?>
      <a href="<?php echo url_for('@slug?slug=' . $object->slug); ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1" aria-hidden="true"></i><?php echo __('Back to record'); ?></a>
    <?php } ?>
  </div>
</div>

<?php if (!empty($notice)) { ?>
  <div class="alert alert-success"><?php echo $h($notice); ?></div>
<?php } ?>

<div class="alert <?php echo $capability['can_embed_media'] && $capability['can_sign_manifest'] ? 'alert-info' : 'alert-warning'; ?>">
  <i class="fas fa-info-circle me-1" aria-hidden="true"></i><strong><?php echo __('Signing status'); ?>:</strong> <?php echo $h($capability['summary']); ?>
</div>

<?php if (empty($records)) { ?>
  <div class="card mb-3"><div class="card-body text-center text-muted py-5">
    <i class="fas fa-fingerprint fa-2x mb-2 d-block" aria-hidden="true"></i>
    <?php echo __('No provenance records yet. Record a digitisation to create a verifiable content-credentials manifest.'); ?>
  </div></div>
<?php } else { ?>
  <div class="table-responsive mb-3">
    <table class="table table-striped table-sm align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th><?php echo __('File'); ?></th>
          <th><?php echo __('Captured by'); ?></th>
          <th><?php echo __('Device'); ?></th>
          <th><?php echo __('Captured at'); ?></th>
          <th><?php echo __('Signing'); ?></th>
          <th><?php echo __('Recorded'); ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $r) { ?>
          <tr>
            <td><?php echo (int) $r->id; ?></td>
            <td><?php echo $r->digital_object_id ? '#' . (int) $r->digital_object_id : $h(__('whole record')); ?></td>
            <td><?php echo $h($r->captured_by ?? '-'); ?></td>
            <td><?php echo $h($r->capture_device ?? '-'); ?></td>
            <td><?php echo $h($r->captured_at ?? '-'); ?></td>
            <td>
              <?php if ($r->sign_status === 'signed') { ?>
                <span class="badge bg-success"><i class="fas fa-certificate me-1" aria-hidden="true"></i><?php echo __('Signed'); ?></span>
              <?php } else { ?>
                <span class="badge bg-secondary"><?php echo __('Unsigned'); ?></span>
              <?php } ?>
            </td>
            <td><?php echo $h($r->created_at); ?></td>
            <td class="text-end">
              <a href="<?php echo url_for('@ahg_c2pa_admin_show?id=' . $ioId . '&pid=' . (int) $r->id); ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-shield-alt me-1" aria-hidden="true"></i><?php echo __('Verify'); ?></a>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
<?php } ?>

<?php if (!empty($manifests)) { ?>
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-file-signature me-2" aria-hidden="true"></i><?php echo __('Signed manifests stored for this record'); ?></div>
    <div class="card-body">
      <p class="text-muted small"><?php echo __('Every C2PA manifest issued for this record: digitisation provenance, AI outputs (NER, summaries, translations, description suggestions) and metadata-export issuances.'); ?></p>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>#</th><th><?php echo __('Action'); ?></th><th><?php echo __('Model'); ?></th><th><?php echo __('Key'); ?></th><th><?php echo __('Issued'); ?></th><th></th></tr></thead>
          <tbody>
            <?php foreach ($manifests as $m) { ?>
              <tr>
                <td><?php echo (int) $m->id; ?></td>
                <td><code><?php echo $h($m->action ?? '-'); ?></code></td>
                <td><?php echo $h(trim(($m->model_id ?? '') . ' ' . ($m->model_version ?? '')) ?: '-'); ?></td>
                <td><code class="small"><?php echo $h($m->kid ?? '-'); ?></code></td>
                <td><?php echo $h($m->created_at ?? '-'); ?></td>
                <td class="text-end"><a href="<?php echo url_for('@ahg_c2pa_manifest?id=' . (int) $m->id); ?>" class="btn btn-sm btn-outline-secondary" rel="noopener"><i class="fas fa-file-code me-1" aria-hidden="true"></i>JSON</a></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php } ?>

<p class="small"><a href="<?php echo url_for('@ahg_c2pa_admin_coverage'); ?>"><i class="fas fa-chart-pie me-1" aria-hidden="true"></i><?php echo __('Collection coverage dashboard'); ?></a></p>
<?php end_slot(); ?>
