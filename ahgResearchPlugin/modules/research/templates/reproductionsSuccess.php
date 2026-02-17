<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Reproduction Requests</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><i class="fas fa-copy text-primary me-2"></i>Reproduction Requests</h1>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'newReproduction']); ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> New Request
    </a>
</div>
<div class="row mb-3">
    <div class="col-md-4">
        <form method="get" class="d-flex gap-2">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach (['draft', 'submitted', 'quoted', 'approved', 'processing', 'in_production', 'completed', 'cancelled'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $sf_request->getParameter('status') === $s ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $s)); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if (!empty($requests)): ?>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>Purpose</th>
                        <th>Items</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><code><?php echo $req->reference_number ?: 'DRAFT-' . $req->id; ?></code></td>
                            <td><?php echo htmlspecialchars(substr($req->purpose ?? '', 0, 50)); ?></td>
                            <td><span class="badge bg-secondary"><?php echo $req->item_count ?? 0; ?></span></td>
                            <td><?php echo $req->total_cost ? 'R' . number_format($req->total_cost, 2) : '-'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo match($req->status) { 'completed' => 'success', 'processing', 'in_production' => 'info', 'cancelled' => 'danger', 'draft' => 'secondary', default => 'warning' }; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $req->status)); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($req->created_at)); ?></td>
                            <td>
                                <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewReproduction', 'id' => $req->id]); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-copy fa-3x text-muted mb-3"></i>
            <h5>No Reproduction Requests</h5>
            <p class="text-muted">Request copies or scans of archival materials.</p>
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'newReproduction']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Request
            </a>
        </div>
    </div>
<?php endif; ?>
