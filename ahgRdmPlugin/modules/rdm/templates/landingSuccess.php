<?php decorate_with('layout_1col.php'); ?>

<?php
  // Access status from the human-gate disposition / lifecycle status.
  $disposition = $dataset->disposition ?? null;
  if ($dataset->status === 'published' || $disposition === 'release') {
      $access = ['Open access', '#198754', 'fa-lock-open'];
  } elseif ($disposition === 'embargo') {
      $access = ['Embargoed', '#fd7e14', 'fa-hourglass-half'];
  } elseif (in_array($disposition, ['restrict', 'de-identify'], true)) {
      $access = ['Restricted', '#dc3545', 'fa-lock'];
  } else {
      $access = ['Not yet released', '#6c757d', 'fa-clock'];
  }
  $isOpen = $access[0] === 'Open access';
  $publisher = 'The Archive and Heritage Group';
?>

<?php slot('title'); ?>
  <h1 class="visually-hidden"><?php echo esc_specialchars($dataset->title); ?></h1>
<?php end_slot(); ?>

<div class="card">
  <div class="card-body">
    <div class="d-flex align-items-start justify-content-between">
      <h1 class="h4 mb-1"><i class="fas fa-database me-2"></i><?php echo esc_specialchars($dataset->title); ?></h1>
      <span class="badge" style="background:<?php echo $access[1]; ?>">
        <i class="fas <?php echo $access[2]; ?> me-1"></i><?php echo $access[0]; ?>
      </span>
    </div>
    <p class="text-muted small mb-3">
      Research dataset<?php echo !empty($dataset->project_title) ? ' · ' . esc_specialchars($dataset->project_title) : ''; ?>
      · <?php echo (int) $fileCount; ?> file(s)
    </p>

    <?php if (!empty($dataset->description)): ?>
      <p><?php echo nl2br(esc_specialchars($dataset->description)); ?></p>
    <?php endif; ?>

    <?php // DataCite-style citation ?>
    <div class="border rounded p-3 bg-light small mb-3">
      <div class="fw-bold mb-1">Cite this dataset</div>
      <?php echo esc_specialchars($publisher); ?> (<?php echo esc_specialchars((string) $year); ?>).
      <em><?php echo esc_specialchars($dataset->title); ?></em>. <?php echo esc_specialchars($publisher); ?>.
      <?php if (!empty($dataset->doi)): ?>
        <a href="<?php echo esc_specialchars($doiUrl); ?>" target="_blank" rel="noopener"><?php echo esc_specialchars($doiUrl); ?></a>
      <?php else: ?>
        <span class="text-muted">(DOI assigned on release)</span>
      <?php endif; ?>
    </div>

    <?php if (!empty($dataset->doi)): ?>
      <p class="small mb-2"><span class="text-muted">DOI:</span> <code><?php echo esc_specialchars($dataset->doi); ?></code></p>
    <?php endif; ?>

    <?php if (!empty($dmp['linked'])): ?>
      <p class="small mb-2">
        <span class="text-muted"><i class="fas fa-clipboard-list me-1"></i>Data management:</span>
        Governed by a Data Management Plan
        <span class="badge bg-light text-dark border"><?php echo esc_specialchars($dmp['linked']->status ?? 'draft'); ?></span>
      </p>
    <?php endif; ?>

    <?php if ($isOpen): ?>
      <div class="alert alert-success py-2 small mb-0"><i class="fas fa-lock-open me-1"></i>This dataset is openly available. Sign in to access the files.</div>
    <?php elseif ($access[0] === 'Embargoed'): ?>
      <div class="alert alert-warning py-2 small mb-0"><i class="fas fa-hourglass-half me-1"></i>This dataset is under embargo. Metadata is public; files are not yet available.</div>
    <?php elseif ($access[0] === 'Restricted'): ?>
      <div class="alert alert-danger py-2 small mb-0"><i class="fas fa-lock me-1"></i>Access to the files is restricted (POPIA / personal data). Contact the repository for mediated access.</div>
    <?php else: ?>
      <div class="alert alert-secondary py-2 small mb-0">This dataset has not been released yet.</div>
    <?php endif; ?>
  </div>
</div>
