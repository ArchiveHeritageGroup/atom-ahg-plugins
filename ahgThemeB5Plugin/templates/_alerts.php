<?php if ($sf_user->hasFlash('notice') && $sf_user->getFlash('notice')): ?>
  <div class="alert alert-info alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('success') && $sf_user->getFlash('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('success'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error') && $sf_user->getFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
