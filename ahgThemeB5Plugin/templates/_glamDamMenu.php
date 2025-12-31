<?php
/**
 * GLAM/DAM Menu - Galleries, Libraries, Archives, Museums & Digital Asset Management
 * Provides access to sector-specific tools and features
 */
$isAuthenticated = $sf_user->isAuthenticated();
$isAdmin = $sf_user->isAdministrator();
?>
<?php if ($isAuthenticated): ?>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
#glam-dam-menu + .dropdown-menu { font-size: 0.85rem; }
#glam-dam-menu + .dropdown-menu .dropdown-item { padding: 0.25rem 1rem; }
#glam-dam-menu + .dropdown-menu .dropdown-header { padding: 0.25rem 1rem; font-size: 0.75rem; }
#glam-dam-menu + .dropdown-menu .dropdown-divider { margin: 0.25rem 0; }
</style>
<li class="nav-item dropdown d-flex flex-column">
  <a class="nav-link dropdown-toggle d-flex align-items-center p-0" href="#" id="glam-dam-menu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-2x fa-fw fa-landmark px-0 px-lg-2 py-2" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-custom-class="d-none d-lg-block" title="<?php echo __('GLAM/DAM'); ?>" aria-hidden="true"></i>
    <span class="d-lg-none mx-1" aria-hidden="true"><?php echo __('GLAM/DAM'); ?></span>
    <span class="visually-hidden"><?php echo __('GLAM/DAM'); ?></span>
  </a>
  <ul class="dropdown-menu dropdown-menu-end mb-2" aria-labelledby="glam-dam-menu">
    
    <!-- Browse by Sector -->
    <li><h6 class="dropdown-header"><?php echo __('Browse by Sector'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'display', 'action' => 'browse']); ?>"><i class="fas fa-th fa-fw me-2"></i><?php echo __('All Collections'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'display', 'action' => 'browse', 'type' => 'archive']); ?>"><i class="fas fa-archive fa-fw me-2"></i><?php echo __('Archives'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'display', 'action' => 'browse', 'type' => 'museum']); ?>"><i class="fas fa-university fa-fw me-2"></i><?php echo __('Museum Objects'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'display', 'action' => 'browse', 'type' => 'library']); ?>"><i class="fas fa-book fa-fw me-2"></i><?php echo __('Library Materials'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'display', 'action' => 'browse', 'type' => 'gallery']); ?>"><i class="fas fa-images fa-fw me-2"></i><?php echo __('Gallery Works'); ?></a></li>
    
    <li><hr class="dropdown-divider"></li>
    
    <!-- Digital Assets -->
    <li><h6 class="dropdown-header"><?php echo __('Digital Assets'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'digitalobject', 'action' => 'browse']); ?>"><i class="fas fa-photo-video fa-fw me-2"></i><?php echo __('Browse Digital Objects'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'display', 'action' => 'browse', 'hasDigital' => '1']); ?>"><i class="fas fa-file-image fa-fw me-2"></i><?php echo __('Items with Media'); ?></a></li>
    
    <?php if ($isAdmin): ?>
    <li><hr class="dropdown-divider"></li>
    
    <!-- Reports -->
    <li><h6 class="dropdown-header"><?php echo __('Reports'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'reports', 'action' => 'index']); ?>"><i class="fas fa-chart-bar fa-fw me-2"></i><?php echo __('Reports Dashboard'); ?></a></li>
    <?php endif; ?>
    
    <li><hr class="dropdown-divider"></li>
    
    <!-- More -->
    <li><h6 class="dropdown-header"><?php echo __('More'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'conditionPhotos']); ?>"><i class="fas fa-clipboard-check fa-fw me-2"></i><?php echo __('Condition Assessment'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'index']); ?>"><i class="fas fa-tasks fa-fw me-2"></i><?php echo __('SPECTRUM Procedures'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'cco', 'action' => 'index']); ?>"><i class="fas fa-history fa-fw me-2"></i><?php echo __('Provenance (CCO)'); ?></a></li>
    
    <?php if ($isAdmin): ?>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'damTools']); ?>"><i class="fas fa-tools fa-fw me-2"></i><?php echo __('DAM Tools'); ?></a></li>
    <?php endif; ?>
    
  </ul>
</li>
<?php endif; ?>
