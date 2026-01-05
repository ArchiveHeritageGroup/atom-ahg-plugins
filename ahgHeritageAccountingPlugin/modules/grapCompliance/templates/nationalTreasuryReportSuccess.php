<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('National Treasury Report') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1"><i class="fas fa-file-alt me-2"></i><?php echo __('National Treasury Report') ?></h1>
                <p class="text-muted mb-0"><?php echo __('GRAP 103 Heritage Assets - Financial Year %1%', ['%1%' => $financialYear]) ?></p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i><?php echo __('Print') ?></button>
        </div>
    </div>

    <!-- Summary by Class -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><?php echo __('Summary by Asset Class') ?></h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('Asset Class') ?></th>
                        <th class="text-end"><?php echo __('Count') ?></th>
                        <th class="text-end"><?php echo __('Total Carrying Amount') ?></th>
                        <th class="text-end"><?php echo __('Total Impairment') ?></th>
                        <th class="text-end"><?php echo __('Revaluation Surplus') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $grandTotal = 0; $grandImpairment = 0; $grandSurplus = 0; $grandCount = 0; ?>
                    <?php foreach ($byClass as $class): ?>
                        <?php 
                        $grandTotal += $class->total_value; 
                        $grandImpairment += $class->total_impairment;
                        $grandSurplus += $class->total_surplus;
                        $grandCount += $class->count;
                        ?>
                        <tr>
                            <td><?php echo esc_entities($class->class_name ?: 'Unclassified') ?></td>
                            <td class="text-end"><?php echo number_format($class->count) ?></td>
                            <td class="text-end"><?php echo number_format($class->total_value, 2) ?></td>
                            <td class="text-end text-danger"><?php echo number_format($class->total_impairment, 2) ?></td>
                            <td class="text-end text-success"><?php echo number_format($class->total_surplus, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td><?php echo __('Grand Total') ?></td>
                        <td class="text-end"><?php echo number_format($grandCount) ?></td>
                        <td class="text-end"><?php echo number_format($grandTotal, 2) ?></td>
                        <td class="text-end text-danger"><?php echo number_format($grandImpairment, 2) ?></td>
                        <td class="text-end text-success"><?php echo number_format($grandSurplus, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Detailed Asset List -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><?php echo __('Heritage Asset Register') ?></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Asset ID') ?></th>
                            <th><?php echo __('Description') ?></th>
                            <th><?php echo __('Class') ?></th>
                            <th><?php echo __('Status') ?></th>
                            <th><?php echo __('Basis') ?></th>
                            <th class="text-end"><?php echo __('Cost') ?></th>
                            <th class="text-end"><?php echo __('Carrying Amount') ?></th>
                            <th><?php echo __('Acquisition Date') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td><?php echo esc_entities($asset->object_identifier ?: '-') ?></td>
                                <td><?php echo esc_entities($asset->object_title ?: '-') ?></td>
                                <td><?php echo esc_entities($asset->class_name ?: '-') ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $asset->recognition_status)) ?></td>
                                <td><?php echo ucfirst($asset->measurement_basis ?: '-') ?></td>
                                <td class="text-end"><?php echo number_format($asset->acquisition_cost, 2) ?></td>
                                <td class="text-end"><?php echo number_format($asset->current_carrying_amount, 2) ?></td>
                                <td><?php echo $asset->acquisition_date ?: '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
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
