<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1 class="h4 mb-0"><i class="fas fa-clipboard-check me-2"></i>RDM Compliance Scoreboard</h1>
<?php end_slot(); ?>

<div class="d-flex align-items-center justify-content-end mb-3 gap-2">
  <a href="<?php echo url_for('@rdm_datasets_dashboard'); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-gauge-high me-1"></i>Dashboard</a>
  <a href="<?php echo url_for('@rdm_datasets_index'); ?>" class="btn btn-outline-secondary btn-sm">All datasets</a>
</div>

<?php // Summary strip ?>
<?php
  $cards = [
    ['total', 'Datasets', '#0d6efd'],
    ['flagged', 'POPIA-flagged', '#dc3545'],
    ['restricted', 'Restricted/embargoed', '#fd7e14'],
    ['open', 'Open (published)', '#198754'],
    ['unreviewed', 'Awaiting review', '#6c757d'],
    ['dmp_linked', 'DMP-linked', '#0dcaf0'],
  ];
?>
<div class="row g-2 mb-3">
  <?php foreach ($cards as $c): ?>
    <div class="col">
      <div class="card text-center"><div class="card-body py-2">
        <div class="h4 mb-0" style="color:<?php echo $c[2]; ?>"><?php echo (int) ($summary[$c[0]] ?? 0); ?></div>
        <div class="small text-muted"><?php echo $c[1]; ?></div>
      </div></div>
    </div>
  <?php endforeach; ?>
</div>

<?php // Filters ?>
<form method="get" action="<?php echo url_for('@rdm_datasets_compliance'); ?>" class="row g-2 align-items-end mb-3">
  <div class="col-md-4">
    <label class="form-label small mb-0">Faculty / institution</label>
    <select name="institution" class="form-select form-select-sm">
      <option value="">All</option>
      <?php foreach ($institutions as $inst): ?>
        <option value="<?php echo esc_specialchars($inst); ?>" <?php echo (($filters['institution'] ?? '') === $inst) ? 'selected' : ''; ?>><?php echo esc_specialchars($inst); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label small mb-0">POPIA verdict</label>
    <select name="verdict" class="form-select form-select-sm">
      <option value="">All</option>
      <?php foreach (['CLEAR', 'PERSONAL', 'SPECIAL_CATEGORY'] as $v): ?>
        <option value="<?php echo $v; ?>" <?php echo (($filters['verdict'] ?? '') === $v) ? 'selected' : ''; ?>><?php echo $v; ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label small mb-0">Disposition</label>
    <select name="disposition" class="form-select form-select-sm">
      <option value="">All</option>
      <?php foreach (['restrict', 'embargo', 'de-identify', 'release'] as $v): ?>
        <option value="<?php echo $v; ?>" <?php echo (($filters['disposition'] ?? '') === $v) ? 'selected' : ''; ?>><?php echo $v; ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2"><button class="btn btn-primary btn-sm w-100">Filter</button></div>
</form>

<?php
  $verdictColor = ['CLEAR' => 'success', 'PERSONAL' => 'warning', 'SPECIAL_CATEGORY' => 'danger'];
  $accessColor = ['release' => 'success', 'embargo' => 'warning', 'restrict' => 'danger', 'de-identify' => 'info'];
?>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-sm mb-0 align-middle">
      <thead><tr>
        <th>Dataset</th><th>Faculty</th><th>POPIA</th><th>Findings</th><th>Access</th><th>DOI</th><th>DMP/Project</th>
      </tr></thead>
      <tbody>
        <?php if (count($rows) === 0): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No datasets match.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><a href="<?php echo url_for('@rdm_datasets_show?id=' . $r->id); ?>"><?php echo esc_specialchars($r->title); ?></a></td>
              <td class="small text-muted"><?php echo esc_specialchars($r->institution ?? '—'); ?></td>
              <td>
                <?php if (!empty($r->verdict)): ?>
                  <span class="badge bg-<?php echo $verdictColor[$r->verdict] ?? 'secondary'; ?>"><?php echo esc_specialchars($r->verdict); ?></span>
                <?php else: ?>
                  <span class="text-muted small">not scanned</span>
                <?php endif; ?>
              </td>
              <td class="small">
                <?php echo (int) $r->findings; ?>
                <?php if ((int) $r->pending > 0): ?>
                  <span class="text-warning">(<?php echo (int) $r->pending; ?> pending)</span>
                <?php elseif ((int) $r->confirmed > 0): ?>
                  <span class="text-danger">(<?php echo (int) $r->confirmed; ?> confirmed)</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($r->disposition)): ?>
                  <span class="badge bg-<?php echo $accessColor[$r->disposition] ?? 'secondary'; ?>"><?php echo esc_specialchars($r->disposition); ?></span>
                <?php else: ?>
                  <span class="badge bg-light text-dark"><?php echo esc_specialchars($r->status); ?></span>
                <?php endif; ?>
              </td>
              <td class="small">
                <?php if (!empty($r->doi)): ?>
                  <a href="https://doi.org/<?php echo esc_specialchars($r->doi); ?>" target="_blank" rel="noopener"><code><?php echo esc_specialchars($r->doi); ?></code></a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="small">
                <?php if (!empty($r->dmp_id)): ?>
                  <span class="badge bg-info text-dark" title="<?php echo esc_specialchars($r->dmp_title ?? ''); ?>"><i class="fas fa-clipboard-list me-1"></i>DMP: <?php echo esc_specialchars($r->dmp_status ?? ''); ?></span>
                  <?php if (!empty($r->project_title)): ?><div class="text-muted"><?php echo esc_specialchars(mb_strimwidth($r->project_title, 0, 24, '…')); ?></div><?php endif; ?>
                <?php elseif (!empty($r->project_title)): ?>
                  <?php echo esc_specialchars(mb_strimwidth($r->project_title, 0, 28, '…')); ?>
                  <div><span class="badge bg-light text-dark border">no DMP</span></div>
                <?php else: ?>
                  <span class="text-muted">unlinked</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
