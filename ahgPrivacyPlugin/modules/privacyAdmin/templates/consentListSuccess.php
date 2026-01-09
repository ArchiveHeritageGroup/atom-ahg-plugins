<?php use_helper('Date') ?>
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-check2-circle me-2"></i><?php echo __('Consent Records'); ?></h1>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'consentAdd']); ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i><?php echo __('Record Consent'); ?></a>
    </div>
    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><?php echo __('Consent Records'); ?></span>
            <span class="badge bg-secondary"><?php echo count($consents ?? []) ?> <?php echo __('records'); ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($consents)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                <p class="mb-0"><?php echo __('No consent records found'); ?></p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Data Subject'); ?></th>
                            <th><?php echo __('Name'); ?></th>
                            <th><?php echo __('Purpose'); ?></th>
                            <th><?php echo __('Consent'); ?></th>
                            <th><?php echo __('Date'); ?></th>
                            <th><?php echo __('Status'); ?></th>
                            <th><?php echo __('Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consents as $record): ?>
                        <tr>
                            <td><?php echo esc_entities($record->data_subject_id ?? ''); ?></td>
                            <td><?php echo esc_entities($record->subject_name ?? '-'); ?></td>
                            <td><?php echo esc_entities($record->purpose ?? ''); ?></td>
                            <td><?php echo ($record->consent_given ?? 0) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?></td>
                            <td><?php echo $record->consent_date ?? '-'; ?></td>
                            <td><span class="badge bg-<?php echo ($record->status ?? 'active') === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($record->status ?? 'active'); ?></span></td>
                            <td>
                                <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'consentView', 'id' => $record->id]); ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                                <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'consentEdit', 'id' => $record->id]); ?>" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
