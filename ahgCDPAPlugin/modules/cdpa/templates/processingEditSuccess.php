<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'processing']); ?>">Processing</a></li>
                    <li class="breadcrumb-item active">Edit Activity</li>
                </ol>
            </nav>
            <h1><i class="fas fa-cogs me-2"></i>Edit Processing Activity</h1>
        </div>
    </div>

    <form method="post" class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Activity Details</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Activity Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($activity->name); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">Select...</option>
                                <option value="hr" <?php echo ($activity->category ?? '') === 'hr' ? 'selected' : ''; ?>>HR/Employment</option>
                                <option value="customer" <?php echo ($activity->category ?? '') === 'customer' ? 'selected' : ''; ?>>Customer Data</option>
                                <option value="marketing" <?php echo ($activity->category ?? '') === 'marketing' ? 'selected' : ''; ?>>Marketing</option>
                                <option value="research" <?php echo ($activity->category ?? '') === 'research' ? 'selected' : ''; ?>>Research</option>
                                <option value="operations" <?php echo ($activity->category ?? '') === 'operations' ? 'selected' : ''; ?>>Operations</option>
                                <option value="archive" <?php echo ($activity->category ?? '') === 'archive' ? 'selected' : ''; ?>>Archives</option>
                                <option value="other" <?php echo ($activity->category ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Data Types Processed</label>
                            <input type="text" name="data_types" class="form-control" value="<?php echo htmlspecialchars($activity->data_types ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Purpose of Processing <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="3" required><?php echo htmlspecialchars($activity->purpose ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Legal Basis <span class="text-danger">*</span></label>
                            <select name="legal_basis" class="form-select" required>
                                <option value="">Select...</option>
                                <option value="consent" <?php echo ($activity->legal_basis ?? '') === 'consent' ? 'selected' : ''; ?>>Consent</option>
                                <option value="contract" <?php echo ($activity->legal_basis ?? '') === 'contract' ? 'selected' : ''; ?>>Contractual Necessity</option>
                                <option value="legal_obligation" <?php echo ($activity->legal_basis ?? '') === 'legal_obligation' ? 'selected' : ''; ?>>Legal Obligation</option>
                                <option value="vital_interest" <?php echo ($activity->legal_basis ?? '') === 'vital_interest' ? 'selected' : ''; ?>>Vital Interest</option>
                                <option value="public_interest" <?php echo ($activity->legal_basis ?? '') === 'public_interest' ? 'selected' : ''; ?>>Public Interest</option>
                                <option value="legitimate_interest" <?php echo ($activity->legal_basis ?? '') === 'legitimate_interest' ? 'selected' : ''; ?>>Legitimate Interest</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Retention Period</label>
                            <input type="text" name="retention_period" class="form-control" value="<?php echo htmlspecialchars($activity->retention_period ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Storage & Security</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Storage Location</label>
                            <input type="text" name="storage_location" class="form-control" value="<?php echo htmlspecialchars($activity->storage_location ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Security Safeguards</label>
                            <textarea name="safeguards" class="form-control" rows="2"><?php echo htmlspecialchars($activity->safeguards ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Special Categories</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="cross_border" id="cross_border" <?php echo ($activity->cross_border ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cross_border">Cross-border transfer</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="children_data" id="children_data" <?php echo ($activity->children_data ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="children_data">Children's data</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="biometric_data" id="biometric_data" <?php echo ($activity->biometric_data ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="biometric_data">Biometric data</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'processing']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
