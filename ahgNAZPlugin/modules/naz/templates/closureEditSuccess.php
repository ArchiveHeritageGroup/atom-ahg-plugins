<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures']); ?>">Closures</a></li>
                    <li class="breadcrumb-item active">Edit Closure</li>
                </ol>
            </nav>
            <h1><i class="fas fa-lock me-2"></i>Edit Closure Period</h1>
        </div>
    </div>

    <form method="post" class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Closure Details</h5></div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Record</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($closure->record_title ?? 'Record #' . $closure->information_object_id); ?></dd>
                    </dl>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Closure Type</label>
                            <select name="closure_type" class="form-select">
                                <option value="standard" <?php echo $closure->closure_type === 'standard' ? 'selected' : ''; ?>>Standard</option>
                                <option value="extended" <?php echo $closure->closure_type === 'extended' ? 'selected' : ''; ?>>Extended</option>
                                <option value="indefinite" <?php echo $closure->closure_type === 'indefinite' ? 'selected' : ''; ?>>Indefinite</option>
                                <option value="ministerial" <?php echo $closure->closure_type === 'ministerial' ? 'selected' : ''; ?>>Ministerial</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?php echo $closure->status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="expired" <?php echo $closure->status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                <option value="extended" <?php echo $closure->status === 'extended' ? 'selected' : ''; ?>>Extended</option>
                                <option value="released" <?php echo $closure->status === 'released' ? 'selected' : ''; ?>>Released</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $closure->end_date ?? ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Review Date</label>
                            <input type="date" name="review_date" class="form-control" value="<?php echo $closure->review_date ?? ''; ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Closure Reason</label>
                            <textarea name="closure_reason" class="form-control" rows="3"><?php echo htmlspecialchars($closure->closure_reason ?? ''); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Release Notes</label>
                            <textarea name="release_notes" class="form-control" rows="2"><?php echo htmlspecialchars($closure->release_notes ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Save Changes</button>
                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures']); ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
