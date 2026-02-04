<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item active">Data Protection Impact Assessments</li>
                </ol>
            </nav>
            <h1><i class="fas fa-clipboard-check me-2"></i>Data Protection Impact Assessments</h1>
            <p class="text-muted">DPIAs required for high-risk processing under CDPA</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpiaCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New DPIA
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if ($dpias->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-clipboard-check fa-3x mb-3"></i>
                    <p>No DPIAs on record.</p>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpiaCreate']); ?>" class="btn btn-primary">Create DPIA</a>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Processing Activity</th>
                            <th>Risk Level</th>
                            <th>Status</th>
                            <th>Assessor</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dpias as $dpia): ?>
                            <?php
                            $riskColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];
                            $statusColors = ['draft' => 'secondary', 'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpiaView', 'id' => $dpia->id]); ?>">
                                        <strong><?php echo htmlspecialchars($dpia->name); ?></strong>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($dpia->activity_name ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $riskColors[$dpia->risk_level] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($dpia->risk_level ?? 'Unknown'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$dpia->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($dpia->status ?? 'Draft'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($dpia->assessor_name ?? '-'); ?></td>
                                <td><?php echo date('j M Y', strtotime($dpia->created_at)); ?></td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpiaView', 'id' => $dpia->id]); ?>" class="btn btn-sm btn-outline-primary">
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
