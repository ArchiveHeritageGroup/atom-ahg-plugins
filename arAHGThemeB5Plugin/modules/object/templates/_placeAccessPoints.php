<?php
require_once sfConfig::get('sf_plugins_dir').'/arAHGThemeB5Plugin/lib/helper/AhgLaravelHelper.php';

$resourceId = is_object($resource) ? ($resource->id ?? null) : $resource;
if (!$resourceId) { return; }
$places = ahg_get_place_access_points($resourceId);
if (empty($places)) { return; }
$isSidebar = isset($sidebar) && $sidebar;
?>
<?php if ($isSidebar) { ?>
  <section id="placeAccessPointsSection">
    <h4><?php echo __('Place access points'); ?></h4>
    <ul class="list-unstyled">
      <?php foreach ($places as $place) { ?>
        <li><?php if ($place->slug) { ?><a href="<?php echo url_for(['module' => 'term', 'action' => 'index', 'slug' => $place->slug]); ?>"><?php echo htmlspecialchars($place->name ?? ''); ?></a><?php } else { echo htmlspecialchars($place->name ?? ''); } ?></li>
      <?php } ?>
    </ul>
  </section>
<?php } else { ?>
  <div class="field">
    <h3><?php echo __('Place access points'); ?></h3>
    <div><ul>
      <?php foreach ($places as $place) { ?>
        <li><?php if ($place->slug) { ?><a href="<?php echo url_for(['module' => 'term', 'action' => 'index', 'slug' => $place->slug]); ?>"><?php echo htmlspecialchars($place->name ?? ''); ?></a><?php } else { echo htmlspecialchars($place->name ?? ''); } ?></li>
      <?php } ?>
    </ul></div>
  </div>
<?php } ?>
