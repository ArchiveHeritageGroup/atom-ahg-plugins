<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo esc_specialchars($dataset->title); ?></h1>
<?php end_slot(); ?>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<dl class="row">
  <dt class="col-sm-3">Status</dt>
  <dd class="col-sm-9"><span class="badge bg-secondary"><?php echo esc_specialchars($dataset->status); ?></span></dd>

  <?php if (!empty($dataset->project_title)): ?>
    <dt class="col-sm-3">Project</dt>
    <dd class="col-sm-9"><?php echo esc_specialchars($dataset->project_title); ?></dd>
  <?php endif; ?>

  <?php if (!empty($dataset->description)): ?>
    <dt class="col-sm-3">Description</dt>
    <dd class="col-sm-9"><?php echo nl2br(esc_specialchars($dataset->description)); ?></dd>
  <?php endif; ?>

  <dt class="col-sm-3">Container record</dt>
  <dd class="col-sm-9">information_object #<?php echo (int) $dataset->io_parent_id; ?></dd>

  <dt class="col-sm-3">POPIA verdict</dt>
  <dd class="col-sm-9">
    <?php
      $vmap = [
        'CLEAR' => 'bg-success',
        'PERSONAL' => 'bg-warning text-dark',
        'SPECIAL_CATEGORY' => 'bg-danger',
      ];
      $v = $dataset->verdict ?? null;
    ?>
    <?php if ($v): ?>
      <span class="badge <?php echo $vmap[$v] ?? 'bg-secondary'; ?>"><?php echo esc_specialchars($v); ?></span>
      <?php if (!empty($dataset->scanned_at)): ?>
        <span class="text-muted ms-2">scanned <?php echo esc_specialchars((string) $dataset->scanned_at); ?></span>
      <?php endif; ?>
    <?php else: ?>
      <span class="text-muted">not yet scanned</span>
    <?php endif; ?>
  </dd>
</dl>

<form method="post" action="<?php echo url_for('@rdm_datasets_scan?id=' . $dataset->id); ?>" class="mb-3">
  <button type="submit" class="btn btn-outline-primary"
    <?php echo (empty($files) || $dataset->status === 'scanning') ? 'disabled' : ''; ?>>
    <i class="fas fa-shield-halved"></i>
    <?php echo $dataset->verdict ? 'Re-run POPIA scan' : 'Run POPIA scan'; ?>
  </button>
  <?php if ($dataset->status === 'scanning'): ?>
    <span class="text-info ms-2"><i class="fas fa-spinner fa-spin"></i> scanning… reload to refresh</span>
  <?php endif; ?>
</form>

