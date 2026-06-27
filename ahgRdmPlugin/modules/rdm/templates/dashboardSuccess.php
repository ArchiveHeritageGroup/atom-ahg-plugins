<?php decorate_with('layout_1col.php'); ?>

<?php
  $k = $d['kpi'];
  $verdictColors     = ['CLEAR' => '#198754', 'PERSONAL' => '#fd7e14', 'SPECIAL_CATEGORY' => '#dc3545', 'unscanned' => '#adb5bd'];
  $dispositionColors = ['restrict' => '#dc3545', 'embargo' => '#fd7e14', 'de-identify' => '#0dcaf0', 'release' => '#198754', 'undecided' => '#adb5bd'];
  $methodColors      = ['deterministic' => '#0d6efd', 'lexicon' => '#6f42c1', 'ner' => '#20c997'];
  $nonce = sfConfig::get('csp_nonce', '');
  $nonceAttr = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';
?>

<?php slot('title'); ?>
  <h1 class="h4 mb-0"><i class="fas fa-gauge-high me-2"></i>RDM Dashboard</h1>
<?php end_slot(); ?>

<div class="d-flex align-items-center justify-content-end mb-3 gap-2">
  <a href="<?php echo url_for('@rdm_datasets_compliance'); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-clipboard-check me-1"></i>Compliance scoreboard</a>
  <a href="<?php echo url_for('@rdm_datasets_index'); ?>" class="btn btn-outline-secondary btn-sm">All datasets</a>
  <a href="<?php echo url_for('@rdm_datasets_create'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New dataset</a>
</div>

<?php // Filters: deposit date range + faculty/institution ?>
<form method="get" action="<?php echo url_for('@rdm_datasets_dashboard'); ?>" class="row g-2 align-items-end mb-3">
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
    <label class="form-label small mb-0">Deposited from</label>
    <input type="date" name="from" value="<?php echo esc_specialchars($filters['from'] ?? ''); ?>" class="form-control form-control-sm">
  </div>
  <div class="col-md-3">
    <label class="form-label small mb-0">Deposited to</label>
    <input type="date" name="to" value="<?php echo esc_specialchars($filters['to'] ?? ''); ?>" class="form-control form-control-sm">
  </div>
  <div class="col-md-2 d-flex gap-1">
    <button class="btn btn-primary btn-sm flex-grow-1">Filter</button>
    <?php if (!empty($filters)): ?>
      <a href="<?php echo url_for('@rdm_datasets_dashboard'); ?>" class="btn btn-outline-secondary btn-sm" title="Clear filters"><i class="fas fa-times"></i></a>
    <?php endif; ?>
  </div>
</form>
<?php if (!empty($filters)): ?>
  <p class="small text-muted mb-2"><i class="fas fa-filter me-1"></i>Filtered view. <span class="text-muted">The 12-month deposit trend always shows the rolling year.</span></p>
<?php endif; ?>

<?php // Defensibility one-liner ?>
<div class="alert alert-light border d-flex flex-wrap gap-3 align-items-center small mb-3">
  <span><i class="fas fa-shield-halved text-success me-1"></i><strong><?php echo (int) $k['datasets']; ?></strong> datasets</span>
  <span class="text-danger"><strong><?php echo (int) $k['flagged']; ?></strong> POPIA-flagged</span>
  <span class="text-warning"><strong><?php echo (int) $k['restricted']; ?></strong> restricted/embargoed</span>
  <span class="text-info"><strong><?php echo (int) $k['dmp_linked']; ?></strong> DMP-linked (<?php echo (int) $k['dmp_pct']; ?>%)</span>
  <?php if ($k['backlog'] > 0): ?>
    <span class="ms-auto badge bg-warning text-dark"><i class="fas fa-gavel me-1"></i><?php echo (int) $k['backlog']; ?> awaiting human review</span>
  <?php else: ?>
    <span class="ms-auto badge bg-success"><i class="fas fa-check me-1"></i>No review backlog</span>
  <?php endif; ?>
</div>

