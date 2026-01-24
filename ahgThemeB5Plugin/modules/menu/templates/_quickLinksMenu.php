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
  </ul>
</li>
