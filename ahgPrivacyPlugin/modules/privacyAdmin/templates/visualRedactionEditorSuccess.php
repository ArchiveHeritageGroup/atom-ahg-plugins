<?php
/**
 * Visual Redaction Editor
 *
 * Uses the IIIF viewer with Annotorious for drawing redaction rectangles on images/PDFs.
 * Integrates with OpenSeadragon for zoomable image viewing.
 */
$objectId = $sf_request->getParameter('id');
$object = $sf_data->getRaw('object');
$docInfo = $sf_data->getRaw('docInfo');
$regions = $sf_data->getRaw('regions') ?? [];

// IIIF Configuration
$baseUrl = sfConfig::get('app_iiif_base_url', 'https://psis.theahg.co.za');
$cantaloupeUrl = sfConfig::get('app_iiif_cantaloupe_url', 'https://psis.theahg.co.za/iiif/2');
$frameworkPath = sfConfig::get('app_iiif_framework_path', '/atom-framework/src/Extensions/IiifViewer');
$viewerId = 'redaction-viewer-' . $objectId;
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-mask me-2 text-danger"></i>Visual Redaction Editor
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'piiScan']); ?>">PII Scanner</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'piiReview']); ?>">Review Queue</a></li>
                    <li class="breadcrumb-item active">Visual Editor</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            <a href="<?php echo url_for([$object, 'module' => 'informationobject']); ?>" class="btn btn-outline-secondary" target="_blank">
                <i class="fas fa-external-link-alt me-1"></i>View Record
            </a>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'piiReview']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('success'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $sf_user->getFlash('error'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

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
                                <small class="text-muted d-block">Redactions</small>
                                <span class="badge bg-danger"><?php echo count($regions); ?></span>
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

    <?php if ($docInfo): ?>
        <!-- Redaction Toolbar -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary tool-btn active" id="tool-select" title="Select/Pan (V)">
                            <i class="fas fa-mouse-pointer"></i> Select
                        </button>
                        <button type="button" class="btn btn-danger tool-btn" id="tool-rect" title="Draw Redaction Rectangle (R)">
                            <i class="far fa-square"></i> Draw Redaction
                        </button>
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        <span class="text-muted small" id="status-text">Ready - Click "Draw Redaction" to start</span>
                        <button type="button" class="btn btn-primary" id="btn-save-redactions">
                            <i class="fas fa-save me-1"></i>Save Redactions
                        </button>
                        <button type="button" class="btn btn-warning" id="btn-apply-redactions">
                            <i class="fas fa-check-double me-1"></i>Apply & Generate
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="row">
            <!-- Viewer Column -->
            <div class="col-lg-9">
                <div class="card" style="background: #1a1a1a;">
                    <div class="card-body p-0">
                        <?php if ($docInfo['is_pdf']): ?>
                            <!-- PDF Viewer with text selection -->
                            <div id="pdf-redaction-container" style="position: relative;">
                                <div class="pdf-toolbar bg-dark text-white p-2 d-flex gap-2 align-items-center">
                                    <button class="btn btn-sm btn-outline-light" id="pdf-prev"><i class="fas fa-chevron-left"></i></button>
                                    <span id="pdf-page-info">Page 1 of <?php echo $docInfo['page_count']; ?></span>
                                    <button class="btn btn-sm btn-outline-light" id="pdf-next"><i class="fas fa-chevron-right"></i></button>
                                    <div class="ms-auto">
                                        <button class="btn btn-sm btn-outline-light" id="pdf-zoom-out"><i class="fas fa-search-minus"></i></button>
                                        <button class="btn btn-sm btn-outline-light" id="pdf-zoom-in"><i class="fas fa-search-plus"></i></button>
                                    </div>
                                </div>
                                <div id="pdf-viewer-area" style="height: 600px; overflow: auto; background: #2a2a2a; display: flex; justify-content: center; padding: 20px;">
                                    <div id="pdf-page-wrapper" style="position: relative; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
                                        <canvas id="pdf-canvas"></canvas>
                                        <div id="fabric-container" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                                            <canvas id="redaction-overlay"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- OpenSeadragon Image Viewer with Annotorious -->
                            <div id="osd-redaction-viewer" style="width: 100%; height: 600px; background: #1a1a1a;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Redaction List Column -->
            <div class="col-lg-3">
                <div class="card bg-dark text-white h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list me-2"></i>Redaction Regions</span>
                        <span class="badge bg-danger" id="region-count"><?php echo count($regions); ?></span>
                    </div>
                    <div class="card-body p-0" id="region-list" style="max-height: 500px; overflow-y: auto;">
                        <?php if (empty($regions)): ?>
                            <div class="text-center text-muted py-4" id="empty-regions">
                                <i class="fas fa-draw-polygon fa-2x mb-2"></i>
                                <p class="mb-0">No redactions yet.<br>Draw rectangles on the image.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($regions as $idx => $region): ?>
                                <div class="region-item p-2 border-bottom border-secondary" data-id="<?php echo $region->id; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="text-danger">#<?php echo $idx + 1; ?></strong>
                                            <small class="text-muted ms-2">Page <?php echo $region->page_number; ?></small>
                                            <?php if ($region->label): ?>
                                                <br><small><?php echo esc_entities($region->label); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-light btn-select-region" title="Select">
                                                <i class="fas fa-crosshairs"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-delete-region" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-outline-danger w-100" id="btn-clear-all" <?php echo empty($regions) ? 'disabled' : ''; ?>>
                            <i class="fas fa-trash-alt me-1"></i>Clear All
                        </button>
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
                        <h6><i class="fas fa-draw-polygon text-danger me-2"></i>Drawing Redactions</h6>
                        <ul class="small mb-0">
                            <li>Click <strong>"Draw Redaction"</strong> button</li>
                            <li>Click and drag to draw a rectangle</li>
                            <li>The area will be blacked out in the output</li>
                            <li>Use Select mode to move/resize regions</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-check-double text-success me-2"></i>Applying Redactions</h6>
                        <ul class="small mb-0">
                            <li>Save your redaction regions</li>
                            <li>Click "Apply & Generate"</li>
                            <li>A new redacted file is created</li>
                            <li>Original file is preserved</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            This record does not have a digital object attached. Visual redaction requires a PDF or image file.
        </div>
    <?php endif; ?>
</div>

<?php if ($docInfo): ?>
<!-- Load required libraries -->
<link rel="stylesheet" href="<?php echo $frameworkPath; ?>/public/css/iiif-viewer.css">
<link rel="stylesheet" href="<?php echo $frameworkPath; ?>/public/viewers/annotorious/annotorious.min.css">
<link rel="stylesheet" href="/plugins/ahgPrivacyPlugin/css/redaction-annotator.css">

<?php if ($docInfo['is_pdf']): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
</script>
<?php else: ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/openseadragon/4.1.0/openseadragon.min.js"></script>
<script src="<?php echo $frameworkPath; ?>/public/viewers/annotorious/openseadragon-annotorious.min.js"></script>
<?php endif; ?>

<script src="/plugins/ahgCorePlugin/js/vendor/fabric.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Visual Redaction Editor initializing...');

    const objectId = <?php echo (int)$objectId; ?>;
    const isPdf = <?php echo $docInfo['is_pdf'] ? 'true' : 'false'; ?>;
    const pageCount = <?php echo $docInfo['page_count'] ?? 1; ?>;
    const documentUrl = '<?php echo addslashes($docInfo['url'] ?? ''); ?>';
    const existingRegions = <?php echo json_encode($regions); ?>;

    console.log('Config:', { objectId, isPdf, pageCount, documentUrl });

    // State
    let currentTool = 'select';
    let currentPage = 1;
    let scale = 1.5;
    let pdfDoc = null;
    let fabricCanvas = null;
    let osdViewer = null;
    let annotorious = null;
    let regions = existingRegions.map(r => ({
        ...r,
        coordinates: typeof r.coordinates === 'string' ? JSON.parse(r.coordinates) : r.coordinates
    }));
    let isDirty = false;
    let isDrawing = false;
    let startPoint = null;
    let tempRect = null;

    // Tool buttons
    document.getElementById('tool-select').addEventListener('click', () => setTool('select'));
    document.getElementById('tool-rect').addEventListener('click', () => setTool('rect'));

    // Action buttons
    document.getElementById('btn-save-redactions').addEventListener('click', saveRedactions);
    document.getElementById('btn-apply-redactions').addEventListener('click', applyRedactions);
    document.getElementById('btn-clear-all')?.addEventListener('click', clearAllRegions);

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;
        if (e.key === 'v' || e.key === 'V') { e.preventDefault(); setTool('select'); }
        else if (e.key === 'r' || e.key === 'R') { e.preventDefault(); setTool('rect'); }
        else if (e.key === 'Delete' || e.key === 'Backspace') deleteSelected();
        else if (e.ctrlKey && e.key === 's') { e.preventDefault(); saveRedactions(); }
    });

    function setTool(tool) {
        console.log('Setting tool:', tool);
        currentTool = tool;
        document.querySelectorAll('.tool-btn').forEach(btn => {
            btn.classList.remove('active', 'btn-danger');
            btn.classList.add('btn-outline-secondary');
        });
        const activeBtn = document.getElementById('tool-' + tool);
        if (activeBtn) {
            activeBtn.classList.add('active');
            activeBtn.classList.remove('btn-outline-secondary');
            if (tool === 'rect') activeBtn.classList.add('btn-danger');
        }

        updateStatus(tool === 'rect' ? 'Draw mode - Click and drag to create redaction' : 'Select mode - Click regions to select');

        if (isPdf && fabricCanvas) {
            fabricCanvas.isDrawingMode = false;
            fabricCanvas.selection = tool === 'select';
            fabricCanvas.getObjects().forEach(obj => {
                obj.selectable = tool === 'select';
                obj.evented = tool === 'select';
            });
            fabricCanvas.hoverCursor = tool === 'rect' ? 'crosshair' : 'move';
            fabricCanvas.defaultCursor = tool === 'rect' ? 'crosshair' : 'default';
            fabricCanvas.renderAll();
            console.log('Fabric canvas updated for tool:', tool);
        } else if (!isPdf && annotorious) {
            if (tool === 'rect') {
                annotorious.setDrawingTool('rect');
                annotorious.setDrawingEnabled(true);
            } else {
                annotorious.setDrawingEnabled(false);
            }
        }
    }

    function updateStatus(text) {
        document.getElementById('status-text').textContent = text;
    }

    function updateRegionCount() {
        document.getElementById('region-count').textContent = regions.length;
        const clearBtn = document.getElementById('btn-clear-all');
        if (clearBtn) clearBtn.disabled = regions.length === 0;
    }

    // Initialize based on document type
    if (isPdf) {
        initPdfViewer();
    } else {
        initImageViewer();
    }

    // ======================
    // PDF Viewer with Fabric.js overlay
    // ======================
    async function initPdfViewer() {
        updateStatus('Loading PDF...');
        console.log('Loading PDF from:', documentUrl);

        try {
            pdfDoc = await pdfjsLib.getDocument(documentUrl).promise;
            console.log('PDF loaded, pages:', pdfDoc.numPages);
            await renderPdfPage(1);
            updateStatus('Ready - Click "Draw Redaction" to start');
        } catch (error) {
            console.error('PDF load error:', error);
            updateStatus('Error loading PDF: ' + error.message);
        }

        // Page navigation
        document.getElementById('pdf-prev').addEventListener('click', () => {
            if (currentPage > 1) renderPdfPage(currentPage - 1);
        });
        document.getElementById('pdf-next').addEventListener('click', () => {
            if (currentPage < pageCount) renderPdfPage(currentPage + 1);
        });
        document.getElementById('pdf-zoom-in').addEventListener('click', () => {
            scale = Math.min(3, scale + 0.25);
            renderPdfPage(currentPage);
        });
        document.getElementById('pdf-zoom-out').addEventListener('click', () => {
            scale = Math.max(0.5, scale - 0.25);
            renderPdfPage(currentPage);
        });
    }

    async function renderPdfPage(pageNum) {
        currentPage = pageNum;
        document.getElementById('pdf-page-info').textContent = `Page ${pageNum} of ${pageCount}`;

        const page = await pdfDoc.getPage(pageNum);
        const viewport = page.getViewport({ scale: scale });

        // Render PDF to canvas
        const pdfCanvas = document.getElementById('pdf-canvas');
        const ctx = pdfCanvas.getContext('2d');
        pdfCanvas.width = viewport.width;
        pdfCanvas.height = viewport.height;

        // Update wrapper size
        const wrapper = document.getElementById('pdf-page-wrapper');
        wrapper.style.width = viewport.width + 'px';
        wrapper.style.height = viewport.height + 'px';

        await page.render({ canvasContext: ctx, viewport: viewport }).promise;
        console.log('PDF page rendered:', pageNum, 'Size:', viewport.width, 'x', viewport.height);

        // Initialize Fabric.js overlay
        initFabricOverlay(viewport.width, viewport.height);
        renderRegionsOnCanvas();
    }

    function initFabricOverlay(width, height) {
        console.log('Initializing Fabric.js overlay:', width, 'x', height);

        // Update container size
        const container = document.getElementById('fabric-container');
        container.style.width = width + 'px';
        container.style.height = height + 'px';

        // Set canvas size
        const overlayCanvas = document.getElementById('redaction-overlay');
        overlayCanvas.width = width;
        overlayCanvas.height = height;
        overlayCanvas.style.width = width + 'px';
        overlayCanvas.style.height = height + 'px';

        if (fabricCanvas) {
            fabricCanvas.dispose();
        }

        fabricCanvas = new fabric.Canvas('redaction-overlay', {
            width: width,
            height: height,
            selection: currentTool === 'select',
            preserveObjectStacking: true
        });

        // Set cursor based on current tool
        fabricCanvas.defaultCursor = currentTool === 'rect' ? 'crosshair' : 'default';
        fabricCanvas.hoverCursor = currentTool === 'rect' ? 'crosshair' : 'move';

        console.log('Fabric canvas created');

        // Drawing events
        fabricCanvas.on('mouse:down', function(opt) {
            console.log('Mouse down, tool:', currentTool, 'target:', opt.target);
            if (currentTool !== 'rect') return;
            if (opt.target) return; // Don't start drawing if clicking on existing object

            isDrawing = true;
            fabricCanvas.selection = false;
            startPoint = fabricCanvas.getPointer(opt.e);
            console.log('Start drawing at:', startPoint);
        });

        fabricCanvas.on('mouse:move', function(opt) {
            if (!isDrawing || !startPoint) return;

            const pointer = fabricCanvas.getPointer(opt.e);

            if (tempRect) {
                fabricCanvas.remove(tempRect);
            }

            const left = Math.min(startPoint.x, pointer.x);
            const top = Math.min(startPoint.y, pointer.y);
            const w = Math.abs(pointer.x - startPoint.x);
            const h = Math.abs(pointer.y - startPoint.y);

            tempRect = new fabric.Rect({
                left: left,
                top: top,
                width: w,
                height: h,
                fill: 'rgba(255,0,0,0.3)',
                stroke: '#ff0000',
                strokeWidth: 2,
                strokeDashArray: [5, 5],
                selectable: false,
                evented: false
            });
            fabricCanvas.add(tempRect);
            fabricCanvas.renderAll();
        });

        fabricCanvas.on('mouse:up', function(opt) {
            console.log('Mouse up, isDrawing:', isDrawing);
            if (!isDrawing || !startPoint) return;

            isDrawing = false;
            fabricCanvas.selection = currentTool === 'select';

            if (tempRect) {
                fabricCanvas.remove(tempRect);
                tempRect = null;
            }

            const pointer = fabricCanvas.getPointer(opt.e);
            const w = Math.abs(pointer.x - startPoint.x);
            const h = Math.abs(pointer.y - startPoint.y);

            console.log('Rectangle size:', w, 'x', h);

            if (w > 10 && h > 10) {
                const coords = {
                    x: Math.min(startPoint.x, pointer.x) / fabricCanvas.width,
                    y: Math.min(startPoint.y, pointer.y) / fabricCanvas.height,
                    width: w / fabricCanvas.width,
                    height: h / fabricCanvas.height
                };
                console.log('Adding region:', coords);
                addRegion(coords);
            }

            startPoint = null;
        });

        fabricCanvas.on('object:modified', function(e) {
            updateRegionFromCanvas(e.target);
        });

        console.log('Fabric events registered');
    }

    // ======================
    // Image Viewer with OpenSeadragon + Annotorious
    // ======================
    function initImageViewer() {
        updateStatus('Loading image...');

        // Build IIIF image URL
        const imageId = documentUrl.replace(/^\/uploads\//, '').replace(/\//g, '%2F');
        const iiifUrl = '<?php echo $cantaloupeUrl; ?>/' + encodeURIComponent(imageId) + '/info.json';

        osdViewer = OpenSeadragon({
            id: 'osd-redaction-viewer',
            tileSources: iiifUrl,
            prefixUrl: 'https://cdnjs.cloudflare.com/ajax/libs/openseadragon/4.1.0/images/',
            showNavigator: true,
            navigatorPosition: 'BOTTOM_RIGHT',
            showRotationControl: true,
            gestureSettingsMouse: { clickToZoom: false }
        });

        osdViewer.addHandler('open', function() {
            initAnnotorious();
            updateStatus('Ready - Click "Draw Redaction" to start');
        });

        osdViewer.addHandler('open-failed', function(e) {
            // Fallback to direct image URL
            osdViewer.open({ type: 'image', url: documentUrl });
        });
    }

    function initAnnotorious() {
        if (!window.Annotorious) {
            console.error('Annotorious not loaded');
            return;
        }

        annotorious = OpenSeadragon.Annotorious(osdViewer, {
            locale: 'auto',
            allowEmpty: true,
            disableEditor: true, // No popup editor for redactions
            formatters: [redactionFormatter]
        });

        // Style redactions as black rectangles
        function redactionFormatter(annotation) {
            return {
                className: 'redaction-annotation',
                style: 'stroke: #ff0000; stroke-width: 3px; fill: rgba(0,0,0,0.5);'
            };
        }

        // Load existing regions as annotations
        regions.forEach((region, idx) => {
            const coords = region.coordinates;
            annotorious.addAnnotation({
                '@context': 'http://www.w3.org/ns/anno.jsonld',
                id: '#redaction-' + region.id,
                type: 'Annotation',
                body: [{ type: 'TextualBody', value: region.label || 'Redaction ' + (idx + 1) }],
                target: {
                    selector: {
                        type: 'FragmentSelector',
                        conformsTo: 'http://www.w3.org/TR/media-frags/',
                        value: `xywh=percent:${coords.x * 100},${coords.y * 100},${coords.width * 100},${coords.height * 100}`
                    }
                },
                regionData: region
            });
        });

        // Handle new annotations
        annotorious.on('createAnnotation', function(annotation) {
            const selector = annotation.target?.selector;
            if (selector && selector.value) {
                const match = selector.value.match(/xywh=percent:([\d.]+),([\d.]+),([\d.]+),([\d.]+)/);
                if (match) {
                    addRegion({
                        x: parseFloat(match[1]) / 100,
                        y: parseFloat(match[2]) / 100,
                        width: parseFloat(match[3]) / 100,
                        height: parseFloat(match[4]) / 100
                    }, annotation);
                }
            }
        });

        annotorious.on('deleteAnnotation', function(annotation) {
            if (annotation.regionData) {
                const idx = regions.findIndex(r => r.id === annotation.regionData.id);
                if (idx > -1) {
                    regions.splice(idx, 1);
                    isDirty = true;
                    updateRegionList();
                }
            }
        });

        annotorious.on('updateAnnotation', function(annotation) {
            if (annotation.regionData) {
                const selector = annotation.target?.selector;
                if (selector && selector.value) {
                    const match = selector.value.match(/xywh=percent:([\d.]+),([\d.]+),([\d.]+),([\d.]+)/);
                    if (match) {
                        annotation.regionData.coordinates = {
                            x: parseFloat(match[1]) / 100,
                            y: parseFloat(match[2]) / 100,
                            width: parseFloat(match[3]) / 100,
                            height: parseFloat(match[4]) / 100
                        };
                        isDirty = true;
                    }
                }
            }
        });

        setTool('select');
    }

    // ======================
    // Region Management
    // ======================
    function addRegion(coords, annotation = null) {
        const region = {
            id: 'new_' + Date.now(),
            page_number: currentPage,
            region_type: 'rectangle',
            coordinates: coords,
            normalized: true,
            source: 'manual',
            status: 'pending',
            label: 'Redaction ' + (regions.length + 1)
        };

        regions.push(region);
        isDirty = true;

        if (annotation) {
            annotation.regionData = region;
        }

        if (isPdf) {
            renderRegionsOnCanvas();
        }
        updateRegionList();
        updateStatus('Redaction added');
    }

    function renderRegionsOnCanvas() {
        if (!fabricCanvas) return;

        // Clear existing
        fabricCanvas.getObjects().slice().forEach(obj => {
            if (obj.regionData) fabricCanvas.remove(obj);
        });

        // Render regions for current page
        const pageRegions = regions.filter(r => r.page_number === currentPage);
        pageRegions.forEach((region, idx) => {
            const coords = region.coordinates;
            const rect = new fabric.Rect({
                left: coords.x * fabricCanvas.width,
                top: coords.y * fabricCanvas.height,
                width: coords.width * fabricCanvas.width,
                height: coords.height * fabricCanvas.height,
                fill: 'rgba(0,0,0,0.5)',
                stroke: region.status === 'applied' ? '#28a745' : '#ff0000',
                strokeWidth: 2,
                strokeDashArray: region.status === 'pending' ? [5, 5] : null,
                selectable: currentTool === 'select',
                evented: currentTool === 'select',
                transparentCorners: false,
                cornerColor: '#fff',
                cornerSize: 8
            });
            rect.regionData = region;
            fabricCanvas.add(rect);
        });

        fabricCanvas.renderAll();
    }

    function updateRegionFromCanvas(canvasObj) {
        if (!canvasObj.regionData) return;
        canvasObj.regionData.coordinates = {
            x: canvasObj.left / fabricCanvas.width,
            y: canvasObj.top / fabricCanvas.height,
            width: (canvasObj.width * canvasObj.scaleX) / fabricCanvas.width,
            height: (canvasObj.height * canvasObj.scaleY) / fabricCanvas.height
        };
        isDirty = true;
    }

    function updateRegionList() {
        const listEl = document.getElementById('region-list');
        const pageRegions = isPdf ? regions.filter(r => r.page_number === currentPage) : regions;

        if (pageRegions.length === 0) {
            listEl.innerHTML = '<div class="text-center text-muted py-4" id="empty-regions"><i class="fas fa-draw-polygon fa-2x mb-2"></i><p class="mb-0">No redactions yet.<br>Draw rectangles on the image.</p></div>';
        } else {
            listEl.innerHTML = pageRegions.map((region, idx) => `
                <div class="region-item p-2 border-bottom border-secondary" data-id="${region.id}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="text-danger">#${idx + 1}</strong>
                            ${isPdf ? '<small class="text-muted ms-2">Page ' + region.page_number + '</small>' : ''}
                            <br><small>${region.label || 'Redaction'}</small>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-danger btn-delete-region" data-id="${region.id}" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');

            // Bind delete buttons
            listEl.querySelectorAll('.btn-delete-region').forEach(btn => {
                btn.addEventListener('click', () => deleteRegionById(btn.dataset.id));
            });
        }

        updateRegionCount();
    }

    function deleteSelected() {
        if (isPdf && fabricCanvas) {
            const active = fabricCanvas.getActiveObjects();
            active.forEach(obj => {
                if (obj.regionData) {
                    const idx = regions.findIndex(r => r.id === obj.regionData.id);
                    if (idx > -1) regions.splice(idx, 1);
                    fabricCanvas.remove(obj);
                }
            });
            fabricCanvas.discardActiveObject();
            fabricCanvas.renderAll();
            isDirty = true;
            updateRegionList();
        }
    }

    function deleteRegionById(id) {
        const idx = regions.findIndex(r => r.id == id);
        if (idx > -1) {
            regions.splice(idx, 1);
            isDirty = true;

            if (isPdf) {
                renderRegionsOnCanvas();
            } else if (annotorious) {
                annotorious.removeAnnotation('#redaction-' + id);
            }
            updateRegionList();
        }
    }

    function clearAllRegions() {
        if (!confirm('Delete all redaction regions?')) return;
        regions = [];
        isDirty = true;
        if (isPdf) renderRegionsOnCanvas();
        else if (annotorious) annotorious.clearAnnotations();
        updateRegionList();
    }

    // ======================
    // Save & Apply
    // ======================
    async function saveRedactions() {
        if (!isDirty && regions.every(r => !String(r.id).startsWith('new_'))) {
            updateStatus('No changes to save');
            return true;
        }

        if (regions.length === 0) {
            updateStatus('No regions to save');
            return true;
        }

        updateStatus('Saving...');
        console.log('Saving regions:', regions);

        try {
            const payload = {
                object_id: objectId,
                page: isPdf ? currentPage : 1,
                regions: regions
            };
            console.log('Save payload:', payload);

            const response = await fetch('<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'saveVisualRedaction']); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const text = await response.text();
                console.error('Save server error:', response.status, text);
                updateStatus('Server error: ' + response.status);
                return false;
            }

            const text = await response.text();
            console.log('Save response:', text);

            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                updateStatus('Invalid response from server');
                return false;
            }

            if (data.success) {
                isDirty = false;
                if (data.regions) {
                    regions = data.regions.map(r => ({
                        ...r,
                        coordinates: typeof r.coordinates === 'string' ? JSON.parse(r.coordinates) : r.coordinates
                    }));
                }
                updateRegionList();
                if (isPdf) renderRegionsOnCanvas();
                updateStatus('Saved successfully (' + (data.saved_count || regions.length) + ' regions)');
                return true;
            } else {
                updateStatus('Error: ' + (data.error || 'Save failed'));
                return false;
            }
        } catch (error) {
            console.error('Save error:', error);
            updateStatus('Error saving: ' + error.message);
            return false;
        }
    }

    async function applyRedactions() {
        // Check if there are any regions to apply
        if (regions.length === 0) {
            alert('No redaction regions to apply. Please draw some redaction areas first.');
            return;
        }

        // Save first if there are unsaved changes
        if (isDirty || regions.some(r => String(r.id).startsWith('new_'))) {
            updateStatus('Saving regions first...');
            await saveRedactions();
        }

        if (!confirm('Apply all redactions?\n\nThis will mark all regions as applied. When users view this document, the marked areas will be blacked out automatically.')) return;

        updateStatus('Applying redactions...');

        try {
            const response = await fetch('<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'applyVisualRedactions']); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ object_id: objectId })
            });

            // Check response status
            if (!response.ok) {
                const text = await response.text();
                console.error('Server error:', response.status, text);
                updateStatus('Server error: ' + response.status);
                alert('Server error: ' + response.status + '\n\nCheck the console for details.');
                return;
            }

            const text = await response.text();
            console.log('Response:', text);

            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError, 'Response was:', text);
                updateStatus('Invalid response from server');
                alert('Server returned invalid JSON. Check the console for details.');
                return;
            }

            if (data.success) {
                updateStatus(data.message || 'Redactions applied!');
                alert((data.message || 'Redactions applied!') + '\n\nUsers will now see the redacted version when viewing this document.');

                // Offer to view the record
                if (data.view_url && confirm('Would you like to view the record now?')) {
                    window.location.href = data.view_url;
                } else {
                    // Reload regions to show applied status
                    setTimeout(() => location.reload(), 500);
                }
            } else {
                updateStatus('Error: ' + (data.error || 'Apply failed'));
                alert('Error: ' + (data.error || 'Apply failed'));
            }
        } catch (error) {
            console.error('Apply error:', error);
            updateStatus('Error applying: ' + error.message);
            alert('Error: ' + error.message);
        }
    }

    // Initial region list render
    updateRegionList();
});
</script>

<style>
.redaction-annotation {
    stroke: #ff0000 !important;
    stroke-width: 3px !important;
    fill: rgba(0, 0, 0, 0.5) !important;
}
.a9s-annotationlayer .a9s-annotation.redaction-annotation .a9s-inner {
    stroke: #ff0000;
    fill: rgba(0, 0, 0, 0.5);
}
.tool-btn.active {
    font-weight: bold;
}
.region-item:hover {
    background: rgba(255, 255, 255, 0.1);
}
#pdf-redaction-container {
    background: #1a1a1a;
}
#pdf-page-wrapper {
    position: relative;
}
#pdf-canvas {
    display: block;
}
#fabric-container {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 10;
}
#fabric-container .canvas-container {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
}
#fabric-container canvas {
    position: absolute;
    top: 0;
    left: 0;
}
/* Ensure upper canvas receives pointer events */
.canvas-container .upper-canvas {
    cursor: crosshair;
}
</style>
<?php endif; ?>
