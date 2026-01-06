<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="h2"><i class="fas fa-clipboard-list me-2"></i><?php echo __('Record of Processing Activities (ROPA)'); ?></span>
        </div>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaAdd']); ?>" class="btn btn-success">
            <i class="fas fa-plus me-1"></i><?php echo __('Add Activity'); ?>
        </a>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('Name'); ?></th>
                        <th><?php echo __('Purpose'); ?></th>
                        <th><?php echo __('Lawful Basis'); ?></th>
                        <th><?php echo __('DPIA'); ?></th>
                        <th><?php echo __('Status'); ?></th>
                        <th><?php echo __('Next Review'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($activities->isEmpty()): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4"><?php echo __('No processing activities recorded'); ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($activities as $activity): ?>
                    <?php
                    $statusClasses = ['draft' => 'secondary', 'pending_review' => 'warning', 'approved' => 'success', 'archived' => 'dark'];
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaView', 'id' => $activity->id]); ?>">
                                <strong><?php echo esc_entities($activity->name); ?></strong>
                            </a>
                        </td>
                        <td><?php echo esc_entities(mb_substr($activity->purpose ?? '', 0, 50)); ?>...</td>
                        <td><?php echo esc_entities($lawfulBases[$activity->lawful_basis] ?? $activity->lawful_basis); ?></td>
                        <td>
                            <?php if ($activity->dpia_required): ?>
                                <?php if ($activity->dpia_completed): ?>
                                <span class="text-success"><i class="fas fa-check-circle"></i> <?php echo __('Complete'); ?></span>
                                <?php else: ?>
                                <span class="text-warning"><i class="fas fa-exclamation-circle"></i> <?php echo __('Required'); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $statusClasses[$activity->status] ?? 'secondary'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $activity->status)); ?>
                            </span>
                        </td>
                        <td><?php echo $activity->next_review_date ?? '-'; ?></td>
                        <td>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaEdit', 'id' => $activity->id]); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
