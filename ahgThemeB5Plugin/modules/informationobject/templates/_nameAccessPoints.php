<?php
/**
 * Name Access Points - Bootstrap 5 standard styling
 */
require_once sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/lib/helper/AhgLaravelHelper.php';

$resourceId = is_object($resource) ? ($resource->id ?? null) : $resource;
if (!$resourceId) return;

$actorEvents = ahg_get_actor_events($resourceId);
$nameAccessPoints = ahg_get_name_access_relations($resourceId);

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

// Don't display if no data
if (empty($allActors)) return;

$isSidebar = isset($sidebar) && $sidebar;
?>

<?php if ($isSidebar): ?>
<h4><?php echo __('Related people and organizations'); ?></h4>
<ul class="list-unstyled">
  <?php foreach ($allActors as $actor): ?>
    <li>
      <?php if ($actor->slug): ?>
        <a href="<?php echo url_for(['module' => 'actor', 'slug' => $actor->slug]); ?>"><?php echo esc_entities($actor->name ?? ''); ?></a>
      <?php else: ?>
        <?php echo esc_entities($actor->name ?? ''); ?>
      <?php endif; ?>
      <?php if (!empty($actor->event_type)): ?>
        <span class="text-muted">(<?php echo esc_entities($actor->event_type); ?>)</span>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>

<?php else: ?>
<h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Related people and organizations'); ?></h4>
<?php foreach ($allActors as $actor): 
  $actorLink = $actor->slug 
      ? '<a href="' . url_for(['module' => 'actor', 'slug' => $actor->slug]) . '">' . esc_entities($actor->name ?? '') . '</a>'
      : esc_entities($actor->name ?? '');
  if (!empty($actor->event_type)) {
      $actorLink .= ' <span class="text-muted">(' . esc_entities($actor->event_type) . ')</span>';
  }
  echo render_show(null, $actorLink);
endforeach; ?>
<?php endif; ?>
