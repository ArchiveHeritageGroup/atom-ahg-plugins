<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
<h1 class="multiline">
    <?php echo render_title($resource); ?>
    <span class="sub"><?php echo $isNew ? __('Add rights') : __('Edit rights'); ?></span>
</h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<form method="post" action="<?php echo $isNew 
    ? url_for(['module' => 'rights', 'action' => 'add', 'slug' => $resource->slug]) 
    : url_for(['module' => 'rights', 'action' => 'edit', 'slug' => $resource->slug, 'id' => $right['id']]); ?>" 
    id="rightsEditForm">
    <input type="hidden" name="_csrf_token" value="<?php echo $sf_user->getAttribute('_csrf_token'); ?>">

    <div class="accordion mb-3" id="rightsAccordion">
        
        <!-- Rights Basis Section -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#basisSection">
                    <?php echo __('Rights basis'); ?>
                </button>
            </h2>
            <div id="basisSection" class="accordion-collapse collapse show">
                <div class="accordion-body">
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Basis'); ?> <span class="text-danger">*</span></label>
                        <select name="basis" id="basis" class="form-select" required>
                            <option value=""><?php echo __('-- Select --'); ?></option>
                            <?php foreach ($formOptions['basis_options'] as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo ($right['basis'] ?? '') === $value ? 'selected' : ''; ?>>
                                    <?php echo __($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Rights Statement'); ?></label>
                        <select name="rights_statement_id" class="form-select">
                            <option value=""><?php echo __('-- Select --'); ?></option>
                            <?php foreach ($formOptions['rights_statements'] as $stmt): ?>
                                <option value="<?php echo $stmt['id']; ?>" <?php echo ($right['rights_statement_id'] ?? '') == $stmt['id'] ? 'selected' : ''; ?>>
                                    <?php echo esc_entities($stmt['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text"><?php echo __('Standardized statement from rightsstatements.org'); ?></div>
                    </div>

                    <!-- Copyright Fields -->
                    <div id="copyrightFields" class="basis-fields" style="display: none;">
                        <hr><h6 class="text-muted mb-3"><?php echo __('Copyright Information'); ?></h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo __('Copyright status'); ?></label>
                                <select name="copyright_status" class="form-select">
                                    <option value=""><?php echo __('-- Select --'); ?></option>
                                    <?php foreach ($formOptions['copyright_status_options'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo ($right['copyright_status'] ?? '') === $v ? 'selected' : ''; ?>><?php echo __($l); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo __('Status determination date'); ?></label>
                                <input type="date" name="copyright_status_date" class="form-control" value="<?php echo $right['copyright_status_date'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo __('Jurisdiction'); ?></label>
                                <input type="text" name="copyright_jurisdiction" class="form-control" maxlength="2" placeholder="ZA" value="<?php echo esc_entities($right['copyright_jurisdiction'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo __('Expiry date'); ?></label>
                                <input type="date" name="copyright_expiry_date" class="form-control" value="<?php echo $right['copyright_expiry_date'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Copyright holder'); ?></label>
                            <input type="text" name="copyright_holder" class="form-control" value="<?php echo esc_entities($right['copyright_holder'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Copyright note'); ?></label>
                            <textarea name="copyright_note" class="form-control" rows="2"><?php echo esc_entities($right['copyright_note'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- License Fields -->
                    <div id="licenseFields" class="basis-fields" style="display: none;">
                        <hr><h6 class="text-muted mb-3"><?php echo __('License Information'); ?></h6>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('License type'); ?></label>
                            <select name="license_type" id="license_type" class="form-select">
                                <option value=""><?php echo __('-- Select --'); ?></option>
                                <option value="cc" <?php echo ($right['license_type'] ?? '') === 'cc' ? 'selected' : ''; ?>><?php echo __('Creative Commons'); ?></option>
                                <option value="open" <?php echo ($right['license_type'] ?? '') === 'open' ? 'selected' : ''; ?>><?php echo __('Other Open License'); ?></option>
                                <option value="proprietary" <?php echo ($right['license_type'] ?? '') === 'proprietary' ? 'selected' : ''; ?>><?php echo __('Proprietary'); ?></option>
                                <option value="custom" <?php echo ($right['license_type'] ?? '') === 'custom' ? 'selected' : ''; ?>><?php echo __('Custom'); ?></option>
                            </select>
                        </div>
                        <div id="ccLicenseField" class="mb-3" style="display: none;">
                            <label class="form-label"><?php echo __('Creative Commons License'); ?></label>
                            <select name="cc_license_id" class="form-select">
                                <option value=""><?php echo __('-- Select --'); ?></option>
                                <?php foreach ($formOptions['cc_licenses'] as $license): ?>
                                    <option value="<?php echo $license['id']; ?>" <?php echo ($right['cc_license_id'] ?? '') == $license['id'] ? 'selected' : ''; ?>>
                                        <?php echo esc_entities($license['code'] . ' - ' . $license['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('License identifier'); ?></label>
                            <input type="text" name="license_identifier" class="form-control" value="<?php echo esc_entities($right['license_identifier'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('License URL'); ?></label>
                            <input type="url" name="license_url" class="form-control" value="<?php echo esc_entities($right['license_url'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('License terms'); ?></label>
                            <textarea name="license_terms" class="form-control" rows="3"><?php echo esc_entities($right['license_terms'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Statute Fields -->
                    <div id="statuteFields" class="basis-fields" style="display: none;">
                        <hr><h6 class="text-muted mb-3"><?php echo __('Statute Information'); ?></h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo __('Jurisdiction'); ?></label>
                                <input type="text" name="statute_jurisdiction" class="form-control" maxlength="2" placeholder="ZA" value="<?php echo esc_entities($right['statute_jurisdiction'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo __('Determination date'); ?></label>
                                <input type="date" name="statute_determination_date" class="form-control" value="<?php echo $right['statute_determination_date'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Statute citation'); ?></label>
                            <input type="text" name="statute_citation" class="form-control" value="<?php echo esc_entities($right['statute_citation'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Statute note'); ?></label>
                            <textarea name="statute_note" class="form-control" rows="2"><?php echo esc_entities($right['statute_note'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Donor Fields -->
                    <div id="donorFields" class="basis-fields" style="display: none;">
                        <hr><h6 class="text-muted mb-3"><?php echo __('Donor Information'); ?></h6>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Donor name'); ?></label>
                            <input type="text" name="donor_name" class="form-control" value="<?php echo esc_entities($right['donor_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Policy Fields -->
                    <div id="policyFields" class="basis-fields" style="display: none;">
                        <hr><h6 class="text-muted mb-3"><?php echo __('Policy Information'); ?></h6>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Policy identifier'); ?></label>
                            <input type="text" name="policy_identifier" class="form-control" value="<?php echo esc_entities($right['policy_identifier'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Common Date Fields -->
                    <hr>
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label"><?php echo __('Start date'); ?></label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $right['start_date'] ?? ''; ?>">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label"><?php echo __('End date'); ?></label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $right['end_date'] ?? ''; ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="end_date_open" id="end_date_open" value="1" <?php echo ($right['end_date_open'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="end_date_open"><?php echo __('Open'); ?></label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Rights holder'); ?></label>
                        <input type="text" name="rights_holder_name" class="form-control" value="<?php echo esc_entities($right['rights_holder_name'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Rights note'); ?></label>
                        <textarea name="rights_note" class="form-control" rows="3"><?php echo esc_entities($right['rights_note'] ?? ''); ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- Granted Rights (Acts) Section -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#actsSection">
                    <?php echo __('Act / Granted rights'); ?>
                </button>
            </h2>
            <div id="actsSection" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <p class="text-muted small"><?php echo __('Define what actions are allowed or restricted.'); ?></p>
                    
                    <div id="grantedRightsContainer">
                        <?php 
                        $grantedRights = $right['granted_rights'] ?? [['act' => '', 'restriction' => 'allow']];
                        foreach ($grantedRights as $i => $g): 
                        ?>
                        <div class="granted-right-row row mb-2">
                            <div class="col-md-4">
                                <select name="acts[]" class="form-select form-select-sm">
                                    <option value=""><?php echo __('-- Select act --'); ?></option>
                                    <?php foreach ($formOptions['act_options'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo ($g['act'] ?? '') === $v ? 'selected' : ''; ?>><?php echo __($l); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="restrictions[]" class="form-select form-select-sm">
                                    <?php foreach ($formOptions['restriction_options'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo ($g['restriction'] ?? 'allow') === $v ? 'selected' : ''; ?>><?php echo __($l); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="restriction_reasons[]" class="form-control form-control-sm" placeholder="<?php echo __('Reason (optional)'); ?>" value="<?php echo esc_entities($g['restriction_reason'] ?? ''); ?>">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-grant" title="<?php echo __('Remove'); ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addGrantedRight">
                        <i class="fas fa-plus me-1"></i><?php echo __('Add act'); ?>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Form Actions -->
    <div class="actions section">
        <ul class="list-inline mb-0">
            <li class="list-inline-item">
                <a href="<?php echo url_for([$resource, 'module' => 'informationobject']); ?>" class="btn btn-secondary">
                    <?php echo __('Cancel'); ?>
                </a>
            </li>
            <li class="list-inline-item">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i><?php echo __('Save'); ?>
                </button>
            </li>
        </ul>
    </div>

</form>

<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.addEventListener('DOMContentLoaded', function() {
    var basisSelect = document.getElementById('basis');
    var licenseTypeSelect = document.getElementById('license_type');
    
    function showBasisFields() {
        document.querySelectorAll('.basis-fields').forEach(function(el) {
            el.style.display = 'none';
        });
        
        var basis = basisSelect.value;
        if (basis === 'copyright') {
            document.getElementById('copyrightFields').style.display = 'block';
        } else if (basis === 'license') {
            document.getElementById('licenseFields').style.display = 'block';
        } else if (basis === 'statute') {
            document.getElementById('statuteFields').style.display = 'block';
        } else if (basis === 'donor') {
            document.getElementById('donorFields').style.display = 'block';
        } else if (basis === 'policy') {
            document.getElementById('policyFields').style.display = 'block';
        }
    }
    
    function showCcLicense() {
        var ccField = document.getElementById('ccLicenseField');
        ccField.style.display = licenseTypeSelect.value === 'cc' ? 'block' : 'none';
    }
    
    basisSelect.addEventListener('change', showBasisFields);
    if (licenseTypeSelect) {
        licenseTypeSelect.addEventListener('change', showCcLicense);
    }
    
    // Initialize
    showBasisFields();
    if (licenseTypeSelect) showCcLicense();
    
    // Add granted right row
    document.getElementById('addGrantedRight').addEventListener('click', function() {
        var container = document.getElementById('grantedRightsContainer');
        var template = container.querySelector('.granted-right-row').cloneNode(true);
        template.querySelectorAll('input, select').forEach(function(el) {
            el.value = '';
        });
        container.appendChild(template);
    });
    
    // Remove granted right row
    document.getElementById('grantedRightsContainer').addEventListener('click', function(e) {
        if (e.target.closest('.remove-grant')) {
            var rows = document.querySelectorAll('.granted-right-row');
            if (rows.length > 1) {
                e.target.closest('.granted-right-row').remove();
            }
        }
    });
});
</script>

<?php end_slot(); ?>
