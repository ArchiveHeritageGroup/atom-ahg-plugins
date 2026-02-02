<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1><?php echo __('CSV Export'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><?php echo __('Export Archival Descriptions to CSV'); ?></h5>
  </div>
  <div class="card-body">

    <?php if ($sf_user->hasFlash('error')): ?>
      <div class="alert alert-danger"><?php echo $sf_user->getFlash('error') ?></div>
    <?php endif ?>

    <div class="alert alert-info">
      <i class="bi bi-info-circle me-2"></i>
      <?php echo __('Export archival descriptions in CSV format compatible with AtoM import.'); ?>
    </div>

    <form action="<?php echo url_for(['module' => 'export', 'action' => 'csv']) ?>" method="post">

      <div class="mb-3">
        <label class="form-label"><?php echo __('Repository'); ?></label>
        <select name="repository_id" class="form-select">
          <option value=""><?php echo __('All repositories'); ?></option>
          <?php foreach ($repositories as $repo): ?>
            <option value="<?php echo $repo->id ?>"><?php echo htmlspecialchars($repo->name) ?></option>
          <?php endforeach ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label"><?php echo __('Level of Description'); ?></label>
        <select name="level_ids[]" class="form-select" multiple size="5">
          <?php foreach ($levels as $level): ?>
            <option value="<?php echo $level->id ?>"><?php echo htmlspecialchars($level->name) ?></option>
          <?php endforeach ?>
        </select>
        <small class="text-muted"><?php echo __('Hold Ctrl/Cmd to select multiple. Leave empty for all levels.'); ?></small>
      </div>

      <div class="mb-3">
        <label class="form-label"><?php echo __('Parent Record Slug (Optional)'); ?></label>
        <input type="text" name="parent_slug" class="form-control" placeholder="<?php echo __('e.g. my-fonds-123'); ?>">
        <small class="text-muted"><?php echo __('Export only descendants of this record.'); ?></small>
      </div>

      <div class="mb-3 form-check">
        <input type="checkbox" name="include_descendants" value="1" class="form-check-input" id="includeDescendants">
        <label class="form-check-label" for="includeDescendants">
          <?php echo __('Include all descendants (not just direct children)'); ?>
        </label>
      </div>

      <hr>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'export', 'action' => 'index']) ?>" class="btn btn-secondary">
          <i class="bi bi-arrow-left me-1"></i><?php echo __('Back'); ?>
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-download me-1"></i><?php echo __('Export CSV'); ?>
        </button>
      </div>

    </form>

  </div>
</div>

<?php end_slot() ?>
