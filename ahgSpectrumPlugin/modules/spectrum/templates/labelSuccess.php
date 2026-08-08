<?php decorate_with('layout_3col'); ?>
<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .spectr-font-size-11pt-0089 { font-size: 11pt; }
  .spectr-max-width-300px-c0dc { max-width: 300px; }
</style>

<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1 class="no-print"><?php echo __('Print Labels'); ?>: <?php echo esc_entities($resource->title ?? $resource->slug); ?></h1>
<?php end_slot(); ?>

<?php slot('context-menu'); ?>
<section id="action-icons">
  <ul class="list-unstyled">
    <li>
      <a class="atom-icon-link" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $resource->slug]); ?>">
        <i class="fas fa-fw fa-arrow-left me-1" aria-hidden="true"></i><?php echo __('Back to record'); ?>
      </a>
    </li>
  </ul>
</section>
<?php end_slot(); ?>

<?php
use Illuminate\Database\Capsule\Manager as DB;

$objectId = $resource->id;

// Helper to safely query a table
function safeQuery($table, $objectId, $column) {
    try {
        return DB::table($table)->where('information_object_id', $objectId)->value($column);
    } catch (\Exception $e) {
        return null;
    }
}

// Detect sector
$sector = 'archive';
$sectorConfig = null;
try {
    $sectorConfig = DB::table('display_object_config')
        ->where('object_id', $objectId)
        ->value('object_type');
} catch (\Exception $e) {}
if ($sectorConfig) $sector = $sectorConfig;

// Build barcode sources - NO SLUG, only real identifiers
$barcodeSources = [];

// 1. Identifier (always available)
if (!empty($resource->identifier)) {
    $barcodeSources['identifier'] = [
        'label' => __('Identifier'),
        'value' => $resource->identifier,
    ];
}

// 2. ISBN from library_item
$isbn = safeQuery('library_item', $objectId, 'isbn');
if (!empty($isbn)) {
    $barcodeSources['isbn'] = [
        'label' => __('ISBN'),
        'value' => $isbn,
    ];
    $sector = 'library';
}

// 3. ISSN from library_item
$issn = safeQuery('library_item', $objectId, 'issn');
if (!empty($issn)) {
    $barcodeSources['issn'] = [
        'label' => __('ISSN'),
        'value' => $issn,
    ];
}


// LCCN from library_item
$lccn = safeQuery('library_item', $objectId, 'lccn');
if (!empty($lccn)) {
    $barcodeSources['lccn'] = [
        'label' => __('LCCN'),
        'value' => $lccn,
    ];
}

// OpenLibrary ID from library_item
$openlibrary = safeQuery('library_item', $objectId, 'openlibrary_id');
if (!empty($openlibrary)) {
    $barcodeSources['openlibrary'] = [
        'label' => __('OpenLibrary ID'),
        'value' => $openlibrary,
    ];
}
// 4. Barcode from library_item
$barcode = safeQuery('library_item', $objectId, 'barcode');
if (!empty($barcode)) {
    $barcodeSources['barcode'] = [
        'label' => __('Barcode'),
        'value' => $barcode,
    ];
}

// 5. Call Number from library_item
$callNumber = safeQuery('library_item', $objectId, 'call_number');
if (!empty($callNumber)) {
    $barcodeSources['call_number'] = [
        'label' => __('Call Number'),
        'value' => $callNumber,
    ];
}

// 6. Accession Number from museum_object
$accession = safeQuery('museum_object', $objectId, 'accession_number');
if (!empty($accession)) {
    $barcodeSources['accession'] = [
        'label' => __('Accession Number'),
        'value' => $accession,
    ];
    $sector = 'museum';
}

// 7. Object Number from museum_object
$objectNumber = safeQuery('museum_object', $objectId, 'object_number');
if (!empty($objectNumber)) {
    $barcodeSources['object_number'] = [
        'label' => __('Object Number'),
        'value' => $objectNumber,
    ];
}

// 8. Title as last option
$barcodeSources['title'] = [
    'label' => __('Title'),
    'value' => $resource->title ?? '',
];

// Default: use ISBN if available, then identifier, then title
$defaultBarcodeData = '';
$preferredOrder = ['isbn', 'issn', 'barcode', 'accession', 'identifier', 'title'];
foreach ($preferredOrder as $key) {
    if (!empty($barcodeSources[$key]['value'])) {
        $defaultBarcodeData = $barcodeSources[$key]['value'];
        break;
    }
}

$qrUrl = sfContext::getInstance()->getRequest()->getUriPrefix() . '/' . $resource->slug;

