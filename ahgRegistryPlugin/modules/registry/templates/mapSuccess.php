<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Institutions Map'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Map')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0"><?php echo __('Institutions Map'); ?></h1>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionBrowse']); ?>" class="btn btn-outline-primary btn-sm">
    <i class="fas fa-list me-1"></i> <?php echo __('List View'); ?>
  </a>
</div>

<div class="row">

  <!-- Map -->
  <div class="col-lg-9 mb-4">
    <div id="registry-map" class="border rounded" style="height: 600px;"></div>
  </div>

  <!-- Sidebar: institution list -->
  <div class="col-lg-3">
    <div class="card">
      <div class="card-header fw-semibold">
        <?php echo __('Institutions'); ?>
        <span class="badge bg-primary ms-1" id="map-count">0</span>
      </div>
      <div class="card-body p-0" style="max-height: 555px; overflow-y: auto;">
        <div class="list-group list-group-flush" id="institution-list">
          <!-- Populated by JavaScript -->
        </div>
      </div>
    </div>
  </div>

</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" <?php echo $na; ?>></script>

<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {

  var defaultLat = <?php echo (float) $defaultLat; ?>;
  var defaultLng = <?php echo (float) $defaultLng; ?>;
  var defaultZoom = <?php echo (int) $defaultZoom; ?>;

  var map = L.map('registry-map').setView([defaultLat, defaultLng], defaultZoom);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 18
  }).addTo(map);

  var institutions = <?php echo json_encode($institutions, JSON_UNESCAPED_UNICODE); ?>;
  var markers = [];
  var listContainer = document.getElementById('institution-list');
  var countBadge = document.getElementById('map-count');

  if (countBadge) {
    countBadge.textContent = institutions.length;
  }

  var typeColors = {
    'archive': '#0d6efd',
    'library': '#198754',
    'museum': '#dc3545',
    'gallery': '#6f42c1',
    'dam': '#0dcaf0',
    'heritage_site': '#fd7e14',
    'research_centre': '#20c997',
    'government': '#6c757d',
    'university': '#ffc107',
    'other': '#adb5bd'
  };

  institutions.forEach(function(inst) {
    if (!inst.latitude || !inst.longitude) return;

    var color = typeColors[inst.institution_type] || '#6c757d';
    var typeLabel = (inst.institution_type || 'other').replace(/_/g, ' ');
    typeLabel = typeLabel.charAt(0).toUpperCase() + typeLabel.slice(1);

    var icon = L.divIcon({
      className: 'registry-marker',
      html: '<div style="background-color: ' + color + '; width: 12px; height: 12px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,.3);"></div>',
      iconSize: [16, 16],
      iconAnchor: [8, 8]
    });

    var popupContent = '<div style="min-width: 200px;">'
      + '<strong>' + escapeHtml(inst.name) + '</strong>'
      + '<br><span class="badge" style="background-color: ' + color + '; font-size: 10px;">' + typeLabel + '</span>';

    if (inst.city || inst.country) {
      popupContent += '<br><small class="text-muted">' + escapeHtml([inst.city, inst.country].filter(Boolean).join(', ')) + '</small>';
    }

    if (inst.slug) {
      popupContent += '<br><a href="' + escapeHtml(inst.url || ('/registry/institutions/' + inst.slug)) + '" class="small"><?php echo __('View Profile'); ?> &rarr;</a>';
    }

    popupContent += '</div>';

    var marker = L.marker([parseFloat(inst.latitude), parseFloat(inst.longitude)], { icon: icon })
      .addTo(map)
      .bindPopup(popupContent);

    markers.push({ marker: marker, data: inst });

    // Add to sidebar list
    if (listContainer) {
      var item = document.createElement('a');
      item.className = 'list-group-item list-group-item-action py-2';
      item.href = '#';
      item.innerHTML = '<div class="d-flex align-items-center">'
        + '<div style="width: 10px; height: 10px; border-radius: 50%; background: ' + color + '; margin-right: 8px; flex-shrink: 0;"></div>'
        + '<div class="small">'
        + '<strong>' + escapeHtml(inst.name) + '</strong>'
        + (inst.city ? '<br><span class="text-muted">' + escapeHtml(inst.city) + '</span>' : '')
        + '</div></div>';

      item.addEventListener('click', function(e) {
        e.preventDefault();
        map.setView([parseFloat(inst.latitude), parseFloat(inst.longitude)], 14);
        marker.openPopup();
      });

      listContainer.appendChild(item);
    }
  });

  function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

});
</script>

<style <?php echo $na; ?>>
  .registry-marker {
    background: transparent !important;
    border: none !important;
  }
</style>

<?php end_slot(); ?>
