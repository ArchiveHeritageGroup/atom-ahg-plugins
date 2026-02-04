<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'monuments']); ?>">National Monuments</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($monument->monument_number ?? 'MON-' . $monument->id); ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-monument me-2"></i><?php echo htmlspecialchars($monument->name); ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'monuments']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Monuments
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Monument Details -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Monument Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Monument Number</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($monument->monument_number ?? 'MON-' . $monument->id); ?></dd>

                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($monument->name); ?></dd>

                        <dt class="col-sm-4">Category</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($monument->category_name ?? '-'); ?></dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($monument->description ?? '-')); ?></dd>

                        <dt class="col-sm-4">Historical Significance</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($monument->historical_significance ?? '-')); ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Location -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Location</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Province</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($monument->province ?? '-'); ?></dd>

                        <dt class="col-sm-4">District</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($monument->district ?? '-'); ?></dd>

                        <dt class="col-sm-4">Location Description</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($monument->location_description ?? '-')); ?></dd>

                        <?php if ($monument->gps_latitude && $monument->gps_longitude): ?>
                        <dt class="col-sm-4">GPS Coordinates</dt>
                        <dd class="col-sm-8">
                            <code><?php echo $monument->gps_latitude; ?>, <?php echo $monument->gps_longitude; ?></code>
                            <a href="https://www.google.com/maps?q=<?php echo $monument->gps_latitude; ?>,<?php echo $monument->gps_longitude; ?>" target="_blank" class="ms-2">
                                <i class="fas fa-external-link-alt"></i> View on Map
                            </a>
                        </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Inspections -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Inspection History</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($inspections->isEmpty()): ?>
                        <div class="p-4 text-center text-muted">
                            <p class="mb-0">No inspection records available.</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Inspector</th>
                                    <th>Condition</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inspections as $insp): ?>
                                    <tr>
                                        <td><?php echo date('j M Y', strtotime($insp->inspection_date)); ?></td>
                                        <td><?php echo htmlspecialchars($insp->inspector_name ?? '-'); ?></td>
                                        <td>
                                            <?php
                                            $condColors = ['excellent' => 'success', 'good' => 'info', 'fair' => 'warning', 'poor' => 'danger', 'critical' => 'dark'];
                                            ?>
                                            <span class="badge bg-<?php echo $condColors[$insp->condition_rating] ?? 'secondary'; ?>">
                                                <?php echo ucfirst($insp->condition_rating); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($insp->notes ?? '', 0, 50)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Status Cards -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Legal Status</h5></div>
                <div class="card-body text-center">
                    <?php
                    $legalColors = ['gazetted' => 'success', 'provisional' => 'info', 'proposed' => 'warning', 'delisted' => 'danger'];
                    ?>
                    <span class="badge bg-<?php echo $legalColors[$monument->legal_status] ?? 'secondary'; ?> fs-5 px-4 py-2">
                        <?php echo ucfirst($monument->legal_status ?? 'Unknown'); ?>
                    </span>
                    <?php if ($monument->gazette_date): ?>
                    <p class="mt-2 mb-0 small text-muted">
                        Gazetted: <?php echo date('j F Y', strtotime($monument->gazette_date)); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Condition & Protection</h5></div>
                <div class="card-body">
                    <?php
                    $conditionColors = ['excellent' => 'success', 'good' => 'info', 'fair' => 'warning', 'poor' => 'danger', 'critical' => 'dark'];
                    $statusColors = ['active' => 'success', 'at_risk' => 'warning', 'destroyed' => 'danger'];
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Condition:</span>
                        <span class="badge bg-<?php echo $conditionColors[$monument->condition_rating] ?? 'secondary'; ?>">
                            <?php echo ucfirst($monument->condition_rating ?? 'Unknown'); ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Status:</span>
                        <span class="badge bg-<?php echo $statusColors[$monument->status] ?? 'secondary'; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $monument->status ?? 'Unknown')); ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Protection Level:</span>
                        <span><?php echo ucfirst($monument->protection_level ?? '-'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Ownership:</span>
                        <span><?php echo ucfirst($monument->ownership_type ?? '-'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Record Info -->
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Record Info</h5></div>
                <div class="card-body">
                    <small class="text-muted">
                        <p class="mb-1"><strong>Registered:</strong> <?php echo $monument->created_at ? date('j M Y H:i', strtotime($monument->created_at)) : '-'; ?></p>
                        <p class="mb-0"><strong>Updated:</strong> <?php echo $monument->updated_at ? date('j M Y H:i', strtotime($monument->updated_at)) : '-'; ?></p>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
