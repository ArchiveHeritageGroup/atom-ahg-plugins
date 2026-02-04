<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'sites']); ?>">Archaeological Sites</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($site->site_number ?? 'SITE-' . $site->id); ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($site->name); ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'sites']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Sites
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Site Details -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Site Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Site Number</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($site->site_number ?? 'SITE-' . $site->id); ?></dd>

                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($site->name); ?></dd>

                        <dt class="col-sm-4">Site Type</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $site->site_type ?? '-'))); ?></dd>

                        <dt class="col-sm-4">Period</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($site->period ?? '-'); ?></dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($site->description ?? '-')); ?></dd>

                        <dt class="col-sm-4">Research Potential</dt>
                        <dd class="col-sm-8"><?php echo ucfirst($site->research_potential ?? '-'); ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Location -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Location</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Province</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($site->province ?? '-'); ?></dd>

                        <dt class="col-sm-4">District</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($site->district ?? '-'); ?></dd>

                        <dt class="col-sm-4">Location Description</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($site->location_description ?? '-')); ?></dd>

                        <?php if ($site->gps_latitude && $site->gps_longitude): ?>
                        <dt class="col-sm-4">GPS Coordinates</dt>
                        <dd class="col-sm-8">
                            <code><?php echo $site->gps_latitude; ?>, <?php echo $site->gps_longitude; ?></code>
                            <a href="https://www.google.com/maps?q=<?php echo $site->gps_latitude; ?>,<?php echo $site->gps_longitude; ?>" target="_blank" class="ms-2">
                                <i class="fas fa-external-link-alt"></i> View on Map
                            </a>
                        </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Discovery -->
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Discovery Information</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Discovery Date</dt>
                        <dd class="col-sm-8"><?php echo $site->discovery_date ? date('j F Y', strtotime($site->discovery_date)) : '-'; ?></dd>

                        <dt class="col-sm-4">Discovered By</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($site->discovered_by ?? '-'); ?></dd>

                        <dt class="col-sm-4">Registered</dt>
                        <dd class="col-sm-8"><?php echo $site->created_at ? date('j F Y', strtotime($site->created_at)) : '-'; ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Status -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Protection Status</h5></div>
                <div class="card-body text-center">
                    <?php
                    $statusColors = ['protected' => 'success', 'proposed' => 'info', 'at_risk' => 'warning', 'destroyed' => 'danger'];
                    $color = $statusColors[$site->protection_status] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $color; ?> fs-5 px-4 py-2">
                        <?php echo ucfirst(str_replace('_', ' ', $site->protection_status ?? 'Unknown')); ?>
                    </span>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Record Info</h5></div>
                <div class="card-body">
                    <small class="text-muted">
                        <p class="mb-1"><strong>Created:</strong> <?php echo $site->created_at ? date('j M Y H:i', strtotime($site->created_at)) : '-'; ?></p>
                        <p class="mb-0"><strong>Updated:</strong> <?php echo $site->updated_at ? date('j M Y H:i', strtotime($site->updated_at)) : '-'; ?></p>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
