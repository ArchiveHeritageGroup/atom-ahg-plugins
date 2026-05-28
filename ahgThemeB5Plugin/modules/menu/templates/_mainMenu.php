<?php
// Check which sector plugins are enabled
if (!function_exists('checkPluginEnabled')) {
    function checkPluginEnabled($pluginName) {
        static $plugins = null;
        if ($plugins === null) {
            try {
                $pluginNames = \Illuminate\Database\Capsule\Manager::table('atom_plugin')
                    ->where('is_enabled', 1)
                    ->pluck('name')
                    ->toArray();
                $plugins = array_flip($pluginNames);
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

<?php // Library module menu — the circulation desk, patrons, acquisitions,
      // serials, ILL, e-resources (KBART), Z39.50/SRU and COUNTER/SUSHI
      // admin tools previously had NO navigation entry and were reachable
      // by URL only. Admin-gated for safety (staff desk tools). Raw paths
      // are the plugin's registered RouteLoader routes. ?>
<?php if ($hasLibrary && $sf_user->isAdministrator()): ?>
  <li class="nav-item dropdown d-flex flex-column">
    <a
      class="nav-link dropdown-toggle d-flex align-items-center p-0"
      href="#"
      id="library-menu"
      role="button"
      data-bs-toggle="dropdown"
      aria-expanded="false">
      <i
        class="fas fa-2x fa-fw fa-book px-0 px-lg-2 py-2"
        data-bs-toggle="tooltip"
        data-bs-placement="bottom"
        data-bs-custom-class="d-none d-lg-block"
        title="<?php echo __('Library'); ?>"
        aria-hidden="true">
      </i>
      <span class="d-lg-none mx-1" aria-hidden="true"><?php echo __('Library'); ?></span>
      <span class="visually-hidden"><?php echo __('Library'); ?></span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end mb-2" aria-labelledby="library-menu">
      <li><h6 class="dropdown-header"><?php echo __('Library'); ?></h6></li>
      <li><a class="dropdown-item" href="/library"><i class="fas fa-book fa-fw me-2"></i><?php echo __('Catalogue'); ?></a></li>
      <li><a class="dropdown-item" href="/opac"><i class="fas fa-search fa-fw me-2"></i><?php echo __('Public catalogue (OPAC)'); ?></a></li>
      <li><hr class="dropdown-divider"></li>
      <li><h6 class="dropdown-header"><?php echo __('Circulation & patrons'); ?></h6></li>
      <li><a class="dropdown-item" href="/circulation"><i class="fas fa-exchange-alt fa-fw me-2"></i><?php echo __('Circulation desk'); ?></a></li>
      <li><a class="dropdown-item" href="/patron"><i class="fas fa-users fa-fw me-2"></i><?php echo __('Patrons'); ?></a></li>
      <li><hr class="dropdown-divider"></li>
      <li><h6 class="dropdown-header"><?php echo __('Collection development'); ?></h6></li>
      <li><a class="dropdown-item" href="/acquisition"><i class="fas fa-shopping-cart fa-fw me-2"></i><?php echo __('Acquisitions'); ?></a></li>
      <li><a class="dropdown-item" href="/serial"><i class="fas fa-newspaper fa-fw me-2"></i><?php echo __('Serials'); ?></a></li>
      <li><a class="dropdown-item" href="/ill"><i class="fas fa-people-arrows fa-fw me-2"></i><?php echo __('Interlibrary loan'); ?></a></li>
      <li><hr class="dropdown-divider"></li>
      <li><h6 class="dropdown-header"><?php echo __('E-resources & interchange'); ?></h6></li>
      <li><a class="dropdown-item" href="/library/kbart/vendors"><i class="fas fa-rss fa-fw me-2"></i><?php echo __('KBART feeds'); ?></a></li>
      <li><a class="dropdown-item" href="/library/z3950"><i class="fas fa-network-wired fa-fw me-2"></i><?php echo __('Z39.50 / SRU'); ?></a></li>
      <li><a class="dropdown-item" href="/library/isbn-providers"><i class="fas fa-barcode fa-fw me-2"></i><?php echo __('ISBN providers'); ?></a></li>
      <li><hr class="dropdown-divider"></li>
      <li><h6 class="dropdown-header"><?php echo __('Usage & administration'); ?></h6></li>
      <li><a class="dropdown-item" href="/admin/library/counter"><i class="fas fa-chart-line fa-fw me-2"></i><?php echo __('COUNTER usage'); ?></a></li>
      <li><a class="dropdown-item" href="/admin/library/sushi"><i class="fas fa-key fa-fw me-2"></i><?php echo __('SUSHI settings'); ?></a></li>
      <li><a class="dropdown-item" href="/admin/library/catalogue"><i class="fas fa-clipboard-list fa-fw me-2"></i><?php echo __('Catalogue audit'); ?></a></li>
      <li><a class="dropdown-item" href="/admin/library/frbr"><i class="fas fa-code-branch fa-fw me-2"></i><?php echo __('FRBR work-key overrides'); ?></a></li>
      <li><a class="dropdown-item" href="/admin/library/creators"><i class="fas fa-user-edit fa-fw me-2"></i><?php echo __('Creators authority'); ?></a></li>
      <li><a class="dropdown-item" href="/admin/library/subjects"><i class="fas fa-tags fa-fw me-2"></i><?php echo __('Subjects authority'); ?></a></li>
    </ul>
  </li>
<?php endif; ?>
