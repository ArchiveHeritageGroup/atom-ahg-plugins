<?php if ($sf_user->isAuthenticated()): ?>
<li class="nav-item">
  <a class="nav-link" href="<?php echo url_for(['module' => 'donor', 'action' => 'dashboard']) ?>">
    <i class="fas fa-hand-holding-heart me-1"></i>
    <?php echo __('Donor Dashboard') ?>
  </a>
</li>
<?php endif ?>
