<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-gear text-primary me-2"></i><?php echo __('Preservation Policies'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<div class="d-flex justify-content-between mb-4">
    <p class="text-muted mb-0">Automated preservation policies and schedules.</p>
    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Policy'); ?></th>
                    <th><?php echo __('Type'); ?></th>
                    <th><?php echo __('Schedule'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th><?php echo __('Last Run'); ?></th>
                    <th><?php echo __('Next Run'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($policies as $policy): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($policy->name); ?></strong>
                        <?php if ($policy->description): ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($policy->description); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-secondary"><?php echo ucfirst($policy->policy_type); ?></span></td>
                    <td><code><?php echo htmlspecialchars($policy->schedule_cron ?? 'Manual'); ?></code></td>
                    <td>
                        <?php if ($policy->is_active): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $policy->last_run_at ? date('Y-m-d H:i', strtotime($policy->last_run_at)) : '-'; ?></td>
                    <td><?php echo $policy->next_run_at ? date('Y-m-d H:i', strtotime($policy->next_run_at)) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <i class="bi bi-terminal me-2"></i><?php echo __('CLI Commands'); ?>
    </div>
    <div class="card-body">
        <p>Run fixity checks from command line:</p>
        <pre class="bg-dark text-light p-3 rounded">
# Check 100 objects not verified in 7+ days
php plugins/ahgPreservationPlugin/bin/run-fixity-check.php

# Check all objects with verbose output
php plugins/ahgPreservationPlugin/bin/run-fixity-check.php --all --verbose

# Custom limits
php plugins/ahgPreservationPlugin/bin/run-fixity-check.php --limit=500 --min-age=30</pre>

        <p class="mt-3">Add to crontab for scheduled runs:</p>
        <pre class="bg-dark text-light p-3 rounded">
# Daily fixity check at 2am
0 2 * * * cd /usr/share/nginx/archive && php plugins/ahgPreservationPlugin/bin/run-fixity-check.php >> /var/log/fixity.log 2>&1</pre>
    </div>
</div>

<?php end_slot() ?>
