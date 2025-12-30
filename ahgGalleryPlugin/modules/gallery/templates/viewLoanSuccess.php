<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'loans']); ?>">Loans</a></li>
        <li class="breadcrumb-item active"><?php echo $loan->loan_number; ?></li>
    </ol>
</nav>
<?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
<?php $colors = ['inquiry' => 'secondary', 'requested' => 'info', 'approved' => 'primary', 'agreed' => 'primary', 'on_loan' => 'success', 'in_transit_out' => 'warning', 'in_transit_return' => 'warning', 'returned' => 'secondary', 'cancelled' => 'danger']; ?>
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h2 mb-1"><?php echo $loan->loan_number; ?> <span class="badge bg-<?php echo $loan->loan_type === 'incoming' ? 'info' : 'warning'; ?>"><?php echo ucfirst($loan->loan_type); ?></span> <span class="badge bg-<?php echo $colors[$loan->status] ?? 'secondary'; ?>"><?php echo ucfirst(str_replace('_', ' ', $loan->status)); ?></span></h1>
        <p class="text-muted mb-0"><?php echo $loan->institution_name; ?></p>
    </div>
    <form method="post" class="d-flex gap-2">
        <input type="hidden" name="do" value="update_status">
        <select name="status" class="form-select form-select-sm" style="width:auto">
            <?php foreach (['inquiry', 'requested', 'approved', 'agreed', 'in_transit_out', 'on_loan', 'in_transit_return', 'returned', 'cancelled', 'declined'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $loan->status === $s ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $s)); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
    </form>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Loan Details</h5></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th width="140">Purpose:</th><td><?php echo $loan->purpose ?: '-'; ?></td></tr>
                    <tr><th>Dates:</th><td><?php echo $loan->loan_start_date ? $loan->loan_start_date . ' to ' . $loan->loan_end_date : '-'; ?></td></tr>
                    <tr><th>Insurance Value:</th><td><?php echo $loan->insurance_value ? 'R ' . number_format($loan->insurance_value, 2) : '-'; ?></td></tr>
                    <tr><th>Provider:</th><td><?php echo $loan->insurance_provider ?: '-'; ?></td></tr>
                    <tr><th>Agreement:</th><td><?php echo $loan->agreement_signed ? '<span class="badge bg-success">Signed</span>' : '<span class="badge bg-warning">Pending</span>'; ?></td></tr>
                    <tr><th>Facility Report:</th><td><?php echo $loan->facility_report_received ? '<span class="badge bg-success">Received</span>' : '<a href="' . url_for(['module' => 'gallery', 'action' => 'facilityReport', 'loan_id' => $loan->id]) . '" class="btn btn-sm btn-outline-primary">Add Report</a>'; ?></td></tr>
                </table>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-building me-2"></i>Institution</h5></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th width="100">Name:</th><td><?php echo $loan->institution_name; ?></td></tr>
                    <tr><th>Address:</th><td><?php echo $loan->institution_address ?: '-'; ?></td></tr>
                    <tr><th>Contact:</th><td><?php echo $loan->contact_name ?: '-'; ?></td></tr>
                    <tr><th>Email:</th><td><?php echo $loan->contact_email ? '<a href="mailto:' . $loan->contact_email . '">' . $loan->contact_email . '</a>' : '-'; ?></td></tr>
                    <tr><th>Phone:</th><td><?php echo $loan->contact_phone ?: '-'; ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-cubes me-2"></i>Loan Objects (<?php echo count($loan->objects); ?>)</h5></div>
            <?php if (!empty($loan->objects)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Object</th><th>Insurance</th><th>Condition Out</th></tr></thead>
                        <tbody>
                            <?php foreach ($loan->objects as $o): ?>
                                <tr>
                                    <td><a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $o->slug]); ?>"><?php echo $o->object_title ?: 'Object'; ?></a></td>
                                    <td><?php echo $o->insurance_value ? 'R ' . number_format($o->insurance_value, 2) : '-'; ?></td>
                                    <td><?php echo $o->condition_out ?: '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card-body text-center text-muted">No objects added</div>
            <?php endif; ?>
        </div>
    </div>
</div>
