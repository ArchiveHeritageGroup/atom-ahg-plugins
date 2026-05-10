<?php $title = __('SharePoint inbound events'); ?>
<h1><?php echo $title; ?></h1>

<form method="get" class="mb-3">
    <label for="status" class="me-2"><?php echo __('Filter by status'); ?>:</label>
    <select name="status" id="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
        <option value=""><?php echo __('All'); ?></option>
        <?php foreach (['received','queued','processing','completed','failed','skipped_duplicate','skipped_not_allowlisted'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
        <?php endforeach; ?>
    </select>
</form>

<table class="table table-sm table-striped">
    <thead>
        <tr>
            <th><?php echo __('ID'); ?></th>
            <th><?php echo __('Received'); ?></th>
            <th><?php echo __('Drive'); ?></th>
            <th><?php echo __('Item'); ?></th>
            <th><?php echo __('Change'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Attempts'); ?></th>
            <th><?php echo __('IO'); ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($events as $ev): ?>
        <tr>
            <td><?php echo (int) $ev->id; ?></td>
            <td><?php echo htmlspecialchars($ev->received_at); ?></td>
            <td><?php echo (int) $ev->drive_id; ?></td>
            <td class="small text-muted"><?php echo htmlspecialchars($ev->sp_item_id ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($ev->change_type); ?></td>
            <td>
                <?php
                $map = [
                    'received' => 'secondary',
                    'queued' => 'info',
                    'processing' => 'primary',
                    'completed' => 'success',
                    'failed' => 'danger',
                    'skipped_duplicate' => 'warning',
                    'skipped_not_allowlisted' => 'warning',
                ];
                $cls = $map[$ev->status] ?? 'secondary';
                ?>
                <span class="badge bg-<?php echo $cls; ?>"><?php echo htmlspecialchars($ev->status); ?></span>
            </td>
            <td><?php echo (int) $ev->attempts; ?></td>
            <td><?php echo $ev->information_object_id ? (int) $ev->information_object_id : '—'; ?></td>
            <td>
                <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'eventDetail', 'id' => $ev->id]); ?>" class="btn btn-sm btn-outline-secondary"><?php echo __('Detail'); ?></a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
