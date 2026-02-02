<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-ban me-2"></i>Deactivate DOI</h1>
            <p class="text-muted">Create a tombstone for this DOI</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'view', 'id' => $doi->id]) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Warning:</strong> Deactivating a DOI will hide it from DataCite discovery. The DOI will still resolve
        but will be marked as deleted. This action can be reversed.
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">DOI Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">DOI</dt>
                        <dd class="col-sm-9">
                            <code><?php echo htmlspecialchars($doi->doi) ?></code>
                        </dd>

                        <dt class="col-sm-3">Record</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($doi->object_title ?? 'Untitled') ?></dd>

                        <dt class="col-sm-3">Current Status</dt>
                        <dd class="col-sm-9">
                            <span class="badge bg-<?php echo $doi->status === 'findable' ? 'success' : 'secondary' ?>">
                                <?php echo htmlspecialchars($doi->status) ?>
                            </span>
                        </dd>

                        <dt class="col-sm-3">Minted</dt>
                        <dd class="col-sm-9"><?php echo date('Y-m-d H:i', strtotime($doi->minted_at)) ?></dd>
                    </dl>

                    <hr>

                    <form method="post" action="<?php echo url_for(['module' => 'doi', 'action' => 'deactivate', 'id' => $doi->id]) ?>">
                        <div class="mb-3">
                            <label class="form-label">Reason for Deactivation</label>
                            <textarea name="reason" class="form-control" rows="3"
                                      placeholder="e.g., Record deleted, Duplicate entry, Error in metadata..."></textarea>
                            <div class="form-text">This will be recorded in the audit log</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-ban me-1"></i> Deactivate DOI
                            </button>
                            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'view', 'id' => $doi->id]) ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">What Happens?</h5>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li class="mb-2">The DOI will be marked as "deleted" in your system</li>
                        <li class="mb-2">DataCite will hide the DOI from search indexes</li>
                        <li class="mb-2">The DOI URL will still resolve (for citation integrity)</li>
                        <li class="mb-2">You can reactivate the DOI later if needed</li>
                        <li>The original metadata is preserved</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
