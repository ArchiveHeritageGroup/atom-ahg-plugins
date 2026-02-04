<?php decorate_with('layout_3col'); ?>

<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1 class="no-print"><?php echo __('Print Labels'); ?>: <?php echo esc_specialchars(html_entity_decode($resource->title ?? $resource->slug, ENT_QUOTES, 'UTF-8')); ?></h1>
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
error_log("DEBUG ISBN for $objectId: " . var_export($isbn, true));
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
    'value' => html_entity_decode($resource->title ?? '', ENT_QUOTES, 'UTF-8'),
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
    .no-print, #sidebar, #context-menu, nav, header, footer { display: none !important; }
    body { background: white !important; }
    .label-preview { width: fit-content; min-width: 200px; box-shadow: none !important; border: 1px solid #ccc !important; }
}
.label-preview { width: fit-content; min-width: 200px;
    background: white;
    border: 2px solid #333;
    padding: 15px;
    margin: 20px auto;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.barcode-img { max-height: 60px; width: auto; max-width: 100%; }
.qr-img { max-width: 120px; max-height: 120px; }
</style>

<div class="row">
    <div class="col-md-8 no-print">
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
                        <select class="form-select" id="barcodeSource" onchange="updateBarcodeSource()">
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
                        <select class="form-select" id="labelSize" onchange="updateLabelSize()">
                            <option value="200"><?php echo __('Small (50mm)'); ?></option>
                            <option value="300" selected><?php echo __('Medium (75mm)'); ?></option>
                            <option value="400"><?php echo __('Large (100mm)'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Show Options -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('Show'); ?></label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showBarcode" checked onchange="toggleBarcode()">
                            <label class="form-check-label" for="showBarcode"><?php echo __('Linear Barcode'); ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showQR" checked onchange="toggleQR()">
                            <label class="form-check-label" for="showQR"><?php echo __('QR Code'); ?></label>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('Include'); ?></label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showTitle" checked onchange="toggleTitle()">
                            <label class="form-check-label" for="showTitle"><?php echo __('Title'); ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showRepo" checked onchange="toggleRepo()">
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
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-1"></i><?php echo __('Print Label'); ?>
            </button>
            <button type="button" class="btn btn-secondary" onclick="downloadLabel()">
                <i class="fas fa-download me-1"></i><?php echo __('Download PNG'); ?>
            </button>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><?php echo __('Preview'); ?></div>
            <div class="card-body text-center">
                <div class="label-preview" id="labelContent" style="max-width: 300px;">
                    <div id="labelTitle" class="fw-bold mb-2" style="font-size: 11pt;">
                        <?php echo esc_specialchars(html_entity_decode($resource->title ?? $resource->slug, ENT_QUOTES, 'UTF-8')); ?>
                    </div>
                    
                    <div id="labelRepo" class="small text-muted mb-2">
                        <?php if ($resource->repository): ?>
                            <?php echo esc_entities($resource->repository->getAuthorizedFormOfName(['cultureFallback' => true])); ?>
                        <?php endif; ?>
                    </div>
                    
                    <div id="barcodeSection" class="mb-2">
                        <img id="barcodeImg" class="barcode-img"
                             src="https://barcodeapi.org/api/128/<?php echo rawurlencode($defaultBarcodeData); ?>" 
                             alt="Barcode">
                        <div class="small mt-1" id="barcodeText"><?php echo esc_entities($defaultBarcodeData); ?></div>
                    </div>
                    
                    <div id="qrSection">
                        <img id="qrImg" class="qr-img"
                             src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo rawurlencode($qrUrl); ?>" 
                             alt="QR Code">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function updateBarcodeSource() {
    var value = document.getElementById('barcodeSource').value;
    document.getElementById('barcodeImg').src = 'https://barcodeapi.org/api/128/' + encodeURIComponent(value);
    document.getElementById('barcodeText').textContent = value;
}

function updateLabelSize() {
    document.getElementById('labelContent').style.maxWidth = document.getElementById('labelSize').value + 'px';
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

function downloadLabel() {
    if (typeof html2canvas !== 'undefined') {
        html2canvas(document.getElementById('labelContent')).then(function(canvas) {
            var link = document.createElement('a');
            link.download = 'label-<?php echo $resource->slug; ?>.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    } else {
        alert('<?php echo __('Download requires html2canvas. Use Print instead.'); ?>');
    }
}
</script>
