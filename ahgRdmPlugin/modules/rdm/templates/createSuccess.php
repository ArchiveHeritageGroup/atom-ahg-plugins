<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1>New research dataset</h1>
<?php end_slot(); ?>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<form method="post" action="<?php echo url_for('@rdm_datasets_create'); ?>" class="mt-3" style="max-width:640px;">
  <div class="mb-3">
    <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="title" name="title" required maxlength="500"
           value="<?php echo esc_specialchars($sf_request->getParameter('title', '')); ?>">
  </div>

  <div class="mb-3">
    <label class="form-label" for="description">Description</label>
    <textarea class="form-control" id="description" name="description" rows="4"><?php echo esc_specialchars($sf_request->getParameter('description', '')); ?></textarea>
    <div class="form-text">Becomes the container record's scope &amp; content.</div>
  </div>

  <div class="mb-3">
    <label class="form-label" for="project_id">Research project ID</label>
    <input type="number" class="form-control" id="project_id" name="project_id" min="1"
           value="<?php echo esc_specialchars($sf_request->getParameter('project_id', '')); ?>">
    <div class="form-text">Optional — link to an existing <code>research_project</code>.</div>
  </div>

  <button type="submit" class="btn btn-primary">Create dataset</button>
  <a class="btn btn-link" href="<?php echo url_for('@rdm_datasets_index'); ?>">Cancel</a>
</form>
