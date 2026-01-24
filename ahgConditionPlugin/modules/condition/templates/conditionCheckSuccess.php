<?php 
use_helper('Text');
$slug = $sf_request->getParameter('slug');
$baseUrl = sfContext::getInstance()->getRequest()->getRelativeUrlRoot() . '/index.php';
?>

<div class="row">
  <div class="col-md-12">
    <h1 class="h3 mb-4">
      <i class="fas fa-clipboard-check me-2"></i>
      <?php echo __('Condition Reports') ?>
    </h1>
    
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
          <a href="<?php echo $baseUrl . '/' . $slug ?>"><?php echo esc_entities($resourceTitle) ?></a>
        </li>
        <li class="breadcrumb-item active"><?php echo __('Condition') ?></li>
      </ol>
    </nav>
    
    <div class="mb-4">
      <a href="<?php echo $baseUrl . '/' . $slug ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i><?php echo __('Back') ?>
      </a>
      <?php if ($sf_user->isAuthenticated()): ?>
      <a href="<?php echo $baseUrl ?>/ahgCondition/photos?id=new&object_id=<?php echo $resource->id ?>" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i><?php echo __('New Condition Check') ?>
      </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($latestCondition): ?>
<div class="row mb-4">
  <div class="col-md-12">
    <div class="card shadow-sm border-primary">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
          <i class="fas fa-star me-2"></i>
          <?php echo __('Latest Condition') ?>
        </h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3">
            <strong><?php echo __('Date') ?>:</strong><br>
            <?php echo esc_entities($latestCondition->check_date ?? 'N/A') ?>
          </div>
          <div class="col-md-3">
            <strong><?php echo __('Status') ?>:</strong><br>
            <?php 
            $status = $latestCondition->overall_condition ?? 'unknown';
            $badgeClass = match($status) {
                'good', 'excellent' => 'bg-success',
                'fair' => 'bg-warning text-dark',
                'poor', 'critical' => 'bg-danger',
                default => 'bg-secondary'
            };
            ?>
            <span class="badge <?php echo $badgeClass ?>"><?php echo esc_entities(ucfirst($status)) ?></span>
          </div>
          <div class="col-md-3">
            <strong><?php echo __('Assessor') ?>:</strong><br>
            <?php echo esc_entities($latestCondition->assessor ?? 'N/A') ?>
          </div>
          <div class="col-md-3">
            <a href="<?php echo $baseUrl ?>/ahgCondition/photos?id=<?php echo $latestCondition->id ?>" class="btn btn-outline-primary btn-sm">
              <i class="fas fa-images me-1"></i><?php echo __('View Photos') ?>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row">
  <div class="col-md-12">
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <i class="fas fa-history me-2"></i>
          <?php echo __('Condition History') ?>
        </h5>
        <span class="badge bg-secondary"><?php echo count($conditions) ?></span>
      </div>
      <div class="card-body">
        <?php if (!empty($conditions)): ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th><?php echo __('Date') ?></th>
                  <th><?php echo __('Status') ?></th>
                  <th><?php echo __('Type') ?></th>
                  <th><?php echo __('Assessor') ?></th>
                  <th><?php echo __('Notes') ?></th>
                  <th><?php echo __('Actions') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($conditions as $condition): ?>
                  <tr>
                    <td><?php echo esc_entities($condition->check_date ?? '') ?></td>
                    <td>
                      <?php 
                      $status = $condition->overall_condition ?? 'unknown';
                      $badgeClass = match($status) {
                          'good', 'excellent' => 'bg-success',
                          'fair' => 'bg-warning text-dark',
                          'poor', 'critical' => 'bg-danger',
                          default => 'bg-secondary'
                      };
                      ?>
                      <span class="badge <?php echo $badgeClass ?>"><?php echo esc_entities(ucfirst($status)) ?></span>
                    </td>
                    <td><?php echo esc_entities($condition->check_type ?? '') ?></td>
                    <td><?php echo esc_entities($condition->assessor ?? '') ?></td>
                    <td><?php echo esc_entities(truncate_text($condition->notes ?? '', 50)) ?></td>
                    <td>
                      <a href="<?php echo $baseUrl ?>/ahgCondition/photos?id=<?php echo $condition->id ?>" 
                         class="btn btn-sm btn-outline-primary" title="<?php echo __('View Photos') ?>">
                        <i class="fas fa-images"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-4">
            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
            <p class="text-muted mb-0"><?php echo __('No condition reports found for this object.') ?></p>
            <?php if ($sf_user->isAuthenticated()): ?>
            <a href="<?php echo $baseUrl ?>/ahgCondition/photos?id=new&object_id=<?php echo $resource->id ?>" class="btn btn-primary mt-3">
              <i class="fas fa-plus me-2"></i><?php echo __('Create First Condition Check') ?>
            </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
