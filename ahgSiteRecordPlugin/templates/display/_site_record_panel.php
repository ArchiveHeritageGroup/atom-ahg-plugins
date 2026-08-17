<?php

use AhgSiteRecordPlugin\Services\SiteRecordService;

/**
 * Site record panel, shown on an authority record.
 *
 * Rendered by DisplayActionRegistry with $resource in scope (the actor). Renders
 * nothing when the authority record is not a site, so it is invisible on the
 * majority of records.
 *
 * Locality comes from SiteRecordService::present(), which has already applied the
 * visibility rule - this template never sees an exact coordinate it is not
 * entitled to show.
 */

if (!isset($resource) || empty($resource->id)) {
    return;
}

if (!class_exists(SiteRecordService::class)) {
    return;
}

$service = new SiteRecordService();
$record = $service->findByActorForDisplay((int) $resource->id);

if (!$record) {
    // No site record for this authority record.
    //
    // For the public, and for anyone who cannot create one, render nothing -
    // most authority records are not sites and an empty panel on every one of
    // them is noise.
    //
    // For an editor, render the way in. The add route has existed since the
    // plugin shipped and nothing ever linked to it, so on an instance with no
    // site records yet - a fresh install, or archaeology on 2026-08-17 - there
    // was no path to the first one short of typing /site-record/add/<actor id>
    // by hand. The panel is the only place this belongs: a site record is an
    // extension of the authority record, so it is created from it.
    // From the context, not $sf_user.
    //
    // $sf_user only exists when a template is rendered through the view layer.
    // SiteRecordPanelInjector includes this partial from response.filter_content,
    // where it is not defined - the same reason esc_specialchars has to be
    // loaded by hand further up. Testing $sf_user there is always false, so the
    // add button rendered nowhere and looked like the panel was simply not
    // working.
    $user = \sfContext::hasInstance() ? \sfContext::getInstance()->getUser() : null;

    if (!$user
        || !method_exists($user, 'hasCredential')
        || (!$user->hasCredential('editor') && !$user->hasCredential('administrator'))) {
        return;
    }

    ?>
    <section class="ahg-site-record-panel border rounded p-3 mb-3">
      <h2 class="h5 mb-2">Site record</h2>
      <p class="text-muted mb-3">
        This authority record has no site record. Add one to hold locality, site
        attributes and field recorders.
      </p>
      <a class="btn btn-outline-primary"
         href="<?php echo url_for('/site-record/add/'.(int) $resource->id); ?>">Add site record</a>
    </section>
    <?php

    return;
}

$locality = $record->locality;
$attributes = $service->attributesByTaxonomy((int) $record->id);
$recorders = $service->repository()->recorders((int) $record->id);

$taxonomyLabels = [
    'site_tradition' => 'Tradition',
    'site_type' => 'Site type',
    'site_damage' => 'Damage',
    'site_surface_content' => 'Surface contents',
    'site_excavation_potential' => 'Excavation potential',
    'site_mineral_content' => 'Mineral and rock contents',
    'site_deposit_depth' => 'Deposit depth',
    'site_deposit_content' => 'Deposit contents',
];
?>

