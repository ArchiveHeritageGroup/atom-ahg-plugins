<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1>Research datasets</h1>
<?php end_slot(); ?>

<div class="mb-3 d-flex gap-2">
  <a class="btn btn-primary" href="<?php echo url_for('@rdm_datasets_create'); ?>">
    <i class="fas fa-plus"></i> New dataset
  </a>
  <a class="btn btn-outline-secondary" href="<?php echo url_for('@rdm_datasets_compliance'); ?>">
    <i class="fas fa-clipboard-check"></i> Compliance scoreboard
  </a>
</div>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>

<?php if (empty($datasets)): ?>
  <p class="text-muted">No datasets yet. Create one to deposit research data.</p>
<?php else: ?>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Title</th>
        <th>Project</th>
        <th>Status</th>
        <th class="text-end">Files</th>
        <th>Created</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($datasets as $d): ?>
        <tr>
          <td><a href="<?php echo url_for('@rdm_datasets_show?id=' . $d->id); ?>"><?php echo esc_specialchars($d->title); ?></a></td>
          <td><?php echo esc_specialchars($d->project_title ?? '—'); ?></td>
          <td><span class="badge bg-secondary"><?php echo esc_specialchars($d->status); ?></span></td>
          <td class="text-end"><?php echo (int) $d->file_count; ?></td>
          <td><?php echo esc_specialchars((string) $d->created_at); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
