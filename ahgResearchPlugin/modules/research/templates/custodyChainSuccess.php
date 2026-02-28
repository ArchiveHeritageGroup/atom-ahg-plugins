<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php include_partial('research/accessibilityHelpers') ?>

<nav aria-label="<?php echo __('Breadcrumb') ?>">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']) ?>"><?php echo __('Research') ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'retrievalQueue']) ?>"><?php echo __('Retrieval Queue') ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo __('Custody Chain') ?></li>
    </ol>
</nav>

<h1 class="h2 mb-4">
    <i class="fas fa-link text-primary me-2" aria-hidden="true"></i>
    <?php echo __('Custody Chain') ?>: <?php echo htmlspecialchars($objectTitle) ?>
</h1>

<?php if (empty($chain)): ?>
    <div class="alert alert-info" role="status">
        <i class="fas fa-info-circle me-2" aria-hidden="true"></i>
        <?php echo __('No custody records found for this object.') ?>
    </div>
<?php else: ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo __('Full Chain of Custody') ?> (<?php echo count($chain) ?> <?php echo __('events') ?>)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" aria-label="<?php echo __('Chain of custody for %1%', ['%1%' => $objectTitle]) ?>">
                <caption class="visually-hidden"><?php echo __('Chronological chain of custody events combining handoffs, movements, and provenance') ?></caption>
                <thead class="table-light">
                    <tr>
                        <th scope="col"><?php echo __('Date') ?></th>
                        <th scope="col"><?php echo __('Source') ?></th>
                        <th scope="col"><?php echo __('Event') ?></th>
                        <th scope="col"><?php echo __('From') ?></th>
                        <th scope="col"><?php echo __('To') ?></th>
                        <th scope="col"><?php echo __('Condition') ?></th>
                        <th scope="col"><?php echo __('Confirmed') ?></th>
                        <th scope="col"><?php echo __('Notes') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chain as $event): ?>
                    <tr>
                        <td>
                            <?php if ($event->event_date): ?>
                                <span title="<?php echo $event->event_date ?>"><?php echo date('M j, Y H:i', strtotime($event->event_date)) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif ?>
                        </td>
                        <td>
                            <?php
                            $sourceColor = match($event->source) {
                                'custody_handoff' => 'primary',
                                'spectrum_movement' => 'info',
                                'provenance' => 'warning',
                                default => 'secondary',
                            };
                            $sourceIcon = match($event->source) {
                                'custody_handoff' => 'hand-holding',
                                'spectrum_movement' => 'truck',
                                'provenance' => 'scroll',
                                default => 'circle',
                            };
                            ?>
                            <span class="badge bg-<?php echo $sourceColor ?>" role="note">
                                <i class="fas fa-<?php echo $sourceIcon ?> me-1" aria-hidden="true"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $event->source)) ?>
                            </span>
                        </td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $event->event_type ?? '')) ?></td>
                        <td>
                            <?php if ($event->from_handler): ?>
                                <small><?php echo htmlspecialchars($event->from_handler) ?></small>
                            <?php endif ?>
                            <?php if ($event->from_location): ?>
                                <br><small class="text-muted"><i class="fas fa-map-pin me-1" aria-hidden="true"></i><?php echo htmlspecialchars($event->from_location) ?></small>
                            <?php endif ?>
                        </td>
                        <td>
                            <?php if ($event->to_handler): ?>
                                <small><?php echo htmlspecialchars($event->to_handler) ?></small>
                            <?php endif ?>
                            <?php if ($event->to_location): ?>
                                <br><small class="text-muted"><i class="fas fa-map-pin me-1" aria-hidden="true"></i><?php echo htmlspecialchars($event->to_location) ?></small>
                            <?php endif ?>
                        </td>
                        <td>
                            <?php if ($event->condition_status): ?>
                                <?php $condColor = match($event->condition_status) { 'excellent' => 'success', 'good' => 'info', 'fair' => 'warning', 'poor' => 'warning', 'critical' => 'danger', default => 'secondary' } ?>
                                <span class="badge bg-<?php echo $condColor ?>" role="status" aria-label="<?php echo __('Condition: %1%', ['%1%' => ucfirst($event->condition_status)]) ?>">
                                    <?php echo ucfirst($event->condition_status) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif ?>
                        </td>
                        <td>
                            <?php if ($event->signature_confirmed): ?>
                                <i class="fas fa-check-circle text-success" aria-hidden="true" title="<?php echo __('Confirmed') ?>"></i>
                                <span class="visually-hidden"><?php echo __('Confirmed') ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif ?>
                        </td>
                        <td><small><?php echo htmlspecialchars(substr($event->notes ?? '', 0, 60)) ?></small></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif ?>
