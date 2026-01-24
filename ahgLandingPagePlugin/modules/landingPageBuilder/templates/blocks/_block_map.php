<?php
/**
 * Map Block Template
 */
$title = $config['title'] ?? 'Our Locations';
$height = $config['height'] ?? '400px';
$zoom = $config['zoom'] ?? 10;
$locations = $data ?? [];

$mapId = 'map-' . uniqid();
?>

<?php if (!empty($title)): ?>
  <h2 class="h4 mb-4"><?php echo esc_entities($title) ?></h2>
<?php endif ?>

<?php if (empty($locations)): ?>
  <p class="text-muted">No locations with coordinates available.</p>
<?php else: ?>
  <div id="<?php echo $mapId ?>" style="height: <?php echo $height ?>;" class="rounded border"></div>
  
  <link rel="stylesheet" href="/plugins/ahgCorePlugin/web/css/vendor/leaflet.min.css" />
  <script src="/plugins/ahgCorePlugin/web/js/vendor/leaflet.min.js"></script>
  
  <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  (function() {
      const locations = <?php echo json_encode($locations) ?>;
      
      if (locations.length === 0) return;
      
      // Calculate center from locations
      const lats = locations.map(l => parseFloat(l.latitude));
      const lngs = locations.map(l => parseFloat(l.longitude));
      const centerLat = lats.reduce((a, b) => a + b, 0) / lats.length;
      const centerLng = lngs.reduce((a, b) => a + b, 0) / lngs.length;
      
      const map = L.map('<?php echo $mapId ?>').setView([centerLat, centerLng], <?php echo $zoom ?>);
      
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);
      
      locations.forEach(loc => {
          const marker = L.marker([loc.latitude, loc.longitude]).addTo(map);
          const popup = `
              <strong>${loc.name}</strong><br>
              ${loc.street_address || ''} ${loc.city || ''}<br>
              <a href="/repository/${loc.slug}">View Repository</a>
          `;
          marker.bindPopup(popup);
      });
      
      // Fit bounds if multiple locations
      if (locations.length > 1) {
          const bounds = L.latLngBounds(locations.map(l => [l.latitude, l.longitude]));
          map.fitBounds(bounds, { padding: [20, 20] });
      }
  })();
  </script>
<?php endif ?>
