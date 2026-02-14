<?php $n = sfConfig::get('csp_nonce', ''); $nattr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<div class="container-fluid py-3">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researcher', 'action' => 'dashboard']) ?>">Researcher</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $submission->id]) ?>"><?php echo htmlspecialchars($submission->title) ?></a></li>
      <li class="breadcrumb-item active">Publish</li>
    </ol>
  </nav>

  <div class="row justify-content-center">
    <div class="col-lg-10">

      <?php if (!$publishResult): ?>
        <!-- Confirmation -->
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-globe me-2"></i>Publish to AtoM to Heratio</h5>
          </div>
          <div class="card-body">
            <div class="alert alert-warning">
              <i class="bi bi-exclamation-triangle me-2"></i>
              <strong>This action will create permanent records in AtoM.</strong>
              Information objects, digital objects, access points, and related records will be created
              from the items in this submission. This cannot be easily undone.
            </div>

            <dl class="row">
              <dt class="col-3">Submission</dt>
              <dd class="col-9"><?php echo htmlspecialchars($submission->title) ?></dd>
              <dt class="col-3">Items</dt>
              <dd class="col-9"><?php echo $submission->total_items ?> items, <?php echo $submission->total_files ?> files</dd>
              <dt class="col-3">Status</dt>
              <dd class="col-9"><span class="badge bg-success">Approved</span></dd>
            </dl>

            <form method="post">
              <div class="d-flex justify-content-between">
                <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $submission->id]) ?>"
                   class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-left me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary" onclick="return confirm('Publish this submission? Records will be created in AtoM.')">
                  <i class="bi bi-globe me-1"></i>Publish Now
                </button>
              </div>
            </form>
          </div>
        </div>

      <?php else: ?>
        <!-- Results -->
        <div class="card border-success mb-4">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Publication Complete</h5>
          </div>
          <div class="card-body">

            <!-- Summary -->
            <div class="row g-3 mb-4">
              <div class="col-md-3 text-center">
                <h3 class="mb-0 text-success"><?php echo count($publishResult['created_objects']) ?></h3>
                <small class="text-muted">Records Created</small>
              </div>
              <div class="col-md-3 text-center">
                <h3 class="mb-0 text-primary"><?php echo count($publishResult['created_actors']) ?></h3>
                <small class="text-muted">Creators Created</small>
              </div>
              <div class="col-md-3 text-center">
                <h3 class="mb-0 text-info"><?php echo count($publishResult['created_repos']) ?></h3>
                <small class="text-muted">Repositories Created</small>
              </div>
              <div class="col-md-3 text-center">
                <h3 class="mb-0 text-<?php echo count($publishResult['errors']) > 0 ? 'danger' : 'muted' ?>"><?php echo count($publishResult['errors']) ?></h3>
                <small class="text-muted">Errors</small>
              </div>
            </div>

            <!-- Created Records -->
            <?php if (!empty($publishResult['created_objects'])): ?>
            <div class="card mb-3">
              <div class="card-header"><h6 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Created Records</h6></div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Title</th>
                        <th>Level</th>
                        <th>AtoM Link</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($publishResult['created_objects'] as $obj): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($obj['title']) ?></td>
                        <td><small><?php echo $obj['level'] ?? '-' ?></small></td>
                        <td>
                          <?php if (!empty($obj['slug'])): ?>
                            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $obj['slug']]) ?>" target="_blank">
                              <i class="bi bi-box-arrow-up-right me-1"></i><?php echo $obj['slug'] ?>
                            </a>
                          <?php else: ?>
                            <small class="text-muted">ID: <?php echo $obj['object_id'] ?></small>
                          <?php endif ?>
                        </td>
                      </tr>
                      <?php endforeach ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <?php endif ?>

            <!-- Created Creators -->
            <?php if (!empty($publishResult['created_actors'])): ?>
            <div class="card mb-3">
              <div class="card-header"><h6 class="mb-0"><i class="bi bi-person me-2"></i>Created Creators</h6></div>
              <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                  <?php foreach ($publishResult['created_actors'] as $actor): ?>
                    <li class="list-group-item"><?php echo htmlspecialchars($actor['title']) ?> (ID: <?php echo $actor['object_id'] ?>)</li>
                  <?php endforeach ?>
                </ul>
              </div>
            </div>
            <?php endif ?>

            <!-- Created Repositories -->
            <?php if (!empty($publishResult['created_repos'])): ?>
            <div class="card mb-3">
              <div class="card-header"><h6 class="mb-0"><i class="bi bi-building me-2"></i>Created Repositories</h6></div>
              <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                  <?php foreach ($publishResult['created_repos'] as $repo): ?>
                    <li class="list-group-item"><?php echo htmlspecialchars($repo['title']) ?> (ID: <?php echo $repo['object_id'] ?>)</li>
                  <?php endforeach ?>
                </ul>
              </div>
            </div>
            <?php endif ?>

            <!-- Errors -->
            <?php if (!empty($publishResult['errors'])): ?>
            <div class="card border-danger">
              <div class="card-header bg-danger text-white"><h6 class="mb-0"><i class="bi bi-exclamation-circle me-2"></i>Errors</h6></div>
              <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                  <?php foreach ($publishResult['errors'] as $err): ?>
                    <li class="list-group-item list-group-item-danger small"><?php echo htmlspecialchars($err) ?></li>
                  <?php endforeach ?>
                </ul>
              </div>
            </div>
            <?php endif ?>

            <hr>

            <div class="d-flex justify-content-between">
              <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
              </a>
              <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $submission->id]) ?>"
                 class="btn btn-primary">
                <i class="bi bi-eye me-1"></i>View Submission
              </a>
            </div>

          </div>
        </div>
      <?php endif ?>

    </div>
  </div>

</div>
