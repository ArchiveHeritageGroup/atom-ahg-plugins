<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Compliance Dashboard</li>
    </ol>
</nav>

<h1 class="h2 mb-4">Compliance Dashboard</h1>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <?php $es = $ethicsStatus ?? 'not_started'; ?>
                <div class="fs-4 fw-bold text-<?php echo match($es) { 'approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', default => 'secondary' }; ?>">
                    <i class="fas fa-<?php echo match($es) { 'approved' => 'check-circle', 'pending' => 'clock', 'rejected' => 'times-circle', default => 'minus-circle' }; ?>"></i>
                </div>
                <small class="text-muted">Ethics: <?php echo ucfirst(str_replace('_', ' ', $es)); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-primary"><?php echo (int) ($odrlPolicyCount ?? 0); ?></div>
                <small class="text-muted">ODRL Policies</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <?php $sens = $sensitivitySummary ?? []; $maxLevel = $sens['max_level'] ?? 'none'; ?>
                <div class="fs-4 fw-bold text-<?php echo match($maxLevel) { 'top_secret' => 'danger', 'secret' => 'warning', 'confidential' => 'info', default => 'success' }; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $maxLevel)); ?>
                </div>
                <small class="text-muted">Max Security Level</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <?php $avgTrust = $avgTrustScore ?? null; ?>
                <div class="fs-4 fw-bold text-<?php echo ($avgTrust !== null && $avgTrust >= 0.7) ? 'success' : (($avgTrust !== null && $avgTrust >= 0.4) ? 'warning' : 'secondary'); ?>">
                    <?php echo $avgTrust !== null ? round($avgTrust * 100) . '%' : '-'; ?>
                </div>
                <small class="text-muted">Avg Trust Score</small>
            </div>
        </div>
    </div>
</div>

<!-- Ethics Milestones -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Ethics Milestones</h5>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'ethicsMilestones', 'project_id' => $project->id]); ?>" class="btn btn-sm btn-outline-primary">Manage</a>
    </div>
    <div class="card-body">
        <?php if (!empty($ethicsMilestones)): ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Title</th><th>Type</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($ethicsMilestones as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m->title); ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($m->milestone_type ?? 'ethics'); ?></span></td>
                        <td><span class="badge bg-<?php echo match($m->status ?? '') { 'completed' => 'success', 'approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', default => 'secondary' }; ?>"><?php echo ucfirst($m->status ?? 'pending'); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted mb-0">No ethics milestones created yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ODRL Policies -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">ODRL Rights Policies</h5>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'odrlPolicies']); ?>" class="btn btn-sm btn-outline-primary">Manage Policies</a>
    </div>
    <div class="card-body">
        <?php if (!empty($odrlPolicies)): ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Policy Type</th><th>Target</th><th>Action</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach ($odrlPolicies as $p): ?>
                    <tr>
                        <td><span class="badge bg-<?php echo match($p->policy_type ?? '') { 'permission' => 'success', 'prohibition' => 'danger', 'obligation' => 'warning', default => 'secondary' }; ?>"><?php echo ucfirst($p->policy_type ?? ''); ?></span></td>
                        <td><?php echo htmlspecialchars($p->target_type ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($p->action ?? ''); ?></td>
                        <td><small><?php echo $p->created_at ?? ''; ?></small></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted mb-0">No ODRL policies applied to this project.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Security Levels of Linked Items -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Sensitivity Summary</h5></div>
    <div class="card-body">
        <?php if (!empty($sensitivityBreakdown)): ?>
        <div class="row">
            <?php foreach ($sensitivityBreakdown as $level => $count): ?>
            <div class="col-md-3 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-<?php echo match($level) { 'top_secret' => 'danger', 'secret' => 'warning', 'confidential' => 'info', 'unclassified' => 'success', default => 'secondary' }; ?>"><?php echo ucfirst(str_replace('_', ' ', $level)); ?></span>
                    <strong><?php echo (int) $count; ?></strong> items
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="text-muted mb-0">No security classifications found for project resources.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Trust Scores Summary -->
<div class="card">
    <div class="card-header"><h5 class="mb-0">Source Trust Scores</h5></div>
    <div class="card-body">
        <?php if (!empty($trustScores)): ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Source</th><th>Score</th></tr></thead>
                <tbody>
                <?php foreach ($trustScores as $ts): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ts->title ?? ('Object #' . ($ts->object_id ?? ''))); ?></td>
                        <td>
                            <?php $tpct = round((float)($ts->score ?? 0) * 100); $tc = $tpct >= 80 ? 'success' : ($tpct >= 50 ? 'warning' : 'danger'); ?>
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress" style="width:80px;height:6px"><div class="progress-bar bg-<?php echo $tc; ?>" style="width:<?php echo $tpct; ?>%"></div></div>
                                <small><?php echo $tpct; ?>%</small>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted mb-0">No trust scores computed for project sources.</p>
        <?php endif; ?>
    </div>
</div>
