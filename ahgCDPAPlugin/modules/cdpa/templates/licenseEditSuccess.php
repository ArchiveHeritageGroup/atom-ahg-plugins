<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'license']); ?>">License</a></li>
                    <li class="breadcrumb-item active"><?php echo $license ? 'Edit' : 'Register'; ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-id-card me-2"></i><?php echo $license ? 'Edit' : 'Register'; ?> POTRAZ License</h1>
        </div>
    </div>

    <form method="post" class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">License Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">License Number <span class="text-danger">*</span></label>
                            <input type="text" name="license_number" class="form-control"
                                   value="<?php echo htmlspecialchars($license->license_number ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tier <span class="text-danger">*</span></label>
                            <select name="tier" class="form-select" required>
                                <option value="">Select...</option>
                                <option value="tier1" <?php echo ($license->tier ?? '') === 'tier1' ? 'selected' : ''; ?>>Tier 1 - Small Scale</option>
                                <option value="tier2" <?php echo ($license->tier ?? '') === 'tier2' ? 'selected' : ''; ?>>Tier 2 - Medium Scale</option>
                                <option value="tier3" <?php echo ($license->tier ?? '') === 'tier3' ? 'selected' : ''; ?>>Tier 3 - Large Scale</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Organization Name <span class="text-danger">*</span></label>
                            <input type="text" name="organization_name" class="form-control"
                                   value="<?php echo htmlspecialchars($license->organization_name ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">POTRAZ Reference</label>
                            <input type="text" name="potraz_ref" class="form-control"
                                   value="<?php echo htmlspecialchars($license->potraz_ref ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data Subjects Count</label>
                            <input type="number" name="data_subjects_count" class="form-control"
                                   value="<?php echo $license->data_subjects_count ?? ''; ?>" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Dates</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Registration Date</label>
                            <input type="date" name="registration_date" class="form-control"
                                   value="<?php echo $license->registration_date ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Issue Date</label>
                            <input type="date" name="issue_date" class="form-control"
                                   value="<?php echo $license->issue_date ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" name="expiry_date" class="form-control"
                                   value="<?php echo $license->expiry_date ?? ''; ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Notes</h5></div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="4"><?php echo htmlspecialchars($license->notes ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Save License
                    </button>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'license']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
