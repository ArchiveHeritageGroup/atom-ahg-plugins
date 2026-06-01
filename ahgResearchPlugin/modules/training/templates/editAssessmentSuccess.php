<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('training/trainingSidebar', ['active' => $sidebarActive ?? 'training']) ?>
<?php end_slot() ?>
<?php
$course = isset($course) && is_array($course) ? $course : [];
$assessment = isset($assessment) && is_array($assessment) ? $assessment : null;
$questions = isset($questions) && is_array($questions) ? $questions : [];
$cid = (int) ($course['id'] ?? 0);
// Always render a few blank rows so the builder can add questions.
// NB: capture the starting count before the loop — appending to $rows inside
// the loop would otherwise make count($rows) grow each iteration and never
// terminate (integer-overflow / OOM).
$rows = $questions;
$startRows = count($rows);
for ($i = $startRows; $i < $startRows + 3; $i++) {
    $rows[$i] = ['q' => '', 'options' => ['', '', '', ''], 'answer' => 0];
}
?>
<?php slot('title') ?>
<h1><i class="fas fa-clipboard-check text-primary me-2"></i><?php echo __('Assessment'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'training', 'action' => 'index']); ?>"><?php echo __('Training'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'training', 'action' => 'show', 'id' => $cid]); ?>"><?php echo htmlspecialchars((string) ($course['title'] ?? '')); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Assessment'); ?></li>
  </ol>
</nav>

<form method="post" class="card">
  <div class="card-body">
    <div class="row mb-3">
      <div class="col-md-8">
        <label class="form-label"><?php echo __('Assessment title'); ?></label>
        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars((string) ($assessment['title'] ?? 'Assessment')); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label"><?php echo __('Pass mark % (overrides course)'); ?></label>
        <input type="number" name="pass_mark" class="form-control" min="0" max="100"
               value="<?php echo $assessment && $assessment['pass_mark'] !== null ? (int) $assessment['pass_mark'] : ''; ?>"
               placeholder="<?php echo (int) ($course['pass_mark'] ?? 80); ?>">
      </div>
    </div>

    <hr>
    <p class="text-muted"><?php echo __('Multiple-choice questions. Select the correct option with the radio. Empty questions or those with fewer than two options are dropped on save.'); ?></p>

    <?php foreach ($rows as $i => $q): ?>
      <?php $opts = is_array($q['options'] ?? null) ? array_values($q['options']) : ['', '', '', '']; ?>
      <?php while (count($opts) < 4) { $opts[] = ''; } ?>
      <div class="card mb-3">
        <div class="card-body">
          <div class="mb-2">
            <label class="form-label small"><?php echo __('Question'); ?> <?php echo $i + 1; ?></label>
            <input type="text" name="q[<?php echo $i; ?>]" class="form-control" value="<?php echo htmlspecialchars((string) ($q['q'] ?? '')); ?>">
          </div>
          <?php foreach ($opts as $oi => $optText): ?>
            <div class="input-group input-group-sm mb-1">
              <span class="input-group-text">
                <input type="radio" name="answer[<?php echo $i; ?>]" value="<?php echo $oi; ?>" <?php echo (int) ($q['answer'] ?? 0) === $oi ? 'checked' : ''; ?>>
              </span>
              <input type="text" name="options[<?php echo $i; ?>][<?php echo $oi; ?>]" class="form-control" placeholder="<?php echo __('Option'); ?> <?php echo $oi + 1; ?>" value="<?php echo htmlspecialchars((string) $optText); ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="card-footer text-end">
    <a href="<?php echo url_for(['module' => 'training', 'action' => 'show', 'id' => $cid]); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
    <button type="submit" class="btn btn-primary"><?php echo __('Save assessment'); ?></button>
  </div>
</form>
<?php end_slot() ?>
