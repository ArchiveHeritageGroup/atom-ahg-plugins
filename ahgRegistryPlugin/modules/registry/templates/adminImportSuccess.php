<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('WordPress Import'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('WordPress Import')],
]]); ?>

<h1 class="h3 mb-4"><?php echo __('WordPress Import'); ?></h1>

<!-- Errors -->
<?php if (isset($errors) && count($errors) > 0): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <i class="fas fa-exclamation-triangle me-2"></i>
  <strong><?php echo __('Import Errors'); ?></strong>
  <ul class="mb-0 mt-2">
    <?php foreach ($errors as $err): ?>
      <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
    <?php endforeach; ?>
  </ul>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('Close'); ?>"></button>
</div>
<?php endif; ?>

<!-- Import results -->
<?php if (!empty($imported)): ?>
<div class="alert alert-success">
  <i class="fas fa-check-circle me-2"></i>
  <strong><?php echo __('Import Completed Successfully'); ?></strong>
  <?php if (!empty($importResult)): ?>
  <div class="mt-3">
    <div class="row g-3">
      <?php if (isset($importResult['institutions'])): ?>
      <div class="col-auto">
        <span class="badge bg-primary fs-6"><?php echo (int) $importResult['institutions']; ?> <?php echo __('institutions'); ?></span>
      </div>
      <?php endif; ?>
      <?php if (isset($importResult['vendors'])): ?>
      <div class="col-auto">
        <span class="badge bg-success fs-6"><?php echo (int) $importResult['vendors']; ?> <?php echo __('vendors'); ?></span>
      </div>
      <?php endif; ?>
      <?php if (isset($importResult['software'])): ?>
      <div class="col-auto">
        <span class="badge bg-info fs-6"><?php echo (int) $importResult['software']; ?> <?php echo __('software'); ?></span>
      </div>
      <?php endif; ?>
      <?php if (isset($importResult['groups'])): ?>
      <div class="col-auto">
        <span class="badge bg-secondary fs-6"><?php echo (int) $importResult['groups']; ?> <?php echo __('groups'); ?></span>
      </div>
      <?php endif; ?>
      <?php if (isset($importResult['blog_posts'])): ?>
      <div class="col-auto">
        <span class="badge bg-warning text-dark fs-6"><?php echo (int) $importResult['blog_posts']; ?> <?php echo __('blog posts'); ?></span>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Preview results -->
<?php if (!empty($preview)): ?>
<div class="card mb-4">
  <div class="card-header fw-semibold bg-info text-white">
    <i class="fas fa-eye me-2"></i><?php echo __('Import Preview'); ?>
  </div>
  <div class="card-body">

    <?php if (!empty($preview['institutions'])): ?>
    <h5 class="mb-2"><?php echo __('Institutions'); ?> <span class="badge bg-primary"><?php echo count($preview['institutions']); ?></span></h5>
    <div class="table-responsive mb-4">
      <table class="table table-sm table-bordered">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Country'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($preview['institutions'] as $inst): ?>
          <tr>
            <td><?php echo htmlspecialchars($inst['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($inst['type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($inst['country'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($preview['vendors'])): ?>
    <h5 class="mb-2"><?php echo __('Vendors'); ?> <span class="badge bg-success"><?php echo count($preview['vendors']); ?></span></h5>
    <div class="table-responsive mb-4">
      <table class="table table-sm table-bordered">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Country'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($preview['vendors'] as $v): ?>
          <tr>
            <td><?php echo htmlspecialchars($v['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($v['type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($v['country'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($preview['software'])): ?>
    <h5 class="mb-2"><?php echo __('Software'); ?> <span class="badge bg-info"><?php echo count($preview['software']); ?></span></h5>
    <div class="table-responsive mb-4">
      <table class="table table-sm table-bordered">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Category'); ?></th>
            <th><?php echo __('License'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($preview['software'] as $sw): ?>
          <tr>
            <td><?php echo htmlspecialchars($sw['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($sw['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($sw['license'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </div>
  <div class="card-footer text-end">
    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminImport']); ?>" class="d-inline">
      <input type="hidden" name="form_action" value="import">
      <input type="hidden" name="import_data" value="<?php echo htmlspecialchars($sf_request->getParameter('import_data', ''), ENT_QUOTES, 'UTF-8'); ?>">
      <button type="submit" class="btn btn-success">
        <i class="fas fa-file-import me-1"></i> <?php echo __('Confirm Import'); ?>
      </button>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Import form -->
<div class="card">
  <div class="card-header fw-semibold">
    <i class="fas fa-file-import me-2"></i><?php echo __('Paste JSON Data'); ?>
  </div>
  <div class="card-body">
    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminImport']); ?>">
      <div class="mb-3">
        <label for="import_data" class="form-label"><?php echo __('WordPress Export JSON'); ?></label>
        <textarea class="form-control font-monospace" id="import_data" name="import_data" rows="12" placeholder="<?php echo __('Paste your WordPress export JSON data here...'); ?>"><?php echo htmlspecialchars($sf_request->getParameter('import_data', ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        <div class="form-text"><?php echo __('Paste the exported JSON data from your WordPress site. The data will be validated before import.'); ?></div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" name="form_action" value="preview" class="btn btn-outline-primary">
          <i class="fas fa-eye me-1"></i> <?php echo __('Preview'); ?>
        </button>
        <button type="submit" name="form_action" value="import" class="btn btn-success">
          <i class="fas fa-file-import me-1"></i> <?php echo __('Import'); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<?php end_slot(); ?>
