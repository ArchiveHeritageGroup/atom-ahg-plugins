<?php decorate_with('layout_1col.php') ?>
<?php
$t = $sf_data->getRaw('triage');
$f = $sf_data->getRaw('filters');
$totalRows = $sf_data->getRaw('totalRows');
$pageRows = $sf_data->getRaw('pageRows');
$h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$c = $t['counts'];
$w = $t['weights'];
$query = array_filter(['repository_id' => $f['repository_id'] ?: null, 'collection_id' => $f['collection_id'] ?: null, 'years' => $f['years']]);
$recordUrl = function ($slug) { return $slug ? url_for(['module' => 'informationobject', 'slug' => $slug]) : null; };
$forecastMax = max(1, max($t['forecastByMonth'] ?: [0]));
$sourceNotes = [];
foreach ($t['sources'] as $table => $s) {
    if (null === $s['count']) {
        $sourceNotes[] = __('%1% is not installed on this instance (table %2% missing); its signals are left out of the score.', ['%1%' => $s['label'], '%2%' => $table]);
    } elseif (0 === $s['count']) {
        $sourceNotes[] = __('%1% is empty (table %2%); no record can score on it yet.', ['%1%' => $s['label'], '%2%' => $table]);
    }
}
?>
<?php slot('title') ?>
<h1><i class="fas fa-triangle-exclamation text-primary me-2"></i><?php echo __('Preservation triage'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<p class="text-muted">
  <?php echo __('Ranks every record that has condition data or digital files by how much conservation and digital-preservation attention it needs, from condition assessments, fixity checks, format risk, virus scans and the age of the last assessment or check. Read-only.'); ?>
</p>

<form method="get" action="<?php echo url_for(['module' => 'preservationTriage', 'action' => 'index']); ?>" class="row g-2 align-items-end mb-4">
  <div class="col-md-4">
    <label class="form-label" for="triage-repository"><?php echo __('Repository'); ?></label>
    <select class="form-select" id="triage-repository" name="repository_id">
      <option value=""><?php echo __('All repositories'); ?></option>
      <?php foreach ($t['repositories'] as $id => $name): ?>
      <option value="<?php echo (int) $id; ?>"<?php echo (int) $id === $f['repository_id'] ? ' selected' : ''; ?>><?php echo $h($name); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="triage-collection"><?php echo __('Top-level collection'); ?></label>
    <select class="form-select" id="triage-collection" name="collection_id">
      <option value=""><?php echo __('All collections'); ?></option>
      <?php foreach ($t['collections'] as $id => $title): ?>
      <option value="<?php echo (int) $id; ?>"<?php echo (int) $id === $f['collection_id'] ? ' selected' : ''; ?>><?php echo $h($title); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label" for="triage-years"><?php echo __('Age threshold (years)'); ?></label>
    <input class="form-control" type="number" min="1" max="50" id="triage-years" name="years" value="<?php echo (int) $f['years']; ?>">
  </div>
  <div class="col-md-2 d-flex gap-2">
    <button type="submit" class="btn btn-primary"><?php echo __('Apply'); ?></button>
    <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'preservationTriage', 'action' => 'export']).($query ? '?'.http_build_query($query) : ''); ?>"><i class="fas fa-file-csv me-1"></i><?php echo __('CSV'); ?></a>
  </div>
</form>

<?php if ($sourceNotes): ?>
<div class="alert alert-info">
  <strong><?php echo __('Data available on this instance'); ?></strong>
  <ul class="mb-0">
    <?php foreach ($sourceNotes as $note): ?><li><?php echo $h($note); ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php
