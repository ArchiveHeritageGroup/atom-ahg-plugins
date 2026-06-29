<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive ?? 'submissions', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php
$item = $item ?? null;
$editable = $editable ?? true;
$val = function ($k) use ($item) { return $item && isset($item[$k]) ? $item[$k] : ''; };
$levels = ['Fonds', 'Collection', 'Subfonds', 'Series', 'File', 'Item'];
$ro = $editable ? '' : 'readonly disabled';
?>

<?php slot('title') ?>
<h1 class="mb-0"><i class="fas fa-file-import text-primary me-2"></i>
  <?php echo $submission ? esc_specialchars($submission['title']) : __('New submission'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php foreach (['notice', 'error'] as $f): if ($sf_user->hasFlash($f)): ?>
  <div class="alert alert-<?php echo 'error' === $f ? 'danger' : 'success'; ?>"><?php echo $sf_user->getFlash($f); ?></div>
<?php endif; endforeach; ?>

<nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'submissions']); ?>"><?php echo __('My submissions'); ?></a></li>
  <li class="breadcrumb-item active"><?php echo $submission ? esc_specialchars($submission['title']) : __('New'); ?></li>
</ol></nav>

<?php if (!$submission): ?>
  <?php // Step 1 — choose a submission type ?>
  <div class="card"><div class="card-body">
    <p class="text-muted"><?php echo __('Choose the kind of archaeological archive you are describing. Each type is described against ISAD(G).'); ?></p>
    <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'submissionEdit']); ?>">
      <input type="hidden" name="do" value="create">
      <div class="mb-3">
        <label class="form-label fw-bold" for="type"><?php echo __('Submission type'); ?></label>
        <select class="form-select" id="type" name="type" required>
          <?php foreach ($types as $key => $t): ?>
            <option value="<?php echo $key; ?>"><?php echo esc_specialchars($t['label']); ?> (<?php echo $t['level']; ?>)</option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label fw-bold" for="title"><?php echo __('Collection or project title'); ?></label>
        <input type="text" class="form-control" id="title" name="title" required>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right me-1"></i><?php echo __('Continue'); ?></button>
    </form>
  </div></div>
<?php else: ?>
  <?php // Step 2 — ISAD(G) description form ?>
  <?php if (!$editable): ?>
    <div class="alert alert-secondary"><i class="fas fa-lock me-2"></i>
      <?php echo __('This submission is'); ?> <strong><?php echo SubmissionService::statusLabel($submission['status']); ?></strong>
      <?php echo __('and is read-only.'); ?></div>
  <?php endif ?>
  <?php if ('returned' === $submission['status'] && !empty($submission['return_comment'])): ?>
    <div class="alert alert-warning"><i class="fas fa-comment-dots me-2"></i>
      <strong><?php echo __('Returned for revision:'); ?></strong> <?php echo esc_specialchars($submission['return_comment']); ?></div>
  <?php endif ?>

  <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'submissionEdit']); ?>">
    <input type="hidden" name="id" value="<?php echo (int) $submission['id']; ?>">
    <div class="card mb-3"><div class="card-body">
      <div class="row g-3">
        <div class="col-md-8"><label class="form-label fw-bold"><?php echo __('Collection or project title'); ?></label>
          <input type="text" class="form-control" name="title" value="<?php echo esc_specialchars($val('title')); ?>" <?php echo $ro; ?> required></div>
        <div class="col-md-4"><label class="form-label fw-bold"><?php echo __('Level of description'); ?></label>
          <select class="form-select" name="level_of_description" <?php echo $ro; ?>>
            <?php foreach ($levels as $lvl): ?>
              <option value="<?php echo $lvl; ?>" <?php echo $val('level_of_description') === $lvl ? 'selected' : ''; ?>><?php echo $lvl; ?></option>
            <?php endforeach ?>
          </select></div>
        <div class="col-md-6"><label class="form-label"><?php echo __('Reference / identifier'); ?></label>
          <input type="text" class="form-control" name="identifier" value="<?php echo esc_specialchars($val('identifier')); ?>" <?php echo $ro; ?>></div>
        <div class="col-md-6"><label class="form-label"><?php echo __('Date range of research'); ?></label>
          <input type="text" class="form-control" name="date_display" placeholder="e.g. 1995–1998" value="<?php echo esc_specialchars($val('date_display')); ?>" <?php echo $ro; ?>></div>
        <div class="col-12"><label class="form-label"><?php echo __('What does this material document? (scope and content)'); ?></label>
          <textarea class="form-control" rows="4" name="scope_and_content" <?php echo $ro; ?>><?php echo esc_specialchars($val('scope_and_content')); ?></textarea></div>
        <div class="col-md-6"><label class="form-label"><?php echo __('Researcher / principal investigator(s)'); ?></label>
          <input type="text" class="form-control" name="creators" value="<?php echo esc_specialchars($val('creators')); ?>" <?php echo $ro; ?>></div>
        <div class="col-md-6"><label class="form-label"><?php echo __('Site name(s) / geographic area'); ?></label>
          <input type="text" class="form-control" name="places" value="<?php echo esc_specialchars($val('places')); ?>" <?php echo $ro; ?>></div>
        <div class="col-md-6"><label class="form-label"><?php echo __('Subjects / periods / material types'); ?></label>
          <input type="text" class="form-control" name="subjects" value="<?php echo esc_specialchars($val('subjects')); ?>" <?php echo $ro; ?>></div>
        <div class="col-md-6"><label class="form-label"><?php echo __('Extent and medium'); ?></label>
          <input type="text" class="form-control" name="extent_and_medium" value="<?php echo esc_specialchars($val('extent_and_medium')); ?>" <?php echo $ro; ?>></div>
        <div class="col-md-6"><label class="form-label"><?php echo __('Who can access it? (conditions governing access)'); ?></label>
          <textarea class="form-control" rows="2" name="access_conditions" <?php echo $ro; ?>><?php echo esc_specialchars($val('access_conditions')); ?></textarea></div>
        <div class="col-md-6"><label class="form-label"><?php echo __('Can images or files be reused? (reproduction)'); ?></label>
          <textarea class="form-control" rows="2" name="reproduction_conditions" <?php echo $ro; ?>><?php echo esc_specialchars($val('reproduction_conditions')); ?></textarea></div>
        <div class="col-12"><label class="form-label"><?php echo __('Related field notes, reports or datasets (notes)'); ?></label>
          <textarea class="form-control" rows="2" name="notes" <?php echo $ro; ?>><?php echo esc_specialchars($val('notes')); ?></textarea></div>
      </div>
    </div></div>
    <?php if ($editable): ?>
    <div class="d-flex gap-2">
      <button type="submit" name="do" value="save" class="btn btn-outline-primary"><i class="fas fa-save me-1"></i><?php echo __('Save draft'); ?></button>
      <button type="submit" name="do" value="submit" class="btn btn-success"
              onclick="return confirm('<?php echo __('Submit for archival review? You will not be able to edit it while it is under review.'); ?>')">
        <i class="fas fa-paper-plane me-1"></i><?php echo __('Submit for review'); ?></button>
    </div>
    <?php endif ?>
  </form>
<?php endif ?>
<?php end_slot() ?>
