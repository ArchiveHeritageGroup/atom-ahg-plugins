<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .custom-height-320px-border-radius-3-10ee { height: 320px; border-radius: .375rem; }
</style>
<?php
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Site Location map panel.
 *
 * Renders a Leaflet map from the site_latitude / site_longitude custom fields.
 * Precise archaeological coordinates are sensitive (looting risk), so:
 *   - authenticated staff (or a record with the sensitivity flag turned off)
 *     see the EXACT position with a marker;
 *   - the public sees a GENERALISED position - coordinates rounded to ~0.1 deg
 *     and drawn as a coarse circle, never a precise pin - with a note.
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

// Staff (authenticated) always see exact; guests see exact only when the site
// is explicitly marked non-sensitive.
$isStaff = false;
try {
    $isStaff = \sfContext::getInstance()->getUser()->isAuthenticated();
} catch (\Throwable $e) {
    $isStaff = false;
}
$showExact = $isStaff || !$sensitive;

if ($showExact) {
    $dispLat = $lat;
    $dispLng = $lng;
    $zoom = 13;
    $fuzzKm = 0;
} else {
    $dispLat = round($lat, 1);   // ~11 km N-S
    $dispLng = round($lng, 1);
    $zoom = 9;
    $fuzzKm = 12;
}

$mapId = 'siteLocationMap_' . $objectId;
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
?>
<section id="siteLocationArea" class="border-bottom">
  <h5 class="section-title border-bottom pb-2 mb-3"><?php echo __('Site location'); ?></h5>

  <link rel="stylesheet" href="/plugins/ahgThemeB5Plugin/web/css/leaflet.min.css">
  <div id="<?php echo $mapId; ?>" class="mb-2 custom-height-320px-border-radius-3-10ee" ></div>

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
      <?php echo __('Approximate location shown. Precise coordinates for this site are restricted; sign in as authorised staff to view them.'); ?>
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
      L.circle([lat, lng], { radius: <?php echo (int) ($fuzzKm * 1000); ?>, color: '#1d6a52', weight: 1, fillColor: '#1d6a52', fillOpacity: 0.12 }).addTo(map);
      <?php else: ?>
      L.marker([lat, lng]).addTo(map);
      <?php endif; ?>
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
  })();
  </script>
</section>
