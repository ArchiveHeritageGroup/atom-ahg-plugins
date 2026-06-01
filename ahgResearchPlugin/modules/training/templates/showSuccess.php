<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('training/trainingSidebar', ['active' => $sidebarActive ?? 'training']) ?>
<?php end_slot() ?>
<?php
$course = isset($course) && is_array($course) ? $course : [];
$modules = isset($modules) && is_array($modules) ? $modules : [];
$assessment = isset($assessment) && is_array($assessment) ? $assessment : null;
$questions = isset($questions) && is_array($questions) ? $questions : [];
$enrolments = isset($enrolments) && is_array($enrolments) ? $enrolments : [];
$lectures = isset($lectures) && is_array($lectures) ? $lectures : [];
$cid = (int) ($course['id'] ?? 0);
?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1><i class="fas fa-graduation-cap text-primary me-2"></i><?php echo htmlspecialchars((string) ($course['title'] ?? '')); ?></h1>
  <div class="d-flex gap-2">
    <a href="<?php echo url_for(['module' => 'training', 'action' => 'builder', 'id' => $cid]); ?>" class="btn btn-outline-secondary"><i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?></a>
    <form method="post" action="<?php echo url_for(['module' => 'training', 'action' => 'destroy', 'id' => $cid]); ?>" onsubmit="return confirm('<?php echo __('Delete this course and all its data?'); ?>');">
      <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i><?php echo __('Delete'); ?></button>
    </form>
  </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'training', 'action' => 'index']); ?>"><?php echo __('Training'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo htmlspecialchars((string) ($course['title'] ?? '')); ?></li>
  </ol>
</nav>

<div class="row mb-3">
  <div class="col-md-3"><strong><?php echo __('Audience'); ?>:</strong> <?php echo htmlspecialchars((string) ($course['audience'] ?? '-')); ?></div>
  <div class="col-md-2"><strong><?php echo __('Language'); ?>:</strong> <?php echo htmlspecialchars((string) ($course['language'] ?? '-')); ?></div>
  <div class="col-md-2"><strong><?php echo __('Pass mark'); ?>:</strong> <?php echo (int) ($course['pass_mark'] ?? 80); ?>%</div>
  <div class="col-md-3">
    <strong><?php echo __('Status'); ?>:</strong>
    <form method="post" action="<?php echo url_for(['module' => 'training', 'action' => 'setStatus', 'id' => $cid]); ?>" class="d-inline">
      <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
        <?php foreach (['draft', 'published', 'archived'] as $st): ?>
          <option value="<?php echo $st; ?>" <?php echo ($course['status'] ?? '') === $st ? 'selected' : ''; ?>><?php echo __(ucfirst($st)); ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</div>

