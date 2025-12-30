<?php use_helper('Text'); ?>
<h1 class="h3 mb-4"><i class="fas fa-heartbeat me-2"></i><?php echo __('Condition Management'); ?></h1>
<div class="row mb-4">
    <div class="col-md-4"><div class="card text-white bg-primary"><div class="card-body"><h4><?php echo $stats['total_checks'] ?? 0; ?></h4><small>Total Checks</small></div></div></div>
    <div class="col-md-4"><div class="card text-white bg-danger"><div class="card-body"><h4><?php echo $stats['critical'] ?? 0; ?></h4><small>Critical</small></div></div></div>
    <div class="col-md-4"><div class="card text-white bg-warning"><div class="card-body"><h4><?php echo $stats['poor'] ?? 0; ?></h4><small>Poor</small></div></div></div>
</div>
<div class="mb-3"><a href="/admin/condition/risk" class="btn btn-outline-danger"><?php echo __('Risk Assessment'); ?></a></div>
<div class="card">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Recent Condition Checks'); ?></h5></div>
    <div class="card-body">
        <?php if (!empty($recentEvents)): ?>
            <table class="table table-striped">
                <thead><tr><th>Date</th><th>Object</th><th>Condition</th><th>Assessor</th></tr></thead>
                <tbody>
                <?php foreach ($recentEvents as $e): ?>
                    <tr>
                        <td><?php echo esc_entities($e->check_date ?? ''); ?></td>
                        <td><a href="/<?php echo $e->slug ?? ''; ?>"><?php echo esc_entities(substr($e->title ?? 'Untitled', 0, 40)); ?></a></td>
                        <td><span class="badge bg-<?php echo in_array($e->overall_condition ?? '', ['critical','poor']) ? 'danger' : 'success'; ?>"><?php echo ucfirst($e->overall_condition ?? ''); ?></span></td>
                        <td><?php echo esc_entities($e->checked_by ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted text-center py-4"><?php echo __('No condition checks'); ?></p>
        <?php endif; ?>
    </div>
</div>
