<?php $title = sprintf(__('SharePoint event #%d'), (int) $event->id); ?>
<h1><?php echo $title; ?></h1>

<dl class="row">
    <dt class="col-sm-3"><?php echo __('Received'); ?></dt><dd class="col-sm-9"><?php echo htmlspecialchars($event->received_at); ?></dd>
    <dt class="col-sm-3"><?php echo __('Processed'); ?></dt><dd class="col-sm-9"><?php echo htmlspecialchars($event->processed_at ?? '—'); ?></dd>
    <dt class="col-sm-3"><?php echo __('Status'); ?></dt><dd class="col-sm-9"><code><?php echo htmlspecialchars($event->status); ?></code></dd>
    <dt class="col-sm-3"><?php echo __('Attempts'); ?></dt><dd class="col-sm-9"><?php echo (int) $event->attempts; ?></dd>
    <dt class="col-sm-3"><?php echo __('Drive'); ?></dt><dd class="col-sm-9"><?php echo (int) $event->drive_id; ?></dd>
    <dt class="col-sm-3"><?php echo __('SP item'); ?></dt><dd class="col-sm-9 small text-muted"><?php echo htmlspecialchars($event->sp_item_id ?? '—'); ?></dd>
    <dt class="col-sm-3"><?php echo __('eTag'); ?></dt><dd class="col-sm-9 small text-muted"><?php echo htmlspecialchars($event->sp_etag ?? '—'); ?></dd>
    <dt class="col-sm-3"><?php echo __('AtoM information_object'); ?></dt>
    <dd class="col-sm-9"><?php echo $event->information_object_id ? (int) $event->information_object_id : '—'; ?></dd>
</dl>

<?php if (!empty($event->last_error)): ?>
    <div class="alert alert-danger"><strong><?php echo __('Last error'); ?>:</strong> <?php echo htmlspecialchars($event->last_error); ?></div>
<?php endif; ?>

<h3 class="mt-4"><?php echo __('Raw payload'); ?></h3>
<pre class="bg-light p-3 small"><?php echo htmlspecialchars(is_string($event->raw_payload) ? $event->raw_payload : json_encode($event->raw_payload, JSON_PRETTY_PRINT)); ?></pre>

<form method="post" class="mt-3">
    <input type="hidden" name="form_action" value="retry">
    <button type="submit" class="btn btn-warning">
        <i class="fa fa-redo me-1"></i><?php echo __('Re-queue this event'); ?>
    </button>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'events']); ?>" class="btn btn-link"><?php echo __('Back to event log'); ?></a>
</form>