<!-- Modules -->
<div class="card mb-4">
  <div class="card-header"><i class="fas fa-layer-group me-2"></i><?php echo __('Modules'); ?></div>
  <?php if ($modules): ?>
    <ul class="list-group list-group-flush">
      <?php foreach ($modules as $m): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span>
            <span class="badge bg-light text-dark me-2"><?php echo (int) $m['sort_order']; ?></span>
            <?php echo htmlspecialchars((string) $m['title']); ?>
            <?php if (!empty($m['lecture_id'])): ?><span class="badge bg-info ms-2"><i class="fas fa-link me-1"></i><?php echo __('Lecture'); ?> #<?php echo (int) $m['lecture_id']; ?></span><?php endif; ?>
          </span>
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo url_for(['module' => 'training', 'action' => 'editModule', 'id' => $m['id']]); ?>"><?php echo __('Edit'); ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <div class="card-body text-muted"><?php echo __('No modules yet.'); ?></div>
  <?php endif; ?>
  <div class="card-footer">
    <form method="post" action="<?php echo url_for(['module' => 'training', 'action' => 'storeModule', 'id' => $cid]); ?>" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small"><?php echo __('Module title'); ?></label>
        <input type="text" name="title" class="form-control form-control-sm" required>
      </div>
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Reuse curriculum lecture'); ?></label>
        <select name="lecture_id" class="form-select form-select-sm">
          <option value=""><?php echo __('-- none --'); ?></option>
          <?php foreach ($lectures as $lec): ?>
            <option value="<?php echo (int) $lec['id']; ?>"><?php echo htmlspecialchars((string) $lec['title']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Own content (Markdown)'); ?></label>
        <input type="text" name="body_markdown" class="form-control form-control-sm" placeholder="<?php echo __('optional inline Markdown'); ?>">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-plus me-1"></i><?php echo __('Add'); ?></button>
      </div>
    </form>
  </div>
</div>

<!-- Assessment -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-clipboard-check me-2"></i><?php echo __('Assessment'); ?></span>
    <a class="btn btn-sm btn-outline-primary" href="<?php echo url_for(['module' => 'training', 'action' => 'editAssessment', 'id' => $cid]); ?>"><?php echo __('Edit assessment'); ?></a>
  </div>
  <div class="card-body">
    <?php if ($assessment): ?>
      <p class="mb-1"><strong><?php echo htmlspecialchars((string) ($assessment['title'] ?? __('Assessment'))); ?></strong></p>
      <p class="text-muted mb-0">
        <?php echo count($questions); ?> <?php echo __('questions'); ?> &middot;
        <?php echo __('Pass mark'); ?>: <?php echo $assessment['pass_mark'] !== null ? (int) $assessment['pass_mark'] : (int) ($course['pass_mark'] ?? 80); ?>%
        <?php if ($assessment['pass_mark'] !== null): ?><span class="badge bg-warning text-dark ms-1"><?php echo __('overrides course default'); ?></span><?php endif; ?>
      </p>
    <?php else: ?>
      <p class="text-muted mb-0"><?php echo __('No assessment configured yet.'); ?></p>
    <?php endif; ?>
  </div>
</div>

<!-- Enrolments -->
<div class="card">
  <div class="card-header"><i class="fas fa-users me-2"></i><?php echo __('Enrolments'); ?></div>
  <?php if ($enrolments): ?>
    <table class="table mb-0">
      <thead><tr><th><?php echo __('Learner'); ?></th><th><?php echo __('Status'); ?></th><th class="text-center"><?php echo __('Best score'); ?></th><th class="text-end"><?php echo __('Actions'); ?></th></tr></thead>
      <tbody>
        <?php foreach ($enrolments as $e): ?>
          <tr>
            <td><?php echo htmlspecialchars((string) ($e['learner_name'] ?? '-')); ?><?php if (!empty($e['learner_email'])): ?><br><small class="text-muted"><?php echo htmlspecialchars((string) $e['learner_email']); ?></small><?php endif; ?></td>
            <td><span class="badge bg-secondary"><?php echo htmlspecialchars((string) $e['status']); ?></span></td>
            <td class="text-center"><?php echo $e['score'] !== null ? ((int) $e['score'] . '%') : '-'; ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?php echo url_for(['module' => 'training', 'action' => 'learn', 'id' => $e['id']]); ?>"><?php echo __('Open'); ?></a>
              <form method="post" action="<?php echo url_for(['module' => 'training', 'action' => 'destroyEnrolment', 'id' => $e['id']]); ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Remove enrolment?'); ?>');">
                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="card-body text-muted"><?php echo __('No learners enrolled yet.'); ?></div>
  <?php endif; ?>
  <div class="card-footer">
    <form method="post" action="<?php echo url_for(['module' => 'training', 'action' => 'enrol', 'id' => $cid]); ?>" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small"><?php echo __('Learner name'); ?></label>
        <input type="text" name="learner_name" class="form-control form-control-sm" required>
      </div>
      <div class="col-md-4">
        <label class="form-label small"><?php echo __('Learner email'); ?></label>
        <input type="email" name="learner_email" class="form-control form-control-sm">
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-sm btn-success w-100"><i class="fas fa-user-plus me-1"></i><?php echo __('Enrol learner'); ?></button>
      </div>
    </form>
  </div>
</div>
<?php end_slot() ?>
