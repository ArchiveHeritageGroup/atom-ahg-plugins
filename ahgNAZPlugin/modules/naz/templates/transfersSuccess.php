<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item active">Records Transfers</li>
                </ol>
            </nav>
            <h1><i class="fas fa-truck me-2"></i>Records Transfers</h1>
            <p class="text-muted">Transfers of records to the National Archives of Zimbabwe</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'transferCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Transfer
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group">
                <a href="<?php echo url_for(['module' => 'naz', 'action' => 'transfers']); ?>"
                   class="btn btn-<?php echo !$currentStatus ? 'primary' : 'outline-primary'; ?>">All</a>
                <a href="<?php echo url_for(['module' => 'naz', 'action' => 'transfers', 'status' => 'proposed']); ?>"
                   class="btn btn-<?php echo 'proposed' === $currentStatus ? 'secondary' : 'outline-secondary'; ?>">Proposed</a>
                <a href="<?php echo url_for(['module' => 'naz', 'action' => 'transfers', 'status' => 'scheduled']); ?>"
                   class="btn btn-<?php echo 'scheduled' === $currentStatus ? 'info' : 'outline-info'; ?>">Scheduled</a>
                <a href="<?php echo url_for(['module' => 'naz', 'action' => 'transfers', 'status' => 'in_transit']); ?>"
                   class="btn btn-<?php echo 'in_transit' === $currentStatus ? 'warning' : 'outline-warning'; ?>">In Transit</a>
                <a href="<?php echo url_for(['module' => 'naz', 'action' => 'transfers', 'status' => 'received']); ?>"
                   class="btn btn-<?php echo 'received' === $currentStatus ? 'primary' : 'outline-primary'; ?>">Received</a>
                <a href="<?php echo url_for(['module' => 'naz', 'action' => 'transfers', 'status' => 'accessioned']); ?>"
                   class="btn btn-<?php echo 'accessioned' === $currentStatus ? 'success' : 'outline-success'; ?>">Accessioned</a>
            </div>
        </div>
    </div>

    <!-- Transfers Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($transfers->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-truck fa-3x mb-3"></i>
                    <p>No transfers found.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Transfer #</th>
                            <th>Agency</th>
                            <th>Type</th>
                            <th>Proposed Date</th>
                            <th>Quantity</th>
                            <th>Restricted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transfers as $transfer): ?>
                            <?php
                            $isOverdue = in_array($transfer->status, ['proposed', 'scheduled'])
                                && $transfer->proposed_date
                                && strtotime($transfer->proposed_date) < time();
                            $statusColors = [
                                'proposed' => 'secondary',
                                'scheduled' => 'info',
                                'in_transit' => 'warning',
                                'received' => 'primary',
                                'accessioned' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'dark',
                            ];
                            ?>
                            <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                                <td>
                                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'transferView', 'id' => $transfer->id]); ?>">
                                        <?php echo htmlspecialchars($transfer->transfer_number); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($transfer->transferring_agency); ?></td>
                                <td><?php echo ucfirst($transfer->transfer_type); ?></td>
                                <td>
                                    <?php echo $transfer->proposed_date ?? '-'; ?>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger">OVERDUE</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($transfer->quantity_linear_metres): ?>
                                        <?php echo number_format($transfer->quantity_linear_metres, 2); ?>m
                                    <?php endif; ?>
                                    <?php if ($transfer->quantity_boxes): ?>
                                        / <?php echo $transfer->quantity_boxes; ?> boxes
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($transfer->contains_restricted): ?>
                                        <span class="badge bg-danger"><i class="fas fa-lock"></i> Yes</span>
                                    <?php else: ?>
                                        <span class="text-muted">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$transfer->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $transfer->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'transferView', 'id' => $transfer->id]); ?>"
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
