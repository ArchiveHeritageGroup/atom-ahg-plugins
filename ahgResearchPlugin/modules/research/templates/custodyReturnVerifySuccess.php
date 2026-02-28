<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php include_partial('research/accessibilityHelpers') ?>

<nav aria-label="<?php echo __('Breadcrumb') ?>">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']) ?>"><?php echo __('Research') ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'retrievalQueue']) . '?queue=return' ?>"><?php echo __('Retrieval Queue') ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo __('Return / Verify') ?></li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-arrow-right-to-bracket text-success me-2" aria-hidden="true"></i><?php echo __('Return & Verify Material') ?></h1>

<?php if ($materialRequest): ?>
<div class="row">
    <div class="col-lg-8">
        <!-- Item Details -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><?php echo __('Material Details') ?></h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4"><?php echo __('Request ID') ?></dt>
                    <dd class="col-sm-8"><code>#<?php echo $materialRequest->id ?></code></dd>
                    <dt class="col-sm-4"><?php echo __('Item') ?></dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($materialRequest->item_title ?? 'Untitled') ?></dd>
                    <dt class="col-sm-4"><?php echo __('Researcher') ?></dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars(trim(($materialRequest->first_name ?? '') . ' ' . ($materialRequest->last_name ?? ''))) ?></dd>
                    <dt class="col-sm-4"><?php echo __('Current Location') ?></dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($materialRequest->location_current ?? '—') ?></dd>
                    <dt class="col-sm-4"><?php echo __('Status') ?></dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?php echo match($materialRequest->status) { 'returned' => 'success', 'in_use' => 'primary', default => 'info' } ?>" role="status">
                            <i class="fas fa-circle me-1" aria-hidden="true"></i><?php echo ucfirst(str_replace('_', ' ', $materialRequest->status ?? '')) ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Return Form -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><?php echo __('Condition Assessment') ?></h5></div>
            <div class="card-body">
                <form method="post" action="<?php echo url_for("research/custody/{$materialRequest->id}/checkin") ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="conditionBefore" class="form-label fw-bold"><?php echo __('Condition Before (at checkout)') ?></label>
                            <select name="condition_before" id="conditionBefore" class="form-select" aria-required="true">
                                <?php foreach ($conditionOptions as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt->code) ?>" <?php echo $opt->is_default ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($opt->label) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="conditionAfter" class="form-label fw-bold"><?php echo __('Condition After (at return)') ?> <span class="text-danger" aria-hidden="true">*</span></label>
                            <select name="condition_after" id="conditionAfter" class="form-select" aria-required="true" required>
                                <?php foreach ($conditionOptions as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt->code) ?>" <?php echo $opt->is_default ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($opt->label) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="returnNotes" class="form-label"><?php echo __('Notes') ?></label>
                        <textarea name="notes" id="returnNotes" class="form-control" rows="3"
                                  placeholder="<?php echo __('Note any damage, missing pages, etc.') ?>"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-arrow-right-to-bracket me-1" aria-hidden="true"></i><?php echo __('Confirm Return') ?>
                        </button>
                        <a href="<?php echo url_for(['module' => 'research', 'action' => 'retrievalQueue']) . '?queue=return' ?>" class="btn btn-outline-secondary">
                            <?php echo __('Cancel') ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Handoff History -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-history me-2" aria-hidden="true"></i><?php echo __('Custody History') ?></h5></div>
            <div class="card-body p-0">
                <?php if (empty($handoffHistory)): ?>
                    <p class="text-muted p-3"><?php echo __('No previous handoffs.') ?></p>
                <?php else: ?>
                    <ul class="list-group list-group-flush" aria-label="<?php echo __('Custody handoff history') ?>">
                        <?php foreach ($handoffHistory as $h): ?>
                        <li class="list-group-item py-2">
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-<?php echo match($h->handoff_type) { 'checkout' => 'warning', 'checkin' => 'success', 'transfer' => 'info', default => 'secondary' } ?>" role="status">
                                    <?php echo ucfirst(str_replace('_', ' ', $h->handoff_type)) ?>
                                </span>
                                <small class="text-muted"><?php echo date('M j H:i', strtotime($h->created_at)) ?></small>
                            </div>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($h->from_handler_name ?? '?') ?> → <?php echo htmlspecialchars($h->to_handler_name ?? '?') ?>
                            </small>
                            <?php if ($h->condition_at_handoff): ?>
                                <br><small>Condition: <strong><?php echo ucfirst($h->condition_at_handoff) ?></strong></small>
                            <?php endif ?>
                            <?php if ($h->signature_confirmed): ?>
                                <br><small class="text-success"><i class="fas fa-signature me-1" aria-hidden="true"></i><?php echo __('Confirmed') ?></small>
                            <?php endif ?>
                        </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-warning" role="alert"><?php echo __('Material request not found.') ?></div>
<?php endif ?>
