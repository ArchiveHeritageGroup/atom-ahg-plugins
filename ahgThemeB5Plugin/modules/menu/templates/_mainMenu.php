<?php
// Check which sector plugins are enabled
if (!function_exists('checkPluginEnabled')) {
    function checkPluginEnabled($pluginName) {
        static $plugins = null;
        if ($plugins === null) {
            try {
                $conn = Propel::getConnection();
                $stmt = $conn->prepare('SELECT name FROM atom_plugin WHERE is_enabled = 1');
                $stmt->execute();
                $plugins = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
            } catch (Exception $e) {
                $plugins = [];
            }
        }
        return isset($plugins[$pluginName]);
    }
}

$hasLibrary = checkPluginEnabled('ahgLibraryPlugin');
$hasMuseum = checkPluginEnabled('ahgMuseumPlugin');
$hasGallery = checkPluginEnabled('ahgGalleryPlugin');
$hasDam = checkPluginEnabled('arDAMPlugin') || checkPluginEnabled('ahgDAMPlugin');
?>
<?php foreach ([$addMenu, $manageMenu, $importMenu, $adminMenu] as $menu) { ?>
  <?php if (
      $menu && ('add' == $menu->getName()
      || 'manage' == $menu->getName())
      || $sf_user->isAdministrator()
  ) { ?>
    <li class="nav-item dropdown d-flex flex-column">
      <a
      
        class="nav-link dropdown-toggle d-flex align-items-center p-0"
        href="#"
        id="<?php echo $menu->getName(); ?>-menu"
        role="button"
        data-bs-toggle="dropdown"
        aria-expanded="false">
        <i
          class="fas fa-2x fa-fw fa-<?php echo $icons[$menu->getName()]; ?> px-0 px-lg-2 py-2"
          data-bs-toggle="tooltip"
          data-bs-placement="bottom"
          data-bs-custom-class="d-none d-lg-block"
          title="<?php echo $menu->getLabel(['cultureFallback' => true]); ?>"
          aria-hidden="true">
        </i>
        <span class="d-lg-none mx-1" aria-hidden="true">
          <?php echo $menu->getLabel(['cultureFallback' => true]); ?>
        </span>
        <span class="visually-hidden">
          <?php echo $menu->getLabel(['cultureFallback' => true]); ?>
        </span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end mb-2" aria-labelledby="<?php echo $menu->getName(); ?>-menu">
        <li>
          <h6 class="dropdown-header">
            <?php echo $menu->getLabel(['cultureFallback' => true]); ?>
          </h6>
        </li>
        <?php foreach ($menu->getChildren() as $child) { ?>
          <?php if ($child->checkUserAccess()) { ?>
            <li <?php echo isset($child->name) ? 'id="node_'.$child->name.'"' : ''; ?>>
              <?php echo link_to(
                  $child->getLabel(['cultureFallback' => true]),
                  $child->getPath(['getUrl' => true, 'resolveAlias' => true]),
                  ['class' => 'dropdown-item']
              ); ?>
            </li>
          <?php } ?>
        <?php } ?>

        <?php // Inject sector-specific items for Add menu ?>
        <?php if ('add' == $menu->getName()): ?>
          <?php if ($hasLibrary || $hasMuseum || $hasGallery || $hasDam): ?>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header"><?php echo __('Sector Items'); ?></h6></li>
          <?php endif; ?>
          <?php if ($hasMuseum): ?>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'museum', 'action' => 'add']); ?>"><i class="fas fa-university fa-fw me-2"></i><?php echo __('Museum object'); ?></a></li>
          <?php endif; ?>
          <?php if ($hasGallery): ?>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'gallery', 'action' => 'add']); ?>"><i class="fas fa-images fa-fw me-2"></i><?php echo __('Gallery item'); ?></a></li>
          <?php endif; ?>
          <?php if ($hasLibrary): ?>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'library', 'action' => 'add']); ?>"><i class="fas fa-book fa-fw me-2"></i><?php echo __('Library item'); ?></a></li>
          <?php endif; ?>
          <?php if ($hasDam): ?>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'dam', 'action' => 'create']); ?>"><i class="fas fa-photo-video fa-fw me-2"></i><?php echo __('Photo/DAM asset'); ?></a></li>
          <?php endif; ?>
        <?php endif; ?>

        <?php // Inject Central Dashboards for Manage menu ?>
        <?php if ('manage' == $menu->getName()): ?>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'reports', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt fa-fw me-2"></i><?php echo __('Central Dashboards'); ?></a></li>
        <?php endif; ?>

      </ul>
    </li>
  <?php } ?>
<?php } ?>