<section class="ahg-site-record-panel mb-4">
  <h2 class="h5"><i class="fas fa-map-location-dot me-2"></i>Site record</h2>

  <table class="table table-sm table-bordered">
    <tbody>
      <?php if (!empty($record->site_number)) { ?>
        <tr>
          <th scope="row" class="w-25">Site number</th>
          <td><?php echo esc_specialchars($record->site_number); ?></td>
        </tr>
      <?php } ?>

      <?php if (!empty($record->date_visited)) { ?>
        <tr>
          <th scope="row">Date visited</th>
          <td><?php echo esc_specialchars($record->date_visited); ?></td>
        </tr>
      <?php } ?>

      <?php if (!empty($record->region_code)) { ?>
        <tr>
          <th scope="row">Region</th>
          <td>
            <?php echo esc_specialchars($service->label('site_region', $record->region_code)); ?>
            <?php if (!empty($record->sub_region_code)) { ?>
              - <?php echo esc_specialchars($service->label('site_sub_region', $record->sub_region_code)); ?>
            <?php } ?>
          </td>
        </tr>
      <?php } ?>

      <?php if ($locality['has_coordinates']) { ?>
        <tr>
          <th scope="row">Location</th>
          <td>
            <?php echo esc_specialchars(sprintf('%.6F, %.6F', $locality['latitude'], $locality['longitude'])); ?>
            <?php if ($locality['exact']) { ?>
              <span class="text-muted">(<?php echo esc_specialchars($locality['datum']); ?>)</span>
            <?php } else { ?>
              <span class="badge bg-secondary ms-1">approximate</span>
              <div class="small text-muted"><?php echo esc_specialchars($locality['note']); ?></div>
            <?php } ?>
          </td>
        </tr>
      <?php } ?>

      <?php if ($locality['exact'] && !empty($locality['map_sheet'])) { ?>
        <tr>
          <th scope="row">Map sheet</th>
          <td><?php echo esc_specialchars($locality['map_sheet']); ?></td>
        </tr>
      <?php } ?>

      <?php if ($locality['exact'] && null !== $locality['altitude_m']) { ?>
        <tr>
          <th scope="row">Altitude</th>
          <td><?php echo esc_specialchars($locality['altitude_m']); ?> m</td>
        </tr>
      <?php } ?>

      <?php foreach ($attributes as $taxonomy => $values) { ?>
        <tr>
          <th scope="row"><?php echo esc_specialchars($taxonomyLabels[$taxonomy] ?? $taxonomy); ?></th>
          <td>
            <?php foreach ($values as $v) { ?>
              <span class="badge bg-light text-dark border me-1"><?php echo esc_specialchars($v['label']); ?></span>
            <?php } ?>
          </td>
        </tr>
      <?php } ?>

      <?php if ($recorders) { ?>
        <tr>
          <th scope="row">Field recorders</th>
          <td>
            <?php $names = [];
            foreach ($recorders as $r) {
                $names[] = $r->name;
            } ?>
            <?php echo esc_specialchars(implode(', ', $names)); ?>
          </td>
        </tr>
      <?php } ?>

      <?php if (!empty($record->site_description)) { ?>
        <tr>
          <th scope="row">Site description</th>
          <td><?php echo esc_specialchars($record->site_description); ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>

  <?php
  // A way in to the record itself.
  //
  // Without these the panel is a dead end: it shows the site record but leaves a
  // reader who wants to correct something with no route to the edit screen
  // except knowing /site-record/<id>/edit and looking the id up by hand. Shown
  // only to those who could actually use them.
  $canEdit = false;

  try {
      $u = \sfContext::getInstance()->getUser();
      $canEdit = $u && $u->isAuthenticated()
          && ($u->hasCredential('editor') || $u->hasCredential('administrator'));
  } catch (\Throwable $e) {
      $canEdit = false;
  }
  ?>

  <?php if ($canEdit) { ?>
    <p class="mb-2">
      <a class="btn btn-sm btn-outline-primary" href="/index.php/site-record/<?php echo (int) $record->id; ?>/edit">
        <i class="fas fa-pen me-1"></i>Edit site record
      </a>
      <a class="btn btn-sm btn-outline-secondary" href="/index.php/site-record/<?php echo (int) $record->id; ?>">
        View full record
      </a>
      <a class="btn btn-sm btn-outline-secondary" href="/index.php/site-record">
        All site records
      </a>
    </p>
  <?php } ?>

  <?php if ($locality['exact']) { ?>
    <p class="small text-muted">
      <i class="fas fa-lock me-1"></i>Locality shown in full because you hold clearance.
      It is generalised for other readers.
    </p>
  <?php } ?>
</section>
