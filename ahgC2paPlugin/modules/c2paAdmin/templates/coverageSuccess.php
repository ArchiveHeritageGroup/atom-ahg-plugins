<?php decorate_with('layout_1col.php'); ?>
<?php
/*
 * Authenticity-coverage dashboard (staff): how much of the digitised
 * collection carries signed content credentials, and where the gaps are.
 * PSIS twin of Heratio resources/views/coverage.blade.php. Progress-bar widths
 * come from a nonce'd <style> block, since a CSP nonce never covers a style
 * attribute.
 */
$r = $sf_data->getRaw('report');
$h = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$clamp = static fn ($v): float => max(0.0, min(100.0, (float) $v));
$totalMasters = (int) $r['total_masters'];
$coveragePct = (float) $r['coverage_pct'];
$breakdown = $r['breakdown'];
?>
<?php slot('title'); ?>
<h1><i class="fas fa-shield-alt me-2" aria-hidden="true"></i><?php echo __('Authenticity Coverage'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  .c2pa-bar-lg { height: 1.5rem; }
  .c2pa-bar-sm { height: .5rem; }
  .c2pa-w-total { width: <?php echo $clamp($coveragePct); ?>%; }
<?php foreach ($breakdown as $i => $row) { ?>
  .c2pa-w-<?php echo (int) $i; ?> { width: <?php echo $clamp($row['coverage_pct']); ?>%; }
<?php } ?>
</style>

<p class="text-muted"><?php echo __('How much of your digitised collection carries verifiable content credentials, and where the gaps are. Use this to plan which holdings still need signing.'); ?></p>

<?php if (!$r['can_sign']) { ?>
  <div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i><?php echo $h($r['capability_summary']); ?></div>
<?php } ?>

<?php if ($totalMasters <= 0) { ?>
  <div class="card mb-3"><div class="card-body text-center text-muted py-5">
    <i class="fas fa-shield-alt fa-2x mb-3 d-block" aria-hidden="true"></i>
    <?php if (!$r['enabled']) { ?>
      <p class="mb-0"><?php echo __('The content-credentials provenance table is not installed yet (ahgC2paPlugin database/install.sql).'); ?></p>
    <?php } else { ?>
      <p class="mb-0"><?php echo __('No master digital objects found to measure.'); ?></p>
    <?php } ?>
  </div></div>
<?php } else { ?>
  <div class="card mb-4 border-2 border-success-subtle">
    <div class="card-body p-4">
      <div class="row align-items-center g-3">
        <div class="col-md-3 text-center">
          <div class="display-3 fw-bold text-success"><?php echo number_format($coveragePct, 1); ?>%</div>
          <div class="text-muted"><?php echo __('of master files verifiable'); ?></div>
        </div>
        <div class="col-md-9">
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span><?php echo __('Signed master files'); ?>: <strong><?php echo number_format((int) $r['covered_masters']); ?></strong></span>
            <span><?php echo __('Total master files'); ?>: <strong><?php echo number_format($totalMasters); ?></strong></span>
          </div>
          <div class="progress c2pa-bar-lg mb-2" role="progressbar" aria-label="<?php echo $h(__('Collection coverage')); ?>" aria-valuenow="<?php echo (int) round($clamp($coveragePct)); ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar bg-success c2pa-w-total"><?php echo $coveragePct >= 8 ? number_format($coveragePct, 1) . '%' : ''; ?></div>
          </div>
          <p class="small text-muted mb-0"><i class="fas fa-exclamation-circle me-1" aria-hidden="true"></i><?php echo number_format((int) $r['uncovered_masters']); ?> <?php echo __('master files are not yet covered by a signed content credential. Run php symfony c2pa:provenance-backfill to close the gap.'); ?></p>
        </div>
      </div>
    </div>
  </div>
<?php } ?>

<div class="row g-3 mb-4">
  <?php foreach ([
      ['signed_records', 'text-success', __('signed provenance records')],
      ['unsigned_records', 'text-warning', __('unsigned provenance records')],
      ['manifests_total', '', __('manifests issued (all kinds)')],
      ['ai_manifests', '', __('AI-output manifests')],
  ] as [$key, $cls, $label]) { ?>
    <div class="col-6 col-lg-3">
      <div class="card h-100 text-center"><div class="card-body">
        <div class="display-6 <?php echo $cls; ?>"><?php echo number_format((int) $r[$key]); ?></div>
        <div class="small text-muted"><?php echo $h($label); ?></div>
      </div></div>
    </div>
  <?php } ?>
</div>

<?php if (!empty($r['last_signed_at']) || !empty($r['issuers'])) { ?>
  <div class="card mb-4">
    <div class="card-header"><i class="fas fa-key me-2" aria-hidden="true"></i><?php echo __('Signing'); ?></div>
    <div class="card-body small">
      <?php if (!empty($r['last_signed_at'])) { ?><p class="mb-2"><?php echo __('Most recent provenance signature'); ?>: <strong><?php echo $h($r['last_signed_at']); ?></strong></p><?php } ?>
      <?php if (!empty($r['issuers'])) { ?>
        <p class="mb-1"><?php echo __('Signing keys in use'); ?>:</p>
        <ul class="mb-0"><?php foreach ($r['issuers'] as $iss) { ?><li><code><?php echo $h($iss['kid'] ?: '-'); ?></code> - <?php echo (int) $iss['count']; ?></li><?php } ?></ul>
      <?php } ?>
    </div>
  </div>
<?php } ?>

<?php if (!empty($breakdown)) { ?>
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-university me-2" aria-hidden="true"></i><?php echo __('Coverage by holding repository'); ?></div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead><tr><th><?php echo __('Repository'); ?></th><th class="text-end"><?php echo __('Master files'); ?></th><th class="text-end"><?php echo __('Signed'); ?></th><th class="w-25"><?php echo __('Coverage'); ?></th></tr></thead>
        <tbody>
          <?php foreach ($breakdown as $i => $row) { ?>
            <tr>
              <td><?php echo $h($row['label']); ?></td>
              <td class="text-end"><?php echo number_format((int) $row['masters']); ?></td>
              <td class="text-end"><?php echo number_format((int) $row['covered']); ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="progress c2pa-bar-sm flex-grow-1" role="progressbar" aria-label="<?php echo $h($row['label']); ?>" aria-valuenow="<?php echo (int) round($clamp($row['coverage_pct'])); ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-success c2pa-w-<?php echo (int) $i; ?>"></div>
                  </div>
                  <span class="small text-muted"><?php echo number_format((float) $row['coverage_pct'], 1); ?>%</span>
                </div>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <?php if ($r['breakdown_truncated']) { ?><div class="card-footer small text-muted"><?php echo __('Showing the 25 largest repositories.'); ?></div><?php } ?>
  </div>
<?php } ?>

<p class="small text-muted"><a href="<?php echo url_for('@ahg_c2pa_check'); ?>"><i class="fas fa-external-link-alt me-1" aria-hidden="true"></i><?php echo __('Public check tool'); ?></a></p>
<?php end_slot(); ?>
