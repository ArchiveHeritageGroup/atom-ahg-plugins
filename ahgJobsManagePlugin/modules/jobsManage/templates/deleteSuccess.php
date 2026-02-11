<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <?php if (!empty($id) && isset($job)): ?>
    <h1><?php echo __('Delete job #%1%?', ['%1%' => (int) $job->id]); ?></h1>
  <?php else: ?>
    <h1><?php echo __('Clear inactive jobs?'); ?></h1>
  <?php endif; ?>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (!empty($id) && isset($job)): ?>
    <!-- Single job delete confirmation -->
    <div class="alert alert-warning">
      <p class="mb-2">
        <?php echo __('Are you sure you want to delete the following job?'); ?>
      </p>
      <ul class="mb-0">
        <li><strong><?php echo __('Job ID'); ?>:</strong> <?php echo (int) $job->id; ?></li>
        <li><strong><?php echo __('Name'); ?>:</strong> <?php echo esc_specialchars($job->name); ?></li>
        <li>
          <strong><?php echo __('Status'); ?>:</strong>
          <?php echo esc_specialchars(\AhgJobsManage\Services\JobsService::getStatusLabel($job->status_id)); ?>
        </li>
      </ul>
    </div>

    <form method="post" action="<?php echo url_for('@jobs_delete'); ?>">
      <input type="hidden" name="id" value="<?php echo (int) $job->id; ?>">

      <ul class="actions mb-3 nav gap-2">
        <li>
          <a href="<?php echo url_for('@jobs_browse'); ?>" class="btn atom-btn-outline-light" role="button">
            <?php echo __('Cancel'); ?>
          </a>
        </li>
        <li>
          <input class="btn atom-btn-outline-danger" type="submit" value="<?php echo __('Delete'); ?>">
        </li>
      </ul>
    </form>

  <?php else: ?>
    <!-- Bulk delete confirmation -->
    <div class="alert alert-warning">
      <p class="mb-2">
        <?php echo __('Are you sure you want to delete all completed and failed jobs?'); ?>
      </p>
      <ul class="mb-0">
        <li><strong><?php echo __('Completed jobs'); ?>:</strong> <?php echo (int) $stats['completed']; ?></li>
        <li><strong><?php echo __('Failed jobs'); ?>:</strong> <?php echo (int) $stats['failed']; ?></li>
        <li><strong><?php echo __('Total to delete'); ?>:</strong> <?php echo (int) ($stats['completed'] + $stats['failed']); ?></li>
      </ul>
      <p class="mt-2 mb-0">
        <strong><?php echo __('Active (in-progress) jobs will not be affected.'); ?></strong>
      </p>
    </div>

    <form method="post" action="<?php echo url_for('@jobs_delete'); ?>">

      <ul class="actions mb-3 nav gap-2">
        <li>
          <a href="<?php echo url_for('@jobs_browse'); ?>" class="btn atom-btn-outline-light" role="button">
            <?php echo __('Cancel'); ?>
          </a>
        </li>
        <li>
          <input class="btn atom-btn-outline-danger" type="submit" value="<?php echo __('Delete all inactive'); ?>">
        </li>
      </ul>
    </form>

  <?php endif; ?>

<?php end_slot(); ?>
