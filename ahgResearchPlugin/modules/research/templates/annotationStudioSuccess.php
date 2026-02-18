<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php $nonce = sfConfig::get('csp_nonce', ''); $nonceAttr = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce).'"' : ''; ?>
<meta name="csp-nonce" content="<?php echo htmlspecialchars(preg_replace('/^nonce=/', '', $nonce)); ?>">

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <?php if (!empty($objectSlug)): ?>
            <li class="breadcrumb-item"><a href="/<?php echo htmlspecialchars($objectSlug); ?>"><?php echo htmlspecialchars($objectTitle); ?></a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active">Annotation Studio</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Annotation Studio: <?php echo htmlspecialchars($objectTitle); ?></h1>
    <div class="d-flex gap-2">
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'exportAnnotationsIIIF', 'object_id' => $objectId]); ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download me-1"></i>Export IIIF</a>
        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#importIiifModal"><i class="fas fa-file-import me-1"></i>Import IIIF</button>
        <?php if (!empty($objectSlug)): ?>
            <a href="/<?php echo htmlspecialchars($objectSlug); ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fas fa-external-link-alt me-1"></i>View Record</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($imageUrl)): ?>
<!-- Image Annotation Canvas -->
<div class="row">
    <div class="col-lg-9">
        <div class="card">
            <!-- Drawing Toolbar -->
            <div class="card-header p-2">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="btn-group" role="group">
                        <button type="button" class="tool-btn btn btn-sm btn-primary active" data-tool="select" title="Select (V)"><i class="fas fa-mouse-pointer"></i></button>
                        <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="rect" title="Rectangle (R)"><i class="far fa-square"></i></button>
                        <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="circle" title="Circle (C)"><i class="far fa-circle"></i></button>
                        <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="arrow" title="Arrow (A)"><i class="fas fa-long-arrow-alt-right"></i></button>
                        <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="freehand" title="Freehand (F)"><i class="fas fa-pencil-alt"></i></button>
                        <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="text" title="Text (T)"><i class="fas fa-font"></i></button>
                        <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="marker" title="Marker (M)"><i class="fas fa-map-marker-alt"></i></button>
                    </div>
                    <div class="d-flex align-items-center gap-1 ms-2">
                        <label class="form-label mb-0 small">Color:</label>
                        <input type="color" id="annColor" value="#FF0000" class="border rounded" style="width:32px;height:26px;padding:1px;cursor:pointer;">
                    </div>
                    <div class="btn-group ms-2" role="group">
                        <button type="button" class="btn btn-sm btn-outline-info" id="toggleAnnotations" title="Show/Hide Annotations"><i class="fas fa-eye"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="deleteSelected" title="Delete Selected"><i class="fas fa-trash"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-warning" id="undoLast" title="Undo Last"><i class="fas fa-undo"></i></button>
                    </div>
                    <div class="btn-group ms-2" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="zoomIn" title="Zoom In"><i class="fas fa-search-plus"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="zoomOut" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="zoomFit" title="Fit to View"><i class="fas fa-expand"></i></button>
                    </div>
                    <span class="ms-auto small text-muted" id="statusText">Loading...</span>
                </div>
            </div>
            <!-- Canvas Area -->
            <div class="card-body p-0" id="canvasWrapper" style="background:#1a1a2e;overflow:auto;min-height:500px;display:flex;justify-content:center;align-items:center;padding:20px !important;">
                <canvas id="annotationCanvas"></canvas>
            </div>
            <div class="card-footer py-1 d-flex justify-content-between small text-muted">
                <span id="canvasInfo">-</span>
                <span id="annotationCount">0 annotations</span>
            </div>
        </div>
    </div>

    <!-- Right panel: Annotations list + Create form -->
    <div class="col-lg-3">
        <!-- Save drawn annotations -->
        <div class="card mb-3">
            <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-save me-1"></i>Save Drawn Annotation</h6></div>
            <div class="card-body py-2">
                <p class="small text-muted mb-2">Draw a shape on the image, then save it as a W3C annotation.</p>
                <div class="mb-2">
                    <label class="form-label form-label-sm mb-1">Motivation</label>
                    <select id="newMotivation" class="form-select form-select-sm">
                        <option value="commenting">Commenting</option>
                        <option value="describing">Describing</option>
                        <option value="classifying">Classifying</option>
                        <option value="tagging">Tagging</option>
                        <option value="highlighting">Highlighting</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label form-label-sm mb-1">Note</label>
                    <textarea id="newBody" class="form-control form-control-sm" rows="2" placeholder="Annotation text..."></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label form-label-sm mb-1">Visibility</label>
                    <select id="newVisibility" class="form-select form-select-sm">
                        <option value="private">Private</option>
                        <option value="shared">Shared</option>
                        <option value="public">Public</option>
                    </select>
                </div>
                <button id="saveAnnotationBtn" class="btn btn-primary btn-sm w-100"><i class="fas fa-plus me-1"></i>Save Selected Region</button>
            </div>
        </div>

        <!-- Existing annotations -->
        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list me-1"></i>Annotations (<?php echo count($annotations); ?>)</h6>
            </div>
            <div class="card-body py-0 px-0" style="max-height:400px;overflow-y:auto;">
                <?php if (empty($annotations)): ?>
                    <p class="text-muted small text-center py-3 mb-0">No annotations yet. Draw on the image and save.</p>
                <?php else: ?>
                    <?php foreach ($annotations as $ann): ?>
                    <?php
                        // Unescape from sfOutputEscaperObjectDecorator (Symfony HTML-encodes string properties)
                        $rawBody = sfOutputEscaper::unescape($ann->body_json ?? null);
                        if (is_string($rawBody)) {
                            $body = json_decode($rawBody, true) ?: [];
                        } elseif (is_object($rawBody)) {
                            $body = json_decode(json_encode($rawBody), true) ?: [];
                        } elseif (is_array($rawBody)) {
                            $body = $rawBody;
                        } else {
                            $body = [];
                        }
                        $bodyText = $body['value'] ?? $body['text'] ?? '';
                        $targets = [];
                        try {
                            $targets = \Illuminate\Database\Capsule\Manager::table('research_annotation_target')
                                ->where('annotation_id', $ann->id)->orderBy('id')->get()->toArray();
                        } catch (\Exception $e) {}
                        $hasFragment = false;
                        $selectorData = [];
                        foreach ($targets as $t) {
                            if (($t->selector_type ?? '') === 'FragmentSelector') {
                                $hasFragment = true;
                                $selectorData = is_string($t->selector_json ?? null) ? json_decode($t->selector_json, true) : [];
                                break;
                            }
                        }
                    ?>
                    <div class="border-bottom p-2 annotation-list-item <?php echo $hasFragment ? 'has-region' : ''; ?>"
                         data-annotation-id="<?php echo (int) $ann->id; ?>"
                         data-selector="<?php echo htmlspecialchars(json_encode($selectorData)); ?>"
                         style="cursor:pointer;font-size:0.85em;">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="badge bg-info" style="font-size:0.7em;"><?php echo htmlspecialchars($ann->motivation ?? 'commenting'); ?></span>
                            <div class="d-flex gap-1">
                                <button class="btn btn-link btn-sm p-0 text-warning edit-ann-btn" data-id="<?php echo (int) $ann->id; ?>" data-body="<?php echo htmlspecialchars($bodyText); ?>" data-motivation="<?php echo htmlspecialchars($ann->motivation ?? 'commenting'); ?>" title="Edit"><i class="fas fa-edit" style="font-size:0.75em;"></i></button>
                                <button class="btn btn-link btn-sm p-0 text-danger delete-ann-btn" data-id="<?php echo (int) $ann->id; ?>" title="Delete"><i class="fas fa-trash" style="font-size:0.75em;"></i></button>
                            </div>
                        </div>
                        <div class="mt-1 text-truncate"><?php echo htmlspecialchars($bodyText ?: '(no text)'); ?></div>
                        <?php if ($hasFragment): ?>
                            <small class="text-success"><i class="fas fa-vector-square me-1"></i>Has region</small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- No image available - text-only mode -->
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>No digital object image found for this record. You can still create text annotations below.
</div>
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Annotations (<?php echo count($annotations); ?>)</h5></div>
            <div class="card-body">
                <?php if (empty($annotations)): ?>
                    <p class="text-muted">No annotations yet.</p>
                <?php else: ?>
                    <?php foreach ($annotations as $ann): ?>
                    <?php
                        $rawBody2 = sfOutputEscaper::unescape($ann->body_json ?? null);
                        $body = is_string($rawBody2) ? json_decode($rawBody2, true) ?: [] : (is_object($rawBody2) ? json_decode(json_encode($rawBody2), true) ?: [] : ($rawBody2 ?? []));
                    ?>
                    <div class="border rounded p-2 mb-2">
                        <span class="badge bg-info"><?php echo htmlspecialchars($ann->motivation ?? 'commenting'); ?></span>
                        <span class="ms-2"><?php echo htmlspecialchars($body['value'] ?? $body['text'] ?? ''); ?></span>
                        <button class="btn btn-sm btn-link text-danger float-end delete-ann-btn" data-id="<?php echo (int) $ann->id; ?>"><i class="fas fa-trash"></i></button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Create Annotation</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Motivation</label>
                    <select id="newMotivation" class="form-select form-select-sm">
                        <option value="commenting">Commenting</option>
                        <option value="describing">Describing</option>
                        <option value="classifying">Classifying</option>
                        <option value="tagging">Tagging</option>
                        <option value="highlighting">Highlighting</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Body Text</label>
                    <textarea id="newBody" class="form-control" rows="3" placeholder="Enter annotation..."></textarea>
                </div>
                <button id="createTextAnnotationBtn" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i>Create</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Edit Annotation Modal -->
