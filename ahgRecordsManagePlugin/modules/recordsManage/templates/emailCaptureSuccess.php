<?php
$rows = $sf_data->getRaw('rows');
$counts = $sf_data->getRaw('counts');
$nodes = $sf_data->getRaw('nodes');
$badge = ['captured' => 'secondary', 'classified' => 'warning', 'declared' => 'success'];
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="h2"><i class="fas fa-envelope-open-text me-2"></i><?php echo __('Email capture'); ?></span>
        <div>
            <span class="badge bg-secondary"><?php echo (int) $counts['captured']; ?> <?php echo __('captured'); ?></span>
            <span class="badge bg-warning text-dark"><?php echo (int) $counts['classified']; ?> <?php echo __('classified'); ?></span>
            <span class="badge bg-success"><?php echo (int) $counts['declared']; ?> <?php echo __('declared'); ?></span>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
    <?php if ($sf_user->hasFlash('error')): ?><div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div><?php endif; ?>

    <div class="card mb-4"><div class="card-body">
        <form method="post" enctype="multipart/form-data" action="<?php echo url_for(['module' => 'recordsManage', 'action' => 'emailCapture']); ?>" class="row g-2 align-items-end">
            <input type="hidden" name="do" value="upload">
            <div class="col-md-6">
                <label class="form-label small"><?php echo __('Capture an .eml email file'); ?></label>
                <input type="file" name="eml" accept=".eml,message/rfc822" class="form-control form-control-sm" required>
            </div>
            <div class="col-auto"><button class="btn btn-primary btn-sm"><i class="fas fa-upload me-1"></i><?php echo __('Capture'); ?></button></div>
            <div class="col-12"><span class="text-muted small"><?php echo __('The original .eml is preserved; you can then classify it to a file-plan node and declare it as a record.'); ?></span></div>
        </form>
    </div></div>

    <div class="card"><div class="card-header fw-bold"><?php echo __('Capture queue'); ?></div>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0 align-middle">
        <thead class="table-light"><tr><th><?php echo __('From'); ?></th><th><?php echo __('Subject'); ?></th><th><?php echo __('Sent'); ?></th><th><?php echo __('Status'); ?></th><th><?php echo __('Classify / Declare'); ?></th></tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4"><?php echo __('No emails captured yet'); ?></td></tr>
        <?php else: foreach ($rows as $e): ?>
        <tr>
            <td class="small"><?php echo htmlspecialchars((string) $e->from_address); ?></td>
            <td><?php echo htmlspecialchars((string) $e->subject); ?><?php echo $e->attachment_count ? ' <i class="fas fa-paperclip text-muted" title="'.$e->attachment_count.' attachment(s)"></i>' : ''; ?></td>
            <td class="small text-muted"><?php echo htmlspecialchars(substr((string) $e->sent_at, 0, 16)); ?></td>
            <td><span class="badge bg-<?php echo $badge[$e->status] ?? 'secondary'; ?>"><?php echo htmlspecialchars($e->status); ?></span><?php echo $e->fileplan_code ? '<div class="small text-muted">'.htmlspecialchars($e->fileplan_code).'</div>' : ''; ?></td>
            <td>
                <?php if ($e->status !== 'declared'): ?>
                <form method="post" class="d-flex gap-1 align-items-center" action="<?php echo url_for(['module' => 'recordsManage', 'action' => 'emailCapture']); ?>">
                    <input type="hidden" name="do" value="classify"><input type="hidden" name="id" value="<?php echo $e->id; ?>">
                    <select name="fileplan_node_id" class="form-select form-select-sm" style="max-width:240px" required>
                        <option value=""><?php echo __('— file-plan node —'); ?></option>
                        <?php foreach ((array) $nodes as $n): ?><option value="<?php echo $n->id; ?>" <?php echo $e->fileplan_node_id == $n->id ? 'selected' : ''; ?>><?php echo str_repeat('— ', (int) $n->depth).htmlspecialchars($n->code.' '.$n->title); ?></option><?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-warning" title="<?php echo __('Classify'); ?>"><i class="fas fa-tags"></i></button>
                </form>
                <?php if ($e->status === 'classified'): ?>
                <form method="post" class="mt-1" action="<?php echo url_for(['module' => 'recordsManage', 'action' => 'emailCapture']); ?>">
                    <input type="hidden" name="do" value="declare"><input type="hidden" name="id" value="<?php echo $e->id; ?>">
                    <button class="btn btn-sm btn-success"><i class="fas fa-file-circle-check me-1"></i><?php echo __('Declare as record'); ?></button>
                </form>
                <?php endif; ?>
                <?php else: ?>
                <span class="text-muted small"><i class="fas fa-check"></i> <?php echo __('IO #').$e->information_object_id; ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table></div></div>
</div>
