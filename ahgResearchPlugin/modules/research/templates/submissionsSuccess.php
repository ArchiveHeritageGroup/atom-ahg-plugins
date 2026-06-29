<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive ?? 'submissions', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
  <h1 class="mb-0"><i class="fas fa-file-import text-primary me-2"></i><?php echo __('My submissions'); ?></h1>
  <div class="d-flex gap-2">
    <?php if (!empty($isArchivist)): ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'submissionReviewQueue']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-inbox me-1"></i><?php echo __('Review queue'); ?>
    </a>
    <?php endif ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'submissionEdit']); ?>" class="btn btn-primary">
      <i class="fas fa-plus me-1"></i><?php echo __('New submission'); ?>
    </a>
  </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php foreach (['notice', 'error'] as $f): if ($sf_user->hasFlash($f)): ?>
  <div class="alert alert-<?php echo 'error' === $f ? 'danger' : 'success'; ?>"><?php echo $sf_user->getFlash($f); ?></div>
<?php endif; endforeach; ?>

<p class="text-muted"><?php echo __('Describe an archaeological archive against ISAD(G) and submit it for archival review. Submitted descriptions are reviewed by archival staff before publication.'); ?></p>

<?php if (empty($submissions)): ?>
  <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><?php echo __('You have no submissions yet.'); ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'submissionEdit']); ?>" class="alert-link"><?php echo __('Start one'); ?></a>.</div>
<?php else: ?>
  <table class="table table-striped align-middle">
    <thead><tr>
      <th><?php echo __('Title'); ?></th><th><?php echo __('Type'); ?></th>
      <th><?php echo __('Status'); ?></th><th><?php echo __('Updated'); ?></th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($submissions as $s): ?>
      <?php
      $badge = ['draft' => 'secondary', 'submitted' => 'info', 'under_review' => 'primary',
                'returned' => 'warning', 'approved' => 'success', 'published' => 'dark', 'rejected' => 'danger'][$s['status']] ?? 'secondary';
      $editable = in_array($s['status'], ['draft', 'returned'], true);
      ?>
      <tr>
        <td><?php echo esc_specialchars($s['title']); ?></td>
        <td><small class="text-muted"><?php echo esc_specialchars(SubmissionService::typeLabel((string) $s['source_type'])); ?></small></td>
        <td><span class="badge bg-<?php echo $badge; ?>"><?php echo SubmissionService::statusLabel($s['status']); ?></span>
          <?php if ('returned' === $s['status'] && !empty($s['return_comment'])): ?>
            <i class="fas fa-comment-dots text-warning ms-1" title="<?php echo esc_specialchars($s['return_comment']); ?>"></i>
          <?php endif ?></td>
        <td><small><?php echo esc_specialchars((string) ($s['updated_at'] ?? $s['created_at'])); ?></small></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-<?php echo $editable ? 'primary' : 'secondary'; ?>"
             href="<?php echo url_for(['module' => 'research', 'action' => 'submissionEdit', 'id' => $s['id']]); ?>">
            <?php echo $editable ? __('Edit') : __('View'); ?>
          </a>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
<?php endif ?>
<?php end_slot() ?>
