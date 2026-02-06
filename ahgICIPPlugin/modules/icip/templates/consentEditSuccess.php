<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_dashboard') ?>">ICIP</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_consent_list') ?>">Consent Records</a></li>
            <li class="breadcrumb-item active"><?php echo $id ? 'Edit' : 'Add' ?> Consent</li>
        </ol>
    </nav>

    <h1 class="mb-4">
        <i class="bi bi-<?php echo $id ? 'pencil' : 'plus-circle' ?> me-2"></i>
        <?php echo $id ? 'Edit Consent Record' : 'Add Consent Record' ?>
    </h1>

    <?php if ($object): ?>
        <div class="alert alert-info">
            <i class="bi bi-archive me-2"></i>
            <strong>Record:</strong>
            <a href="<?php echo url_for([$object, 'module' => 'informationobject']) ?>"><?php echo htmlspecialchars($object->title ?? $object->identifier ?? 'Untitled') ?></a>
        </div>
    <?php endif ?>

    <form method="post" class="needs-validation" novalidate>
        <div class="row">
            <div class="col-lg-8">
                <!-- Record Selection -->
                <?php if (!$object): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Information Object</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Object ID <span class="text-danger">*</span></label>
                                <input type="number" name="information_object_id" class="form-control" required value="<?php echo htmlspecialchars($consent->information_object_id ?? $objectId ?? '') ?>">
                                <div class="form-text">Enter the information object ID this consent applies to</div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="information_object_id" value="<?php echo $object->id ?>">
                <?php endif ?>

                <!-- Consent Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Consent Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Community</label>
                                <select name="community_id" class="form-select">
                                    <option value="">Not specified / Multiple</option>
                                    <?php foreach ($communities as $community): ?>
                                        <option value="<?php echo $community->id ?>" <?php echo ($consent->community_id ?? '') == $community->id ? 'selected' : '' ?>>
                                            <?php echo htmlspecialchars($community->name) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Consent Status <span class="text-danger">*</span></label>
                                <select name="consent_status" class="form-select" required>
                                    <?php foreach ($statusOptions as $value => $label): ?>
                                        <option value="<?php echo $value ?>" <?php echo ($consent->consent_status ?? 'unknown') === $value ? 'selected' : '' ?>>
                                            <?php echo $label ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Consent Scope</label>
                            <div class="row">
                                <?php
                                $currentScope = [];
                                if (!empty($consent->consent_scope)) {
                                    $currentScope = json_decode($consent->consent_scope, true) ?? [];
                                }
                                ?>
                                <?php foreach ($scopeOptions as $value => $label): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="form-check">
                                            <input type="checkbox" name="consent_scope[]" value="<?php echo $value ?>" class="form-check-input" id="scope_<?php echo $value ?>" <?php echo in_array($value, $currentScope) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="scope_<?php echo $value ?>"><?php echo $label ?></label>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                            <div class="form-text">Select all applicable consent scopes</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Consent Date</label>
                                <input type="date" name="consent_date" class="form-control" value="<?php echo $consent->consent_date ?? '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="consent_expiry_date" class="form-control" value="<?php echo $consent->consent_expiry_date ?? '' ?>">
                                <div class="form-text">Leave blank for indefinite consent</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Consent Granted By</label>
                            <input type="text" name="consent_granted_by" class="form-control" value="<?php echo htmlspecialchars($consent->consent_granted_by ?? '') ?>">
                            <div class="form-text">Person or authority who granted consent</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Consent Document Path</label>
                            <input type="text" name="consent_document_path" class="form-control" value="<?php echo htmlspecialchars($consent->consent_document_path ?? '') ?>">
                            <div class="form-text">Path to uploaded consent document (if applicable)</div>
                        </div>
                    </div>
                </div>

                <!-- Conditions & Restrictions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Conditions & Restrictions</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Conditions</label>
                            <textarea name="conditions" class="form-control" rows="4"><?php echo htmlspecialchars($consent->conditions ?? '') ?></textarea>
                            <div class="form-text">Any conditions attached to this consent</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Restrictions</label>
                            <textarea name="restrictions" class="form-control" rows="4"><?php echo htmlspecialchars($consent->restrictions ?? '') ?></textarea>
                            <div class="form-text">Specific usage restrictions that apply</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($consent->notes ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Actions -->
                <div class="card mb-4">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-check-circle me-1"></i>
                            <?php echo $id ? 'Save Changes' : 'Create Consent Record' ?>
                        </button>
                        <a href="<?php echo url_for('@icip_consent_list') ?>" class="btn btn-outline-secondary w-100">
                            Cancel
                        </a>
                    </div>
                </div>

                <!-- Status Guide -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Status Guide</h6>
                    </div>
                    <div class="card-body small">
                        <dl class="mb-0">
                            <dt class="text-muted">Not Required</dt>
                            <dd>No consent needed for this material</dd>

                            <dt class="text-warning">Pending Consultation</dt>
                            <dd>Awaiting initial community contact</dd>

                            <dt class="text-info">In Progress</dt>
                            <dd>Consultation underway</dd>

                            <dt class="text-success">Full Consent</dt>
                            <dd>Unrestricted consent granted</dd>

                            <dt class="text-primary">Conditional/Restricted</dt>
                            <dd>Consent with specific limitations</dd>

                            <dt class="text-danger">Denied</dt>
                            <dd>Consent refused by community</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
