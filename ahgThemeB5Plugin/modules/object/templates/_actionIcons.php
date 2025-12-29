<?php
/**
 * Information Object Action Icons Partial - Laravel Version
 *
 * @package    ahgThemeB5Plugin
 * @subpackage templates
 */

use Illuminate\Database\Capsule\Manager as DB;

if (!function_exists('ahg_get_collection_root_id')) {
    function ahg_get_collection_root_id($resource): ?int
    {
        if (!$resource || !isset($resource->lft) || !isset($resource->rgt)) {
            return $resource->id ?? null;
        }
        
        $root = DB::table('information_object')
            ->where('lft', '<=', $resource->lft)
            ->where('rgt', '>=', $resource->rgt)
            ->where('parent_id', 1)
            ->orderBy('lft')
            ->first();
        
        return $root ? $root->id : ($resource->id ?? null);
    }
}

if (!function_exists('ahg_has_digital_object')) {
    function ahg_has_digital_object($resourceId): bool
    {
        if (!$resourceId) {
            return false;
        }
        
        return DB::table('digital_object')
            ->where('object_id', $resourceId)
            ->exists();
    }
}

if (!function_exists('ahg_show_inventory')) {
    function ahg_show_inventory($resource): bool
    {
        if (!$resource || !isset($resource->id)) {
            return false;
        }
        
        return DB::table('information_object')
            ->where('parent_id', $resource->id)
            ->exists();
    }
}

if (!function_exists('ahg_url_for_dc_export')) {
    function ahg_url_for_dc_export($resource): string
    {
        $slug = $resource->slug ?? null;
        if ($slug) {
            return url_for(['module' => 'sfDcPlugin', 'action' => 'index', 'slug' => $slug, 'sf_format' => 'xml']);
        }
        return url_for(['module' => 'sfDcPlugin', 'action' => 'index', 'id' => $resource->id, 'sf_format' => 'xml']);
    }
}

if (!function_exists('ahg_url_for_ead_export')) {
    function ahg_url_for_ead_export($resource): string
    {
        $slug = $resource->slug ?? null;
        if ($slug) {
            return url_for(['module' => 'sfEadPlugin', 'action' => 'index', 'slug' => $slug, 'sf_format' => 'xml']);
        }
        return url_for(['module' => 'sfEadPlugin', 'action' => 'index', 'id' => $resource->id, 'sf_format' => 'xml']);
    }
}

if (!function_exists('ahg_resource_url')) {
    function ahg_resource_url($resource, string $module, string $action): string
    {
        $slug = is_object($resource) ? ($resource->slug ?? null) : null;
        if ($slug) {
            return url_for(['module' => $module, 'action' => $action, 'slug' => $slug]);
        }
        $id = is_object($resource) ? ($resource->id ?? null) : $resource;
        return url_for(['module' => $module, 'action' => $action, 'id' => $id]);
    }
}

// Get resource properties
$slug = $resource->slug ?? null;
$resourceId = $resource->id ?? null;
$collectionRootId = ahg_get_collection_root_id($resource);
$hasDigitalObject = ahg_has_digital_object($resourceId);
$showInventory = ahg_show_inventory($resource);
?>

