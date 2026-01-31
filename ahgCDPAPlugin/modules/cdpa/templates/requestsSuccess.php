<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item active">Data Subject Requests</li>
                </ol>
            </nav>
            <h1><i class="fas fa-user-clock me-2"></i>Data Subject Requests</h1>
            <p class="text-muted">30-day response deadline per CDPA requirements</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requestCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Request
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group">
                <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requests']); ?>"
                   class="btn btn-<?php echo !$currentStatus ? 'primary' : 'outline-primary'; ?>">All</a>
                <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requests', 'status' => 'pending']); ?>"
                   class="btn btn-<?php echo 'pending' === $currentStatus ? 'warning' : 'outline-warning'; ?>">Pending</a>
                <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requests', 'status' => 'in_progress']); ?>"
                   class="btn btn-<?php echo 'in_progress' === $currentStatus ? 'info' : 'outline-info'; ?>">In Progress</a>
                <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requests', 'status' => 'completed']); ?>"
                   class="btn btn-<?php echo 'completed' === $currentStatus ? 'success' : 'outline-success'; ?>">Completed</a>
                <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requests', 'status' => 'rejected']); ?>"
                   class="btn btn-<?php echo 'rejected' === $currentStatus ? 'danger' : 'outline-danger'; ?>">Rejected</a>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($requests->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No requests found.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Data Subject</th>
                            <th>Request Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <?php
                            $isOverdue = 'pending' === $req->status && strtotime($req->due_date) < time();
                            $statusColors = [
                                'pending' => 'warning',
                                'in_progress' => 'info',
                                'completed' => 'success',
                                'rejected' => 'danger',
                                'extended' => 'secondary',
                            ];
                            ?>
                            <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                                <td>
                                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requestView', 'id' => $req->id]); ?>">
                                        <?php echo htmlspecialchars($req->reference_number); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo ucfirst($req->request_type); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($req->data_subject_name); ?></td>
                                <td><?php echo $req->request_date; ?></td>
                                <td>
                                    <?php if ($isOverdue): ?>
                                        <span class="text-danger fw-bold"><?php echo $req->due_date; ?> (OVERDUE)</span>
                                    <?php else: ?>
                                        <?php echo $req->due_date; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$req->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $req->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requestView', 'id' => $req->id]); ?>"
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
