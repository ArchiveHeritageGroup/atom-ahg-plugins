<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
<h1><i class="fas fa-book me-2"></i><?php echo __('ISBN Lookup Providers'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
<div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-server me-1"></i><?php echo __('Configured Providers'); ?></span>
    <a href="<?php echo url_for('ahgLibraryPlugin/isbnProviderEdit') ?>" class="btn btn-sm btn-primary">
      <i class="fas fa-plus me-1"></i><?php echo __('Add Provider'); ?>
    </a>
  </div>
  <div class="card-body p-0">
    <table class="table table-striped table-hover mb-0">
      <thead>
        <tr>
          <th style="width:50px"><?php echo __('Priority'); ?></th>
          <th><?php echo __('Provider'); ?></th>
          <th><?php echo __('API Endpoint'); ?></th>
          <th style="width:100px"><?php echo __('Rate Limit'); ?></th>
          <th style="width:100px"><?php echo __('Status'); ?></th>
          <th style="width:120px"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($providers as $provider): ?>
        <tr>
          <td class="text-center">
            <span class="badge bg-secondary"><?php echo $provider->priority; ?></span>
          </td>
          <td>
            <strong><?php echo esc_specialchars($provider->name); ?></strong>
            <br><small class="text-muted"><?php echo esc_specialchars($provider->slug); ?></small>
          </td>
          <td>
            <small class="text-muted text-break"><?php echo esc_specialchars($provider->api_endpoint); ?></small>
          </td>
          <td class="text-center">
            <?php echo $provider->rate_limit_per_minute; ?>/min
          </td>
          <td class="text-center">
            <?php if ($provider->enabled): ?>
              <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Enabled'); ?></span>
            <?php else: ?>
              <span class="badge bg-secondary"><i class="fas fa-times me-1"></i><?php echo __('Disabled'); ?></span>
            <?php endif; ?>
          </td>
          <td>
            <div class="btn-group btn-group-sm">
              <a href="<?php echo url_for(['module' => 'ahgLibraryPlugin', 'action' => 'isbnProviderEdit', 'id' => $provider->id]) ?>" class="btn btn-outline-primary" title="<?php echo __('Edit'); ?>">
                <i class="fas fa-edit"></i>
              </a>
              <a href="<?php echo url_for(['module' => 'ahgLibraryPlugin', 'action' => 'isbnProviderToggle', 'id' => $provider->id]) ?>" class="btn btn-outline-<?php echo $provider->enabled ? 'warning' : 'success'; ?>" title="<?php echo $provider->enabled ? __('Disable') : __('Enable'); ?>">
                <i class="fas fa-<?php echo $provider->enabled ? 'pause' : 'play'; ?>"></i>
              </a>
              <?php if (!in_array($provider->slug, ['openlibrary', 'googlebooks', 'worldcat'])): ?>
              <a href="<?php echo url_for(['module' => 'ahgLibraryPlugin', 'action' => 'isbnProviderDelete', 'id' => $provider->id]) ?>" class="btn btn-outline-danger" title="<?php echo __('Delete'); ?>" onclick="return confirm('<?php echo __('Are you sure?'); ?>')">
                <i class="fas fa-trash"></i>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (count($providers) === 0): ?>
        <tr>
          <td colspan="6" class="text-center text-muted py-4">
            <i class="fas fa-info-circle me-1"></i><?php echo __('No providers configured'); ?>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <i class="fas fa-info-circle me-1"></i><?php echo __('About ISBN Providers'); ?>
  </div>
  <div class="card-body">
    <p class="mb-2"><?php echo __('ISBN lookup uses these providers in priority order to fetch book metadata.'); ?></p>
    <ul class="mb-0">
      <li><strong>Open Library</strong> - <?php echo __('Free, no API key required, good coverage'); ?></li>
      <li><strong>Google Books</strong> - <?php echo __('Free tier available, excellent coverage'); ?></li>
      <li><strong>WorldCat</strong> - <?php echo __('Requires API key, comprehensive library data'); ?></li>
    </ul>
  </div>
</div>

<div class="mt-3">
  <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'index']) ?>" class="btn btn-secondary">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Settings'); ?>
  </a>
</div>

<?php end_slot() ?>
