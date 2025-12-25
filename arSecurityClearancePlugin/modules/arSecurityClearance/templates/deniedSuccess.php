<?php
/**
 * Access Denied Template.
 */
?>

<div class="container">
  <div class="row justify-content-center mt-5">
    <div class="col-md-6">
      <div class="card border-danger">
        <div class="card-header bg-danger text-white">
          <h4 class="mb-0"><i class="fas fa-ban"></i> <?php echo __('Access Denied') ?></h4>
        </div>
        <div class="card-body text-center">
          <i class="fas fa-lock fa-5x text-danger mb-4"></i>
          
          <h5><?php echo __('You do not have permission to access this resource.') ?></h5>
          
          <?php if ($classification): ?>
          <p class="mt-3">
            <strong><?php echo __('Required Classification:') ?></strong>
            <span class="badge" style="background-color: <?php echo $classification->color ?>">
              <?php echo esc_entities($classification->name) ?>
            </span>
          </p>
          <?php endif ?>

          <?php if (!empty($accessResult['reason'])): ?>
          <p class="alert alert-warning mt-3">
            <?php echo esc_entities($accessResult['reason']) ?>
          </p>
          <?php endif ?>

          <hr>

          <div class="d-grid gap-2">
            <?php if (!empty($accessResult['requires_2fa'])): ?>
            <a href="/security/2fa?return=<?php echo urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-warning">
              <i class="fas fa-shield-alt"></i> <?php echo __('Verify 2FA') ?>
            </a>
            <?php endif ?>
            
            <?php if (!empty($accessResult['requires_request'])): ?>
            <a href="/security/request/<?php echo $resource->id ?>" class="btn btn-primary">
              <i class="fas fa-key"></i> <?php echo __('Request Access') ?>
            </a>
            <?php endif ?>
            
            <a href="javascript:history.back()" class="btn btn-secondary">
              <i class="fas fa-arrow-left"></i> <?php echo __('Go Back') ?>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
