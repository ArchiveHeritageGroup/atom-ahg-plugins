<?php if ($sf_user->getAttribute('search-realm') && sfConfig::get('app_enable_institutional_scoping')) { ?>
  <?php include_component('repository', 'holdingsInstitution', ['resource' => QubitRepository::getById($sf_user->getAttribute('search-realm'))]); ?>
<?php } else { ?>
  <?php echo get_component('repository', 'logo'); ?>
<?php } ?>
<?php echo get_component('informationobject', 'treeView'); ?>
<?php echo get_component('menu', 'staticPagesMenu'); ?>
<?php
// Check if a plugin is enabled
if (!function_exists('isPluginActive')) {
    function isPluginActive($pluginName) {
        static $plugins = null;
        if ($plugins === null) {
            try {
                $conn = Propel::getConnection();
                $stmt = $conn->prepare('SELECT name FROM atom_plugin WHERE is_enabled = 1');
                $stmt->execute();
                $plugins = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
            } catch (Exception $e) {
                $plugins = [];
            }
        }
        return isset($plugins[$pluginName]);
    }
}

// Museum/CCO specific links for authenticated users
if (isset($resource)) {
  $resourceSlug = null;
  if ($resource instanceof QubitInformationObject) {
    $resourceSlug = $resource->slug;
  } elseif (is_object($resource) && isset($resource->slug)) {
    $resourceSlug = $resource->slug;
  }

  // Check which plugins are enabled
  $hasCco = isPluginActive('ahgCcoPlugin');
  $hasCondition = isPluginActive('ahgConditionPlugin');
  $hasSpectrum = isPluginActive('ahgSpectrumPlugin') || isPluginActive('sfMuseumPlugin');
  $hasGrap = isPluginActive('ahgGrapPlugin');
  $hasOais = isPluginActive('ahgOaisPlugin');
  $hasResearch = isPluginActive('ahgResearchPlugin');
  $hasDisplay = isPluginActive('ahgDisplayPlugin');

  // Only show section if at least one plugin is enabled
  if ($resourceSlug && ($hasCco || $hasCondition || $hasSpectrum || $hasGrap || $hasOais || $hasResearch || $hasDisplay)) {
?>
<section class="sidebar-widget">
  <h4><?php echo __('Collections Management'); ?></h4>
  <ul>
    <?php if ($hasCco): ?>
    <li><?php echo link_to(__('Provenance'), ['module' => 'cco', 'action' => 'provenance', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
    <?php if ($hasCondition): ?>
    <li><?php echo link_to(__('Condition assessment'), ['module' => 'ahgCondition', 'action' => 'conditionCheck', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
    <?php if ($hasSpectrum): ?>
    <li><?php echo link_to(__('Spectrum data'), '/index.php/' . $resourceSlug . '/spectrum'); ?></li>
    <?php endif; ?>
    <?php if ($hasGrap): ?>
    <li><?php echo link_to(__('GRAP data'), ['module' => 'grap', 'action' => 'index', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
    <?php if ($hasOais): ?>
    <li><?php echo link_to(__('Digital Preservation (OAIS)'), ['module' => 'oais', 'action' => 'createSip', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
    <?php if ($hasResearch): ?>
    <li><?php echo link_to(__('Cite this Record'), ['module' => 'research', 'action' => 'cite', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
  </ul>
</section>
<?php
  }
}
?>

<!-- NER Extract Section -->
<?php if (isset($resource) && $sf_user->isAuthenticated() && in_array('ahgNerPlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
<section class="sidebar-widget">
  <h4><?php echo __('Named Entity Recognition'); ?></h4>
  <ul>
    <li>
      <a href="#" id="nerExtractBtn" onclick="extractEntities(<?php echo $resource->id ?>); return false;">
        <i class="bi bi-cpu me-1"></i><?php echo __('Extract Entities'); ?>
      </a>
    </li>
    <li>
      <a href="/ner/review">
        <i class="bi bi-list-check me-1"></i><?php echo __('Review Dashboard'); ?>
      </a>
    </li>
  </ul>
</section>

<!-- NER Results Modal -->
<div class="modal fade" id="nerModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-cpu me-2"></i>Extracted Entities</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="nerModalBody"></div>
      <div class="modal-footer">
        <span id="nerProcessingTime" class="text-muted small me-auto"></span>
        <a href="/ner/review" class="btn btn-success" id="nerReviewBtn" style="display:none;">Review & Link</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<script>
function extractEntities(objectId) {
  var modal = new bootstrap.Modal(document.getElementById('nerModal'));
  modal.show();
  document.getElementById('nerModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Extracting...</p></div>';
  document.getElementById('nerReviewBtn').style.display = 'none';

  fetch('/ner/extract/' + objectId, { method: 'POST' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.success) {
        document.getElementById('nerModalBody').innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed') + '</div>';
        return;
      }
      var html = '';
      var cfg = { PERSON: {icon:'bi-person-fill',color:'primary',label:'People'}, ORG: {icon:'bi-building',color:'success',label:'Organizations'}, GPE: {icon:'bi-geo-alt-fill',color:'info',label:'Places'}, DATE: {icon:'bi-calendar',color:'warning',label:'Dates'} };
      var total = 0;
      for (var type in data.entities) {
        var items = data.entities[type];
        if (items && items.length) {
          total += items.length;
          var c = cfg[type] || {icon:'bi-tag',color:'secondary',label:type};
          html += '<div class="mb-3"><h6 class="text-'+c.color+'"><i class="'+c.icon+' me-1"></i>'+c.label+' <span class="badge bg-'+c.color+'">'+items.length+'</span></h6><div class="d-flex flex-wrap gap-2">';
          for (var i=0; i<items.length; i++) { html += '<span class="badge bg-'+c.color+' bg-opacity-75 fs-6 fw-normal">'+items[i]+'</span>'; }
          html += '</div></div>';
        }
      }
      if (total === 0) { html = '<div class="alert alert-info">No entities found</div>'; }
      else { document.getElementById('nerReviewBtn').style.display = 'inline-block'; }
      document.getElementById('nerModalBody').innerHTML = html;
      document.getElementById('nerProcessingTime').textContent = 'Found ' + total + ' entities in ' + (data.processing_time_ms||0) + 'ms';
    })
    .catch(function(err) {
      document.getElementById('nerModalBody').innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>';
    });
}
</script>
<?php endif; ?>

<?php if (isPluginActive('ahgExtendedRightsPlugin')): ?>
<?php if (file_exists(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/informationobject/templates/_extendedRightsContextMenu.php')) { include_partial('informationobject/extendedRightsContextMenu', ['resource' => $resource]); } ?>
<?php endif; ?>
