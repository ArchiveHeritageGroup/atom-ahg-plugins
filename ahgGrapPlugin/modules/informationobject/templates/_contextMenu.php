<?php
/**
 * GRAP Context Menu Integration.
 *
 * This partial adds GRAP links to the information object context menu.
 * 
 * For Bootstrap 5 themes (arDominionB5Plugin, arWDBThemeB5Plugin, etc.):
 * Add to your theme's informationobject/_contextMenu.php:
 *
 *   <?php include_partial('grap/contextMenu', ['resource' => $resource]) ?>
 *
 * For Bootstrap 2 themes:
 * Add to your theme's informationobject/_actions.php in the More dropdown:
 *
 *   <?php include_partial('grap/menu', ['resource' => $resource]) ?>
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

if (!isset($resource) || !$resource instanceof QubitInformationObject) {
    return;
}
?>

<!-- GRAP Financial Data Links -->
<li><hr class="dropdown-divider"></li>
<li>
  <a class="dropdown-item" href="<?php echo url_for(['slug' => $resource->slug, 'module' => 'grap', 'action' => 'index']) ?>">
    <i class="fas fa-calculator me-2"></i>
    <?php echo __('View GRAP data') ?>
  </a>
</li>
<?php if (($sf_user->isAdministrator() || $sf_user->hasCredential('editor'))): ?>
<li>
  <a class="dropdown-item" href="<?php echo url_for(['slug' => $resource->slug, 'module' => 'grap', 'action' => 'edit']) ?>">
    <i class="fas fa-edit me-2"></i>
    <?php echo __('Edit GRAP data') ?>
  </a>
</li>
<?php endif ?>
