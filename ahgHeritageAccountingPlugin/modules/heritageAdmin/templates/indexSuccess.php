<?php use_helper('Text') ?>

<h1><i class="fas fa-landmark me-2"></i><?php echo __('Heritage Accounting') ?></h1>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary"><?php echo $stats['total_assets'] ?? 0 ?></h3>
                <small class="text-muted"><?php echo __('Total Assets') ?></small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo __('Assets by Standard') ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['by_standard']) && count($stats['by_standard']) > 0): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?php echo __('Standard') ?></th>
                                <th><?php echo __('Code') ?></th>
                                <th class="text-end"><?php echo __('Assets') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['by_standard'] as $row): ?>
                                <tr>
                                    <td><?php echo $row->name ?></td>
                                    <td><code><?php echo $row->code ?></code></td>
                                    <td class="text-end"><?php echo number_format($row->count) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted mb-0"><?php echo __('No assets recorded yet.') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo __('Accounting Standards') ?></h5>
            </div>
            <div class="card-body">
                <p><?php echo __('Manage heritage accounting standards (GRAP, FRS, GASB, PSAS, etc.)') ?></p>
                <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'standardList']) ?>" class="btn btn-primary">
                    <i class="fas fa-list me-1"></i><?php echo __('Manage Standards') ?>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo __('Valuation Rules') ?></h5>
            </div>
            <div class="card-body">
                <p><?php echo __('Configure valuation rules and calculation methods.') ?></p>
                <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'ruleList']) ?>" class="btn btn-primary">
                    <i class="fas fa-calculator me-1"></i><?php echo __('Manage Rules') ?>
                </a>
            </div>
        </div>
    </div>
</div>
