<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $isNew = empty($standard); ?>
<?php slot('title'); ?><?php echo $isNew ? __('Add Standard') : __('Edit Standard'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Standards'), 'url' => url_for(['module' => 'registry', 'action' => 'adminStandards'])],
  ['label' => $isNew ? __('Add') : __('Edit')],
]]); ?>

<h1 class="h3 mb-4"><?php echo $isNew ? __('Add Standard') : __('Edit Standard'); ?></h1>

<form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandardEdit']); ?>">
  <input type="hidden" name="form_action" value="save">
  <?php if (!$isNew): ?>
    <input type="hidden" name="id" value="<?php echo (int) $standard->id; ?>">
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0"><?php echo __('Standard Details'); ?></h5>
    </div>
    <div class="card-body">

      <div class="row mb-3">
        <div class="col-md-8">
          <label for="name" class="form-label"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($standard->name ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-4">
          <label for="acronym" class="form-label"><?php echo __('Acronym'); ?></label>
          <input type="text" class="form-control" id="acronym" name="acronym" value="<?php echo htmlspecialchars($standard->acronym ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>

      <div class="mb-3">
        <label for="category" class="form-label"><?php echo __('Category'); ?></label>
        <?php $categoryOptions = ['descriptive', 'preservation', 'rights', 'accounting', 'compliance', 'metadata', 'interchange', 'sector']; ?>
        <select class="form-select" id="category" name="category">
          <option value=""><?php echo __('-- Select category --'); ?></option>
          <?php foreach ($categoryOptions as $opt): ?>
            <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (($standard->category ?? '') === $opt) ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $opt)), ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label for="short_description" class="form-label"><?php echo __('Short Description'); ?></label>
        <textarea class="form-control" id="short_description" name="short_description" rows="2"><?php echo htmlspecialchars($standard->short_description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <div class="mb-3">
        <label for="description" class="form-label"><?php echo __('Description'); ?></label>
        <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($standard->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label for="website_url" class="form-label"><?php echo __('Website URL'); ?></label>
          <input type="url" class="form-control" id="website_url" name="website_url" value="<?php echo htmlspecialchars($standard->website_url ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
          <label for="issuing_body" class="form-label"><?php echo __('Issuing Body'); ?></label>
          <input type="text" class="form-control" id="issuing_body" name="issuing_body" value="<?php echo htmlspecialchars($standard->issuing_body ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label for="current_version" class="form-label"><?php echo __('Current Version'); ?></label>
          <input type="text" class="form-control" id="current_version" name="current_version" value="<?php echo htmlspecialchars($standard->current_version ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
          <label for="publication_year" class="form-label"><?php echo __('Publication Year'); ?></label>
          <input type="number" class="form-control" id="publication_year" name="publication_year" min="1900" max="2100" value="<?php echo htmlspecialchars($standard->publication_year ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label"><?php echo __('Sector Applicability'); ?></label>
        <?php
          $sectorOptions = ['archive', 'library', 'museum', 'gallery', 'dam'];
          $rawApplicability = isset($standard->sector_applicability) ? sfOutputEscaper::unescape($standard->sector_applicability) : null;
          $currentSectors = [];
          if (is_string($rawApplicability)) {
              $decoded = json_decode($rawApplicability, true);
              if (is_array($decoded)) {
                  $currentSectors = $decoded;
              }
          } elseif (is_array($rawApplicability)) {
              $currentSectors = $rawApplicability;
          }
        ?>
        <div class="d-flex flex-wrap gap-3">
          <?php foreach ($sectorOptions as $sector): ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="sector_applicability[]" value="<?php echo htmlspecialchars($sector, ENT_QUOTES, 'UTF-8'); ?>" id="sector_<?php echo $sector; ?>"<?php echo in_array($sector, $currentSectors) ? ' checked="checked"' : ''; ?>>
            <label class="form-check-label" for="sector_<?php echo $sector; ?>"><?php echo htmlspecialchars(ucfirst($sector), ENT_QUOTES, 'UTF-8'); ?></label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured"<?php echo (!empty($standard->is_featured)) ? ' checked="checked"' : ''; ?>>
            <label class="form-check-label" for="is_featured"><?php echo __('Featured'); ?></label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"<?php echo ($isNew || !isset($standard->is_active) || $standard->is_active) ? ' checked="checked"' : ''; ?>>
            <label class="form-check-label" for="is_active"><?php echo __('Active'); ?></label>
          </div>
        </div>
        <div class="col-md-4">
          <label for="sort_order" class="form-label"><?php echo __('Sort Order'); ?></label>
          <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" value="<?php echo (int) ($standard->sort_order ?? 0); ?>">
        </div>
      </div>

    </div>
  </div>

  <div class="d-flex gap-2 mb-4">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save me-1"></i> <?php echo __('Save'); ?>
    </button>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandards']); ?>" class="btn btn-outline-secondary">
      <?php echo __('Cancel'); ?>
    </a>
  </div>
</form>

<!-- Extensions section (only for existing standards) -->
<?php if (!$isNew): ?>
<div class="card mb-4" id="extensions">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0"><?php echo __('Heratio Extensions'); ?></h5>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminExtensionEdit', 'standardId' => (int) $standard->id]); ?>" class="btn btn-sm btn-primary">
      <i class="fas fa-plus me-1"></i> <?php echo __('Add Extension'); ?>
    </a>
  </div>
  <div class="card-body">
    <?php if (!empty($extensions)): ?>
    <div class="table-responsive">
      <table class="table table-hover table-striped align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Title'); ?></th>
            <th><?php echo __('Plugin'); ?></th>
            <th style="width: 60px;"><?php echo __('Sort'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($extensions as $ext): ?>
          <tr>
            <td>
              <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $ext->extension_type ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
            </td>
            <td><?php echo htmlspecialchars($ext->title ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
              <?php if (!empty($ext->plugin_name)): ?>
                <code><?php echo htmlspecialchars($ext->plugin_name, ENT_QUOTES, 'UTF-8'); ?></code>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td><?php echo (int) ($ext->sort_order ?? 0); ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminExtensionEdit', 'standardId' => (int) $standard->id, 'id' => (int) $ext->id]); ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Edit'); ?>">
                  <i class="fas fa-edit"></i>
                </a>
                <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandardEdit', 'id' => (int) $standard->id]); ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Are you sure you want to delete this extension?'); ?>');">
                  <input type="hidden" name="form_action" value="delete_extension">
                  <input type="hidden" name="extension_id" value="<?php echo (int) $ext->id; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Delete'); ?>">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="text-center py-4">
      <i class="fas fa-puzzle-piece fa-2x text-muted mb-2"></i>
      <p class="text-muted mb-0"><?php echo __('No extensions defined for this standard yet.'); ?></p>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php end_slot(); ?>
