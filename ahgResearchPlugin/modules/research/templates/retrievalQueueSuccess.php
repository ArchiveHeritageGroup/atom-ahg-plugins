<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php include_partial('research/accessibilityHelpers') ?>
<?php use_helper('Date') ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-boxes-stacked me-2" aria-hidden="true"></i>
                Material Retrieval Queue
            </h1>
        </div>
    </div>

    <!-- Queue Summary Cards -->
    <div class="row mb-4">
        <?php foreach ($queueCounts as $code => $queue): ?>
        <div class="col-md-2 col-sm-4 mb-3">
            <a href="<?php echo url_for('research/retrievalQueue?queue=' . $code) ?>"
               class="card text-decoration-none h-100 <?php echo ($currentQueue && $currentQueue->code === $code) ? 'border-primary border-2' : '' ?>">
                <div class="card-body text-center">
                    <i class="fas fa-<?php echo htmlspecialchars($queue['icon']) ?> fa-2x mb-2"
                       style="color: <?php echo htmlspecialchars($queue['color']) ?>" aria-hidden="true"></i>
                    <h3 class="mb-0"><?php echo $queue['count'] ?></h3>
                    <small class="text-muted"><?php echo htmlspecialchars($queue['name']) ?></small>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($currentQueue): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-<?php echo htmlspecialchars($currentQueue->icon) ?> me-2"
                   style="color: <?php echo htmlspecialchars($currentQueue->color) ?>" aria-hidden="true"></i>
                <?php echo htmlspecialchars($currentQueue->name) ?>
            </h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print me-1" aria-hidden="true"></i> Print List
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($requests)): ?>
            <div class="alert alert-info" role="status">
                <i class="fas fa-info-circle me-2" aria-hidden="true"></i>
                No requests in this queue.
            </div>
            <?php else: ?>
            <form method="post" id="queueForm">
                <input type="hidden" name="form_action" value="update_status">

                <div class="table-responsive">
                    <table class="table table-hover" aria-label="Material retrieval queue requests">
                        <caption class="visually-hidden">List of material retrieval requests with status, priority, and actions</caption>
                        <thead>
                            <tr>
                                <th scope="col" style="width: 30px;">
                                    <input type="checkbox" id="selectAll" class="form-check-input" aria-label="Select all requests">
                                </th>
                                <th scope="col">Request</th>
                                <th scope="col">Item</th>
                                <th scope="col">Location</th>
                                <th scope="col">Researcher</th>
                                <th scope="col">Booking</th>
                                <th scope="col">Location</th>
                                <th scope="col">Priority</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="request_ids[]"
                                           value="<?php echo $request->id ?>" class="form-check-input request-checkbox"
                                           aria-label="Select request #<?php echo $request->id ?>">
                                </td>
                                <td>
                                    <strong>#<?php echo $request->id ?></strong>
                                    <?php if ($request->paging_slip_printed): ?>
                                    <span class="badge bg-secondary" title="Call slip printed" role="status" aria-label="Call slip printed">
                                        <i class="fas fa-print" aria-hidden="true"></i>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-medium"><?php echo htmlspecialchars($request->item_title ?? 'Untitled') ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($request->location_code) ?></small>
                                </td>
                                <td>
                                    <?php if ($request->shelf_location): ?>
                                    <small>
                                        <?php echo htmlspecialchars($request->shelf_location) ?>
                                        <?php if ($request->box_number): ?>
                                        <br>Box: <?php echo htmlspecialchars($request->box_number) ?>
                                        <?php endif; ?>
                                        <?php if ($request->folder_number): ?>
                                        / Folder: <?php echo htmlspecialchars($request->folder_number) ?>
                                        <?php endif; ?>
                                    </small>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($request->first_name . ' ' . $request->last_name) ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($request->room_name) ?></small>
                                </td>
                                <td>
                                    <?php echo date('M j', strtotime($request->booking_date)) ?>
                                    <br><small><?php echo substr($request->start_time, 0, 5) ?> - <?php echo substr($request->end_time, 0, 5) ?></small>
                                </td>
                                <td>
                                    <?php if ($request->location_current): ?>
                                        <small><?php echo htmlspecialchars($request->location_current) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <?php
                                    $priorityClass = match($request->priority) {
                                        'rush' => 'danger',
                                        'high' => 'warning',
                                        default => 'secondary',
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $priorityClass ?>" role="status"
                                          aria-label="<?php echo __('Priority: %1%', ['%1%' => ucfirst($request->priority ?? 'normal')]) ?>">
                                        <i class="fas fa-<?php echo match($request->priority) { 'rush' => 'bolt', 'high' => 'arrow-up', default => 'minus' } ?> me-1" aria-hidden="true"></i>
                                        <?php echo ucfirst($request->priority ?? 'normal') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="<?php echo __('Actions for request %1%', ['%1%' => $request->id]) ?>">
                                        <a href="<?php echo url_for('research/printCallSlips?ids=' . $request->id) ?>"
                                           class="btn btn-outline-secondary" target="_blank" title="<?php echo __('Print Call Slip') ?>">
                                            <i class="fas fa-print" aria-hidden="true"></i>
                                        </a>
                                        <?php if (in_array($request->status, ['retrieved', 'delivered'])): ?>
                                        <a href="<?php echo url_for("research/custody/{$request->id}/checkout") ?>"
                                           class="btn btn-outline-warning" title="<?php echo __('Checkout') ?>">
                                            <i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i>
                                        </a>
                                        <?php endif ?>
                                        <?php if ($request->status === 'in_use'): ?>
                                        <a href="<?php echo url_for("research/custody/{$request->id}/checkin") ?>"
                                           class="btn btn-outline-success" title="<?php echo __('Return') ?>">
                                            <i class="fas fa-arrow-right-to-bracket" aria-hidden="true"></i>
                                        </a>
                                        <?php endif ?>
                                        <?php if ($request->object_id ?? null): ?>
                                        <a href="<?php echo url_for("research/custody/chain/{$request->object_id}") ?>"
                                           class="btn btn-outline-info" title="<?php echo __('Custody Chain') ?>">
                                            <i class="fas fa-link" aria-hidden="true"></i>
                                        </a>
                                        <?php endif ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Batch Actions -->
                <div class="card bg-light mt-3">
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Update Status</label>
                                <select name="new_status" class="form-select">
                                    <option value="">-- Select Status --</option>
                                    <option value="requested">Requested</option>
                                    <option value="retrieved">Retrieved</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="in_use">In Use</option>
                                    <option value="returned">Returned</option>
                                    <option value="unavailable">Unavailable</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Notes (optional)</label>
                                <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                            </div>
                            <div class="col-md-5">
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check me-1" aria-hidden="true"></i> Update Selected
                                    </button>
                                    <a href="<?php echo url_for('research/printCallSlips') ?>"
                                       class="btn btn-outline-secondary" id="printSelectedBtn" target="_blank">
                                        <i class="fas fa-print me-1" aria-hidden="true"></i> Print
                                    </a>
                                    <a href="<?php echo url_for('research/batchCheckout') ?>"
                                       class="btn btn-outline-warning" id="batchCheckoutBtn">
                                        <i class="fas fa-arrow-right-from-bracket me-1" aria-hidden="true"></i> Checkout
                                    </a>
                                    <a href="<?php echo url_for('research/batchReturn') ?>"
                                       class="btn btn-outline-success" id="batchReturnBtn">
                                        <i class="fas fa-undo me-1" aria-hidden="true"></i> Return
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info" role="status">
        <i class="fas fa-info-circle me-2" aria-hidden="true"></i>
        Select a queue above to view requests.
    </div>
    <?php endif; ?>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.request-checkbox').forEach(cb => cb.checked = this.checked);
        updatePrintBtn();
    });

    // Update print button URL with selected IDs
    document.querySelectorAll('.request-checkbox').forEach(cb => {
        cb.addEventListener('change', updatePrintBtn);
    });

    function updatePrintBtn() {
        const ids = Array.from(document.querySelectorAll('.request-checkbox:checked'))
            .map(cb => cb.value).join(',');
        const printBtn = document.getElementById('printSelectedBtn');
        if (printBtn) {
            printBtn.href = '<?php echo url_for('research/printCallSlips') ?>?ids=' + ids;
        }
        const checkoutBtn = document.getElementById('batchCheckoutBtn');
        if (checkoutBtn) {
            checkoutBtn.href = '<?php echo url_for('research/batchCheckout') ?>?ids=' + ids;
        }
        const returnBtn = document.getElementById('batchReturnBtn');
        if (returnBtn) {
            returnBtn.href = '<?php echo url_for('research/batchReturn') ?>?ids=' + ids;
        }
    }
});
</script>
