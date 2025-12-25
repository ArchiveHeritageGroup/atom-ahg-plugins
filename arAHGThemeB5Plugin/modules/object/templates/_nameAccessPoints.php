<?php
/**
 * Name Access Points Partial - Laravel Version
 *
 * Shows actors related to the information object via events and relations.
 *
 * @package    arAHGThemeB5Plugin
 * @subpackage templates
 */

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Get actor events for resource (creators, contributors, etc.)
 */
function ahg_get_actor_events($resourceId): array
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    return DB::table('event as e')
        ->join('actor as a', 'e.actor_id', '=', 'a.id')
        ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
            $join->on('a.id', '=', 'ai.id')
                ->where('ai.culture', '=', $culture);
        })
        ->leftJoin('actor_i18n as ai_en', function ($join) {
            $join->on('a.id', '=', 'ai_en.id')
                ->where('ai_en.culture', '=', 'en');
        })
        ->leftJoin('term_i18n as ti', function ($join) use ($culture) {
            $join->on('e.type_id', '=', 'ti.id')
                ->where('ti.culture', '=', $culture);
        })
        ->leftJoin('term_i18n as ti_en', function ($join) {
            $join->on('e.type_id', '=', 'ti_en.id')
                ->where('ti_en.culture', '=', 'en');
        })
        ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
        ->where('e.object_id', $resourceId)
        ->whereNotNull('e.actor_id')
        ->select([
            'a.id',
            'e.type_id',
            'slug.slug',
            DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as name'),
            DB::raw('COALESCE(ti.name, ti_en.name) as event_type'),
        ])
        ->orderBy(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'))
        ->get()
        ->toArray();
}

/**
 * Get name access points via relation table
 */
function ahg_get_name_access_relations($resourceId): array
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    // Name access point relation type ID = 161
    $nameAccessRelationTypeId = 161;
    
    return DB::table('relation as r')
        ->join('actor as a', 'r.object_id', '=', 'a.id')
        ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
            $join->on('a.id', '=', 'ai.id')
                ->where('ai.culture', '=', $culture);
        })
        ->leftJoin('actor_i18n as ai_en', function ($join) {
            $join->on('a.id', '=', 'ai_en.id')
                ->where('ai_en.culture', '=', 'en');
        })
        ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
        ->where('r.subject_id', $resourceId)
        ->where('r.type_id', $nameAccessRelationTypeId)
        ->select([
            'a.id',
            'slug.slug',
            DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as name'),
        ])
        ->orderBy(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'))
        ->get()
        ->toArray();
}

// Get resource ID
$resourceId = is_object($resource) ? ($resource->id ?? null) : $resource;

if (!$resourceId) {
    return;
}

// Get actor events (creators, contributors, etc.)
$actorEvents = ahg_get_actor_events($resourceId);

// Get name access points via relations
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

<?php if ($isSidebar) { ?>
  <section id="nameAccessPointsSection">
    <h4><?php echo __('Related people and organizations'); ?></h4>
    <ul class="list-unstyled">
      <?php foreach ($allActors as $actor) { ?>
        <li>
          <?php if ($actor->slug) { ?>
            <a href="<?php echo url_for(['module' => 'actor', 'action' => 'index', 'slug' => $actor->slug]); ?>">
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
  <div class="field">
    <h3><?php echo __('Related people and organizations'); ?></h3>
    <div>
      <ul>
        <?php foreach ($allActors as $actor) { ?>
          <li>
            <?php if ($actor->slug) { ?>
              <a href="<?php echo url_for(['module' => 'actor', 'action' => 'index', 'slug' => $actor->slug]); ?>">
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
    </div>
  </div>
<?php } ?>