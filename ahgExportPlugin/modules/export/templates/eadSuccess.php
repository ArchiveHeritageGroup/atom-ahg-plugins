<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1><?php echo __('EAD 2002 Export'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><?php echo __('Export to Encoded Archival Description (EAD)'); ?></h5>
  </div>
  <div class="card-body">

    <?php if ($sf_user->hasFlash('error')): ?>
      <div class="alert alert-danger"><?php echo $sf_user->getFlash('error') ?></div>
    <?php endif ?>

    <div class="alert alert-info">
      <i class="bi bi-info-circle me-2"></i>
      <?php echo __('Export archival descriptions in EAD 2002 XML format. Select a top-level record (fonds/collection) to export with its hierarchy.'); ?>
    </div>

    <form action="<?php echo url_for(['module' => 'export', 'action' => 'ead']) ?>" method="post">

      <div class="mb-3">
        <label class="form-label"><?php echo __('Select Record to Export'); ?></label>
        <select name="object_id" class="form-select" required>
          <option value=""><?php echo __('-- Select a fonds or collection --'); ?></option>
          <?php foreach ($fonds as $f): ?>
            <option value="<?php echo $f->id ?>">
              <?php echo htmlspecialchars(($f->identifier ? $f->identifier . ' - ' : '') . $f->title) ?>
            </option>
          <?php endforeach ?>
        </select>
        <small class="text-muted"><?php echo __('Showing top-level archival descriptions.'); ?></small>
      </div>

      <div class="mb-3 form-check">
        <input type="checkbox" name="include_descendants" value="1" class="form-check-input" id="includeDescendants" checked>
        <label class="form-check-label" for="includeDescendants">
          <?php echo __('Include all descendants (series, files, items)'); ?>
        </label>
      </div>

      <hr>

      <h6><?php echo __('EAD Export includes:'); ?></h6>
      <ul class="small text-muted">
        <li><?php echo __('Descriptive identification (unitid, unittitle, unitdate)'); ?></li>
        <li><?php echo __('Scope and content'); ?></li>
        <li><?php echo __('Arrangement'); ?></li>
        <li><?php echo __('Access and use restrictions'); ?></li>
        <li><?php echo __('Custodial history'); ?></li>
        <li><?php echo __('Subject access points'); ?></li>
        <li><?php echo __('Hierarchical component structure (dsc/c)'); ?></li>
      </ul>

      <hr>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'export', 'action' => 'index']) ?>" class="btn btn-secondary">
          <i class="bi bi-arrow-left me-1"></i><?php echo __('Back'); ?>
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-download me-1"></i><?php echo __('Export EAD XML'); ?>
        </button>
      </div>

    </form>

  </div>
</div>

<?php end_slot() ?>
