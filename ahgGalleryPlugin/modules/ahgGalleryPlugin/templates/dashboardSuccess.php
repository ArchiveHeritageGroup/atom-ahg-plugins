<?php use_helper('Text'); ?>
<?php slot('title'); ?><?php echo __('Gallery Dashboard'); ?><?php end_slot(); ?>

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col-12">
      <h1><i class="fas fa-palette me-2"></i><?php echo __('Gallery Management'); ?></h1>
      <p class="lead text-muted"><?php echo __('Manage artwork and gallery items using CCO cataloguing standards'); ?></p>
    </div>
  </div>

  <!-- Statistics Row -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card bg-primary text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-title mb-0"><?php echo __('Total Items'); ?></h6>
              <h2 class="mb-0"><?php echo $totalItems; ?></h2>
            </div>
            <i class="fas fa-palette fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-title mb-0"><?php echo __('With Media'); ?></h6>
              <h2 class="mb-0"><?php echo $itemsWithMedia; ?></h2>
            </div>
            <i class="fas fa-image fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-title mb-0"><?php echo __('Without Media'); ?></h6>
              <h2 class="mb-0"><?php echo $totalItems - $itemsWithMedia; ?></h2>
            </div>
            <i class="fas fa-image fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-warning text-dark">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-title mb-0"><?php echo __('Media Coverage'); ?></h6>
              <h2 class="mb-0"><?php echo $totalItems > 0 ? round(($itemsWithMedia / $totalItems) * 100) : 0; ?>%</h2>
            </div>
            <i class="fas fa-chart-pie fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Actions and Recent Items -->
  <div class="row">
    <div class="col-md-4">
      <div class="card mb-4">
        <div class="card-header bg-dark text-white">
          <h5 class="mb-0"><i class="fas fa-bolt me-2"></i><?php echo __('Quick Actions'); ?></h5>
        </div>
        <div class="card-body">
          <a href="<?php echo url_for(['module' => 'ahgGalleryPlugin', 'action' => 'add']); ?>" class="btn btn-primary w-100 mb-2">
            <i class="fas fa-plus me-2"></i><?php echo __('Add new gallery item'); ?>
          </a>
          <a href="<?php echo url_for(['module' => 'ahgGalleryPlugin', 'action' => 'browse']); ?>" class="btn btn-outline-primary w-100 mb-2">
            <i class="fas fa-list me-2"></i><?php echo __('Browse all items'); ?>
          </a>
          <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse', 'displayStandard' => \AtomFramework\Helpers\DisplayStandardHelper::getTermIdByCode('gallery')]); ?>" class="btn btn-outline-secondary w-100">
          <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'index']); ?>" class="btn btn-outline-success w-100 mt-2">
            <i class="fas fa-chart-bar me-2"></i><?php echo __("Gallery Reports"); ?>
          </a>
            <i class="fas fa-search me-2"></i><?php echo __('Advanced search'); ?>
          </a>
        </div>
      </div>

      <div class="card">
        <div class="card-header bg-dark text-white">
          <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('About CCO'); ?></h5>
        </div>
        <div class="card-body">
          <p class="small"><?php echo __('Cataloguing Cultural Objects (CCO) is a standard for describing cultural works and their images.'); ?></p>
          <p class="small mb-0"><?php echo __('Fields include: work type, materials, techniques, dimensions, subjects, and provenance.'); ?></p>
        </div>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card">
        <div class="card-header bg-dark text-white">
          <h5 class="mb-0"><i class="fas fa-clock me-2"></i><?php echo __('Recent Gallery Items'); ?></h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th><?php echo __('Identifier'); ?></th>
                  <th><?php echo __('Title'); ?></th>
                  <th class="text-center"><?php echo __('Media'); ?></th>
                  <th><?php echo __('Actions'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentItems as $item): ?>
                <tr>
                  <td><code><?php echo esc_entities($item->identifier); ?></code></td>
                  <td>
                    <a href="<?php echo url_for(['module' => 'ahgGalleryPlugin', 'action' => 'index', 'slug' => $item->slug]); ?>">
                      <?php echo esc_entities($item->title ?: '[Untitled]'); ?>
                    </a>
                  </td>
                  <td class="text-center">
                    <?php if ($item->digital_object_id): ?>
                      <i class="fas fa-check-circle text-success"></i>
                    <?php else: ?>
                      <i class="fas fa-times-circle text-muted"></i>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="<?php echo url_for(['module' => 'ahgGalleryPlugin', 'action' => 'edit', 'slug' => $item->slug]); ?>" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-edit"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentItems)): ?>
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">
                    <?php echo __('No gallery items yet. Add your first item to get started.'); ?>
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
