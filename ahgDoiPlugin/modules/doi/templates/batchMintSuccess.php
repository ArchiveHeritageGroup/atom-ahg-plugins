<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-plus me-2"></i>Batch Mint DOIs</h1>
            <p class="text-muted">Queue multiple records for DOI minting</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $sf_user->getFlash('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <form method="post" action="<?php echo url_for(['module' => 'doi', 'action' => 'batchMint']) ?>">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Records Without DOIs</h5>
                <div>
                    <button type="button" id="select-all" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-check-square me-1"></i> Select All
                    </button>
                    <button type="button" id="deselect-all" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-square me-1"></i> Deselect All
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($records->isEmpty()): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                        <p>All records have DOIs!</p>
                        <a href="<?php echo url_for(['module' => 'doi', 'action' => 'browse']) ?>" class="btn btn-outline-primary">View All DOIs</a>
                    </div>
                <?php else: ?>
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="check-all">
                                </th>
                                <th>Title</th>
                                <th>ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="object_ids[]" value="<?php echo $record->id ?>"
                                               class="form-check-input record-checkbox">
                                    </td>
                                    <td><?php echo htmlspecialchars($record->title ?? 'Untitled') ?></td>
                                    <td class="text-muted"><?php echo $record->id ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                    <div class="text-muted p-3 small">
                        Showing up to 100 records. Use CLI for larger batches: <code>php symfony doi:mint --all --limit=500</code>
                    </div>
                <?php endif ?>
            </div>
        </div>

        <?php if (!$records->isEmpty()): ?>
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <label class="form-label">Initial DOI State</label>
                            <select name="state" class="form-select" style="max-width: 200px;">
                                <option value="findable">Findable (Recommended)</option>
                                <option value="registered">Registered</option>
                                <option value="draft">Draft</option>
                            </select>
                            <div class="form-text">
                                Findable = publicly discoverable, Registered = resolvable but not indexed, Draft = not yet active
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus me-1"></i> Queue Selected for Minting
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var checkAll = document.getElementById('check-all');
    var selectAll = document.getElementById('select-all');
    var deselectAll = document.getElementById('deselect-all');
    var checkboxes = document.querySelectorAll('.record-checkbox');

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            checkboxes.forEach(function(cb) {
                cb.checked = checkAll.checked;
            });
        });
    }

    if (selectAll) {
        selectAll.addEventListener('click', function() {
            checkboxes.forEach(function(cb) { cb.checked = true; });
            if (checkAll) checkAll.checked = true;
        });
    }

    if (deselectAll) {
        deselectAll.addEventListener('click', function() {
            checkboxes.forEach(function(cb) { cb.checked = false; });
            if (checkAll) checkAll.checked = false;
        });
    }
});
</script>
