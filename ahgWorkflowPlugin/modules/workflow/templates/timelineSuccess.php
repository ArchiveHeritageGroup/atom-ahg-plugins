<?php use_helper('Date') ?>
<?php $actionMeta = []; foreach (sfOutputEscaper::unescape($actions) as $a) { $actionMeta[$a->code] = $a; } ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-stream me-2"></i>Event Timeline</h1>
            <?php if ($object): ?>
                <p class="text-muted mb-0"><?php echo esc_entities($object->title ?? "Object #{$objectId}") ?></p>
            <?php endif ?>
        </div>
        <div>
            <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'timeline', 'object_id' => $objectId, 'format' => 'json']) ?>" class="btn btn-sm btn-outline-secondary me-1" title="JSON export">
                <i class="fas fa-code me-1"></i>JSON
            </a>
            <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'timeline', 'object_id' => $objectId, 'format' => 'csv']) ?>" class="btn btn-sm btn-outline-secondary me-1" title="CSV export">
                <i class="fas fa-file-csv me-1"></i>CSV
            </a>
            <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="module" value="workflow">
                <input type="hidden" name="action" value="timeline">
                <input type="hidden" name="object_id" value="<?php echo $objectId ?>">
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-0">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="workflow" <?php echo ($filters['type'] ?? '') === 'workflow' ? 'selected' : '' ?>>Workflow</option>
                        <option value="notes" <?php echo ($filters['type'] ?? '') === 'notes' ? 'selected' : '' ?>>Notes</option>
                        <option value="system" <?php echo ($filters['type'] ?? '') === 'system' ? 'selected' : '' ?>>System</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-0">From</label>
                    <input type="date" name="date_from" value="<?php echo esc_entities($filters['date_from'] ?? '') ?>" class="form-control form-control-sm">
                </div>
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-0">To</label>
                    <input type="date" name="date_to" value="<?php echo esc_entities($filters['date_to'] ?? '') ?>" class="form-control form-control-sm">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Timeline -->
    <?php $timelineEvents = sfOutputEscaper::unescape($events) ?>
    <?php if (empty($timelineEvents)): ?>
        <div class="alert alert-info">No events found for this object.</div>
    <?php else: ?>
        <div class="timeline">
            <?php foreach ($timelineEvents as $event): ?>
                <?php
                    $meta = $actionMeta[$event->action] ?? null;
                    $color = $meta->color ?? '#6c757d';
                    $icon = $meta->icon ?? 'fa-circle';
                    $label = $meta->label ?? ucfirst(str_replace('_', ' ', $event->action));
                ?>
                <div class="card mb-2">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-start">
                            <span class="badge me-3 mt-1" style="background-color: <?php echo $color ?>; min-width: 120px;">
                                <i class="fas <?php echo $icon ?> me-1"></i><?php echo esc_entities($label) ?>
                            </span>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <?php if ($event->from_status || $event->to_status): ?>
                                            <span class="text-muted"><?php echo esc_entities($event->from_status ?? '?') ?></span>
                                            <i class="fas fa-arrow-right mx-1 text-muted small"></i>
                                            <strong><?php echo esc_entities($event->to_status ?? '?') ?></strong>
                                        <?php endif ?>
                                        <?php if ($event->comment): ?>
                                            <div class="small mt-1"><?php echo esc_entities($event->comment) ?></div>
                                        <?php endif ?>
                                    </div>
                                    <div class="text-end text-muted small text-nowrap ms-3">
                                        <div><?php echo esc_entities($event->performer_name ?? 'System') ?></div>
                                        <div><?php echo esc_entities($event->performed_at) ?></div>
                                        <?php if ($event->correlation_id): ?>
                                            <div class="badge bg-light text-dark" title="Bulk operation"><?php echo substr($event->correlation_id, 0, 8) ?>...</div>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
