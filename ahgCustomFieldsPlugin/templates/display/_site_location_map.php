<?php
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Site Location map panel.
 *
 * Renders a Leaflet map from the site_latitude / site_longitude custom fields.
 * Precise archaeological coordinates are sensitive (looting risk), so:
 *   - editors and administrators (or anyone, for a record with the sensitivity
 *     flag turned off) see the EXACT position with a marker;
 *   - everyone else, including signed-in researchers, sees what the setting
 *     site_locality_public allows: by default nothing but a note that the
 *     location is restricted, or an area of about 10, 25 or 50 km drawn as a
 *     coarse circle, never a precise pin.
 * The same rule, setting and clearance as ahgSiteRecordPlugin's
 * LocalityVisibilityService, applied here so this plugin stands on its own.
 * (Until 26 Oct 2026 any signed-in user saw the exact position, and the public
 * a fixed ~11 km circle.)
 *
 * The flag defaults to ON (sensitive) when a record has never set it, so a new
 * site is protected by default. Leaflet JS/CSS ship with ahgThemeB5Plugin and
 * OSM tiles are already CSP-whitelisted. Renders nothing when the record has no
 * coordinates (i.e. every non-site record).
 *
 * Included by DisplayActionRegistry::renderPanel() with $resource in scope.
 */

$objectId = (int) $resource->id;
if ($objectId <= 1) {
    return;
}

$rows = DB::table('custom_field_value as v')
    ->join('custom_field_definition as d', 'd.id', '=', 'v.field_definition_id')
    ->where('v.object_id', $objectId)
    ->whereIn('d.field_key', ['site_latitude', 'site_longitude', 'coordinate_datum', 'location_sensitive'])
    ->select('d.field_key', 'v.value_number', 'v.value_text', 'v.value_boolean')
    ->get();

$lat = null;
$lng = null;
$datum = 'WGS84';
$sensitive = true;
$sensitiveSet = false;

foreach ($rows as $r) {
    switch ($r->field_key) {
        case 'site_latitude':
            $lat = $r->value_number;
            break;
        case 'site_longitude':
            $lng = $r->value_number;
            break;
        case 'coordinate_datum':
            if (null !== $r->value_text && '' !== $r->value_text) {
                $datum = $r->value_text;
            }
            break;
        case 'location_sensitive':
            $sensitiveSet = true;
            $sensitive = (null === $r->value_boolean) ? true : (bool) $r->value_boolean;
            break;
    }
}

if (null === $lat || null === $lng || '' === $lat || '' === $lng) {
    return;
}
$lat = (float) $lat;
$lng = (float) $lng;
if (!$sensitiveSet) {
    $sensitive = true; // default-protect records that never set the flag
}

// Editors and administrators see exact; everyone else only when the site is
// explicitly marked non-sensitive. Not "any signed-in user": a self-registered
// researcher must not get a precise position.
$isStaff = false;
try {
    $u = \sfContext::getInstance()->getUser();
    $isStaff = $u->isAuthenticated() && ($u->hasCredential('editor') || $u->hasCredential('administrator'));
} catch (\Throwable $e) {
    $isStaff = false;
}
$showExact = $isStaff || !$sensitive;

// What the public may see (site_locality_public): none, or about 10, 25 or 50 km.
$publicMode = 'none';
if (class_exists('\\AtomExtensions\\Services\\AhgSettingsService')) {
    try {
        $publicMode = (string) \AtomExtensions\Services\AhgSettingsService::get('site_locality_public', 'none');
    } catch (\Throwable $e) {
        $publicMode = 'none';
    }
}
if (!in_array($publicMode, ['none', '10', '25', '50'], true)) {
    $publicMode = 'none';
}

if ($showExact) {
    $dispLat = $lat;
    $dispLng = $lng;
    $zoom = 13;
    $fuzzKm = 0;
} elseif ('none' === $publicMode) {
    // No map and no number at all: only the note below.
    $dispLat = $dispLng = null;
    $zoom = 0;
    $fuzzKm = 0;
} else {
    $fuzzKm = (int) $publicMode;
    $step = $fuzzKm / 111.0;   // degrees of latitude in that many km
    $dispLat = round($lat / $step) * $step;
    $dispLng = round($lng / $step) * $step;
    $zoom = ['10' => 10, '25' => 9, '50' => 8][$publicMode];
}
$withheld = !$showExact && 'none' === $publicMode;

$mapId = 'siteLocationMap_' . $objectId;
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
?>
<section id="siteLocationArea" class="border-bottom">
  <h5 class="section-title border-bottom pb-2 mb-3"><?php echo __('Site location'); ?></h5>

  <?php if ($withheld): ?>
    <p class="text-muted small mb-0">
      <span class="badge bg-secondary me-1"><?php echo __('restricted'); ?></span>
      <?php echo __('The location of this site is restricted to protect it.'); ?>
    </p>
  </section>
  <?php return; endif; ?>

  <link rel="stylesheet" href="/plugins/ahgThemeB5Plugin/web/css/leaflet.min.css">
  <div id="<?php echo $mapId; ?>" class="mb-2" data-ahg-style="height: 320px; border-radius: .375rem;"></div>

  <?php if ($showExact): ?>
    <p class="text-muted small mb-0">
      <?php echo htmlspecialchars(number_format($lat, 5) . ', ' . number_format($lng, 5)); ?>
      <span class="ms-1">(<?php echo __('datum'); ?>: <?php echo htmlspecialchars($datum); ?>)</span>
      <?php if (!$isStaff && !$sensitive): ?>
        <span class="badge bg-secondary ms-1"><?php echo __('public location'); ?></span>
      <?php endif; ?>
    </p>
  <?php else: ?>
    <p class="text-muted small mb-0">
      <i class="fas fa-info-circle me-1"></i>
      <?php echo __('Location shown to approximately %km% km. Precise coordinates for this site are restricted to protect it.', ['%km%' => $fuzzKm]); ?>
    </p>
  <?php endif; ?>

  <script src="/plugins/ahgThemeB5Plugin/web/js/leaflet.min.js"></script>
  <script <?php echo $nonceAttr; ?>>
  (function () {
    function init() {
      if (typeof L === 'undefined') { return setTimeout(init, 100); }
      var el = document.getElementById('<?php echo $mapId; ?>');
      if (!el || el._leaflet_id) { return; }
      var lat = <?php echo json_encode($dispLat); ?>, lng = <?php echo json_encode($dispLng); ?>;
      var map = L.map('<?php echo $mapId; ?>').setView([lat, lng], <?php echo (int) $zoom; ?>);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);
      <?php if ($fuzzKm > 0): ?>
      L.circle([lat, lng], { radius: <?php echo (int) ($fuzzKm * 500); ?>, color: '#1d6a52', weight: 1, fillColor: '#1d6a52', fillOpacity: 0.12 }).addTo(map);
      <?php else: ?>
      L.marker([lat, lng]).addTo(map);
      <?php endif; ?>
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
  })();
  </script>
</section>
