<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php include_partial('research/accessibilityHelpers') ?>

<nav aria-label="<?php echo __('Breadcrumb') ?>">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']) ?>"><?php echo __('Research') ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'retrievalQueue']) ?>"><?php echo __('Retrieval Queue') ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo __('Checkout') ?></li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-arrow-right-from-bracket text-primary me-2" aria-hidden="true"></i><?php echo __('Custody Checkout') ?></h1>

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
                    <dt class="col-sm-4"><?php echo __('Location') ?></dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($materialRequest->shelf_location ?? $materialRequest->location_code ?? '—') ?></dd>
                    <dt class="col-sm-4"><?php echo __('Researcher') ?></dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars(trim(($materialRequest->first_name ?? '') . ' ' . ($materialRequest->last_name ?? ''))) ?></dd>
                    <dt class="col-sm-4"><?php echo __('Priority') ?></dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?php echo match($materialRequest->priority ?? 'normal') { 'rush' => 'danger', 'high' => 'warning', default => 'info' } ?>" role="status">
                            <?php echo ucfirst($materialRequest->priority ?? 'normal') ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Checkout Form -->
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><?php echo __('Checkout Details') ?></h5></div>
            <div class="card-body">
                <form method="post" action="<?php echo url_for("research/custody/{$materialRequest->id}/checkout") ?>">
                    <div class="mb-3">
                        <label for="condition" class="form-label fw-bold"><?php echo __('Condition at Checkout') ?> <span class="text-danger" aria-hidden="true">*</span></label>
                        <select name="condition" id="condition" class="form-select" aria-required="true" required>
                            <?php foreach ($conditionOptions as $opt): ?>
                                <option value="<?php echo htmlspecialchars($opt->code) ?>"
                                        <?php echo $opt->is_default ? 'selected' : '' ?>
                                        style="color: <?php echo $opt->color ?? '' ?>">
                                    <?php echo htmlspecialchars($opt->label) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="barcode" class="form-label"><?php echo __('Barcode Scan') ?></label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true"><i class="fas fa-barcode"></i></span>
                            <input type="text" name="barcode" id="barcode" class="form-control"
                                   placeholder="<?php echo __('Scan or enter barcode...') ?>"
                                   aria-label="<?php echo __('Barcode') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="toLocation" class="form-label"><?php echo __('Destination') ?></label>
                        <input type="text" name="to_location" id="toLocation" class="form-control"
                               placeholder="<?php echo __('e.g., Reading Room A, Seat 5') ?>"
                               value="Reading Room">
                    </div>
                    <div class="mb-3">
                        <label for="checkoutNotes" class="form-label"><?php echo __('Notes') ?></label>
                        <textarea name="notes" id="checkoutNotes" class="form-control" rows="2"
                                  placeholder="<?php echo __('Any special handling notes...') ?>"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-arrow-right-from-bracket me-1" aria-hidden="true"></i><?php echo __('Confirm Checkout') ?>
                        </button>
                        <a href="<?php echo url_for(['module' => 'research', 'action' => 'retrievalQueue']) ?>" class="btn btn-outline-secondary">
                            <?php echo __('Cancel') ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-warning" role="alert"><i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i><?php echo __('Material request not found.') ?></div>
<?php endif ?>
