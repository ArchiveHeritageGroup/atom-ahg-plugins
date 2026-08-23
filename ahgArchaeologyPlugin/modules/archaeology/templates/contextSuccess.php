<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>Context <?php echo esc_specialchars($service_->contextLabel($ctx)); ?></h1>
  <p class="text-muted mb-0">Site <?php echo esc_specialchars($ctx->site_number); ?></p>
<?php end_slot(); ?>

<div class="ahg-archaeology-context">

  <div class="d-flex gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_contexts?siteId='.$ctx->site_id); ?>">Back to stratigraphy</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_context_edit?id='.$ctx->id); ?>">Edit context</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_context_pdf?id='.$ctx->id); ?>">PDF sheet</a>
  </div>

  <table class="table table-sm">
    <tbody>
      <?php
      $fields = [
          'Type' => $ctx->type_name,
          'Phase' => $ctx->phase_name,
          'Top elevation (m)' => $ctx->top_elevation_m,
          'Bottom elevation (m)' => $ctx->bottom_elevation_m,
          'Trench / square / spit' => $ctx->excavation_reference,
          'Excavator' => $ctx->excavator,
          'Excavation date' => $ctx->excavation_date,
          'Dating' => trim((string) $ctx->date_earliest.' - '.(string) $ctx->date_latest, ' -'),
          'Dating note' => $ctx->dating_note,
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

  <?php if ($ctx->description) { ?>
    <h2 class="h6">Description</h2>
    <p><?php echo nl2br(esc_specialchars($ctx->description)); ?></p>
  <?php } ?>

  <?php if ($ctx->interpretation) { ?>
    <h2 class="h6">Interpretation</h2>
    <p><?php echo nl2br(esc_specialchars($ctx->interpretation)); ?></p>
  <?php } ?>

  <h2 class="h6">Stratigraphic relationships</h2>

  <?php if ($sf_user->hasFlash('error')) { ?>
    <div class="alert alert-warning"><?php echo esc_specialchars($sf_user->getFlash('error')); ?></div>
  <?php } ?>

  <?php if (!$relationships) { ?>
    <p class="text-muted">None recorded.</p>
  <?php } else { ?>
    <table class="table table-sm">
      <tbody>
        <?php foreach ($relationships as $rel) { ?>
          <tr>
            <td class="w-25">
              <?php echo esc_specialchars($relTypes[$rel->relationship_type]['label'] ?? $rel->relationship_type); ?>
            </td>
            <td>
              <a href="<?php echo url_for('@archaeology_context?id='.$rel->related_id); ?>">
                <?php echo esc_specialchars($rel->related_number); ?>
              </a>
              <?php if ($rel->note) { ?>
                <span class="text-muted small"><?php echo esc_specialchars($rel->note); ?></span>
              <?php } ?>
            </td>
            <td class="text-end">
              <form method="post" class="d-inline"
                    action="<?php echo url_for('@archaeology_relationship_delete?id='.$ctx->id.'&relId='.$rel->id); ?>">
                <?php // No CSRF field - ahgCorePlugin injects it. See contextEditSuccess.php. ?>
                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
              </form>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
    <p class="text-muted small">
      Removing a relationship removes its mirror as well.
    </p>
  <?php } ?>

  <?php if (!$relatedChoices) { ?>
    <p class="text-muted small">
      This site has no other contexts yet, so there is nothing to relate this one to.
    </p>
  <?php } else { ?>
    <form method="post" action="<?php echo url_for('@archaeology_relationship_add?id='.$ctx->id); ?>"
          class="row g-2 align-items-end mb-4">

      <?php // No CSRF field - ahgCorePlugin injects it. See contextEditSuccess.php. ?>

      <div class="col-md-3">
        <label class="form-label" for="relationship_type">This context</label>
        <select class="form-select" id="relationship_type" name="relationship_type">
          <?php foreach ($relTypes as $key => $meta) { ?>
            <option value="<?php echo esc_specialchars($key); ?>"><?php echo esc_specialchars($meta['label']); ?></option>
          <?php } ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="related_context_id">Context</label>
        <select class="form-select" id="related_context_id" name="related_context_id">
          <?php foreach ($relatedChoices as $choice) { ?>
            <option value="<?php echo (int) $choice->id; ?>"><?php echo esc_specialchars($choice->context_number); ?></option>
          <?php } ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="note">Note</label>
        <input type="text" class="form-control" id="note" name="note">
      </div>

      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Add</button>
      </div>

      <div class="col-12">
        <div class="form-text">
          The reciprocal is recorded automatically, and a relationship that would make the
          sequence impossible is refused.
        </div>
      </div>

    </form>
  <?php } ?>

  <h2 class="h6">Finds</h2>

  <p>
    <a class="btn btn-sm btn-outline-secondary"
       href="<?php echo url_for('@archaeology_object_add').'?siteId='.$ctx->site_id; ?>">Add a find</a>
    <a class="btn btn-sm btn-outline-secondary"
       href="<?php echo url_for('@archaeology_objects').'?context_id='.$ctx->id; ?>">All finds from this context</a>
  </p>

  <?php if (!$finds) { ?>
    <p class="text-muted">No finds are linked to this context.</p>
  <?php } else { ?>
    <table class="table table-sm table-striped">
      <thead>
        <tr>
          <th scope="col">Accession number</th>
          <th scope="col">Title</th>
          <th scope="col">Count</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($finds as $find) { ?>
          <tr>
            <td><a href="<?php echo url_for('@archaeology_object?id='.$find->id); ?>"><?php echo esc_specialchars($find->accession_number); ?></a></td>
            <td><?php echo esc_specialchars($find->title ?? ''); ?></td>
            <td><?php echo number_format((int) $find->item_count); ?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  <?php } ?>

  <?php if ($ctx->information_object_id) { ?>
    <p class="text-muted small">
      Plan and section drawings for this context are held on its description.
    </p>
  <?php } ?>

</div>
