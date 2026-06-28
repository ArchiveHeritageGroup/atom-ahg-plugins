<?php use_helper('Date') ?>

<?php include_partial('workflow/accessibilityHelpers') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-history me-2"></i>Workflow history
            <small class="text-muted">&mdash; <?php echo esc_entities($object->title ?? "Object #{$objectId}") ?></small>
        </h1>
        <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'history']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>All activity
        </a>
    </div>

    <?php if (empty($history)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-history fa-4x mb-3 opacity-50"></i>
            <h4>No workflow history for this item yet</h4>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th>Action</th>
                        <th>Workflow</th>
                        <th>Step</th>
                        <th>Status</th>
                        <th>User</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $entry): ?>
                        <tr>
                            <td class="text-nowrap">
                                <?php echo $entry->performed_at ? date('M j, Y H:i', strtotime($entry->performed_at)) : '-' ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php
                                    echo match($entry->action ?? '') {
                                        'approved', 'completed' => 'success',
                                        'rejected' => 'danger',
                                        'claimed' => 'primary',
                                        'started' => 'info',
                                        'returned' => 'warning',
                                        'released' => 'secondary',
                                        default => 'secondary'
                                    };
                                ?>"><?php echo ucfirst($entry->action ?? '-') ?></span>
                            </td>
                            <td><?php echo esc_entities($entry->workflow_name ?? '-') ?></td>
                            <td><?php echo esc_entities($entry->step_name ?? '-') ?></td>
                            <td class="text-nowrap">
                                <?php if (!empty($entry->from_status) || !empty($entry->to_status)): ?>
                                    <small class="text-muted"><?php echo esc_entities($entry->from_status ?? '?') ?></small>
                                    <i class="fas fa-arrow-right mx-1 small"></i>
                                    <small><?php echo esc_entities($entry->to_status ?? '?') ?></small>
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
