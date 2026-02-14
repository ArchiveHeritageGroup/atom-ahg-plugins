<?php $n = sfConfig::get('csp_nonce', ''); $nattr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<div class="container-fluid py-3">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researcher', 'action' => 'dashboard']) ?>">Researcher</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $submission->id]) ?>"><?php echo htmlspecialchars($submission->title) ?></a></li>
      <li class="breadcrumb-item active">Edit</li>
    </ol>
  </nav>

  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="card">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Edit Submission</h5>
        </div>
        <div class="card-body">

          <form method="post">

            <div class="mb-3">
              <label class="form-label fw-bold">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($submission->title) ?>">
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Description</label>
              <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($submission->description ?? '') ?></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Target Repository</label>
              <select name="repository_id" class="form-select">
                <option value="">-- Select repository --</option>
                <?php foreach ($repositories as $repo): ?>
                  <option value="<?php echo $repo->id ?>" <?php echo (int) $submission->repository_id === (int) $repo->id ? 'selected' : '' ?>>
                    <?php echo htmlspecialchars($repo->name) ?>
                  </option>
                <?php endforeach ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Parent Record (optional)</label>
              <input type="text" name="parent_object_id" class="form-control"
                     value="<?php echo $submission->parent_object_id ?? '' ?>"
                     placeholder="AtoM object ID (leave blank for root level)">
            </div>

            <hr>

            <div class="d-flex justify-content-between">
              <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $submission->id]) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Cancel
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>Save Changes
              </button>
            </div>

          </form>

        </div>
      </div>

    </div>
  </div>

</div>