<?php // KPI cards ?>
<?php
  $kpiCards = [
    ['datasets', 'Datasets', '#0d6efd', 'fa-database'],
    ['files', 'Files deposited', '#6c757d', 'fa-file'],
    ['flagged', 'POPIA-flagged', '#dc3545', 'fa-user-shield'],
    ['backlog', 'Awaiting review', '#fd7e14', 'fa-gavel'],
    ['restricted', 'Restricted', '#fd7e14', 'fa-lock'],
    ['open', 'Open access', '#198754', 'fa-lock-open'],
    ['dois', 'DOIs minted', '#0dcaf0', 'fa-fingerprint'],
    ['dmp_linked', 'DMP-linked', '#20c997', 'fa-clipboard-list'],
  ];
?>
<div class="row g-2 mb-3">
  <?php foreach ($kpiCards as $c): ?>
    <div class="col-6 col-md-3 col-xl">
      <div class="card h-100"><div class="card-body py-2 text-center">
        <div class="small text-muted"><i class="fas <?php echo $c[3]; ?> me-1"></i><?php echo $c[1]; ?></div>
        <div class="h4 mb-0" style="color:<?php echo $c[2]; ?>"><?php echo (int) $k[$c[0]]; ?></div>
      </div></div>
    </div>
  <?php endforeach; ?>
</div>

<?php // Charts row 1 ?>
<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card h-100"><div class="card-header fw-bold py-2 small">POPIA verdict</div>
    <div class="card-body"><canvas id="verdictChart" height="200"></canvas></div></div></div>
  <div class="col-md-4"><div class="card h-100"><div class="card-header fw-bold py-2 small">Access disposition</div>
    <div class="card-body"><canvas id="dispositionChart" height="200"></canvas></div></div></div>
  <div class="col-md-4"><div class="card h-100"><div class="card-header fw-bold py-2 small">Detection method <span class="text-muted">(rule vs AI)</span></div>
    <div class="card-body"><canvas id="methodChart" height="200"></canvas></div></div></div>
</div>

<?php // Charts row 2 ?>
<div class="row g-3 mb-3">
  <div class="col-md-6"><div class="card h-100"><div class="card-header fw-bold py-2 small">Findings by PII type</div>
    <div class="card-body"><canvas id="typeChart" height="220"></canvas></div></div></div>
  <div class="col-md-6"><div class="card h-100"><div class="card-header fw-bold py-2 small">Deposits (last 12 months)</div>
    <div class="card-body"><canvas id="trendChart" height="220"></canvas></div></div></div>
</div>

<?php // Per-faculty posture + gate backlog ?>
<div class="row g-3 mb-3">
  <div class="col-md-7"><div class="card h-100">
    <div class="card-header fw-bold py-2 small">Posture by faculty / institution</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0 align-middle">
        <thead><tr><th>Faculty</th><th class="text-end">Datasets</th><th class="text-end">Flagged</th><th class="text-end">DMP</th></tr></thead>
        <tbody>
          <?php if (empty($d['by_institution'])): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">No datasets yet.</td></tr>
          <?php else: foreach ($d['by_institution'] as $row): ?>
            <tr>
              <td class="small"><?php echo esc_specialchars($row->institution); ?></td>
              <td class="text-end"><?php echo (int) $row->total; ?></td>
              <td class="text-end"><?php echo ((int) $row->flagged > 0) ? '<span class="badge bg-danger">' . (int) $row->flagged . '</span>' : '<span class="text-muted">0</span>'; ?></td>
              <td class="text-end"><?php echo $d['has_dmp'] ? '<span class="text-info">' . (int) $row->dmp . '</span>' : '<span class="text-muted">—</span>'; ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div></div>
  <div class="col-md-5"><div class="card h-100">
    <div class="card-header fw-bold py-2 small"><i class="fas fa-gavel me-1"></i>Human-gate backlog</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0 align-middle">
        <thead><tr><th>Dataset</th><th>Verdict</th><th class="text-end">Pending</th></tr></thead>
        <tbody>
          <?php if (empty($d['backlog_list'])): ?>
            <tr><td colspan="3" class="text-center text-success py-3"><i class="fas fa-check-circle me-1"></i>Nothing awaiting review.</td></tr>
          <?php else: foreach ($d['backlog_list'] as $row): ?>
            <tr>
              <td class="small"><a href="<?php echo url_for('@rdm_datasets_show?id=' . $row->id); ?>"><?php echo esc_specialchars(mb_strimwidth((string) $row->title, 0, 32, '…')); ?></a></td>
              <td><?php echo $row->verdict ? '<span class="badge bg-' . ($row->verdict === 'SPECIAL_CATEGORY' ? 'danger' : 'warning') . ' text-dark">' . esc_specialchars($row->verdict) . '</span>' : '—'; ?></td>
              <td class="text-end"><span class="badge bg-warning text-dark"><?php echo (int) $row->pending; ?></span></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div></div>
