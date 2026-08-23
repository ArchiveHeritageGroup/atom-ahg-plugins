<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>Dig plan</h1>
  <p class="text-muted mb-0"><?php echo esc_specialchars($site->title ?: $site->site_number); ?></p>
<?php end_slot(); ?>

<?php
// Everything below is drawn with inline SVG using presentation attributes
// (fill, stroke, x, y). Deliberately no style="" attributes anywhere: a CSP nonce
// covers <style> and <script> ELEMENTS but never style ATTRIBUTES, so a drawing
// built with inline styles renders unstyled - which is the same fault that
// collapses the IIIF viewer on this instance.

$PLOT_H = (int) round(430 * $exaggeration);   // drawing height, scaled by the exaggeration control
$PAD_T = 18;

$min = $plan['min'];
$max = $plan['max'];
$span = (null !== $min && null !== $max && $max > $min) ? ($max - $min) : null;

/** Elevation in metres to a y coordinate, highest elevation at the top. */
$yFor = function ($metres) use ($max, $span, $PLOT_H, $PAD_T) {
    if (null === $span) {
        return $PAD_T;
    }

    return $PAD_T + (($max - (float) $metres) / $span) * $PLOT_H;
};

$shades = ['#e6edec', '#d2dedd', '#bccecc', '#a6bebb', '#90aeaa', '#7a9e99', '#648e88'];
$GREEN = '#10373E';
$CUT = '#B4472B';
?>

