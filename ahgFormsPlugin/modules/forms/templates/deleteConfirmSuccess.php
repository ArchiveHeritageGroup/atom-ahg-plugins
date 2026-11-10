<?php
// Confirmation before a delete. The delete links used to delete on a plain GET, so a
// prefetch, a crawler or a link in an email could remove a template or assignment.
$what = isset($sf_data) ? $sf_data->getRaw('what') : $what;
$detail = isset($sf_data) ? $sf_data->getRaw('detail') : $detail;
$back = isset($sf_data) ? $sf_data->getRaw('back') : $back;
?>
<div class="container-fluid">
    <h1><i class="fas fa-trash me-2"></i>Delete <?php echo htmlspecialchars($what) ?></h1>
    <div class="card mt-3">
        <div class="card-body">
            <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($detail) ?></strong>?</p>
            <?php if ('form template' === $what): ?>
                <p class="text-muted small">Its fields, field mappings, drafts and assignments are deleted with it. Records already described with the form are not changed.</p>
            <?php endif ?>
            <form method="post" class="d-flex gap-2">
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Delete</button>
                <a href="<?php echo url_for($back) ?>" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
