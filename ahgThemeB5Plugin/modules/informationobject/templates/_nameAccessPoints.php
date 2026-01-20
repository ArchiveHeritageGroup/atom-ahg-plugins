<?php
/**
 * Name Access Points - Bootstrap 5 standard styling
 */
require_once sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/lib/helper/AhgLaravelHelper.php';

// Load PII masking helper if privacy plugin is enabled
$piiEnabled = in_array('ahgPrivacyPlugin', sfProjectConfiguration::getActive()->getPlugins());
if ($piiEnabled) {
    require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/helper/PiiHelper.php';
}

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
  <?php foreach ($allActors as $actor):
    $actorName = $actor->name ?? '';
    $isMasked = false;
    if ($piiEnabled && function_exists('pii_mask_value')) {
        $maskResult = pii_mask_value($resourceId, $actorName, 'PERSON');
        if ($maskResult['masked']) {
            $actorName = $maskResult['value'];
            $isMasked = true;
        }
    }
  ?>
    <li>
      <?php if ($isMasked): ?>
        <span class="text-danger"><?php echo esc_entities($actorName); ?></span>
      <?php elseif ($actor->slug): ?>
        <a href="<?php echo url_for(['module' => 'actor', 'slug' => $actor->slug]); ?>"><?php echo esc_entities($actorName); ?></a>
      <?php else: ?>
        <?php echo esc_entities($actorName); ?>
      <?php endif; ?>
      <?php if (!empty($actor->event_type) && !$isMasked): ?>
        <span class="text-muted">(<?php echo esc_entities($actor->event_type); ?>)</span>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>

<?php else: ?>
<h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Related people and organizations'); ?></h4>
<?php foreach ($allActors as $actor):
  $actorName = $actor->name ?? '';
  $isMasked = false;
  if ($piiEnabled && function_exists('pii_mask_value')) {
      $maskResult = pii_mask_value($resourceId, $actorName, 'PERSON');
      if ($maskResult['masked']) {
          $actorName = $maskResult['value'];
          $isMasked = true;
      }
  }

  if ($isMasked) {
      $actorLink = '<span class="text-danger">' . esc_entities($actorName) . '</span>';
  } else {
      $actorLink = $actor->slug
          ? '<a href="' . url_for(['module' => 'actor', 'slug' => $actor->slug]) . '">' . esc_entities($actorName) . '</a>'
          : esc_entities($actorName);
      if (!empty($actor->event_type)) {
          $actorLink .= ' <span class="text-muted">(' . esc_entities($actor->event_type) . ')</span>';
      }
  }
  echo render_show(null, $actorLink);
endforeach; ?>
<?php endif; ?>