// Barcode and QR are generated locally as data URIs (#260). They used to be
// <img src> pointing at barcodeapi.org and api.qrserver.com, which sent the
// record identifier and public URL to third parties, produced nothing without
// outbound internet, and are not in the CSP img-src allowance. Pre-rendering
// every dropdown option means switching the barcode source is a local src swap
// rather than another external request.
// Keyed by the encoded value, because that is what the dropdown's option
// values carry, so the client can look one up directly.
$barcodeUris = [];
foreach ($barcodeSources as $source) {
    if (!empty($source['value'])) {
        $barcodeUris[$source['value']] = \AtomExtensions\Services\BarcodeService::barcodeDataUri($source['value']);
    }
}
$defaultBarcodeUri = \AtomExtensions\Services\BarcodeService::barcodeDataUri($defaultBarcodeData);
$qrUri = \AtomExtensions\Services\BarcodeService::qrDataUri($qrUrl);

$sectorLabels = [
    'library' => __('Library Item'),
    'archive' => __('Archival Record'),
    'museum' => __('Museum Object'),
    'gallery' => __('Gallery Artwork'),
];
$sectorLabel = $sectorLabels[$sector] ?? __('Record');
?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
@media print {
    /* Hide everything, then reveal only the label itself. The previous rule
       blacklisted selectors (.no-print, nav, header, footer), which missed the
       theme banner, the system-error alert bar and the surrounding panels - so
       printing produced the whole screen instead of the label. */
    body * { visibility: hidden !important; }
    #labelContent, #labelContent * { visibility: visible !important; }
    #labelContent {
        position: absolute;
        top: 0;
        left: 0;
        margin: 0 !important;
        box-shadow: none !important;
        border: 1px solid #ccc !important;
    }
    body { background: white !important; }
    @page { margin: 10mm; }
}
.label-preview {
    background: white;
    border: 2px solid #333;
    padding: 15px;
    margin: 20px auto;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
/* max-width keeps a linear (Code 128) barcode inside the label frame - without
   it a long identifier renders wider than the 300px preview and overflows. */
.barcode-img { max-height: 60px; max-width: 100%; height: auto; }
.qr-img { max-width: 120px; max-height: 120px; }
</style>

<div class="row no-print">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">
                <i class="fas fa-cog me-2"></i><?php echo __('Label Configuration'); ?>
                <span class="badge bg-secondary ms-2"><?php echo $sectorLabel; ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Barcode Source Dropdown -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-barcode me-1"></i><?php echo __('Barcode Source'); ?>
                        </label>
                        <select class="form-select" id="barcodeSource">
                            <?php foreach ($barcodeSources as $key => $source): ?>
                                <?php if (!empty($source['value'])): ?>
                                <option value="<?php echo htmlspecialchars($source['value']); ?>"
                                        <?php echo ($source['value'] === $defaultBarcodeData) ? 'selected' : ''; ?>>
                                    <?php echo $source['label']; ?>: <?php echo htmlspecialchars($source['value']); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Label Size -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold"><?php echo __('Label Size'); ?></label>
                        <select class="form-select" id="labelSize">
                            <option value="200"><?php echo __('Small (50mm)'); ?></option>
                            <option value="300" selected><?php echo __('Medium (75mm)'); ?></option>
                            <option value="400"><?php echo __('Large (100mm)'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Show Options -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('Show'); ?></label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showBarcode" checked>
                            <label class="form-check-label" for="showBarcode"><?php echo __('Linear Barcode'); ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showQR" checked>
                            <label class="form-check-label" for="showQR"><?php echo __('QR Code'); ?></label>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('Include'); ?></label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showTitle" checked>
                            <label class="form-check-label" for="showTitle"><?php echo __('Title'); ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showRepo" checked>
                            <label class="form-check-label" for="showRepo"><?php echo __('Repository'); ?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="mb-3">
            <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $resource->slug]); ?>">
                <i class="fas fa-arrow-left me-1"></i><?php echo __('Back'); ?>
            </a>
            <button type="button" class="btn btn-primary" id="printLabelBtn">
                <i class="fas fa-print me-1"></i><?php echo __('Print Label'); ?>
            </button>
            <a class="btn btn-secondary" id="downloadLabelLink" download
               href="<?php echo url_for(['module' => 'spectrum', 'action' => 'labelPng', 'slug' => $resource->slug]); ?>">
                <i class="fas fa-download me-1"></i><?php echo __('Download PNG'); ?>
            </a>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><?php echo __('Preview'); ?></div>
            <div class="card-body text-center">
                <div class="label-preview" id="labelContent" class="spectr-max-width-300px-c0dc">
                    <div id="labelTitle" class="fw-bold mb-2 spectr-font-size-11pt-0089" >
                        <?php echo esc_entities($resource->title ?? $resource->slug); ?>
                    </div>
                    
                    <div id="labelRepo" class="small text-muted mb-2">
                        <?php if ($resource->repository): ?>
                            <?php echo esc_entities($resource->repository->getAuthorizedFormOfName(['cultureFallback' => true])); ?>
                        <?php endif; ?>
                    </div>
                    
                    <div id="barcodeSection" class="mb-2">
                        <img id="barcodeImg" class="barcode-img"
                             src="<?php echo $defaultBarcodeUri; ?>"
                             data-barcode-uris="<?php echo esc_entities(json_encode($barcodeUris)); ?>"
                             alt="<?php echo __('Barcode'); ?>">
                        <div class="small mt-1" id="barcodeText"><?php echo esc_entities($defaultBarcodeData); ?></div>
                    </div>

                    <div id="qrSection">
                        <img id="qrImg" class="qr-img"
                             src="<?php echo $qrUri; ?>"
                             alt="<?php echo __('QR code'); ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function updateBarcodeSource() {
    var value = document.getElementById('barcodeSource').value;
    var img = document.getElementById('barcodeImg');

    // Every option was rendered server-side, so switching source is a local
    // swap - no request, and nothing leaves the instance (#260).
    var uris = {};
    try { uris = JSON.parse(img.getAttribute('data-barcode-uris') || '{}'); } catch (e) { uris = {}; }

    if (uris[value]) {
        img.src = uris[value];
    }

    document.getElementById('barcodeText').textContent = value;
}

