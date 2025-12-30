<?php use_helper('Text'); ?>
<h1 class="h3 mb-4"><?php echo __('Record of Processing Activities (ROPA)'); ?></h1>
<div class="mb-3">
    <a href="/admin/privacy" class="btn btn-secondary"><?php echo __('Back'); ?></a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRopaModal">
        <i class="fas fa-plus me-1"></i><?php echo __('Add Processing Activity'); ?>
    </button>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($activities)): ?>
            <table class="table table-striped">
                <thead><tr><th>Name</th><th>Purpose</th><th>Lawful Basis</th><th>DPIA</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($activities as $a): ?>
                    <tr>
                        <td><strong><?php echo esc_entities($a->name ?? ''); ?></strong></td>
                        <td><?php echo esc_entities(substr($a->purpose ?? '', 0, 50)); ?></td>
                        <td><?php echo esc_entities($a->lawful_basis ?? ''); ?></td>
                        <td>
                            <?php if ($a->dpia_required ?? false): ?>
                                <span class="badge bg-<?php echo ($a->dpia_completed ?? false) ? 'success' : 'warning'; ?>">
                                    <?php echo ($a->dpia_completed ?? false) ? 'Complete' : 'Required'; ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-success"><?php echo ucfirst($a->status ?? 'active'); ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editRopa(<?php echo $a->id; ?>)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteRopa(<?php echo $a->id; ?>)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted text-center py-4"><?php echo __('No processing activities recorded. Click "Add Processing Activity" to create one.'); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Add ROPA Modal -->
<div class="modal fade" id="addRopaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="/admin/privacy/ropa">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clipboard-list me-2"></i><?php echo __('Add Processing Activity'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Activity Name'); ?> *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Lawful Basis (POPIA S11)'); ?></label>
                            <select name="lawful_basis" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="consent">Consent (S11(1)(a))</option>
                                <option value="contract">Contract (S11(1)(b))</option>
                                <option value="legal_obligation">Legal Obligation (S11(1)(c))</option>
                                <option value="legitimate_interest">Legitimate Interest (S11(1)(d))</option>
                                <option value="vital_interest">Vital Interest (S11(1)(d))</option>
                                <option value="public_interest">Public Interest (S11(1)(e))</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Purpose of Processing'); ?> *</label>
                        <textarea name="purpose" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Data Categories'); ?></label>
                            <textarea name="data_categories" class="form-control" rows="2" placeholder="e.g., Names, ID numbers, Contact details"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Data Subjects'); ?></label>
                            <textarea name="data_subjects" class="form-control" rows="2" placeholder="e.g., Researchers, Donors, Staff"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Recipients'); ?></label>
                            <input type="text" name="recipients" class="form-control" placeholder="Who receives this data?">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Retention Period'); ?></label>
                            <input type="text" name="retention_period" class="form-control" placeholder="e.g., 7 years">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="dpia_required" value="1" class="form-check-input" id="dpiaRequired">
                                <label class="form-check-label" for="dpiaRequired"><?php echo __('DPIA Required'); ?></label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Status'); ?></label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="under_review">Under Review</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Security Measures'); ?></label>
                        <textarea name="security_measures" class="form-control" rows="2" placeholder="Describe technical and organizational security measures"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('Save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
