<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'reproductions']); ?>">Reproductions</a></li>
        <li class="breadcrumb-item active"><?php echo $reproductionRequest->reference_number ?: 'DRAFT-' . $reproductionRequest->id; ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h2">Reproduction Request</h1>
        <code><?php echo $reproductionRequest->reference_number ?: 'DRAFT-' . $reproductionRequest->id; ?></code>
        <span class="badge ms-2 bg-<?php echo match($reproductionRequest->status) { 'completed' => 'success', 'processing', 'in_production' => 'info', 'cancelled' => 'danger', 'draft' => 'secondary', default => 'warning' }; ?>">
            <?php echo ucfirst(str_replace('_', ' ', $reproductionRequest->status)); ?>
        </span>
    </div>
    <?php if ($reproductionRequest->status === 'draft'): ?>
        <form method="post" class="d-inline">
            <input type="hidden" name="form_action" value="submit">
            <button type="submit" class="btn btn-success" <?php echo empty($items) ? 'disabled' : ''; ?>>
                <i class="fas fa-paper-plane me-1"></i> Submit Request
            </button>
        </form>
    <?php endif; ?>
</div>

<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <!-- Request Details -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Request Details</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Purpose:</strong><br>
                        <?php echo ucfirst($reproductionRequest->purpose ?? 'Not specified'); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Delivery Method:</strong><br>
                        <?php echo ucfirst($reproductionRequest->delivery_method ?? 'Digital'); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Urgency:</strong><br>
                        <?php echo ucfirst($reproductionRequest->urgency ?? 'Normal'); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Submitted:</strong><br>
                        <?php echo $reproductionRequest->submitted_at ? date('M j, Y H:i', strtotime($reproductionRequest->submitted_at)) : 'Not submitted'; ?>
                    </div>
                </div>
                <?php if ($reproductionRequest->intended_use): ?>
                    <div class="mb-3">
                        <strong>Intended Use:</strong><br>
                        <?php echo nl2br(htmlspecialchars($reproductionRequest->intended_use)); ?>
                    </div>
                <?php endif; ?>
                <?php if ($reproductionRequest->special_instructions): ?>
                    <div class="mb-3">
                        <strong>Special Instructions:</strong><br>
                        <?php echo nl2br(htmlspecialchars($reproductionRequest->special_instructions)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Items</h5>
                <?php if ($reproductionRequest->status === 'draft'): ?>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus me-1"></i> Add Item
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($items)): ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Format</th>
                                <th>Qty</th>
                                <th>Cost</th>
                                <?php if ($reproductionRequest->status === 'draft'): ?>
                                    <th></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <?php if ($item->object_title): ?>
                                            <?php echo htmlspecialchars($item->object_title); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Object #<?php echo $item->object_id; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $item->reproduction_type)); ?></td>
                                    <td><?php echo $item->format ?: '-'; ?> <?php echo $item->size ?: ''; ?></td>
                                    <td><?php echo $item->quantity; ?></td>
                                    <td><?php echo $item->unit_cost ? 'R' . number_format($item->unit_cost * $item->quantity, 2) : '-'; ?></td>
                                    <?php if ($reproductionRequest->status === 'draft'): ?>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="form_action" value="remove_item">
                                                <input type="hidden" name="item_id" value="<?php echo $item->id; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-box-open fa-2x mb-2"></i>
                        <p>No items added yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Cost Summary -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Cost Summary</h6></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Subtotal:</td>
                        <td class="text-end">R<?php echo number_format($reproductionRequest->subtotal ?? 0, 2); ?></td>
                    </tr>
                    <?php if ($reproductionRequest->rush_fee ?? 0): ?>
                        <tr>
                            <td>Rush Fee:</td>
                            <td class="text-end">R<?php echo number_format($reproductionRequest->rush_fee, 2); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($reproductionRequest->delivery_fee ?? 0): ?>
                        <tr>
                            <td>Delivery:</td>
                            <td class="text-end">R<?php echo number_format($reproductionRequest->delivery_fee, 2); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="fw-bold">
                        <td>Total:</td>
                        <td class="text-end">R<?php echo number_format($reproductionRequest->total_cost ?? 0, 2); ?></td>
                    </tr>
                </table>
                <?php if ($reproductionRequest->status === 'quoted'): ?>
                    <hr>
                    <p class="small text-muted mb-0">Quote valid for 30 days from <?php echo date('M j, Y', strtotime($reproductionRequest->quoted_at)); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status Timeline -->
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-history me-2"></i>Timeline</h6></div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($reproductionRequest->created_at)); ?></small><br>
                    Request created
                </li>
                <?php if ($reproductionRequest->submitted_at): ?>
                    <li class="list-group-item">
                        <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($reproductionRequest->submitted_at)); ?></small><br>
                        Submitted for processing
                    </li>
                <?php endif; ?>
                <?php if ($reproductionRequest->quoted_at): ?>
                    <li class="list-group-item">
                        <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($reproductionRequest->quoted_at)); ?></small><br>
                        Quote provided
                    </li>
                <?php endif; ?>
                <?php if ($reproductionRequest->completed_at): ?>
                    <li class="list-group-item">
                        <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($reproductionRequest->completed_at)); ?></small><br>
                        Completed
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<?php if ($reproductionRequest->status === 'draft'): ?>
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="form_action" value="add_item">
                <div class="modal-header">
                    <h5 class="modal-title">Add Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Object ID *</label>
                        <input type="number" name="object_id" class="form-control" required>
                        <small class="text-muted">Enter the archive object ID</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reproduction Type *</label>
                        <select name="reproduction_type" class="form-select" required>
                            <option value="digital_scan">Digital Scan</option>
                            <option value="photograph">Photograph</option>
                            <option value="photocopy">Photocopy</option>
                            <option value="microfilm">Microfilm Copy</option>
                            <option value="certified_copy">Certified Copy</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Format</label>
                            <select name="format" class="form-select">
                                <option value="JPEG">JPEG</option>
                                <option value="TIFF">TIFF</option>
                                <option value="PDF">PDF</option>
                                <option value="PNG">PNG</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Size</label>
                            <select name="size" class="form-select">
                                <option value="A4">A4</option>
                                <option value="A3">A3</option>
                                <option value="A2">A2</option>
                                <option value="original">Original Size</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Resolution</label>
                            <select name="resolution" class="form-select">
                                <option value="300dpi">300 DPI (Standard)</option>
                                <option value="600dpi">600 DPI (High)</option>
                                <option value="1200dpi">1200 DPI (Archive)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Color</label>
                            <select name="color_mode" class="form-select">
                                <option value="color">Color</option>
                                <option value="grayscale">Grayscale</option>
                                <option value="bw">Black & White</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Special Instructions</label>
                        <textarea name="special_instructions" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
