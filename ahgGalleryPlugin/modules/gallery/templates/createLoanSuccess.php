<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'loans']); ?>">Loans</a></li>
        <li class="breadcrumb-item active">New Loan</li>
    </ol>
</nav>
<h1 class="h2 mb-4"><i class="fas fa-plus text-primary me-2"></i>New Loan</h1>
<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-exchange-alt me-2"></i>Loan Details</h5>
                    <div class="mb-3"><label class="form-label">Loan Type *</label>
                        <select name="loan_type" class="form-select" required>
                            <option value="incoming">Incoming (borrowing from another institution)</option>
                            <option value="outgoing">Outgoing (lending to another institution)</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Purpose</label><input type="text" name="purpose" class="form-control" placeholder="e.g., Exhibition, Research, Conservation"></div>
                    <div class="mb-3"><label class="form-label">Related Exhibition</label>
                        <select name="exhibition_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($exhibitions as $e): ?><option value="<?php echo $e->id; ?>"><?php echo $e->title; ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label">Start Date</label><input type="date" name="loan_start_date" class="form-control"></div>
                        <div class="col-6"><label class="form-label">End Date</label><input type="date" name="loan_end_date" class="form-control"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-building me-2"></i>Institution</h5>
                    <div class="mb-3"><label class="form-label">Institution Name *</label><input type="text" name="institution_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea name="institution_address" class="form-control" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Contact Name</label><input type="text" name="contact_name" class="form-control"></div>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label">Email</label><input type="email" name="contact_email" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Phone</label><input type="tel" name="contact_phone" class="form-control"></div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'loans']); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Loan</button>
            </div>
        </form>
    </div>
</div>
