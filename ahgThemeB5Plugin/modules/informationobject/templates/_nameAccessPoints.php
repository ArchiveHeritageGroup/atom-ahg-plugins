<?php
require_once sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/lib/helper/AhgLaravelHelper.php';

$resourceId = is_object($resource) ? ($resource->id ?? null) : $resource;
if (!$resourceId) { return; }
$actorEvents = ahg_get_actor_events($resourceId);
$nameAccessPoints = ahg_get_name_access_relations($resourceId);
$allActors = []; $seenIds = [];
foreach ($actorEvents as $event) { if (!in_array($event->id, $seenIds)) { $seenIds[] = $event->id; $allActors[] = $event; } }
foreach ($nameAccessPoints as $nap) { if (!in_array($nap->id, $seenIds)) { $seenIds[] = $nap->id; $nap->event_type = null; $allActors[] = $nap; } }
if (empty($allActors)) { return; }
$isSidebar = isset($sidebar) && $sidebar;
?>
<?php if ($isSidebar) { ?>
  <section id="nameAccessPointsSection">
    <h4><?php echo __('Related people and organizations'); ?></h4>
    <ul class="list-unstyled">
      <?php foreach ($allActors as $actor) { ?>
        <li><?php if ($actor->slug) { ?><a href="<?php echo url_for(['module' => 'actor', 'action' => 'index', 'slug' => $actor->slug]); ?>"><?php echo htmlspecialchars($actor->name ?? ''); ?></a><?php } else { echo htmlspecialchars($actor->name ?? ''); } ?><?php if (!empty($actor->event_type)) { ?> <span class="text-muted">(<?php echo htmlspecialchars($actor->event_type); ?>)</span><?php } ?></li>
      <?php } ?>
    </ul>
  </section>
<?php } else { ?>
  <div class="field">
    <h3><?php echo __('Related people and organizations'); ?></h3>
    <div><ul>
      <?php foreach ($allActors as $actor) { ?>
        <li><?php if ($actor->slug) { ?><a href="<?php echo url_for(['module' => 'actor', 'action' => 'index', 'slug' => $actor->slug]); ?>"><?php echo htmlspecialchars($actor->name ?? ''); ?></a><?php } else { echo htmlspecialchars($actor->name ?? ''); } ?><?php if (!empty($actor->event_type)) { ?> <span class="text-muted">(<?php echo htmlspecialchars($actor->event_type); ?>)</span><?php } ?></li>
      <?php } ?>
    </ul></div>
  </div>
<?php } ?>
