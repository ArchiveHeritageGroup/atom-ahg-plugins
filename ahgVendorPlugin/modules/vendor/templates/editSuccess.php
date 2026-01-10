<?php 
$vendorRaw = isset($sf_data) ? $sf_data->getRaw('vendor') : $vendor;
$serviceTypesRaw = isset($sf_data) ? $sf_data->getRaw('serviceTypes') : $serviceTypes;
$vendorTypesRaw = isset($sf_data) ? $sf_data->getRaw('vendorTypes') : $vendorTypes;
$vendorServicesRaw = isset($sf_data) ? $sf_data->getRaw('vendorServices') : (isset($vendorServices) ? $vendorServices : []);

$isNew = empty($vendorRaw->id);
$pageTitle = $isNew ? 'Add New Vendor' : 'Edit Vendor: ' . $vendorRaw->name;
?>

<div class="container-fluid px-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('ahg_vend_index'); ?>">Vendor Management</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('ahg_vend_list'); ?>">Vendors</a></li>
            <?php if (!$isNew): ?>
            <li class="breadcrumb-item"><a href="<?php echo url_for('ahg_vend_view', ['slug' => $vendorRaw->slug]); ?>"><?php echo esc_entities($vendorRaw->name); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?php echo $isNew ? 'Add New' : 'Edit'; ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <i class="fas fa-<?php echo $isNew ? 'plus' : 'edit'; ?> me-2"></i><?php echo $pageTitle; ?>
        </h1>
    </div>

    <form method="post" action="<?php echo url_for($isNew ? 'ahg_vend_add' : 'ahg_vend_edit', $isNew ? [] : ['slug' => $vendorRaw->slug ?? '']); ?>" class="needs-validation" novalidate>
        <?php if (!$isNew): ?>
        <input type="hidden" name="id" value="<?php echo $vendorRaw->id; ?>">
        <?php endif; ?>

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
                                <input type="text" name="name" class="form-control" value="<?php echo esc_entities($vendorRaw->name ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Vendor Code</label>
                                <input type="text" name="vendor_code" class="form-control" value="<?php echo esc_entities($vendorRaw->vendor_code ?? ''); ?>" placeholder="Auto-generated if empty">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vendor Type *</label>
                                <select name="vendor_type" class="form-select" required>
                                    <option value="">Select Type...</option>
                                    <?php foreach ($vendorTypesRaw as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($vendorRaw->vendor_type ?? '') === $key ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php echo ($vendorRaw->status ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($vendorRaw->status ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="pending_approval" <?php echo ($vendorRaw->status ?? '') === 'pending_approval' ? 'selected' : ''; ?>>Pending Approval</option>
                                    <option value="suspended" <?php echo ($vendorRaw->status ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Registration Number</label>
                                <input type="text" name="registration_number" class="form-control" value="<?php echo esc_entities($vendorRaw->registration_number ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">VAT Number</label>
                                <input type="text" name="vat_number" class="form-control" value="<?php echo esc_entities($vendorRaw->vat_number ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-phone me-2"></i>Contact Information
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo esc_entities($vendorRaw->phone ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alternative Phone</label>
                                <input type="text" name="phone_alt" class="form-control" value="<?php echo esc_entities($vendorRaw->phone_alt ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo esc_entities($vendorRaw->email ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control" value="<?php echo esc_entities($vendorRaw->website ?? ''); ?>" placeholder="https://">
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
                            <textarea name="street_address" class="form-control" rows="2"><?php echo esc_entities($vendorRaw->street_address ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="<?php echo esc_entities($vendorRaw->city ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Province/State</label>
                                <input type="text" name="province" class="form-control" value="<?php echo esc_entities($vendorRaw->province ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" value="<?php echo esc_entities($vendorRaw->postal_code ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control" value="<?php echo esc_entities($vendorRaw->country ?? 'South Africa'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Banking Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-university me-2"></i>Banking Details
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" value="<?php echo esc_entities($vendorRaw->bank_name ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Branch Name</label>
                                <input type="text" name="bank_branch" class="form-control" value="<?php echo esc_entities($vendorRaw->bank_branch ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Account Number</label>
                                <input type="text" name="bank_account_number" class="form-control" value="<?php echo esc_entities($vendorRaw->bank_account_number ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Branch Code</label>
                                <input type="text" name="bank_branch_code" class="form-control" value="<?php echo esc_entities($vendorRaw->bank_branch_code ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Account Type</label>
                            <select name="bank_account_type" class="form-select">
                                <option value="">Select...</option>
                                <option value="cheque" <?php echo ($vendorRaw->bank_account_type ?? '') === 'cheque' ? 'selected' : ''; ?>>Cheque Account</option>
                                <option value="savings" <?php echo ($vendorRaw->bank_account_type ?? '') === 'savings' ? 'selected' : ''; ?>>Savings Account</option>
                                <option value="transmission" <?php echo ($vendorRaw->bank_account_type ?? '') === 'transmission' ? 'selected' : ''; ?>>Transmission Account</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-sticky-note me-2"></i>Notes
                    </div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="4"><?php echo esc_entities($vendorRaw->notes ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Services -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-tools me-2"></i>Services Provided
                    </div>
                    <div class="card-body">
                        <div class="services-list" style="max-height: 300px; overflow-y: auto;">
                            <?php 
                            $vendorServiceIds = [];
                            if ($vendorServicesRaw) {
                                foreach ($vendorServicesRaw as $vs) {
                                    $vendorServiceIds[] = is_object($vs) ? $vs->id : $vs['id'];
                                }
                            }
                            ?>
                            <?php foreach ($serviceTypesRaw as $service): ?>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="service_ids[]" value="<?php echo $service->id; ?>" 
                                       class="form-check-input" id="service_<?php echo $service->id; ?>"
                                       <?php echo in_array($service->id, $vendorServiceIds) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="service_<?php echo $service->id; ?>">
                                    <?php echo esc_entities($service->name); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Insurance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-shield-alt me-2"></i>Insurance Details
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input type="checkbox" name="has_insurance" value="1" class="form-check-input" id="hasInsurance"
                                   <?php echo !empty($vendorRaw->has_insurance) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="hasInsurance">Has Insurance</label>
                        </div>

                        <div id="insuranceFields">
                            <div class="mb-3">
                                <label class="form-label">Insurance Provider</label>
                                <input type="text" name="insurance_provider" class="form-control" value="<?php echo esc_entities($vendorRaw->insurance_provider ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Policy Number</label>
                                <input type="text" name="insurance_policy_number" class="form-control" value="<?php echo esc_entities($vendorRaw->insurance_policy_number ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="insurance_expiry_date" class="form-control" value="<?php echo $vendorRaw->insurance_expiry_date ?? ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Coverage Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">R</span>
                                    <input type="number" name="insurance_coverage_amount" class="form-control" step="0.01" value="<?php echo $vendorRaw->insurance_coverage_amount ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Options -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-cog me-2"></i>Options
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input type="checkbox" name="is_preferred" value="1" class="form-check-input" id="isPreferred"
                                   <?php echo !empty($vendorRaw->is_preferred) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="isPreferred">Preferred Vendor</label>
                        </div>
                        <div class="form-check mb-2">
                            <input type="checkbox" name="is_bbbee_compliant" value="1" class="form-check-input" id="isBBBEE"
                                   <?php echo !empty($vendorRaw->is_bbbee_compliant) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="isBBBEE">B-BBEE Compliant</label>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i><?php echo $isNew ? 'Create Vendor' : 'Save Changes'; ?>
                            </button>
                            <?php if (!$isNew): ?>
                            <a href="<?php echo url_for('ahg_vend_view', ['slug' => $vendorRaw->slug]); ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <?php else: ?>
                            <a href="<?php echo url_for('ahg_vend_list'); ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle insurance fields visibility
    const hasInsurance = document.getElementById('hasInsurance');
    const insuranceFields = document.getElementById('insuranceFields');
    
    function toggleInsurance() {
        insuranceFields.style.display = hasInsurance.checked ? 'block' : 'none';
    }
    
    hasInsurance.addEventListener('change', toggleInsurance);
    toggleInsurance();

    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>
