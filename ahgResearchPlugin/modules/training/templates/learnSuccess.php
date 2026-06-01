<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('training/trainingSidebar', ['active' => $sidebarActive ?? 'training']) ?>
<?php end_slot() ?>
<?php
$enrol = isset($enrol) && is_array($enrol) ? $enrol : [];
$course = isset($course) && is_array($course) ? $course : [];
$modules = isset($modules) && is_array($modules) ? $modules : [];
$doneIds = isset($doneIds) && is_array($doneIds) ? array_map('intval', $doneIds) : [];
$questions = isset($questions) && is_array($questions) ? $questions : [];
$allDone = !empty($allDone);
$certificate = isset($certificate) && is_array($certificate) ? $certificate : null;
$eid = (int) ($enrol['id'] ?? 0);
?>
<?php slot('title') ?>
<h1><i class="fas fa-graduation-cap text-primary me-2"></i><?php echo htmlspecialchars((string) ($course['title'] ?? '')); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'training', 'action' => 'index']); ?>"><?php echo __('Training'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo htmlspecialchars((string) ($enrol['learner_name'] ?? __('Learner'))); ?></li>
  </ol>
</nav>

<div class="alert alert-light border d-flex justify-content-between align-items-center">
  <span>
    <strong><?php echo __('Status'); ?>:</strong> <span class="badge bg-secondary"><?php echo htmlspecialchars((string) ($enrol['status'] ?? '')); ?></span>
    <?php if (($enrol['score'] ?? null) !== null): ?>&middot; <strong><?php echo __('Best score'); ?>:</strong> <?php echo (int) $enrol['score']; ?>%<?php endif; ?>
  </span>
  <?php if ($certificate): ?>
    <a class="btn btn-success" href="<?php echo url_for(['module' => 'training', 'action' => 'certificate', 'id' => $eid]); ?>"><i class="fas fa-certificate me-1"></i><?php echo __('View certificate'); ?></a>
  <?php endif; ?>
</div>

<!-- Modules -->
<div class="card mb-4">
  <div class="card-header"><i class="fas fa-layer-group me-2"></i><?php echo __('Modules'); ?></div>
  <?php if ($modules): ?>
    <div class="accordion accordion-flush" id="moduleAcc">
      <?php foreach ($modules as $idx => $m): ?>
        <?php $done = in_array((int) $m['id'], $doneIds, true); ?>
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mod<?php echo (int) $m['id']; ?>">
              <?php if ($done): ?><i class="fas fa-check-circle text-success me-2"></i><?php else: ?><i class="far fa-circle text-muted me-2"></i><?php endif; ?>
              <?php echo htmlspecialchars((string) $m['title']); ?>
            </button>
          </h2>
          <div id="mod<?php echo (int) $m['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#moduleAcc">
            <div class="accordion-body">
              <?php if (!empty($m['lecture_html'])): ?>
                <?php if (!empty($m['lecture_title'])): ?><h5><?php echo htmlspecialchars((string) $m['lecture_title']); ?></h5><?php endif; ?>
                <div><?php echo $m['lecture_html']; ?></div>
              <?php elseif (!empty($m['body_html'])): ?>
                <div><?php echo $m['body_html']; ?></div>
              <?php else: ?>
                <p class="text-muted"><?php echo __('No content for this module.'); ?></p>
              <?php endif; ?>
              <form method="post" action="<?php echo url_for(['module' => 'training', 'action' => 'completeModule', 'id' => $eid, 'module_id' => $m['id']]); ?>" class="mt-3">
                <input type="hidden" name="completed" value="<?php echo $done ? '0' : '1'; ?>">
                <button type="submit" class="btn btn-sm <?php echo $done ? 'btn-outline-secondary' : 'btn-outline-success'; ?>">
                  <?php echo $done ? __('Mark incomplete') : __('Mark complete'); ?>
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="card-body text-muted"><?php echo __('This course has no modules.'); ?></div>
  <?php endif; ?>
</div>

<!-- Assessment unlock -->
<div class="card">
  <div class="card-header"><i class="fas fa-clipboard-check me-2"></i><?php echo __('Assessment'); ?></div>
  <div class="card-body">
    <?php if (!$questions): ?>
      <p class="text-muted mb-0"><?php echo __('No assessment configured for this course.'); ?></p>
    <?php elseif (!$allDone): ?>
      <p class="text-muted mb-0"><i class="fas fa-lock me-1"></i><?php echo __('Complete all modules to unlock the assessment.'); ?></p>
    <?php elseif (($enrol['status'] ?? '') === 'completed'): ?>
      <p class="text-success mb-0"><i class="fas fa-check-circle me-1"></i><?php echo __('Course completed.'); ?></p>
    <?php else: ?>
      <p><?php echo count($questions); ?> <?php echo __('questions.'); ?></p>
      <a class="btn btn-primary" href="<?php echo url_for(['module' => 'training', 'action' => 'takeAssessment', 'id' => $eid]); ?>"><?php echo __('Take assessment'); ?></a>
    <?php endif; ?>
  </div>
</div>
<?php end_slot() ?>
