<?php
/**
 * Quick Links Menu - Hardcoded (no database)
 * Mimics AtoM 2.10 quick links but without menu table dependency
 */
?>
<li class="nav-item dropdown d-flex flex-column">
  <a class="nav-link dropdown-toggle d-flex align-items-center p-0" href="#" id="quick-links-menu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-2x fa-fw fa-info-circle px-0 px-lg-2 py-2" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-custom-class="d-none d-lg-block" title="<?php echo __('Quick links'); ?>" aria-hidden="true"></i>
    <span class="d-lg-none mx-1" aria-hidden="true"><?php echo __('Quick links'); ?></span>
    <span class="visually-hidden"><?php echo __('Quick links'); ?></span>
  </a>
  <ul class="dropdown-menu dropdown-menu-end mb-2" aria-labelledby="quick-links-menu">
    <li><h6 class="dropdown-header"><?php echo __('Quick links'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'staticpage', 'action' => 'index', 'slug' => 'about']); ?>"><i class="fas fa-info-circle fa-fw me-2"></i><?php echo __('About'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'staticpage', 'action' => 'index', 'slug' => 'contact']); ?>"><i class="fas fa-envelope fa-fw me-2"></i><?php echo __('Contact'); ?></a></li>
    <?php if ($sf_user->isAuthenticated()): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Help'); ?></h6></li>
    <li><a class="dropdown-item" href="https://www.accesstomemory.org/docs/" target="_blank"><i class="fas fa-book fa-fw me-2"></i><?php echo __('AtoM Documentation'); ?></a></li>
    <li><a class="dropdown-item" href="https://wiki.accesstomemory.org/" target="_blank"><i class="fas fa-globe fa-fw me-2"></i><?php echo __('AtoM Wiki'); ?></a></li>
    <?php endif; ?>
    <?php if ($sf_user->isAdministrator()): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('System'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'settings', 'action' => 'list']); ?>"><i class="fas fa-cog fa-fw me-2"></i><?php echo __('Settings'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'staticpage', 'action' => 'list']); ?>"><i class="fas fa-file-alt fa-fw me-2"></i><?php echo __('Manage Static Pages'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'menu', 'action' => 'list']); ?>"><i class="fas fa-bars fa-fw me-2"></i><?php echo __('Manage Menus'); ?></a></li>
    <?php endif; ?>
  </ul>
</li>
