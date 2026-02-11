<?php decorate_with('layout_1col.php'); ?>
<?php use_helper('Date'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Job report #%1%', ['%1%' => (int) $job->id]); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="<?php echo __('Breadcrumb'); ?>">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="/"><?php echo __('Home'); ?></a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for('@jobs_browse'); ?>"><?php echo __('Jobs'); ?></a></li>
      <li class="breadcrumb-item active" aria-current="page"><?php echo __('Report #%1%', ['%1%' => (int) $job->id]); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <!-- Job overview card -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0"><?php echo __('Job overview'); ?></h5>
    </div>
    <div class="card-body">
      <table class="table table-borderless mb-0">
        <tbody>
          <tr>
            <th class="text-nowrap" style="width: 200px;"><?php echo __('Job ID'); ?></th>
            <td><?php echo (int) $job->id; ?></td>
          </tr>
          <tr>
            <th><?php echo __('Job name'); ?></th>
            <td><?php echo esc_specialchars($job->name); ?></td>
          </tr>
          <tr>
            <th><?php echo __('Status'); ?></th>
            <td>
              <?php
                  $badgeClass = \AhgJobsManage\Services\JobsService::getStatusBadge($job->status_id);
                  $statusLabel = \AhgJobsManage\Services\JobsService::getStatusLabel($job->status_id);
              ?>
              <span class="badge bg-<?php echo $badgeClass; ?> fs-6">
                <?php echo esc_specialchars($statusLabel); ?>
              </span>
            </td>
          </tr>
          <tr>
            <th><?php echo __('User'); ?></th>
            <td><?php echo esc_specialchars($job->user_name ?? __('System')); ?></td>
          </tr>
          <tr>
            <th><?php echo __('Created'); ?></th>
            <td><?php echo !empty($job->created_at) ? format_date($job->created_at, 'f') : '-'; ?></td>
          </tr>
          <tr>
            <th><?php echo __('Completed'); ?></th>
            <td><?php echo !empty($job->completed_at) ? format_date($job->completed_at, 'f') : '-'; ?></td>
          </tr>
          <tr>
            <th><?php echo __('Related object'); ?></th>
            <td>
              <?php if (!empty($job->object_slug)): ?>
                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $job->object_slug]); ?>">
                  <?php echo esc_specialchars($job->object_slug); ?>
                </a>
              <?php elseif (!empty($job->object_id)): ?>
                <span class="text-muted">#<?php echo (int) $job->object_id; ?></span>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php if (!empty($job->download_path)): ?>
            <tr>
              <th><?php echo __('Download'); ?></th>
              <td>
                <a href="<?php echo esc_specialchars($job->download_path); ?>" class="btn btn-outline-primary btn-sm">
                  <i class="fas fa-download me-1"></i><?php echo __('Download file'); ?>
                </a>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Error notes -->
  <?php $rawErrorNotes = $sf_data->getRaw('errorNotes'); ?>
  <?php if (!empty($rawErrorNotes)): ?>
    <div class="card mb-4 border-danger">
      <div class="card-header bg-danger text-white">
        <h5 class="card-title mb-0">
          <i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Error details'); ?>
        </h5>
      </div>
      <div class="card-body">
        <?php foreach ($rawErrorNotes as $note): ?>
          <div class="alert alert-danger mb-2">
            <?php echo nl2br(esc_specialchars($note->content ?? '')); ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Info notes -->
  <?php $rawInfoNotes = $sf_data->getRaw('infoNotes'); ?>
  <?php if (!empty($rawInfoNotes)): ?>
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Job notes'); ?></h5>
      </div>
      <div class="card-body">
        <?php foreach ($rawInfoNotes as $note): ?>
          <div class="alert alert-info mb-2">
            <?php echo nl2br(esc_specialchars($note->content ?? '')); ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Job output -->
  <?php if (!empty($job->output)): ?>
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><?php echo __('Job output'); ?></h5>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="toggle-wrap-btn" title="<?php echo __('Toggle word wrap'); ?>">
          <i class="fas fa-align-left"></i>
        </button>
      </div>
      <div class="card-body p-0">
        <pre id="job-output" class="p-3 mb-0" style="max-height: 600px; overflow: auto; background: #f8f9fa; font-size: 0.85rem; white-space: pre-wrap; word-wrap: break-word;"><?php echo esc_specialchars($job->output); ?></pre>
      </div>
    </div>

    <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
      (function() {
        var btn = document.getElementById('toggle-wrap-btn');
        var pre = document.getElementById('job-output');
        var wrapped = true;
        if (btn && pre) {
          btn.addEventListener('click', function() {
            wrapped = !wrapped;
            pre.style.whiteSpace = wrapped ? 'pre-wrap' : 'pre';
            pre.style.wordWrap = wrapped ? 'break-word' : 'normal';
          });
        }
      })();
    </script>
  <?php endif; ?>

<?php end_slot(); ?>

<?php slot('after-content'); ?>

  <section class="actions mb-3 d-flex gap-2">
    <a href="<?php echo url_for('@jobs_browse'); ?>" class="btn atom-btn-outline-light">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to jobs'); ?>
    </a>
    <?php if ($job->status_id != \AhgJobsManage\Services\JobsService::STATUS_IN_PROGRESS): ?>
      <a href="<?php echo url_for('@jobs_delete') . '?id=' . (int) $job->id; ?>" class="btn btn-outline-danger">
        <i class="fas fa-trash-alt me-1"></i><?php echo __('Delete this job'); ?>
      </a>
    <?php endif; ?>
  </section>

<?php end_slot(); ?>
