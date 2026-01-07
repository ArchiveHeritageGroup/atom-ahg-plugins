<?php use_helper('Text'); ?>

<div class="row">
    <div class="col-md-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-shield-alt me-2"></i>
            <?php echo __('Security Compliance Dashboard'); ?>
        </h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary h-100">
            <div class="card-body">
                <h4 class="mb-0"><?php echo $stats['classified_objects'] ?? 0; ?></h4>
                <small><?php echo __('Classified Objects'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning h-100">
            <div class="card-body">
                <h4 class="mb-0"><?php echo $stats['pending_reviews'] ?? 0; ?></h4>
                <small><?php echo __('Pending Reviews'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success h-100">
            <div class="card-body">
                <h4 class="mb-0"><?php echo $stats['cleared_users'] ?? 0; ?></h4>
                <small><?php echo __('Cleared Users'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info h-100">
            <div class="card-body">
                <h4 class="mb-0"><?php echo $stats['access_logs_today'] ?? 0; ?></h4>
                <small><?php echo __('Access Logs Today'); ?></small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><?php echo __('Recent Audit Logs'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentLogs)): ?>
                    <table class="table table-sm">
                        <thead><tr><th>Action</th><th>User</th><th>Time</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td><?php echo esc_entities($log->action ?? ''); ?></td>
                                <td><?php echo esc_entities($log->username ?? ''); ?></td>
                                <td><small><?php echo esc_entities($log->created_at ?? ''); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted text-center"><?php echo __('No recent logs'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><?php echo __('Retention Schedules (NARSSA)'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($retentionSchedules)): ?>
                    <table class="table table-sm">
                        <thead><tr><th>Ref</th><th>Type</th><th>Period</th></tr></thead>
                        <tbody>
                        <?php foreach ($retentionSchedules as $s): ?>
                            <tr>
                                <td><code><?php echo esc_entities($s->narssa_ref ?? ''); ?></code></td>
                                <td><?php echo esc_entities($s->record_type ?? ''); ?></td>
                                <td><?php echo esc_entities($s->retention_period ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted text-center"><?php echo __('No retention schedules'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
