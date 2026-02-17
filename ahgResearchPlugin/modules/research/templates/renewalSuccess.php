<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php slot('title') ?>
<h1><i class="fas fa-sync-alt me-2"></i><?php echo __('Request Registration Renewal'); ?></h1>
<?php end_slot() ?>
<?php slot('content') ?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <?php if ($sf_user->hasFlash('error')): ?>
      <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
    <?php endif; ?>
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><i class="fas fa-user-clock me-2"></i><?php echo __('Researcher Renewal'); ?></h4>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          <strong><?php echo __('Current Status:'); ?></strong>
          <span class="badge bg-<?php echo $researcher->status === 'expired' ? 'danger' : 'success'; ?>">
            <?php echo ucfirst($researcher->status); ?>
          </span>
          <?php if ($researcher->expires_at): ?>
            <br><small><?php echo __('Expires:'); ?> <?php echo date('M j, Y', strtotime($researcher->expires_at)); ?></small>
          <?php endif; ?>
        </div>
        <form method="post">
          <div class="mb-3">
            <label for="reason" class="form-label"><?php echo __('Reason for Renewal'); ?></label>
            <textarea class="form-control" id="reason" name="reason" rows="4" 
                      placeholder="<?php echo __('Please describe why you need to renew your researcher registration...'); ?>"></textarea>
          </div>
          <div class="d-flex justify-content-between">
            <a href="<?php echo url_for('research/profile'); ?>" class="btn btn-secondary">
              <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Profile'); ?>
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-paper-plane me-1"></i><?php echo __('Submit Renewal Request'); ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php end_slot() ?>
