<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Annotation Studio</li>
    </ol>
</nav>

<h1 class="h2 mb-4">Annotation Studio: <?php echo htmlspecialchars($objectTitle); ?></h1>

<div class="row">
    <!-- Main content: annotations list -->
    <div class="col-lg-8">
        <!-- Existing annotations -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Annotations (<?php echo count($annotations); ?>)</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#importIiifModal"><i class="fas fa-file-import me-1"></i>Import IIIF</button>
                    <a href="<?php echo url_for(['module' => 'research', 'action' => 'exportAnnotationsIIIF', 'object_id' => $objectId]); ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download me-1"></i>Export IIIF</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($annotations)): ?>
                    <p class="text-muted">No annotations yet for this object. Create one below.</p>
                <?php else: ?>
                    <?php foreach ($annotations as $ann): ?>
                    <div class="border rounded p-3 mb-3 annotation-card" data-annotation-id="<?php echo (int) $ann->id; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge bg-info"><?php echo htmlspecialchars($ann->motivation ?? 'commenting'); ?></span>
                                <span class="badge bg-<?php echo ($ann->visibility ?? 'private') === 'public' ? 'success' : (($ann->visibility ?? '') === 'shared' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($ann->visibility ?? 'private'); ?></span>
                            </div>
                            <small class="text-muted"><?php echo $ann->created_at; ?></small>
                        </div>
                        <div class="mt-2"><?php
                            $body = is_string($ann->body_json ?? null) ? json_decode($ann->body_json, true) : ($ann->body_json ?? []);
                            echo htmlspecialchars($body['value'] ?? $body['text'] ?? json_encode($body));
                        ?></div>

                        <!-- Per-annotation target display -->
                        <?php
                        $targets = [];
                        try {
                            $targets = \Illuminate\Database\Capsule\Manager::table('research_annotation_target')
                                ->where('annotation_id', $ann->id)
                                ->orderBy('id')
                                ->get()
                                ->toArray();
                        } catch (\Exception $e) { /* silent */ }
                        ?>
                        <?php if (!empty($targets)): ?>
                        <div class="mt-2">
                            <small class="text-muted d-block mb-1">Targets:</small>
                            <?php foreach ($targets as $t): ?>
                                <span class="badge bg-light text-dark border me-1 mb-1">
                                    <?php echo htmlspecialchars($t->selector_type ?? 'none'); ?>
                                    <small class="text-muted">(<?php echo htmlspecialchars($t->source_type ?? ''); ?><?php echo $t->source_id ? '#' . (int)$t->source_id : ''; ?>)</small>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Action buttons -->
                        <div class="mt-2 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary add-target-btn" data-annotation-id="<?php echo (int) $ann->id; ?>" data-bs-toggle="collapse" data-bs-target="#addTarget-<?php echo (int) $ann->id; ?>"><i class="fas fa-crosshairs me-1"></i>Add Target</button>
                            <button class="btn btn-sm btn-outline-success promote-btn" data-annotation-id="<?php echo (int) $ann->id; ?>"><i class="fas fa-arrow-up me-1"></i>Promote to Assertion</button>
                        </div>

                        <!-- Collapsible add-target section -->
                        <div class="collapse mt-2" id="addTarget-<?php echo (int) $ann->id; ?>">
                            <div class="card card-body bg-light">
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Selector Type</label>
                                    <select class="form-select form-select-sm target-selector-type">
                                        <option value="TextQuoteSelector">Text Span (TextQuoteSelector)</option>
                                        <option value="FragmentSelector">Image Region (FragmentSelector)</option>
                                        <option value="TimeSelector">AV Timestamp (TimeSelector)</option>
                                        <option value="PointSelector">Point (PointSelector)</option>
                                        <option value="SvgSelector">SVG Region (SvgSelector)</option>
                                    </select>
                                </div>
                                <!-- TextQuoteSelector fields -->
                                <div class="target-fields target-TextQuoteSelector">
                                    <div class="mb-2"><label class="form-label form-label-sm">Exact</label><input type="text" class="form-control form-control-sm" name="exact" placeholder="Exact text match"></div>
                                    <div class="mb-2"><label class="form-label form-label-sm">Prefix</label><input type="text" class="form-control form-control-sm" name="prefix" placeholder="Text before"></div>
                                    <div class="mb-2"><label class="form-label form-label-sm">Suffix</label><input type="text" class="form-control form-control-sm" name="suffix" placeholder="Text after"></div>
                                </div>
                                <!-- FragmentSelector fields -->
                                <div class="target-fields target-FragmentSelector" style="display:none">
                                    <div class="mb-2"><label class="form-label form-label-sm">Region (xywh=pixel)</label><input type="text" class="form-control form-control-sm" name="xywh" placeholder="100,100,200,150"></div>
                                </div>
                                <!-- TimeSelector fields -->
                                <div class="target-fields target-TimeSelector" style="display:none">
                                    <div class="row">
                                        <div class="col-6 mb-2"><label class="form-label form-label-sm">Start (seconds)</label><input type="number" class="form-control form-control-sm" name="start" step="0.1" placeholder="0.0"></div>
                                        <div class="col-6 mb-2"><label class="form-label form-label-sm">End (seconds)</label><input type="number" class="form-control form-control-sm" name="end" step="0.1" placeholder="10.0"></div>
                                    </div>
                                </div>
                                <!-- PointSelector fields -->
                                <div class="target-fields target-PointSelector" style="display:none">
                                    <div class="row">
                                        <div class="col-6 mb-2"><label class="form-label form-label-sm">X</label><input type="number" class="form-control form-control-sm" name="x" placeholder="0"></div>
                                        <div class="col-6 mb-2"><label class="form-label form-label-sm">Y</label><input type="number" class="form-control form-control-sm" name="y" placeholder="0"></div>
                                    </div>
                                </div>
                                <!-- SvgSelector fields -->
                                <div class="target-fields target-SvgSelector" style="display:none">
                                    <div class="mb-2"><label class="form-label form-label-sm">SVG Markup</label><textarea class="form-control form-control-sm" name="svg" rows="3" placeholder="<svg>...</svg>"></textarea></div>
                                </div>
                                <button class="btn btn-sm btn-primary save-target-btn" data-annotation-id="<?php echo (int) $ann->id; ?>">Save Target</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar: create form + object info -->
    <div class="col-lg-4">
        <!-- Create annotation panel -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Create Annotation</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Motivation</label>
                    <select id="newMotivation" class="form-select form-select-sm">
                        <option value="commenting">Commenting</option>
                        <option value="describing">Describing</option>
                        <option value="classifying">Classifying</option>
                        <option value="linking">Linking</option>
                        <option value="questioning">Questioning</option>
                        <option value="tagging">Tagging</option>
                        <option value="highlighting">Highlighting</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Body Text</label>
                    <textarea id="newBody" class="form-control" rows="4" placeholder="Enter your annotation..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Visibility</label>
                    <select id="newVisibility" class="form-select form-select-sm">
                        <option value="private">Private</option>
                        <option value="shared">Shared</option>
                        <option value="public">Public</option>
                    </select>
                </div>
                <button id="createAnnotationBtn" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i>Create Annotation</button>
            </div>
        </div>

        <!-- Object info -->
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Object Info</h5></div>
            <div class="card-body">
                <p class="mb-1"><strong>ID:</strong> <?php echo (int) $objectId; ?></p>
                <p class="mb-0"><strong>Title:</strong> <?php echo htmlspecialchars($objectTitle); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- IIIF Import Modal -->
