<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Releases'); ?> - <?php echo htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Vendor Dashboard'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorDashboard'])],
  ['label' => __('Software'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorSoftware'])],
  ['label' => htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8')],
  ['label' => __('Releases')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><?php echo __('Releases'); ?></h1>
    <p class="text-muted mb-0"><?php echo htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
  <div>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareReleaseAdd', 'id' => $software->id]); ?>" class="btn btn-primary btn-sm">
      <i class="fas fa-plus me-1"></i> <?php echo __('Add Release'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareUpload', 'id' => $software->id]); ?>" class="btn btn-outline-success btn-sm ms-1">
      <i class="fas fa-upload me-1"></i> <?php echo __('Upload Package'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftware']); ?>" class="btn btn-outline-secondary btn-sm ms-1">
      <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
    </a>
  </div>
</div>

<?php if (!empty($releases) && count($releases) > 0): ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Version'); ?></th>
          <th><?php echo __('Type'); ?></th>
          <th><?php echo __('Release Date'); ?></th>
          <th><?php echo __('Git Tag'); ?></th>
          <th><?php echo __('Download'); ?></th>
          <th class="text-center"><?php echo __('Downloads'); ?></th>
          <th class="text-center"><?php echo __('Status'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($releases as $rel): ?>
        <tr>
          <td>
            <strong><?php echo htmlspecialchars($rel->version ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if (!empty($rel->is_latest)): ?>
              <span class="badge bg-success ms-1"><?php echo __('Latest'); ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php
              $typeColors = ['major' => 'danger', 'minor' => 'warning', 'patch' => 'info', 'rc' => 'secondary', 'beta' => 'dark', 'alpha' => 'light'];
              $rType = $rel->release_type ?? 'patch';
              $tColor = $typeColors[$rType] ?? 'secondary';
            ?>
            <span class="badge bg-<?php echo $tColor; ?><?php echo 'light' === $tColor ? ' text-dark border' : ''; ?>"><?php echo htmlspecialchars(ucfirst($rType), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td>
            <?php echo !empty($rel->released_at) ? date('M j, Y', strtotime($rel->released_at)) : '<span class="text-muted">-</span>'; ?>
          </td>
          <td>
            <?php if (!empty($rel->git_tag)): ?>
              <code><?php echo htmlspecialchars($rel->git_tag, ENT_QUOTES, 'UTF-8'); ?></code>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($rel->download_url)): ?>
              <a href="<?php echo htmlspecialchars($rel->download_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-download me-1"></i> <?php echo __('Download'); ?>
              </a>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td class="text-center"><?php echo number_format($rel->download_count ?? 0); ?></td>
          <td class="text-center">
            <?php if (!empty($rel->is_stable)): ?>
              <span class="badge bg-success"><?php echo __('Stable'); ?></span>
            <?php else: ?>
              <span class="badge bg-warning text-dark"><?php echo __('Pre-release'); ?></span>
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
  <i class="fas fa-tags fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No releases yet'); ?></h5>
  <p class="text-muted"><?php echo __('Create your first release to distribute your software.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareReleaseAdd', 'id' => $software->id]); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i> <?php echo __('Add Release'); ?>
  </a>
</div>
<?php endif; ?>

<?php end_slot(); ?>
