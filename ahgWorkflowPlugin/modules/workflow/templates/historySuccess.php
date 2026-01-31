<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-history me-2"></i>Workflow History</h1>
        <a href="<?php echo url_for('workflow/dashboard') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    <?php if (empty($activity)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-history fa-4x mb-3 opacity-50"></i>
            <h4>No workflow activity yet</h4>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th>Action</th>
                        <th>Workflow</th>
                        <th>Object</th>
                        <th>User</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activity as $entry): ?>
                        <tr>
                            <td class="text-nowrap">
                                <?php echo date('M j, Y H:i', strtotime($entry->performed_at)) ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php
                                    echo match($entry->action) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'claimed' => 'primary',
                                        'started' => 'info',
                                        'returned' => 'warning',
                                        'completed' => 'success',
                                        'released' => 'secondary',
                                        default => 'secondary'
                                    };
                                ?>"><?php echo ucfirst($entry->action) ?></span>
                            </td>
                            <td><?php echo esc_entities($entry->workflow_name ?? '-') ?></td>
                            <td>
                                <?php if ($entry->object_id): ?>
                                    <a href="<?php echo url_for("workflow/history/{$entry->object_id}") ?>">
                                        <?php echo esc_entities($entry->object_title ?? "Object #{$entry->object_id}") ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif ?>
                            </td>
                            <td><?php echo esc_entities($entry->username ?? 'Unknown') ?></td>
                            <td><small><?php echo esc_entities($entry->comment ?? '-') ?></small></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
