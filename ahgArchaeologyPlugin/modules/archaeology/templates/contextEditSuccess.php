<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo $ctx ? 'Edit context '.esc_specialchars($ctx->context_number) : 'Add context'; ?></h1>
  <p class="text-muted mb-0">Site <?php echo esc_specialchars($site->site_number); ?></p>
<?php end_slot(); ?>

<div class="ahg-archaeology-context-edit">

  <form method="post" action="<?php echo $ctx
      ? url_for('@archaeology_context_edit?id='.$ctx->id)
      : url_for('@archaeology_context_add').'?siteId='.$siteId; ?>">

    <?php // No CSRF field here on purpose.
     //
     // AhgController declares `public string $csrf_token` as a real property, so
     // it never reaches the template variable holder - $csrf_token is undefined
     // here, and a hidden field built from it posts empty. ahgCorePlugin injects
     // the correct token into every same-origin POST form; a hand-written field
     // overrides that injection and the save fails CSRF validation. ?>

    <div class="row g-3">

      <div class="col-md-3">
        <label class="form-label" for="context_number">Context number</label>
        <input type="text" class="form-control" id="context_number" name="context_number" required
               value="<?php echo esc_specialchars($ctx->context_number ?? ''); ?>">
        <div class="form-text">Unique within this site. Need not be numeric.</div>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="context_type_id">Type</label>
        <select class="form-select" id="context_type_id" name="context_type_id">
          <option value="">Not recorded</option>
          <?php foreach ($vocabularies['context_type'] as $term) { ?>
            <option value="<?php echo (int) $term->id; ?>"
              <?php echo ((int) ($ctx->context_type_id ?? 0)) === (int) $term->id ? 'selected' : ''; ?>>
              <?php echo esc_specialchars($term->name); ?>
            </option>
          <?php } ?>
        </select>
        <?php if (!$vocabularies['context_type']) { ?>
          <div class="form-text text-warning">
            No context types are defined. Run the vocabulary seeder to create them.
          </div>
        <?php } ?>
      </div>

      <div class="col-md-5">
        <label class="form-label" for="phase_id">Phase</label>
        <select class="form-select" id="phase_id" name="phase_id">
          <option value="">Not recorded</option>
          <?php foreach ($vocabularies['phase'] as $term) { ?>
            <option value="<?php echo (int) $term->id; ?>"
              <?php echo ((int) ($ctx->phase_id ?? 0)) === (int) $term->id ? 'selected' : ''; ?>>
              <?php echo esc_specialchars($term->name); ?>
            </option>
          <?php } ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="top_elevation_m">Top elevation (m)</label>
        <input type="number" step="0.001" class="form-control" id="top_elevation_m" name="top_elevation_m"
               value="<?php echo esc_specialchars((string) ($ctx->top_elevation_m ?? '')); ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label" for="bottom_elevation_m">Bottom elevation (m)</label>
        <input type="number" step="0.001" class="form-control" id="bottom_elevation_m" name="bottom_elevation_m"
               value="<?php echo esc_specialchars((string) ($ctx->bottom_elevation_m ?? '')); ?>">
        <div class="form-text">Leave blank when unmeasured. Blank is not zero.</div>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="excavation_reference">Trench / square / spit</label>
        <input type="text" class="form-control" id="excavation_reference" name="excavation_reference"
               value="<?php echo esc_specialchars($ctx->excavation_reference ?? ''); ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label" for="excavation_date">Excavation date</label>
        <input type="date" class="form-control" id="excavation_date" name="excavation_date"
               value="<?php echo esc_specialchars((string) ($ctx->excavation_date ?? '')); ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label" for="excavator">Excavator</label>
        <input type="text" class="form-control" id="excavator" name="excavator"
               value="<?php echo esc_specialchars($ctx->excavator ?? ''); ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label" for="date_earliest">Earliest date</label>
        <input type="text" class="form-control" id="date_earliest" name="date_earliest"
               placeholder="c. 1400 AD"
               value="<?php echo esc_specialchars($ctx->date_earliest ?? ''); ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label" for="date_latest">Latest date</label>
        <input type="text" class="form-control" id="date_latest" name="date_latest"
               placeholder="2500 BP"
               value="<?php echo esc_specialchars($ctx->date_latest ?? ''); ?>">
      </div>

      <div class="col-12">
        <label class="form-label" for="dating_note">Dating note</label>
        <textarea class="form-control" id="dating_note" name="dating_note" rows="2"><?php echo esc_specialchars($ctx->dating_note ?? ''); ?></textarea>
      </div>

      <div class="col-12">
        <label class="form-label" for="description">Description</label>
        <textarea class="form-control" id="description" name="description" rows="4"><?php echo esc_specialchars($ctx->description ?? ''); ?></textarea>
      </div>

      <div class="col-12">
        <label class="form-label" for="interpretation">Interpretation</label>
        <textarea class="form-control" id="interpretation" name="interpretation" rows="4"><?php echo esc_specialchars($ctx->interpretation ?? ''); ?></textarea>
      </div>

    </div>

    <div class="mt-3 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Save context</button>
      <a class="btn btn-outline-secondary"
         href="<?php echo $ctx
             ? url_for('@archaeology_context?id='.$ctx->id)
             : url_for('@archaeology_contexts?siteId='.$siteId); ?>">Cancel</a>
    </div>

  </form>

</div>
