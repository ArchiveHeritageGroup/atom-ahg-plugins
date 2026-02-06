<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'index']); ?>">Duplicate Detection</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'browse']); ?>">Browse</a></li>
                    <li class="breadcrumb-item active">Merge</li>
                </ol>
            </nav>
            <h1><i class="fas fa-compress-arrows-alt me-2"></i>Merge Duplicate Records</h1>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
    <?php endif; ?>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Warning:</strong> Merging records is permanent. The secondary record will be archived and its digital objects and child records transferred to the primary record.
    </div>

    <form method="post" id="mergeForm">
        <!-- Detection Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Detection Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Similarity Score:</strong>
                        <?php $score = $detection->similarity_score * 100; ?>
                        <span class="badge bg-<?php echo $score >= 90 ? 'danger' : ($score >= 75 ? 'warning' : 'info'); ?>">
                            <?php echo number_format($score, 1); ?>%
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Detection Method:</strong>
                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $detection->detection_method))); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Detected:</strong>
                        <?php echo $detection->detected_at ? date('M j, Y H:i', strtotime($detection->detected_at)) : '-'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Select Primary Record -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-star me-2"></i>Step 1: Select Primary Record</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">The primary record will be kept. The secondary record's data will be merged into it.</p>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card h-100 border-2 primary-option" id="optionA">
                            <div class="card-header bg-light">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="primary_id"
                                           value="<?php echo $recordA->id; ?>" id="primaryA" checked>
                                    <label class="form-check-label fw-bold" for="primaryA">
                                        Record A (Keep This)
                                    </label>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5><?php echo htmlspecialchars($recordA->title ?? 'Untitled'); ?></h5>
                                <p class="text-muted mb-2">
                                    <strong>Identifier:</strong> <?php echo htmlspecialchars($recordA->identifier ?? 'N/A'); ?>
                                </p>
                                <p class="text-muted mb-2">
                                    <strong>Level:</strong> <?php echo htmlspecialchars($recordA->level_of_description ?? 'N/A'); ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <strong>Repository:</strong> <?php echo htmlspecialchars($recordA->repository_name ?? 'N/A'); ?>
                                </p>
                            </div>
                            <div class="card-footer bg-light">
                                <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $recordA->slug]); ?>"
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-1"></i> View Record
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100 border-2 primary-option" id="optionB">
                            <div class="card-header bg-light">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="primary_id"
                                           value="<?php echo $recordB->id; ?>" id="primaryB">
                                    <label class="form-check-label fw-bold" for="primaryB">
                                        Record B (Keep This)
                                    </label>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5><?php echo htmlspecialchars($recordB->title ?? 'Untitled'); ?></h5>
                                <p class="text-muted mb-2">
                                    <strong>Identifier:</strong> <?php echo htmlspecialchars($recordB->identifier ?? 'N/A'); ?>
                                </p>
                                <p class="text-muted mb-2">
                                    <strong>Level:</strong> <?php echo htmlspecialchars($recordB->level_of_description ?? 'N/A'); ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <strong>Repository:</strong> <?php echo htmlspecialchars($recordB->repository_name ?? 'N/A'); ?>
                                </p>
                            </div>
                            <div class="card-footer bg-light">
                                <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $recordB->slug]); ?>"
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-1"></i> View Record
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- What Will Happen -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Step 2: Review Merge Actions</h5>
            </div>
            <div class="card-body">
                <p>The following actions will be performed:</p>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fas fa-file me-2 text-primary"></i>
                        Digital objects from the secondary record will be transferred to the primary record
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-sitemap me-2 text-primary"></i>
                        Child records from the secondary record will be moved under the primary record
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-link me-2 text-primary"></i>
                        The secondary record's slug will redirect to the primary record
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-archive me-2 text-primary"></i>
                        The secondary record will be archived (not deleted) for audit purposes
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-history me-2 text-primary"></i>
                        A merge log entry will be created for compliance and auditing
                    </li>
                </ul>
            </div>
        </div>

        <!-- Confirmation -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Step 3: Confirm Merge</h5>
            </div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmMerge" required>
                    <label class="form-check-label" for="confirmMerge">
                        I understand that this action is permanent and cannot be undone.
                    </label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-danger" id="mergeBtn" disabled>
                        <i class="fas fa-compress-arrows-alt me-1"></i> Merge Records
                    </button>
                    <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'compare', 'id' => $detection->id]); ?>"
                       class="btn btn-outline-secondary">
                        <i class="fas fa-columns me-1"></i> Back to Compare
                    </a>
                    <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'browse']); ?>"
                       class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.primary-option {
    cursor: pointer;
    transition: all 0.2s;
}
.primary-option:hover {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.primary-option.selected {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
}
</style>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var confirmCheck = document.getElementById('confirmMerge');
    var mergeBtn = document.getElementById('mergeBtn');
    var optionA = document.getElementById('optionA');
    var optionB = document.getElementById('optionB');
    var radioA = document.getElementById('primaryA');
    var radioB = document.getElementById('primaryB');

    // Enable/disable merge button based on confirmation
    confirmCheck.addEventListener('change', function() {
        mergeBtn.disabled = !this.checked;
    });

    // Visual selection for cards
    function updateSelection() {
        optionA.classList.toggle('selected', radioA.checked);
        optionB.classList.toggle('selected', radioB.checked);
    }

    radioA.addEventListener('change', updateSelection);
    radioB.addEventListener('change', updateSelection);

    optionA.addEventListener('click', function(e) {
        if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
            radioA.checked = true;
            updateSelection();
        }
    });

    optionB.addEventListener('click', function(e) {
        if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
            radioB.checked = true;
            updateSelection();
        }
    });

    // Initial state
    updateSelection();
});
</script>
