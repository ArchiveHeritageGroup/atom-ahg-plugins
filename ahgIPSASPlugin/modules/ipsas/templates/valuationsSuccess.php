<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>">IPSAS</a></li>
                    <li class="breadcrumb-item active">Valuations</li>
                </ol>
            </nav>
            <h1><i class="fas fa-calculator me-2"></i>Asset Valuations</h1>
            <p class="text-muted">Track asset value changes for IPSAS compliance</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'valuationCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Valuation
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="initial" <?php echo 'initial' === ($currentType ?? '') ? 'selected' : ''; ?>>Initial</option>
                        <option value="revaluation" <?php echo 'revaluation' === ($currentType ?? '') ? 'selected' : ''; ?>>Revaluation</option>
                        <option value="impairment" <?php echo 'impairment' === ($currentType ?? '') ? 'selected' : ''; ?>>Impairment</option>
                        <option value="reversal" <?php echo 'reversal' === ($currentType ?? '') ? 'selected' : ''; ?>>Reversal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="year" class="form-control" placeholder="Year" value="<?php echo htmlspecialchars($currentYear ?? date('Y')); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Valuations Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($valuations) || (is_object($valuations) && $valuations->isEmpty())): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-calculator fa-3x mb-3"></i>
                    <p>No valuations found.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Asset</th>
                            <th>Type</th>
                            <th>Basis</th>
                            <th>Previous Value</th>
                            <th>New Value</th>
                            <th>Change</th>
                            <th>Valuer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($valuations as $val): ?>
                            <?php
                            $change = ($val->new_value ?? 0) - ($val->previous_value ?? 0);
                            $typeColors = ['initial' => 'primary', 'revaluation' => 'success', 'impairment' => 'danger', 'reversal' => 'warning'];
                            ?>
                            <tr>
                                <td><?php echo date('j M Y', strtotime($val->valuation_date)); ?></td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assetView', 'id' => $val->asset_id]); ?>">
                                        <?php echo htmlspecialchars($val->asset_title ?? $val->asset_number ?? '#' . $val->asset_id); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $typeColors[$val->valuation_type] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($val->valuation_type); ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $val->valuation_basis ?? '-')); ?></td>
                                <td><?php echo $val->currency ?? 'USD'; ?> <?php echo number_format($val->previous_value ?? 0, 2); ?></td>
                                <td><?php echo $val->currency ?? 'USD'; ?> <?php echo number_format($val->new_value ?? 0, 2); ?></td>
                                <td class="<?php echo $change >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $change >= 0 ? '+' : ''; ?><?php echo number_format($change, 2); ?>
                                </td>
                                <td><?php echo htmlspecialchars($val->valuer_name ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
