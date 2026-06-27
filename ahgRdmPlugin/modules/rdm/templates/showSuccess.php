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

<?php if (!empty($findings)): ?>
  <h2 class="h4">POPIA findings (<?php echo count($findings); ?>)</h2>
  <p class="text-muted">Samples are masked. Each finding is <strong>pending</strong> human review (next phase).</p>
  <table class="table table-sm table-striped">
    <thead>
      <tr><th>Type</th><th>Category</th><th>Sample</th><th>Confidence</th><th>Method</th><th>File</th></tr>
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
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
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
