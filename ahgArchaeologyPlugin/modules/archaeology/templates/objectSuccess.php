<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo esc_specialchars($find->title ?: $find->accession_number); ?></h1>
  <p class="text-muted mb-0">Accession <?php echo esc_specialchars($find->accession_number); ?></p>
<?php end_slot(); ?>

<div class="ahg-archaeology-find">

  <div class="d-flex gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_objects'); ?>">Back to finds</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_object_edit?id='.$find->id); ?>">Edit find</a>
    <?php if ($find->context_id) { ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_context?id='.$find->context_id); ?>">Context <?php echo esc_specialchars($find->context_number); ?></a>
    <?php } ?>
    <?php if ($find->site_pk) { ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_site?id='.$find->site_pk); ?>">Site <?php echo esc_specialchars($find->site_number); ?></a>
    <?php } ?>
  </div>

  <?php if (!$find->context_id) { ?>
    <div class="alert alert-warning">
      This find is not linked to a context, so it cannot be counted as part of any
      assemblage. Its recorded stratigraphic reference is
      <?php echo $find->context_reference ? '"'.esc_specialchars($find->context_reference).'"' : 'blank'; ?>.
    </div>
  <?php } ?>

  <table class="table table-sm">
    <tbody>
      <?php
      $fields = [
          'Site' => $find->site_number,
          'Context' => $find->context_number,
          'Object type' => $find->object_type_name,
          'Material' => $find->material_name,
          'Period' => $find->period_name,
          'Count' => $find->item_count,
          'Weight (g)' => $find->weight_g,
          'Length (mm)' => $find->length_mm,
          'Width (mm)' => $find->width_mm,
          'Thickness (mm)' => $find->thickness_mm,
          'Diameter (mm)' => $find->diameter_mm,
          'Dimensions note' => $find->dimensions_note,
          'Trench / spit' => $find->excavation_reference,
          'Find date' => $find->find_date,
          'Find location' => $find->find_location,
          'Finder' => $find->finder,
          'Dating' => trim((string) $find->date_earliest.' - '.(string) $find->date_latest, ' -'),
          'Storage location' => $find->storage_location,
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

  <?php foreach (['Provenance' => $find->provenance, 'Notes' => $find->notes] as $heading => $text) { ?>
    <?php if ($text) { ?>
      <h2 class="h6"><?php echo esc_specialchars($heading); ?></h2>
      <p><?php echo nl2br(esc_specialchars($text)); ?></p>
    <?php } ?>
  <?php } ?>

</div>