$tiles = [
    ['in_scope', __('Records in scope'), 'secondary'],
    ['needs_attention', __('Records scoring above zero'), 'primary'],
    ['condition_poor', __('Condition poor or worse'), 'danger'],
    ['overdue', __('Assessments overdue'), 'warning'],
    ['stale_assessment', __('Assessed over %1% years ago', ['%1%' => $t['ageYears']]), 'warning'],
    ['fixity_failed', __('Records with failed fixity'), 'danger'],
    ['format_at_risk', __('Records in high/critical-risk formats'), 'warning'],
    ['infected', __('Records with an infected file'), 'danger'],
    ['no_checksum', __('Records with unchecksummed files'), 'secondary'],
    ['never_verified', __('Checksummed but never fixity-checked'), 'secondary'],
    ['forecast', __('Passing %1% years in the next 12 months', ['%1%' => $t['ageYears']]), 'info'],
];
?>
<div class="row row-cols-2 row-cols-md-4 row-cols-xl-6 g-3 mb-4">
  <?php foreach ($tiles as [$key, $label, $tone]): ?>
  <div class="col">
    <div class="card h-100 text-center">
      <div class="card-body">
        <div class="fs-3 fw-bold text-<?php echo $c[$key] > 0 ? $tone : 'muted'; ?>"><?php echo number_format($c[$key]); ?></div>
        <div class="small"><?php echo $label; ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between">
    <span><i class="fas fa-list-ol me-2"></i><?php echo __('Priority list'); ?></span>
    <span class="text-muted small"><?php echo $totalRows > $pageRows ? __('Top %1% of %2%; the CSV has all of them', ['%1%' => $pageRows, '%2%' => number_format($totalRows)]) : __('%1% records', ['%1%' => number_format($totalRows)]); ?></span>
  </div>
  <?php if (!$t['rows']): ?>
  <div class="card-body text-muted"><?php echo __('No record scores above zero with the current filters.'); ?></div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th><?php echo __('Score'); ?></th>
          <th><?php echo __('Record'); ?></th>
          <th><?php echo __('Repository / collection'); ?></th>
          <th><?php echo __('Why'); ?></th>
          <th><?php echo __('Last assessed'); ?></th>
          <th><?php echo __('Last fixity'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($t['rows'] as $i => $r): ?>
        <tr>
          <td><?php echo $i + 1; ?></td>
          <td><span class="badge bg-<?php echo $r['score'] >= 50 ? 'danger' : ($r['score'] >= 25 ? 'warning text-dark' : 'secondary'); ?>"><?php echo (int) $r['score']; ?></span></td>
          <td>
            <?php if ($url = $recordUrl($r['slug'])): ?><a href="<?php echo $url; ?>"><?php echo $h($r['title']); ?></a><?php else: ?><?php echo $h($r['title']); ?><?php endif; ?>
            <?php if ($r['identifier']): ?><div class="small text-muted"><?php echo $h($r['identifier']); ?></div><?php endif; ?>
          </td>
          <td class="small"><?php echo $h($r['repository']); ?><?php if ($r['collection']): ?><div class="text-muted"><?php echo $h($r['collection']); ?></div><?php endif; ?></td>
          <td class="small"><ul class="mb-0 ps-3"><?php foreach ($r['reasons'] as $reason): ?><li><?php echo $h($reason); ?></li><?php endforeach; ?></ul></td>
          <td class="small text-nowrap"><?php echo $h($r['last_assessment'] ?: __('never')); ?></td>
          <td class="small text-nowrap"><?php echo $h($r['last_fixity'] ?: __('never')); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="fas fa-calendar-days me-2"></i><?php echo __('Forecast: assessments and fixity checks passing %1% years, next 12 months', ['%1%' => $t['ageYears']]); ?></div>
      <div class="card-body">
        <table class="table table-sm mb-3">
          <tbody>
            <?php foreach ($t['forecastByMonth'] as $month => $n): ?>
            <tr>
              <td class="text-nowrap"><?php echo $h($month); ?></td>
              <td><meter class="w-100" min="0" max="<?php echo (int) $forecastMax; ?>" value="<?php echo (int) $n; ?>"><?php echo (int) $n; ?></meter></td>
              <td class="text-end"><?php echo (int) $n; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (!$t['forecast']): ?>
        <p class="text-muted mb-0"><?php echo __('Nothing becomes older than %1% years in the next 12 months. Records never assessed or never checked are not in the forecast; they already count above.', ['%1%' => $t['ageYears']]); ?></p>
        <?php else: ?>
        <ul class="small mb-0">
          <?php foreach ($t['forecast'] as $fc): ?>
          <li><?php echo $h($fc['due']); ?> - <?php if ($url = $recordUrl($fc['slug'])): ?><a href="<?php echo $url; ?>"><?php echo $h($fc['title']); ?></a><?php else: ?><?php echo $h($fc['title']); ?><?php endif; ?> (<?php echo $h($fc['what']); ?>, <?php echo __('last'); ?> <?php echo $h($fc['last']); ?>)</li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="fas fa-scale-balanced me-2"></i><?php echo __('How the score is worked out'); ?></div>
      <div class="card-body small">
        <p><?php echo __('A record\'s score is the sum of the points below. Each point-earning finding is listed beside the record, so any score can be checked by hand. Ties are broken by the oldest assessment first.'); ?></p>
        <table class="table table-sm">
          <tbody>
            <tr><td><?php echo __('Latest condition: unacceptable or critical / poor / fair'); ?></td><td class="text-end text-nowrap"><?php echo $w['condition_unacceptable'].' / '.$w['condition_poor'].' / '.$w['condition_fair']; ?></td></tr>
            <tr><td><?php echo __('Treatment priority on latest assessment: urgent / high'); ?></td><td class="text-end text-nowrap"><?php echo $w['priority_urgent'].' / '.$w['priority_high']; ?></td></tr>
            <tr><td><?php echo __('Active severe or critical damage entry on the latest condition report (each, capped)'); ?></td><td class="text-end text-nowrap"><?php echo $w['damage_severe'].' ('.__('max').' '.$w['damage_cap'].')'; ?></td></tr>
            <tr><td><?php echo __('Next check date or schedule due date has passed'); ?></td><td class="text-end"><?php echo $w['assessment_overdue']; ?></td></tr>
            <tr><td><?php echo __('Latest assessment older than %1% years', ['%1%' => $t['ageYears']]); ?></td><td class="text-end"><?php echo $w['assessment_stale']; ?></td></tr>
            <tr><td><?php echo __('Failed or missing-file fixity check (each, capped)'); ?></td><td class="text-end text-nowrap"><?php echo $w['fixity_failed'].' ('.__('max').' '.$w['fixity_failed_cap'].')'; ?></td></tr>
            <tr><td><?php echo __('Fixity check could not complete'); ?></td><td class="text-end"><?php echo $w['fixity_error']; ?></td></tr>
            <tr><td><?php echo __('Latest virus scan of a file: threat found / scan error'); ?></td><td class="text-end text-nowrap"><?php echo $w['virus_infected'].' / '.$w['virus_error']; ?></td></tr>
            <tr><td><?php echo __('Worst format risk among the files: critical / high / medium'); ?></td><td class="text-end text-nowrap"><?php echo $w['format_critical'].' / '.$w['format_high'].' / '.$w['format_medium']; ?></td></tr>
            <tr><td><?php echo __('A master file has no stored checksum'); ?></td><td class="text-end"><?php echo $w['no_checksum']; ?></td></tr>
            <tr><td><?php echo __('Checksummed but never fixity-checked, or last checked over %1% years ago', ['%1%' => $t['ageYears']]); ?></td><td class="text-end"><?php echo $w['fixity_stale']; ?></td></tr>
          </tbody>
        </table>
        <p class="mb-0 text-muted">
          <?php echo __('Latest condition comes from the newest condition report or completed Spectrum condition check (scheduled or pending checks are ignored). Format risk is the registry level for the identified format, raised by a format obsolescence assessment where one exists. The age threshold defaults to the AHG setting preservation_triage_age_years, else 5 years, and can be changed above for this view. Weights are fixed in PreservationTriageService::WEIGHTS.'); ?>
        </p>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header"><i class="fas fa-database me-2"></i><?php echo __('Data sources'); ?></div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <tbody>
        <?php foreach ($t['sources'] as $table => $s): ?>
        <tr>
          <td><?php echo $h($s['label']); ?></td>
          <td class="small text-muted"><code><?php echo $h($table); ?></code></td>
          <td class="text-end"><?php echo null === $s['count'] ? '<span class="badge bg-secondary">'.__('not installed').'</span>' : number_format($s['count']); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php end_slot() ?>
