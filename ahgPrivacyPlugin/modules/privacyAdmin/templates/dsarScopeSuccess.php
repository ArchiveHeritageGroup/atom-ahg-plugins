<?php
$dsar = $sf_data->getRaw('dsar');
$objects = $sf_data->getRaw('objects');
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarView', 'id' => $dsar->id]); ?>" class="btn btn-outline-secondary btn-sm me-2"><i class="fas fa-arrow-left"></i></a>
            <span class="h2"><i class="fas fa-user-shield me-2"></i><?php echo __('Redaction scope'); ?> - <?php echo esc_entities($dsar->reference_number); ?></span>
            <div class="text-muted small mt-1"><?php echo __('Add the descriptions covered by this request. Each one gets a privacy profile pre-populated (status: pending) so you can mark fields for redaction in the response.'); ?></div>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
    <?php if ($sf_user->hasFlash('error')): ?><div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header fw-bold"><?php echo __('Add a description to scope'); ?></div>
        <div class="card-body">
            <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarScope', 'id' => $dsar->id]); ?>" class="row g-2">
                <input type="hidden" name="do" value="add">
                <div class="col-md-9">
                    <label class="form-label small"><?php echo __('Archival description (numeric id or slug)'); ?></label>
                    <input type="text" name="io" class="form-control form-control-sm" required placeholder="<?php echo __('e.g. 1234 or my-collection-slug'); ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-plus me-1"></i><?php echo __('Add and pre-populate'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-bold"><?php echo __('Descriptions in scope'); ?></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr>
                    <th><?php echo __('Description'); ?></th>
                    <th><?php echo __('Privacy status'); ?></th>
                    <th><?php echo __('Added'); ?></th>
                    <th class="text-end"><?php echo __('Actions'); ?></th>
                </tr></thead>
                <tbody>
                <?php if (!empty($objects)): foreach ($objects as $o): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?php echo $o->title !== null ? htmlspecialchars((string) $o->title) : __('(untitled)'); ?></div>
                            <div class="text-muted small">#<?php echo (int) $o->information_object_id; ?></div>
                        </td>
                        <td>
                            <?php $st = $o->redaction_status ?? 'pending'; ?>
                            <span class="badge <?php echo $st === 'full' ? 'bg-danger' : ($st === 'partial' ? 'bg-warning text-dark' : 'bg-info text-dark'); ?>"><?php echo ucfirst($st); ?></span>
                        </td>
                        <td class="small text-muted"><?php echo htmlspecialchars((string) $o->created_at); ?></td>
                        <td class="text-end">
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'redactionManage', 'id' => $o->information_object_id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen me-1"></i><?php echo __('Redact fields'); ?></a>
                            <form method="post" class="d-inline" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarScope', 'id' => $dsar->id]); ?>" onsubmit="return confirm('<?php echo __('Remove from scope?'); ?>');">
                                <input type="hidden" name="do" value="remove"><input type="hidden" name="io_id" value="<?php echo (int) $o->information_object_id; ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" class="text-center text-muted p-4"><?php echo __('No descriptions in scope yet.'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
