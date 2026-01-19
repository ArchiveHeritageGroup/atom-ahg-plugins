<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-calendar-event text-primary me-2"></i><?php echo __('Preservation Events (PREMIS)'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="btn-group flex-wrap">
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'events']); ?>"
           class="btn btn-sm btn-<?php echo !$currentType ? 'primary' : 'outline-primary'; ?>">All</a>
        <?php foreach ($eventTypes as $type): ?>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'events', 'type' => $type->event_type]); ?>"
           class="btn btn-sm btn-<?php echo $currentType === $type->event_type ? 'primary' : 'outline-primary'; ?>">
            <?php echo str_replace('_', ' ', ucfirst($type->event_type)); ?> (<?php echo $type->count; ?>)
        </a>
        <?php endforeach; ?>
    </div>
    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Event Type'); ?></th>
                    <th><?php echo __('Object'); ?></th>
                    <th><?php echo __('Detail'); ?></th>
                    <th><?php echo __('Outcome'); ?></th>
                    <th><?php echo __('Agent'); ?></th>
                    <th><?php echo __('Date/Time'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                <tr>
                    <td><span class="badge bg-secondary"><?php echo str_replace('_', ' ', $event->event_type); ?></span></td>
                    <td>
                        <?php if ($event->digital_object_id): ?>
                            <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'object', 'id' => $event->digital_object_id]); ?>">
                                <?php echo htmlspecialchars(substr($event->filename ?? $event->object_title ?? 'Object', 0, 30)); ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(substr($event->event_detail ?? '', 0, 50)); ?></td>
                    <td>
                        <?php if ($event->event_outcome === 'success'): ?>
                            <span class="badge bg-success">Success</span>
                        <?php elseif ($event->event_outcome === 'failure'): ?>
                            <span class="badge bg-danger">Failure</span>
                        <?php elseif ($event->event_outcome === 'warning'): ?>
                            <span class="badge bg-warning">Warning</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?php echo ucfirst($event->event_outcome); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><small><?php echo htmlspecialchars($event->linking_agent_value ?? '-'); ?></small></td>
                    <td><small><?php echo date('Y-m-d H:i', strtotime($event->event_datetime)); ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php end_slot() ?>
