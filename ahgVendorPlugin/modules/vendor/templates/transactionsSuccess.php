
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2"><i class="fas fa-exchange-alt me-2"></i>Vendor Transactions</h1>
        <div>
            <a href="<?php echo url_for('ahg_vend_index'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
            </a>
            <a href="<?php echo url_for('ahg_vend_transaction_add'); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>New Transaction
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?php echo url_for('ahg_vend_transactions'); ?>" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Transaction #..." value="<?php echo esc_entities($filters['search'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <?php foreach ($statusOptions as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo ($filters['status'] ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">All Vendors</option>
                        <?php foreach ($vendors as $vendor): ?>
                        <option value="<?php echo $vendor->id; ?>" <?php echo ($filters['vendor_id'] ?? '') == $vendor->id ? 'selected' : ''; ?>><?php echo esc_entities($vendor->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Service</label>
                    <select name="service_type_id" class="form-select">
                        <option value="">All Services</option>
                        <?php foreach ($serviceTypes as $service): ?>
                        <option value="<?php echo $service->id; ?>" <?php echo ($filters['service_type_id'] ?? '') == $service->id ? 'selected' : ''; ?>><?php echo esc_entities($service->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo esc_entities($filters['date_from'] ?? ''); ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo esc_entities($filters['date_to'] ?? ''); ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="overdue" value="1" class="form-check-input" id="overdueOnly" <?php echo ($filters['overdue'] ?? '') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="overdueOnly">Overdue</label>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header">
            <span class="badge bg-secondary me-2"><?php echo $transactions->count(); ?></span> Transactions
        </div>
        <div class="card-body p-0">
            <?php if ($transactions->count() > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Transaction #</th>
                            <th>Vendor</th>
                            <th>Service</th>
                            <th>Items</th>
                            <th>Request Date</th>
                            <th>Expected Return</th>
                            <th>Status</th>
                            <th>Est. Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $trans): ?>
                        <?php 
                        $isOverdue = $trans->expected_return_date 
                            && strtotime($trans->expected_return_date) < time() 
                            && !$trans->actual_return_date 
                            && !in_array($trans->status, ['returned', 'cancelled']);
                        ?>
                        <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                            <td>
                                <a href="<?php echo url_for('ahg_vend_transaction_view', ['id' => $trans->id]); ?>">
                                    <strong><?php echo esc_entities($trans->transaction_number); ?></strong>
                                </a>
                                <?php if ($isOverdue): ?>
                                <span class="badge bg-danger ms-1">Overdue</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo url_for('ahg_vend_view', ['slug' => $trans->vendor_slug]); ?>">
                                    <?php echo esc_entities($trans->vendor_name); ?>
                                </a>
                            </td>
                            <td><?php echo esc_entities($trans->service_name); ?></td>
                            <td><span class="badge bg-secondary"><?php echo $trans->item_count; ?></span></td>
                            <td><?php echo date('d M Y', strtotime($trans->request_date)); ?></td>
                            <td>
                                <?php if ($trans->expected_return_date): ?>
                                    <?php echo date('d M Y', strtotime($trans->expected_return_date)); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_partial('vendor/statusBadge', ['status' => $trans->status]); ?></td>
                            <td>
                                <?php if ($trans->actual_cost): ?>
                                R<?php echo number_format($trans->actual_cost, 2); ?>
                                <?php elseif ($trans->estimated_cost): ?>
                                <span class="text-muted">~R<?php echo number_format($trans->estimated_cost, 2); ?></span>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo url_for('ahg_vend_transaction_view', ['id' => $trans->id]); ?>" class="btn btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo url_for('ahg_vend_transaction_edit', ['id' => $trans->id]); ?>" class="btn btn-outline-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-exchange-alt fa-3x mb-3"></i>
                <p>No transactions found</p>
                <a href="<?php echo url_for('ahg_vend_transaction_add'); ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Create First Transaction
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
