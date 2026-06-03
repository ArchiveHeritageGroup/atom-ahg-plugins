<?php
$profiled = $sf_data->getRaw('profiled');
$reasons = $sf_data->getRaw('reasons');
$fieldNames = $sf_data->getRaw('fieldNames');
$fields = $sf_data->getRaw('fields');
$ioId = $sf_data->getRaw('ioId');
$ioTitle = $sf_data->getRaw('ioTitle');
$visualRegions = $sf_data->getRaw('visualRegions');
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-2"><i class="fas fa-arrow-left"></i></a>
            <span class="h2"><i class="fas fa-eye-slash me-2"></i><?php echo __('Field-level redaction'); ?></span>
            <div class="text-muted small mt-1"><?php echo __('Redact structured metadata fields for public viewers; staff see the full record.'); ?></div>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
    <?php if ($sf_user->hasFlash('error')): ?><div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div><?php endif; ?>

    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-4"><div class="card-header fw-bold"><?php echo $ioId ? __('Redacted fields for IO #').$ioId : __('Add a field redaction'); ?></div>
            <div class="card-body">
                <?php if ($ioId && $ioTitle !== null): ?><p class="text-muted"><?php echo htmlspecialchars((string) $ioTitle); ?></p><?php endif; ?>

                <?php if ($ioId && !empty($visualRegions)): ?>
                <div class="alert alert-info py-2 small">
                    <i class="fas fa-image me-1"></i>
                    <?php echo __('This description also has %count% visual redaction region(s) on its digital object.', ['%count%' => (int) $visualRegions]); ?>
                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'visualRedactionEditor', 'id' => $ioId]); ?>" class="alert-link"><?php echo __('Open visual redaction editor'); ?></a>
                </div>
                <?php endif; ?>

                <?php if ($ioId && !empty($fields)): ?>
                <table class="table table-sm align-middle">
                    <thead class="table-light"><tr><th><?php echo __('Field'); ?></th><th><?php echo __('Type'); ?></th><th><?php echo __('Reason'); ?></th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($fields as $f): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($f->field_name); ?></code></td>
                        <td><?php echo htmlspecialchars($f->redaction_type); ?><?php echo $f->redaction_pattern ? ' <span class="text-muted small">('.htmlspecialchars($f->redaction_pattern).')</span>' : ''; ?></td>
                        <td class="small"><?php echo htmlspecialchars($f->reason); ?></td>
                        <td class="text-end">
                            <form method="post" class="d-inline" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'redactionManage', 'id' => $ioId]); ?>">
                                <input type="hidden" name="do" value="remove"><input type="hidden" name="field_id" value="<?php echo $f->id; ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <hr>
                <?php endif; ?>

                <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'redactionManage']); ?>">
                    <input type="hidden" name="do" value="add">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small"><?php echo __('Information object id'); ?></label>
                            <input type="number" name="io_id" class="form-control form-control-sm" required value="<?php echo $ioId ?: ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small"><?php echo __('Field'); ?></label>
                            <select name="field_name" class="form-select form-select-sm" required>
                                <?php foreach ($fieldNames as $fn): ?><option value="<?php echo $fn; ?>"><?php echo $fn; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small"><?php echo __('Redaction type'); ?></label>
                            <select name="redaction_type" class="form-select form-select-sm">
                                <option value="full"><?php echo __('Full'); ?></option>
                                <option value="partial"><?php echo __('Partial (pattern)'); ?></option>
                                <option value="pseudonymised"><?php echo __('Pseudonymised'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small"><?php echo __('Partial pattern'); ?></label>
                            <select name="redaction_pattern" class="form-select form-select-sm">
                                <option value=""><?php echo __('— none —'); ?></option>
                                <option value="email_partial">email_partial</option>
                                <option value="phone_partial">phone_partial</option>
                                <option value="id_last4">id_last4</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small"><?php echo __('Reason'); ?></label>
                            <select name="reason" class="form-select form-select-sm">
                                <?php foreach ($reasons as $r): ?><option value="<?php echo htmlspecialchars($r->label_en); ?>"><?php echo htmlspecialchars($r->label_en); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 form-check ms-2 mt-2">
                            <input type="checkbox" class="form-check-input" name="is_sensitive" id="is_sensitive" value="1">
                            <label class="form-check-label small" for="is_sensitive"><?php echo __('Sensitive (medical / biometric / financial)'); ?></label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-3"><i class="fas fa-plus me-1"></i><?php echo __('Save field redaction'); ?></button>
                </form>
            </div></div>
        </div>

        <div class="col-lg-5">
            <div class="card"><div class="card-header fw-bold"><?php echo __('Objects with redactions'); ?></div>
            <div class="list-group list-group-flush">
                <?php if (empty($profiled)): ?>
                <div class="list-group-item text-muted"><?php echo __('No objects have field redactions yet.'); ?></div>
                <?php else: foreach ($profiled as $p): ?>
                <a class="list-group-item list-group-item-action d-flex justify-content-between" href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'redactionManage', 'id' => $p->information_object_id]); ?>">
                    <span><?php echo $p->title ? htmlspecialchars($p->title) : __('IO #').$p->information_object_id; ?></span>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($p->redaction_status); ?></span>
                </a>
                <?php endforeach; endif; ?>
            </div></div>
        </div>
    </div>
</div>
