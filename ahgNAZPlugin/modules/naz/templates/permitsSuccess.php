<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item active">Research Permits</li>
                </ol>
            </nav>
            <h1><i class="fas fa-id-card me-2"></i>Research Permits</h1>
            <p class="text-muted">Foreign researchers: US$200 fee | Local researchers: Free</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permitCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Application
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group">
                <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits']); ?>"
                   class="btn btn-<?php echo !$currentStatus ? 'primary' : 'outline-primary'; ?>">All</a>
                <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits', 'status' => 'pending']); ?>"
                   class="btn btn-<?php echo 'pending' === $currentStatus ? 'warning' : 'outline-warning'; ?>">Pending</a>
                <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits', 'status' => 'approved']); ?>"
                   class="btn btn-<?php echo 'approved' === $currentStatus ? 'info' : 'outline-info'; ?>">Approved</a>
                <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits', 'status' => 'active']); ?>"
                   class="btn btn-<?php echo 'active' === $currentStatus ? 'success' : 'outline-success'; ?>">Active</a>
                <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits', 'status' => 'expired']); ?>"
                   class="btn btn-<?php echo 'expired' === $currentStatus ? 'secondary' : 'outline-secondary'; ?>">Expired</a>
                <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits', 'status' => 'rejected']); ?>"
                   class="btn btn-<?php echo 'rejected' === $currentStatus ? 'danger' : 'outline-danger'; ?>">Rejected</a>
            </div>
        </div>
    </div>

    <!-- Permits Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($permits->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-id-card fa-3x mb-3"></i>
                    <p>No permits found.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Permit #</th>
                            <th>Researcher</th>
                            <th>Type</th>
                            <th>Research Topic</th>
                            <th>Valid Until</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permits as $permit): ?>
                            <?php
                            $isExpiring = 'active' === $permit->status && strtotime($permit->end_date) < strtotime('+30 days');
                            $statusColors = [
                                'pending' => 'warning',
                                'approved' => 'info',
                                'active' => 'success',
                                'expired' => 'secondary',
                                'rejected' => 'danger',
                                'revoked' => 'dark',
                            ];
                            $typeColors = [
                                'local' => 'success',
                                'foreign' => 'primary',
                                'institutional' => 'info',
                            ];
                            ?>
                            <tr class="<?php echo $isExpiring ? 'table-warning' : ''; ?>">
                                <td>
                                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permitView', 'id' => $permit->id]); ?>">
                                        <?php echo htmlspecialchars($permit->permit_number); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($permit->first_name.' '.$permit->last_name); ?>
                                    <br>
                                    <small class="badge bg-<?php echo $typeColors[$permit->researcher_type] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($permit->researcher_type); ?>
                                    </small>
                                </td>
                                <td><?php echo ucfirst($permit->permit_type); ?></td>
                                <td>
                                    <span title="<?php echo htmlspecialchars($permit->research_topic); ?>">
                                        <?php echo htmlspecialchars(substr($permit->research_topic, 0, 40)); ?>
                                        <?php echo strlen($permit->research_topic) > 40 ? '...' : ''; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $permit->end_date; ?>
                                    <?php if ($isExpiring): ?>
                                        <span class="badge bg-warning text-dark">Expiring</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($permit->fee_amount > 0): ?>
                                        US$<?php echo number_format($permit->fee_amount, 2); ?>
                                        <?php if ($permit->fee_paid): ?>
                                            <i class="fas fa-check-circle text-success" title="Paid"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger" title="Unpaid"></i>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Free</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$permit->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($permit->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permitView', 'id' => $permit->id]); ?>"
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
