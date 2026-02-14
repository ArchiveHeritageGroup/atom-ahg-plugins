<?php $n = sfConfig::get('csp_nonce', ''); $nattr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<div class="container-fluid py-3">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researcher', 'action' => 'dashboard']) ?>">Researcher</a></li>
      <li class="breadcrumb-item active">New Submission</li>
    </ol>
  </nav>

  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="card">
        <div class="card-header bg-success text-white">
          <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create New Submission</h5>
        </div>
        <div class="card-body">

          <p class="text-muted mb-4">
            Create a submission package to upload and describe a collection. After adding items and files,
            submit for archivist review.
          </p>

          <form method="post">

            <div class="mb-3">
              <label class="form-label fw-bold">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" required placeholder="e.g., Smith Family Papers 1950-1975">
              <small class="text-muted">A descriptive title for this submission package.</small>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Description</label>
              <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the collection being submitted..."></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Target Repository</label>
              <select name="repository_id" class="form-select">
                <option value="">-- Select repository --</option>
                <?php foreach ($repositories as $repo): ?>
                  <option value="<?php echo $repo->id ?>"><?php echo htmlspecialchars($repo->name) ?></option>
                <?php endforeach ?>
              </select>
              <small class="text-muted">The archival institution where this collection will be placed.</small>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Parent Record (optional)</label>
              <input type="text" name="parent_object_id" class="form-control" placeholder="AtoM object ID (leave blank for root level)">
              <small class="text-muted">If this submission should be placed under an existing record, enter its ID.</small>
            </div>

            <hr>

            <div class="d-flex justify-content-between">
              <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Cancel
              </a>
              <button type="submit" class="btn btn-success">
                <i class="bi bi-check-lg me-1"></i>Create Submission
              </button>
            </div>

          </form>

        </div>
      </div>

    </div>
  </div>

</div>
