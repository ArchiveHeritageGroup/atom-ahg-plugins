<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>Site map</h1>
<?php end_slot(); ?>

<?php
// Drawn as inline SVG with presentation attributes only. No tiles, no external
// requests, no style="" attributes.
$GREEN = '#10373E';
$CUT = '#B4472B';
?>

<div class="ahg-archaeology-map">

  <div class="d-flex gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_sites'); ?>">Browse sites</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_index'); ?>">Archaeology</a>
  </div>

  <?php
  // State the coverage before showing the map. A map that plots only the placed
  // sites, without saying how many were left off, invites the reader to conclude
  // there is nothing in the empty areas.
  ?>
  <p class="text-muted">
    <?php echo (int) $coverage['placed']; ?> of <?php echo (int) $coverage['total']; ?>
    site<?php echo 1 === (int) $coverage['total'] ? '' : 's'; ?> have a recorded position.
    <?php if ($coverage['unplaced'] > 0) { ?>
      <strong><?php echo (int) $coverage['unplaced']; ?></strong> cannot be plotted and are not shown below.
    <?php } ?>
    <?php if ($coverage['without_accuracy'] > 0) { ?>
      <?php echo (int) $coverage['without_accuracy']; ?> have coordinates with no recorded accuracy,
      which means unrecorded rather than exact.
    <?php } ?>
  </p>

  <form method="get" action="<?php echo url_for('@archaeology_map'); ?>" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
      <label class="form-label" for="lat">Latitude</label>
      <input type="number" step="0.000001" class="form-control form-control-sm" id="lat" name="lat"
             value="<?php echo esc_specialchars($lat); ?>" placeholder="-25.805874">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="lng">Longitude</label>
      <input type="number" step="0.000001" class="form-control form-control-sm" id="lng" name="lng"
             value="<?php echo esc_specialchars($lng); ?>" placeholder="28.278294">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="radius">Within (km)</label>
      <input type="number" step="0.1" class="form-control form-control-sm" id="radius" name="radius"
             value="<?php echo esc_specialchars('' === $radius ? '50' : $radius); ?>">
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_map'); ?>">All sites</a>
    </div>
  </form>

  <?php if (null !== $near) { ?>
    <div class="alert alert-info py-2">
      <?php echo count($near); ?> site<?php echo 1 === count($near) ? '' : 's'; ?>
      within <?php echo esc_specialchars($radius); ?> km of
      <?php echo esc_specialchars($lat); ?>, <?php echo esc_specialchars($lng); ?>.
      Distances are great-circle, computed from the recorded coordinates.
    </div>
  <?php } ?>

  <?php if (!$sites) { ?>
    <div class="alert alert-warning">No sites to plot.</div>
  <?php } else { ?>

    <?php
    // Symfony hands templates their variables wrapped in an output escaper, so
    // $sites is a decorator rather than an array and array_map() on it is a fatal
    // on PHP 8 - which took this whole page down. Unwrap once, here; every field
    // read below still goes through esc_specialchars, so nothing is emitted raw.
    $siteRows = $sites instanceof sfOutputEscaperArrayDecorator
        ? $sites->getRawValue()
        : (array) $sites;

    // Fit the drawing to the sites rather than to a fixed extent, so a single
    // country's worth of sites does not end up as one dot on a continent.
    $lats = array_map(static fn ($s) => (float) $s->latitude, $siteRows);
    $lngs = array_map(static fn ($s) => (float) $s->longitude, $siteRows);
    $minLat = min($lats); $maxLat = max($lats);
    $minLng = min($lngs); $maxLng = max($lngs);

    // A single site, or several at the same place, would give a zero span.
    $padLat = max(0.5, ($maxLat - $minLat) * 0.25);
    $padLng = max(0.5, ($maxLng - $minLng) * 0.25);
    $minLat -= $padLat; $maxLat += $padLat;
    $minLng -= $padLng; $maxLng += $padLng;

    $W = 900; $H = 460;
    $xFor = static fn ($lng) => ($lng - $minLng) / max(0.0001, $maxLng - $minLng) * $W;
    $yFor = static fn ($lat) => ($maxLat - $lat) / max(0.0001, $maxLat - $minLat) * $H;
    ?>

    <div class="border rounded p-2 mb-3 overflow-auto">
      <svg viewBox="0 0 <?php echo $W; ?> <?php echo $H; ?>" width="100%" height="<?php echo $H; ?>"
           role="img" aria-label="Recorded site positions">
        <rect x="0" y="0" width="<?php echo $W; ?>" height="<?php echo $H; ?>" fill="#f5f8f8" stroke="#cfdad9"></rect>

        <?php
        // Graticule at a step that suits the extent.
        $spanLng = $maxLng - $minLng;
        $step = $spanLng > 20 ? 10 : ($spanLng > 5 ? 2 : ($spanLng > 1 ? 0.5 : 0.1));
        for ($g = ceil($minLng / $step) * $step; $g <= $maxLng; $g += $step) { ?>
          <line x1="<?php echo $xFor($g); ?>" y1="0" x2="<?php echo $xFor($g); ?>" y2="<?php echo $H; ?>" stroke="#e1e9e8"></line>
          <text x="<?php echo $xFor($g) + 3; ?>" y="<?php echo $H - 4; ?>" font-size="9" fill="#8a9a99"><?php echo round($g, 2); ?>&deg;</text>
        <?php }
        for ($g = ceil($minLat / $step) * $step; $g <= $maxLat; $g += $step) { ?>
          <line x1="0" y1="<?php echo $yFor($g); ?>" x2="<?php echo $W; ?>" y2="<?php echo $yFor($g); ?>" stroke="#e1e9e8"></line>
          <text x="4" y="<?php echo $yFor($g) - 3; ?>" font-size="9" fill="#8a9a99"><?php echo round($g, 2); ?>&deg;</text>
        <?php } ?>

        <?php foreach ($siteRows as $s) {
            $x = $xFor((float) $s->longitude);
            $y = $yFor((float) $s->latitude);
            // The accuracy radius is drawn to scale where one is recorded, so a
            // 5 km estimate cannot be read as a survey point.
            $r = null;
            if (null !== $s->spatial_accuracy_m && $s->spatial_accuracy_m > 0) {
                $kmPerDeg = 111.0;
                $r = ((float) $s->spatial_accuracy_m / 1000.0) / $kmPerDeg
                    / max(0.0001, $maxLat - $minLat) * $H;
            }
            ?>
          <?php if (null !== $r && $r > 2) { ?>
            <circle cx="<?php echo $x; ?>" cy="<?php echo $y; ?>" r="<?php echo min(120, $r); ?>"
                    fill="<?php echo $CUT; ?>" fill-opacity="0.12" stroke="<?php echo $CUT; ?>" stroke-opacity="0.35"></circle>
          <?php } ?>
          <circle cx="<?php echo $x; ?>" cy="<?php echo $y; ?>" r="5"
                  fill="<?php echo $s->excavated ? $CUT : $GREEN; ?>"></circle>
          <text x="<?php echo $x + 8; ?>" y="<?php echo $y - 6; ?>" font-size="10" fill="<?php echo $GREEN; ?>">
            <?php echo esc_specialchars($s->site_number); ?>
          </text>
        <?php } ?>
      </svg>
      <p class="text-muted small mb-0">
        Positions are drawn from recorded coordinates. Filled circles are excavated sites.
        A shaded halo, where present, is the recorded positional accuracy drawn to scale.
        No map tiles are loaded.
      </p>
    </div>

    <table class="table table-sm table-striped">
      <thead>
        <tr>
          <th scope="col">Site</th>
          <th scope="col">Name</th>
          <th scope="col">Region</th>
          <th scope="col">Latitude</th>
          <th scope="col">Longitude</th>
          <th scope="col">Accuracy</th>
          <?php if (null !== $near) { ?><th scope="col">Distance</th><?php } ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($siteRows as $s) { ?>
          <tr>
            <td><a href="<?php echo url_for('@archaeology_site?id='.$s->id); ?>"><?php echo esc_specialchars($s->site_number); ?></a></td>
            <td><?php echo esc_specialchars($s->title ?? ''); ?></td>
            <td><?php echo esc_specialchars($s->region ?? ''); ?></td>
            <td><?php echo esc_specialchars(number_format((float) $s->latitude, 6)); ?></td>
            <td><?php echo esc_specialchars(number_format((float) $s->longitude, 6)); ?></td>
            <td>
              <?php if (null === $s->spatial_accuracy_m) { ?>
                <span class="text-warning">unrecorded</span>
              <?php } else { ?>
                &plusmn; <?php echo (int) $s->spatial_accuracy_m; ?> m
              <?php } ?>
            </td>
            <?php if (null !== $near) { ?>
              <td><?php echo esc_specialchars(number_format((float) $s->distance_km, 1)); ?> km</td>
            <?php } ?>
          </tr>
        <?php } ?>
      </tbody>
    </table>

  <?php } ?>

  <h2 class="h6 mt-4">On search and spatial</h2>
  <p class="text-muted small">
    Sites, contexts and finds are indexed with the rest of the catalogue and are found by
    ordinary search, because each one extends a description. Spatial queries run against the
    database rather than the search index: AtoM's description mapping carries no geographic
    field, so proximity cannot be asked of the index without changing base AtoM's search
    configuration.
  </p>

</div>
