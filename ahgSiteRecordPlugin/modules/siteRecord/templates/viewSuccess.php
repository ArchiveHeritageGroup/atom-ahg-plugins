<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>
    Site record
    <?php if (!empty($record->site_number)) { ?>
      <span class="text-muted"><?php echo esc_specialchars($record->site_number); ?></span>
    <?php } ?>
  </h1>
<?php end_slot(); ?>

<?php
$locality = $record->locality;

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

<div class="ahg-site-record-view">

  <?php if ($actor) { ?>
    <p>
      Authority record:
      <?php if (!empty($actor->slug)) { ?>
        <a href="<?php echo url_for('/'.$actor->slug); ?>"><?php echo esc_specialchars($actor->name ?: $actor->slug); ?></a>
      <?php } else { ?>
        <?php echo esc_specialchars($actor->name ?? ''); ?>
      <?php } ?>
    </p>
  <?php } ?>

  <table class="table table-bordered">
    <tbody>
      <tr><th scope="row" class="w-25">Site number</th><td><?php echo esc_specialchars($record->site_number ?? ''); ?></td></tr>
      <tr><th scope="row">Date visited</th><td><?php echo esc_specialchars($record->date_visited ?? ''); ?></td></tr>
      <tr>
        <th scope="row">Region</th>
        <td>
          <?php echo esc_specialchars($service_->label('site_region', $record->region_code ?? null)); ?>
          <?php if (!empty($record->sub_region_code)) { ?>
            - <?php echo esc_specialchars($service_->label('site_sub_region', $record->sub_region_code)); ?>
          <?php } ?>
        </td>
      </tr>
      <tr><th scope="row">Aspect</th><td><?php echo esc_specialchars($service_->label('site_aspect', $record->aspect_code ?? null)); ?></td></tr>

      <tr>
        <th scope="row">Location</th>
        <td>
          <?php if (!$locality['has_coordinates']) { ?>
            <span class="text-muted">Not recorded</span>
          <?php } else { ?>
            <?php echo esc_specialchars(sprintf('%.6F, %.6F', $locality['latitude'], $locality['longitude'])); ?>
            <?php if ($locality['exact']) { ?>
              <span class="text-muted">(<?php echo esc_specialchars($locality['datum']); ?>)</span>
            <?php } else { ?>
              <span class="badge bg-secondary ms-1">approximate</span>
              <div class="small text-muted"><?php echo esc_specialchars($locality['note']); ?></div>
            <?php } ?>
          <?php } ?>
        </td>
      </tr>

      <?php if ($locality['exact']) { ?>
        <tr><th scope="row">Map sheet</th><td><?php echo esc_specialchars($locality['map_sheet'] ?? ''); ?></td></tr>
        <tr><th scope="row">Altitude</th><td><?php echo null === $locality['altitude_m'] ? '' : esc_specialchars($locality['altitude_m']).' m'; ?></td></tr>
        <?php if (!empty($locality['locality_original'])) { ?>
          <tr><th scope="row">Locality as recorded</th><td><?php echo esc_specialchars($locality['locality_original']); ?></td></tr>
        <?php } ?>
      <?php } ?>

      <tr>
        <th scope="row">Dimensions</th>
        <td>
          <?php $dims = array_filter([
              $record->height_m ?? null ? 'H '.$record->height_m.' m' : null,
              $record->width_m ?? null ? 'W '.$record->width_m.' m' : null,
              $record->depth_m ?? null ? 'D '.$record->depth_m.' m' : null,
          ]); ?>
          <?php echo $dims ? esc_specialchars(implode(' x ', $dims)) : '<span class="text-muted">-</span>'; ?>
        </td>
      </tr>

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
            <ul class="list-unstyled mb-0">
              <?php foreach ($recorders as $r) { ?>
                <li>
                  <?php echo esc_specialchars($r->name); ?>
                  <?php if (!empty($r->role_code)) { ?>
                    <span class="text-muted">- <?php echo esc_specialchars($service_->label('site_recorder_role', $r->role_code)); ?></span>
                  <?php } ?>
                </li>
              <?php } ?>
            </ul>
          </td>
        </tr>
      <?php } ?>

      <tr><th scope="row">Site description</th><td><?php echo nl2br(esc_specialchars($record->site_description ?? '')); ?></td></tr>
      <tr><th scope="row">Photograph numbers</th><td><?php echo esc_specialchars($record->photograph_numbers ?? ''); ?></td></tr>
      <tr><th scope="row">Contact</th><td><?php echo esc_specialchars(trim(($record->contact_name ?? '').' '.($record->contact_email ?? ''))); ?></td></tr>
      <tr><th scope="row">Notes</th><td><?php echo nl2br(esc_specialchars($record->notes ?? '')); ?></td></tr>
    </tbody>
  </table>

  <div class="d-flex gap-2">
    <a class="btn btn-primary" href="<?php echo url_for('@site_record_edit?id='.$record->id); ?>">Edit</a>

    <?php // Back to the authority record this site record belongs to.
          //
          // A site record is only ever reached from its authority record - the
          // panel on that page is the way in - so landing here with no way back
          // means retyping the site name into search to return to where you
          // started. "Back to site records" goes to the browse list, which is
          // not where the reader came from.
          //
          // Guarded on the slug because the actor is looked up by id and a
          // record whose actor has been deleted would otherwise render
          // href="/" and silently send the reader to the home page. ?>
    <?php if (!empty($actor->slug)) { ?>
      <a class="btn btn-outline-primary" href="<?php echo url_for('/'.$actor->slug); ?>">Back to record</a>
    <?php } ?>

    <a class="btn btn-outline-secondary" href="<?php echo url_for('@site_record_browse'); ?>">Back to site records</a>

    <?php if ($sf_user->hasCredential('administrator')) { ?>
      <?php // Delete is POST with a token - never a link, which is what made the
            // legacy application deletable by anything that followed a URL. ?>
      <form method="post" action="<?php echo url_for('@site_record_delete?id='.$record->id); ?>"
            data-ahg-confirm="Delete this site record? Field observations cannot be recollected without revisiting the site.">
      <?php // No CSRF field here on purpose.
       //
       // AhgController declares `public string $csrf_token` as a real property, so
       // assigning it never passes through sfComponent::__set() and never reaches
       // the template variable holder. $csrf_token is undefined here, so a
       // hand-written field renders EMPTY and fails validation.
       //
       // ahgCorePlugin injects the correct token into every same-origin POST form,
       // and skips any form already carrying the field - so emitting an empty one
       // suppressed the real one and every save answered
       // {"error":"CSRF token validation failed"}. Leave it to the injector. ?>
        <button type="submit" class="btn btn-outline-danger">Delete</button>
      </form>
    <?php } ?>
  </div>
</div>
