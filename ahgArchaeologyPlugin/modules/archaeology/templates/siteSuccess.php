<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo esc_specialchars($site->title ?: $site->site_number); ?></h1>
  <p class="text-muted mb-0">Site <?php echo esc_specialchars($site->site_number); ?></p>
<?php end_slot(); ?>

<div class="ahg-archaeology-site">

  <div class="d-flex gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_site_edit?id='.$site->id); ?>">Edit site</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_contexts?siteId='.$site->id); ?>">Stratigraphy</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_plan?siteId='.$site->id); ?>">Dig plan and map</a>
    <a class="btn btn-primary btn-sm" href="<?php echo url_for('@archaeology_context_add').'?siteId='.$site->id; ?>">Add context</a>
  </div>

  <table class="table table-sm">
    <tbody>
      <?php
      $fields = [
          'National site number' => $site->national_site_number,
          'Site type' => $site->site_type_name,
          'Period' => $site->period_name,
          'Region' => $site->region,
          'Locality' => $site->locality,
          'Elevation (m)' => $site->elevation_m,
          'Dating' => trim((string) $site->date_earliest.' - '.(string) $site->date_latest, ' -'),
          'Excavated' => $site->excavated ? 'Yes' : 'No',
          'Excavation years' => $site->excavation_years,
          'Excavator' => $site->excavator,
          'Institution' => $site->excavation_institution,
          'Permit number' => $site->permit_number,
      ];
      ?>
      <?php foreach ($fields as $label => $value) { ?>
        <?php if (null !== $value && '' !== $value) { ?>
          <tr>
            <th scope="row" class="w-25"><?php echo esc_specialchars($label); ?></th>
            <td><?php echo esc_specialchars((string) $value); ?></td>
          </tr>
        <?php } ?>
      <?php } ?>
    </tbody>
  </table>

  <?php if ($site->location_description) { ?>
    <h2 class="h6">Location</h2>
    <p><?php echo esc_specialchars($site->location_description); ?></p>
  <?php } ?>

  <h2 class="h5 mt-4">Contexts</h2>

  <?php if (!$contexts) { ?>
    <div class="alert alert-info">
      No contexts recorded for this site.
      <a href="<?php echo url_for('@archaeology_context_add').'?siteId='.$site->id; ?>">Add the first one</a>.
    </div>
  <?php } else { ?>
    <table class="table table-sm table-striped">
      <thead>
        <tr>
          <th scope="col">Context</th>
          <th scope="col">Type</th>
          <th scope="col">Phase</th>
          <th scope="col">Top (m)</th>
          <th scope="col">Bottom (m)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($contexts as $context) { ?>
          <tr>
            <td><a href="<?php echo url_for('@archaeology_context?id='.$context->id); ?>"><?php echo esc_specialchars($context->context_number); ?></a></td>
            <td><?php echo esc_specialchars($context->type_name ?? ''); ?></td>
            <td><?php echo esc_specialchars($context->phase_name ?? ''); ?></td>
            <td><?php echo null === $context->top_elevation_m ? '' : esc_specialchars((string) $context->top_elevation_m); ?></td>
            <td><?php echo null === $context->bottom_elevation_m ? '' : esc_specialchars((string) $context->bottom_elevation_m); ?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  <?php } ?>

</div>
