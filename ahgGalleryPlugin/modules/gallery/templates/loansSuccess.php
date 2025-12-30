<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item active">Loans</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><i class="fas fa-exchange-alt text-primary me-2"></i>Loans</h1>
    <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'createLoan']); ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Loan</a>
</div>
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="incoming" <?php echo $currentType === 'incoming' ? 'selected' : ''; ?>>Incoming</option>
                    <option value="outgoing" <?php echo $currentType === 'outgoing' ? 'selected' : ''; ?>>Outgoing</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['inquiry', 'requested', 'approved', 'agreed', 'in_transit_out', 'on_loan', 'in_transit_return', 'returned', 'cancelled'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $currentStatus === $s ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $s)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-secondary w-100">Filter</button></div>
        </form>
    </div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Loan #</th><th>Type</th><th>Institution</th><th>Dates</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($loans)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No loans found</td></tr>
                <?php else: ?>
                    <?php foreach ($loans as $l): ?>
                        <tr>
                            <td><strong><?php echo $l->loan_number; ?></strong></td>
                            <td><span class="badge bg-<?php echo $l->loan_type === 'incoming' ? 'info' : 'warning'; ?>"><?php echo ucfirst($l->loan_type); ?></span></td>
                            <td><?php echo $l->institution_name; ?></td>
                            <td><?php echo $l->loan_start_date ? $l->loan_start_date . ' - ' . $l->loan_end_date : '-'; ?></td>
                            <td><?php
                                $colors = ['inquiry' => 'secondary', 'requested' => 'info', 'approved' => 'primary', 'agreed' => 'primary', 'on_loan' => 'success', 'in_transit_out' => 'warning', 'in_transit_return' => 'warning', 'returned' => 'secondary', 'cancelled' => 'danger'];
                                echo '<span class="badge bg-' . ($colors[$l->status] ?? 'secondary') . '">' . ucfirst(str_replace('_', ' ', $l->status)) . '</span>';
                            ?></td>
                            <td><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'viewLoan', 'id' => $l->id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
