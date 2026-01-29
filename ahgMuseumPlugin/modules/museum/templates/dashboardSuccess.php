<?php use_helper('Text'); ?>
<?php slot('title'); ?><?php echo __('Museum Dashboard'); ?><?php end_slot(); ?>

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col-12">
      <h1><i class="fas fa-landmark me-2"></i><?php echo __('Museum Management'); ?></h1>
      <p class="lead text-muted"><?php echo __('Manage museum objects using CCO/Spectrum cataloguing standards'); ?></p>
    </div>
  </div>

  <!-- Statistics Row -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card bg-primary text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-title mb-0"><?php echo __('Total Objects'); ?></h6>
              <h2 class="mb-0"><?php echo number_format($totalItems); ?></h2>
            </div>
            <i class="fas fa-cube fa-2x opacity-50"></i>
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
              <h2 class="mb-0"><?php echo number_format($itemsWithMedia); ?></h2>
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
              <h6 class="card-title mb-0"><?php echo __('Condition Checked'); ?></h6>
              <h2 class="mb-0"><?php echo number_format($itemsWithCondition); ?></h2>
            </div>
            <i class="fas fa-heartbeat fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-warning text-dark">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-title mb-0"><?php echo __('With Provenance'); ?></h6>
              <h2 class="mb-0"><?php echo number_format($itemsWithProvenance); ?></h2>
            </div>
            <i class="fas fa-history fa-2x opacity-50"></i>
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
          <a href="<?php echo url_for(['module' => 'museum', 'action' => 'add']); ?>" class="btn btn-primary w-100 mb-2">
            <i class="fas fa-plus me-2"></i><?php echo __('Add new museum object'); ?>
          </a>
          <a href="<?php echo url_for(['module' => 'display', 'action' => 'browse', 'type' => 'museum']); ?>" class="btn btn-outline-primary w-100 mb-2">
            <i class="fas fa-list me-2"></i><?php echo __('Browse all objects'); ?>
          </a>
          <a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'index']); ?>" class="btn btn-outline-success w-100 mb-2">
            <i class="fas fa-chart-bar me-2"></i><?php echo __('Museum Reports'); ?>
          </a>
          <a href="<?php echo url_for(['module' => 'dashboard', 'action' => 'index']); ?>" class="btn btn-outline-info w-100 mb-2">
            <i class="fas fa-chart-line me-2"></i><?php echo __('Data Quality Dashboard'); ?>
          </a>
          <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'dashboard']); ?>" class="btn btn-outline-secondary w-100">
            <i class="fas fa-theater-masks me-2"></i><?php echo __('Exhibitions'); ?>
          </a>
        </div>
      </div>

      <?php if (!empty($workTypeStats)): ?>
      <div class="card">
        <div class="card-header bg-dark text-white">
          <h5 class="mb-0"><i class="fas fa-shapes me-2"></i><?php echo __('Top Work Types'); ?></h5>
        </div>
        <ul class="list-group list-group-flush">
          <?php foreach ($workTypeStats as $stat): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <?php echo esc_entities($stat->work_type ?: 'Unknown'); ?>
            <span class="badge bg-primary rounded-pill"><?php echo $stat->count; ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-md-8">
      <div class="card">
        <div class="card-header bg-dark text-white">
          <h5 class="mb-0"><i class="fas fa-clock me-2"></i><?php echo __('Recent Museum Objects'); ?></h5>
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
                    <a href="<?php echo url_for(['module' => 'museum', 'action' => 'index', 'slug' => $item->slug]); ?>">
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
                    <a href="<?php echo url_for(['module' => 'museum', 'action' => 'edit', 'slug' => $item->slug]); ?>" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-edit"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentItems)): ?>
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">
                    <?php echo __('No museum objects yet. Add your first object to get started.'); ?>
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
