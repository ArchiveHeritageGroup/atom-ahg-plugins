<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $detail = $software['software']; ?>

<?php slot('title'); ?><?php echo htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'); ?> - <?php echo __('Releases'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Software'), 'url' => url_for(['module' => 'registry', 'action' => 'softwareBrowse'])],
  ['label' => htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'), 'url' => url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $detail->slug])],
  ['label' => __('Releases')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><?php echo htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'); ?> &mdash; <?php echo __('Releases'); ?></h1>
    <?php if (!empty($detail->latest_version)): ?>
    <p class="text-muted mb-0"><?php echo __('Latest version: %1%', ['%1%' => htmlspecialchars($detail->latest_version, ENT_QUOTES, 'UTF-8')]); ?></p>
    <?php endif; ?>
  </div>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $detail->slug]); ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Software'); ?>
  </a>
</div>

<?php if (!empty($releases)): ?>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th><?php echo __('Version'); ?></th>
        <th><?php echo __('Type'); ?></th>
        <th><?php echo __('Release Date'); ?></th>
        <th><?php echo __('Git Tag'); ?></th>
        <th><?php echo __('Downloads'); ?></th>
        <th><?php echo __('Notes'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($releases as $rel): ?>
      <tr>
        <td>
          <strong><?php echo htmlspecialchars($rel->version, ENT_QUOTES, 'UTF-8'); ?></strong>
          <?php if (!empty($rel->is_latest)): ?>
            <span class="badge bg-primary ms-1"><?php echo __('Latest'); ?></span>
          <?php endif; ?>
          <?php if (!empty($rel->is_stable)): ?>
            <span class="badge bg-success ms-1"><?php echo __('Stable'); ?></span>
          <?php else: ?>
            <span class="badge bg-warning text-dark ms-1"><?php echo __('Pre-release'); ?></span>
          <?php endif; ?>
        </td>
        <td>
          <?php
            $typeColors = ['major' => 'danger', 'minor' => 'warning', 'patch' => 'secondary', 'beta' => 'info', 'rc' => 'info', 'alpha' => 'dark'];
            $color = $typeColors[$rel->release_type ?? ''] ?? 'secondary';
          ?>
          <span class="badge bg-<?php echo $color; ?>"><?php echo htmlspecialchars($rel->release_type ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td><?php echo !empty($rel->released_at) ? date('M j, Y', strtotime($rel->released_at)) : '-'; ?></td>
        <td>
          <?php if (!empty($rel->git_tag)): ?>
            <?php if (!empty($detail->git_url) && !empty($rel->git_compare_url)): ?>
              <a href="<?php echo htmlspecialchars($rel->git_compare_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                <code><?php echo htmlspecialchars($rel->git_tag, ENT_QUOTES, 'UTF-8'); ?></code>
              </a>
            <?php else: ?>
              <code><?php echo htmlspecialchars($rel->git_tag, ENT_QUOTES, 'UTF-8'); ?></code>
            <?php endif; ?>
          <?php else: ?>
            -
          <?php endif; ?>
        </td>
        <td>
          <?php if (!empty($rel->file_path)): ?>
            <a href="<?php echo htmlspecialchars($rel->file_path, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('Download'); ?>">
              <i class="fas fa-download"></i>
            </a>
            <small class="text-muted ms-1"><?php echo number_format($rel->download_count ?? 0); ?></small>
          <?php else: ?>
            <small class="text-muted"><?php echo number_format($rel->download_count ?? 0); ?></small>
          <?php endif; ?>
        </td>
        <td>
          <?php if (!empty($rel->release_notes)): ?>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#notes-<?php echo (int) $rel->id; ?>">
              <i class="fas fa-file-alt"></i>
            </button>
          <?php else: ?>
            -
          <?php endif; ?>
        </td>
      </tr>
      <?php if (!empty($rel->release_notes)): ?>
      <tr class="collapse" id="notes-<?php echo (int) $rel->id; ?>">
        <td colspan="6" class="bg-light">
          <div class="p-2 small"><?php echo nl2br(htmlspecialchars($rel->release_notes, ENT_QUOTES, 'UTF-8')); ?></div>
        </td>
      </tr>
      <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-tag fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No releases yet'); ?></h5>
  <p class="text-muted"><?php echo __('No releases have been published for this software.'); ?></p>
</div>
<?php endif; ?>

<?php end_slot(); ?>
