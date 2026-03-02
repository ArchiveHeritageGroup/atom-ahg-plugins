<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Software Used'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Institution'), 'url' => url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard'])],
  ['label' => __('Software Used')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Software Used'); ?></h1>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard']); ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
  </a>
</div>

<!-- Add software form -->
<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="fas fa-plus-circle me-2 text-success"></i><?php echo __('Add Software'); ?></div>
  <div class="card-body">
    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionSoftware']); ?>">
      <input type="hidden" name="form_action" value="add">
      <div class="row g-3 align-items-end">
        <div class="col-md-5">
          <label for="sw-select" class="form-label"><?php echo __('Software'); ?></label>
          <select class="form-select" id="sw-select" name="software_id" required>
            <option value=""><?php echo __('-- Select software --'); ?></option>
            <?php if (!empty($allSoftware)):
              foreach ($allSoftware as $s): ?>
                <option value="<?php echo (int) $s->id; ?>"><?php echo htmlspecialchars($s->name, ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($s->latest_version) ? ' (v' . htmlspecialchars($s->latest_version, ENT_QUOTES, 'UTF-8') . ')' : ''; ?></option>
            <?php endforeach; endif; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="sw-version" class="form-label"><?php echo __('Version in Use'); ?></label>
          <input type="text" class="form-control" id="sw-version" name="version_in_use" placeholder="<?php echo __('e.g., 2.8.2'); ?>">
        </div>
        <div class="col-md-3">
          <label for="sw-notes" class="form-label"><?php echo __('Notes'); ?></label>
          <input type="text" class="form-control" id="sw-notes" name="notes" placeholder="<?php echo __('Optional'); ?>">
        </div>
        <div class="col-md-1">
          <button type="submit" class="btn btn-success w-100" title="<?php echo __('Add'); ?>">
            <i class="fas fa-plus"></i>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Current software list -->
<?php if (!empty($software) && count($software) > 0): ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Software'); ?></th>
          <th><?php echo __('Version in Use'); ?></th>
          <th><?php echo __('Deployment Date'); ?></th>
          <th><?php echo __('Notes'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($software as $sw): ?>
        <tr>
          <td>
            <?php if (!empty($sw->slug)): ?>
              <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $sw->slug]); ?>">
                <strong><?php echo htmlspecialchars($sw->name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
              </a>
            <?php else: ?>
              <strong><?php echo htmlspecialchars($sw->name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($sw->version_in_use)): ?>
              <span class="badge bg-secondary"><?php echo htmlspecialchars($sw->version_in_use, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($sw->deployment_date)): ?>
              <?php echo date('M j, Y', strtotime($sw->deployment_date)); ?>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($sw->notes ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="text-end">
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionSoftware']); ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Remove this software?'); ?>');">
              <input type="hidden" name="form_action" value="remove">
              <input type="hidden" name="assignment_id" value="<?php echo (int) $sw->assignment_id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Remove'); ?>">
                <i class="fas fa-times"></i>
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
<div class="text-center py-4">
  <i class="fas fa-laptop-code fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No software linked yet'); ?></h5>
  <p class="text-muted"><?php echo __('Use the form above to add software used by your institution.'); ?></p>
</div>
<?php endif; ?>

<?php end_slot(); ?>
