<?php
/**
 * NER PDF Overlay Viewer (Issue #20)
 *
 * Displays PDF documents with approved/linked NER entities highlighted.
 * Uses PDF.js for rendering and text layer search for highlighting.
 */
$objectId = $sf_data->getRaw('objectId');
$object = $sf_data->getRaw('object');
$docInfo = $sf_data->getRaw('docInfo');
$entityCounts = $sf_data->getRaw('entityCounts') ?? [];
$totalEntities = $sf_data->getRaw('totalEntities') ?? 0;

// Entity type colors
$typeColors = [
    'PERSON' => ['bg' => '#4e79a7', 'label' => 'Person'],
    'PER' => ['bg' => '#4e79a7', 'label' => 'Person'],
    'ORG' => ['bg' => '#59a14f', 'label' => 'Organization'],
    'GPE' => ['bg' => '#e15759', 'label' => 'Place'],
    'LOC' => ['bg' => '#e15759', 'label' => 'Location'],
    'DATE' => ['bg' => '#b07aa1', 'label' => 'Date'],
    'TIME' => ['bg' => '#b07aa1', 'label' => 'Time'],
    'EVENT' => ['bg' => '#76b7b2', 'label' => 'Event'],
    'WORK_OF_ART' => ['bg' => '#ff9da7', 'label' => 'Work'],
];
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-file-pdf me-2 text-danger"></i>NER Entity Overlay
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ai', 'action' => 'review']); ?>">NER Review</a></li>
                    <li class="breadcrumb-item active">PDF Overlay</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            <a href="<?php echo url_for([$object, 'module' => 'informationobject']); ?>" class="btn btn-outline-secondary" target="_blank">
                <i class="fas fa-external-link-alt me-1"></i>View Record
            </a>
            <a href="<?php echo url_for(['module' => 'ai', 'action' => 'review']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <!-- Document Info Card -->
    <div class="card bg-dark text-white mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-1"><?php echo esc_entities($object->getTitle(['cultureFallback' => true]) ?? 'Untitled'); ?></h5>
                    <small class="text-muted"><?php echo esc_entities($object->identifier ?? $object->slug ?? ''); ?></small>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end gap-4">
                        <?php if ($docInfo): ?>
                            <div class="text-end">
                                <small class="text-muted d-block">Type</small>
                                <span><?php echo $docInfo['is_pdf'] ? '<i class="fas fa-file-pdf text-danger"></i> PDF' : '<i class="fas fa-image text-info"></i> Image'; ?></span>
                            </div>
                            <?php if ($docInfo['is_pdf']): ?>
                                <div class="text-end">
                                    <small class="text-muted d-block">Pages</small>
                                    <span><?php echo $docInfo['page_count']; ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="text-end">
                                <small class="text-muted d-block">Entities</small>
                                <span class="badge bg-info"><?php echo $totalEntities; ?></span>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0 py-1 px-3">
                                <i class="fas fa-exclamation-triangle me-1"></i>No digital object found
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($docInfo && $docInfo['is_pdf']): ?>
        <!-- Entity Type Toggles -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap" id="entity-toggles">
                        <span class="text-muted small me-2"><i class="fas fa-eye me-1"></i>Show:</span>
                        <?php foreach ($entityCounts as $type => $count): ?>
                            <?php $config = $typeColors[$type] ?? ['bg' => '#bababa', 'label' => $type]; ?>
                            <label class="form-check form-check-inline mb-0 entity-toggle-label">
                                <input type="checkbox" class="form-check-input entity-type-toggle"
                                       data-type="<?php echo $type; ?>" checked>
                                <span class="badge" style="background: <?php echo $config['bg']; ?>;">
                                    <?php echo $config['label']; ?> (<?php echo $count; ?>)
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" id="btn-show-all" title="Show All">
                            <i class="fas fa-eye"></i> All
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btn-hide-all" title="Hide All">
                            <i class="fas fa-eye-slash"></i> None
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- PDF Viewer -->
            <div class="col-lg-9">
                <div class="card" style="background: #1a1a1a;">
                    <div class="card-body p-0">
                        <!-- PDF Toolbar -->
                        <div class="pdf-overlay-toolbar bg-dark text-white p-2 d-flex gap-2 align-items-center">
                            <button class="btn btn-sm btn-outline-light" id="pdf-prev" disabled>
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span id="pdf-page-info">Page 1 of <?php echo $docInfo['page_count']; ?></span>
                            <button class="btn btn-sm btn-outline-light" id="pdf-next" <?php echo $docInfo['page_count'] <= 1 ? 'disabled' : ''; ?>>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            <div class="ms-3">
                                <button class="btn btn-sm btn-outline-light" id="pdf-zoom-out">
                                    <i class="fas fa-search-minus"></i>
                                </button>
                                <span class="mx-2 small" id="zoom-level">100%</span>
                                <button class="btn btn-sm btn-outline-light" id="pdf-zoom-in">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                            </div>
                            <div class="ms-auto">
                                <span class="small text-muted" id="status-text">Loading...</span>
                            </div>
                        </div>

                        <!-- PDF Viewer Area -->
                        <div id="pdf-viewer-area" style="height: 650px; overflow: auto; background: #2a2a2a; display: flex; justify-content: center; padding: 20px;">
                            <div id="pdf-page-wrapper" style="position: relative; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
                                <canvas id="pdf-canvas"></canvas>
                                <div id="text-layer" class="textLayer"></div>
                                <div id="highlight-layer" class="ner-highlight-layer"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Entity Panel -->
            <div class="col-lg-3">
                <div class="card bg-dark text-white h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-brain me-2"></i>Detected Entities</span>
                        <span class="badge bg-info" id="entity-count"><?php echo $totalEntities; ?></span>
                    </div>
                    <div class="card-body p-0" id="entity-list" style="max-height: 550px; overflow-y: auto;">
                        <div class="text-center text-muted py-4" id="entity-loading">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            Loading entities...
                        </div>
                    </div>
                    <div class="card-footer border-top border-secondary">
                        <div class="small text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Click an entity to jump to its location in the document.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Card -->
        <div class="card mt-4">
            <div class="card-header bg-light">
                <i class="fas fa-question-circle me-2"></i>How to Use
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6><i class="fas fa-eye text-primary me-2"></i>Viewing Entities</h6>
                        <ul class="small mb-0">
                            <li>Entities are highlighted directly on the PDF text</li>
                            <li>Colors indicate entity type (person, org, place, date)</li>
                            <li>Hover over highlights to see entity details</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-filter text-success me-2"></i>Filtering</h6>
                        <ul class="small mb-0">
                            <li>Use the toggles above to show/hide entity types</li>
                            <li>Click "All" or "None" for quick filtering</li>
                            <li>Entity panel shows all entities for current page</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-mouse-pointer text-info me-2"></i>Navigation</h6>
                        <ul class="small mb-0">
                            <li>Click an entity in the panel to scroll to it</li>
                            <li>Use page navigation for multi-page PDFs</li>
                            <li>Zoom controls adjust the view size</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($docInfo && !$docInfo['is_pdf']): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            This feature currently supports PDF documents only. The attached file is: <?php echo esc_entities($docInfo['mime_type']); ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            This record does not have a digital object attached. The PDF overlay viewer requires a PDF document.
        </div>
    <?php endif; ?>
</div>

<?php if ($docInfo && $docInfo['is_pdf']): ?>
<!-- Load PDF.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
</script>

<!-- Load NER PDF Overlay CSS -->
<link rel="stylesheet" href="/plugins/ahgAIPlugin/web/css/ner-pdf-overlay.css">

<!-- Load NER PDF Overlay JS -->
<script src="/plugins/ahgAIPlugin/web/js/ner-pdf-overlay.js"></script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize NER PDF Overlay
    const overlay = new NerPdfOverlay({
        containerId: 'pdf-viewer-area',
        pdfUrl: '<?php echo addslashes($docInfo['url']); ?>',
        objectId: <?php echo (int)$objectId; ?>,
        pageCount: <?php echo (int)$docInfo['page_count']; ?>,
        apiUrl: '<?php echo url_for(['module' => 'ai', 'action' => 'getApprovedEntities', 'id' => $objectId]); ?>',
    });

    overlay.init();
});
</script>
<?php endif; ?>
