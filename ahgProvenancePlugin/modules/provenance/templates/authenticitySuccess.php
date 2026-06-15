<?php
$a = $sf_data->getRaw('authenticity') ?: ['inferences' => [], 'c2pa' => [], 'summary' => []];
$resource = $sf_data->getRaw('resource');
$objectId = (int) $sf_data->getRaw('objectId');
$sum = $a['summary'] ?? [];
$title = $resource ? ($resource->getTitle(['cultureFallback' => true]) ?: ('#' . $objectId)) : ('#' . $objectId);

$verdictBadge = function (string $v) {
    switch ($v) {
        case 'verified':  return '<span class="badge bg-success"><i class="fas fa-shield-alt me-1"></i>Signature verified</span>';
        case 'tampered':  return '<span class="badge bg-danger"><i class="fas fa-triangle-exclamation me-1"></i>Signature mismatch</span>';
        case 'signed':    return '<span class="badge bg-info text-dark"><i class="fas fa-signature me-1"></i>Signed</span>';
        default:          return '<span class="badge bg-secondary"><i class="fas fa-minus me-1"></i>Unsigned</span>';
    }
};
?>
<div class="container-fluid py-3 provenance-authenticity">
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div>
      <h1 class="h3 mb-1"><i class="fas fa-fingerprint me-2"></i><?php echo __('Authenticity report') ?></h1>
      <div class="text-muted"><?php echo esc_entities($title) ?></div>
    </div>
    <?php if ($resource): ?>
      <a class="btn btn-outline-secondary btn-sm" href="/<?php echo esc_entities($resource->slug) ?>" target="_blank"><i class="fas fa-external-link-alt me-1"></i><?php echo __('Open record') ?></a>
    <?php endif ?>
  </div>

  <p class="text-muted">
    <?php echo __('This report surfaces the machine provenance the platform records for this description: AI inferences that produced or enriched its metadata (each cryptographically signed at inference time) and any C2PA content credentials bound to its digital objects.') ?>
  </p>

  <div class="row mb-4">
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('AI inferences') ?></div><div class="display-6"><?php echo (int) ($sum['inferences'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Signed') ?></div><div class="display-6"><?php echo (int) ($sum['signed'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Verified') ?></div><div class="display-6 text-success"><?php echo (int) ($sum['verified'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('C2PA manifests') ?></div><div class="display-6"><?php echo (int) ($sum['c2pa'] ?? 0) ?></div></div></div></div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-robot me-2"></i><?php echo __('AI inference provenance') ?></h5></div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0 align-middle">
        <thead class="table-light"><tr>
          <th><?php echo __('Field') ?></th>
          <th><?php echo __('Service') ?></th>
          <th><?php echo __('Model') ?></th>
          <th><?php echo __('Confidence') ?></th>
          <th><?php echo __('Recorded') ?></th>
          <th><?php echo __('Authenticity') ?></th>
        </tr></thead>
        <tbody>
        <?php if (empty($a['inferences'])): ?>
          <tr><td colspan="6" class="text-muted p-3"><?php echo __('No AI inferences recorded for this record.') ?></td></tr>
        <?php else: foreach ($a['inferences'] as $r): ?>
          <tr>
            <td><code><?php echo esc_entities((string) ($r->target_field ?: '—')) ?></code></td>
            <td><?php echo esc_entities((string) $r->service_name) ?></td>
            <td><span title="<?php echo esc_entities((string) $r->model_version) ?>"><?php echo esc_entities((string) $r->model_name) ?><?php if ($r->model_version): ?> <span class="text-muted small">@<?php echo esc_entities((string) $r->model_version) ?></span><?php endif ?></span></td>
            <td><?php echo $r->confidence_pct === null ? '<span class="text-muted">—</span>' : (esc_entities((string) $r->confidence_pct) . '%') ?></td>
            <td class="small text-muted"><?php echo esc_entities((string) $r->occurred_at) ?></td>
            <td><?php echo $verdictBadge((string) $r->verdict) ?><?php if (!empty($r->signer_key_id)): ?> <span class="small text-muted" title="<?php echo __('Signing key') ?>"><?php echo esc_entities(substr((string) $r->signer_key_id, 0, 12)) ?></span><?php endif ?></td>
          </tr>
        <?php endforeach ?>
        <?php endif ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($a['inferences'])): ?>
      <div class="card-footer small text-muted">
        <?php echo __('“Verified” = the inference’s Ed25519 signature was re-checked against the current signing key and matches the recorded manifest. “Signed” = a signature is present but was minted by a rotated key (still tamper-evident, verify with the archived key). “Unsigned” = recorded before signing was enabled.') ?>
      </div>
    <?php endif ?>
  </div>

  <div class="card">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-certificate me-2"></i><?php echo __('C2PA content credentials') ?></h5></div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0 align-middle">
        <thead class="table-light"><tr>
          <th><?php echo __('Digital object') ?></th>
          <th><?php echo __('Manifest label') ?></th>
          <th><?php echo __('Signing key (kid)') ?></th>
          <th><?php echo __('Asset hash') ?></th>
          <th><?php echo __('Signed') ?></th>
        </tr></thead>
        <tbody>
        <?php if (empty($a['c2pa'])): ?>
          <tr><td colspan="5" class="text-muted p-3"><?php echo __('No C2PA content credentials bound to this record’s digital objects.') ?></td></tr>
        <?php else: foreach ($a['c2pa'] as $c): ?>
          <tr>
            <td>#<?php echo (int) $c->digital_object_id ?></td>
            <td><?php echo esc_entities((string) ($c->manifest_label ?: '—')) ?></td>
            <td><code class="small"><?php echo esc_entities((string) ($c->kid ?: '—')) ?></code></td>
            <td><code class="small"><?php echo esc_entities(substr((string) ($c->asset_hash ?? ''), 0, 16)) ?>…</code></td>
            <td class="small text-muted"><?php echo esc_entities((string) $c->created_at) ?></td>
          </tr>
        <?php endforeach ?>
        <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
