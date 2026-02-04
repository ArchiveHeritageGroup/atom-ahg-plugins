<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item active">Records Schedules</li>
                </ol>
            </nav>
            <h1><i class="fas fa-calendar-alt me-2"></i>Records Schedules</h1>
            <p class="text-muted">Retention schedules for government agencies</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'scheduleCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Schedule
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if ($schedules->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                    <p>No records schedules found.</p>
                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'scheduleCreate']); ?>" class="btn btn-primary">Create Schedule</a>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Schedule #</th>
                            <th>Agency</th>
                            <th>Record Series</th>
                            <th>Retention</th>
                            <th>Disposal</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $s): ?>
                            <?php
                            $statusColors = ['draft' => 'secondary', 'pending' => 'warning', 'approved' => 'success', 'superseded' => 'info', 'archived' => 'dark'];
                            $disposalColors = ['destroy' => 'danger', 'transfer' => 'success', 'review' => 'warning', 'permanent' => 'info'];
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'scheduleView', 'id' => $s->id]); ?>">
                                        <?php echo htmlspecialchars($s->schedule_number); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars(substr($s->agency_name, 0, 30)); ?></td>
                                <td><?php echo htmlspecialchars(substr($s->record_series, 0, 35)); ?></td>
                                <td><?php echo $s->retention_period_active; ?>+<?php echo $s->retention_period_semi; ?> yrs</td>
                                <td>
                                    <span class="badge bg-<?php echo $disposalColors[$s->disposal_action] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($s->disposal_action); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$s->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($s->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'scheduleView', 'id' => $s->id]); ?>" class="btn btn-sm btn-outline-primary">
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
