<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php include_partial('research/accessibilityHelpers') ?>

<nav aria-label="<?php echo __('Breadcrumb') ?>">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']) ?>"><?php echo __('Research') ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo __('Requests Dashboard') ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><i class="fas fa-clipboard-list text-primary me-2" aria-hidden="true"></i><?php echo __('Requests Dashboard') ?></h1>
</div>

<!-- Summary Cards -->
<div class="row mb-4" role="group" aria-label="<?php echo __('Request summary') ?>">
    <div class="col-md-3 mb-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <i class="fas fa-box-archive fa-2x text-primary mb-2" aria-hidden="true"></i>
                <h3 class="mb-0"><?php echo $dashboard['totals']['material'] ?? 0 ?></h3>
                <small class="text-muted"><?php echo __('Material Requests') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-info h-100">
            <div class="card-body text-center">
                <i class="fas fa-copy fa-2x text-info mb-2" aria-hidden="true"></i>
                <h3 class="mb-0"><?php echo $dashboard['totals']['reproduction'] ?? 0 ?></h3>
                <small class="text-muted"><?php echo __('Reproduction Requests') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-danger h-100">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2" aria-hidden="true"></i>
                <h3 class="mb-0"><?php echo $dashboard['totals']['overdue'] ?? 0 ?></h3>
                <small class="text-muted"><?php echo __('Overdue / Breached') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <form method="get" role="search" aria-label="<?php echo __('Filter requests') ?>">
                    <div class="mb-2">
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="<?php echo __('Search...') ?>"
                               value="<?php echo htmlspecialchars($filters['search'] ?? '') ?>"
                               aria-label="<?php echo __('Search requests') ?>">
                    </div>
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill">
                            <i class="fas fa-search" aria-hidden="true"></i> <?php echo __('Search') ?>
                        </button>
                        <?php if (!empty($filters)): ?>
                        <a href="<?php echo url_for(['module' => 'research', 'action' => 'requestsDashboard']) ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </a>
                        <?php endif ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Material Requests Table -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-box-archive me-2" aria-hidden="true"></i><?php echo __('Material Requests') ?></h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($dashboard['material'])): ?>
            <div class="alert alert-info m-3"><i class="fas fa-info-circle me-2" aria-hidden="true"></i><?php echo __('No material requests.') ?></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0" aria-label="<?php echo __('Material requests') ?>">
                <caption class="visually-hidden"><?php echo __('List of material requests with SLA status') ?></caption>
                <thead class="table-light">
                    <tr>
                        <th scope="col"><?php echo __('ID') ?></th>
                        <th scope="col"><?php echo __('Item') ?></th>
                        <th scope="col"><?php echo __('Researcher') ?></th>
                        <th scope="col"><?php echo __('Status') ?></th>
                        <th scope="col"><?php echo __('SLA') ?></th>
                        <th scope="col"><?php echo __('Triage') ?></th>
                        <th scope="col"><?php echo __('Assigned To') ?></th>
                        <th scope="col"><?php echo __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($dashboard['material'] as $req): ?>
                    <tr>
                        <td><code>#<?php echo $req->id ?></code></td>
                        <td><?php echo htmlspecialchars(substr($req->item_title ?? 'Untitled', 0, 50)) ?></td>
                        <td><?php echo htmlspecialchars(trim(($req->first_name ?? '') . ' ' . ($req->last_name ?? ''))) ?></td>
                        <td>
                            <?php $statusColor = match($req->status) { 'returned' => 'success', 'retrieved','delivered' => 'info', 'in_use' => 'primary', 'unavailable' => 'danger', default => 'warning' } ?>
                            <span class="badge bg-<?php echo $statusColor ?>" role="status" aria-label="<?php echo __('Status: %1%', ['%1%' => ucfirst(str_replace('_', ' ', $req->status))]) ?>">
                                <i class="fas fa-circle me-1" aria-hidden="true"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $req->status)) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($req->sla) && isset($req->sla['status'])): ?>
                                <?php $slaStatus = $req->sla['status'] ?>
                                <span class="badge" role="status"
                                      style="background-color: <?php echo $slaColors[$slaStatus] ?? '#6c757d' ?>"
                                      aria-label="<?php echo __('SLA: %1%', ['%1%' => ucfirst(str_replace('_', ' ', $slaStatus))]) ?>">
                                    <i class="fas <?php echo $slaIcons[$slaStatus] ?? 'fa-minus-circle' ?> me-1" aria-hidden="true"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $slaStatus)) ?>
                                </span>
                            <?php endif ?>
                        </td>
                        <td>
                            <?php if ($req->triage_status): ?>
                                <?php $triageColor = match($req->triage_status) { 'triage_approved' => 'success', 'triage_denied' => 'danger', 'needs_information' => 'warning', default => 'secondary' } ?>
                                <span class="badge bg-<?php echo $triageColor ?>" role="status">
                                    <?php echo ucfirst(str_replace(['triage_', '_'], ['', ' '], $req->triage_status)) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary" role="status"><i class="fas fa-clock me-1" aria-hidden="true"></i><?php echo __('Pending') ?></span>
                            <?php endif ?>
                        </td>
                        <td><?php echo htmlspecialchars($req->assigned_to_name ?? '—') ?></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group" aria-label="<?php echo __('Actions for request %1%', ['%1%' => $req->id]) ?>">
                                <a href="<?php echo url_for("research/request/{$req->id}/triage/material") ?>" class="btn btn-outline-primary" title="<?php echo __('Triage') ?>">
                                    <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                                </a>
                                <a href="<?php echo url_for("research/request/{$req->id}/correspond/material") ?>" class="btn btn-outline-info" title="<?php echo __('Correspondence') ?>">
                                    <i class="fas fa-envelope" aria-hidden="true"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- Reproduction Requests Table -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-copy me-2" aria-hidden="true"></i><?php echo __('Reproduction Requests') ?></h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($dashboard['reproduction'])): ?>
            <div class="alert alert-info m-3"><i class="fas fa-info-circle me-2" aria-hidden="true"></i><?php echo __('No reproduction requests.') ?></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0" aria-label="<?php echo __('Reproduction requests') ?>">
                <caption class="visually-hidden"><?php echo __('List of reproduction requests with SLA status') ?></caption>
                <thead class="table-light">
                    <tr>
                        <th scope="col"><?php echo __('Reference') ?></th>
                        <th scope="col"><?php echo __('Researcher') ?></th>
                        <th scope="col"><?php echo __('Purpose') ?></th>
                        <th scope="col"><?php echo __('Status') ?></th>
                        <th scope="col"><?php echo __('SLA') ?></th>
                        <th scope="col"><?php echo __('Triage') ?></th>
                        <th scope="col"><?php echo __('Cost') ?></th>
                        <th scope="col"><?php echo __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($dashboard['reproduction'] as $req): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($req->reference_number ?? 'N/A') ?></code></td>
                        <td><?php echo htmlspecialchars(trim(($req->first_name ?? '') . ' ' . ($req->last_name ?? ''))) ?></td>
                        <td><?php echo htmlspecialchars(substr($req->purpose ?? '', 0, 40)) ?></td>
                        <td>
                            <?php $statusColor = match($req->status) { 'completed' => 'success', 'processing','in_production' => 'info', 'cancelled' => 'danger', default => 'warning' } ?>
                            <span class="badge bg-<?php echo $statusColor ?>" role="status" aria-label="<?php echo __('Status: %1%', ['%1%' => ucfirst(str_replace('_', ' ', $req->status))]) ?>">
                                <i class="fas fa-circle me-1" aria-hidden="true"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $req->status)) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($req->sla) && isset($req->sla['status'])): ?>
                                <?php $slaStatus = $req->sla['status'] ?>
                                <span class="badge" role="status"
                                      style="background-color: <?php echo $slaColors[$slaStatus] ?? '#6c757d' ?>"
                                      aria-label="<?php echo __('SLA: %1%', ['%1%' => ucfirst(str_replace('_', ' ', $slaStatus))]) ?>">
                                    <i class="fas <?php echo $slaIcons[$slaStatus] ?? 'fa-minus-circle' ?> me-1" aria-hidden="true"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $slaStatus)) ?>
                                </span>
                            <?php endif ?>
                        </td>
                        <td>
                            <?php if ($req->triage_status): ?>
                                <?php $triageColor = match($req->triage_status) { 'triage_approved' => 'success', 'triage_denied' => 'danger', 'needs_information' => 'warning', default => 'secondary' } ?>
                                <span class="badge bg-<?php echo $triageColor ?>" role="status">
                                    <?php echo ucfirst(str_replace(['triage_', '_'], ['', ' '], $req->triage_status)) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary" role="status"><i class="fas fa-clock me-1" aria-hidden="true"></i><?php echo __('Pending') ?></span>
                            <?php endif ?>
                        </td>
                        <td><?php echo $req->estimated_cost ? 'R' . number_format($req->estimated_cost, 2) : '—' ?></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group" aria-label="<?php echo __('Actions for request %1%', ['%1%' => $req->reference_number ?? $req->id]) ?>">
                                <a href="<?php echo url_for("research/request/{$req->id}/triage/reproduction") ?>" class="btn btn-outline-primary" title="<?php echo __('Triage') ?>">
                                    <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                                </a>
                                <a href="<?php echo url_for("research/request/{$req->id}/correspond/reproduction") ?>" class="btn btn-outline-info" title="<?php echo __('Correspondence') ?>">
                                    <i class="fas fa-envelope" aria-hidden="true"></i>
                                </a>
                                <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewReproduction', 'id' => $req->id]) ?>" class="btn btn-outline-secondary" title="<?php echo __('View') ?>">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </div>
</div>

<div aria-live="polite" aria-atomic="true" class="visually-hidden" id="dashboardAnnouncements" role="status"></div>
