<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo $find ? 'Edit find '.esc_specialchars($find->accession_number) : 'Add find'; ?></h1>
<?php end_slot(); ?>

<?php
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? ' '.preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
?>

<div class="ahg-archaeology-find-edit">

  <?php if ($sf_user->hasFlash('error')) { ?>
    <div class="alert alert-warning"><?php echo esc_specialchars($sf_user->getFlash('error')); ?></div>
  <?php } ?>

  <form method="post" action="<?php echo $find
      ? url_for('@archaeology_object_edit?id='.$find->id)
      : url_for('@archaeology_object_add'); ?>">

    <?php // No CSRF field - ahgCorePlugin injects it. See contextEditSuccess.php. ?>

    <div class="row g-3">

      <div class="col-md-4">
        <label class="form-label" for="accession_number">Accession number</label>
        <input type="text" class="form-control" id="accession_number" name="accession_number" required
               value="<?php echo esc_specialchars($find->accession_number ?? ''); ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label" for="site_id">Site</label>
        <select class="form-select" id="site_id" name="site_id"
                data-contexts-url="<?php echo esc_specialchars(url_for('@archaeology_contexts_json?siteId=')); ?>">
          <option value="">Not recorded</option>
          <?php foreach ($siteChoices as $s) { ?>
            <option value="<?php echo (int) $s->id; ?>"<?php echo (int) $selectedSiteId === (int) $s->id ? ' selected' : ''; ?>>
              <?php echo esc_specialchars($s->site_number); ?>
            </option>
          <?php } ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="context_id">Context</label>
        <select class="form-select" id="context_id" name="context_id">
          <option value="">Not recorded</option>
          <?php foreach ($contextChoices as $c) { ?>
            <option value="<?php echo (int) $c->id; ?>"<?php echo (int) ($find->context_id ?? 0) === (int) $c->id ? ' selected' : ''; ?>>
              <?php echo esc_specialchars($c->context_number); ?>
            </option>
          <?php } ?>
        </select>
        <div class="form-text">
          Linking a find to its context is what makes it part of an assemblage.
          A context belongs to one site, so choosing one sets the site.
        </div>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="object_type_id">Object type</label>
        <select class="form-select" id="object_type_id" name="object_type_id">
          <option value="">Not recorded</option>
          <?php foreach ($vocabularies['object_type'] as $term) { ?>
            <option value="<?php echo (int) $term->id; ?>"<?php echo (int) ($find->object_type_id ?? 0) === (int) $term->id ? ' selected' : ''; ?>>
              <?php echo esc_specialchars($term->name); ?>
            </option>
          <?php } ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="material_id">Material</label>
        <select class="form-select" id="material_id" name="material_id">
          <option value="">Not recorded</option>
          <?php foreach ($vocabularies['material'] as $term) { ?>
            <option value="<?php echo (int) $term->id; ?>"<?php echo (int) ($find->material_id ?? 0) === (int) $term->id ? ' selected' : ''; ?>>
              <?php echo esc_specialchars($term->name); ?>
            </option>
          <?php } ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="period_id">Period</label>
        <select class="form-select" id="period_id" name="period_id">
          <option value="">Not recorded</option>
          <?php foreach ($vocabularies['period'] as $term) { ?>
            <option value="<?php echo (int) $term->id; ?>"<?php echo (int) ($find->period_id ?? 0) === (int) $term->id ? ' selected' : ''; ?>>
              <?php echo esc_specialchars($term->name); ?>
            </option>
          <?php } ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label" for="item_count">Count</label>
        <input type="number" min="1" class="form-control" id="item_count" name="item_count"
               value="<?php echo esc_specialchars((string) ($find->item_count ?? 1)); ?>">
        <div class="form-text">A bulk assemblage is one record with a count.</div>
      </div>

      <div class="col-md-2">
        <label class="form-label" for="weight_g">Weight (g)</label>
        <input type="number" step="0.001" class="form-control" id="weight_g" name="weight_g"
               value="<?php echo esc_specialchars((string) ($find->weight_g ?? '')); ?>">
      </div>

      <?php foreach ([
          'length_mm' => 'Length (mm)',
          'width_mm' => 'Width (mm)',
          'thickness_mm' => 'Thickness (mm)',
          'diameter_mm' => 'Diameter (mm)',
      ] as $field => $label) { ?>
        <div class="col-md-2">
          <label class="form-label" for="<?php echo $field; ?>"><?php echo esc_specialchars($label); ?></label>
          <input type="number" step="0.01" class="form-control" id="<?php echo $field; ?>" name="<?php echo $field; ?>"
                 value="<?php echo esc_specialchars((string) ($find->{$field} ?? '')); ?>">
        </div>
      <?php } ?>

      <div class="col-md-4">
        <label class="form-label" for="excavation_reference">Trench / square / spit</label>
        <input type="text" class="form-control" id="excavation_reference" name="excavation_reference"
               value="<?php echo esc_specialchars($find->excavation_reference ?? ''); ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label" for="find_date">Find date</label>
        <input type="date" class="form-control" id="find_date" name="find_date"
               value="<?php echo esc_specialchars((string) ($find->find_date ?? '')); ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label" for="finder">Finder</label>
        <input type="text" class="form-control" id="finder" name="finder"
               value="<?php echo esc_specialchars($find->finder ?? ''); ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label" for="date_earliest">Earliest date</label>
        <input type="text" class="form-control" id="date_earliest" name="date_earliest"
               placeholder="c. 1600 AD"
               value="<?php echo esc_specialchars($find->date_earliest ?? ''); ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label" for="date_latest">Latest date</label>
        <input type="text" class="form-control" id="date_latest" name="date_latest"
               value="<?php echo esc_specialchars($find->date_latest ?? ''); ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label" for="storage_location">Storage location</label>
        <input type="text" class="form-control" id="storage_location" name="storage_location"
               value="<?php echo esc_specialchars($find->storage_location ?? ''); ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label" for="dimensions_note">Dimensions note</label>
        <input type="text" class="form-control" id="dimensions_note" name="dimensions_note"
               value="<?php echo esc_specialchars($find->dimensions_note ?? ''); ?>">
      </div>

      <div class="col-12">
        <label class="form-label" for="provenance">Provenance</label>
        <textarea class="form-control" id="provenance" name="provenance" rows="3"><?php echo esc_specialchars($find->provenance ?? ''); ?></textarea>
      </div>

      <div class="col-12">
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo esc_specialchars($find->notes ?? ''); ?></textarea>
      </div>

    </div>

    <div class="mt-3 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Save find</button>
      <a class="btn btn-outline-secondary"
         href="<?php echo $find ? url_for('@archaeology_object?id='.$find->id) : url_for('@archaeology_objects'); ?>">Cancel</a>
    </div>

  </form>

  <script<?php echo $nonceAttr; ?>>
  // Refresh the context list when the site changes. Without this the picker keeps
  // whichever site's contexts were rendered at page load, so changing the site
  // leaves a list that looks right and belongs to the wrong dig - and the save
  // would then be refused for a mismatch the user could not see.
  //
  // Progressive enhancement: with scripting off the form still saves, because the
  // server re-renders the correct contexts for whatever site is selected.
  (function () {
      var site = document.getElementById('site_id');
      var context = document.getElementById('context_id');

      if (!site || !context) {
          return;
      }

      site.addEventListener('change', function () {
          var id = site.value;

          if (!id) {
              context.innerHTML = '<option value="">Not recorded</option>';
              return;
          }

          fetch(site.getAttribute('data-contexts-url') + encodeURIComponent(id), {
              credentials: 'same-origin'
          })
              .then(function (r) { return r.ok ? r.json() : []; })
              .then(function (rows) {
                  var previous = context.value;
                  context.innerHTML = '<option value="">Not recorded</option>';

                  rows.forEach(function (row) {
                      var opt = document.createElement('option');
                      opt.value = row.id;
                      opt.textContent = row.number;

                      if (String(row.id) === String(previous)) {
                          opt.selected = true;
                      }

                      context.appendChild(opt);
                  });
              })
              .catch(function () {
                  // Leave the existing list rather than emptying it: a failed
                  // fetch should not silently discard a valid selection.
              });
      });
  })();
  </script>

</div>
