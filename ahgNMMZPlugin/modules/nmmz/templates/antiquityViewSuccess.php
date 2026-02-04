<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'antiquities']); ?>">Antiquities</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($antiquity->accession_number ?? 'ANT-' . $antiquity->id); ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-vase me-2"></i><?php echo htmlspecialchars($antiquity->name); ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'antiquities']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Antiquities
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Object Details -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Object Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Accession Number</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($antiquity->accession_number ?? 'ANT-' . $antiquity->id); ?></dd>

                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($antiquity->name); ?></dd>

                        <dt class="col-sm-4">Object Type</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars(ucfirst($antiquity->object_type ?? '-')); ?></dd>

                        <dt class="col-sm-4">Material</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($antiquity->material ?? '-'); ?></dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($antiquity->description ?? '-')); ?></dd>

                        <dt class="col-sm-4">Dimensions</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($antiquity->dimensions ?? '-'); ?></dd>

                        <dt class="col-sm-4">Estimated Age</dt>
                        <dd class="col-sm-8"><?php echo $antiquity->estimated_age_years ? $antiquity->estimated_age_years . ' years' : '-'; ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Provenance -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Provenance</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Provenance/History</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($antiquity->provenance ?? '-')); ?></dd>

                        <dt class="col-sm-4">Find Location</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($antiquity->find_location ?? '-'); ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Current Status -->
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Current Status</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Current Location</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($antiquity->current_location ?? '-'); ?></dd>

                        <dt class="col-sm-4">Estimated Value</dt>
                        <dd class="col-sm-8"><?php echo $antiquity->estimated_value ? '$' . number_format($antiquity->estimated_value, 2) : '-'; ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Status & Condition -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Status</h5></div>
                <div class="card-body text-center">
                    <?php
                    $statusColors = ['in_collection' => 'success', 'on_loan' => 'info', 'missing' => 'danger', 'exported' => 'warning'];
                    $conditionColors = ['excellent' => 'success', 'good' => 'info', 'fair' => 'warning', 'poor' => 'danger', 'fragmentary' => 'dark'];
                    ?>
                    <div class="mb-3">
                        <span class="badge bg-<?php echo $statusColors[$antiquity->status] ?? 'secondary'; ?> fs-5 px-4 py-2">
                            <?php echo ucfirst(str_replace('_', ' ', $antiquity->status ?? 'Unknown')); ?>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted">Condition:</small><br>
                        <span class="badge bg-<?php echo $conditionColors[$antiquity->condition_rating] ?? 'secondary'; ?>">
                            <?php echo ucfirst($antiquity->condition_rating ?? 'Unknown'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Export Permit Link -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Export Control</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Antiquities require NMMZ export permits for removal from Zimbabwe.</p>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'permitCreate']); ?>?antiquity_id=<?php echo $antiquity->id; ?>" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-file-export me-1"></i> Apply for Export Permit
                    </a>
                </div>
            </div>

            <!-- Record Info -->
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Record Info</h5></div>
                <div class="card-body">
                    <small class="text-muted">
                        <p class="mb-1"><strong>Created:</strong> <?php echo $antiquity->created_at ? date('j M Y H:i', strtotime($antiquity->created_at)) : '-'; ?></p>
                        <p class="mb-0"><strong>Updated:</strong> <?php echo $antiquity->updated_at ? date('j M Y H:i', strtotime($antiquity->updated_at)) : '-'; ?></p>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
