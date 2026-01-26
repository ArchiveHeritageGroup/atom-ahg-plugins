<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'index']); ?>" class="text-decoration-none text-muted">
                <i class="fas fa-brain me-2"></i><?php echo __('Semantic Search'); ?>
            </a>
            <i class="fas fa-chevron-right mx-2 small text-muted"></i>
            <?php echo __('Sync Logs'); ?>
        </h1>
        <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'index']); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
        </a>
    </div>

    <!-- Sync Logs Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-sync me-1"></i><?php echo __('Synchronization History'); ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Started'); ?></th>
                            <th><?php echo __('Type'); ?></th>
                            <th><?php echo __('Status'); ?></th>
                            <th><?php echo __('Terms Added'); ?></th>
                            <th><?php echo __('Terms Updated'); ?></th>
                            <th><?php echo __('Synonyms Added'); ?></th>
                            <th><?php echo __('Duration'); ?></th>
                            <th><?php echo __('Message'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $logs = $sf_data->getRaw('logs'); ?>
                        <?php if ($logs && count($logs) > 0): ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <span class="text-muted small">
                                        <?php echo date('Y-m-d H:i:s', strtotime($log->started_at)); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $log->sync_type === 'wordnet' ? 'info' : ($log->sync_type === 'wikidata' ? 'dark' : 'secondary'); ?>">
                                        <?php echo ucfirst($log->sync_type ?? 'unknown'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log->status === 'completed'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Completed'); ?></span>
                                    <?php elseif ($log->status === 'running'): ?>
                                        <span class="badge bg-warning"><i class="fas fa-spinner fa-spin me-1"></i><?php echo __('Running'); ?></span>
                                    <?php elseif ($log->status === 'failed'): ?>
                                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i><?php echo __('Failed'); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo $log->status ?? '-'; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?php echo number_format($log->terms_added ?? 0); ?></td>
                                <td class="text-end"><?php echo number_format($log->terms_updated ?? 0); ?></td>
                                <td class="text-end"><?php echo number_format($log->synonyms_added ?? 0); ?></td>
                                <td>
                                    <?php if ($log->completed_at && $log->started_at): ?>
                                        <?php
                                        $duration = strtotime($log->completed_at) - strtotime($log->started_at);
                                        if ($duration < 60) {
                                            echo $duration . 's';
                                        } elseif ($duration < 3600) {
                                            echo floor($duration / 60) . 'm ' . ($duration % 60) . 's';
                                        } else {
                                            echo floor($duration / 3600) . 'h ' . floor(($duration % 3600) / 60) . 'm';
                                        }
                                        ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-muted small" title="<?php echo htmlspecialchars($log->error_message ?? ''); ?>">
                                        <?php echo truncate_text($log->error_message ?? '-', 50); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle me-1"></i><?php echo __('No sync logs found'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
