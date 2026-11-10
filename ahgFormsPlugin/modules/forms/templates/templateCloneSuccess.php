<?php
// Clone asked for this page and it did not exist: the Clone button gave a 500 error.
$source = isset($sf_data) ? $sf_data->getRaw('template') : $template;
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-copy me-2"></i>Clone Form Template</h1>
            <p class="text-muted">Copy <strong><?php echo htmlspecialchars($source->name) ?></strong> with all its fields and their mappings. The copy can be changed freely; the original stays as it is.</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templates']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label" for="clone-name">Name of the copy *</label>
                    <input type="text" id="clone-name" name="name" class="form-control" required maxlength="255" value="<?php echo htmlspecialchars('Copy of ' . $source->name) ?>">
                </div>
                <p class="text-muted small mb-3"><?php echo count($source->fields ?? []) ?> fields will be copied. The copy opens in the form builder.</p>
                <button type="submit" class="btn btn-primary"><i class="fas fa-copy me-1"></i> Clone template</button>
            </form>
        </div>
    </div>
</div>
