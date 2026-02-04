<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item active">Processing Activities</li>
                </ol>
            </nav>
            <h1><i class="fas fa-cogs me-2"></i>Processing Activities Register</h1>
            <p class="text-muted">Record of Processing Activities (ROPA) under CDPA</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'processingCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add Activity
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if ($activities->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-cogs fa-3x mb-3"></i>
                    <p>No processing activities registered.</p>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'processingCreate']); ?>" class="btn btn-primary">Register Activity</a>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Activity Name</th>
                            <th>Category</th>
                            <th>Legal Basis</th>
                            <th>Data Types</th>
                            <th>Flags</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $a): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($a->name); ?></strong></td>
                                <td><?php echo htmlspecialchars(ucfirst($a->category ?? '-')); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($a->legal_basis ?? '-'); ?></span></td>
                                <td><?php echo htmlspecialchars(substr($a->data_types ?? '', 0, 40)); ?></td>
                                <td>
                                    <?php if ($a->cross_border): ?><span class="badge bg-info me-1" title="Cross-border transfer">CB</span><?php endif; ?>
                                    <?php if ($a->children_data): ?><span class="badge bg-warning text-dark me-1" title="Children's data">CD</span><?php endif; ?>
                                    <?php if ($a->biometric_data): ?><span class="badge bg-danger me-1" title="Biometric data">BD</span><?php endif; ?>
                                    <?php if ($a->health_data): ?><span class="badge bg-danger me-1" title="Health data">HD</span><?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'processingEdit', 'id' => $a->id]); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
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
