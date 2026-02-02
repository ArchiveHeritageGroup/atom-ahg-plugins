<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-link me-2"></i>Mint DOI</h1>
            <p class="text-muted">Create a DOI for this record</p>
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

    <?php if ($existingDoi): ?>
        <!-- Already has DOI -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            This record already has a DOI:
            <a href="https://doi.org/<?php echo htmlspecialchars($existingDoi->doi) ?>" target="_blank" class="text-monospace">
                <?php echo htmlspecialchars($existingDoi->doi) ?>
            </a>
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'view', 'id' => $existingDoi->id]) ?>" class="btn btn-sm btn-outline-primary ms-3">
                View DOI Details
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <!-- Record Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Record Information</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Title</dt>
                            <dd class="col-sm-9"><?php echo htmlspecialchars($record->title ?? 'Untitled') ?></dd>

                            <dt class="col-sm-3">Object ID</dt>
                            <dd class="col-sm-9"><?php echo $record->id ?></dd>

                            <?php if ($record->identifier): ?>
                                <dt class="col-sm-3">Identifier</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($record->identifier) ?></dd>
                            <?php endif ?>
                        </dl>
                    </div>
                </div>

                <!-- Mint Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Mint DOI</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo url_for(['module' => 'doi', 'action' => 'mint', 'id' => $record->id]) ?>">
                            <div class="mb-3">
                                <label class="form-label">Initial State</label>
                                <select name="state" class="form-select">
                                    <option value="findable">Findable (Recommended)</option>
                                    <option value="registered">Registered</option>
                                    <option value="draft">Draft</option>
                                </select>
                                <div class="form-text">
                                    <strong>Findable:</strong> DOI is publicly discoverable and indexed in DataCite search<br>
                                    <strong>Registered:</strong> DOI resolves but is not indexed in search<br>
                                    <strong>Draft:</strong> DOI is reserved but not yet active
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-link me-1"></i> Mint DOI Now
                                </button>
                                <a href="<?php echo url_for(['module' => 'doi', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Help -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">About DOIs</h5>
                    </div>
                    <div class="card-body">
                        <p class="small">
                            A DOI (Digital Object Identifier) is a persistent identifier used to
                            uniquely identify objects. DOIs are widely used for academic and
                            archival resources.
                        </p>
                        <p class="small">
                            Once minted, a DOI:
                        </p>
                        <ul class="small">
                            <li>Provides a permanent link to the resource</li>
                            <li>Can be cited in publications</li>
                            <li>Is indexed in DataCite and other services</li>
                            <li>Cannot be deleted (only deactivated)</li>
                        </ul>
                        <p class="small text-muted mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            DOIs are minted through DataCite. Ensure your configuration is correct before minting.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
</div>
