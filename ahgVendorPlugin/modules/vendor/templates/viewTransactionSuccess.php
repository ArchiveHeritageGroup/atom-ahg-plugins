<?php 
$transactionRaw = isset($sf_data) ? $sf_data->getRaw('transaction') : $transaction;
$vendorRaw = isset($sf_data) ? $sf_data->getRaw('vendor') : $vendor;
$itemsRaw = isset($sf_data) ? $sf_data->getRaw('items') : $items;

$statusColors = [
    'pending' => 'warning',
    'approved' => 'info',
    'in_progress' => 'primary',
    'completed' => 'success',
    'cancelled' => 'secondary',
    'on_hold' => 'dark'
];
?>

<div class="container-fluid px-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'vendor', 'action' => 'index']); ?>">Vendor Management</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'vendor', 'action' => 'transactions']); ?>">Transactions</a></li>
            <li class="breadcrumb-item active"><?php echo esc_entities($transactionRaw->transaction_number); ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <i class="fas fa-file-invoice me-2"></i><?php echo esc_entities($transactionRaw->transaction_number); ?>
            <span class="badge bg-<?php echo $statusColors[$transactionRaw->status] ?? 'secondary'; ?> ms-2">
                <?php echo ucfirst(str_replace('_', ' ', $transactionRaw->status)); ?>
            </span>
        </h1>
        <div>
            <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'editTransaction', 'id' => $transactionRaw->id]); ?>" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i>Edit
            </a>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#statusModal">
                <i class="fas fa-sync me-1"></i>Update Status
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Transaction Details -->
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-info-circle me-2"></i>Transaction Details</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><th width="40%">Transaction #</th><td><code><?php echo esc_entities($transactionRaw->transaction_number); ?></code></td></tr>
                                <tr><th>Vendor</th><td><a href="<?php echo url_for(['module' => 'vendor', 'action' => 'view', 'slug' => $vendorRaw->slug]); ?>"><?php echo esc_entities($vendorRaw->name); ?></a></td></tr>
                                <tr><th>Service Type</th><td><?php echo esc_entities($transactionRaw->service_name ?? '-'); ?></td></tr>
                                <tr><th>Priority</th><td><span class="badge bg-<?php echo ['low'=>'success','normal'=>'primary','high'=>'warning','urgent'=>'danger'][$transactionRaw->priority ?? 'normal'] ?? 'secondary'; ?>"><?php echo ucfirst($transactionRaw->priority ?? 'normal'); ?></span></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><th width="40%">Request Date</th><td><?php echo $transactionRaw->request_date ? date('d M Y', strtotime($transactionRaw->request_date)) : '-'; ?></td></tr>
                                <tr><th>Due Date</th><td><?php echo $transactionRaw->due_date ? date('d M Y', strtotime($transactionRaw->due_date)) : '-'; ?></td></tr>
                                <tr><th>Completion Date</th><td><?php echo $transactionRaw->completion_date ? date('d M Y', strtotime($transactionRaw->completion_date)) : '-'; ?></td></tr>
                                <tr><th>Reference</th><td><?php echo esc_entities($transactionRaw->reference_number ?? '-'); ?></td></tr>
                            </table>
                        </div>
                    </div>
                    <?php if ($transactionRaw->description): ?>
                    <div class="mt-3"><strong>Description:</strong><p class="mb-0"><?php echo nl2br(esc_entities($transactionRaw->description)); ?></p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- GLAM/DAM Items -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-archive me-2"></i>GLAM/DAM Items</span>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus me-1"></i>Link Item
                    </button>
                </div>
                <div class="card-body p-0">
                    <?php $itemCount = $itemsRaw ? (is_array($itemsRaw) ? count($itemsRaw) : $itemsRaw->count()) : 0; ?>
                    <?php if ($itemCount > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Title</th><th>Identifier</th><th>Notes</th><th>Qty</th><th>Cost</th><th>Status</th><th width="80">Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php $total = 0; foreach ($itemsRaw as $item): $total += ($item->unit_cost ?? 0) * ($item->quantity ?? 1); ?>
                                <tr>
                                    <td><?php if (!empty($item->io_slug)): ?><a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->io_slug]); ?>" target="_blank"><i class="fas fa-external-link-alt fa-xs me-1"></i><?php echo esc_entities($item->io_title ?? 'Untitled'); ?></a><?php else: echo esc_entities($item->description ?? '-'); endif; ?></td>
                                    <td><code><?php echo esc_entities($item->identifier ?? '-'); ?></code></td>
                                    <td><small><?php echo esc_entities($item->notes ?? '-'); ?></small></td>
                                    <td><?php echo $item->quantity ?? 1; ?></td>
                                    <td><?php echo $item->unit_cost ? 'R' . number_format($item->unit_cost * ($item->quantity ?? 1), 2) : '-'; ?></td>
                                    <td><span class="badge bg-<?php echo ['pending'=>'warning','in_progress'=>'info','completed'=>'success'][$item->status ?? 'pending'] ?? 'secondary'; ?>"><?php echo ucfirst(str_replace('_', ' ', $item->status ?? 'pending')); ?></span></td>
                                    <td>
                                        <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'removeTransactionItem', 'transaction_id' => $transactionRaw->id, 'item_id' => $item->id]); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this item?');"><i class="fas fa-unlink"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light"><tr><th colspan="4" class="text-end">Total:</th><th><strong>R<?php echo number_format($total, 2); ?></strong></th><th colspan="2"></th></tr></tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted"><i class="fas fa-archive fa-2x mb-2"></i><p class="mb-0">No items linked yet</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($transactionRaw->notes): ?>
            <div class="card mb-4"><div class="card-header"><i class="fas fa-sticky-note me-2"></i>Notes</div><div class="card-body"><?php echo nl2br(esc_entities($transactionRaw->notes)); ?></div></div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <!-- Costs -->
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-dollar-sign me-2"></i>Cost Summary</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th>Estimated</th><td class="text-end"><?php echo $transactionRaw->estimated_cost ? 'R' . number_format($transactionRaw->estimated_cost, 2) : '-'; ?></td></tr>
                        <tr><th>Actual</th><td class="text-end"><strong><?php echo $transactionRaw->actual_cost ? 'R' . number_format($transactionRaw->actual_cost, 2) : '-'; ?></strong></td></tr>
                        <tr class="table-light"><th>Items Total</th><td class="text-end"><strong>R<?php echo number_format($total ?? 0, 2); ?></strong></td></tr>
                    </table>
                </div>
            </div>

            <!-- Invoice -->
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-file-invoice-dollar me-2"></i>Invoice</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th>Invoice #</th><td><?php echo esc_entities($transactionRaw->invoice_number ?? '-'); ?></td></tr>
                        <tr><th>Invoice Date</th><td><?php echo $transactionRaw->invoice_date ? date('d M Y', strtotime($transactionRaw->invoice_date)) : '-'; ?></td></tr>
                        <tr><th>Payment</th><td><span class="badge bg-<?php echo ['pending'=>'warning','paid'=>'success','partial'=>'info','overdue'=>'danger'][$transactionRaw->payment_status ?? 'pending'] ?? 'secondary'; ?>"><?php echo ucfirst($transactionRaw->payment_status ?? 'pending'); ?></span></td></tr>
                    </table>
                </div>
            </div>

            <!-- Record Info -->
            <div class="card">
                <div class="card-header"><i class="fas fa-info me-2"></i>Record Info</div>
                <div class="card-body"><small class="text-muted">Created: <?php echo date('d M Y H:i', strtotime($transactionRaw->created_at)); ?><br>Updated: <?php echo date('d M Y H:i', strtotime($transactionRaw->updated_at)); ?></small></div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url_for(['module' => 'vendor', 'action' => 'updateTransactionStatus', 'id' => $transactionRaw->id]); ?>">
                <div class="modal-header"><h5 class="modal-title">Update Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select name="status" class="form-select" required>
                            <?php foreach (['pending', 'approved', 'in_progress', 'on_hold', 'completed', 'cancelled'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $transactionRaw->status === $s ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $s)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Notes</label><textarea name="status_notes" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update</button></div>
            </form>
        </div>
    </div>
</div>

<?php include_partial('vendor/addItemModal', ['transactionRaw' => $transactionRaw]); ?>
