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
$hasRic = checkPluginEnabled('ahgRicManagePlugin');
?>
<?php foreach ([$addMenu, $manageMenu, $importMenu, $adminMenu] as $menu) { ?>
  <?php $menuName = $menu ? $menu->getName() : ''; ?>
  <?php if (
      $menu && ('add' == $menuName
      || 'manage' == $menuName)
      || $sf_user->isAdministrator()
      // Import is also available to the Editor role (Admin still gets everything).
      || ('import' == $menuName && $sf_user->hasCredential(['administrator', 'editor'], false))
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
          <?php if ($hasLibrary || $hasMuseum || $hasGallery || $hasDam || $hasRic): ?>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header"><?php echo __('Sector Items'); ?></h6></li>
          <?php endif; ?>
          <?php if ($hasRic): ?>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ricManage', 'action' => 'add']); ?>"><i class="fas fa-project-diagram fa-fw me-2"></i><?php echo __('Records in Context (RiC)'); ?></a></li>
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

        <?php // Inject Central Dashboards for Manage menu.
              //
              // Gated on ahgReportsPlugin: the link goes to reports/index, which only
              // exists while that plugin is enabled. Ungated, the Manage menu offered
              // "Central Dashboards" on every instance and answered 404 - seen on a
              // minimal install, 2026-08-18. Same fault as the Exhibition spaces entry,
              // in a different template. ?>
        <?php if ('manage' == $menu->getName()
                  && in_array('ahgReportsPlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'reports', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt fa-fw me-2"></i><?php echo __('Central Dashboards'); ?></a></li>
        <?php endif; ?>

        <?php // Plugins contribute their Manage entries through AhgNav. ahgCorePlugin only
              // injects them on non-B5 themes (this theme builds its own menus), so on B5
              // they were never shown: Custom Fields, Grid entry and others had no menu
              // path, and editors could not reach features they may use. Listed here,
              // filtered by the user's credentials, minus anything the menu already has.
        if ('manage' == $menu->getName() && class_exists('AhgNav')) {
            $norm = static fn ($u) => rtrim(str_replace('/index.php', '', (string) $u), '/');
            // Skip what the menu already offers, by URL or by label ("Backup & Restore",
            // "Central Dashboard" beside "Central Dashboards").
            $have = [$norm(url_for(['module' => 'reports', 'action' => 'index'])) => true];
            $labels = ['central dashboard' => true];
            // ahgBackupPlugin adds its own Manage entry after rendering (MenuInjector).
            if (class_exists('AhgBackup\\Listeners\\MenuInjector')) {
                $have['/backup'] = true;
                // The injector adds Backup & Restore for anyone with a Manage menu, but
                // the page admits administrators only. It skips its entry when the page
                // already holds node_ahgBackup, so mark it present for everyone else.
                if (!$sf_user->isAdministrator()) {
                    echo '<!-- node_ahgBackup: administrators only -->';
                }
            }
            foreach ($menu->getChildren() as $child) {
                $have[$norm(url_for($child->getPath(['getUrl' => true, 'resolveAlias' => true])))] = true;
                $labels[rtrim(strtolower(trim(html_entity_decode(strip_tags($child->getLabel(['cultureFallback' => true]))))), 's')] = true;
            }
            // An entry that names no credentials is shown to administrators only: several
            // such entries lead to admin-only pages, and an editor must not be offered a 403.
            $isAdmin = $sf_user->isAdministrator();
            $extra = array_filter(AhgNav::resolved('manage', $sf_user), static fn ($i) => !isset($have[$norm($i['href'])])
                && !isset($labels[rtrim(strtolower(trim(html_entity_decode(__($i['label'])))), 's')])
                && ($isAdmin || !empty($i['credentials'])));
            uasort($extra, static fn ($a, $b) => strcasecmp(__($a['label']), __($b['label'])));
            if ($extra) { ?>
          <li><hr class="dropdown-divider"></li>
          <li><h6 class="dropdown-header"><?php echo __('Extensions'); ?></h6></li>
          <?php foreach ($extra as $item) { ?>
            <li><a class="dropdown-item" href="<?php echo esc_entities($item['href']); ?>"><?php echo esc_entities(__($item['label'])); ?><?php echo empty($item['badgeCount']) ? '' : ' <span class="badge bg-secondary">'.(int) $item['badgeCount'].'</span>'; ?></a></li>
          <?php } ?>
          <style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>#manage-menu + .dropdown-menu{max-height:80vh;overflow-y:auto}</style>
        <?php }
        } ?>

      </ul>
    </li>
  <?php } ?>
<?php } ?>

<?php // Library module menu - the circulation desk, patrons, acquisitions,
      // serials, ILL, e-resources (KBART), Z39.50/SRU and COUNTER/SUSHI
      // admin tools were relocated OUT of the main nav into the Reports &
      // Dashboards hub at /reports/ ("Sector Dashboards" > Library) on
      // 2026-06-01 (per Johan). The full toolset lives there now; this
      // dropdown was removed to declutter the primary navigation. ?>
