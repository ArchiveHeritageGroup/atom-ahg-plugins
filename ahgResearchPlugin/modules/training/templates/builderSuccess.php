<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('training/trainingSidebar', ['active' => $sidebarActive ?? 'training']) ?>
<?php end_slot() ?>
<?php
$course = isset($course) && is_array($course) ? $course : null;
$isEdit = $course !== null;
?>
<?php slot('title') ?>
<h1><i class="fas fa-graduation-cap text-primary me-2"></i><?php echo $isEdit ? __('Edit course') : __('New course'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'training', 'action' => 'index']); ?>"><?php echo __('Training'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo $isEdit ? __('Edit') : __('New'); ?></li>
  </ol>
</nav>

<form method="post" class="card">
  <div class="card-body">
    <div class="mb-3">
      <label class="form-label"><?php echo __('Title'); ?> *</label>
      <input type="text" name="title" class="form-control" required
             value="<?php echo htmlspecialchars((string) ($course['title'] ?? '')); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label"><?php echo __('Description'); ?></label>
      <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars((string) ($course['description'] ?? '')); ?></textarea>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label"><?php echo __('Audience / role'); ?></label>
        <input type="text" name="audience" class="form-control"
               value="<?php echo htmlspecialchars((string) ($course['audience'] ?? '')); ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label"><?php echo __('Language'); ?></label>
        <input type="text" name="language" class="form-control"
               value="<?php echo htmlspecialchars((string) ($course['language'] ?? '')); ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label"><?php echo __('Pass mark %'); ?></label>
        <input type="number" name="pass_mark" class="form-control" min="0" max="100"
               value="<?php echo (int) ($course['pass_mark'] ?? 80); ?>">
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label"><?php echo __('Status'); ?></label>
        <select name="status" class="form-select">
          <?php foreach (['draft', 'published', 'archived'] as $st): ?>
            <option value="<?php echo $st; ?>" <?php echo ($course['status'] ?? 'draft') === $st ? 'selected' : ''; ?>><?php echo __(ucfirst($st)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label"><?php echo __('Sort order'); ?></label>
        <input type="number" name="sort_order" class="form-control" value="<?php echo (int) ($course['sort_order'] ?? 0); ?>">
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <a href="<?php echo $isEdit ? url_for(['module' => 'training', 'action' => 'show', 'id' => $course['id']]) : url_for(['module' => 'training', 'action' => 'index']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
    <button type="submit" class="btn btn-primary"><?php echo __('Save course'); ?></button>
  </div>
</form>
<?php end_slot() ?>
