<?php
require_once sfConfig::get('sf_plugins_dir').'/arAHGThemeB5Plugin/lib/helper/AhgLaravelHelper.php';
use Illuminate\Database\Capsule\Manager as DB;

$slug = $resource->slug ?? null;
$resourceId = $resource->id ?? null;
$collectionRootId = ahg_get_collection_root_id($resource);
$hasDigitalObject = ahg_has_digital_object($resourceId);
$showInventory = ahg_show_inventory($resource);

// Get collection root slug
$collectionRootSlug = null;
if ($collectionRootId) {
    $collectionRootSlug = DB::table('slug')->where('object_id', $collectionRootId)->value('slug');
}

// Check for finding aid
$hasGeneratedFindingAid = false;
if ($collectionRootId) {
    $hasGeneratedFindingAid = DB::table('property')
        ->where('object_id', $collectionRootId)
        ->where('name', 'findingAidPath')
        ->exists();
}

// Check for children (for calculate dates)
$hasChildren = DB::table('information_object')->where('parent_id', $resourceId)->exists();
?>
<section id="action-icons">
  <h4 class="h5 mb-2"><?php echo __('Clipboard'); ?></h4>
  <ul class="list-unstyled">
    <li><?php echo get_component('clipboard', 'button', ['slug' => $slug, 'wide' => true, 'type' => 'informationObject']); ?></li>
  </ul>

  <h4 class="h5 mb-2"><?php echo __('Explore'); ?></h4>
  <ul class="list-unstyled">
    <li><a class="atom-icon-link" href="<?php echo ahg_resource_url($resource, 'informationobject', 'reports'); ?>"><i class="fas fa-fw fa-print me-1" aria-hidden="true"></i><?php echo __('Reports'); ?></a></li>
    <?php if ($showInventory) { ?><li><a class="atom-icon-link" href="<?php echo ahg_resource_url($resource, 'informationobject', 'inventory'); ?>"><i class="fas fa-fw fa-list-alt me-1" aria-hidden="true"></i><?php echo __('Inventory'); ?></a></li><?php } ?>
    <li>
      <?php if (isset($resource) && sfConfig::get('app_enable_institutional_scoping') && $sf_user->hasAttribute('search-realm')) { ?>
        <a class="atom-icon-link" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse', 'collection' => $collectionRootId, 'repos' => $sf_user->getAttribute('search-realm'), 'topLod' => false]); ?>">
      <?php } else { ?>
        <a class="atom-icon-link" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse', 'collection' => $collectionRootId, 'topLod' => false]); ?>">
      <?php } ?>
        <i class="fas fa-fw fa-list me-1" aria-hidden="true"></i><?php echo __('Browse as list'); ?></a>
    </li>
    <?php if ($hasDigitalObject) { ?><li><a class="atom-icon-link" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse', 'collection' => $collectionRootId, 'topLod' => false, 'view' => 'card', 'onlyMedia' => true]); ?>"><i class="fas fa-fw fa-image me-1" aria-hidden="true"></i><?php echo __('Browse digital objects'); ?></a></li><?php } ?>
  </ul>

  <?php if ($sf_user->isAdministrator()) { ?>
  <h4 class="h5 mb-2"><?php echo __('Import'); ?></h4>
  <ul class="list-unstyled">
    <li><a class="atom-icon-link" href="<?php echo ahg_resource_url($resource, 'object', 'importSelect') . '?type=xml'; ?>"><i class="fas fa-fw fa-download me-1" aria-hidden="true"></i><?php echo __('XML'); ?></a></li>
    <li><a class="atom-icon-link" href="<?php echo ahg_resource_url($resource, 'object', 'importSelect') . '?type=csv'; ?>"><i class="fas fa-fw fa-download me-1" aria-hidden="true"></i><?php echo __('CSV'); ?></a></li>
  </ul>
  <?php } ?>

  <h4 class="h5 mb-2"><?php echo __('Export'); ?></h4>
  <ul class="list-unstyled">
    <?php if ($sf_context->getConfiguration()->isPluginEnabled('sfDcPlugin')) { ?><li><a class="atom-icon-link" href="<?php echo ahg_url_for_dc_export($resource); ?>"><i class="fas fa-fw fa-upload me-1" aria-hidden="true"></i><?php echo __('Dublin Core 1.1 XML'); ?></a></li><?php } ?>
    <?php if ($sf_context->getConfiguration()->isPluginEnabled('sfEadPlugin')) { ?><li><a class="atom-icon-link" href="<?php echo ahg_url_for_ead_export($resource); ?>"><i class="fas fa-fw fa-upload me-1" aria-hidden="true"></i><?php echo __('EAD 2002 XML'); ?></a></li><?php } ?>
    <?php if ('sfModsPlugin' == $sf_context->getModuleName() && $sf_context->getConfiguration()->isPluginEnabled('sfModsPlugin')) { ?><li><a class="atom-icon-link" href="<?php echo url_for(['module' => 'sfModsPlugin', 'action' => 'index', 'slug' => $slug, 'sf_format' => 'xml']); ?>"><i class="fas fa-fw fa-upload me-1" aria-hidden="true"></i><?php echo __('MODS 3.5 XML'); ?></a></li><?php } ?>
  </ul>

  <?php // Finding Aid - inline instead of component ?>
  <?php if ($hasGeneratedFindingAid && $collectionRootSlug) { ?>
  <h4 class="h5 mb-2"><?php echo __('Finding aid'); ?></h4>
  <ul class="list-unstyled">
    <li>
      <a class="atom-icon-link" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'findingAid', 'slug' => $collectionRootSlug]); ?>">
        <i class="fas fa-fw fa-file-pdf me-1" aria-hidden="true"></i><?php echo __('Download finding aid'); ?>
      </a>
    </li>
    <?php if ($sf_user->isAdministrator()) { ?>
    <li>
      <a class="atom-icon-link" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'generateFindingAid', 'slug' => $collectionRootSlug]); ?>">
        <i class="fas fa-fw fa-cogs me-1" aria-hidden="true"></i><?php echo __('Generate finding aid'); ?>
      </a>
    </li>
    <?php } ?>
  </ul>
  <?php } elseif ($sf_user->isAdministrator() && $collectionRootSlug) { ?>
  <h4 class="h5 mb-2"><?php echo __('Finding aid'); ?></h4>
  <ul class="list-unstyled">
    <li>
      <a class="atom-icon-link" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'generateFindingAid', 'slug' => $collectionRootSlug]); ?>">
        <i class="fas fa-fw fa-cogs me-1" aria-hidden="true"></i><?php echo __('Generate finding aid'); ?>
      </a>
    </li>
  </ul>
  <?php } ?>

  <?php // Calculate Dates - inline instead of component ?>
  <?php if ($sf_user->isAdministrator() && $hasChildren && $slug) { ?>
  <h4 class="h5 mb-2"><?php echo __('Calculate dates'); ?></h4>
  <ul class="list-unstyled">
    <li>
      <a class="atom-icon-link" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'calculateDates', 'slug' => $slug]); ?>">
        <i class="fas fa-fw fa-calendar me-1" aria-hidden="true"></i><?php echo __('Calculate dates'); ?>
      </a>
    </li>
  </ul>
  <?php } ?>

</section>
