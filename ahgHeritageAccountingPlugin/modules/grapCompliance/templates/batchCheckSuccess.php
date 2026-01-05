<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('GRAP 103 Batch Compliance Check') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="fas fa-check-double me-2"></i><?php echo __('Batch Compliance Check') ?></h1>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i><?php echo __('Print') ?></button>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white">
            <span><?php echo __('Checked %1% GRAP Assets', ['%1%' => count($results)]) ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Identifier') ?></th>
                            <th><?php echo __('Title') ?></th>
                            <th><?php echo __('Class') ?></th>
                            <th class="text-center"><?php echo __('Score') ?></th>
                            <th class="text-center"><?php echo __('Passed') ?></th>
                            <th class="text-center"><?php echo __('Warnings') ?></th>
                            <th class="text-center"><?php echo __('Failed') ?></th>
                            <th class="text-center"><?php echo __('Status') ?></th>
                            <th class="text-center"><?php echo __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                            <?php $asset = $result['asset']; $compliance = $result['compliance']; ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) ?>">
                                        <?php echo esc_entities($asset->object_identifier ?: 'N/A') ?>
                                    </a>
                                </td>
                                <td><?php echo esc_entities($asset->object_title ?: '-') ?></td>
                                <td><?php echo esc_entities($asset->class_name ?: '-') ?></td>
                                <td class="text-center">
                                    <strong class="<?php echo $compliance['summary']['score'] >= 80 ? 'text-success' : ($compliance['summary']['score'] >= 50 ? 'text-warning' : 'text-danger') ?>">
                                        <?php echo $compliance['summary']['score'] ?>%
                                    </strong>
                                </td>
                                <td class="text-center text-success"><?php echo $compliance['summary']['passed'] ?></td>
                                <td class="text-center text-warning"><?php echo $compliance['summary']['warnings'] ?></td>
                                <td class="text-center text-danger"><?php echo $compliance['summary']['failed'] ?></td>
                                <td class="text-center">
                                    <?php if ($compliance['summary']['status'] == 'compliant'): ?>
                                        <span class="badge bg-success"><?php echo __('Compliant') ?></span>
                                    <?php elseif ($compliance['summary']['status'] == 'partially_compliant'): ?>
                                        <span class="badge bg-warning text-dark"><?php echo __('Partial') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?php echo __('Non-Compliant') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?php echo url_for(['module' => 'grapCompliance', 'action' => 'check', 'id' => $asset->id]) ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('View Details') ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($results)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4"><?php echo __('No GRAP assets to check.') ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-print-none">
        <a href="<?php echo url_for(['module' => 'grapCompliance', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to GRAP Dashboard') ?>
        </a>
    </div>
</div>