function updateLabelSize() {
    var size = parseInt(document.getElementById('labelSize').value, 10) || 300;
    document.getElementById('labelContent').style.maxWidth = size + 'px';

    // Scale the codes with the label. Without this the selector only ever made the
    // label narrower: .barcode-img is capped at 60px tall and max-width:100%, so it
    // shrank on the small size and never grew on the large one.
    var barcode = document.getElementById('barcodeImg');
    if (barcode) {
        barcode.style.width = '100%';
        barcode.style.maxHeight = Math.round(size / 5) + 'px';
    }
    var qr = document.getElementById('qrImg');
    if (qr) {
        var q = Math.round(size * 0.4);
        qr.style.maxWidth = q + 'px';
        qr.style.maxHeight = q + 'px';
    }
}

function toggleBarcode() {
    document.getElementById('barcodeSection').style.display = document.getElementById('showBarcode').checked ? 'block' : 'none';
}

function toggleQR() {
    document.getElementById('qrSection').style.display = document.getElementById('showQR').checked ? 'block' : 'none';
}

function toggleTitle() {
    document.getElementById('labelTitle').style.display = document.getElementById('showTitle').checked ? 'block' : 'none';
}

function toggleRepo() {
    document.getElementById('labelRepo').style.display = document.getElementById('showRepo').checked ? 'block' : 'none';
}

// The PNG is composed server-side (spectrum/labelPng) rather than rendered in the
// browser: html2canvas returned a 0x0 canvas from a correctly sized element, which
// is a fault inside the library. Keeping the link's query string in step with the
// on-screen choices is all the client needs to do.
function updateDownloadLink() {
    var link = document.getElementById('downloadLabelLink');
    if (!link) { return; }
    var base = link.getAttribute('href').split('?')[0];
    var size = document.getElementById('labelSize');
    var src = document.getElementById('barcodeSource');
    var params = [];
    if (size) { params.push('size=' + encodeURIComponent(size.value)); }
    if (src) { params.push('data=' + encodeURIComponent(src.value)); }
    link.setAttribute('href', base + (params.length ? '?' + params.join('&') : ''));
}

// Event wiring, not inline handlers: AtoM's CSP script-src has no 'unsafe-inline',
// so onclick="" / onchange="" attributes are refused by the browser and every
// control on this page silently did nothing. This block carries the nonce, so it
// runs; the listeners it registers are what make the page work.
(function () {
    function on(id, evt, fn) {
        var el = document.getElementById(id);
        if (el) { el.addEventListener(evt, fn); }
    }

    on('printLabelBtn', 'click', function () { window.print(); });
    on('barcodeSource', 'change', updateBarcodeSource);
    on('labelSize', 'change', updateLabelSize);
    on('labelSize', 'change', updateDownloadLink);
    on('barcodeSource', 'change', updateDownloadLink);
    updateLabelSize();       // match the preview to the selected size on load
    updateDownloadLink();    // and the download link to both selectors
    on('showBarcode', 'change', toggleBarcode);
    on('showQR', 'change', toggleQR);
    on('showTitle', 'change', toggleTitle);
    on('showRepo', 'change', toggleRepo);
})();
</script>
