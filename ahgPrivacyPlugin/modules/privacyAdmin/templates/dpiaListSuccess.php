<?php $rows = $sf_data->getRaw('dpias'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="h2"><i class="fas fa-shield-halved me-2"></i><?php echo __('Data Protection Impact Assessments (DPIA)'); ?></span>
            <div class="text-muted small mt-1"><?php echo __('GDPR Article 35 — assessments of high-risk processing'); ?></div>
        </div>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dpiaForm']); ?>" class="btn btn-success">
            <i class="fas fa-plus me-1"></i><?php echo __('New DPIA'); ?>
        </a>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>
    <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
    <?php endif; ?>

    <?php
    $badge = ['draft' => 'secondary', 'review' => 'warning', 'completed' => 'success', 'archived' => 'dark'];
    ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('Name'); ?></th>
                        <th><?php echo __('Linked ROPA activity'); ?></th>
                        <th><?php echo __('Risk'); ?></th>
                        <th><?php echo __('Status'); ?></th>
                        <th><?php echo __('Signed off'); ?></th>
                        <th><?php echo __('Updated'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4"><?php echo __('No DPIAs recorded yet'); ?></td></tr>
                    <?php else: foreach ($rows as $d): ?>
                    <tr>
                        <td><a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dpiaForm', 'id' => $d->id]); ?>"><?php echo htmlspecialchars($d->name); ?></a></td>
                        <td><?php echo $d->activity_name ? htmlspecialchars($d->activity_name) : '<span class="text-muted">—</span>'; ?></td>
                        <td><?php echo $d->high_risk ? '<span class="badge bg-danger">'.__('High risk').'</span>' : '<span class="badge bg-light text-dark">'.__('Standard').'</span>'; ?></td>
                        <td><span class="badge bg-<?php echo $badge[$d->status] ?? 'secondary'; ?>"><?php echo __(ucfirst($d->status)); ?></span></td>
                        <td><?php echo $d->signed_off_at ? htmlspecialchars(substr($d->signed_off_at, 0, 10)) : '<span class="text-muted">—</span>'; ?></td>
                        <td class="text-muted small"><?php echo htmlspecialchars(substr((string) $d->updated_at, 0, 16)); ?></td>
                        <td class="text-end">
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dpiaForm', 'id' => $d->id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i></a>
                            <?php if ($d->status === 'completed'): ?>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dpiaArchive', 'id' => $d->id]); ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Archive'); ?>"><i class="fas fa-box-archive"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
