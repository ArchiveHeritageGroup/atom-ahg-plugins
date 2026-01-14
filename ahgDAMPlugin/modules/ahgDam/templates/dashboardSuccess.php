<?php decorate_with('layout_2col'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-images fa-2x text-danger me-3"></i>
    <div>
      <h1 class="mb-0"><?php echo __('Digital Asset Management'); ?></h1>
      <span class="small text-muted"><?php echo __('Photo archive and DAM'); ?></span>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
  <div class="card mb-3" style="background-color: #dc3545;">
    <div class="card-body py-2 text-white text-center">
      <i class="fas fa-cog"></i> <?php echo __('DAM Actions'); ?>
    </div>
  </div>

  <div class="list-group mb-3">
    <a href="<?php echo url_for(['module' => 'dam', 'action' => 'create']); ?>" class="list-group-item list-group-item-action">
      <i class="fas fa-plus text-success me-2"></i><?php echo __('Create new asset'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'dam', 'action' => 'bulkCreate']); ?>" class="list-group-item list-group-item-action">
      <i class="fas fa-upload text-primary me-2"></i><?php echo __('Bulk upload'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'browse', 'type' => 'dam']); ?>" class="list-group-item list-group-item-action">
      <i class="fas fa-search text-info me-2"></i><?php echo __('Browse all assets'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'browse', 'type' => 'dam', 'hasDigital' => 1]); ?>" class="list-group-item list-group-item-action">
      <i class="fas fa-image text-warning me-2"></i><?php echo __('With digital objects'); ?>
    <a href="<?php echo url_for(['module' => 'damReports', 'action' => 'index']); ?>" class="list-group-item list-group-item-action">
      <i class="fas fa-chart-bar text-info me-2"></i><?php echo __("DAM Reports"); ?>
    </a>
    </a>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <!-- Statistics Cards -->
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card text-white bg-danger">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h4 class="mb-0"><?php echo $totalAssets; ?></h4>
              <small><?php echo __('Total DAM Assets'); ?></small>
            </div>
            <i class="fas fa-images fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-white bg-success">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h4 class="mb-0"><?php echo $withDigitalObjects; ?></h4>
              <small><?php echo __('With Digital Files'); ?></small>
            </div>
            <i class="fas fa-file-image fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-white bg-info">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h4 class="mb-0"><?php echo $totalAssets > 0 ? round(($withDigitalObjects / $totalAssets) * 100) : 0; ?>%</h4>
              <small><?php echo __('Digitized'); ?></small>
            </div>
            <i class="fas fa-chart-pie fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="card mb-4">
    <div class="card-header bg-light">
      <i class="fas fa-bolt"></i> <?php echo __('Quick Actions'); ?>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4 mb-2">
          <a href="<?php echo url_for(['module' => 'dam', 'action' => 'create']); ?>" class="btn btn-success btn-lg w-100">
            <i class="fas fa-plus me-2"></i><?php echo __('New Asset'); ?>
          </a>
        </div>
        <div class="col-md-4 mb-2">
          <a href="<?php echo url_for(['module' => 'dam', 'action' => 'bulkCreate']); ?>" class="btn btn-primary btn-lg w-100">
            <i class="fas fa-upload me-2"></i><?php echo __('Bulk Upload'); ?>
          </a>
        </div>
        <div class="col-md-4 mb-2">
          <a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'browse', 'type' => 'dam']); ?>" class="btn btn-info btn-lg w-100">
        </div>
        <div class="col-md-3">
      </div>
    </div>
  </div>

  <!-- Media Type Breakdown -->
  <?php if (!empty($mediaTypes)): ?>
  <div class="card mb-4">
    <div class="card-header bg-light">
      <i class="fas fa-chart-bar"></i> <?php echo __('Media Types'); ?>
    </div>
    <div class="card-body">
      <div class="row">
        <?php foreach ($mediaTypes as $mt): ?>
          <?php 
          $icon = match($mt->media_type) {
            'image' => 'fa-image',
            'video' => 'fa-video',
            'audio' => 'fa-music',
            'application' => 'fa-file-alt',
            default => 'fa-file'
          };
          $color = match($mt->media_type) {
            'image' => 'success',
            'video' => 'danger',
            'audio' => 'warning',
            'application' => 'info',
            default => 'secondary'
          };
          ?>
          <div class="col-md-3 col-6 mb-3 text-center">
            <div class="p-3 border rounded">
              <i class="fas <?php echo $icon; ?> fa-2x text-<?php echo $color; ?> mb-2"></i>
              <h5 class="mb-0"><?php echo $mt->count; ?></h5>
              <small class="text-muted"><?php echo ucfirst($mt->media_type ?: 'Unknown'); ?></small>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Recent Assets -->
  <div class="card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <span><i class="fas fa-clock"></i> <?php echo __('Recent Assets'); ?></span>
      <a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'browse', 'type' => 'dam', 'sort' => 'date', 'dir' => 'desc']); ?>" class="btn btn-sm btn-outline-secondary"><?php echo __('View all'); ?></a>
    </div>
    <div class="card-body p-0">
      <?php if (empty($recentAssets)): ?>
        <div class="text-center text-muted py-5">
          <i class="fas fa-inbox fa-3x mb-3"></i>
          <p><?php echo __('No DAM assets yet'); ?></p>
          <a href="<?php echo url_for(['module' => 'dam', 'action' => 'create']); ?>" class="btn btn-success">
            <i class="fas fa-plus"></i> <?php echo __('Create your first asset'); ?>
          </a>
        </div>
      <?php else: ?>
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Title'); ?></th>
              <th style="width:150px"><?php echo __('Identifier'); ?></th>
              <th style="width:100px"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentAssets as $asset): ?>
              <tr>
                <td>
                  <a href="<?php echo url_for('@slug?slug=' . $asset->slug); ?>">
                    <?php echo esc_entities($asset->title ?: '[Untitled]'); ?>
                  </a>
                </td>
                <td><small class="text-muted"><?php echo esc_entities($asset->identifier ?: '-'); ?></small></td>
                <td class="text-end">
                  <a href="<?php echo url_for('@slug?slug=' . $asset->slug); ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-eye"></i></a>
                  <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'edit', 'slug' => $asset->slug]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
<?php end_slot(); ?>
