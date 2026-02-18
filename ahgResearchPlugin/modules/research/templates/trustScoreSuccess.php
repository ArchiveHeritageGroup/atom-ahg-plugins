<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Trust Score</li>
    </ol>
</nav>

<?php
$scoreColor = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
$scoreLabel = $score >= 80 ? 'High Trust' : ($score >= 50 ? 'Moderate Trust' : 'Low Trust');

// Compute dimension breakdowns
$sourceWeight = match($assessment->source_type ?? '') {
    'primary' => 40, 'secondary' => 25, 'tertiary' => 10, default => 0
};
$completenessWeight = match($assessment->completeness ?? '') {
    'complete' => 30, 'partial' => 20, 'fragment' => 10,
    'missing_pages' => 15, 'redacted' => 15, default => 0
};
$qualityScore = 0;
if (!empty($qualityMetrics)) {
    $sum = 0;
    foreach ($qualityMetrics as $m) { $sum += (float) $m->metric_value; }
    $avg = $sum / count($qualityMetrics);
    $qualityScore = (int) round(max(0, min(1, $avg)) * 30);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2">Trust Score</h1>
        <?php if ($objectInfo): ?>
            <p class="text-muted mb-0">
                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $objectInfo->slug ?? '']); ?>">
                    <?php echo htmlspecialchars($objectInfo->title ?? 'Object #' . $objectId); ?>
                </a>
                <?php if ($objectInfo->identifier): ?>
                    <small class="ms-2">(<?php echo htmlspecialchars($objectInfo->identifier); ?>)</small>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <a href="/research/source-assessment/<?php echo $objectId; ?>" class="btn btn-outline-primary"><i class="fas fa-clipboard-check me-1"></i>Assess Source</a>
        <a href="/research/evidence/<?php echo $objectId; ?>" class="btn btn-outline-secondary"><i class="fas fa-search me-1"></i>Evidence Viewer</a>
    </div>
</div>

<!-- Score Overview -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-<?php echo $scoreColor; ?>">
            <div class="card-body text-center">
                <div class="position-relative d-inline-block mb-3" style="width:140px;height:140px;">
                    <svg viewBox="0 0 36 36" style="width:140px;height:140px;transform:rotate(-90deg);">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e9ecef" stroke-width="3"/>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                              fill="none" stroke="var(--bs-<?php echo $scoreColor; ?>)" stroke-width="3"
                              stroke-dasharray="<?php echo $score; ?>, 100"/>
                    </svg>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <span class="display-4 fw-bold text-<?php echo $scoreColor; ?>"><?php echo $score; ?></span>
                    </div>
                </div>
                <h5 class="text-<?php echo $scoreColor; ?>"><?php echo $scoreLabel; ?></h5>
                <small class="text-muted">Composite score out of 100</small>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Score Breakdown</h5></div>
            <div class="card-body">
                <!-- Source Type -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-archive me-1 text-primary"></i>Source Type
                            <?php if ($assessment): ?>
                                <span class="badge bg-light text-dark ms-1"><?php echo ucfirst($assessment->source_type); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="fw-bold"><?php echo $sourceWeight; ?>/40</span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-primary" style="width:<?php echo round($sourceWeight / 40 * 100); ?>%"></div>
                    </div>
                </div>
                <!-- Completeness -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-puzzle-piece me-1 text-info"></i>Completeness
                            <?php if ($assessment): ?>
                                <span class="badge bg-light text-dark ms-1"><?php echo ucfirst(str_replace('_', ' ', $assessment->completeness)); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="fw-bold"><?php echo $completenessWeight; ?>/30</span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-info" style="width:<?php echo round($completenessWeight / 30 * 100); ?>%"></div>
                    </div>
                </div>
                <!-- Quality Metrics -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-star me-1 text-warning"></i>Quality Metrics
                            <small class="text-muted">(<?php echo count($qualityMetrics); ?> metrics)</small>
                        </span>
                        <span class="fw-bold"><?php echo $qualityScore; ?>/30</span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-warning" style="width:<?php echo round($qualityScore / 30 * 100); ?>%"></div>
                    </div>
                </div>
                <?php if (!$assessment): ?>
                    <div class="alert alert-info py-2 mb-0"><i class="fas fa-info-circle me-1"></i>No source assessment yet. <a href="/research/source-assessment/<?php echo $objectId; ?>">Submit one</a> to get a meaningful score.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($assessment): ?>
<!-- Latest Assessment -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Latest Assessment</h5></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Source Type</strong><br>
                <span class="badge bg-<?php echo match($assessment->source_type) { 'primary' => 'success', 'secondary' => 'info', 'tertiary' => 'secondary', default => 'dark' }; ?> fs-6"><?php echo ucfirst($assessment->source_type); ?></span>
            </div>
            <div class="col-md-3">
                <strong>Form</strong><br>
                <?php echo ucfirst(str_replace('_', ' ', $assessment->source_form ?? 'original')); ?>
            </div>
            <div class="col-md-3">
                <strong>Completeness</strong><br>
                <?php echo ucfirst(str_replace('_', ' ', $assessment->completeness ?? 'unknown')); ?>
            </div>
            <div class="col-md-3">
                <strong>Assessed by</strong><br>
                <?php echo htmlspecialchars(($assessment->assessor_first_name ?? '') . ' ' . ($assessment->assessor_last_name ?? '')); ?>
                <br><small class="text-muted"><?php echo $assessment->assessed_at; ?></small>
            </div>
        </div>
        <?php if ($assessment->rationale): ?>
            <div class="mt-3">
                <strong>Rationale</strong>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($assessment->rationale)); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($assessment->bias_context): ?>
            <div class="mt-2">
                <strong>Bias Context</strong>
                <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($assessment->bias_context)); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($qualityMetrics)): ?>
<!-- Quality Metrics -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-star me-2"></i>Quality Metrics</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Metric</th><th>Value</th><th style="width:40%">Score</th><th>Service</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($qualityMetrics as $m):
                    $pct = round((float) $m->metric_value * 100, 1);
                    $barColor = $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
                ?>
                    <tr>
                        <td>
                            <i class="fas fa-<?php echo match($m->metric_type) {
                                'ocr_confidence' => 'font',
                                'image_quality' => 'image',
                                'digitisation_completeness' => 'check-double',
                                'fixity_status' => 'shield-alt',
                                default => 'circle'
                            }; ?> me-1 text-muted"></i>
                            <?php echo ucwords(str_replace('_', ' ', $m->metric_type)); ?>
                        </td>
                        <td class="fw-bold"><?php echo $pct; ?>%</td>
                        <td>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-<?php echo $barColor; ?>" style="width:<?php echo $pct; ?>%"></div>
                            </div>
                        </td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($m->source_service ?? '-'); ?></small></td>
                        <td><small class="text-muted"><?php echo $m->created_at; ?></small></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (count($assessmentHistory) > 1): ?>
<!-- Assessment History -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-history me-2"></i>Assessment History</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Assessor</th><th>Source Type</th><th>Completeness</th><th>Manual Score</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($assessmentHistory as $h): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(($h->assessor_first_name ?? '') . ' ' . ($h->assessor_last_name ?? '')); ?></td>
                        <td><span class="badge bg-<?php echo match($h->source_type) { 'primary' => 'success', 'secondary' => 'info', 'tertiary' => 'secondary', default => 'dark' }; ?>"><?php echo ucfirst($h->source_type); ?></span></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $h->completeness)); ?></td>
                        <td><?php echo $h->trust_score !== null ? $h->trust_score . '/100' : '-'; ?></td>
                        <td><?php echo $h->assessed_at; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
