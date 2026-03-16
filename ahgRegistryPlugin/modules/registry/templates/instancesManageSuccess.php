<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Manage Instances'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Institution'), 'url' => url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard'])],
  ['label' => __('Instances')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('System Instances'); ?></h1>
  <div>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstanceAdd']); ?>" class="btn btn-primary btn-sm">
      <i class="fas fa-plus me-1"></i> <?php echo __('Add Instance'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard']); ?>" class="btn btn-outline-secondary btn-sm ms-1">
      <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
    </a>
  </div>
</div>

<?php if (!empty($instances) && count($instances) > 0): ?>
<div class="card mb-4">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('URL'); ?></th>
          <th><?php echo __('Type'); ?></th>
          <th><?php echo __('Software'); ?></th>
          <th><?php echo __('Hosting'); ?></th>
          <th class="text-center"><?php echo __('Status'); ?></th>
          <th class="text-center"><?php echo __('Sync'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($instances as $inst): ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($inst->name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></td>
          <td>
            <?php if (!empty($inst->url)): ?>
              <a href="<?php echo htmlspecialchars($inst->url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="text-truncate d-inline-block" style="max-width: 200px;">
                <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $inst->url), ENT_QUOTES, 'UTF-8'); ?>
              </a>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td>
            <?php
              $typeColors = ['production' => 'primary', 'staging' => 'warning', 'dev' => 'info', 'demo' => 'secondary', 'offline' => 'dark'];
              $tColor = $typeColors[$inst->instance_type ?? ''] ?? 'secondary';
            ?>
            <span class="badge bg-<?php echo $tColor; ?>"><?php echo htmlspecialchars(ucfirst($inst->instance_type ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td>
            <?php echo htmlspecialchars(($inst->software ?? '') . ($inst->software_version ? ' v' . $inst->software_version : ''), ENT_QUOTES, 'UTF-8'); ?>
          </td>
          <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $inst->hosting ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="text-center">
            <?php
              $status = $inst->status ?? 'unknown';
              $statusColors = ['online' => 'success', 'offline' => 'danger', 'maintenance' => 'warning'];
              $sColor = $statusColors[$status] ?? 'secondary';
            ?>
            <span class="badge bg-<?php echo $sColor; ?>"><?php echo htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td class="text-center">
            <?php if (!empty($inst->sync_enabled)): ?>
              <span class="badge bg-success"><i class="fas fa-sync-alt"></i></span>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td class="text-end text-nowrap">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstanceEdit', 'id' => $inst->id]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('Edit'); ?>">
              <i class="fas fa-edit"></i>
            </a>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstanceDelink', 'id' => $inst->id]); ?>" class="btn btn-sm btn-outline-warning" title="<?php echo __('De-link from institution'); ?>" onclick="return confirm('<?php echo __('De-link this instance from your institution? It will become an orphan and can be re-linked later.'); ?>');">
              <i class="fas fa-unlink"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-server fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No instances registered'); ?></h5>
  <p class="text-muted"><?php echo __('Register your first AtoM instance to track deployments.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstanceAdd']); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i> <?php echo __('Add Instance'); ?>
  </a>
</div>
<?php endif; ?>

<?php if (!empty($orphanedInstances) && count($orphanedInstances) > 0): ?>
<h2 class="h5 mt-4 mb-3"><i class="fas fa-unlink me-2 text-warning"></i><?php echo __('Orphaned Instances (No Institution)'); ?></h2>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('URL'); ?></th>
          <th><?php echo __('Type'); ?></th>
          <th><?php echo __('Software'); ?></th>
          <th class="text-center"><?php echo __('Status'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orphanedInstances as $oi): ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($oi->name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></td>
          <td>
            <?php if (!empty($oi->url)): ?>
              <a href="<?php echo htmlspecialchars($oi->url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="text-truncate d-inline-block" style="max-width: 200px;">
                <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $oi->url), ENT_QUOTES, 'UTF-8'); ?>
              </a>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td>
            <?php
              $tColor = $typeColors[$oi->instance_type ?? ''] ?? 'secondary';
            ?>
            <span class="badge bg-<?php echo $tColor; ?>"><?php echo htmlspecialchars(ucfirst($oi->instance_type ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td><?php echo htmlspecialchars(($oi->software ?? '') . ($oi->software_version ? ' v' . $oi->software_version : ''), ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="text-center">
            <?php
              $oStatus = $oi->status ?? 'unknown';
              $oColor = $statusColors[$oStatus] ?? 'secondary';
            ?>
            <span class="badge bg-<?php echo $oColor; ?>"><?php echo htmlspecialchars(ucfirst($oStatus), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td class="text-end">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstanceRelink', 'id' => $oi->id]); ?>" class="btn btn-sm btn-outline-success" title="<?php echo __('Link to institution'); ?>">
              <i class="fas fa-link me-1"></i><?php echo __('Re-link'); ?>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php end_slot(); ?>
