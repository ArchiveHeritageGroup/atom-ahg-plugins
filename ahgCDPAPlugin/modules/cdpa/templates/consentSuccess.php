<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item active">Consent Management</li>
                </ol>
            </nav>
            <h1><i class="fas fa-check-circle me-2"></i>Consent Management</h1>
            <p class="text-muted">Track data subject consents under CDPA</p>
        </div>
        <div class="col-auto">
            <div class="btn-group">
                <?php if ($showAll): ?>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'consent']); ?>" class="btn btn-outline-primary">Active Only</a>
                <?php else: ?>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'consent', 'show_all' => 1]); ?>" class="btn btn-outline-primary">Show All</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if ($consents->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <p>No consent records found.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Data Subject</th>
                            <th>Purpose</th>
                            <th>Consent Date</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consents as $c): ?>
                            <?php
                            $isExpired = $c->expiry_date && strtotime($c->expiry_date) < time();
                            $isWithdrawn = $c->withdrawn_at;
                            ?>
                            <tr class="<?php echo ($isExpired || $isWithdrawn) ? 'table-secondary' : ''; ?>">
                                <td><?php echo htmlspecialchars($c->data_subject_name ?? $c->data_subject_email); ?></td>
                                <td><?php echo htmlspecialchars($c->purpose ?? '-'); ?></td>
                                <td><?php echo date('j M Y', strtotime($c->consent_date)); ?></td>
                                <td><?php echo $c->expiry_date ? date('j M Y', strtotime($c->expiry_date)) : 'No expiry'; ?></td>
                                <td>
                                    <?php if ($isWithdrawn): ?>
                                        <span class="badge bg-danger">Withdrawn</span>
                                    <?php elseif ($isExpired): ?>
                                        <span class="badge bg-warning text-dark">Expired</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?php echo ucfirst($c->consent_method ?? '-'); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
