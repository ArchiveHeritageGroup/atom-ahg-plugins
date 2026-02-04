<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'transfers']); ?>">Transfers</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($transfer->transfer_number); ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-truck me-2"></i>Transfer <?php echo htmlspecialchars($transfer->transfer_number); ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'transfers']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Transfer Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Transfer Number</dt>
                        <dd class="col-sm-8"><strong><?php echo htmlspecialchars($transfer->transfer_number); ?></strong></dd>

                        <dt class="col-sm-4">Transferring Agency</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($transfer->transferring_agency); ?></dd>

                        <dt class="col-sm-4">Contact</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($transfer->agency_contact ?? '-'); ?></dd>

                        <dt class="col-sm-4">Transfer Type</dt>
                        <dd class="col-sm-8"><?php echo ucfirst($transfer->transfer_type ?? 'scheduled'); ?></dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($transfer->description ?? '-')); ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Quantity</h5></div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h4><?php echo $transfer->quantity_linear_metres ?? '-'; ?></h4>
                            <small class="text-muted">Linear Metres</small>
                        </div>
                        <div class="col-md-4">
                            <h4><?php echo $transfer->quantity_boxes ?? '-'; ?></h4>
                            <small class="text-muted">Boxes</small>
                        </div>
                        <div class="col-md-4">
                            <h4><?php echo $transfer->quantity_items ?? '-'; ?></h4>
                            <small class="text-muted">Items</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Status</h5></div>
                <div class="card-body text-center">
                    <?php $statusColors = ['proposed' => 'secondary', 'scheduled' => 'info', 'in_transit' => 'warning', 'received' => 'primary', 'accessioned' => 'success', 'rejected' => 'danger', 'cancelled' => 'dark']; ?>
                    <span class="badge bg-<?php echo $statusColors[$transfer->status] ?? 'secondary'; ?> fs-5 px-4 py-2">
                        <?php echo ucfirst(str_replace('_', ' ', $transfer->status)); ?>
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Dates</h5></div>
                <div class="card-body">
                    <p><strong>Date Range:</strong> <?php echo $transfer->date_range_start ?? '?'; ?> to <?php echo $transfer->date_range_end ?? '?'; ?></p>
                    <p><strong>Proposed:</strong> <?php echo $transfer->proposed_date ? date('j M Y', strtotime($transfer->proposed_date)) : '-'; ?></p>
                    <p class="mb-0"><strong>Received:</strong> <?php echo $transfer->actual_date ? date('j M Y', strtotime($transfer->actual_date)) : '-'; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