<section id="action-icons">

  <h4 class="h5 mb-2"><?php echo __('Clipboard'); ?></h4>
  <ul class="list-unstyled">
    <li>
      <?php echo get_component('clipboard', 'button', ['slug' => $slug, 'wide' => true, 'type' => 'informationObject']); ?>
    </li>
  </ul>

  <h4 class="h5 mb-2"><?php echo __('Explore'); ?></h4>
  <ul class="list-unstyled">

    <li>
      <a class="atom-icon-link" href="<?php echo ahg_resource_url($resource, 'informationobject', 'reports'); ?>">
        <i class="fas fa-fw fa-print me-1" aria-hidden="true">
        </i><?php echo __('Reports'); ?>
      </a>
    </li>

    <?php if ($showInventory) { ?>
      <li>
        <a class="atom-icon-link" href="<?php echo ahg_resource_url($resource, 'informationobject', 'inventory'); ?>">
          <i class="fas fa-fw fa-list-alt me-1" aria-hidden="true">
          </i><?php echo __('Inventory'); ?>
        </a>
      </li>
    <?php } ?>

    <li>
      <?php if (isset($resource) && sfConfig::get('app_enable_institutional_scoping') && $sf_user->hasAttribute('search-realm')) { ?>
        <a class="atom-icon-link" href="<?php echo url_for([
            'module' => 'informationobject',
            'action' => 'browse',
            'collection' => $collectionRootId,
            'repos' => $sf_user->getAttribute('search-realm'),
            'topLod' => false, ]); ?>">
      <?php } else { ?>
        <a class="atom-icon-link" href="<?php echo url_for([
            'module' => 'informationobject',
            'action' => 'browse',
            'collection' => $collectionRootId,
            'topLod' => false, ]); ?>">
      <?php } ?>
        <i class="fas fa-fw fa-list me-1" aria-hidden="true">
        </i><?php echo __('Browse as list'); ?>
      </a>
    </li>

    <?php if ($hasDigitalObject) { ?>
      <li>
        <a class="atom-icon-link" href="<?php echo url_for([
            'module' => 'informationobject',
            'action' => 'browse',
            'collection' => $collectionRootId,
            'topLod' => false,
            'view' => 'card',
            'onlyMedia' => true, ]); ?>">
          <i class="fas fa-fw fa-image me-1" aria-hidden="true">
          </i><?php echo __('Browse digital objects'); ?>
        </a>
      </li>
    <?php } ?>
  </ul>

  <?php if ($sf_user->isAdministrator()) { ?>
    <h4 class="h5 mb-2"><?php echo __('Import'); ?></h4>
    <ul class="list-unstyled">
      <li>
        <a class="atom-icon-link" href="<?php echo ahg_resource_url($resource, 'object', 'importSelect') . '?type=xml'; ?>">
          <i class="fas fa-fw fa-download me-1" aria-hidden="true">
          </i><?php echo __('XML'); ?>
        </a>
      </li>

      <li>
        <a class="atom-icon-link" href="<?php echo ahg_resource_url($resource, 'object', 'importSelect') . '?type=csv'; ?>">
          <i class="fas fa-fw fa-download me-1" aria-hidden="true">
          </i><?php echo __('CSV'); ?>
        </a>
      </li>
    </ul>
  <?php } ?>

  <h4 class="h5 mb-2"><?php echo __('Export'); ?></h4>
  <ul class="list-unstyled">
    <?php if ($sf_context->getConfiguration()->isPluginEnabled('sfDcPlugin')) { ?>
      <li>
        <a class="atom-icon-link" href="<?php echo ahg_url_for_dc_export($resource); ?>">
          <i class="fas fa-fw fa-upload me-1" aria-hidden="true">
          </i><?php echo __('Dublin Core 1.1 XML'); ?>
        </a>
      </li>
    <?php } ?>

    <?php if ($sf_context->getConfiguration()->isPluginEnabled('sfEadPlugin')) { ?>
      <li>
        <a class="atom-icon-link" href="<?php echo ahg_url_for_ead_export($resource); ?>">
          <i class="fas fa-fw fa-upload me-1" aria-hidden="true">
          </i><?php echo __('EAD 2002 XML'); ?>
        </a>
      </li>
    <?php } ?>

    <?php if ('sfModsPlugin' == $sf_context->getModuleName() && $sf_context->getConfiguration()->isPluginEnabled('sfModsPlugin')) { ?>
      <li>
        <a class="atom-icon-link" href="<?php echo url_for(['module' => 'sfModsPlugin', 'action' => 'index', 'slug' => $slug, 'sf_format' => 'xml']); ?>">
          <i class="fas fa-fw fa-upload me-1" aria-hidden="true">
          </i><?php echo __('MODS 3.5 XML'); ?>
        </a>
      </li>
    <?php } ?>
  </ul>

  <?php echo get_component('informationobject', 'findingAid', ['resource' => $resource, 'contextMenu' => true]); ?>

  <?php echo get_component('informationobject', 'calculateDatesLink', ['resource' => $resource, 'contextMenu' => true]); ?>

</section>