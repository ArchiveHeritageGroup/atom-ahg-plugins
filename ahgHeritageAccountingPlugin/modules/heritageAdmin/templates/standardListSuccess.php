<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2"><i class="fas fa-balance-scale me-2"></i><?php echo __('Accounting Standards'); ?></h1>
        <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'standardAdd']); ?>" class="btn btn-success">
            <i class="fas fa-plus me-1"></i><?php echo __('Add Standard'); ?>
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
                        <th width="60"><?php echo __('Order'); ?></th>
                        <th width="100"><?php echo __('Code'); ?></th>
                        <th><?php echo __('Name'); ?></th>
                        <th><?php echo __('Country/Region'); ?></th>
                        <th width="80"><?php echo __('Capital.'); ?></th>
                        <th width="80"><?php echo __('Status'); ?></th>
                        <th width="150"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($standards as $s): ?>
                    <tr class="<?php echo $s->is_active ? '' : 'table-secondary'; ?>">
                        <td><?php echo $s->sort_order; ?></td>
                        <td><code><?php echo esc_entities($s->code); ?></code></td>
                        <td>
                            <strong><?php echo esc_entities($s->name); ?></strong>
                            <?php if ($s->description): ?>
                            <br><small class="text-muted"><?php echo truncate_text($s->description, 80); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_entities($s->country); ?></td>
                        <td class="text-center">
                            <?php if ($s->capitalisation_required): ?>
                            <span class="badge bg-warning text-dark"><?php echo __('Required'); ?></span>
                            <?php else: ?>
                            <span class="badge bg-secondary"><?php echo __('Optional'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($s->is_active): ?>
                            <span class="badge bg-success"><?php echo __('Active'); ?></span>
                            <?php else: ?>
                            <span class="badge bg-danger"><?php echo __('Disabled'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'ruleList', 'standard_id' => $s->id]); ?>" 
                               class="btn btn-sm btn-outline-info" title="<?php echo __('Rules'); ?>">
                                <i class="fas fa-clipboard-check"></i>
                            </a>
                            <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'standardEdit', 'id' => $s->id]); ?>" 
                               class="btn btn-sm btn-outline-primary" title="<?php echo __('Edit'); ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'standardToggle', 'id' => $s->id]); ?>" 
                               class="btn btn-sm btn-outline-<?php echo $s->is_active ? 'warning' : 'success'; ?>" 
                               title="<?php echo $s->is_active ? __('Disable') : __('Enable'); ?>">
                                <i class="fas fa-<?php echo $s->is_active ? 'toggle-on' : 'toggle-off'; ?>"></i>
                            </a>
                            <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'standardDelete', 'id' => $s->id]); ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('<?php echo __('Delete this standard?'); ?>')" 
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
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('About Accounting Standards'); ?></h5>
        </div>
        <div class="card-body">
            <p><?php echo __('Heritage accounting standards define how cultural and heritage assets should be recognized, measured, and disclosed in financial statements.'); ?></p>
            <ul class="mb-0">
                <li><strong>GRAP 103</strong> - South Africa (most comprehensive heritage-specific standard)</li>
                <li><strong>FRS 102</strong> - United Kingdom heritage assets section</li>
                <li><strong>GASB 34</strong> - US Government entities</li>
                <li><strong>FASB 958</strong> - US Non-profit organizations</li>
                <li><strong>PSAS 3150</strong> - Canada public sector</li>
                <li><strong>IPSAS 45</strong> - International (Africa, Asia, etc.)</li>
            </ul>
        </div>
    </div>
</div>
