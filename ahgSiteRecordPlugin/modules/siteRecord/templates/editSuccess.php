<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo isset($record) && $record ? 'Edit site record' : 'New site record'; ?></h1>
<?php end_slot(); ?>

<?php
use AhgSiteRecordPlugin\Services\SiteRecordService;

$locality = isset($record) && $record ? $record->locality : null;
$val = static function ($field, $default = '') use ($record) {
    return isset($record) && $record && isset($record->{$field}) ? $record->{$field} : $default;
};

$selected = [];
foreach ($attributes as $taxonomy => $values) {
    foreach ($values as $v) {
        $selected[$taxonomy][] = $v['code'];
    }
}

$groups = [
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

<form method="post" class="ahg-site-record-form">
  <input type="hidden" name="_csrf_token" value="<?php echo esc_specialchars($csrf_token ?? ''); ?>">

  <?php if ($actor) { ?>
    <p class="text-muted">Authority record: <strong><?php echo esc_specialchars($actor->name ?? ''); ?></strong></p>
  <?php } ?>

  <fieldset class="mb-4">
    <legend class="h5">Identification</legend>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label" for="site_number">Site number</label>
        <input type="text" class="form-control" id="site_number" name="site_number"
               value="<?php echo esc_specialchars($val('site_number')); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="date_visited">Date visited</label>
        <input type="date" class="form-control" id="date_visited" name="date_visited"
               value="<?php echo esc_specialchars($val('date_visited')); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="photograph_numbers">Photograph numbers</label>
        <input type="text" class="form-control" id="photograph_numbers" name="photograph_numbers"
               value="<?php echo esc_specialchars($val('photograph_numbers')); ?>">
      </div>
    </div>
  </fieldset>

  <fieldset class="mb-4">
    <legend class="h5">Locality</legend>

    <div class="alert alert-warning">
      <i class="fas fa-triangle-exclamation me-1"></i>
      Precise location is the field that enables looting. Leave the record marked
      sensitive unless the site's location is already public knowledge - readers
      without clearance are shown a generalised area instead of a point.
    </div>

    <?php if (null !== $locality && !$locality['exact']) { ?>
      <div class="alert alert-danger">
        You do not hold locality clearance, so the coordinates below are generalised.
        Saving this form would overwrite the exact position with the coarsened one.
        Ask an administrator to edit locality on this record.
      </div>
    <?php } ?>

    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label" for="latitude">Latitude</label>
        <input type="number" step="0.0000001" class="form-control" id="latitude" name="latitude"
               value="<?php echo esc_specialchars(null !== $locality ? ($locality['latitude'] ?? '') : ''); ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="longitude">Longitude</label>
        <input type="number" step="0.0000001" class="form-control" id="longitude" name="longitude"
               value="<?php echo esc_specialchars(null !== $locality ? ($locality['longitude'] ?? '') : ''); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label" for="coordinate_datum">Datum</label>
        <input type="text" class="form-control" id="coordinate_datum" name="coordinate_datum"
               value="<?php echo esc_specialchars($val('coordinate_datum', 'WGS84')); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label" for="altitude_m">Altitude (m)</label>
        <input type="number" class="form-control" id="altitude_m" name="altitude_m"
               value="<?php echo esc_specialchars(null !== $locality ? ($locality['altitude_m'] ?? '') : ''); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label" for="map_sheet">Map sheet</label>
        <input type="text" class="form-control" id="map_sheet" name="map_sheet"
               value="<?php echo esc_specialchars(null !== $locality ? ($locality['map_sheet'] ?? '') : ''); ?>">
      </div>
      <div class="col-12">
        <label class="form-label" for="locality_original">Locality as originally recorded</label>
        <textarea class="form-control" id="locality_original" name="locality_original" rows="2"
        ><?php echo esc_specialchars(null !== $locality ? ($locality['locality_original'] ?? '') : ''); ?></textarea>
        <div class="form-text">Kept verbatim - map sheet strings, site codes, original notation.</div>
      </div>
      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="locality_sensitive" name="locality_sensitive" value="1"
            <?php echo (null === $locality || !isset($record->locality_sensitive) || $record->locality_sensitive) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="locality_sensitive">
            Locality is sensitive - generalise it for readers without clearance
          </label>
        </div>
      </div>
    </div>
  </fieldset>

  <fieldset class="mb-4">
    <legend class="h5">Setting</legend>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label" for="region_code">Region</label>
        <select class="form-select" id="region_code" name="region_code">
          <option value="">-</option>
          <?php foreach ($service_->choices('site_region') as $code => $label) { ?>
            <option value="<?php echo esc_specialchars($code); ?>"
              <?php echo $val('region_code') === $code ? 'selected' : ''; ?>><?php echo esc_specialchars($label); ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="aspect_code">Aspect</label>
        <select class="form-select" id="aspect_code" name="aspect_code">
          <option value="">-</option>
          <?php foreach ($service_->choices('site_aspect') as $code => $label) { ?>
            <option value="<?php echo esc_specialchars($code); ?>"
              <?php echo $val('aspect_code') === $code ? 'selected' : ''; ?>><?php echo esc_specialchars($label); ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Dimensions (m)</label>
        <div class="input-group">
          <input type="number" step="0.01" class="form-control" name="height_m" placeholder="H"
                 value="<?php echo esc_specialchars($val('height_m')); ?>" aria-label="Height in metres">
          <input type="number" step="0.01" class="form-control" name="width_m" placeholder="W"
                 value="<?php echo esc_specialchars($val('width_m')); ?>" aria-label="Width in metres">
          <input type="number" step="0.01" class="form-control" name="depth_m" placeholder="D"
                 value="<?php echo esc_specialchars($val('depth_m')); ?>" aria-label="Depth in metres">
        </div>
      </div>
    </div>
  </fieldset>

  <?php foreach ($groups as $taxonomy => $legend) { ?>
    <?php $choices = $service_->choices($taxonomy); ?>
    <?php if (!$choices) { continue; } ?>
    <fieldset class="mb-3">
      <legend class="h6"><?php echo esc_specialchars($legend); ?></legend>
      <div class="row">
        <?php foreach ($choices as $code => $label) { ?>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox"
                     id="<?php echo esc_specialchars($taxonomy.'_'.$code); ?>"
                     name="<?php echo esc_specialchars($taxonomy); ?>[]"
                     value="<?php echo esc_specialchars($code); ?>"
                <?php echo in_array($code, $selected[$taxonomy] ?? [], true) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="<?php echo esc_specialchars($taxonomy.'_'.$code); ?>">
                <?php echo esc_specialchars($label); ?>
              </label>
            </div>
          </div>
        <?php } ?>
      </div>
      <div class="mt-2">
        <input type="text" class="form-control form-control-sm"
               name="attribute_note[<?php echo esc_specialchars($taxonomy); ?>]"
               placeholder="Other / notes"
               value="<?php echo esc_specialchars($attributes[$taxonomy][0]['note'] ?? ''); ?>">
      </div>
    </fieldset>
  <?php } ?>

  <fieldset class="mb-4">
    <legend class="h5">Field recorders</legend>
    <?php $rows = $recorders ?: [null, null, null]; ?>
    <?php foreach ($rows as $i => $r) { ?>
      <div class="row g-2 mb-2">
        <div class="col-md-6">
          <input type="text" class="form-control" name="recorder[<?php echo $i; ?>][name]"
                 placeholder="Name" value="<?php echo esc_specialchars($r->name ?? ''); ?>"
                 aria-label="Recorder name">
        </div>
        <div class="col-md-4">
          <select class="form-select" name="recorder[<?php echo $i; ?>][role_code]" aria-label="Recorder role">
            <option value="">-</option>
            <?php foreach ($service_->choices('site_recorder_role') as $code => $label) { ?>
              <option value="<?php echo esc_specialchars($code); ?>"
                <?php echo ($r->role_code ?? null) === $code ? 'selected' : ''; ?>><?php echo esc_specialchars($label); ?></option>
            <?php } ?>
          </select>
        </div>
      </div>
    <?php } ?>
  </fieldset>

  <fieldset class="mb-4">
    <legend class="h5">Description</legend>
    <div class="mb-3">
      <label class="form-label" for="site_description">Site description</label>
      <textarea class="form-control" id="site_description" name="site_description" rows="4"
      ><?php echo esc_specialchars($val('site_description')); ?></textarea>
    </div>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" for="contact_name">Contact name</label>
        <input type="text" class="form-control" id="contact_name" name="contact_name"
               value="<?php echo esc_specialchars($val('contact_name')); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="contact_email">Contact email</label>
        <input type="email" class="form-control" id="contact_email" name="contact_email"
               value="<?php echo esc_specialchars($val('contact_email')); ?>">
      </div>
      <div class="col-12">
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-control" id="notes" name="notes" rows="3"
        ><?php echo esc_specialchars($val('notes')); ?></textarea>
      </div>
    </div>
  </fieldset>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Save</button>
    <a class="btn btn-outline-secondary" href="<?php echo url_for('@site_record_browse'); ?>">Cancel</a>
  </div>
</form>
