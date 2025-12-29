<?php
/**
 * GRAP Actions Integration for Bootstrap 2 Themes.
 *
 * For Bootstrap 2 themes, add to your _actions.php More dropdown:
 *
 *   <?php include_partial('grap/menu', ['resource' => $resource]) ?>
 *
 * Or copy the li elements below directly into your More dropdown menu.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

if (!isset($resource) || !$resource instanceof QubitInformationObject) {
    return;
}
?>

<li class="divider"></li>
<li>
  <?php echo link_to(
      '<i class="fa fa-calculator"></i> '.__('View GRAP data'),
      ['slug' => $resource->slug, 'module' => 'grap', 'action' => 'index']
  ) ?>
</li>
<?php if (($sf_user->isAdministrator() || $sf_user->hasCredential('editor'))): ?>
<li>
  <?php echo link_to(
      '<i class="fa fa-pencil"></i> '.__('Edit GRAP data'),
      ['slug' => $resource->slug, 'module' => 'grap', 'action' => 'edit']
  ) ?>
</li>
<?php endif ?>
