<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits']); ?>">Permits</a></li>
                    <li class="breadcrumb-item active">New Permit</li>
                </ol>
            </nav>
            <h1><i class="fas fa-id-card me-2"></i>Issue Research Permit</h1>
        </div>
    </div>

    <form method="post" class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Researcher</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Select Researcher <span class="text-danger">*</span></label>
                            <select name="researcher_id" class="form-select" required>
                                <option value="">Select...</option>
                                <?php foreach ($researchers as $r): ?>
                                    <option value="<?php echo $r->id; ?>"><?php echo htmlspecialchars($r->first_name . ' ' . $r->last_name . ' (' . ucfirst($r->researcher_type) . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Research Details</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Research Topic <span class="text-danger">*</span></label>
                            <input type="text" name="research_topic" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Research Purpose</label>
                            <textarea name="research_purpose" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Permit Type</label>
                            <select name="permit_type" class="form-select">
                                <option value="general">General</option>
                                <option value="restricted">Restricted Access</option>
                                <option value="special">Special Permission</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Validity Period</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+12 months')); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Fees</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Fee Amount</label>
                            <input type="number" name="fee_amount" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Currency</label>
                            <select name="fee_currency" class="form-select">
                                <option value="USD">USD</option>
                                <option value="ZWL">ZWL</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="fee_paid" id="fee_paid">
                                <label class="form-check-label" for="fee_paid">Fee Paid</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Permit Fees</h6>
                <ul class="small mb-0">
                    <li>Foreign researchers: US$200</li>
                    <li>Local researchers: Free</li>
                    <li>Validity: 12 months</li>
                </ul>
            </div>
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Issue Permit</button>
                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits']); ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
