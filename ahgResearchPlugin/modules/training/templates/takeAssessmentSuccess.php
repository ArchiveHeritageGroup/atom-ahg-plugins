<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('training/trainingSidebar', ['active' => $sidebarActive ?? 'training']) ?>
<?php end_slot() ?>
<?php
$enrol = isset($enrol) && is_array($enrol) ? $enrol : [];
$course = isset($course) && is_array($course) ? $course : [];
$questions = isset($questions) && is_array($questions) ? $questions : [];
$allDone = !empty($allDone);
$eid = (int) ($enrol['id'] ?? 0);
?>
<?php slot('title') ?>
<h1><i class="fas fa-clipboard-check text-primary me-2"></i><?php echo __('Assessment'); ?>: <?php echo htmlspecialchars((string) ($course['title'] ?? '')); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'training', 'action' => 'learn', 'id' => $eid]); ?>"><?php echo htmlspecialchars((string) ($enrol['learner_name'] ?? __('Learner'))); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Assessment'); ?></li>
  </ol>
</nav>

<?php if (!$allDone): ?>
  <div class="alert alert-warning"><i class="fas fa-lock me-1"></i><?php echo __('Complete all modules before taking the assessment.'); ?></div>
  <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'training', 'action' => 'learn', 'id' => $eid]); ?>"><?php echo __('Back'); ?></a>
<?php elseif (!$questions): ?>
  <div class="alert alert-info"><?php echo __('No assessment configured.'); ?></div>
<?php else: ?>
  <form method="post" action="<?php echo url_for(['module' => 'training', 'action' => 'submitAssessment', 'id' => $eid]); ?>">
    <?php foreach ($questions as $i => $q): ?>
      <div class="card mb-3">
        <div class="card-body">
          <p class="fw-bold"><?php echo ($i + 1) . '. ' . htmlspecialchars((string) ($q['q'] ?? '')); ?></p>
          <?php foreach ((array) ($q['options'] ?? []) as $oi => $opt): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="answer[<?php echo $i; ?>]" id="q<?php echo $i; ?>o<?php echo $oi; ?>" value="<?php echo $oi; ?>">
              <label class="form-check-label" for="q<?php echo $i; ?>o<?php echo $oi; ?>"><?php echo htmlspecialchars((string) $opt); ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <div class="text-end mb-4">
      <a href="<?php echo url_for(['module' => 'training', 'action' => 'learn', 'id' => $eid]); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
      <button type="submit" class="btn btn-primary"><?php echo __('Submit answers'); ?></button>
    </div>
  </form>
<?php endif; ?>
<?php end_slot() ?>
