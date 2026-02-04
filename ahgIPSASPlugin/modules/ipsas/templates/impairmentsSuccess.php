<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>">IPSAS</a></li>
                    <li class="breadcrumb-item active">Impairments</li>
                </ol>
            </nav>
            <h1><i class="fas fa-exclamation-triangle me-2"></i>Impairment Assessments</h1>
            <p class="text-muted">Track asset impairments under IPSAS 21/26</p>
        </div>
    </div>

    <!-- Impairments Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($impairments) || (is_object($impairments) && $impairments->isEmpty())): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <p>No impairment assessments found.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Asset</th>
                            <th>Type</th>
                            <th>Carrying Value</th>
                            <th>Recoverable Amount</th>
                            <th>Impairment Loss</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($impairments as $imp): ?>
                            <?php
                            $typeColors = ['physical_damage' => 'danger', 'obsolescence' => 'warning', 'market_decline' => 'info', 'legal_restriction' => 'secondary'];
                            ?>
                            <tr>
                                <td><?php echo date('j M Y', strtotime($imp->assessment_date)); ?></td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assetView', 'id' => $imp->asset_id]); ?>">
                                        <?php echo htmlspecialchars($imp->asset_title ?? '#' . $imp->asset_id); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $typeColors[$imp->impairment_type] ?? 'secondary'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $imp->impairment_type)); ?>
                                    </span>
                                </td>
                                <td><?php echo $imp->currency ?? 'USD'; ?> <?php echo number_format($imp->carrying_value ?? 0, 2); ?></td>
                                <td><?php echo $imp->currency ?? 'USD'; ?> <?php echo number_format($imp->recoverable_amount ?? 0, 2); ?></td>
                                <td class="text-danger"><?php echo $imp->currency ?? 'USD'; ?> <?php echo number_format($imp->impairment_loss ?? 0, 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($imp->is_recognized ?? false) ? 'success' : 'warning'; ?>">
                                        <?php echo ($imp->is_recognized ?? false) ? 'Recognized' : 'Pending'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
