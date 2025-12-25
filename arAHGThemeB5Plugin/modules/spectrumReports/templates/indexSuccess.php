<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Spectrum Reports'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'objectEntry']); ?>"><i class="fas fa-sign-in-alt me-2"></i><?php echo __('Object Entry'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'loans']); ?>"><i class="fas fa-exchange-alt me-2"></i><?php echo __('Loans'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'acquisitions']); ?>"><i class="fas fa-hand-holding me-2"></i><?php echo __('Acquisitions'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'movements']); ?>"><i class="fas fa-truck me-2"></i><?php echo __('Movements'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'conditions']); ?>"><i class="fas fa-heartbeat me-2"></i><?php echo __('Condition Checks'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'conservation']); ?>"><i class="fas fa-tools me-2"></i><?php echo __('Conservation'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'valuations']); ?>"><i class="fas fa-dollar-sign me-2"></i><?php echo __('Valuations'); ?></a></li>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'reports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Reports'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-clipboard-list"></i> <?php echo __('Spectrum Reports Dashboard'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="spectrum-dashboard">
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['conditionCheck']); ?></h2>
                    <p class="mb-0"><?php echo __('Condition Checks'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['loanIn'] + $stats['loanOut']); ?></h2>
                    <p class="mb-0"><?php echo __('Total Loans'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['valuation']); ?></h2>
                    <p class="mb-0"><?php echo __('Valuations'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-dark">
                <div class="card-body">
                    <h2><?php echo number_format($stats['acquisition']); ?></h2>
                    <p class="mb-0"><?php echo __('Acquisitions'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i><?php echo __('Procedure Summary'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between"><span>Object Entry</span><span class="badge bg-primary"><?php echo $stats['objectEntry']; ?></span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Object Exit</span><span class="badge bg-secondary"><?php echo $stats['objectExit']; ?></span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Loans In</span><span class="badge bg-success"><?php echo $stats['loanIn']; ?></span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Loans Out</span><span class="badge bg-info"><?php echo $stats['loanOut']; ?></span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Movements</span><span class="badge bg-warning"><?php echo $stats['movement']; ?></span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Conservation</span><span class="badge bg-danger"><?php echo $stats['conservation']; ?></span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Deaccession</span><span class="badge bg-dark"><?php echo $stats['deaccession']; ?></span></li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Recent Activity'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($recentActivity)): ?>
                    <li class="list-group-item text-muted"><?php echo __('No recent activity'); ?></li>
                    <?php else: ?>
                    <?php foreach ($recentActivity as $a): ?>
                    <li class="list-group-item">
                        <small class="text-muted"><?php echo $a->action_date ?? '-'; ?></small><br>
                        <?php echo esc_specialchars($a->action ?? $a->event_type ?? '-'); ?>
                    </li>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php end_slot(); ?>
