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
</dl>

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
