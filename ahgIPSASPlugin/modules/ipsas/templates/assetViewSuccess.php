<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>">IPSAS</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assets']); ?>">Assets</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($asset->asset_number ?? 'Asset'); ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-archive me-2"></i><?php echo htmlspecialchars($asset->title); ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assetEdit', 'id' => $asset->id]); ?>" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> Edit
            </a>
            <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'valuationCreate']); ?>?asset_id=<?php echo $asset->id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-calculator me-1"></i> Add Valuation
            </a>
            <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assets']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Asset Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Asset Number</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($asset->asset_number ?? '-'); ?></dd>
                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($asset->description ?? '-')); ?></dd>
                        <dt class="col-sm-4">Category</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($asset->category_name ?? '-'); ?></dd>
                        <dt class="col-sm-4">Location</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($asset->location ?? '-'); ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Acquisition</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Acquisition Date</dt>
                        <dd class="col-sm-8"><?php echo isset($asset->acquisition_date) ? date('j F Y', strtotime($asset->acquisition_date)) : '-'; ?></dd>
                        <dt class="col-sm-4">Method</dt>
                        <dd class="col-sm-8"><?php echo ucfirst($asset->acquisition_method ?? '-'); ?></dd>
                        <dt class="col-sm-4">Source</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($asset->acquisition_source ?? '-'); ?></dd>
                        <dt class="col-sm-4">Cost</dt>
                        <dd class="col-sm-8">
                            <?php if ($asset->acquisition_cost): ?>
                                <?php echo $asset->acquisition_currency ?? 'USD'; ?> <?php echo number_format($asset->acquisition_cost, 2); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Financial</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Valuation Basis</dt>
                        <dd class="col-sm-8"><?php echo ucfirst(str_replace('_', ' ', $asset->valuation_basis ?? '-')); ?></dd>
                        <dt class="col-sm-4">Current Value</dt>
                        <dd class="col-sm-8">
                            <?php echo $asset->current_value_currency ?? 'USD'; ?>
                            <?php echo number_format($asset->current_value ?? 0, 2); ?>
                        </dd>
                        <dt class="col-sm-4">Insured Value</dt>
                        <dd class="col-sm-8">
                            <?php if ($asset->insured_value): ?>
                                <?php echo $asset->insured_value_currency ?? 'USD'; ?> <?php echo number_format($asset->insured_value, 2); ?>
                            <?php else: ?>
                                Not insured
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <?php if (!empty($valuations) && (is_array($valuations) ? count($valuations) > 0 : !$valuations->isEmpty())): ?>
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Valuation History</h5></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>Date</th><th>Type</th><th>Value</th><th>Valuer</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($valuations as $v): ?>
                            <tr>
                                <td><?php echo date('j M Y', strtotime($v->valuation_date)); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $v->valuation_type)); ?></td>
                                <td><?php echo $v->currency ?? 'USD'; ?> <?php echo number_format($v->new_value, 2); ?></td>
                                <td><?php echo htmlspecialchars($v->valuer_name ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($impairments) && (is_array($impairments) ? count($impairments) > 0 : !$impairments->isEmpty())): ?>
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Impairments</h5></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>Date</th><th>Type</th><th>Loss</th><th>Recognized</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($impairments as $imp): ?>
                            <tr>
                                <td><?php echo date('j M Y', strtotime($imp->assessment_date)); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $imp->impairment_type)); ?></td>
                                <td><?php echo $imp->currency ?? 'USD'; ?> <?php echo number_format($imp->impairment_loss, 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $imp->is_recognized ? 'success' : 'warning'; ?>">
                                        <?php echo $imp->is_recognized ? 'Yes' : 'Pending'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Status</h5></div>
                <div class="card-body text-center">
                    <?php
                    $statusColors = ['active' => 'success', 'on_loan' => 'info', 'in_storage' => 'secondary', 'disposed' => 'danger'];
                    $conditionColors = ['excellent' => 'success', 'good' => 'info', 'fair' => 'warning', 'poor' => 'danger', 'critical' => 'dark'];
                    ?>
                    <span class="badge bg-<?php echo $statusColors[$asset->status] ?? 'secondary'; ?> fs-5 px-4 py-2">
                        <?php echo ucfirst(str_replace('_', ' ', $asset->status)); ?>
                    </span>
                    <p class="mt-3 mb-1"><strong>Condition:</strong></p>
                    <span class="badge bg-<?php echo $conditionColors[$asset->condition_rating] ?? 'secondary'; ?>">
                        <?php echo ucfirst($asset->condition_rating ?? 'Unknown'); ?>
                    </span>
                </div>
            </div>

            <?php if ($asset->risk_level): ?>
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Risk Assessment</h5></div>
                <div class="card-body">
                    <?php $riskColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark']; ?>
                    <p class="mb-2"><strong>Risk Level:</strong>
                        <span class="badge bg-<?php echo $riskColors[$asset->risk_level] ?? 'secondary'; ?>">
                            <?php echo ucfirst($asset->risk_level); ?>
                        </span>
                    </p>
                    <?php if ($asset->risk_notes): ?>
                    <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($asset->risk_notes)); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Record</h5></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Created:</strong> <?php echo date('j M Y', strtotime($asset->created_at)); ?></p>
                    <?php if ($asset->updated_at): ?>
                    <p class="mb-0"><strong>Updated:</strong> <?php echo date('j M Y', strtotime($asset->updated_at)); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
