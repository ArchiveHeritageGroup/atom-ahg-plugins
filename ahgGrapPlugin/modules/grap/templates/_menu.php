<?php
/**
 * GRAP Menu Link Partial.
 *
 * This partial adds the GRAP financial data link to the "More" dropdown.
 * Include this in your theme's _actions.php or contextMenu.
 *
 * Usage:
 *   <?php include_partial('grap/menu', ['resource' => $resource]) ?>
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

if (isset($resource) && $resource instanceof QubitInformationObject): ?>
<li class="divider"></li>
<li>
  <?php echo link_to(
      '<i class="fa fa-calculator"></i> '.__('GRAP financial data'),
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
<?php endif ?>
