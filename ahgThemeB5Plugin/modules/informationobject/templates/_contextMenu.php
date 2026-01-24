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
  $hasProvenance = isPluginActive('ahgProvenancePlugin');

  // Only show section if at least one plugin is enabled
  if ($resourceSlug && ($hasCco || $hasCondition || $hasSpectrum || $hasGrap || $hasOais || $hasResearch || $hasDisplay || $hasProvenance)) {
?>
<section class="sidebar-widget">
  <h4><?php echo __('Collections Management'); ?></h4>
  <ul>
    <?php if ($hasProvenance): ?>
    <li><?php echo link_to(__('Chain of Custody'), ['module' => 'provenance', 'action' => 'timeline', 'slug' => $resourceSlug]); ?></li>
    <li><?php echo link_to(__('Edit Provenance'), ['module' => 'provenance', 'action' => 'view', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
    <?php if ($hasCondition): ?>
    <li><?php echo link_to(__('Condition assessment'), ['module' => 'ahgCondition', 'action' => 'conditionCheck', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
    <?php if ($hasSpectrum): ?>
    <li><?php echo link_to(__('Spectrum data'), '/index.php/' . $resourceSlug . '/spectrum'); ?></li>
    <?php endif; ?>
    <?php if ($hasGrap): ?>
    <li><?php echo link_to(__('GRAP data'), ['module' => 'grap', 'action' => 'view', 'slug' => $resourceSlug]); ?></li>
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

<!-- AI Tools Section (NER, Summarize, Translate) -->
<?php if (isset($resource) && $sf_user->isAuthenticated() && in_array('ahgAIPlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
<section class="sidebar-widget">
  <h4><?php echo __('Named Entity Recognition'); ?></h4>
  <ul>
    <li>
      <a href="#" id="nerExtractBtn" onclick="extractEntities(<?php echo $resource->id ?>); return false;">
        <i class="bi bi-cpu me-1"></i><?php echo __('Extract Entities'); ?>
      </a>
    </li>
    <li>
      <a href="#" id="aiSummarizeBtn" onclick="generateSummary(<?php echo $resource->id ?>); return false;">
        <i class="bi bi-file-text me-1"></i><?php echo __('Generate Summary'); ?>
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

  fetch('/index.php/ner/extract/' + objectId, { method: 'POST' })
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

function generateSummary(objectId) {
  var modal = new bootstrap.Modal(document.getElementById('nerModal'));
  modal.show();
  document.getElementById('nerModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-info"></div><p class="mt-2">Generating summary...</p></div>';
  document.getElementById('nerReviewBtn').style.display = 'none';
  document.getElementById('nerProcessingTime').textContent = '';

  fetch('/index.php/ner/summarize/' + objectId, { method: 'POST' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.success) {
        document.getElementById('nerModalBody').innerHTML = '<div class="alert alert-danger">' + (data.error || 'Summary generation failed') + '</div>';
        return;
      }
      var savedMsg = data.saved ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Saved to Scope \& Content</span>' : '<span class="text-warning">Generated (not saved)</span>';
      var html = '<div class="alert alert-success mb-3">' + savedMsg + '</div>';
      html += '<div class="card"><div class="card-header"><i class="bi bi-file-text me-1"></i>Generated Summary</div>';
      html += '<div class="card-body">' + data.summary + '</div></div>';
      html += '<div class="mt-3"><button class="btn btn-outline-primary" onclick="location.reload()"><i class="bi bi-arrow-clockwise me-1"></i>Refresh Page</button></div>';
      document.getElementById('nerModalBody').innerHTML = html;
      document.getElementById('nerProcessingTime').textContent = 'Source: ' + data.source + ' | ' + (data.processing_time_ms||0) + 'ms';
      if (data.saved && 'Notification' in window && Notification.permission === 'granted') {
        new Notification('Summary Generated', { body: 'Scope \& Content field updated', icon: '/favicon.ico' });
      }
    })
    .catch(function(err) {
      document.getElementById('nerModalBody').innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>';
    });
}

if ('Notification' in window && Notification.permission === 'default') { Notification.requestPermission(); }
</script>
<?php endif; ?>

<!-- Privacy & PII Section -->
<?php if (isset($resource) && $sf_user->isAuthenticated() && in_array('ahgPrivacyPlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
<section class="sidebar-widget">
  <h4><?php echo __('Privacy & PII'); ?></h4>
  <ul>
    <li>
      <a href="#" id="piiScanBtn" onclick="scanForPii(<?php echo $resource->id ?>); return false;">
        <i class="bi bi-shield-exclamation me-1"></i><?php echo __('Scan for PII'); ?>
      </a>
    </li>
    <li>
      <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'piiReview']); ?>">
        <i class="bi bi-clipboard-check me-1"></i><?php echo __('PII Review Queue'); ?>
      </a>
    </li>
    <li>
      <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'visualRedactionEditor', 'id' => $resource->id]); ?>">
        <i class="bi bi-eraser-fill me-1"></i><?php echo __('Visual Redaction Editor'); ?>
      </a>
    </li>
    <li>
      <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'piiScan']); ?>">
        <i class="bi bi-speedometer2 me-1"></i><?php echo __('PII Dashboard'); ?>
      </a>
    </li>
  </ul>
</section>

<!-- PII Results Modal -->
<div class="modal fade" id="piiModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="bi bi-shield-exclamation me-2"></i>PII Detection Results</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="piiModalBody"></div>
      <div class="modal-footer">
        <span id="piiRiskScore" class="me-auto"></span>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'piiReview']); ?>" class="btn btn-warning" id="piiReviewBtn" style="display:none;">Review PII</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<script>
function scanForPii(objectId) {
  var modal = new bootstrap.Modal(document.getElementById('piiModal'));
  modal.show();
  document.getElementById('piiModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-warning"></div><p class="mt-2">Scanning for PII...</p></div>';
  document.getElementById('piiReviewBtn').style.display = 'none';
  document.getElementById('piiRiskScore').textContent = '';

  fetch('/index.php/privacyAdmin/piiScanAjax?id=' + objectId, { method: 'GET' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.success) {
        document.getElementById('piiModalBody').innerHTML = '<div class="alert alert-danger">' + (data.error || 'Scan failed') + '</div>';
        return;
      }
      var html = '';
      var cfg = {
        PERSON: {icon:'bi-person-fill',color:'info',label:'People'},
        ORG: {icon:'bi-building',color:'secondary',label:'Organizations'},
        GPE: {icon:'bi-geo-alt-fill',color:'secondary',label:'Places'},
        DATE: {icon:'bi-calendar',color:'secondary',label:'Dates'},
        SA_ID: {icon:'bi-credit-card-fill',color:'danger',label:'SA ID Numbers'},
        NG_NIN: {icon:'bi-credit-card-fill',color:'danger',label:'Nigerian NIN'},
        PASSPORT: {icon:'bi-passport',color:'danger',label:'Passport Numbers'},
        EMAIL: {icon:'bi-envelope-fill',color:'warning',label:'Email Addresses'},
        PHONE_SA: {icon:'bi-telephone-fill',color:'warning',label:'Phone Numbers (SA)'},
        PHONE_INTL: {icon:'bi-telephone-fill',color:'warning',label:'Phone Numbers (Intl)'},
        BANK_ACCOUNT: {icon:'bi-bank',color:'danger',label:'Bank Accounts'},
        TAX_NUMBER: {icon:'bi-file-earmark-text',color:'danger',label:'Tax Numbers'},
        CREDIT_CARD: {icon:'bi-credit-card',color:'danger',label:'Credit Cards'}
      };
      var total = 0;
      for (var type in data.entities) {
        var items = data.entities[type];
        if (items && items.length) {
          total += items.length;
          var c = cfg[type] || {icon:'bi-tag',color:'secondary',label:type};
          html += '<div class="mb-3"><h6 class="text-'+c.color+'"><i class="'+c.icon+' me-1"></i>'+c.label+' <span class="badge bg-'+c.color+'">'+items.length+'</span></h6><div class="d-flex flex-wrap gap-2">';
          for (var i=0; i<items.length; i++) {
            var riskBadge = items[i].risk === 'high' || items[i].risk === 'critical' ? 'bg-danger' : (items[i].risk === 'medium' ? 'bg-warning text-dark' : 'bg-secondary');
            html += '<span class="badge '+riskBadge+' fs-6 fw-normal" title="Confidence: '+items[i].confidence+'%">'+items[i].value+'</span>';
          }
          html += '</div></div>';
        }
      }
      if (total === 0) { html = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>No PII detected in this record</div>'; }
      else {
        document.getElementById('piiReviewBtn').style.display = 'inline-block';
        if (data.high_risk > 0) {
          html = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><strong>' + data.high_risk + ' high-risk PII entities detected!</strong></div>' + html;
        }
      }
      document.getElementById('piiModalBody').innerHTML = html;
      document.getElementById('piiRiskScore').innerHTML = '<span class="badge '+(data.risk_score > 50 ? 'bg-danger' : (data.risk_score > 20 ? 'bg-warning text-dark' : 'bg-success'))+'">Risk Score: '+data.risk_score+'/100</span> | Found ' + total + ' entities';
    })
    .catch(function(err) {
      document.getElementById('piiModalBody').innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>';
    });
}
</script>
<?php endif; ?>

<!-- Digital Object Tools Section -->
<?php if (isset($resource) && $sf_user->isAuthenticated()): ?>
<?php
if (!isset($hasDigitalObject)) {
    $hasDigitalObject = \Illuminate\Database\Capsule\Manager::table('digital_object')->where('object_id', $resource->id)->exists();
}
if ($hasDigitalObject):
    // Get digital object details
    $doInfo = \Illuminate\Database\Capsule\Manager::table('digital_object')->where('object_id', $resource->id)->first();
    $digitalObjectId = $doInfo->id ?? null;
    $mimeType = $doInfo->mime_type ?? '';
    $isVideo = strpos($mimeType, 'video') !== false;
    $isAudio = strpos($mimeType, 'audio') !== false;
    $isImage = strpos($mimeType, 'image') !== false;

    // Check for existing transcription
    $hasTranscription = false;
    if ($digitalObjectId && ($isVideo || $isAudio)) {
        try {
            $hasTranscription = \Illuminate\Database\Capsule\Manager::table('media_transcription')->where('digital_object_id', $digitalObjectId)->exists();
        } catch (Exception $e) {}
    }
?>
<section class="sidebar-widget">
  <h4><?php echo __('Digital Object Tools'); ?></h4>
  <ul>
    <?php if ($isVideo || $isAudio): ?>
    <li>
      <?php if ($hasTranscription): ?>
      <a href="#" onclick="document.querySelector('[data-bs-target*=transcript-panel]')?.click(); return false;">
        <i class="bi bi-card-text me-1"></i><?php echo __('View Transcript'); ?>
      </a>
      <?php else: ?>
      <a href="#" onclick="triggerTranscription(<?php echo $digitalObjectId; ?>); return false;" id="transcribe-link">
        <i class="bi bi-mic me-1"></i><?php echo __('Transcribe (Whisper)'); ?>
      </a>
      <?php endif; ?>
    </li>
    <?php endif; ?>
    <li>
      <a href="#" onclick="showTechnicalData(<?php echo $digitalObjectId; ?>); return false;">
        <i class="bi bi-info-circle me-1"></i><?php echo __('Technical Data'); ?>
      </a>
    </li>
    <?php if ($isImage): ?>
    <li>
      <a href="#" onclick="extractExifMetadata(<?php echo $digitalObjectId; ?>); return false;">
        <i class="bi bi-camera me-1"></i><?php echo __('Extract EXIF/XMP'); ?>
      </a>
    </li>
    <?php endif; ?>
    <?php if (in_array('ahgAIPlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
    <li>
      <a href="#" onclick="generateSummary(<?php echo $resource->id; ?>); return false;">
        <i class="bi bi-file-text me-1"></i><?php echo __('Summarize'); ?>
      </a>
    </li>
    <?php endif; ?>
    <?php if (in_array('ahgTranslationPlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
    <li>
      <?php include_partial('ahgTranslation/translateModal', ['objectId' => $resource->id]); ?>
    </li>
    <?php endif; ?>
  </ul>
</section>

<!-- Technical Data Modal -->
<div class="modal fade" id="technicalDataModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i><?php echo __('Technical Data'); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="technicalDataBody">
        <div class="text-center py-4"><div class="spinner-border text-info"></div><p class="mt-2">Loading...</p></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
      </div>
    </div>
  </div>
</div>

<script>
function triggerTranscription(doId) {
  var btn = document.getElementById('transcribe-btn-' + doId);
  if (btn) { btn.click(); return; }
  var link = document.getElementById('transcribe-link');
  if (link) { link.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Transcribing...'; link.style.pointerEvents = 'none'; }
  fetch('/media/transcribe/' + doId, { method: 'POST' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) location.reload();
      else { alert('Transcription failed: ' + (data.error || 'Unknown')); if (link) { link.innerHTML = '<i class="bi bi-mic me-1"></i>Transcribe (Whisper)'; link.style.pointerEvents = 'auto'; } }
    })
    .catch(function(err) { alert('Error: ' + err.message); if (link) { link.innerHTML = '<i class="bi bi-mic me-1"></i>Transcribe (Whisper)'; link.style.pointerEvents = 'auto'; } });
}

function showTechnicalData(doId) {
  var modal = new bootstrap.Modal(document.getElementById('technicalDataModal'));
  modal.show();
  document.getElementById('technicalDataBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-info"></div><p class="mt-2">Loading...</p></div>';
  fetch('/media/metadata/' + doId)
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.error) { document.getElementById('technicalDataBody').innerHTML = '<div class="alert alert-warning">' + data.error + '</div><button class="btn btn-primary" onclick="extractMetadataAndRefresh(' + doId + ')"><i class="bi bi-cogs me-1"></i>Extract Metadata</button>'; return; }
      var html = '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Property</th><th>Value</th></tr></thead><tbody>';
      if (data.filename) html += '<tr><td><strong>Filename</strong></td><td>' + data.filename + '</td></tr>';
      if (data.mime_type) html += '<tr><td><strong>MIME Type</strong></td><td>' + data.mime_type + '</td></tr>';
      if (data.byte_size) html += '<tr><td><strong>File Size</strong></td><td>' + formatBytes(data.byte_size) + '</td></tr>';
      if (data.duration) html += '<tr><td><strong>Duration</strong></td><td>' + formatDuration(data.duration) + '</td></tr>';
      if (data.width && data.height) html += '<tr><td><strong>Dimensions</strong></td><td>' + data.width + ' x ' + data.height + ' px</td></tr>';
      if (data.codec) html += '<tr><td><strong>Codec</strong></td><td>' + data.codec + '</td></tr>';
      if (data.bitrate) html += '<tr><td><strong>Bitrate</strong></td><td>' + Math.round(data.bitrate / 1000) + ' kbps</td></tr>';
      if (data.sample_rate) html += '<tr><td><strong>Sample Rate</strong></td><td>' + data.sample_rate + ' Hz</td></tr>';
      if (data.channels) html += '<tr><td><strong>Channels</strong></td><td>' + data.channels + '</td></tr>';
      if (data.exif) { for (var k in data.exif) html += '<tr><td><strong>' + k + '</strong></td><td>' + data.exif[k] + '</td></tr>'; }
      if (data.metadata) { for (var k in data.metadata) html += '<tr><td><strong>' + k + '</strong></td><td>' + data.metadata[k] + '</td></tr>'; }
      html += '</tbody></table></div>';
      if (!data.filename && !data.mime_type && !data.exif && !data.metadata) html = '<div class="alert alert-info">No technical metadata. Click "Extract Metadata" to generate it.</div><button class="btn btn-primary" onclick="extractMetadataAndRefresh(' + doId + ')"><i class="bi bi-cogs me-1"></i>Extract Metadata</button>';
      document.getElementById('technicalDataBody').innerHTML = html;
    })
    .catch(function(err) { document.getElementById('technicalDataBody').innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>'; });
}

function formatBytes(b) { if (!b) return '0 B'; var k = 1024, s = ['B','KB','MB','GB'], i = Math.floor(Math.log(b)/Math.log(k)); return parseFloat((b/Math.pow(k,i)).toFixed(2)) + ' ' + s[i]; }
function formatDuration(sec) { if (!sec) return '0:00'; var h = Math.floor(sec/3600), m = Math.floor((sec%3600)/60), s = Math.floor(sec%60); return h > 0 ? h + ':' + (m<10?'0':'') + m + ':' + (s<10?'0':'') + s : m + ':' + (s<10?'0':'') + s; }

function extractMetadataAndRefresh(doId) {
  document.getElementById('technicalDataBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Extracting metadata...</p></div>';
  fetch('/media/extract/' + doId, { method: 'POST' })
    .then(function(r) { return r.json(); })
    .then(function(data) { if (data.success) showTechnicalData(doId); else document.getElementById('technicalDataBody').innerHTML = '<div class="alert alert-danger">Extraction failed: ' + (data.error || 'Unknown') + '</div>'; })
    .catch(function(err) { document.getElementById('technicalDataBody').innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>'; });
}

function extractExifMetadata(doId) { showTechnicalData(doId); }

</script>

<?php endif; ?>
<?php endif; ?>

<?php if (isPluginActive('ahgExtendedRightsPlugin')): ?>
<?php if (file_exists(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/informationobject/templates/_extendedRightsContextMenu.php')) { include_partial('informationobject/extendedRightsContextMenu', ['resource' => $resource]); } ?>
<?php endif; ?>
