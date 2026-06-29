<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive ?? 'submissionReviewQueue', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php slot('title') ?>
<h1 class="mb-0"><i class="fas fa-inbox text-primary me-2"></i><?php echo __('Submission review queue'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php foreach (['notice', 'error'] as $f): if ($sf_user->hasFlash($f)): ?>
  <div class="alert alert-<?php echo 'error' === $f ? 'danger' : 'success'; ?>"><?php echo $sf_user->getFlash($f); ?></div>
<?php endif; endforeach; ?>

<p class="text-muted"><?php echo __('Researcher submissions awaiting archival review. Open one to review, return for revision, approve, and publish.'); ?></p>

<?php if (empty($queue)): ?>
  <div class="alert alert-info"><i class="fas fa-check-circle me-2"></i><?php echo __('Nothing awaiting review.'); ?></div>
<?php else: ?>
  <table class="table table-hover align-middle">
    <thead><tr>
      <th><?php echo __('Title'); ?></th><th><?php echo __('Type'); ?></th>
      <th><?php echo __('Status'); ?></th><th><?php echo __('Submitted'); ?></th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($queue as $s): ?>
      <?php $badge = ['submitted' => 'info', 'under_review' => 'primary', 'returned' => 'warning', 'approved' => 'success'][$s['status']] ?? 'secondary'; ?>
      <tr>
        <td><?php echo esc_specialchars($s['title']); ?></td>
        <td><small class="text-muted"><?php echo esc_specialchars(SubmissionService::typeLabel((string) $s['source_type'])); ?></small></td>
        <td><span class="badge bg-<?php echo $badge; ?>"><?php echo SubmissionService::statusLabel($s['status']); ?></span></td>
        <td><small><?php echo esc_specialchars((string) ($s['submitted_at'] ?? '—')); ?></small></td>
        <td class="text-end"><a class="btn btn-sm btn-primary" href="<?php echo url_for(['module' => 'research', 'action' => 'submissionReview', 'id' => $s['id']]); ?>"><?php echo __('Review'); ?></a></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
<?php endif ?>
<?php end_slot() ?>
