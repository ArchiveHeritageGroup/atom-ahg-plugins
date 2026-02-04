<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'schedules']); ?>">Schedules</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($schedule->schedule_number); ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-calendar-alt me-2"></i><?php echo htmlspecialchars($schedule->schedule_number); ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'schedules']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Schedule Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Agency Name</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($schedule->agency_name); ?></dd>

                        <dt class="col-sm-4">Agency Code</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($schedule->agency_code ?? '-'); ?></dd>

                        <dt class="col-sm-4">Record Series</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($schedule->record_series); ?></dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($schedule->description ?? '-')); ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Retention & Disposal</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Active Retention</dt>
                        <dd class="col-sm-8"><?php echo $schedule->retention_period_active; ?> years</dd>

                        <dt class="col-sm-4">Semi-active Retention</dt>
                        <dd class="col-sm-8"><?php echo $schedule->retention_period_semi; ?> years</dd>

                        <dt class="col-sm-4">Total Retention</dt>
                        <dd class="col-sm-8"><strong><?php echo $schedule->retention_period_active + $schedule->retention_period_semi; ?> years</strong></dd>

                        <dt class="col-sm-4">Disposal Action</dt>
                        <dd class="col-sm-8">
                            <?php $disposalColors = ['destroy' => 'danger', 'transfer' => 'success', 'review' => 'warning', 'permanent' => 'info']; ?>
                            <span class="badge bg-<?php echo $disposalColors[$schedule->disposal_action] ?? 'secondary'; ?> fs-6">
                                <?php echo ucfirst($schedule->disposal_action); ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Legal Authority</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($schedule->legal_authority ?? '-')); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Status</h5></div>
                <div class="card-body text-center">
                    <?php $statusColors = ['draft' => 'secondary', 'pending' => 'warning', 'approved' => 'success', 'superseded' => 'info', 'archived' => 'dark']; ?>
                    <span class="badge bg-<?php echo $statusColors[$schedule->status] ?? 'secondary'; ?> fs-5 px-4 py-2">
                        <?php echo ucfirst($schedule->status); ?>
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Classification</h5></div>
                <div class="card-body">
                    <p><strong>Classification:</strong> <?php echo ucfirst($schedule->classification ?? '-'); ?></p>
                    <p class="mb-0"><strong>Access:</strong> <?php echo ucfirst($schedule->access_restriction ?? 'Open'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
