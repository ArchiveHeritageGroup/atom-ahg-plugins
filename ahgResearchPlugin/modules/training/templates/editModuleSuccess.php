<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('training/trainingSidebar', ['active' => $sidebarActive ?? 'training']) ?>
<?php end_slot() ?>
<?php
$module = isset($module) && is_array($module) ? $module : [];
$course = isset($course) && is_array($course) ? $course : [];
$lectures = isset($lectures) && is_array($lectures) ? $lectures : [];
$cid = (int) ($course['id'] ?? 0);
?>
<?php slot('title') ?>
<h1><i class="fas fa-layer-group text-primary me-2"></i><?php echo __('Edit module'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'training', 'action' => 'index']); ?>"><?php echo __('Training'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'training', 'action' => 'show', 'id' => $cid]); ?>"><?php echo htmlspecialchars((string) ($course['title'] ?? '')); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Module'); ?></li>
  </ol>
</nav>

<form method="post" class="card">
  <div class="card-body">
    <div class="mb-3">
      <label class="form-label"><?php echo __('Module title'); ?> *</label>
      <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars((string) ($module['title'] ?? '')); ?>">
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label"><?php echo __('Reuse curriculum lecture'); ?></label>
        <select name="lecture_id" class="form-select">
          <option value=""><?php echo __('-- none (use own Markdown below) --'); ?></option>
          <?php foreach ($lectures as $lec): ?>
            <option value="<?php echo (int) $lec['id']; ?>" <?php echo (int) ($module['lecture_id'] ?? 0) === (int) $lec['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $lec['title']); ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!$lectures): ?><small class="text-muted"><?php echo __('No curriculum lectures available (research_lecture not present).'); ?></small><?php endif; ?>
      </div>
      <div class="col-md-2 mb-3">
        <label class="form-label"><?php echo __('Sort order'); ?></label>
        <input type="number" name="sort_order" class="form-control" value="<?php echo (int) ($module['sort_order'] ?? 0); ?>">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label"><?php echo __('Own content (Markdown)'); ?></label>
      <textarea name="body_markdown" class="form-control" rows="10"><?php echo htmlspecialchars((string) ($module['body_markdown'] ?? '')); ?></textarea>
      <small class="text-muted"><?php echo __('Used when no lecture is reused. GitHub-flavoured Markdown.'); ?></small>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <button type="submit" name="form_action" value="delete" class="btn btn-outline-danger" onclick="return confirm('<?php echo __('Delete this module?'); ?>');"><i class="fas fa-trash me-1"></i><?php echo __('Delete'); ?></button>
    <span>
      <a href="<?php echo url_for(['module' => 'training', 'action' => 'show', 'id' => $cid]); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
      <button type="submit" name="form_action" value="save" class="btn btn-primary"><?php echo __('Save module'); ?></button>
    </span>
  </div>
</form>
<?php end_slot() ?>
