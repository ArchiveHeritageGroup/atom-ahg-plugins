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

        <?php if (!empty($metrics)): ?>
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Quality Metrics</h5></div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Metric</th><th>Value</th><th>Source</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($metrics as $m): ?>
                        <tr><td><?php echo htmlspecialchars($m->metric_type); ?></td><td><?php echo number_format((float) $m->metric_value, 4); ?></td><td><?php echo htmlspecialchars($m->source_service ?? ''); ?></td><td><?php echo $m->created_at; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
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
