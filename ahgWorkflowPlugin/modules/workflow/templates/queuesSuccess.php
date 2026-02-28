<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-inbox me-2"></i>Work Queues</h1>
        <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    <!-- SLA Overview Banner -->
    <?php $overview = sfOutputEscaper::unescape($slaOverview) ?>
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-2">
                    <div class="h4 mb-0"><?php echo $overview['total_open'] ?? 0 ?></div>
                    <small class="text-muted">Open Tasks</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0" style="background-color: rgba(40,167,69,0.1);">
                <div class="card-body py-2">
                    <div class="h4 mb-0 text-success"><?php echo $overview['on_track'] ?? 0 ?></div>
                    <small class="text-muted">On Track</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0" style="background-color: rgba(255,193,7,0.1);">
                <div class="card-body py-2">
                    <div class="h4 mb-0 text-warning"><?php echo $overview['at_risk'] ?? 0 ?></div>
                    <small class="text-muted">At Risk</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0" style="background-color: rgba(253,126,20,0.1);">
                <div class="card-body py-2">
                    <div class="h4 mb-0" style="color:#fd7e14"><?php echo $overview['overdue'] ?? 0 ?></div>
                    <small class="text-muted">Overdue</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0" style="background-color: rgba(220,53,69,0.1);">
                <div class="card-body py-2">
                    <div class="h4 mb-0 text-danger"><?php echo $overview['breached'] ?? 0 ?></div>
                    <small class="text-muted">Breached</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-2">
                    <div class="h4 mb-0"><?php echo $overview['health_pct'] ?? 0 ?>%</div>
                    <small class="text-muted">SLA Health</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Cards -->
    <div class="row g-3">
        <?php foreach (sfOutputEscaper::unescape($queues) as $queueStat): ?>
            <?php $q = $queueStat['queue'] ?>
            <div class="col-md-4 col-lg-3">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center" style="border-left: 4px solid <?php echo esc_entities($q->color ?? '#6c757d') ?>;">
                        <i class="fas <?php echo esc_entities($q->icon ?? 'fa-inbox') ?> me-2" style="color: <?php echo esc_entities($q->color ?? '#6c757d') ?>"></i>
                        <strong><?php echo esc_entities($q->name) ?></strong>
                    </div>
                    <div class="card-body">
                        <div class="row text-center g-2 mb-3">
                            <div class="col-4">
                                <div class="h5 mb-0"><?php echo $queueStat['count'] ?></div>
                                <small class="text-muted">Total</small>
                            </div>
                            <div class="col-4">
                                <div class="h5 mb-0 <?php echo $queueStat['overdue'] > 0 ? 'text-danger' : '' ?>"><?php echo $queueStat['overdue'] ?></div>
                                <small class="text-muted">Overdue</small>
                            </div>
                            <div class="col-4">
                                <div class="h5 mb-0"><?php echo $queueStat['unassigned'] ?></div>
                                <small class="text-muted">Unassigned</small>
                            </div>
                        </div>
                        <?php if ($queueStat['avg_age_days'] > 0): ?>
                            <small class="text-muted">Avg age: <?php echo $queueStat['avg_age_days'] ?> days</small>
                        <?php endif ?>
                    </div>
                    <?php if ($q->description): ?>
                        <div class="card-footer small text-muted"><?php echo esc_entities($q->description) ?></div>
                    <?php endif ?>
                </div>
            </div>
        <?php endforeach ?>
    </div>
</div>
