<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="h2"><i class="fas fa-globe me-2"></i><?php echo __('Privacy Jurisdictions'); ?></span>
        </div>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionAdd']); ?>" class="btn btn-success">
            <i class="fas fa-plus me-1"></i><?php echo __('Add Jurisdiction'); ?>
        </a>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>
    <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50"><?php echo __('Order'); ?></th>
                        <th width="80"><?php echo __('Code'); ?></th>
                        <th><?php echo __('Name'); ?></th>
                        <th><?php echo __('Country'); ?></th>
                        <th><?php echo __('Region'); ?></th>
                        <th width="80"><?php echo __('DSAR'); ?></th>
                        <th width="80"><?php echo __('Breach'); ?></th>
                        <th width="80"><?php echo __('Status'); ?></th>
                        <th width="150"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jurisdictions as $j): ?>
                    <tr class="<?php echo $j->is_active ? '' : 'table-secondary'; ?>">
                        <td><?php echo $j->sort_order; ?></td>
                        <td>
                            <?php if ($j->icon): ?>
                            <span class="fi fi-<?php echo $j->icon; ?> me-1"></span>
                            <?php endif; ?>
                            <code><?php echo strtoupper(esc_entities($j->code)); ?></code>
                        </td>
                        <td>
                            <strong><?php echo esc_entities($j->name); ?></strong>
                            <br><small class="text-muted"><?php echo esc_entities($j->full_name); ?></small>
                        </td>
                        <td><?php echo esc_entities($j->country); ?></td>
                        <td><span class="badge bg-info"><?php echo esc_entities($j->region); ?></span></td>
                        <td><?php echo $j->dsar_days; ?> <?php echo __('days'); ?></td>
                        <td><?php echo $j->breach_hours ?: '-'; ?> <?php echo $j->breach_hours ? __('hrs') : ''; ?></td>
                        <td class="text-center">
                            <?php if ($j->is_active): ?>
                            <span class="badge bg-success"><?php echo __('Active'); ?></span>
                            <?php else: ?>
                            <span class="badge bg-danger"><?php echo __('Disabled'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionEdit', 'id' => $j->id]); ?>" 
                               class="btn btn-sm btn-outline-primary" title="<?php echo __('Edit'); ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionToggle', 'id' => $j->id]); ?>" 
                               class="btn btn-sm btn-outline-<?php echo $j->is_active ? 'warning' : 'success'; ?>" 
                               title="<?php echo $j->is_active ? __('Disable') : __('Enable'); ?>">
                                <i class="fas fa-<?php echo $j->is_active ? 'toggle-on' : 'toggle-off'; ?>"></i>
                            </a>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionDelete', 'id' => $j->id]); ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('<?php echo __('Delete this jurisdiction?'); ?>')"
                               title="<?php echo __('Delete'); ?>">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('About Jurisdictions'); ?></h5>
        </div>
        <div class="card-body">
            <p class="mb-0"><?php echo __('Each jurisdiction defines the data protection law applicable to your organization. Configure DSAR response times, breach notification requirements, and regulatory contacts for each jurisdiction you operate in.'); ?></p>
        </div>
    </div>
</div>
