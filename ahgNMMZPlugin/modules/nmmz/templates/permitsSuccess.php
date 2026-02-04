<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item active">Export Permits</li>
                </ol>
            </nav>
            <h1><i class="fas fa-file-export me-2"></i>Export Permits</h1>
            <p class="text-muted">Export permit applications under NMMZ Act [Chapter 25:11]</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'permitCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Application
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo 'pending' === $currentStatus ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo 'approved' === $currentStatus ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo 'rejected' === $currentStatus ? 'selected' : ''; ?>>Rejected</option>
                        <option value="expired" <?php echo 'expired' === $currentStatus ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Permits Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($permits->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-file-export fa-3x mb-3"></i>
                    <p>No export permits found.</p>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'permitCreate']); ?>" class="btn btn-primary">Create Application</a>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Permit #</th>
                            <th>Applicant</th>
                            <th>Object</th>
                            <th>Destination</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permits as $p): ?>
                            <?php
                            $statusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'expired' => 'secondary'];
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'permitView', 'id' => $p->id]); ?>">
                                        <?php echo htmlspecialchars($p->permit_number ?? 'EXP-' . $p->id); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars(substr($p->applicant_name, 0, 25)); ?><?php echo strlen($p->applicant_name) > 25 ? '...' : ''; ?></td>
                                <td><?php echo htmlspecialchars(substr($p->object_description ?? '', 0, 30)); ?><?php echo strlen($p->object_description ?? '') > 30 ? '...' : ''; ?></td>
                                <td><?php echo htmlspecialchars($p->destination_country ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($p->export_purpose ?? '-')); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$p->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($p->status ?? 'unknown'); ?>
                                    </span>
                                </td>
                                <td><?php echo $p->created_at ? date('Y-m-d', strtotime($p->created_at)) : '-'; ?></td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'permitView', 'id' => $p->id]); ?>" class="btn btn-sm btn-outline-primary">
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
