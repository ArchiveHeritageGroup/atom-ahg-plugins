<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Add Release'); ?> - <?php echo htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Vendor Dashboard'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorDashboard'])],
  ['label' => __('Software'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorSoftware'])],
  ['label' => htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorSoftwareReleases', 'id' => $software->id])],
  ['label' => __('Add Release')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <h1 class="h3 mb-2"><?php echo __('Add Release'); ?></h1>
    <p class="text-muted mb-4"><?php echo htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareReleaseAdd', 'id' => $software->id]); ?>">

      <div class="card mb-4">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="rf-version" class="form-label"><?php echo __('Version'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="rf-version" name="version" value="<?php echo htmlspecialchars($sf_request->getParameter('version', ''), ENT_QUOTES, 'UTF-8'); ?>" required placeholder="<?php echo __('e.g., 1.0.0, 2.8.2'); ?>">
            </div>
            <div class="col-md-6">
              <label for="rf-type" class="form-label"><?php echo __('Release Type'); ?></label>
              <select class="form-select" id="rf-type" name="release_type">
                <?php
                  $relTypes = ['patch' => __('Patch'), 'minor' => __('Minor'), 'major' => __('Major'), 'rc' => __('Release Candidate'), 'beta' => __('Beta'), 'alpha' => __('Alpha')];
                  $selType = $sf_request->getParameter('release_type', 'patch');
                  foreach ($relTypes as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selType === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label for="rf-notes" class="form-label"><?php echo __('Release Notes'); ?></label>
              <textarea class="form-control" id="rf-notes" name="release_notes" rows="6" placeholder="<?php echo __('Describe what changed in this release...'); ?>"><?php echo htmlspecialchars($sf_request->getParameter('release_notes', ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="col-md-6">
              <label for="rf-tag" class="form-label"><?php echo __('Git Tag'); ?></label>
              <input type="text" class="form-control" id="rf-tag" name="git_tag" value="<?php echo htmlspecialchars($sf_request->getParameter('git_tag', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g., v1.0.0'); ?>">
            </div>
            <div class="col-md-6">
              <label for="rf-commit" class="form-label"><?php echo __('Git Commit'); ?></label>
              <input type="text" class="form-control" id="rf-commit" name="git_commit" value="<?php echo htmlspecialchars($sf_request->getParameter('git_commit', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g., abc1234'); ?>">
            </div>
            <div class="col-md-6">
              <label for="rf-date" class="form-label"><?php echo __('Release Date'); ?></label>
              <input type="datetime-local" class="form-control" id="rf-date" name="released_at" value="<?php echo htmlspecialchars($sf_request->getParameter('released_at', date('Y-m-d\TH:i')), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="rf-stable" name="is_stable" value="1"<?php echo (null === $sf_request->getParameter('is_stable', null) || $sf_request->getParameter('is_stable')) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="rf-stable"><?php echo __('Stable release'); ?></label>
                <div class="form-text"><?php echo __('Uncheck for pre-release or beta versions.'); ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareReleases', 'id' => $software->id]); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> <?php echo __('Add Release'); ?></button>
      </div>

    </form>

  </div>
</div>

<?php end_slot(); ?>
