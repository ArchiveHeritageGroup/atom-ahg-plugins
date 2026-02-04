<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item active">Protected Records</li>
                </ol>
            </nav>
            <h1><i class="fas fa-shield-alt me-2"></i>Protected Records</h1>
            <p class="text-muted">Records exempt from access under Section 12</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if ($records->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-shield-alt fa-3x mb-3"></i>
                    <p>No protected records registered.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Record</th>
                            <th>Protection Type</th>
                            <th>Reason</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                            <?php $typeColors = ['cabinet' => 'danger', 'security' => 'dark', 'personal' => 'info', 'legal' => 'warning', 'commercial' => 'primary']; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r->record_title ?? 'Record #' . $r->information_object_id); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $typeColors[$r->protection_type] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($r->protection_type); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($r->protection_reason, 0, 40)); ?></td>
                                <td><?php echo date('j M Y', strtotime($r->protection_start)); ?></td>
                                <td><?php echo $r->protection_end ? date('j M Y', strtotime($r->protection_end)) : 'Indefinite'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $r->status === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($r->status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
