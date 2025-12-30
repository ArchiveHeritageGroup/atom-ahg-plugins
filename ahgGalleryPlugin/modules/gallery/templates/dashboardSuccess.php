<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reports', 'action' => 'index']); ?>">Reports</a></li>
        <li class="breadcrumb-item active">Gallery Management</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><i class="fas fa-palette text-primary me-2"></i>Gallery & Exhibition Management</h1>
    <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>
<?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>

<div class="row mb-4">
    <div class="col-md-2"><div class="card bg-success text-white h-100"><div class="card-body text-center"><h3 class="mb-0"><?php echo $stats['exhibitions_open']; ?></h3><small>Open Exhibitions</small></div></div></div>
    <div class="col-md-2"><div class="card bg-info text-white h-100"><div class="card-body text-center"><h3 class="mb-0"><?php echo $stats['exhibitions_planning']; ?></h3><small>Planning</small></div></div></div>
    <div class="col-md-2"><div class="card bg-warning text-dark h-100"><div class="card-body text-center"><h3 class="mb-0"><?php echo $stats['loans_active']; ?></h3><small>Active Loans</small></div></div></div>
    <div class="col-md-2"><div class="card bg-secondary text-white h-100"><div class="card-body text-center"><h3 class="mb-0"><?php echo $stats['loans_pending']; ?></h3><small>Pending Loans</small></div></div></div>
    <div class="col-md-2"><div class="card bg-primary text-white h-100"><div class="card-body text-center"><h3 class="mb-0"><?php echo $stats['artists_represented']; ?></h3><small>Represented</small></div></div></div>
    <div class="col-md-2"><div class="card bg-dark text-white h-100"><div class="card-body text-center"><h3 class="mb-0"><?php echo $stats['artists_total']; ?></h3><small>Total Artists</small></div></div></div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="fas fa-th-large me-2"></i>Quick Links</h5></div>
            <div class="list-group list-group-flush">
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'exhibitions']); ?>" class="list-group-item list-group-item-action"><i class="fas fa-images me-2"></i>Exhibitions</a>
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'createExhibition']); ?>" class="list-group-item list-group-item-action"><i class="fas fa-plus me-2"></i>New Exhibition</a>
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'loans']); ?>" class="list-group-item list-group-item-action"><i class="fas fa-exchange-alt me-2"></i>Loans</a>
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'createLoan']); ?>" class="list-group-item list-group-item-action"><i class="fas fa-plus me-2"></i>New Loan</a>
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'venues']); ?>" class="list-group-item list-group-item-action"><i class="fas fa-building me-2"></i>Venues & Spaces</a>
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'valuations']); ?>" class="list-group-item list-group-item-action"><i class="fas fa-dollar-sign me-2"></i>Valuations</a>
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'artists']); ?>" class="list-group-item list-group-item-action"><i class="fas fa-user-circle me-2"></i>Artists</a>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Upcoming Exhibitions</h5></div>
            <?php if (!empty($stats['upcoming_exhibitions'])): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($stats['upcoming_exhibitions'] as $e): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'viewExhibition', 'id' => $e->id]); ?>"><?php echo $e->title; ?></a>
                            <span class="text-muted"><?php echo $e->start_date; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="card-body text-muted">No upcoming exhibitions</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-warning"><h5 class="mb-0"><i class="fas fa-clock me-2"></i>Expiring Loans</h5></div>
            <?php if (!empty($stats['expiring_loans'])): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($stats['expiring_loans'] as $l): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'viewLoan', 'id' => $l->id]); ?>"><?php echo $l->loan_number; ?></a>
                            <span class="badge bg-danger"><?php echo $l->loan_end_date; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="card-body text-muted">No loans expiring soon</div>
            <?php endif; ?>
        </div>
    </div>
</div>