</div>

<?php // Recent deposits ?>
<div class="card mb-3">
  <div class="card-header fw-bold py-2 small">Recent deposits</div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0 align-middle">
      <thead><tr><th>Dataset</th><th>Status</th><th>Verdict</th><th>Access</th><th>DOI</th><th>Deposited</th></tr></thead>
      <tbody>
        <?php if (empty($d['recent'])): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No deposits yet.</td></tr>
        <?php else: foreach ($d['recent'] as $row): ?>
          <tr>
            <td class="small"><a href="<?php echo url_for('@rdm_datasets_show?id=' . $row->id); ?>"><?php echo esc_specialchars(mb_strimwidth((string) $row->title, 0, 40, '…')); ?></a></td>
            <td class="small text-muted"><?php echo esc_specialchars((string) $row->status); ?></td>
            <td><?php echo $row->verdict ? '<span class="badge" style="background:' . ($verdictColors[$row->verdict] ?? '#6c757d') . '">' . esc_specialchars($row->verdict) . '</span>' : '<span class="text-muted small">not scanned</span>'; ?></td>
            <td class="small"><?php echo esc_specialchars($row->disposition ?? '—'); ?></td>
            <td class="small"><?php echo $row->doi ? '<code>' . esc_specialchars($row->doi) . '</code>' : '<span class="text-muted">—</span>'; ?></td>
            <td class="small text-muted"><?php echo esc_specialchars(substr((string) $row->created_at, 0, 10)); ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" <?php echo $nonceAttr; ?>></script>
<script <?php echo $nonceAttr; ?>>
(function () {
  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.size = 11;
  Chart.defaults.plugins.legend.position = 'bottom';

  const verdict     = <?php echo json_encode($d['verdict']); ?>;
  const disposition = <?php echo json_encode($d['disposition']); ?>;
  const byMethod    = <?php echo json_encode($d['findings_by_method']); ?>;
  const byType      = <?php echo json_encode($d['findings_by_type']); ?>;
  const trend       = <?php echo json_encode($d['deposits_by_month']); ?>;
  const vColors     = <?php echo json_encode($verdictColors); ?>;
  const dColors     = <?php echo json_encode($dispositionColors); ?>;
  const mColors     = <?php echo json_encode($methodColors); ?>;

  const pruneZero = (obj) => Object.fromEntries(Object.entries(obj).filter(([, v]) => Number(v) > 0));

  const doughnut = (id, dataObj, colorMap, fallback) => {
    const el = document.getElementById(id);
    if (!el) return;
    const data = pruneZero(dataObj);
    const labels = Object.keys(data);
    if (!labels.length) { el.parentNode.innerHTML = '<p class="text-muted small text-center mb-0 py-4">' + (fallback || 'No data') + '</p>'; return; }
    new Chart(el, {
      type: 'doughnut',
      data: { labels, datasets: [{ data: Object.values(data).map(Number),
        backgroundColor: labels.map((l) => (colorMap && colorMap[l]) || '#0d6efd') }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '55%' },
    });
  };

  doughnut('verdictChart', verdict, vColors, 'No datasets yet');
  doughnut('dispositionChart', disposition, dColors, 'No datasets yet');
  doughnut('methodChart', byMethod, mColors, 'No findings yet');

  const typeEl = document.getElementById('typeChart');
  if (typeEl) {
    const labels = Object.keys(byType);
    if (!labels.length) { typeEl.parentNode.innerHTML = '<p class="text-muted small text-center mb-0 py-4">No findings yet</p>'; }
    else new Chart(typeEl, {
      type: 'bar',
      data: { labels, datasets: [{ label: 'Findings', data: Object.values(byType).map(Number), backgroundColor: '#dc3545' }] },
      options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
    });
  }

  const trendEl = document.getElementById('trendChart');
  if (trendEl) new Chart(trendEl, {
    type: 'line',
    data: { labels: trend.map((p) => p.label),
      datasets: [{ label: 'Deposits', data: trend.map((p) => p.count), borderColor: '#0d6efd',
        backgroundColor: 'rgba(13,110,253,.1)', fill: true, tension: .3 }] },
    options: { responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
  });
})();
</script>
