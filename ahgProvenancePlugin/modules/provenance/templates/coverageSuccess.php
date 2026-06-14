<?php
$r = $sf_data->getRaw('report') ?: [];
$gaps = $sf_data->getRaw('gaps') ?: [];
$uncovered = $sf_data->getRaw('uncovered') ?: [];
$nazi = $r['nazi_era'] ?? [];
$pct = (int) ($r['coverage_pct'] ?? 0);
function prov_chips($map)
{
    if (empty($map)) { echo '<span class="text-muted">—</span>'; return; }
    foreach ($map as $k => $v) {
        echo '<span class="badge bg-light text-dark border me-1 mb-1">' . htmlspecialchars(ucwords(str_replace('_', ' ', (string) $k))) . ': <strong>' . (int) $v . '</strong></span>';
    }
}
?>
<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-diagram-project me-2"></i><?php echo __('Provenance Coverage'); ?></h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for(['module' => 'provenance', 'action' => 'index']); ?>"><i class="fas fa-arrow-left me-1"></i><?php echo __('Provenance'); ?></a>
  </div>

  <div class="row mb-3">
    <div class="col-md-3 mb-3"><div class="card h-100"><div class="card-body">
      <div class="text-muted small text-uppercase"><?php echo __('Catalogue coverage'); ?></div>
      <div class="display-6"><?php echo $pct; ?>%</div>
      <div class="progress mt-2" style="height:8px"><div class="progress-bar<?php echo $pct < 50 ? ' bg-danger' : ($pct < 80 ? ' bg-warning' : ' bg-success'); ?>" style="width:<?php echo $pct; ?>%"></div></div>
      <div class="small text-muted mt-1"><?php echo (int) ($r['with_provenance'] ?? 0); ?> / <?php echo (int) ($r['published_total'] ?? 0); ?> <?php echo __('published records'); ?></div>
    </div></div></div>
    <div class="col-md-3 mb-3"><div class="card h-100 border-warning"><div class="card-body">
      <div class="text-muted small text-uppercase"><?php echo __('Recorded gaps'); ?></div>
      <div class="display-6"><?php echo (int) ($r['with_gaps'] ?? 0); ?></div>
      <div class="small text-muted"><?php echo (int) ($r['incomplete'] ?? 0); ?> <?php echo __('incomplete'); ?></div>
    </div></div></div>
    <div class="col-md-3 mb-3"><div class="card h-100<?php echo ($nazi['flagged_unclear'] ?? 0) ? ' border-danger' : ''; ?>"><div class="card-body">
      <div class="text-muted small text-uppercase"><?php echo __('Nazi-era diligence'); ?></div>
      <div class="display-6"><?php echo (int) ($nazi['unchecked'] ?? 0); ?></div>
      <div class="small text-muted"><?php echo __('unchecked'); ?> · <?php echo (int) ($nazi['flagged_unclear'] ?? 0); ?> <?php echo __('flagged unclear'); ?></div>
    </div></div></div>
    <div class="col-md-3 mb-3"><div class="card h-100"><div class="card-body">
      <div class="text-muted small text-uppercase"><?php echo __('Provenance records'); ?></div>
      <div class="display-6"><?php echo (int) ($r['records_total'] ?? 0); ?></div>
    </div></div></div>
  </div>

  <div class="row">
    <div class="col-md-6 mb-3"><div class="card h-100"><div class="card-body">
      <h6><?php echo __('By certainty'); ?></h6><?php prov_chips($r['by_certainty'] ?? []); ?>
      <h6 class="mt-3"><?php echo __('By research status'); ?></h6><?php prov_chips($r['by_research_status'] ?? []); ?>
    </div></div></div>
    <div class="col-md-6 mb-3"><div class="card h-100"><div class="card-body">
      <h6><?php echo __('Cultural property status'); ?></h6><?php prov_chips($r['cultural_property'] ?? []); ?>
      <p class="text-muted small mt-3 mb-0"><?php echo __('Export the same data as JSON:'); ?>
        <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'apiCoverage']); ?>"><code>/provenance/coverage-data</code></a></p>
    </div></div></div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-triangle-exclamation me-2 text-warning"></i><?php echo __('Records with provenance gaps'); ?> <span class="badge bg-secondary"><?php echo count($gaps); ?></span></h5></div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th><?php echo __('Object'); ?></th><th><?php echo __('Certainty'); ?></th><th><?php echo __('Status'); ?></th><th><?php echo __('Gap'); ?></th></tr></thead>
        <tbody>
        <?php if (empty($gaps)): ?>
          <tr><td colspan="4" class="text-muted p-3"><?php echo __('No gaps flagged.'); ?></td></tr>
        <?php else: foreach ($gaps as $g): ?>
          <tr>
            <td><?php if (!empty($g['slug'])): ?><a href="<?php echo url_for(['module' => 'provenance', 'action' => 'view', 'slug' => $g['slug']]); ?>">#<?php echo (int) $g['information_object_id']; ?></a><?php else: ?>#<?php echo (int) $g['information_object_id']; ?><?php endif; ?></td>
            <td><?php echo htmlspecialchars((string) ($g['certainty_level'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string) ($g['current_status'] ?? '')); ?></td>
            <td class="small"><?php echo htmlspecialchars((string) ($g['gap_description'] ?? '')); ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mb-5">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-circle-question me-2"></i><?php echo __('Published records with NO provenance'); ?> <span class="badge bg-secondary"><?php echo count($uncovered); ?></span></h5></div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th><?php echo __('ID'); ?></th><th><?php echo __('Title'); ?></th></tr></thead>
        <tbody>
        <?php if (empty($uncovered)): ?>
          <tr><td colspan="2" class="text-muted p-3"><?php echo __('Every published record has a provenance record.'); ?></td></tr>
        <?php else: foreach ($uncovered as $u): ?>
          <tr>
            <td><?php if (!empty($u['slug'])): ?><a href="<?php echo url_for(['module' => 'provenance', 'action' => 'view', 'slug' => $u['slug']]); ?>"><?php echo (int) $u['id']; ?></a><?php else: ?><?php echo (int) $u['id']; ?><?php endif; ?></td>
            <td><?php echo htmlspecialchars((string) ($u['title'] ?? '')); ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
