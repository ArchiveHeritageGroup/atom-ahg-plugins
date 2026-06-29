<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive ?? 'submissionReviewQueue', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php
$item = $item ?? [];
$status = $submission['status'];
$show = function ($label, $v) {
    if ('' === trim((string) $v)) { return; }
    echo '<dt class="col-sm-4 text-muted">' . esc_specialchars($label) . '</dt><dd class="col-sm-8">' . nl2br(esc_specialchars($v)) . '</dd>';
};
$postUrl = url_for(['module' => 'research', 'action' => 'submissionReview', 'id' => $submission['id']]);
?>

<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
  <h1 class="mb-0"><i class="fas fa-clipboard-check text-primary me-2"></i><?php echo esc_specialchars($submission['title']); ?></h1>
  <span class="badge bg-secondary fs-6"><?php echo SubmissionService::statusLabel($status); ?></span>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php foreach (['notice', 'error'] as $f): if ($sf_user->hasFlash($f)): ?>
  <div class="alert alert-<?php echo 'error' === $f ? 'danger' : 'success'; ?>"><?php echo $sf_user->getFlash($f); ?></div>
<?php endif; endforeach; ?>

<nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'submissionReviewQueue']); ?>"><?php echo __('Review queue'); ?></a></li>
  <li class="breadcrumb-item active"><?php echo esc_specialchars($submission['title']); ?></li>
</ol></nav>

<div class="row">
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-header"><?php echo __('Description (ISAD(G))'); ?> · <small class="text-muted"><?php echo esc_specialchars(SubmissionService::typeLabel((string) $submission['source_type'])); ?></small></div>
      <div class="card-body"><dl class="row mb-0">
        <?php
        $show(__('Title'), $item['title'] ?? '');
        $show(__('Level'), $item['level_of_description'] ?? '');
        $show(__('Reference'), $item['identifier'] ?? '');
        $show(__('Dates'), $item['date_display'] ?? '');
        $show(__('Scope and content'), $item['scope_and_content'] ?? '');
        $show(__('Creator(s)'), $item['creators'] ?? '');
        $show(__('Sites / places'), $item['places'] ?? '');
        $show(__('Subjects'), $item['subjects'] ?? '');
        $show(__('Extent and medium'), $item['extent_and_medium'] ?? '');
        $show(__('Access conditions'), $item['access_conditions'] ?? '');
        $show(__('Reproduction'), $item['reproduction_conditions'] ?? '');
        $show(__('Notes'), $item['notes'] ?? '');
        if (!empty($item['published_object_id'])) {
            echo '<dt class="col-sm-4 text-muted">' . __('Published as') . '</dt><dd class="col-sm-8">AtoM description #' . (int) $item['published_object_id'] . ' (draft)</dd>';
        }
        ?>
      </dl></div></div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3"><div class="card-header"><?php echo __('Decision'); ?></div><div class="card-body">
      <?php if ('submitted' === $status): ?>
        <form method="post" action="<?php echo $postUrl; ?>"><input type="hidden" name="do" value="start">
          <button class="btn btn-primary w-100"><i class="fas fa-play me-1"></i><?php echo __('Start review'); ?></button></form>
      <?php elseif ('under_review' === $status): ?>
        <form method="post" action="<?php echo $postUrl; ?>" class="mb-3">
          <input type="hidden" name="do" value="return">
          <label class="form-label"><?php echo __('Return for revision — comment'); ?></label>
          <textarea class="form-control mb-2" name="comment" rows="3" required></textarea>
          <button class="btn btn-warning w-100"><i class="fas fa-undo me-1"></i><?php echo __('Return to researcher'); ?></button>
        </form>
        <form method="post" action="<?php echo $postUrl; ?>"><input type="hidden" name="do" value="approve">
          <button class="btn btn-success w-100"><i class="fas fa-check me-1"></i><?php echo __('Approve'); ?></button></form>
      <?php elseif ('approved' === $status): ?>
        <p class="text-muted small"><?php echo __('Approved. Publishing creates a DRAFT AtoM description for a final archivist check before it goes public.'); ?></p>
        <form method="post" action="<?php echo $postUrl; ?>"><input type="hidden" name="do" value="publish">
          <button class="btn btn-dark w-100"><i class="fas fa-upload me-1"></i><?php echo __('Publish to AtoM (draft)'); ?></button></form>
      <?php else: ?>
        <p class="text-muted mb-0"><?php echo __('No action available for this status.'); ?></p>
      <?php endif ?>
    </div></div>

    <div class="card"><div class="card-header"><?php echo __('Review history'); ?></div>
      <ul class="list-group list-group-flush">
        <?php if (empty($reviews)): ?><li class="list-group-item text-muted small"><?php echo __('No actions yet.'); ?></li><?php endif ?>
        <?php foreach ($reviews as $r): ?>
          <li class="list-group-item small"><strong><?php echo SubmissionService::statusLabel($r['action']); ?></strong>
            <span class="text-muted"><?php echo esc_specialchars((string) $r['created_at']); ?></span>
            <?php if (!empty($r['comment'])): ?><div><?php echo nl2br(esc_specialchars($r['comment'])); ?></div><?php endif ?></li>
        <?php endforeach ?>
      </ul></div>
  </div>
</div>
<?php end_slot() ?>
