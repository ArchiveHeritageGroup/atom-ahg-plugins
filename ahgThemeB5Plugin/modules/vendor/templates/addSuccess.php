<?php echo get_partial('header', ['title' => 'Add Vendor']); ?>

<div class="container-fluid px-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'vendor', 'action' => 'index']); ?>">Vendor Management</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'vendor', 'action' => 'list']); ?>">Vendors</a></li>
            <li class="breadcrumb-item active">Add Vendor</li>
        </ol>
    </nav>

    <h1 class="h2 mb-4"><i class="fas fa-plus-circle me-2"></i>Add New Vendor</h1>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $field => $error): ?>
            <li><?php echo esc_entities($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo url_for(['module' => 'vendor', 'action' => 'add']); ?>">
        <div class="row">
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>Basic Information
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Vendor Name *</label>
                                <input type="text" name="name" class="form-control" value="<?php echo esc_entities($form['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Type</label>
                                <select name="vendor_type" class="form-select">
                                    <?php foreach ($vendorTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($form['vendor_type'] ?? 'company') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Registration Number</label>
                                <input type="text" name="registration_number" class="form-control" value="<?php echo esc_entities($form['registration_number'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">VAT Number</label>
                                <input type="text" name="vat_number" class="form-control" value="<?php echo esc_entities($form['vat_number'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-map-marker-alt me-2"></i>Address
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Street Address</label>
                            <textarea name="street_address" class="form-control" rows="2"><?php echo esc_entities($form['street_address'] ?? ''); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="<?php echo esc_entities($form['city'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Province</label>
                                <input type="text" name="province" class="form-control" value="<?php echo esc_entities($form['province'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" value="<?php echo esc_entities($form['postal_code'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="<?php echo esc_entities($form['country'] ?? 'South Africa'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-phone me-2"></i>Contact Details
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo esc_entities($form['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Alt. Phone</label>
                                <input type="text" name="phone_alt" class="form-control" value="<?php echo esc_entities($form['phone_alt'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fax</label>
                                <input type="text" name="fax" class="form-control" value="<?php echo esc_entities($form['fax'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo esc_entities($form['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control" value="<?php echo esc_entities($form['website'] ?? ''); ?>" placeholder="https://">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Banking -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-university me-2"></i>Banking Details
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" value="<?php echo esc_entities($form['bank_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Branch</label>
                                <input type="text" name="bank_branch" class="form-control" value="<?php echo esc_entities($form['bank_branch'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Account Number</label>
                                <input type="text" name="bank_account_number" class="form-control" value="<?php echo esc_entities($form['bank_account_number'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Branch Code</label>
                                <input type="text" name="bank_branch_code" class="form-control" value="<?php echo esc_entities($form['bank_branch_code'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Insurance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-shield-alt me-2"></i>Insurance Details
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input type="checkbox" name="has_insurance" value="1" class="form-check-input" id="hasInsurance" <?php echo ($form['has_insurance'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="hasInsurance">Vendor has insurance</label>
                        </div>
                        <div id="insuranceDetails">
                            <div class="mb-3">
                                <label class="form-label">Insurance Provider</label>
                                <input type="text" name="insurance_provider" class="form-control" value="<?php echo esc_entities($form['insurance_provider'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Policy Number</label>
                                <input type="text" name="insurance_policy_number" class="form-control" value="<?php echo esc_entities($form['insurance_policy_number'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="insurance_expiry_date" class="form-control" value="<?php echo esc_entities($form['insurance_expiry_date'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Coverage Amount (ZAR)</label>
                                <input type="number" name="insurance_coverage_amount" class="form-control" step="0.01" value="<?php echo esc_entities($form['insurance_coverage_amount'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Services -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-tools me-2"></i>Services Offered
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Select services this vendor provides:</p>
                        <?php foreach ($serviceTypes as $service): ?>
                        <div class="form-check mb-2">
                            <input type="checkbox" name="service_ids[]" value="<?php echo $service->id; ?>" class="form-check-input" id="service_<?php echo $service->id; ?>">
                            <label class="form-check-label" for="service_<?php echo $service->id; ?>"><?php echo esc_entities($service->name); ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-sticky-note me-2"></i>Notes
                    </div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="5" placeholder="Internal notes about this vendor..."><?php echo esc_entities($form['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Save Vendor
                </button>
                <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'list']); ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hasInsurance = document.getElementById('hasInsurance');
    const insuranceDetails = document.getElementById('insuranceDetails');
    
    function toggleInsurance() {
        insuranceDetails.style.display = hasInsurance.checked ? 'block' : 'none';
    }
    
    hasInsurance.addEventListener('change', toggleInsurance);
    toggleInsurance();
});
</script>
