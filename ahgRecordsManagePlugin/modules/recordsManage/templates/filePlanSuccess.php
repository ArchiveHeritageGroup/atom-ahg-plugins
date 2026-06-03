<?php
$rows = $sf_data->getRaw('nodes');
$dropdown = $sf_data->getRaw('dropdown');
$stats = $sf_data->getRaw('stats');
$edit = $sf_data->getRaw('editNode');
$types = ['function', 'series', 'subseries', 'file', 'class'];
$actions = ['', 'destroy', 'transfer', 'retain_permanent', 'review'];
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="h2"><i class="fas fa-sitemap me-2"></i><?php echo __('File plan / classification scheme'); ?></span>
        <span class="text-muted"><?php echo (int) ($stats['total_nodes'] ?? 0); ?> <?php echo __('nodes'); ?></span>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
    <?php if ($sf_user->hasFlash('error')): ?><div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div><?php endif; ?>

    <div class="row">
        <div class="col-lg-7">
            <div class="card"><div class="card-header fw-bold"><?php echo __('Classification tree'); ?></div>
            <div class="table-responsive"><table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th><?php echo __('Code / Title'); ?></th><th><?php echo __('Type'); ?></th><th><?php echo __('Retention'); ?></th><th><?php echo __('Records'); ?></th><th></th></tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4"><?php echo __('No classification nodes yet — add one →'); ?></td></tr>
                <?php else: foreach ($rows as $n): ?>
                <tr>
                    <td><span style="padding-left: <?php echo (int) $n->depth * 18; ?>px"><code><?php echo htmlspecialchars($n->code); ?></code> <?php echo htmlspecialchars($n->title); ?></span></td>
                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($n->node_type); ?></span></td>
                    <td class="small"><?php echo htmlspecialchars((string) $n->retention_period); ?><?php echo $n->disposal_action ? ' <span class="text-muted">('.htmlspecialchars($n->disposal_action).')</span>' : ''; ?></td>
                    <td><?php echo (int) ($n->record_count ?? 0); ?></td>
                    <td class="text-end">
                        <a href="<?php echo url_for(['module' => 'recordsManage', 'action' => 'filePlan', 'edit' => $n->id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i></a>
                        <form method="post" class="d-inline" action="<?php echo url_for(['module' => 'recordsManage', 'action' => 'filePlan']); ?>" onsubmit="return confirm('<?php echo __('Delete this node?'); ?>');">
                            <input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?php echo $n->id; ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table></div></div>
        </div>

        <div class="col-lg-5">
            <div class="card"><div class="card-header fw-bold"><?php echo $edit ? __('Edit node').': '.htmlspecialchars($edit->code) : __('Add node'); ?></div><div class="card-body">
                <form method="post" action="<?php echo url_for(['module' => 'recordsManage', 'action' => 'filePlan']); ?>">
                    <input type="hidden" name="do" value="<?php echo $edit ? 'edit' : 'add'; ?>">
                    <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit->id; ?>"><?php endif; ?>
                    <div class="mb-2"><label class="form-label small"><?php echo __('Parent'); ?></label>
                        <select name="parent_id" class="form-select form-select-sm">
                            <option value="">&mdash; <?php echo __('top level'); ?> &mdash;</option>
                            <?php foreach ((array) $dropdown as $d): if ($edit && $d->id == $edit->id) { continue; } ?>
                            <option value="<?php echo $d->id; ?>" <?php echo ($edit && $edit->parent_id == $d->id) ? 'selected' : ''; ?>><?php echo str_repeat('— ', (int) $d->depth).htmlspecialchars($d->code.' '.$d->title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-5"><label class="form-label small"><?php echo __('Code'); ?> *</label><input name="code" class="form-control form-control-sm" required value="<?php echo $edit ? htmlspecialchars($edit->code) : ''; ?>"></div>
                        <div class="col-7"><label class="form-label small"><?php echo __('Type'); ?></label>
                            <select name="node_type" class="form-select form-select-sm">
                                <?php foreach ($types as $t): ?><option value="<?php echo $t; ?>" <?php echo ($edit && $edit->node_type === $t) ? 'selected' : ''; ?>><?php echo $t; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2 mt-2"><label class="form-label small"><?php echo __('Title'); ?> *</label><input name="title" class="form-control form-control-sm" required value="<?php echo $edit ? htmlspecialchars($edit->title) : ''; ?>"></div>
                    <div class="mb-2"><label class="form-label small"><?php echo __('Description'); ?></label><textarea name="description" class="form-control form-control-sm" rows="2"><?php echo $edit ? htmlspecialchars((string) $edit->description) : ''; ?></textarea></div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small"><?php echo __('Retention period'); ?></label><input name="retention_period" class="form-control form-control-sm" value="<?php echo $edit ? htmlspecialchars((string) $edit->retention_period) : ''; ?>"></div>
                        <div class="col-6"><label class="form-label small"><?php echo __('Disposal action'); ?></label>
                            <select name="disposal_action" class="form-select form-select-sm">
                                <?php foreach ($actions as $a): ?><option value="<?php echo $a; ?>" <?php echo ($edit && $edit->disposal_action === $a) ? 'selected' : ''; ?>><?php echo $a ?: '—'; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm mt-3"><i class="fas fa-save me-1"></i><?php echo $edit ? __('Save') : __('Add node'); ?></button>
                    <?php if ($edit): ?><a href="<?php echo url_for(['module' => 'recordsManage', 'action' => 'filePlan']); ?>" class="btn btn-outline-secondary btn-sm mt-3"><?php echo __('Cancel'); ?></a><?php endif; ?>
                </form>
            </div></div>
        </div>
    </div>
</div>
