<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Evidence Viewer</li>
    </ol>
</nav>

<h1 class="h2 mb-4">Evidence Viewer: <?php echo htmlspecialchars($objectTitle); ?></h1>

<div class="row">
    <!-- Left panel: object preview -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Object Preview</h5></div>
            <div class="card-body text-center">
                <?php if (!empty($thumbnail)): ?>
                    <img src="<?php echo htmlspecialchars($thumbnail); ?>" class="img-fluid rounded mb-2" alt="Preview" style="max-height:300px;">
                <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height:200px;">
                        <i class="fas fa-file-alt fa-3x text-muted"></i>
                    </div>
                <?php endif; ?>
                <p class="mt-2 mb-1"><strong><?php echo htmlspecialchars($objectTitle); ?></strong></p>
                <p class="text-muted small mb-0">Object ID: <?php echo (int) $objectId; ?></p>
                <?php if (!empty($objectSlug)): ?>
                    <a href="/<?php echo htmlspecialchars($objectSlug); ?>" class="btn btn-sm btn-outline-primary mt-2" target="_blank">View Full Record</a>
                <?php endif; ?>
                <?php if (!empty($iiifAvailable)): ?>
                    <a href="/iiif/viewer/<?php echo (int) $objectId; ?>" class="btn btn-sm btn-outline-info mt-2" target="_blank"><i class="fas fa-search-plus me-1"></i>IIIF Viewer</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right panel: tabbed evidence sections -->
    <div class="col-lg-8">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-provenance">Provenance</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-rights">Rights & Security</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-trust">Trust Score</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-annotations">Annotations <span class="badge bg-secondary"><?php echo count($annotations ?? []); ?></span></a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-assertions">Assertions <span class="badge bg-secondary"><?php echo count($assertions ?? []); ?></span></a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-quality">Quality</a></li>
        </ul>
        <div class="tab-content border border-top-0 rounded-bottom p-3">
            <!-- Provenance Tab -->
            <div class="tab-pane fade show active" id="tab-provenance">
                <?php if (!empty($provenance)): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Date</th><th>Action</th><th>User</th><th>Details</th></tr></thead>
                        <tbody>
                        <?php foreach ($provenance as $p): ?>
                            <tr>
                                <td><small><?php echo htmlspecialchars($p->created_at ?? ''); ?></small></td>
                                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($p->action_type ?? $p->action ?? ''); ?></span></td>
                                <td><small><?php echo htmlspecialchars(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')); ?></small></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($p->description ?? $p->details ?? '', 0, 100, '...')); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No provenance records found for this object.</p>
                <?php endif; ?>
            </div>

            <!-- Rights & Security Tab -->
            <div class="tab-pane fade" id="tab-rights">
                <?php if (!empty($securityClearance)): ?>
                <div class="mb-3">
                    <h6>Security Classification</h6>
                    <span class="badge bg-<?php echo match($securityClearance->level ?? '') { 'top_secret' => 'danger', 'secret' => 'warning', 'confidential' => 'info', default => 'secondary' }; ?> fs-6"><?php echo ucfirst(str_replace('_', ' ', $securityClearance->level ?? 'unclassified')); ?></span>
                    <?php if (!empty($securityClearance->reason)): ?>
                        <p class="small text-muted mt-1"><?php echo htmlspecialchars($securityClearance->reason); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($odrlPolicies)): ?>
                <h6>ODRL Policies</h6>
                <ul class="list-group list-group-flush">
                    <?php foreach ($odrlPolicies as $policy): ?>
                    <li class="list-group-item px-0">
                        <strong><?php echo htmlspecialchars($policy->policy_type ?? ''); ?></strong>
                        <small class="text-muted d-block"><?php echo htmlspecialchars($policy->description ?? ''); ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No ODRL policies applied.</p>
                <?php endif; ?>
            </div>

            <!-- Trust Score Tab -->
            <div class="tab-pane fade" id="tab-trust">
                <?php if ($trustScore !== null): ?>
                <div class="text-center mb-3">
                    <?php $pct = round((float) $trustScore * 100); $color = $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger'); ?>
                    <div class="fs-1 fw-bold text-<?php echo $color; ?>"><?php echo $pct; ?>%</div>
                    <p class="text-muted">Trust Score</p>
                </div>
                <?php endif; ?>
                <?php if (!empty($sourceAssessment)): ?>
                <h6>Source Assessment</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Credibility</dt><dd class="col-sm-8"><?php echo htmlspecialchars($sourceAssessment->credibility_score ?? '-'); ?>/10</dd>
                    <dt class="col-sm-4">Reliability</dt><dd class="col-sm-8"><?php echo htmlspecialchars($sourceAssessment->reliability_score ?? '-'); ?>/10</dd>
                    <dt class="col-sm-4">Completeness</dt><dd class="col-sm-8"><?php echo htmlspecialchars($sourceAssessment->completeness_score ?? '-'); ?>/10</dd>
                    <?php if (!empty($sourceAssessment->notes)): ?>
                    <dt class="col-sm-4">Notes</dt><dd class="col-sm-8"><?php echo htmlspecialchars($sourceAssessment->notes); ?></dd>
                    <?php endif; ?>
                </dl>
                <?php else: ?>
                    <p class="text-muted mb-0">No source assessment available.</p>
                <?php endif; ?>
            </div>

            <!-- Annotations Tab -->
            <div class="tab-pane fade" id="tab-annotations">
                <?php if (!empty($annotations)): ?>
                <?php foreach ($annotations as $ann): ?>
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-info"><?php echo htmlspecialchars($ann->motivation ?? 'commenting'); ?></span>
                        <small class="text-muted"><?php echo $ann->created_at; ?></small>
                    </div>
                    <div class="mt-1 small"><?php
                        $body = is_string($ann->body_json ?? null) ? json_decode($ann->body_json, true) : ($ann->body_json ?? []);
                        echo htmlspecialchars($body['value'] ?? $body['text'] ?? json_encode($body));
                    ?></div>
                </div>
                <?php endforeach; ?>
                <a href="<?php echo url_for(['module' => 'research', 'action' => 'annotationStudio', 'object_id' => $objectId]); ?>" class="btn btn-sm btn-outline-primary mt-2">Open Annotation Studio</a>
                <?php else: ?>
                    <p class="text-muted">No annotations for this object.</p>
                    <a href="<?php echo url_for(['module' => 'research', 'action' => 'annotationStudio', 'object_id' => $objectId]); ?>" class="btn btn-sm btn-primary">Create Annotation</a>
                <?php endif; ?>
            </div>

            <!-- Assertions Tab -->
            <div class="tab-pane fade" id="tab-assertions">
                <?php if (!empty($assertions)): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Predicate</th><th>Type</th><th>Status</th><th>Object</th><th>Created</th></tr></thead>
                        <tbody>
                        <?php foreach ($assertions as $a): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($a->predicate ?? ''); ?></td>
                                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($a->assertion_type ?? ''); ?></span></td>
                                <td><span class="badge bg-<?php echo match($a->status ?? '') { 'proposed' => 'info', 'verified' => 'success', 'disputed' => 'danger', default => 'secondary' }; ?>"><?php echo ucfirst($a->status ?? ''); ?></span></td>
                                <td><?php echo htmlspecialchars(($a->object_type ?? '') . ':' . ($a->object_id ?? '')); ?></td>
                                <td><small><?php echo $a->created_at ?? ''; ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No assertions reference this object.</p>
                <?php endif; ?>
            </div>

            <!-- Quality Metrics Tab -->
            <div class="tab-pane fade" id="tab-quality">
                <?php if (!empty($qualityMetrics)): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Metric</th><th>Score</th><th>Note</th></tr></thead>
                        <tbody>
                        <?php foreach ($qualityMetrics as $m): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m->metric_name ?? $m->metric ?? ''); ?></td>
                                <td>
                                    <?php $qpct = round((float)($m->score ?? 0) * 100); $qcolor = $qpct >= 80 ? 'success' : ($qpct >= 50 ? 'warning' : 'danger'); ?>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="progress" style="width:60px;height:6px"><div class="progress-bar bg-<?php echo $qcolor; ?>" style="width:<?php echo $qpct; ?>%"></div></div>
                                        <small><?php echo $qpct; ?>%</small>
                                    </div>
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($m->note ?? ''); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No quality metrics recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
