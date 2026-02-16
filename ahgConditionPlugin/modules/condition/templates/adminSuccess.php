<?php
$recentChecksRaw = isset($sf_data) ? $sf_data->getRaw('recentChecks') : (isset($recentChecks) ? $recentChecks : collect());
$byConditionRaw = isset($sf_data) ? $sf_data->getRaw('byCondition') : (isset($byCondition) ? $byCondition : collect());
$totalChecksVal = isset($sf_data) ? $sf_data->getRaw('totalChecks') : (isset($totalChecks) ? $totalChecks : 0);
$totalPhotosVal = isset($sf_data) ? $sf_data->getRaw('totalPhotos') : (isset($totalPhotos) ? $totalPhotos : 0);
$totalAnnotationsVal = isset($sf_data) ? $sf_data->getRaw('totalAnnotations') : (isset($totalAnnotations) ? $totalAnnotations : 0);

$conditionColors = [
    'excellent' => 'success', 'good' => 'primary', 'fair' => 'info',
    'poor' => 'warning', 'critical' => 'danger', 'pending' => 'secondary',
];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0"><i class="fas fa-clipboard-check me-2"></i><?php echo __('Condition Reports Administration'); ?></h1>
    </div>

    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <h3><?php echo number_format($totalChecksVal); ?></h3>
                    <small><?php echo __('Total Condition Checks'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body text-center">
                    <h3><?php echo number_format($totalPhotosVal); ?></h3>
                    <small><?php echo __('Condition Photos'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body text-center">
                    <h3><?php echo number_format($totalAnnotationsVal); ?></h3>
                    <small><?php echo __('Annotations'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- By Condition -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i><?php echo __('By Condition'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (count($byConditionRaw) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($byConditionRaw as $row):
                            $color = $conditionColors[$row->overall_condition] ?? 'secondary';
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="badge bg-<?php echo $color; ?>"><?php echo htmlspecialchars(ucfirst($row->overall_condition ?? 'Unknown')); ?></span>
                            <span class="badge bg-dark rounded-pill"><?php echo number_format($row->count); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-muted"><?php echo __('No condition checks recorded.'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Checks -->
        <div class="col-md-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Recent Condition Checks'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (count($recentChecksRaw) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th><?php echo __('Reference'); ?></th>
                                    <th><?php echo __('Object'); ?></th>
                                    <th><?php echo __('Condition'); ?></th>
                                    <th><?php echo __('Date'); ?></th>
                                    <th><?php echo __('Checked By'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentChecksRaw as $check):
                                    $color = $conditionColors[$check->overall_condition] ?? 'secondary';
                                ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($check->condition_check_reference ?? ''); ?></code></td>
                                    <td><?php echo htmlspecialchars(mb_strimwidth($check->object_title ?? 'Untitled', 0, 40, '...')); ?></td>
                                    <td><span class="badge bg-<?php echo $color; ?>"><?php echo htmlspecialchars(ucfirst($check->overall_condition ?? '')); ?></span></td>
                                    <td><?php echo $check->check_date ?? ''; ?></td>
                                    <td><?php echo htmlspecialchars($check->checked_by ?? ''); ?></td>
                                    <td>
                                        <a href="/condition/check/<?php echo $check->id; ?>/photos" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-camera"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted"><?php echo __('No condition checks recorded yet.'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