<?php if (!empty($dataset->verdict)): ?>
  <h2 class="h4">POPIA review<?php echo !empty($findings) ? ' — findings (' . count($findings) . ')' : ''; ?></h2>

  <?php // Gate status banner ?>
  <?php if ($gate['can_release']): ?>
    <div class="alert alert-success">
      <i class="fas fa-unlock"></i> Gate clear — no pending or confirmed PERSONAL/SPECIAL findings.
      Open release is permitted (<?php echo (int) $gate['dismissed']; ?> dismissed as false positives).
    </div>
  <?php else: ?>
    <div class="alert alert-warning">
      <i class="fas fa-lock"></i> Open release blocked —
      <strong><?php echo (int) $gate['pending']; ?></strong> finding(s) pending review and
      <strong><?php echo (int) $gate['confirmed_pii']; ?></strong> confirmed PERSONAL/SPECIAL.
      Resolve every finding (none confirmed as PII) to release open, or choose restrict / embargo / de-identify below.
    </div>
  <?php endif; ?>

  <?php if (!empty($findings)): ?>
  <p class="text-muted">Samples are masked. The scan only suggests — confirm real PII or dismiss false positives.</p>
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr><th>Type</th><th>Category</th><th>Sample</th><th>Conf.</th><th>Method</th><th>File</th><th>Review</th></tr>
    </thead>
    <tbody>
      <?php foreach ($findings as $fd): ?>
        <tr>
          <td><?php echo esc_specialchars($fd->type); ?></td>
          <td>
            <?php if ($fd->category === 'special_category'): ?>
              <span class="badge bg-danger">special category</span>
            <?php else: ?>
              <span class="badge bg-warning text-dark">personal</span>
            <?php endif; ?>
          </td>
          <td><code><?php echo esc_specialchars((string) $fd->sample); ?></code></td>
          <td><?php echo esc_specialchars($fd->confidence); ?></td>
          <td><?php echo esc_specialchars($fd->method); ?></td>
          <td><?php echo esc_specialchars((string) $fd->file_name); ?></td>
          <td>
            <?php if ($fd->review_status === 'pending'): ?>
              <form method="post" action="<?php echo url_for('@rdm_datasets_finding_resolve?id=' . $dataset->id . '&fid=' . $fd->id); ?>" class="d-flex gap-1">
                <input type="text" name="note" class="form-control form-control-sm" placeholder="note (optional)" style="max-width:140px;">
                <button name="decision" value="confirm" class="btn btn-sm btn-outline-danger" title="Confirm real PII">Confirm</button>
                <button name="decision" value="dismiss" class="btn btn-sm btn-outline-secondary" title="False positive">Dismiss</button>
              </form>
            <?php elseif ($fd->review_status === 'confirmed'): ?>
              <span class="badge bg-danger">confirmed</span>
              <?php if (!empty($fd->decision_note)): ?><small class="text-muted d-block"><?php echo esc_specialchars($fd->decision_note); ?></small><?php endif; ?>
            <?php else: ?>
              <span class="badge bg-secondary">dismissed</span>
              <?php if (!empty($fd->decision_note)): ?><small class="text-muted d-block"><?php echo esc_specialchars($fd->decision_note); ?></small><?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php // Disposition — release gated on the human review being clear ?>
  <h2 class="h4 mt-4">Disposition</h2>
  <p class="text-muted">
    <?php if (!empty($dataset->disposition)): ?>
      Current: <span class="badge bg-info text-dark"><?php echo esc_specialchars($dataset->disposition); ?></span> ·
    <?php endif; ?>
    Applying a disposition writes ODRL access/embargo policies on the dataset's records and mints a citable DOI
    (real DataCite registration only on a production DOI config; otherwise a reserved test-prefix DOI).
  </p>
  <?php if (!empty($dataset->doi) || !empty($dataset->disposition)): ?>
    <p class="small mb-3">
      <?php if (!empty($dataset->doi)): ?>
        <span class="text-muted">DOI:</span> <code><?php echo esc_specialchars($dataset->doi); ?></code> ·
      <?php endif; ?>
      <a href="<?php echo url_for('@rdm_datasets_landing?id=' . $dataset->id); ?>" target="_blank" rel="noopener">
        <i class="fas fa-external-link-alt"></i> Public landing page
      </a>
    </p>
  <?php endif; ?>
  <form method="post" action="<?php echo url_for('@rdm_datasets_disposition?id=' . $dataset->id); ?>" class="row g-2" style="max-width:760px;">
    <div class="col-auto">
      <select name="disposition" class="form-select form-select-sm">
        <option value="restrict">Restrict access</option>
        <option value="embargo">Embargo (time-limited)</option>
        <option value="de-identify">De-identify then release</option>
        <option value="release" <?php echo $gate['can_release'] ? '' : 'disabled'; ?>>
          Release (open access)<?php echo $gate['can_release'] ? '' : ' — blocked'; ?>
        </option>
      </select>
    </div>
    <div class="col-auto">
      <input type="date" name="embargo_until" class="form-control form-control-sm" title="Embargo until (for embargo)">
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-sm btn-primary">Apply disposition</button>
    </div>
  </form>
<?php endif; ?>

