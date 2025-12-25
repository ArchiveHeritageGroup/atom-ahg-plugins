<?php
/**
 * Identifier Selector - GLAM sector-aware dropdown with scan-to-lookup
 *
 * Usage:
 * <?php include_partial('informationobject/identifierSelector', [
 *     'resource' => $resource,
 *     'sector' => 'library'
 * ]) ?>
 */

$objectId = isset($resource) ? $resource->id : 0;
$sector = $sector ?? 'archive';

$identifierTypes = [
    'library' => [
        'isbn13' => ['label' => 'ISBN-13', 'icon' => 'fa-barcode', 'lookup' => true],
        'isbn10' => ['label' => 'ISBN-10', 'icon' => 'fa-barcode', 'lookup' => true],
        'issn' => ['label' => 'ISSN', 'icon' => 'fa-newspaper', 'lookup' => true],
        'lccn' => ['label' => 'LCCN', 'icon' => 'fa-building-columns', 'lookup' => false],
        'doi' => ['label' => 'DOI', 'icon' => 'fa-link', 'lookup' => false],
        'barcode' => ['label' => 'Barcode', 'icon' => 'fa-qrcode', 'lookup' => false],
    ],
    'archive' => [
        'reference_code' => ['label' => 'Reference Code', 'icon' => 'fa-folder-tree', 'lookup' => false],
        'identifier' => ['label' => 'Identifier', 'icon' => 'fa-hashtag', 'lookup' => false],
        'barcode' => ['label' => 'Barcode', 'icon' => 'fa-qrcode', 'lookup' => false],
    ],
    'museum' => [
        'accession_number' => ['label' => 'Accession Number', 'icon' => 'fa-stamp', 'lookup' => false],
        'object_number' => ['label' => 'Object Number', 'icon' => 'fa-cube', 'lookup' => false],
        'barcode' => ['label' => 'Barcode', 'icon' => 'fa-qrcode', 'lookup' => false],
    ],
    'gallery' => [
        'artwork_id' => ['label' => 'Artwork ID', 'icon' => 'fa-palette', 'lookup' => false],
        'catalogue_number' => ['label' => 'Catalogue Number', 'icon' => 'fa-book', 'lookup' => false],
        'barcode' => ['label' => 'Barcode', 'icon' => 'fa-qrcode', 'lookup' => false],
    ],
    'dam' => [
        'asset_id' => ['label' => 'Asset ID', 'icon' => 'fa-photo-film', 'lookup' => false],
        'identifier' => ['label' => 'Identifier', 'icon' => 'fa-hashtag', 'lookup' => false],
        'barcode' => ['label' => 'Barcode', 'icon' => 'fa-qrcode', 'lookup' => false],
    ],
];

$types = $identifierTypes[$sector] ?? $identifierTypes['archive'];
$hasLookup = in_array($sector, ['library']);
?>

<div class="identifier-selector card mb-3"
     data-object-id="<?php echo $objectId ?>"
     data-sector="<?php echo $sector ?>">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold">
            <i class="fa-solid fa-barcode me-2"></i><?php echo __('Identifier / Barcode') ?>
        </span>
    </div>

    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label"><?php echo __('Identifier Type') ?></label>
                <select class="form-select identifier-type-select"
                        id="identifierType<?php echo $objectId ?>">
                    <?php foreach ($types as $key => $config): ?>
                        <option value="<?php echo $key ?>"
                                data-icon="<?php echo $config['icon'] ?>"
                                data-lookup="<?php echo $config['lookup'] ? '1' : '0' ?>">
                            <?php echo __($config['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label"><?php echo __('Value') ?></label>
                <div class="input-group">
                    <span class="input-group-text identifier-icon">
                        <i class="fa-solid fa-barcode"></i>
                    </span>
                    <input type="text"
                           class="form-control identifier-value-input"
                           id="identifierValue<?php echo $objectId ?>"
                           placeholder="<?php echo __('Scan or enter identifier...') ?>"
                           autocomplete="off">
                    <button type="button"
                            class="btn btn-outline-secondary validate-btn"
                            title="<?php echo __('Validate') ?>">
                        <i class="fa-solid fa-check"></i>
                    </button>
                    <?php if ($hasLookup): ?>
                    <button type="button"
                            class="btn btn-primary lookup-btn"
                            title="<?php echo __('Lookup metadata') ?>">
                        <i class="fa-solid fa-search"></i>
                        <span class="d-none d-md-inline ms-1"><?php echo __('Lookup') ?></span>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="validation-feedback mt-1 small"></div>
            </div>

            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-outline-primary w-100 generate-barcode-btn">
                    <i class="fa-solid fa-qrcode me-1"></i><?php echo __('Generate') ?>
                </button>
            </div>
        </div>

        <?php if ($hasLookup): ?>
        <div class="lookup-results d-none" id="lookupResults<?php echo $objectId ?>">
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>
                    <i class="fa-solid fa-book me-2"></i><?php echo __('Lookup Results') ?>
                </strong>
                <button type="button" class="btn btn-sm btn-outline-secondary close-lookup">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="lookup-content"></div>
            <div class="mt-2">
                <button type="button" class="btn btn-success btn-sm apply-lookup">
                    <i class="fa-solid fa-check me-1"></i><?php echo __('Apply to Form') ?>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm close-lookup ms-2">
                    <?php echo __('Cancel') ?>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <div class="barcode-display d-none" id="barcodeDisplay<?php echo $objectId ?>">
            <hr>
            <div class="row">
                <div class="col-md-6 text-center">
                    <strong class="d-block mb-2"><?php echo __('Linear Barcode') ?></strong>
                    <div class="barcode-linear bg-white p-2 border rounded"></div>
                </div>
                <div class="col-md-6 text-center">
                    <strong class="d-block mb-2"><?php echo __('QR Code') ?></strong>
                    <div class="barcode-qr bg-white p-2 border rounded"></div>
                </div>
            </div>
            <div class="mt-2 text-center">
                <button type="button" class="btn btn-sm btn-outline-secondary print-barcode">
                    <i class="fa-solid fa-print me-1"></i><?php echo __('Print') ?>
                </button>
            </div>
        </div>
    </div>
</div>