<div class="ahg-archaeology-plan">

  <div class="d-flex gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_site?id='.$site->id); ?>">Back to site</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_contexts?siteId='.$site->id); ?>">Stratigraphy and Harris Matrix</a>
  </div>

  <h2 class="h5">Where the site is</h2>

  <?php if (null === $position) { ?>
    <div class="alert alert-info">
      No coordinates are recorded for this site, so it cannot be placed on a map.
      Add a latitude and longitude on the site record.
    </div>
  <?php } else { ?>

    <div class="row g-3 mb-4">
      <div class="col-md-5">
        <table class="table table-sm mb-0">
          <tbody>
            <tr><th scope="row" class="w-50">Latitude</th><td><?php echo esc_specialchars(number_format($position['lat'], 6)); ?></td></tr>
            <tr><th scope="row">Longitude</th><td><?php echo esc_specialchars(number_format($position['lng'], 6)); ?></td></tr>
            <tr><th scope="row">In degrees, minutes, seconds</th>
                <?php
                // Symfony escapes template variables before the template sees them,
                // so these already read 25&deg;48&#039;21.1&quot;S. Escaping again
                // turned the entities themselves into text and the row printed
                // 25&deg;48&amp;#039;21.1&amp;quot;S on screen. Decode back to the
                // real characters, then escape exactly once.
                ?>
                <td><?php echo esc_specialchars(
                    sfOutputEscaper::unescape($position['lat_dms']).' '
                    .sfOutputEscaper::unescape($position['lng_dms'])
                ); ?></td></tr>
            <?php if (null !== $position['elevation_m']) { ?>
              <tr><th scope="row">Elevation</th><td><?php echo (int) $position['elevation_m']; ?> m</td></tr>
            <?php } ?>
            <tr>
              <th scope="row">Positional accuracy</th>
              <td>
                <?php if (null === $position['accuracy_m']) { ?>
                  <span class="text-warning">unrecorded</span>
                  <div class="form-text">Blank means unrecorded, not exact.</div>
                <?php } else { ?>
                  &plusmn; <?php echo (int) $position['accuracy_m']; ?> m
                <?php } ?>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="mt-2">
          <?php
          $lat = $position['lat'];
          $lng = $position['lng'];
          ?>
          <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer"
             href="https://www.openstreetmap.org/?mlat=<?php echo rawurlencode((string) $lat); ?>&amp;mlon=<?php echo rawurlencode((string) $lng); ?>#map=15/<?php echo rawurlencode((string) $lat); ?>/<?php echo rawurlencode((string) $lng); ?>">Open in OpenStreetMap</a>
          <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer"
             href="https://www.google.com/maps/search/?api=1&amp;query=<?php echo rawurlencode($lat.','.$lng); ?>">Open in Google Maps</a>
        </div>

        <p class="form-text mt-2">
          Tiles are not loaded in the page. This instance's content security policy allows no
          map tile host, and adding one is a deliberate decision rather than something a
          plugin should do on its own. The locator below is drawn from the coordinate itself.
        </p>
      </div>

      <div class="col-md-7">
        <?php
        // A graticule locator: no tiles, no external requests, honest about being
        // a position rather than a map. Longitude -20..60, latitude 10..-40 covers
        // Africa, which is where this platform is deployed.
        $LW = 460; $LH = 300;
        $lon0 = -20; $lon1 = 60; $lat0 = 10; $lat1 = -40;
        $px = ($lng - $lon0) / ($lon1 - $lon0) * $LW;
        $py = ($lat - $lat0) / ($lat1 - $lat0) * $LH;
        $px = max(0, min($LW, $px));
        $py = max(0, min($LH, $py));
        ?>
        <svg viewBox="0 0 <?php echo $LW; ?> <?php echo $LH; ?>" width="100%" height="300"
             role="img" aria-label="Locator showing the recorded position">
          <rect x="0" y="0" width="<?php echo $LW; ?>" height="<?php echo $LH; ?>" fill="#f5f8f8" stroke="#cfdad9"></rect>
          <?php for ($lon = $lon0; $lon <= $lon1; $lon += 10) {
              $gx = ($lon - $lon0) / ($lon1 - $lon0) * $LW; ?>
            <line x1="<?php echo $gx; ?>" y1="0" x2="<?php echo $gx; ?>" y2="<?php echo $LH; ?>" stroke="#e1e9e8"></line>
            <text x="<?php echo $gx + 3; ?>" y="<?php echo $LH - 5; ?>" font-size="9" fill="#8a9a99"><?php echo $lon; ?>&deg;</text>
          <?php } ?>
          <?php for ($la = $lat0; $la >= $lat1; $la -= 10) {
              $gy = ($la - $lat0) / ($lat1 - $lat0) * $LH; ?>
            <line x1="0" y1="<?php echo $gy; ?>" x2="<?php echo $LW; ?>" y2="<?php echo $gy; ?>" stroke="#e1e9e8"></line>
            <text x="4" y="<?php echo $gy - 3; ?>" font-size="9" fill="#8a9a99"><?php echo $la; ?>&deg;</text>
          <?php } ?>
          <line x1="0" y1="<?php echo (0 - $lat0) / ($lat1 - $lat0) * $LH; ?>" x2="<?php echo $LW; ?>"
                y2="<?php echo (0 - $lat0) / ($lat1 - $lat0) * $LH; ?>" stroke="#b9c8c7" stroke-dasharray="4 3"></line>
          <circle cx="<?php echo $px; ?>" cy="<?php echo $py; ?>" r="16" fill="<?php echo $CUT; ?>" fill-opacity="0.15"></circle>
          <circle cx="<?php echo $px; ?>" cy="<?php echo $py; ?>" r="5" fill="<?php echo $CUT; ?>"></circle>
          <text x="<?php echo $px + 10; ?>" y="<?php echo $py - 8; ?>" font-size="11" fill="<?php echo $GREEN; ?>" font-weight="bold"><?php echo esc_specialchars($site->site_number); ?></text>
        </svg>
      </div>
    </div>

  <?php } ?>

  <h2 class="h5">The dig, sliced</h2>

  <?php
  $sel = static fn ($list, $v) => in_array($v, (array) $list, true) ? ' checked' : '';
  ?>

  <form method="get" action="<?php echo url_for('@archaeology_plan?siteId='.$site->id); ?>"
        class="border rounded p-3 mb-3">
    <input type="hidden" name="applied" value="1">

    <div class="row g-3">
      <div class="col-md-3">
        <div class="form-label">Trenches</div>
        <?php foreach ($plan['all_trenches'] as $i => $t) { ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="trench[]" id="tr<?php echo $i; ?>"
                   value="<?php echo esc_specialchars($t); ?>"<?php echo $sel($filters['trenches'], $t); ?>>
            <label class="form-check-label" for="tr<?php echo $i; ?>"><?php echo esc_specialchars($t); ?></label>
          </div>
        <?php } ?>
        <div class="form-text">None ticked shows all.</div>
      </div>

      <div class="col-md-3">
        <div class="form-label">Context types</div>
        <?php foreach ($plan['all_types'] as $i => $t) { ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="type[]" id="ty<?php echo $i; ?>"
                   value="<?php echo esc_specialchars($t); ?>"<?php echo $sel($filters['types'], $t); ?>>
            <label class="form-check-label" for="ty<?php echo $i; ?>"><?php echo esc_specialchars($t); ?></label>
          </div>
        <?php } ?>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="min">Elevation from (m)</label>
        <input type="number" step="0.001" class="form-control form-control-sm mb-2" id="min" name="min"
               value="<?php echo esc_specialchars($filters['min']); ?>"
               placeholder="<?php echo null === $plan['min'] ? '' : esc_specialchars((string) $plan['min']); ?>">
        <label class="form-label" for="max">to (m)</label>
        <input type="number" step="0.001" class="form-control form-control-sm" id="max" name="max"
               value="<?php echo esc_specialchars($filters['max']); ?>"
               placeholder="<?php echo null === $plan['max'] ? '' : esc_specialchars((string) $plan['max']); ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label" for="exaggeration">Vertical exaggeration</label>
        <select class="form-select form-select-sm mb-2" id="exaggeration" name="exaggeration">
          <?php foreach ([1 => 'None', 2 => 'x2', 3 => 'x3', 4 => 'x4'] as $v => $label) { ?>
            <option value="<?php echo $v; ?>"<?php echo (int) $exaggeration === $v ? ' selected' : ''; ?>>
              <?php echo esc_specialchars($label); ?>
            </option>
          <?php } ?>
        </select>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="1" id="features" name="features"
            <?php echo $filters['features'] ? ' checked' : ''; ?>>
          <label class="form-check-label" for="features">Show cuts, fills and structures</label>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Redraw</button>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_plan?siteId='.$site->id); ?>">Reset</a>
      </div>
    </div>

    <div class="form-text mt-2">
      Exaggeration stretches the vertical axis only. The elevation scale is relabelled with it,
      so a stretched drawing still reads true; it does not change any recorded value.
    </div>
  </form>

  <?php if (!empty($plan['excluded'])) { ?>
    <div class="alert alert-secondary py-2">
      <?php echo (int) $plan['excluded']; ?> context<?php echo 1 === (int) $plan['excluded'] ? '' : 's'; ?>
      hidden by the current filter.
    </div>
  <?php } ?>

  <?php if (!$plan['trenches']) { ?>
    <div class="alert alert-info">
      No context has both a top and a bottom elevation recorded, so there is nothing to
      draw to scale.
    </div>
  <?php } else { ?>

    <p class="text-muted">
      Vertical axis to scale from recorded elevations, <?php echo esc_specialchars(number_format((float) $min, 2)); ?> m
      to <?php echo esc_specialchars(number_format((float) $max, 2)); ?> m.
      Horizontal extent is schematic and not measured.
      Cuts, fills, masonry and burials are features, drawn to the right of the deposits
      rather than as layers, because they occupy part of a trench and not the whole width of it.
    </p>

    <div class="row g-4">
      <?php foreach ($plan['trenches'] as $trench) { ?>
        <?php
        $W = 470;
        $H = $PLOT_H + 46;
        $bedArea = 250;
        $ncol = max(1, count($trench['columns']));
        $colW = $bedArea / $ncol;
        ?>
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-body">
              <h3 class="h6 card-title"><?php echo esc_specialchars($trench['name']); ?></h3>
              <p class="text-muted small mb-2">
                <?php echo (int) $trench['context_count']; ?> context<?php echo 1 === (int) $trench['context_count'] ? '' : 's'; ?>,
                <?php echo (int) $trench['find_count']; ?> find<?php echo 1 === (int) $trench['find_count'] ? '' : 's'; ?>
              </p>

              <svg viewBox="0 0 <?php echo $W; ?> <?php echo $H; ?>" width="100%" height="<?php echo $H; ?>"
                   role="img" aria-label="Schematic section of <?php echo esc_specialchars($trench['name']); ?>">

                <?php // Elevation scale.
                foreach ($trench['columns'] as $column) {
                    foreach ($column as $bed) {
                        foreach ([$bed->top_elevation_m, $bed->bottom_elevation_m] as $e) {
                            $y = $yFor($e); ?>
                    <line x1="52" y1="<?php echo $y; ?>" x2="58" y2="<?php echo $y; ?>" stroke="#1f2933"></line>
                    <text x="48" y="<?php echo $y + 3; ?>" font-size="9" fill="#1f2933" text-anchor="end"><?php echo number_format((float) $e, 2); ?></text>
                <?php }
                    }
                } ?>
                <line x1="58" y1="<?php echo $PAD_T; ?>" x2="58" y2="<?php echo $PAD_T + $PLOT_H; ?>" stroke="#1f2933"></line>
                <text x="14" y="<?php echo $PAD_T + $PLOT_H / 2; ?>" font-size="10" fill="#1f2933"
                      transform="rotate(-90 14 <?php echo $PAD_T + $PLOT_H / 2; ?>)" text-anchor="middle">elevation (m)</text>

                <?php foreach ($trench['columns'] as $ci => $column) { ?>
                  <?php foreach ($column as $i => $bed) {
                      $y1 = $yFor($bed->top_elevation_m);
                      $y2 = $yFor($bed->bottom_elevation_m);
                      $h = max(2, $y2 - $y1);
                      $x = 62 + $ci * $colW; ?>
                    <rect x="<?php echo $x; ?>" y="<?php echo $y1; ?>" width="<?php echo $colW - 2; ?>" height="<?php echo $h; ?>"
                          fill="<?php echo $shades[($ci + $i) % count($shades)]; ?>" stroke="<?php echo $GREEN; ?>"></rect>
                    <?php if ($h > 15) { ?>
                      <text x="<?php echo $x + 6; ?>" y="<?php echo $y1 + $h / 2 + 3; ?>" font-size="10" fill="#1f2933">
                        <?php echo esc_specialchars($bed->label); ?><?php echo $bed->find_count ? ' · '.$bed->find_count.' find'.(1 === $bed->find_count ? '' : 's') : ''; ?>
                      </text>
                    <?php } ?>
                  <?php } ?>
                <?php } ?>

                <?php foreach ($trench['features'] as $j => $feature) {
                    $y1 = $yFor($feature->top_elevation_m);
                    $y2 = $yFor($feature->bottom_elevation_m);
                    $h = max(2, $y2 - $y1);
                    $x = 322 + ($j % 3) * 50;
                    $fill = 'Fill' === $feature->type_name ? '#f4e2db' : ('Masonry' === $feature->type_name ? '#e8e2d4' : '#ffffff'); ?>
                  <rect x="<?php echo $x; ?>" y="<?php echo $y1; ?>" width="46" height="<?php echo $h; ?>"
                        fill="<?php echo $fill; ?>" stroke="<?php echo $CUT; ?>" stroke-width="1.5"></rect>
                  <?php if ($h > 15) { ?>
                    <text x="<?php echo $x + 23; ?>" y="<?php echo $y1 + $h / 2 + 3; ?>" font-size="9"
                          fill="<?php echo $CUT; ?>" text-anchor="middle" font-weight="bold"><?php echo esc_specialchars($feature->label); ?></text>
                  <?php } ?>
                <?php } ?>

                <text x="62" y="<?php echo $PAD_T + $PLOT_H + 26; ?>" font-size="9" fill="#5b6b6a">deposits</text>
                <text x="322" y="<?php echo $PAD_T + $PLOT_H + 26; ?>" font-size="9" fill="<?php echo $CUT; ?>">features</text>
              </svg>
            </div>
          </div>
        </div>
      <?php } ?>
    </div>

  <?php } ?>

  <?php if ($plan['unplaced']) { ?>
    <div class="alert alert-warning mt-4">
      <strong><?php echo count($plan['unplaced']); ?> context<?php echo 1 === count($plan['unplaced']) ? '' : 's'; ?> could not be drawn</strong>
      because no top and bottom elevation is recorded:
      <?php
      $labels = [];
      foreach ($plan['unplaced'] as $u) {
          $labels[] = $u->label;
      }
      echo esc_specialchars(implode(', ', $labels));
      ?>.
      They are absent from the drawing rather than placed at a guessed depth.
    </div>
  <?php } ?>

</div>