<div class="modal fade" id="importIiifModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import IIIF Annotations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Upload a W3C Web Annotation JSON-LD file to import annotations.</p>
                <div class="mb-3">
                    <label class="form-label">JSON-LD File</label>
                    <input type="file" id="iiifFile" class="form-control" accept=".json,.jsonld">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="importIiifBtn" class="btn btn-primary">Import</button>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var objectId = <?php echo (int) $objectId; ?>;

    // Toggle selector type fields
    document.querySelectorAll('.target-selector-type').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var container = this.closest('.card-body');
            container.querySelectorAll('.target-fields').forEach(function(f) { f.style.display = 'none'; });
            var target = container.querySelector('.target-' + this.value);
            if (target) target.style.display = '';
        });
    });

    // Create annotation
    document.getElementById('createAnnotationBtn').addEventListener('click', function() {
        var bodyText = document.getElementById('newBody').value.trim();
        if (!bodyText) { alert('Please enter annotation text.'); return; }
        var payload = {
            motivation: document.getElementById('newMotivation').value,
            body: { type: 'TextualBody', value: bodyText, format: 'text/plain' },
            visibility: document.getElementById('newVisibility').value,
            targets: [{ source_type: 'information_object', source_id: objectId }]
        };
        fetch('/research/annotation-v2/create', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.success) location.reload();
            else alert(d.error || 'Error creating annotation');
        });
    });

    // Save target to annotation
    document.querySelectorAll('.save-target-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var annId = this.dataset.annotationId;
            var container = this.closest('.card-body');
            var selectorType = container.querySelector('.target-selector-type').value;
            var selectorJson = {};

            if (selectorType === 'TextQuoteSelector') {
                selectorJson = {
                    exact: container.querySelector('[name="exact"]').value,
                    prefix: container.querySelector('[name="prefix"]').value,
                    suffix: container.querySelector('[name="suffix"]').value
                };
            } else if (selectorType === 'FragmentSelector') {
                selectorJson = { value: 'xywh=pixel:' + container.querySelector('[name="xywh"]').value, conformsTo: 'http://www.w3.org/TR/media-frags/' };
            } else if (selectorType === 'TimeSelector') {
                selectorJson = { start: parseFloat(container.querySelector('[name="start"]').value) || 0, end: parseFloat(container.querySelector('[name="end"]').value) || 0 };
            } else if (selectorType === 'PointSelector') {
                selectorJson = { x: parseFloat(container.querySelector('[name="x"]').value) || 0, y: parseFloat(container.querySelector('[name="y"]').value) || 0 };
            } else if (selectorType === 'SvgSelector') {
                selectorJson = { value: container.querySelector('[name="svg"]').value };
            }

            fetch('/research/annotation-v2/create', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    add_target: true,
                    annotation_id: parseInt(annId),
                    source_type: 'information_object',
                    source_id: objectId,
                    selector_type: selectorType,
                    selector_json: selectorJson
                })
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.success) location.reload();
                else alert(d.error || 'Error adding target');
            });
        });
    });

    // Promote to assertion
    document.querySelectorAll('.promote-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Promote this annotation to a research assertion?')) return;
            var annId = this.dataset.annotationId;
            fetch('/research/assertion/create', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    from_annotation_id: parseInt(annId),
                    subject_type: 'information_object',
                    subject_id: objectId,
                    assertion_type: 'attributive',
                    predicate: 'annotated_as'
                })
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.success) alert('Assertion created (ID: ' + d.id + ')');
                else alert(d.error || 'Error promoting annotation');
            });
        });
    });

    // IIIF import
    document.getElementById('importIiifBtn').addEventListener('click', function() {
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
                });
            } catch (ex) { alert('Invalid JSON file.'); }
        };
        reader.readAsText(fileInput.files[0]);
    });
});
</script>