<div class="modal fade" id="editAnnotationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Annotation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="editAnnotationId">
                <div class="mb-3">
                    <label class="form-label">Motivation</label>
                    <select id="editMotivation" class="form-select form-select-sm">
                        <option value="commenting">Commenting</option>
                        <option value="describing">Describing</option>
                        <option value="classifying">Classifying</option>
                        <option value="tagging">Tagging</option>
                        <option value="highlighting">Highlighting</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Body Text</label>
                    <textarea id="editAnnotationBody" class="form-control" rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" id="saveEditAnnotation" class="btn btn-primary">Save</button></div>
        </div>
    </div>
</div>

<!-- IIIF Import Modal -->
<div class="modal fade" id="importIiifModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Import IIIF Annotations</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted">Upload a W3C Web Annotation JSON-LD file.</p>
                <div class="mb-3">
                    <label class="form-label">JSON-LD File</label>
                    <input type="file" id="iiifFile" class="form-control" accept=".json,.jsonld">
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" id="importIiifBtn" class="btn btn-primary">Import</button></div>
        </div>
    </div>
</div>

<!-- Fabric.js -->
<script src="/plugins/ahgCorePlugin/web/js/vendor/fabric.min.js" <?php echo $nonceAttr; ?>></script>

<script <?php echo $nonceAttr; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var objectId = <?php echo (int) $objectId; ?>;
    var imageUrl = <?php echo json_encode($imageUrl ?? ''); ?>;

    // Text-only mode (no image)
    var textOnlyBtn = document.getElementById('createTextAnnotationBtn');
    if (textOnlyBtn) {
        textOnlyBtn.addEventListener('click', function() {
            var bodyText = document.getElementById('newBody').value.trim();
            if (!bodyText) { alert('Please enter annotation text.'); return; }
            fetch('/research/annotation-v2/create', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    motivation: document.getElementById('newMotivation').value,
                    body: { type: 'TextualBody', value: bodyText, format: 'text/plain' },
                    visibility: 'private',
                    targets: [{ source_type: 'information_object', source_id: objectId }]
                })
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.success) location.reload();
                else alert(d.error || 'Error');
            }).catch(function(err) { alert('Network error: ' + err.message); });
        });
    }

    // If no image, skip canvas setup
    if (!imageUrl) {
        bindDeleteButtons();
        return;
    }

    // ========================
    // FABRIC.JS CANVAS SETUP
    // ========================
    var canvasEl = document.getElementById('annotationCanvas');
    var wrapper = document.getElementById('canvasWrapper');
    var canvas = new fabric.Canvas(canvasEl, { selection: true, preserveObjectStacking: true });
    var currentTool = 'select';
    var currentColor = '#FF0000';
    var isDrawing = false;
    var startPoint = null;
    var tempShape = null;
    var lastDrawnShape = null;
    var annotationsVisible = true;
    var imageLoaded = false;
    var originalSize = { width: 0, height: 0 };
    var scale = 1;
    var MIN_CANVAS_WIDTH = 600;
    var MIN_CANVAS_HEIGHT = 300;

    function setStatus(text) {
        var el = document.getElementById('statusText');
        if (el) el.textContent = text;
    }

    function updateCount() {
        var objs = canvas.getObjects().filter(function(o) { return o.annotationData; });
        var el = document.getElementById('annotationCount');
        if (el) el.textContent = objs.length + ' drawn';
    }

    // Load image - upscale small images so drawing is practical
    function loadImage() {
        setStatus('Loading image...');
        var maxWidth = wrapper.clientWidth - 40;
        var maxHeight = Math.max(wrapper.clientHeight - 40, 400);
        if (maxWidth < 400) maxWidth = 600;

        fabric.Image.fromURL(imageUrl + (imageUrl.indexOf('?') >= 0 ? '&' : '?') + '_t=' + Date.now(), function(fabricImg) {
            if (!fabricImg || !fabricImg.width) {
                setStatus('Failed to load image');
                return;
            }
            originalSize = { width: fabricImg.width, height: fabricImg.height };

            // Compute scale: fit to container, but UPSCALE small images
            var scaleX = maxWidth / fabricImg.width;
            var scaleY = maxHeight / fabricImg.height;
            scale = Math.min(scaleX, scaleY);

            // Ensure minimum canvas dimensions for usability
            var canvasW = Math.round(fabricImg.width * scale);
            var canvasH = Math.round(fabricImg.height * scale);
            if (canvasW < MIN_CANVAS_WIDTH && fabricImg.width < MIN_CANVAS_WIDTH) {
                scale = MIN_CANVAS_WIDTH / fabricImg.width;
                canvasW = MIN_CANVAS_WIDTH;
                canvasH = Math.round(fabricImg.height * scale);
            }
            if (canvasH < MIN_CANVAS_HEIGHT && fabricImg.height < MIN_CANVAS_HEIGHT) {
                var newScale = MIN_CANVAS_HEIGHT / fabricImg.height;
                if (newScale > scale) {
                    scale = newScale;
                    canvasW = Math.round(fabricImg.width * scale);
                    canvasH = MIN_CANVAS_HEIGHT;
                }
            }
            if (scale < 0.1) scale = 0.1;

            canvas.setWidth(canvasW);
            canvas.setHeight(canvasH);

            fabricImg.set({ scaleX: scale, scaleY: scale, left: 0, top: 0, originX: 'left', originY: 'top' });
            canvas.setBackgroundImage(fabricImg, function() {
                canvas.renderAll();
                imageLoaded = true;
                document.getElementById('canvasInfo').textContent = originalSize.width + 'x' + originalSize.height + ' at ' + Math.round(scale * 100) + '%';
                setStatus('Ready - select a tool to annotate');
                loadExistingRegions();
            });
        }, { crossOrigin: 'anonymous' });
    }

    // Load existing annotations with FragmentSelector regions onto canvas
    function loadExistingRegions() {
        var items = document.querySelectorAll('.annotation-list-item.has-region');
        items.forEach(function(item) {
            var annId = item.dataset.annotationId;
            var selectorStr = item.dataset.selector || '{}';
            var sel;
            try { sel = JSON.parse(selectorStr); } catch(e) { return; }
            var xywh = sel.value || '';
            var match = xywh.match(/xywh=pixel:(\d+),(\d+),(\d+),(\d+)/);
            if (!match) return;

            var shapeType = sel.shapeType || 'rect';
            var sd = sel.shapeData || {};
            var color = sd.stroke || '#00BFFF';
            var sw = sd.strokeWidth || 2;
            var obj = null;

            if (shapeType === 'circle' || shapeType === 'ellipse') {
                // Recreate ellipse from stored rx/ry or compute from bounding box
                var rx = sd.rx ? sd.rx * scale : (parseInt(match[3]) * scale) / 2;
                var ry = sd.ry ? sd.ry * scale : (parseInt(match[4]) * scale) / 2;
                obj = new fabric.Ellipse({
                    left: parseInt(match[1]) * scale,
                    top: parseInt(match[2]) * scale,
                    rx: rx, ry: ry,
                    stroke: color, strokeWidth: sw,
                    fill: 'rgba(' + hexToRgb(color) + ',0.15)',
                    selectable: true, evented: true
                });
            } else if (shapeType === 'arrow' && sd.x1 !== undefined) {
                // Recreate arrow from stored endpoints
                var x1 = sd.x1 * scale, y1 = sd.y1 * scale;
                var x2 = sd.x2 * scale, y2 = sd.y2 * scale;
                var angle = Math.atan2(y2 - y1, x2 - x1);
                var line = new fabric.Line([x1, y1, x2, y2], {
                    stroke: color, strokeWidth: sw, fill: null,
                    selectable: false, evented: false
                });
                var head = new fabric.Triangle({
                    left: x2, top: y2, width: 15, height: 15,
                    fill: color, angle: (angle * 180) / Math.PI + 90,
                    originX: 'center', originY: 'center',
                    selectable: false, evented: false
                });
                obj = new fabric.Group([line, head], { selectable: true, evented: true });
            } else if (shapeType === 'text' && sd.text) {
                obj = new fabric.IText(sd.text, {
                    left: parseInt(match[1]) * scale,
                    top: parseInt(match[2]) * scale,
                    fontSize: sd.fontSize || 16,
                    fill: color, backgroundColor: 'rgba(255,255,255,0.9)',
                    padding: 5, selectable: true, evented: true
                });
            } else if (shapeType === 'marker') {
                var idx = sd.markerIndex || '?';
                var c = new fabric.Circle({ radius: 14, fill: color, originX: 'center', originY: 'center' });
                var n = new fabric.Text(String(idx), { fontSize: 14, fill: '#FFF', fontWeight: 'bold', originX: 'center', originY: 'center' });
                obj = new fabric.Group([c, n], {
                    left: parseInt(match[1]) * scale,
                    top: parseInt(match[2]) * scale,
                    selectable: true, evented: true
                });
            } else {
                // Default: rectangle
                obj = new fabric.Rect({
                    left: parseInt(match[1]) * scale,
                    top: parseInt(match[2]) * scale,
                    width: parseInt(match[3]) * scale,
                    height: parseInt(match[4]) * scale,
                    stroke: color, strokeWidth: sw,
                    fill: 'rgba(' + hexToRgb(color) + ',0.15)',
                    selectable: true, evented: true
                });
            }

            if (obj) {
                obj.annotationData = {
                    id: 'saved_' + annId,
                    type: shapeType,
                    label: 'Saved annotation #' + annId,
                    color: color,
                    savedAnnotationId: annId
                };
                canvas.add(obj);
            }
        });
        canvas.renderAll();
        updateCount();
    }

    // Tool selection
    function setTool(tool) {
        currentTool = tool;
        var cursors = { select: 'default', rect: 'crosshair', circle: 'crosshair', arrow: 'crosshair', freehand: 'crosshair', text: 'text', marker: 'crosshair' };
        canvas.defaultCursor = cursors[tool] || 'default';
        canvas.hoverCursor = tool === 'select' ? 'move' : cursors[tool];
        canvas.isDrawingMode = (tool === 'freehand');
        if (tool === 'freehand') {
            canvas.freeDrawingBrush.color = currentColor;
            canvas.freeDrawingBrush.width = 3;
        }
        setStatus('Tool: ' + tool);
    }

    // Toolbar clicks
    document.querySelectorAll('.tool-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            setTool(btn.dataset.tool);
            document.querySelectorAll('.tool-btn').forEach(function(b) {
                b.classList.remove('active', 'btn-primary');
                b.classList.add('btn-secondary');
            });
            btn.classList.remove('btn-secondary');
            btn.classList.add('active', 'btn-primary');
        });
    });

    document.getElementById('annColor').addEventListener('change', function(e) {
        currentColor = e.target.value;
    });

    // Canvas mouse events
    canvas.on('mouse:down', function(e) {
        if (currentTool === 'select' || currentTool === 'freehand') return;
        var pointer = canvas.getPointer(e.e);
        isDrawing = true;
        startPoint = pointer;
        if (currentTool === 'text') {
            var text = prompt('Enter annotation text:');
            if (text) {
                var label = new fabric.IText(text, {
                    left: pointer.x, top: pointer.y, fontSize: 16,
                    fill: currentColor, backgroundColor: 'rgba(255,255,255,0.9)',
                    padding: 5, selectable: true, evented: true
                });
                label.annotationData = { id: 'ann_' + Date.now(), type: 'text', label: text, color: currentColor, created_at: new Date().toISOString() };
                canvas.add(label);
                lastDrawnShape = label;
                updateCount();
            }
            isDrawing = false;
        } else if (currentTool === 'marker') {
            var notes = prompt('Enter marker note:');
            var idx = canvas.getObjects().filter(function(o) { return o.annotationData; }).length + 1;
            var circle = new fabric.Circle({ radius: 14, fill: currentColor, originX: 'center', originY: 'center' });
            var number = new fabric.Text(idx.toString(), { fontSize: 14, fill: '#FFF', fontWeight: 'bold', originX: 'center', originY: 'center' });
            var marker = new fabric.Group([circle, number], { left: pointer.x, top: pointer.y, selectable: true, evented: true });
            marker.annotationData = { id: 'ann_' + Date.now(), type: 'marker', label: 'Marker ' + idx, notes: notes || '', color: currentColor, created_at: new Date().toISOString() };
            canvas.add(marker);
            lastDrawnShape = marker;
            updateCount();
            isDrawing = false;
        }
    });

    canvas.on('mouse:move', function(e) {
        if (!isDrawing) return;
        var pointer = canvas.getPointer(e.e);
        if (tempShape) canvas.remove(tempShape);
        tempShape = createShape(startPoint, pointer, true);
        if (tempShape) canvas.add(tempShape);
    });

    canvas.on('mouse:up', function(e) {
        if (!isDrawing) return;
        var pointer = canvas.getPointer(e.e);
        isDrawing = false;
        if (tempShape) { canvas.remove(tempShape); tempShape = null; }
        var w = Math.abs(pointer.x - startPoint.x), h = Math.abs(pointer.y - startPoint.y);
        if (w < 5 && h < 5) return;
        var shape = createShape(startPoint, pointer, false);
        if (!shape) return;
        shape.annotationData = {
            id: 'ann_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8),
            type: currentTool,
            label: currentTool + ' annotation',
            color: currentColor,
            created_at: new Date().toISOString()
        };
        canvas.add(shape);
        canvas.setActiveObject(shape);
        lastDrawnShape = shape;
        updateCount();
        setStatus('Shape drawn - enter note text and click Save');
    });

    // Freehand path completed
    canvas.on('path:created', function(e) {
        if (e.path) {
            e.path.annotationData = {
                id: 'ann_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8),
                type: 'freehand',
                label: 'freehand annotation',
                color: currentColor,
                created_at: new Date().toISOString()
            };
            lastDrawnShape = e.path;
            updateCount();
            setStatus('Freehand drawn - enter note text and click Save');
        }
    });

    function createShape(start, end, isTemp) {
        var opts = {
            stroke: currentColor, strokeWidth: isTemp ? 1 : 3,
            fill: isTemp ? 'rgba(255,0,0,0.1)' : 'rgba(' + hexToRgb(currentColor) + ',0.15)',
            strokeDashArray: isTemp ? [5, 5] : null,
            selectable: !isTemp, evented: !isTemp
        };
        if (currentTool === 'rect') {
            return new fabric.Rect({ left: Math.min(start.x, end.x), top: Math.min(start.y, end.y), width: Math.abs(end.x - start.x), height: Math.abs(end.y - start.y), ...opts });
        } else if (currentTool === 'circle') {
            return new fabric.Ellipse({ left: Math.min(start.x, end.x), top: Math.min(start.y, end.y), rx: Math.abs(end.x - start.x) / 2, ry: Math.abs(end.y - start.y) / 2, ...opts });
        } else if (currentTool === 'arrow') {
            var angle = Math.atan2(end.y - start.y, end.x - start.x);
            var line = new fabric.Line([start.x, start.y, end.x, end.y], { ...opts, fill: null });
            var head = new fabric.Triangle({ left: end.x, top: end.y, width: 15, height: 15, fill: currentColor, angle: (angle * 180) / Math.PI + 90, originX: 'center', originY: 'center', selectable: false, evented: false });
            return new fabric.Group([line, head], { selectable: !isTemp, evented: !isTemp });
        }
        return null;
    }

    function hexToRgb(hex) {
        var r = parseInt(hex.slice(1, 3), 16);
        var g = parseInt(hex.slice(3, 5), 16);
        var b = parseInt(hex.slice(5, 7), 16);
        return r + ',' + g + ',' + b;
    }

    // Get the shape to save - prefer active object, fall back to last drawn
    function getShapeForSave() {
        var active = canvas.getActiveObject();
        if (active && active.annotationData) return active;
        if (lastDrawnShape && canvas.getObjects().indexOf(lastDrawnShape) !== -1) return lastDrawnShape;
        return null;
    }

    // Action buttons
    document.getElementById('toggleAnnotations').addEventListener('click', function() {
        annotationsVisible = !annotationsVisible;
        canvas.getObjects().filter(function(o) { return o.annotationData; }).forEach(function(o) { o.visible = annotationsVisible; });
        canvas.renderAll();
        setStatus(annotationsVisible ? 'Annotations visible' : 'Annotations hidden');
    });

    document.getElementById('deleteSelected').addEventListener('click', function() {
        var objs = canvas.getActiveObjects();
        if (!objs.length) { alert('Select an annotation shape first.'); return; }
        objs.forEach(function(o) { if (o.annotationData) canvas.remove(o); });
        canvas.discardActiveObject();
        lastDrawnShape = null;
        updateCount();
    });

    document.getElementById('undoLast').addEventListener('click', function() {
        var objs = canvas.getObjects().filter(function(o) { return o.annotationData; });
        if (!objs.length) return;
        canvas.remove(objs[objs.length - 1]);
        lastDrawnShape = null;
        updateCount();
    });

    document.getElementById('zoomIn').addEventListener('click', function() { zoom(1.25); });
    document.getElementById('zoomOut').addEventListener('click', function() { zoom(0.8); });
    document.getElementById('zoomFit').addEventListener('click', function() {
        if (!imageLoaded) return;
        canvas.setZoom(1);
        canvas.setWidth(Math.round(originalSize.width * scale));
        canvas.setHeight(Math.round(originalSize.height * scale));
        canvas.renderAll();
    });

    function zoom(factor) {
        var z = canvas.getZoom() * factor;
        if (z < 0.1 || z > 5) return;
        canvas.setZoom(z);
        canvas.setWidth(Math.round(originalSize.width * scale * z));
        canvas.setHeight(Math.round(originalSize.height * scale * z));
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;
        var keyMap = { v: 'select', r: 'rect', c: 'circle', a: 'arrow', f: 'freehand', t: 'text', m: 'marker' };
        if (keyMap[e.key]) {
            setTool(keyMap[e.key]);
            document.querySelectorAll('.tool-btn').forEach(function(b) {
                b.classList.remove('active', 'btn-primary'); b.classList.add('btn-secondary');
            });
            var activeBtn = document.querySelector('.tool-btn[data-tool="' + keyMap[e.key] + '"]');
            if (activeBtn) { activeBtn.classList.remove('btn-secondary'); activeBtn.classList.add('active', 'btn-primary'); }
        }
        if (e.key === 'Delete' || e.key === 'Backspace') {
            var objs = canvas.getActiveObjects();
            objs.forEach(function(o) { if (o.annotationData) canvas.remove(o); });
            canvas.discardActiveObject();
            lastDrawnShape = null;
            updateCount();
        }
    });

    // Save drawn annotation as W3C annotation with FragmentSelector
    document.getElementById('saveAnnotationBtn').addEventListener('click', function() {
        var bodyText = document.getElementById('newBody').value.trim();
        if (!bodyText) { alert('Please enter annotation text.'); return; }

        var shape = getShapeForSave();
        var targets = [{ source_type: 'information_object', source_id: objectId }];

        // If a shape exists, compute its bounding box + store shape geometry
        if (shape) {
            var bound = shape.getBoundingRect();
            var x = Math.round(bound.left / scale);
            var y = Math.round(bound.top / scale);
            var w = Math.round(bound.width / scale);
            var h = Math.round(bound.height / scale);

            var shapeType = (shape.annotationData && shape.annotationData.type) || 'rect';
            var shapeData = {
                stroke: shape.stroke || shape.annotationData.color || currentColor,
                strokeWidth: shape.strokeWidth || 3
            };

            // Store shape-specific geometry for faithful reconstruction
            if (shapeType === 'circle' || shapeType === 'ellipse') {
                shapeData.rx = Math.round((shape.rx || w / 2) / scale);
                shapeData.ry = Math.round((shape.ry || h / 2) / scale);
            } else if (shapeType === 'arrow') {
                // Extract line endpoints from group children
                var objs = shape.getObjects ? shape.getObjects() : [];
                var line = objs.find(function(o) { return o.type === 'line'; });
                if (line) {
                    shapeData.x1 = Math.round((shape.left + line.x1) / scale);
                    shapeData.y1 = Math.round((shape.top + line.y1) / scale);
                    shapeData.x2 = Math.round((shape.left + line.x2) / scale);
                    shapeData.y2 = Math.round((shape.top + line.y2) / scale);
                }
            } else if (shapeType === 'text') {
                shapeData.text = shape.text || '';
                shapeData.fontSize = shape.fontSize || 16;
            } else if (shapeType === 'marker') {
                shapeData.markerIndex = canvas.getObjects().filter(function(o) {
                    return o.annotationData && o.annotationData.type === 'marker';
                }).indexOf(shape) + 1;
            }

            targets = [{
                source_type: 'information_object',
                source_id: objectId,
                selector_type: 'FragmentSelector',
                selector_json: {
                    value: 'xywh=pixel:' + x + ',' + y + ',' + w + ',' + h,
                    conformsTo: 'http://www.w3.org/TR/media-frags/',
                    shapeType: shapeType,
                    shapeData: shapeData
                }
            }];
        }

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

        fetch('/research/annotation-v2/create', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                motivation: document.getElementById('newMotivation').value,
                body: { type: 'TextualBody', value: bodyText, format: 'text/plain' },
                visibility: document.getElementById('newVisibility').value,
                targets: targets
            })
        }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.success) {
                // Mark the shape as saved so it shows as "saved" color
                if (shape) {
                    shape.set({ stroke: '#00BFFF', fill: 'rgba(0,191,255,0.15)' });
                    shape.annotationData.savedAnnotationId = String(d.id);
                    canvas.renderAll();
                }
                lastDrawnShape = null;
                // Clear the form
                document.getElementById('newBody').value = '';
                location.reload();
            } else {
                alert(d.error || 'Error creating annotation');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-plus me-1"></i>Save Selected Region';
            }
        }).catch(function(err) {
            alert('Network error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus me-1"></i>Save Selected Region';
        });
    });

    // Click annotation in sidebar to highlight on canvas
    document.querySelectorAll('.annotation-list-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            if (e.target.closest('.edit-ann-btn') || e.target.closest('.delete-ann-btn')) return;
            var annId = item.dataset.annotationId;
            var obj = canvas.getObjects().find(function(o) {
                return o.annotationData && o.annotationData.savedAnnotationId === annId;
            });
            if (obj) {
                canvas.setActiveObject(obj);
                canvas.renderAll();
                setStatus('Selected annotation #' + annId);
            }
        });
    });

    // Common buttons (edit/delete) for both modes
    bindDeleteButtons();
    bindEditButtons();

    // Load the image
    loadImage();

    function bindDeleteButtons() {
        document.querySelectorAll('.delete-ann-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (!confirm('Delete this annotation?')) return;
                var annId = this.dataset.id;
                fetch('/research/annotation-v2/create', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ delete_annotation: true, annotation_id: parseInt(annId) })
                }).then(function(r) { return r.json(); }).then(function(d) {
                    if (d.success) location.reload();
                    else alert(d.error || 'Error deleting annotation');
                }).catch(function(err) { alert('Network error: ' + err.message); });
            });
        });
    }

    function bindEditButtons() {
        document.querySelectorAll('.edit-ann-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                document.getElementById('editAnnotationId').value = this.dataset.id;
                document.getElementById('editAnnotationBody').value = this.dataset.body;
                document.getElementById('editMotivation').value = this.dataset.motivation;
                new bootstrap.Modal(document.getElementById('editAnnotationModal')).show();
            });
        });

        var saveEditBtn = document.getElementById('saveEditAnnotation');
        if (saveEditBtn) {
            saveEditBtn.addEventListener('click', function() {
                var annId = document.getElementById('editAnnotationId').value;
                fetch('/research/annotation-v2/create', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        update_annotation: true,
                        annotation_id: parseInt(annId),
                        body: { type: 'TextualBody', value: document.getElementById('editAnnotationBody').value, format: 'text/plain' },
                        motivation: document.getElementById('editMotivation').value
                    })
                }).then(function(r) { return r.json(); }).then(function(d) {
                    if (d.success) location.reload();
                    else alert(d.error || 'Error updating');
                }).catch(function(err) { alert('Network error: ' + err.message); });
            });
        }
    }

    // IIIF import
    var importBtn = document.getElementById('importIiifBtn');
    if (importBtn) {
        importBtn.addEventListener('click', function() {
            var fileInput = document.getElementById('iiifFile');
            if (!fileInput.files.length) { alert('Please select a file.'); return; }
            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var jsonData = JSON.parse(e.target.result);
                    fetch('/research/annotations/import/' + objectId, {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(jsonData)
                    }).then(function(r) { return r.json(); }).then(function(d) {
                        if (d.success) { alert('Imported ' + (d.count || 0) + ' annotation(s).'); location.reload(); }
                        else alert(d.error || 'Import error');
                    }).catch(function(err) { alert('Network error: ' + err.message); });
                } catch (ex) { alert('Invalid JSON file.'); }
            };
            reader.readAsText(fileInput.files[0]);
        });
    }
});
</script>