<?php if (!empty($dmp['available'])): ?>
  <div class="card mb-3">
    <div class="card-body">
      <h2 class="h5"><i class="fas fa-clipboard-list me-1"></i>Data Management Plan</h2>
      <?php if (!empty($dmp['linked'])): ?>
        <p class="mb-2">
          <strong><?php echo esc_specialchars($dmp['linked']->title ?? 'DMP'); ?></strong>
          <span class="badge bg-light text-dark border"><?php echo esc_specialchars($dmp['linked']->status ?? 'draft'); ?></span>
          <?php if ($dmp['completeness'] !== null): ?>
            <span class="text-muted small">· <?php echo (int) $dmp['completeness']; ?>% complete</span>
          <?php endif; ?>
        </p>
        <div class="progress mb-2" style="height:6px;">
          <div class="progress-bar" role="progressbar" style="width:<?php echo (int) ($dmp['completeness'] ?? 0); ?>%"></div>
        </div>
        <?php if (!empty($dmp['show_url'])): ?>
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo esc_specialchars($dmp['show_url']); ?>"><i class="fas fa-up-right-from-square"></i> Open DMP</a>
        <?php endif; ?>
        <form method="post" action="<?php echo url_for('@rdm_datasets_dmp_unlink?id=' . $dataset->id); ?>" class="d-inline">
          <button class="btn btn-sm btn-outline-danger">Unlink</button>
        </form>
      <?php elseif (empty($dmp['project_id'])): ?>
        <p class="text-muted small mb-0">Link this dataset to a research project to attach a Data Management Plan.</p>
      <?php else: ?>
        <?php if (!empty($dmp['plans'])): ?>
          <form method="post" action="<?php echo url_for('@rdm_datasets_dmp_link?id=' . $dataset->id); ?>" class="row g-2 align-items-end mb-2">
            <div class="col-auto">
              <label class="form-label small mb-0">Link an existing plan</label>
              <select name="dmp_id" class="form-select form-select-sm">
                <?php foreach ($dmp['plans'] as $p): ?>
                  <option value="<?php echo (int) $p->id; ?>"><?php echo esc_specialchars($p->title ?? ('DMP #' . $p->id)); ?> (<?php echo esc_specialchars($p->status ?? 'draft'); ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-auto"><button class="btn btn-sm btn-primary">Link</button></div>
          </form>
        <?php endif; ?>
        <form method="post" action="<?php echo url_for('@rdm_datasets_dmp_link?id=' . $dataset->id); ?>" class="row g-2 align-items-end">
          <input type="hidden" name="mode" value="create">
          <div class="col-auto">
            <label class="form-label small mb-0">Or create a new DMP</label>
            <input type="text" name="title" class="form-control form-control-sm" placeholder="DMP title" style="max-width:240px;">
          </div>
          <div class="col-auto">
            <input type="text" name="funder" class="form-control form-control-sm" placeholder="Funder (optional)" style="max-width:180px;">
          </div>
          <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Create &amp; link</button></div>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<hr>

<h2 class="h4">Deposited files (<?php echo count($files); ?>)</h2>

<?php if (empty($files)): ?>
  <p class="text-muted">No files deposited yet.</p>
<?php else: ?>
  <table class="table table-sm table-striped">
    <thead>
      <tr><th>File</th><th>Child IO</th><th>Digital object</th></tr>
    </thead>
    <tbody>
      <?php foreach ($files as $f): ?>
        <tr>
          <td><?php echo esc_specialchars($f->original_name); ?></td>
          <td>#<?php echo (int) $f->io_id; ?></td>
          <td><?php echo $f->do_id ? '#' . (int) $f->do_id : '—'; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<hr>

<h2 class="h4">Deposit files</h2>
<form method="post" enctype="multipart/form-data"
      action="<?php echo url_for('@rdm_datasets_deposit?id=' . $dataset->id); ?>" class="mt-2" style="max-width:640px;">
  <div class="mb-3">
    <input type="file" class="form-control" name="files[]" multiple required>
    <div class="form-text">Each file becomes a child record with a master digital object under this dataset.</div>
  </div>
  <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Deposit</button>
  <a class="btn btn-link" href="<?php echo url_for('@rdm_datasets_index'); ?>">Back to datasets</a>
</form>
