<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('Valuation Report') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="fas fa-dollar-sign me-2"></i><?php echo __('Valuation Report') ?></h1>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i><?php echo __('Print') ?></button>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white"><?php echo __('Assets with Valuations') ?></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Identifier') ?></th>
                            <th><?php echo __('Title') ?></th>
                            <th><?php echo __('Class') ?></th>
                            <th><?php echo __('Last Valuation') ?></th>
                            <th class="text-end"><?php echo __('Valuation Amount') ?></th>
                            <th class="text-end"><?php echo __('Carrying Amount') ?></th>
                            <th class="text-end"><?php echo __('Revaluation Surplus') ?></th>
                            <th class="text-end"><?php echo __('Impairment') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td><?php echo esc_entities($asset->identifier ?: '-') ?></td>
                                <td><?php echo esc_entities($asset->title ?: '-') ?></td>
                                <td><?php echo esc_entities($asset->class_name ?: '-') ?></td>
                                <td><?php echo $asset->last_valuation_date ?></td>
                                <td class="text-end"><?php echo number_format($asset->last_valuation_amount, 2) ?></td>
                                <td class="text-end"><?php echo number_format($asset->current_carrying_amount, 2) ?></td>
                                <td class="text-end text-success"><?php echo number_format($asset->revaluation_surplus, 2) ?></td>
                                <td class="text-end text-danger"><?php echo number_format($asset->impairment_loss, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-print-none">
        <a href="<?php echo url_for(['module' => 'heritageReport', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Reports') ?>
        </a>
    </div>
</div>
