<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requests']); ?>">Requests</a></li>
                    <li class="breadcrumb-item active">Request #<?php echo $dsRequest->id; ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-user-clock me-2"></i>Data Subject Request</h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requests']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Requests
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Request Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Request Type</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-primary fs-6"><?php echo ucfirst(str_replace('_', ' ', $dsRequest->request_type)); ?></span>
                        </dd>

                        <dt class="col-sm-4">Reference Number</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($dsRequest->reference_number ?? 'DSR-' . $dsRequest->id); ?></dd>

                        <dt class="col-sm-4">Submitted</dt>
                        <dd class="col-sm-8"><?php echo date('j F Y H:i', strtotime($dsRequest->created_at)); ?></dd>

                        <dt class="col-sm-4">Due Date</dt>
                        <dd class="col-sm-8">
                            <?php
                            $isOverdue = strtotime($dsRequest->due_date) < time() && $dsRequest->status === 'pending';
                            ?>
                            <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : ''; ?>">
                                <?php echo date('j F Y', strtotime($dsRequest->due_date)); ?>
                                <?php if ($isOverdue): ?> (OVERDUE)<?php endif; ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($dsRequest->description ?? '-')); ?></dd>

                        <dt class="col-sm-4">Verification Method</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($dsRequest->verification_method ?? '-'); ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Data Subject Information</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($dsRequest->data_subject_name); ?></dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8"><a href="mailto:<?php echo htmlspecialchars($dsRequest->data_subject_email); ?>"><?php echo htmlspecialchars($dsRequest->data_subject_email); ?></a></dd>

                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($dsRequest->data_subject_phone ?? '-'); ?></dd>

                        <dt class="col-sm-4">ID Number</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($dsRequest->data_subject_id_number ?? '-'); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Status</h5></div>
                <div class="card-body text-center">
                    <?php
                    $statusColors = ['pending' => 'warning', 'in_progress' => 'info', 'completed' => 'success', 'rejected' => 'danger'];
                    ?>
                    <span class="badge bg-<?php echo $statusColors[$dsRequest->status] ?? 'secondary'; ?> fs-5 px-4 py-2">
                        <?php echo ucfirst(str_replace('_', ' ', $dsRequest->status)); ?>
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Request Types</h5></div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li><strong>Access:</strong> Right to obtain copy of data</li>
                        <li><strong>Rectification:</strong> Right to correct data</li>
                        <li><strong>Erasure:</strong> Right to delete data</li>
                        <li><strong>Portability:</strong> Right to data transfer</li>
                        <li><strong>Objection:</strong> Right to object to processing</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
