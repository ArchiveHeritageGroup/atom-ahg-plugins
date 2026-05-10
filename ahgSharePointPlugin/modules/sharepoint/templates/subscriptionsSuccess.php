<?php $title = __('SharePoint webhook subscriptions'); ?>
<h1><?php echo $title; ?></h1>

<p class="lead text-muted"><?php echo __('Two subscriptions per ingest-enabled drive: driveItem (content) + list (metadata, including retention labels).'); ?></p>

<?php if (count($subscriptions) === 0): ?>
    <div class="alert alert-info">
        <?php echo __('No active subscriptions. Run'); ?>
        <code>php symfony sharepoint:subscribe --drive=&lt;id&gt;</code>
        <?php echo __('to create one.'); ?>
    </div>
<?php else: ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?php echo __('Drive'); ?></th>
                <th><?php echo __('Resource'); ?></th>
                <th><?php echo __('Status'); ?></th>
                <th><?php echo __('Expires'); ?></th>
                <th><?php echo __('Last renewed'); ?></th>
                <th><?php echo __('Subscription ID'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($subscriptions as $sub): ?>
            <tr>
                <td><?php echo (int) $sub->drive_id; ?></td>
                <td><code><?php echo htmlspecialchars($sub->resource); ?></code></td>
                <td>
                    <?php
                    $cls = $sub->status === 'active' ? 'success' : ($sub->status === 'error' ? 'danger' : 'secondary');
                    ?>
                    <span class="badge bg-<?php echo $cls; ?>"><?php echo htmlspecialchars($sub->status); ?></span>
                </td>
                <td><?php echo htmlspecialchars($sub->expires_at); ?></td>
                <td><?php echo htmlspecialchars($sub->last_renewed_at ?? '—'); ?></td>
                <td class="small text-muted"><?php echo htmlspecialchars($sub->subscription_id); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
