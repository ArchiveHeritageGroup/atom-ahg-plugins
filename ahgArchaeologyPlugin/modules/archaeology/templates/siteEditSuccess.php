<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo $site ? 'Edit site '.esc_specialchars($site->site_number) : 'Add site'; ?></h1>
<?php end_slot(); ?>

<div class="ahg-archaeology-site-edit">

  <form method="post" action="<?php echo $site
      ? url_for('@archaeology_site_edit?id='.$site->id)
      : url_for('@archaeology_site_add'); ?>">

    <?php // No CSRF field - ahgCorePlugin injects it. See contextEditSuccess.php. ?>

    <div class="row g-3">

      <div class="col-md-4">
        <label class="form-label" for="site_number">Site number</label>
        <input type="text" class="form-control" id="site_number" name="site_number" required
               value="<?php echo esc_specialchars($site->site_number ?? ''); ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label" for="national_site_number">National site number</label>
        <input type="text" class="form-control" id="national_site_number" name="national_site_number"
               value="<?php echo esc_specialchars($site->national_site_number ?? ''); ?>">
        <div class="form-text">SAHRA / ASAPA identifier, where one exists.</div>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="permit_number">Permit number</label>
        <input type="text" class="form-control" id="permit_number" name="permit_number"
               value="<?php echo esc_specialchars($site->permit_number ?? ''); ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label" for="site_type_id">Site type</label>
        <select class="form-select" id="site_type_id" name="site_type_id">
          <option value="">Not recorded</option>
          <?php foreach ($vocabularies['site_type'] as $term) { ?>
            <option value="<?php echo (int) $term->id; ?>"
              <?php echo ((int) ($site->site_type_id ?? 0)) === (int) $term->id ? 'selected' : ''; ?>>
              <?php echo esc_specialchars($term->name); ?>
            </option>
          <?php } ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label" for="period_id">Period</label>
        <select class="form-select" id="period_id" name="period_id">
          <option value="">Not recorded</option>
          <?php foreach ($vocabularies['period'] as $term) { ?>
            <option value="<?php echo (int) $term->id; ?>"
              <?php echo ((int) ($site->period_id ?? 0)) === (int) $term->id ? 'selected' : ''; ?>>
              <?php echo esc_specialchars($term->name); ?>
            </option>
          <?php } ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="region">Region</label>
        <input type="text" class="form-control" id="region" name="region"
               value="<?php echo esc_specialchars($site->region ?? ''); ?>">
      </div>

      <div class="col-md-8">
        <label class="form-label" for="locality">Locality</label>
        <input type="text" class="form-control" id="locality" name="locality"
               value="<?php echo esc_specialchars($site->locality ?? ''); ?>">
      </div>

      <div class="col-12">
        <label class="form-label" for="location_description">Location description</label>
        <textarea class="form-control" id="location_description" name="location_description" rows="3"><?php echo esc_specialchars($site->location_description ?? ''); ?></textarea>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="latitude">Latitude</label>
        <input type="number" step="0.00000001" class="form-control" id="latitude" name="latitude"
               value="<?php echo esc_specialchars((string) ($site->latitude ?? '')); ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label" for="longitude">Longitude</label>
        <input type="number" step="0.00000001" class="form-control" id="longitude" name="longitude"
               value="<?php echo esc_specialchars((string) ($site->longitude ?? '')); ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label" for="elevation_m">Elevation (m)</label>
        <input type="number" class="form-control" id="elevation_m" name="elevation_m"
               value="<?php echo esc_specialchars((string) ($site->elevation_m ?? '')); ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label" for="spatial_accuracy_m">Positional accuracy (m)</label>
        <input type="number" class="form-control" id="spatial_accuracy_m" name="spatial_accuracy_m"
               value="<?php echo esc_specialchars((string) ($site->spatial_accuracy_m ?? '')); ?>">
        <div class="form-text">Blank means unrecorded, not exact.</div>
      </div>

      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="1" id="excavated" name="excavated"
            <?php echo !empty($site->excavated) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="excavated">Excavated</label>
        </div>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="excavation_years">Excavation years</label>
        <input type="text" class="form-control" id="excavation_years" name="excavation_years"
               value="<?php echo esc_specialchars($site->excavation_years ?? ''); ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label" for="excavator">Excavator</label>
        <input type="text" class="form-control" id="excavator" name="excavator"
               value="<?php echo esc_specialchars($site->excavator ?? ''); ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label" for="excavation_institution">Institution</label>
        <input type="text" class="form-control" id="excavation_institution" name="excavation_institution"
               value="<?php echo esc_specialchars($site->excavation_institution ?? ''); ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label" for="date_earliest">Earliest date</label>
        <input type="text" class="form-control" id="date_earliest" name="date_earliest"
               placeholder="c. 1200 AD"
               value="<?php echo esc_specialchars($site->date_earliest ?? ''); ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label" for="date_latest">Latest date</label>
        <input type="text" class="form-control" id="date_latest" name="date_latest"
               value="<?php echo esc_specialchars($site->date_latest ?? ''); ?>">
      </div>

      <div class="col-12">
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo esc_specialchars($site->notes ?? ''); ?></textarea>
      </div>

    </div>

    <div class="mt-3 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Save site</button>
      <a class="btn btn-outline-secondary"
         href="<?php echo $site ? url_for('@archaeology_site?id='.$site->id) : url_for('@archaeology_sites'); ?>">Cancel</a>
    </div>

  </form>

</div>
