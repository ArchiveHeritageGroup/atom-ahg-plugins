<?php use_helper('Date') ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-boxes-stacked me-2"></i>
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
                       style="color: <?php echo htmlspecialchars($queue['color']) ?>"></i>
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
                   style="color: <?php echo htmlspecialchars($currentQueue->color) ?>"></i>
                <?php echo htmlspecialchars($currentQueue->name) ?>
            </h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print List
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($requests)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No requests in this queue.
            </div>
            <?php else: ?>
            <form method="post" id="queueForm">
                <input type="hidden" name="action" value="update_status">

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 30px;">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th>Request</th>
                                <th>Item</th>
                                <th>Location</th>
                                <th>Researcher</th>
                                <th>Booking</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="request_ids[]"
                                           value="<?php echo $request->id ?>" class="form-check-input request-checkbox">
                                </td>
                                <td>
                                    <strong>#<?php echo $request->id ?></strong>
                                    <?php if ($request->paging_slip_printed): ?>
                                    <span class="badge bg-secondary" title="Call slip printed">
                                        <i class="fas fa-print"></i>
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
                                    <?php
                                    $priorityClass = match($request->priority) {
                                        'rush' => 'danger',
                                        'high' => 'warning',
                                        default => 'secondary',
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $priorityClass ?>">
                                        <?php echo ucfirst($request->priority ?? 'normal') ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for('research/printCallSlips?ids=' . $request->id) ?>"
                                       class="btn btn-sm btn-outline-secondary" target="_blank" title="Print Call Slip">
                                        <i class="fas fa-print"></i>
                                    </a>
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
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Update Selected
                                </button>
                                <a href="<?php echo url_for('research/printCallSlips') ?>"
                                   class="btn btn-outline-secondary" id="printSelectedBtn" target="_blank">
                                    <i class="fas fa-print me-1"></i> Print Selected
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
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
        const btn = document.getElementById('printSelectedBtn');
        if (btn) {
            btn.href = '<?php echo url_for('research/printCallSlips') ?>?ids=' + ids;
        }
    }
});
</script>
