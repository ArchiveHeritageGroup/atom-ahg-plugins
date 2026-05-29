<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-map-marker-alt me-2"></i><?php echo __('Embedded-metadata PII'); ?></h1>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
        </a>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
    <?php endif; ?>
    <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
    <?php endif; ?>

    <p class="text-muted">
        <?php echo __('GPS coordinates, people and contact details found in embedded EXIF/IPTC metadata. GPS findings left pending are withheld from public surfaces (IIIF, exports, AI, OCFL) by the redaction gate.'); ?>
    </p>

    <form method="get" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'embeddedPii']); ?>" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="status" class="form-select">
                <?php foreach (['pending' => 'Pending review', 'escalated' => 'Escalated', 'redacted' => 'Redacted', 'cleared' => 'Cleared', 'all' => 'All'] as $k => $v): ?>
                    <option value="<?php echo $k; ?>" <?php echo $status === $k ? 'selected' : ''; ?>><?php echo __($v); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <select name="pii_type" class="form-select">
                <option value=""><?php echo __('All types'); ?></option>
                <?php foreach (['gps_coordinate' => 'GPS coordinate', 'person_name' => 'Person name', 'person_contact' => 'Person contact', 'sensitive_date' => 'Sensitive date'] as $k => $v): ?>
                    <option value="<?php echo $k; ?>" <?php echo $piiType === $k ? 'selected' : ''; ?>><?php echo __($v); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto"><button type="submit" class="btn btn-primary"><?php echo __('Filter'); ?></button></div>
    </form>

    <?php if (empty($sf_data->getRaw('findings'))): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><?php echo __('No findings for this filter.'); ?></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('Digital object'); ?></th>
                        <th><?php echo __('Type'); ?></th>
                        <th><?php echo __('Source'); ?></th>
                        <th><?php echo __('Value'); ?></th>
                        <th><?php echo __('Status'); ?></th>
                        <th class="text-end"><?php echo __('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sf_data->getRaw('findings') as $f): ?>
                        <tr>
                            <td><code><?php echo (int) $f->digital_object_id; ?></code></td>
                            <td><span class="badge bg-secondary"><?php echo esc_entities($f->pii_type); ?></span></td>
                            <td><small><?php echo esc_entities($f->source_table); ?><br><?php echo esc_entities($f->source_field); ?></small></td>
                            <td><small><?php echo esc_entities((string) $f->source_value); ?></small></td>
                            <td>
                                <?php
                                  $map = ['pending' => 'warning text-dark', 'escalated' => 'danger', 'redacted' => 'dark', 'cleared' => 'success'];
                                  $cls = $map[$f->resolution_status] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $cls; ?>"><?php echo esc_entities($f->resolution_status); ?></span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php foreach (['cleared' => 'Clear', 'redacted' => 'Redact', 'escalated' => 'Escalate'] as $st => $lbl): ?>
                                        <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'embeddedPiiResolve']); ?>" class="d-inline">
                                            <input type="hidden" name="id" value="<?php echo (int) $f->id; ?>">
                                            <input type="hidden" name="status" value="<?php echo $st; ?>">
                                            <button type="submit" class="btn btn-outline-secondary"><?php echo __($lbl); ?></button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
