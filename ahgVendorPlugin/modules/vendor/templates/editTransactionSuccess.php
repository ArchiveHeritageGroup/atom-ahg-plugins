<?php
$transactionRaw = isset($sf_data) ? $sf_data->getRaw('transaction') : $transaction;
$vendorsRaw = isset($sf_data) ? $sf_data->getRaw('vendors') : $vendors;
$serviceTypesRaw = isset($sf_data) ? $sf_data->getRaw('serviceTypes') : $serviceTypes;
$statusOptionsRaw = isset($sf_data) ? $sf_data->getRaw('statusOptions') : (isset($statusOptions) ? $statusOptions : []);
$paymentStatusesRaw = isset($sf_data) ? $sf_data->getRaw('paymentStatuses') : (isset($paymentStatuses) ? $paymentStatuses : []);

$isNew = empty($transactionRaw->id);
$pageTitle = $isNew ? 'New Transaction' : 'Edit Transaction: ' . $transactionRaw->transaction_number;
?>

<div class="container-fluid px-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('ahg_vend_index'); ?>">Vendor Management</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('ahg_vend_transactions'); ?>">Transactions</a></li>
            <?php if (!$isNew): ?>
            <li class="breadcrumb-item"><a href="<?php echo url_for('ahg_vend_transaction_view', ['id' => $transactionRaw->id]); ?>"><?php echo esc_entities($transactionRaw->transaction_number); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?php echo $isNew ? 'New' : 'Edit'; ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <i class="fas fa-<?php echo $isNew ? 'plus' : 'edit'; ?> me-2"></i><?php echo $pageTitle; ?>
        </h1>
    </div>

    <form method="post" action="<?php echo url_for($isNew ? 'ahg_vend_transaction_add' : 'ahg_vend_transaction_edit', $isNew ? [] : ['id' => $transactionRaw->id ?? '']); ?>" class="needs-validation" novalidate>
        <?php if (!$isNew): ?>
        <input type="hidden" name="id" value="<?php echo $transactionRaw->id; ?>">
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>Transaction Details
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vendor *</label>
                                <select name="vendor_id" class="form-select" required>
                                    <option value="">Select Vendor...</option>
                                    <?php foreach ($vendorsRaw as $v): ?>
                                    <option value="<?php echo $v->id; ?>" <?php echo ($transactionRaw->vendor_id ?? '') == $v->id ? 'selected' : ''; ?>>
                                        <?php echo esc_entities($v->name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Service Type *</label>
                                <select name="service_type_id" class="form-select" required>
                                    <option value="">Select Service...</option>
                                    <?php foreach ($serviceTypesRaw as $st): ?>
                                    <option value="<?php echo $st->id; ?>" <?php echo ($transactionRaw->service_type_id ?? '') == $st->id ? 'selected' : ''; ?>>
                                        <?php echo esc_entities($st->name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Transaction Number</label>
                                <input type="text" name="transaction_number" class="form-control" 
                                       value="<?php echo esc_entities($transactionRaw->transaction_number ?? ''); ?>" 
                                       placeholder="Auto-generated if empty" <?php echo $isNew ? '' : 'readonly'; ?>>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control" 
                                       value="<?php echo esc_entities($transactionRaw->reference_number ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach ($statusOptionsRaw as $code => $label): ?>
                                    <option value="<?php echo $code; ?>" <?php echo ($transactionRaw->status ?? 'pending') === $code ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="low" <?php echo ($transactionRaw->priority ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="normal" <?php echo ($transactionRaw->priority ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                    <option value="high" <?php echo ($transactionRaw->priority ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="urgent" <?php echo ($transactionRaw->priority ?? '') === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo esc_entities($transactionRaw->description ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Dates -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-calendar me-2"></i>Dates
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Request Date *</label>
                                <input type="date" name="request_date" class="form-control" 
                                       value="<?php echo $transactionRaw->request_date ?? date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" name="due_date" class="form-control" 
                                       value="<?php echo $transactionRaw->due_date ?? ''; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Completion Date</label>
                                <input type="date" name="completion_date" class="form-control" 
                                       value="<?php echo $transactionRaw->completion_date ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Costs -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-dollar-sign me-2"></i>Costs
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estimated Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">R</span>
                                    <input type="number" name="estimated_cost" class="form-control" step="0.01" 
                                           value="<?php echo $transactionRaw->estimated_cost ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Actual Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">R</span>
                                    <input type="number" name="actual_cost" class="form-control" step="0.01" 
                                           value="<?php echo $transactionRaw->actual_cost ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-sticky-note me-2"></i>Notes
                    </div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="4"><?php echo esc_entities($transactionRaw->notes ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Invoice Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Invoice Details
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" name="invoice_number" class="form-control" 
                                   value="<?php echo esc_entities($transactionRaw->invoice_number ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Invoice Date</label>
                            <input type="date" name="invoice_date" class="form-control" 
                                   value="<?php echo $transactionRaw->invoice_date ?? ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <?php foreach ($paymentStatusesRaw as $code => $label): ?>
                                <option value="<?php echo $code; ?>" <?php echo ($transactionRaw->payment_status ?? 'pending') === $code ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" 
                                   value="<?php echo $transactionRaw->payment_date ?? ''; ?>">
                        </div>
                    </div>
                </div>

                <!-- Assignment -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user me-2"></i>Assignment
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Requested By</label>
                            <input type="text" name="requested_by" class="form-control" 
                                   value="<?php echo esc_entities($transactionRaw->requested_by ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assigned To</label>
                            <input type="text" name="assigned_to" class="form-control" 
                                   value="<?php echo esc_entities($transactionRaw->assigned_to ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i><?php echo $isNew ? 'Create Transaction' : 'Save Changes'; ?>
                            </button>
                            <?php if (!$isNew): ?>
                            <a href="<?php echo url_for('ahg_vend_transaction_view', ['id' => $transactionRaw->id]); ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <?php else: ?>
                            <a href="<?php echo url_for('ahg_vend_transactions'); ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>
