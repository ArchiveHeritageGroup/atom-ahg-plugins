<?php
/*
 * Wikidata links for the people and organisations on a description (#209): its
 * creators and name access points that have a Wikidata identifier.
 * Usage: include_partial('authority/descriptionWikidata', ['ioId' => $resource->id])
 */
$ioId = (int) ($ioId ?? 0);
if ($ioId < 1) {
    return;
}

try {
    $db = \Illuminate\Database\Capsule\Manager::class;
    $actorIds = $db::table('event')->where('object_id', $ioId)->whereNotNull('actor_id')->pluck('actor_id')
        ->merge($db::table('relation')->where('subject_id', $ioId)->where('type_id', QubitTerm::NAME_ACCESS_POINT_ID)->pluck('object_id'))
        ->unique()->values()->all();
    $links = $actorIds ? $db::table('ahg_actor_identifier as i')
        ->join('actor as ac', 'ac.id', '=', 'i.actor_id')
        ->join('actor_i18n as a', function ($j) {
            $j->on('a.id', '=', 'ac.id')->on('a.culture', '=', 'ac.source_culture');
        })
        ->whereIn('i.actor_id', $actorIds)
        ->where('i.identifier_type', 'wikidata')
        ->whereNotNull('i.uri')
        ->select('i.actor_id', 'i.uri', 'i.identifier_value', 'a.authorized_form_of_name as name')
        ->orderBy('a.authorized_form_of_name')
        ->get()->unique('actor_id')->values()->all() : [];
} catch (\Throwable $e) {
    return; // identifiers table absent: nothing to show
}

if (empty($links)) {
    return;
}
?>
<section class="card mb-3">
  <h2 class="h5 p-3 mb-0"><i class="fas fa-link me-1" aria-hidden="true"></i><?php echo __('Wikidata'); ?></h2>
  <ul class="list-group list-group-flush">
    <?php foreach ($links as $link) { ?>
      <li class="list-group-item">
        <a href="<?php echo esc_entities($link->uri); ?>" target="_blank" rel="noopener"><?php echo esc_entities($link->name ?: $link->identifier_value); ?></a>
        <span class="text-muted small ms-1"><?php echo esc_entities($link->identifier_value); ?></span>
      </li>
    <?php } ?>
  </ul>
</section>
