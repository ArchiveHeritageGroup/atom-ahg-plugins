<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Manage Contacts'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => $backUrl ? __('Dashboard') : '', 'url' => $backUrl ?? ''],
  ['label' => __('Contacts')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Manage Contacts'); ?></h1>
  <div>
    <?php
      $addAction = 'institution' === $entityType ? 'myInstitutionContactAdd' : 'myVendorContactAdd';
    ?>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => $addAction]); ?>" class="btn btn-primary btn-sm">
      <i class="fas fa-plus me-1"></i> <?php echo __('Add Contact'); ?>
    </a>
    <?php if (!empty($backUrl)): ?>
      <a href="<?php echo $backUrl; ?>" class="btn btn-outline-secondary btn-sm ms-1">
        <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
      </a>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($contacts) && count($contacts) > 0): ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('Email'); ?></th>
          <th><?php echo __('Job Title'); ?></th>
          <th><?php echo __('Roles'); ?></th>
          <th class="text-center"><?php echo __('Primary'); ?></th>
          <th class="text-center"><?php echo __('Visibility'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($contacts as $c): ?>
        <tr>
          <td>
            <strong><?php echo htmlspecialchars(trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')), ENT_QUOTES, 'UTF-8'); ?></strong>
          </td>
          <td>
            <?php if (!empty($c->email)): ?>
              <a href="mailto:<?php echo htmlspecialchars($c->email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($c->email, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($c->job_title ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php if (!empty($c->roles)):
              $rawRoles = sfOutputEscaper::unescape($c->roles);
              $roles = is_string($rawRoles) ? json_decode($rawRoles, true) : (array) $rawRoles;
              if (is_array($roles)):
                foreach ($roles as $role): ?>
                  <span class="badge bg-info text-dark me-1"><?php echo htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endforeach;
              endif;
            endif; ?>
          </td>
          <td class="text-center">
            <?php if (!empty($c->is_primary)): ?>
              <span class="badge bg-success"><i class="fas fa-star"></i></span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <?php if (!empty($c->is_public)): ?>
              <span class="badge bg-primary"><?php echo __('Public'); ?></span>
            <?php else: ?>
              <span class="badge bg-secondary"><?php echo __('Private'); ?></span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <?php
              $editAction = 'institution' === $entityType ? 'myInstitutionContactEdit' : 'myVendorContactEdit';
            ?>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => $editAction, 'id' => $c->id]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('Edit'); ?>">
              <i class="fas fa-edit"></i>
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
  <i class="fas fa-address-book fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No contacts yet'); ?></h5>
  <p class="text-muted"><?php echo __('Add your first contact person to get started.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => $addAction]); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i> <?php echo __('Add Contact'); ?>
  </a>
</div>
<?php endif; ?>

<?php end_slot(); ?>
