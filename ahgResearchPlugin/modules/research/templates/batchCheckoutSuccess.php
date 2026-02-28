<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php include_partial('research/accessibilityHelpers') ?>

<nav aria-label="<?php echo __('Breadcrumb') ?>">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']) ?>"><?php echo __('Research') ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'retrievalQueue']) ?>"><?php echo __('Retrieval Queue') ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo __('Batch Checkout') ?></li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-boxes-stacked text-primary me-2" aria-hidden="true"></i><?php echo __('Batch Checkout') ?></h1>

<?php if (empty($requests)): ?>
    <div class="alert alert-warning" role="alert"><?php echo __('No requests selected for batch checkout.') ?></div>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'retrievalQueue']) ?>" class="btn btn-outline-primary"><?php echo __('Back to Queue') ?></a>
<?php else: ?>
<form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'batchCheckout']) ?>">
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Default Condition for All Items') ?></h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label for="batchCondition" class="form-label fw-bold"><?php echo __('Condition') ?></label>
                    <select name="condition" id="batchCondition" class="form-select">
                        <?php foreach ($conditionOptions as $opt): ?>
                            <option value="<?php echo htmlspecialchars($opt->code) ?>" <?php echo $opt->is_default ? 'selected' : '' ?>>
                                <?php echo htmlspecialchars($opt->label) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="batchLocation" class="form-label"><?php echo __('Destination') ?></label>
                    <input type="text" name="to_location" id="batchLocation" class="form-control" value="Reading Room">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Items') ?> (<?php echo count($requests) ?>)</h5></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" aria-label="<?php echo __('Items for batch checkout') ?>">
                <caption class="visually-hidden"><?php echo __('Select items to check out in batch') ?></caption>
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width: 40px;">
                            <input type="checkbox" class="form-check-input" id="selectAllBatch" checked
                                   aria-label="<?php echo __('Select all items') ?>">
                        </th>
                        <th scope="col"><?php echo __('ID') ?></th>
                        <th scope="col"><?php echo __('Item') ?></th>
                        <th scope="col"><?php echo __('Researcher') ?></th>
                        <th scope="col"><?php echo __('Location') ?></th>
                        <th scope="col"><?php echo __('Priority') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="request_ids[]" value="<?php echo $req->id ?>"
                                   class="form-check-input batch-item-cb" checked
                                   aria-label="<?php echo __('Select %1% for checkout', ['%1%' => htmlspecialchars($req->item_title ?? 'Request #' . $req->id)]) ?>">
                            <input type="hidden" name="researcher_id" value="<?php echo $req->researcher_id ?? 0 ?>">
                        </td>
                        <td><code>#<?php echo $req->id ?></code></td>
                        <td><?php echo htmlspecialchars($req->item_title ?? 'Untitled') ?></td>
                        <td><?php echo htmlspecialchars(trim(($req->first_name ?? '') . ' ' . ($req->last_name ?? ''))) ?></td>
                        <td><?php echo htmlspecialchars($req->shelf_location ?? $req->location_code ?? '—') ?></td>
                        <td>
                            <span class="badge bg-<?php echo match($req->priority ?? 'normal') { 'rush' => 'danger', 'high' => 'warning', default => 'info' } ?>" role="status">
                                <i class="fas fa-<?php echo match($req->priority ?? 'normal') { 'rush' => 'bolt', 'high' => 'arrow-up', default => 'minus' } ?> me-1" aria-hidden="true"></i>
                                <?php echo ucfirst($req->priority ?? 'normal') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-arrow-right-from-bracket me-1" aria-hidden="true"></i><?php echo __('Checkout Selected Items') ?>
        </button>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'retrievalQueue']) ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
    </div>
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('selectAllBatch').addEventListener('change', function() {
    document.querySelectorAll('.batch-item-cb').forEach(function(cb) { cb.checked = this.checked; }.bind(this));
});
</script>
<?php endif ?>
