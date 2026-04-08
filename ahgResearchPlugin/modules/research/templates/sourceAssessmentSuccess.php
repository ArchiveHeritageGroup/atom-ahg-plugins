<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Source Assessment</li>
    </ol>
</nav>

<h1 class="h2 mb-4">Source Assessment: <?php echo htmlspecialchars($objectTitle); ?></h1>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Assess Source</h5></div>
            <div class="card-body">
                <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'saveSourceAssessment', 'object_id' => $objectId]); ?>">
                    <div class="row mb-3">
                        <div class="col-md-4"><label class="form-label">Source Type</label><select name="source_type" class="form-select"><option value="primary" <?php echo ($assessment->source_type ?? '') === 'primary' ? 'selected' : ''; ?>>Primary</option><option value="secondary" <?php echo ($assessment->source_type ?? '') === 'secondary' ? 'selected' : ''; ?>>Secondary</option><option value="tertiary" <?php echo ($assessment->source_type ?? '') === 'tertiary' ? 'selected' : ''; ?>>Tertiary</option></select></div>
                        <div class="col-md-4"><label class="form-label">Source Form</label><select name="source_form" class="form-select"><option value="original">Original</option><option value="scan" <?php echo ($assessment->source_form ?? '') === 'scan' ? 'selected' : ''; ?>>Scan</option><option value="transcription" <?php echo ($assessment->source_form ?? '') === 'transcription' ? 'selected' : ''; ?>>Transcription</option><option value="translation" <?php echo ($assessment->source_form ?? '') === 'translation' ? 'selected' : ''; ?>>Translation</option><option value="born_digital" <?php echo ($assessment->source_form ?? '') === 'born_digital' ? 'selected' : ''; ?>>Born Digital</option></select></div>
                        <div class="col-md-4"><label class="form-label">Completeness</label><select name="completeness" class="form-select"><option value="complete">Complete</option><option value="partial" <?php echo ($assessment->completeness ?? '') === 'partial' ? 'selected' : ''; ?>>Partial</option><option value="fragment" <?php echo ($assessment->completeness ?? '') === 'fragment' ? 'selected' : ''; ?>>Fragment</option><option value="missing_pages" <?php echo ($assessment->completeness ?? '') === 'missing_pages' ? 'selected' : ''; ?>>Missing Pages</option><option value="redacted" <?php echo ($assessment->completeness ?? '') === 'redacted' ? 'selected' : ''; ?>>Redacted</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Rationale</label><textarea name="rationale" class="form-control" rows="3"><?php echo htmlspecialchars($assessment->rationale ?? ''); ?></textarea></div>
                    <div class="mb-3"><label class="form-label">Bias Context</label><textarea name="bias_context" class="form-control" rows="2"><?php echo htmlspecialchars($assessment->bias_context ?? ''); ?></textarea></div>
                    <button type="submit" class="btn btn-primary">Save Assessment</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Quality Metrics</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMetricModal"><i class="fas fa-plus me-1"></i>Add Metric</button>
            </div>
            <div class="card-body">
                <?php if (!empty($metrics)): ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Metric</th><th>Value</th><th>Source</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($metrics as $m): ?>
                        <tr>
                            <td><?php echo ucfirst(str_replace('_', ' ', $m->metric_type)); ?></td>
                            <td><?php echo number_format((float) $m->metric_value, 2); ?></td>
                            <td><small><?php echo htmlspecialchars($m->source_service ?? '-'); ?></small></td>
                            <td><small><?php echo $m->created_at; ?></small></td>
                            <td>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this metric?');">
                                    <input type="hidden" name="form_action" value="delete_metric">
                                    <input type="hidden" name="metric_id" value="<?php echo (int) $m->id; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted mb-0">No quality metrics recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Trust Score</h5></div>
            <div class="card-body text-center">
                <div class="display-4 fw-bold text-<?php echo ($assessment->trust_score ?? 0) >= 70 ? 'success' : (($assessment->trust_score ?? 0) >= 40 ? 'warning' : 'danger'); ?>"><?php echo (int) ($assessment->trust_score ?? 0); ?></div>
                <p class="text-muted">out of 100</p>
            </div>
        </div>
    </div>
</div>

<!-- Add Metric Modal -->
<div class="modal fade" id="addMetricModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post">
            <input type="hidden" name="form_action" value="add_metric">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add Quality Metric</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Metric Type *</label>
                        <select name="metric_type" class="form-select" required>
                            <option value="image_quality">Image Quality</option>
                            <option value="ocr_confidence">OCR Confidence</option>
                            <option value="digitisation_completeness">Digitisation Completeness</option>
                            <option value="fixity_status">Fixity Status</option>
                            <option value="colour_accuracy">Colour Accuracy</option>
                            <option value="resolution_dpi">Resolution (DPI)</option>
                            <option value="file_integrity">File Integrity</option>
                            <option value="metadata_completeness">Metadata Completeness</option>
                            <option value="legibility">Legibility</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value * <small class="text-muted">(numeric, e.g. 85.5)</small></label>
                        <input type="number" name="metric_value" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Source</label>
                        <input type="text" name="source_service" class="form-control" placeholder="e.g. manual_check, ImageMagick">
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add Metric</button></div>
            </div>
        </form>
    </div>
</div>
