<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'index']); ?>" class="text-decoration-none text-muted">
                <i class="fas fa-brain me-2"></i><?php echo __('Semantic Search'); ?>
            </a>
            <i class="fas fa-chevron-right mx-2 small text-muted"></i>
            <?php echo __('Search Logs'); ?>
        </h1>
        <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'index']); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
        </a>
    </div>

    <div class="row">
        <!-- Popular Searches -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i><?php echo __('Popular Searches'); ?>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php $popularSearches = $sf_data->getRaw('popularSearches'); ?>
                        <?php if ($popularSearches && count($popularSearches) > 0): ?>
                            <?php foreach ($popularSearches as $search): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($search->original_query); ?>">
                                    <?php echo htmlspecialchars($search->original_query); ?>
                                </span>
                                <span class="badge bg-primary rounded-pill"><?php echo number_format($search->count); ?></span>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted">
                                <?php echo __('No searches yet'); ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Searches -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i><?php echo __('Recent Searches'); ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('Time'); ?></th>
                                    <th><?php echo __('Original Query'); ?></th>
                                    <th><?php echo __('Expanded Query'); ?></th>
                                    <th><?php echo __('Results'); ?></th>
                                    <th><?php echo __('Time (ms)'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $logs = $sf_data->getRaw('logs'); ?>
                                <?php if ($logs && count($logs) > 0): ?>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <span class="text-muted small">
                                                <?php echo date('M j, H:i', strtotime($log->created_at)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars(truncate_text($log->original_query ?? '', 30)); ?></strong>
                                        </td>
                                        <td>
                                            <span class="text-muted small" title="<?php echo htmlspecialchars($log->expanded_query ?? ''); ?>">
                                                <?php echo htmlspecialchars(truncate_text($log->expanded_query ?? '-', 40)); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <?php if (isset($log->result_count)): ?>
                                                <span class="badge bg-<?php echo $log->result_count > 0 ? 'success' : 'secondary'; ?>">
                                                    <?php echo number_format($log->result_count); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if (isset($log->processing_time_ms)): ?>
                                                <span class="text-muted small"><?php echo number_format($log->processing_time_ms); ?>ms</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-info-circle me-1"></i><?php echo __('No search logs found'); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
