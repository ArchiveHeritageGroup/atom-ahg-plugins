<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Client Institutions'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Vendor Dashboard'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorDashboard'])],
  ['label' => __('Clients')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Client Institutions'); ?></h1>
  <div>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorClientAdd']); ?>" class="btn btn-primary btn-sm">
      <i class="fas fa-plus me-1"></i> <?php echo __('Add Client'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorDashboard']); ?>" class="btn btn-outline-secondary btn-sm ms-1">
      <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
    </a>
  </div>
</div>

<?php if (!empty($clients) && count($clients) > 0): ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Institution'); ?></th>
          <th><?php echo __('Relationship Type'); ?></th>
          <th><?php echo __('Service Description'); ?></th>
          <th><?php echo __('Start Date'); ?></th>
          <th class="text-center"><?php echo __('Status'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clients as $cl): ?>
        <tr>
          <td>
            <?php if (!empty($cl->institution_slug)): ?>
              <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $cl->institution_slug]); ?>">
                <strong><?php echo htmlspecialchars($cl->institution_name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
              </a>
            <?php else: ?>
              <strong><?php echo htmlspecialchars($cl->institution_name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php endif; ?>
          </td>
          <td>
            <?php
              $relColors = ['developer' => 'primary', 'hosting' => 'info', 'maintenance' => 'success', 'consulting' => 'warning', 'digitization' => 'secondary', 'training' => 'dark', 'integration' => 'danger'];
              $rType = $cl->relationship_type ?? '';
              $rColor = $relColors[$rType] ?? 'secondary';
            ?>
            <span class="badge bg-<?php echo $rColor; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $rType)), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td><?php echo htmlspecialchars($cl->service_description ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php echo !empty($cl->start_date) ? date('M j, Y', strtotime($cl->start_date)) : '<span class="text-muted">-</span>'; ?>
          </td>
          <td class="text-center">
            <?php if (!empty($cl->is_active)): ?>
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
  <i class="fas fa-building fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No client relationships yet'); ?></h5>
  <p class="text-muted"><?php echo __('Add your first client institution to showcase your AtoM partnerships.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorClientAdd']); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i> <?php echo __('Add Client'); ?>
  </a>
</div>
<?php endif; ?>

<?php end_slot(); ?>
