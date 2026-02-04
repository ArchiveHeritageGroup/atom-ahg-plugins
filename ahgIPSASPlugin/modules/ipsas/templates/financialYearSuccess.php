<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>">IPSAS</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'reports']); ?>">Reports</a></li>
                    <li class="breadcrumb-item active">Financial Year <?php echo $year ?? date('Y'); ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-calendar-alt me-2"></i>Financial Year Summary <?php echo $year ?? date('Y'); ?></h1>
        </div>
        <div class="col-auto">
            <form method="get" class="d-flex gap-2">
                <select name="year" class="form-select" onchange="this.form.submit()">
                    <?php for ($y = date('Y'); $y >= date('Y') - 10; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($year ?? date('Y')) == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
    </div>

    <?php if (isset($summary)): ?>
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Assets</h6>
                    <h2 class="mb-0"><?php echo number_format($summary['total_assets'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Value</h6>
                    <h2 class="mb-0">$<?php echo number_format($summary['total_value'] ?? 0, 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Additions</h6>
                    <h2 class="mb-0"><?php echo number_format($summary['additions'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="text-dark-50">Impairments</h6>
                    <h2 class="mb-0">$<?php echo number_format($summary['impairments'] ?? 0, 0); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Value by Category</h5></div>
                <div class="card-body">
                    <?php if (!empty($summary['by_category'])): ?>
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Category</th><th class="text-end">Value</th></tr></thead>
                            <tbody>
                                <?php foreach ($summary['by_category'] as $cat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cat->name ?? 'Uncategorized'); ?></td>
                                    <td class="text-end">$<?php echo number_format($cat->total_value ?? 0, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted mb-0">No data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Value by Basis</h5></div>
                <div class="card-body">
                    <?php if (!empty($summary['by_basis'])): ?>
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Valuation Basis</th><th class="text-end">Value</th></tr></thead>
                            <tbody>
                                <?php foreach ($summary['by_basis'] as $basis): ?>
                                <tr>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $basis->valuation_basis ?? '-')); ?></td>
                                    <td class="text-end">$<?php echo number_format($basis->total_value ?? 0, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted mb-0">No data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Movement Summary</h5></div>
                <div class="card-body">
                    <table class="table mb-0">
                        <tbody>
                            <tr>
                                <td>Opening Balance</td>
                                <td class="text-end">$<?php echo number_format($summary['opening_balance'] ?? 0, 2); ?></td>
                            </tr>
                            <tr>
                                <td>Additions</td>
                                <td class="text-end text-success">+$<?php echo number_format($summary['additions_value'] ?? 0, 2); ?></td>
                            </tr>
                            <tr>
                                <td>Disposals</td>
                                <td class="text-end text-danger">-$<?php echo number_format($summary['disposals_value'] ?? 0, 2); ?></td>
                            </tr>
                            <tr>
                                <td>Revaluations</td>
                                <td class="text-end"><?php $rev = ($summary['revaluations'] ?? 0); echo ($rev >= 0 ? '+' : '') . '$' . number_format($rev, 2); ?></td>
                            </tr>
                            <tr>
                                <td>Impairments</td>
                                <td class="text-end text-danger">-$<?php echo number_format($summary['impairments'] ?? 0, 2); ?></td>
                            </tr>
                            <tr class="table-active">
                                <td><strong>Closing Balance</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($summary['closing_balance'] ?? $summary['total_value'] ?? 0, 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>No financial data available for this year.
    </div>
    <?php endif; ?>
</div>
