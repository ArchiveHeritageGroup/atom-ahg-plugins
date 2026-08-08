<?php decorate_with('layout_1col.php'); ?>
<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .rdm-max-width-640px-7f45 { max-width:640px; }
</style>

<?php slot('title'); ?>
  <h1>New research dataset</h1>
<?php end_slot(); ?>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<form method="post" action="<?php echo url_for('@rdm_datasets_create'); ?>" class="mt-3 rdm-max-width-640px-7f45" >
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
