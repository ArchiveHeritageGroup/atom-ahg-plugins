<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Vendor Relationships'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Institution'), 'url' => url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard'])],
  ['label' => __('Vendor Relationships')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Vendor Relationships'); ?></h1>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard']); ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
  </a>
</div>

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
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No vendor relationships'); ?></h5>
  <p class="text-muted"><?php echo __('Vendor relationships are established by vendors through the registry. Browse vendors to connect.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorBrowse']); ?>" class="btn btn-primary">
    <i class="fas fa-search me-1"></i> <?php echo __('Browse Vendors'); ?>
  </a>
</div>
<?php endif; ?>

<?php end_slot(); ?>
