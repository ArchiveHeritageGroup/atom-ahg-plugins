<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php include_partial('research/accessibilityHelpers') ?>

<nav aria-label="<?php echo __('Breadcrumb') ?>">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']) ?>"><?php echo __('Research') ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'retrievalQueue']) . '?queue=return' ?>"><?php echo __('Retrieval Queue') ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo __('Batch Return') ?></li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-undo text-success me-2" aria-hidden="true"></i><?php echo __('Batch Return') ?></h1>

<?php if (empty($requests)): ?>
    <div class="alert alert-warning" role="alert"><?php echo __('No requests selected for batch return.') ?></div>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'retrievalQueue']) . '?queue=return' ?>" class="btn btn-outline-primary"><?php echo __('Back to Queue') ?></a>
<?php else: ?>
<form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'batchReturn']) ?>">
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Return Items') ?> (<?php echo count($requests) ?>)</h5></div>
        <div class="card-body p-0">
            <table class="table mb-0" aria-label="<?php echo __('Items for batch return') ?>">
                <caption class="visually-hidden"><?php echo __('Set condition for each returning item') ?></caption>
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width: 40px;">
                            <input type="checkbox" class="form-check-input" id="selectAllReturn" checked
                                   aria-label="<?php echo __('Select all items') ?>">
                        </th>
                        <th scope="col"><?php echo __('Item') ?></th>
                        <th scope="col"><?php echo __('Researcher') ?></th>
                        <th scope="col"><?php echo __('Condition Before') ?></th>
                        <th scope="col"><?php echo __('Condition After') ?></th>
                        <th scope="col"><?php echo __('Notes') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="request_ids[]" value="<?php echo $req->id ?>"
                                   class="form-check-input return-item-cb" checked
                                   aria-label="<?php echo __('Select %1% for return', ['%1%' => htmlspecialchars($req->item_title ?? 'Request #' . $req->id)]) ?>">
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars(substr($req->item_title ?? 'Untitled', 0, 40)) ?></strong>
                            <br><small class="text-muted"><code>#<?php echo $req->id ?></code></small>
                        </td>
                        <td><?php echo htmlspecialchars(trim(($req->first_name ?? '') . ' ' . ($req->last_name ?? ''))) ?></td>
                        <td>
                            <select name="condition_before_<?php echo $req->id ?>" class="form-select form-select-sm"
                                    aria-label="<?php echo __('Condition before for %1%', ['%1%' => $req->item_title ?? 'item']) ?>">
                                <?php foreach ($conditionOptions as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt->code) ?>" <?php echo $opt->is_default ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($opt->label) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </td>
                        <td>
                            <select name="condition_after_<?php echo $req->id ?>" class="form-select form-select-sm"
                                    aria-label="<?php echo __('Condition after for %1%', ['%1%' => $req->item_title ?? 'item']) ?>">
                                <?php foreach ($conditionOptions as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt->code) ?>" <?php echo $opt->is_default ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($opt->label) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="notes_<?php echo $req->id ?>" class="form-control form-control-sm"
                                   placeholder="<?php echo __('Damage notes...') ?>"
                                   aria-label="<?php echo __('Return notes for %1%', ['%1%' => $req->item_title ?? 'item']) ?>">
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-undo me-1" aria-hidden="true"></i><?php echo __('Process Returns') ?>
        </button>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'retrievalQueue']) . '?queue=return' ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
    </div>
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('selectAllReturn').addEventListener('change', function() {
    document.querySelectorAll('.return-item-cb').forEach(function(cb) { cb.checked = this.checked; }.bind(this));
});
</script>
<?php endif ?>
