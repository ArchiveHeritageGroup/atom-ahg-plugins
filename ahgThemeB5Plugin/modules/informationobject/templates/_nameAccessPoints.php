<?php
/**
 * Name Access Points Partial
 *
 * Shows actors related to the information object via events and relations.
 * Uses helper functions from AhgLaravelHelper.php
 *
 * @package    ahgThemeB5Plugin
 * @subpackage templates
 */

// Load helper functions if not already loaded
if (!function_exists('ahg_get_actor_events')) {
    require_once sfConfig::get('sf_plugins_dir').'/ahgUiOverridesPlugin/lib/helper/AhgLaravelHelper.php';
}

// Get resource ID
$resourceId = is_object($resource) ? ($resource->id ?? null) : $resource;

if (!$resourceId) {
    return;
}

// Get actor events (creators, contributors, etc.) - uses helper function
$actorEvents = ahg_get_actor_events($resourceId);

// Get name access points via relations - uses helper function
$nameAccessPoints = ahg_get_name_access_relations($resourceId);

// Combine and deduplicate by actor ID
$allActors = [];
$seenIds = [];

foreach ($actorEvents as $event) {
    if (!in_array($event->id, $seenIds)) {
        $seenIds[] = $event->id;
        $allActors[] = $event;
    }
}

foreach ($nameAccessPoints as $nap) {
    if (!in_array($nap->id, $seenIds)) {
        $seenIds[] = $nap->id;
        $nap->event_type = null;
        $allActors[] = $nap;
    }
}

if (empty($allActors)) {
    return;
}

// Check if sidebar display
$isSidebar = isset($sidebar) && $sidebar;
?>

<?php
// Get the base path for constructing actor URLs
// AtoM routing uses QubitMetadataRoute - URLs are just /:slug
// The route determines module from object class in database
$basePath = sfContext::getInstance()->getRequest()->getScriptName();
?>
<?php if ($isSidebar) { ?>
  <section id="nameAccessPointsSection">
    <h4><?php echo __('Related people and organizations'); ?></h4>
    <ul class="list-unstyled">
      <?php foreach ($allActors as $actor) { ?>
        <li>
          <?php if ($actor->slug) { ?>
            <a href="<?php echo $basePath; ?>/<?php echo rawurlencode($actor->slug); ?>">
              <?php echo htmlspecialchars($actor->name ?? ''); ?>
            </a>
          <?php } else { ?>
            <?php echo htmlspecialchars($actor->name ?? ''); ?>
          <?php } ?>
          <?php if (!empty($actor->event_type)) { ?>
            <span class="text-muted">(<?php echo htmlspecialchars($actor->event_type); ?>)</span>
          <?php } ?>
        </li>
      <?php } ?>
    </ul>
  </section>
<?php } else { ?>
<div class="field<?php echo isset($sidebar) ? '' : ' '.render_b5_show_field_css_classes(); ?>">

  <?php if (isset($mods)) { ?>
    <?php echo render_b5_show_label(__('Names')); ?>
  <?php } else { ?>
    <?php echo render_b5_show_label(__('Name access points')); ?>
  <?php } ?>

  <div<?php echo isset($sidebar) ? '' : ' class="'.render_b5_show_value_css_classes().'"'; ?>>
    <ul class="<?php echo isset($sidebar) ? 'list-unstyled' : render_b5_show_list_css_classes(); ?>">
      <?php foreach ($allActors as $actor) { ?>
        <li>
          <?php if ($actor->slug) { ?>
            <a href="<?php echo $basePath; ?>/<?php echo rawurlencode($actor->slug); ?>"><?php echo htmlspecialchars($actor->name ?? ''); ?></a>
          <?php } else { ?>
            <?php echo htmlspecialchars($actor->name ?? ''); ?>
          <?php } ?>
          <?php if (!empty($actor->event_type)) { ?>
            <span class="text-muted">(<?php echo htmlspecialchars($actor->event_type); ?>)</span>
          <?php } ?>
        </li>
      <?php } ?>
    </ul>
  </div>

</div>
<?php } ?>
