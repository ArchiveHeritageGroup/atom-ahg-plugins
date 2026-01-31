<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item active">Closure Periods</li>
                </ol>
            </nav>
            <h1><i class="fas fa-lock me-2"></i>Closure Periods</h1>
            <p class="text-muted">Section 10 - 25-year closure period for restricted records</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closureCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add Closure
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="btn-group">
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures']); ?>"
                           class="btn btn-<?php echo !$currentStatus ? 'primary' : 'outline-primary'; ?>">All</a>
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures', 'status' => 'active']); ?>"
                           class="btn btn-<?php echo 'active' === $currentStatus ? 'success' : 'outline-success'; ?>">Active</a>
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures', 'status' => 'expired']); ?>"
                           class="btn btn-<?php echo 'expired' === $currentStatus ? 'warning' : 'outline-warning'; ?>">Expired</a>
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures', 'status' => 'released']); ?>"
                           class="btn btn-<?php echo 'released' === $currentStatus ? 'info' : 'outline-info'; ?>">Released</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="btn-group float-end">
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures', 'type' => 'standard']); ?>"
                           class="btn btn-<?php echo 'standard' === $currentType ? 'secondary' : 'outline-secondary'; ?>">Standard</a>
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures', 'type' => 'extended']); ?>"
                           class="btn btn-<?php echo 'extended' === $currentType ? 'secondary' : 'outline-secondary'; ?>">Extended</a>
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures', 'type' => 'indefinite']); ?>"
                           class="btn btn-<?php echo 'indefinite' === $currentType ? 'secondary' : 'outline-secondary'; ?>">Indefinite</a>
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures', 'type' => 'ministerial']); ?>"
                           class="btn btn-<?php echo 'ministerial' === $currentType ? 'secondary' : 'outline-secondary'; ?>">Ministerial</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Closures Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($closures->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-lock-open fa-3x mb-3"></i>
                    <p>No closure periods found.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Record</th>
                            <th>Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Years</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($closures as $closure): ?>
                            <?php
                            $isExpired = $closure->end_date && strtotime($closure->end_date) < time();
                            $statusColors = [
                                'active' => 'success',
                                'expired' => 'warning',
                                'released' => 'info',
                                'extended' => 'secondary',
                            ];
                            $typeColors = [
                                'standard' => 'primary',
                                'extended' => 'warning',
                                'indefinite' => 'danger',
                                'ministerial' => 'dark',
                            ];
                            ?>
                            <tr class="<?php echo 'active' === $closure->status && $isExpired ? 'table-warning' : ''; ?>">
                                <td>
                                    <?php echo htmlspecialchars($closure->record_title ?? 'Record #'.$closure->information_object_id); ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $typeColors[$closure->closure_type] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($closure->closure_type); ?>
                                    </span>
                                </td>
                                <td><?php echo $closure->start_date; ?></td>
                                <td>
                                    <?php if ($closure->end_date): ?>
                                        <?php echo $closure->end_date; ?>
                                        <?php if ('active' === $closure->status && $isExpired): ?>
                                            <span class="badge bg-danger">PAST DUE</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Indefinite</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $closure->years ?? '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$closure->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($closure->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closureEdit', 'id' => $closure->id]); ?>"
                                       class="btn btn-sm btn-outline-primary">
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
