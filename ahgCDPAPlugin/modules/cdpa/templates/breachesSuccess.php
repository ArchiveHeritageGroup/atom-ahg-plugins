<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item active">Breach Register</li>
                </ol>
            </nav>
            <h1><i class="fas fa-exclamation-triangle me-2"></i>Breach Register</h1>
            <p class="text-muted">POTRAZ notification required within 72 hours of discovery</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'breachCreate']); ?>" class="btn btn-danger">
                <i class="fas fa-plus me-1"></i> Report Breach
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group">
                <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'breaches']); ?>"
                   class="btn btn-<?php echo !$currentStatus ? 'primary' : 'outline-primary'; ?>">All</a>
                <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'breaches', 'status' => 'investigating']); ?>"
                   class="btn btn-<?php echo 'investigating' === $currentStatus ? 'warning' : 'outline-warning'; ?>">Investigating</a>
                <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'breaches', 'status' => 'contained']); ?>"
                   class="btn btn-<?php echo 'contained' === $currentStatus ? 'info' : 'outline-info'; ?>">Contained</a>
                <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'breaches', 'status' => 'resolved']); ?>"
                   class="btn btn-<?php echo 'resolved' === $currentStatus ? 'success' : 'outline-success'; ?>">Resolved</a>
            </div>
        </div>
    </div>

    <!-- Breaches Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($breaches->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                    <p>No breaches recorded.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Incident Date</th>
                            <th>Severity</th>
                            <th>POTRAZ Notified</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($breaches as $breach): ?>
                            <?php
                            $severityColors = [
                                'low' => 'success',
                                'medium' => 'warning',
                                'high' => 'danger',
                                'critical' => 'dark',
                            ];
                            $statusColors = [
                                'investigating' => 'warning',
                                'contained' => 'info',
                                'resolved' => 'success',
                                'ongoing' => 'danger',
                            ];
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'breachView', 'id' => $breach->id]); ?>">
                                        <?php echo htmlspecialchars($breach->reference_number); ?>
                                    </a>
                                </td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $breach->breach_type)); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($breach->incident_date)); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $severityColors[$breach->severity] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($breach->severity); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($breach->potraz_notified): ?>
                                        <span class="text-success"><i class="fas fa-check"></i> <?php echo date('Y-m-d', strtotime($breach->potraz_notified_date)); ?></span>
                                    <?php else: ?>
                                        <span class="text-danger"><i class="fas fa-times"></i> Not Yet</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$breach->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($breach->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'breachView', 'id' => $breach->id]); ?>"
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
