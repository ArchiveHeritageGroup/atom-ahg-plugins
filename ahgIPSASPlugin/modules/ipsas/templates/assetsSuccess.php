<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>">IPSAS</a></li>
                    <li class="breadcrumb-item active">Asset Register</li>
                </ol>
            </nav>
            <h1><i class="fas fa-archive me-2"></i>Heritage Asset Register</h1>
            <p class="text-muted">IPSAS-compliant asset inventory</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assetCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add Asset
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->id; ?>" <?php echo $currentCategory == $cat->id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo 'active' === $currentStatus ? 'selected' : ''; ?>>Active</option>
                        <option value="on_loan" <?php echo 'on_loan' === $currentStatus ? 'selected' : ''; ?>>On Loan</option>
                        <option value="in_storage" <?php echo 'in_storage' === $currentStatus ? 'selected' : ''; ?>>In Storage</option>
                        <option value="disposed" <?php echo 'disposed' === $currentStatus ? 'selected' : ''; ?>>Disposed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="basis" class="form-select">
                        <option value="">All Valuation</option>
                        <option value="historical_cost" <?php echo 'historical_cost' === $currentBasis ? 'selected' : ''; ?>>Historical Cost</option>
                        <option value="fair_value" <?php echo 'fair_value' === $currentBasis ? 'selected' : ''; ?>>Fair Value</option>
                        <option value="nominal" <?php echo 'nominal' === $currentBasis ? 'selected' : ''; ?>>Nominal</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="q" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assets Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($assets->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-archive fa-3x mb-3"></i>
                    <p>No assets found.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Asset #</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Valuation Basis</th>
                            <th>Current Value</th>
                            <th>Status</th>
                            <th>Condition</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $asset): ?>
                            <?php
                            $statusColors = [
                                'active' => 'success',
                                'on_loan' => 'info',
                                'in_storage' => 'secondary',
                                'under_conservation' => 'warning',
                                'disposed' => 'danger',
                                'lost' => 'dark',
                            ];
                            $conditionColors = [
                                'excellent' => 'success',
                                'good' => 'info',
                                'fair' => 'warning',
                                'poor' => 'danger',
                                'critical' => 'dark',
                            ];
                            $basisColors = [
                                'historical_cost' => 'primary',
                                'fair_value' => 'success',
                                'nominal' => 'warning',
                                'not_recognized' => 'secondary',
                            ];
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assetView', 'id' => $asset->id]); ?>">
                                        <?php echo htmlspecialchars($asset->asset_number); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(substr($asset->title, 0, 50)); ?>
                                    <?php echo strlen($asset->title) > 50 ? '...' : ''; ?>
                                </td>
                                <td><?php echo htmlspecialchars($asset->category_name ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $basisColors[$asset->valuation_basis] ?? 'secondary'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $asset->valuation_basis)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $asset->current_value_currency ?? 'USD'; ?>
                                    <?php echo number_format($asset->current_value ?? 0, 2); ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$asset->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $asset->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $conditionColors[$asset->condition_rating] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($asset->condition_rating); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assetView', 'id' => $asset->id]); ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
