<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Service Provider Relationships'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Institution'), 'url' => url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard'])],
  ['label' => __('Service Providers')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Service Provider Relationships'); ?></h1>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard']); ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
  </a>
</div>

<!-- Link vendor form -->
<?php if (!empty($allVendors)): ?>
<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="fas fa-handshake me-2 text-success"></i><?php echo __('Link Service Provider'); ?></div>
  <div class="card-body">
    <form method="post" class="row g-2 align-items-end">
      <input type="hidden" name="form_action" value="add">
      <div class="col-md-3">
        <label class="form-label small fw-semibold"><?php echo __('Vendor'); ?></label>
        <select name="vendor_id" class="form-select" required>
          <option value=""><?php echo __('-- Select vendor --'); ?></option>
          <?php foreach ($allVendors as $v): ?>
            <option value="<?php echo (int) $v->id; ?>"><?php echo htmlspecialchars($v->name, ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold"><?php echo __('Relationship type'); ?></label>
        <select name="relationship_type" class="form-select" required>
          <option value=""><?php echo __('-- Select type --'); ?></option>
          <option value="developer"><?php echo __('Developer'); ?></option>
          <option value="hosting"><?php echo __('Hosting'); ?></option>
          <option value="maintenance"><?php echo __('Maintenance'); ?></option>
          <option value="consulting"><?php echo __('Consulting'); ?></option>
          <option value="digitization"><?php echo __('Digitization'); ?></option>
          <option value="training"><?php echo __('Training'); ?></option>
          <option value="integration"><?php echo __('Integration'); ?></option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold"><?php echo __('Service description'); ?></label>
        <input type="text" name="service_description" class="form-control" placeholder="<?php echo __('Optional'); ?>">
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-success w-100"><i class="fas fa-link me-1"></i> <?php echo __('Link Vendor'); ?></button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($vendors) && count($vendors) > 0): ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Vendor'); ?></th>
          <th><?php echo __('Relationship Type'); ?></th>
          <th><?php echo __('Service Description'); ?></th>
          <th class="text-center"><?php echo __('Status'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vendors as $rel): ?>
        <tr>
          <td>
            <?php if (!empty($rel->vendor_slug)): ?>
              <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorView', 'slug' => $rel->vendor_slug]); ?>">
                <strong><?php echo htmlspecialchars($rel->vendor_name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
              </a>
            <?php else: ?>
              <strong><?php echo htmlspecialchars($rel->vendor_name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php endif; ?>
          </td>
          <td>
            <?php
              $relColors = [
                'developer' => 'primary', 'hosting' => 'info', 'maintenance' => 'success',
                'consulting' => 'warning', 'digitization' => 'secondary', 'training' => 'dark',
                'integration' => 'danger',
              ];
              $rType = $rel->relationship_type ?? '';
              $rColor = $relColors[$rType] ?? 'secondary';
            ?>
            <span class="badge bg-<?php echo $rColor; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $rType)), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td><?php echo htmlspecialchars($rel->service_description ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="text-center">
            <?php if (!empty($rel->is_active)): ?>
              <span class="badge bg-success"><?php echo __('Active'); ?></span>
            <?php else: ?>
              <span class="badge bg-secondary"><?php echo __('Inactive'); ?></span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionVendorRemove', 'id' => (int) $rel->relationship_id]); ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Remove this service provider relationship? The vendor will not be deleted, only de-linked from your institution.'); ?>');">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Remove relationship'); ?>">
                <i class="fas fa-unlink me-1"></i><?php echo __('Remove'); ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No service provider relationships yet'); ?></h5>
  <p class="text-muted"><?php echo __('Use the form above to link a vendor, or browse vendors to find service providers.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorBrowse']); ?>" class="btn btn-primary">
    <i class="fas fa-search me-1"></i> <?php echo __('Browse Vendors'); ?>
  </a>
</div>
<?php endif; ?>

<?php end_slot(); ?>
