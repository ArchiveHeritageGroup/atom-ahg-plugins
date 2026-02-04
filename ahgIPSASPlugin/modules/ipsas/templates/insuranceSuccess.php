<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>">IPSAS</a></li>
                    <li class="breadcrumb-item active">Insurance</li>
                </ol>
            </nav>
            <h1><i class="fas fa-shield-alt me-2"></i>Insurance Policies</h1>
            <p class="text-muted">Manage insurance coverage for heritage assets</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo 'active' === ($currentStatus ?? '') ? 'selected' : ''; ?>>Active</option>
                        <option value="expired" <?php echo 'expired' === ($currentStatus ?? '') ? 'selected' : ''; ?>>Expired</option>
                        <option value="cancelled" <?php echo 'cancelled' === ($currentStatus ?? '') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Policies Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($policies) || (is_object($policies) && $policies->isEmpty())): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-shield-alt fa-3x mb-3"></i>
                    <p>No insurance policies found.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Policy #</th>
                            <th>Provider</th>
                            <th>Coverage Type</th>
                            <th>Coverage Amount</th>
                            <th>Premium</th>
                            <th>Period</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($policies as $policy): ?>
                            <?php
                            $statusColors = ['active' => 'success', 'expired' => 'danger', 'cancelled' => 'secondary'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($policy->policy_number ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($policy->provider_name ?? '-'); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $policy->coverage_type ?? '-')); ?></td>
                                <td>
                                    <?php echo $policy->coverage_currency ?? 'USD'; ?>
                                    <?php echo number_format($policy->coverage_amount ?? 0, 2); ?>
                                </td>
                                <td>
                                    <?php echo $policy->premium_currency ?? 'USD'; ?>
                                    <?php echo number_format($policy->premium_amount ?? 0, 2); ?>
                                </td>
                                <td>
                                    <?php echo isset($policy->coverage_start) ? date('j M Y', strtotime($policy->coverage_start)) : '-'; ?> -
                                    <?php echo isset($policy->coverage_end) ? date('j M Y', strtotime($policy->coverage_end)) : '-'; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$policy->status ?? ''] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($policy->status ?? 'Unknown'); ?>
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
