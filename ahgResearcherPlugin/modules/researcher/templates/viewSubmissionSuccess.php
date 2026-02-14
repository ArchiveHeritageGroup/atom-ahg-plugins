<?php $n = sfConfig::get('csp_nonce', ''); $nattr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<div class="container-fluid py-3">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researcher', 'action' => 'dashboard']) ?>">Researcher</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researcher', 'action' => 'submissions']) ?>">Submissions</a></li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars($submission->title) ?></li>
    </ol>
  </nav>

  <!-- Flash messages -->
  <?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('notice') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif ?>
  <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif ?>

  <?php
    $statusColors = [
      'draft' => 'secondary', 'submitted' => 'warning', 'under_review' => 'info',
      'approved' => 'success', 'published' => 'primary', 'returned' => 'danger', 'rejected' => 'dark',
    ];
    $color = $statusColors[$submission->status] ?? 'secondary';
  ?>

  <div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">

      <!-- Header -->
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h4 class="mb-1"><?php echo htmlspecialchars($submission->title) ?></h4>
          <span class="badge bg-<?php echo $color ?> me-2"><?php echo ucfirst(str_replace('_', ' ', $submission->status)) ?></span>
          <span class="badge bg-<?php echo $submission->source_type === 'offline' ? 'secondary' : 'primary' ?>">
            <?php echo ucfirst($submission->source_type) ?>
          </span>
        </div>
        <div>
          <?php if ($submission->status === 'draft'): ?>
            <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'editSubmission', 'id' => $submission->id]) ?>"
               class="btn btn-outline-primary btn-sm me-1">
              <i class="bi bi-pencil me-1"></i>Edit
            </a>
          <?php endif ?>
          <?php if (in_array($submission->status, ['draft', 'returned'])): ?>
            <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'addItem', 'id' => $submission->id]) ?>"
               class="btn btn-success btn-sm">
              <i class="bi bi-plus-lg me-1"></i>Add Item
            </a>
          <?php endif ?>
        </div>
      </div>

      <?php if ($submission->description): ?>
        <div class="card mb-3">
          <div class="card-body">
            <p class="mb-0"><?php echo nl2br(htmlspecialchars($submission->description)) ?></p>
          </div>
        </div>
      <?php endif ?>

      <!-- Return/Reject comments -->
      <?php if ($submission->status === 'returned' && $submission->return_comment): ?>
        <div class="alert alert-warning">
          <strong><i class="bi bi-exclamation-triangle me-1"></i>Returned by reviewer:</strong>
          <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($submission->return_comment)) ?></p>
        </div>
      <?php endif ?>
      <?php if ($submission->status === 'rejected' && $submission->reject_comment): ?>
        <div class="alert alert-danger">
          <strong><i class="bi bi-x-circle me-1"></i>Rejected:</strong>
          <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($submission->reject_comment)) ?></p>
        </div>
      <?php endif ?>

      <!-- Items Table -->
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Items (<?php echo count($items) ?>)</h6>
        </div>
        <div class="card-body p-0">
          <?php if (empty($items)): ?>
            <div class="text-center text-muted py-4">
              <i class="bi bi-inbox" style="font-size: 1.5rem;"></i>
              <p class="mt-2 mb-0">No items yet.
                <?php if (in_array($submission->status, ['draft', 'returned'])): ?>
                  <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'addItem', 'id' => $submission->id]) ?>">Add your first item</a>.
                <?php endif ?>
              </p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0 table-sm">
                <thead class="table-light">
                  <tr>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Level</th>
                    <th>Files</th>
                    <th>Access Points</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $item): ?>
                  <?php
                    $typeIcons = [
                      'description' => 'bi-file-earmark-text', 'note' => 'bi-sticky',
                      'creator' => 'bi-person', 'repository' => 'bi-building',
                    ];
                    $icon = $typeIcons[$item->item_type] ?? 'bi-file-earmark';
                    $indent = $item->parent_item_id ? 'ps-4' : '';
                    $itemFileCount = isset($files[$item->id]) ? count($files[$item->id]) : 0;

                    $accessPoints = [];
                    if ($item->subjects) { $accessPoints[] = '<span class="badge bg-info text-dark">S</span>'; }
                    if ($item->places) { $accessPoints[] = '<span class="badge bg-warning text-dark">P</span>'; }
                    if ($item->genres) { $accessPoints[] = '<span class="badge bg-secondary">G</span>'; }
                    if ($item->creators) { $accessPoints[] = '<span class="badge bg-primary">C</span>'; }
                  ?>
                  <tr>
                    <td><i class="bi <?php echo $icon ?> me-1"></i><?php echo ucfirst($item->item_type) ?></td>
                    <td class="<?php echo $indent ?>">
                      <?php if ($item->parent_item_id): ?><i class="bi bi-arrow-return-right me-1 text-muted"></i><?php endif ?>
                      <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'editItem', 'id' => $submission->id, 'itemId' => $item->id]) ?>">
                        <?php echo htmlspecialchars($item->title) ?>
                      </a>
                      <?php if ($item->identifier): ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($item->identifier) ?></small>
                      <?php endif ?>
                    </td>
                    <td><small><?php echo $item->level_of_description ?></small></td>
                    <td>
                      <?php if ($itemFileCount > 0): ?>
                        <span class="badge bg-outline-secondary"><?php echo $itemFileCount ?> file<?php echo $itemFileCount > 1 ? 's' : '' ?></span>
                      <?php else: ?>
                        <small class="text-muted">-</small>
                      <?php endif ?>
                    </td>
                    <td><?php echo implode(' ', $accessPoints) ?: '<small class="text-muted">-</small>' ?></td>
                    <td class="text-end">
                      <?php if (in_array($submission->status, ['draft', 'returned'])): ?>
                        <form method="post" action="<?php echo url_for(['module' => 'researcher', 'action' => 'deleteItem', 'id' => $submission->id, 'itemId' => $item->id]) ?>"
                              style="display:inline" onsubmit="return confirm('Delete this item?')">
                          <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      <?php endif ?>
                    </td>
                  </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            </div>
          <?php endif ?>
        </div>
      </div>

      <!-- Review History -->
      <?php if (!empty($reviews)): ?>
      <div class="card mb-3">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Review History</h6>
        </div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            <?php foreach ($reviews as $review): ?>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>
                  <?php
                    $actionIcons = [
                      'comment' => 'bi-chat', 'return' => 'bi-arrow-return-left',
                      'approve' => 'bi-check-circle', 'reject' => 'bi-x-circle', 'publish' => 'bi-globe',
                    ];
                    $aIcon = $actionIcons[$review->action] ?? 'bi-info-circle';
                  ?>
                  <i class="bi <?php echo $aIcon ?> me-1"></i>
                  <?php echo ucfirst($review->action) ?>
                  <small class="text-muted fw-normal ms-1">by <?php echo htmlspecialchars($review->reviewer_name ?? 'System') ?></small>
                </strong>
                <small class="text-muted"><?php echo date('d M Y H:i', strtotime($review->created_at)) ?></small>
              </div>
              <?php if ($review->comment): ?>
                <p class="mb-0 mt-1 text-muted small"><?php echo nl2br(htmlspecialchars($review->comment)) ?></p>
              <?php endif ?>
            </div>
            <?php endforeach ?>
          </div>
        </div>
      </div>
      <?php endif ?>

    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">

      <!-- Info Card -->
      <div class="card mb-3">
        <div class="card-header bg-secondary text-white">
          <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Submission Info</h6>
        </div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-5">ID</dt>
            <dd class="col-7">#<?php echo $submission->id ?></dd>

            <dt class="col-5">Source</dt>
            <dd class="col-7"><?php echo ucfirst($submission->source_type) ?></dd>

            <dt class="col-5">Repository</dt>
            <dd class="col-7"><?php echo htmlspecialchars($repositoryName ?? 'Not set') ?></dd>

            <?php if ($projectName): ?>
            <dt class="col-5">Project</dt>
            <dd class="col-7">
              <i class="bi bi-journal-text me-1"></i><?php echo htmlspecialchars($projectName) ?>
            </dd>
            <?php endif ?>

            <dt class="col-5">Items</dt>
            <dd class="col-7"><?php echo $submission->total_items ?></dd>

            <dt class="col-5">Files</dt>
            <dd class="col-7"><?php echo $submission->total_files ?></dd>

            <dt class="col-5">Total Size</dt>
            <dd class="col-7">
              <?php
                $size = $submission->total_file_size;
                if ($size > 1048576) { echo round($size / 1048576, 1) . ' MB'; }
                elseif ($size > 1024) { echo round($size / 1024, 1) . ' KB'; }
                else { echo $size . ' B'; }
              ?>
            </dd>

            <dt class="col-5">Created</dt>
            <dd class="col-7"><?php echo date('d M Y', strtotime($submission->created_at)) ?></dd>

            <?php if ($submission->submitted_at): ?>
            <dt class="col-5">Submitted</dt>
            <dd class="col-7"><?php echo date('d M Y', strtotime($submission->submitted_at)) ?></dd>
            <?php endif ?>

            <?php if ($submission->published_at): ?>
            <dt class="col-5">Published</dt>
            <dd class="col-7"><?php echo date('d M Y', strtotime($submission->published_at)) ?></dd>
            <?php endif ?>
          </dl>
        </div>
      </div>

      <!-- Actions Card -->
      <div class="card mb-3">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Actions</h6>
        </div>
        <div class="card-body d-grid gap-2">
          <?php if ($submission->status === 'draft'): ?>
            <form method="post" action="<?php echo url_for(['module' => 'researcher', 'action' => 'submit', 'id' => $submission->id]) ?>">
              <button type="submit" class="btn btn-warning w-100" <?php echo count($items) === 0 ? 'disabled' : '' ?>
                      onclick="return confirm('Submit this collection for archivist review?')">
                <i class="bi bi-send me-1"></i>Submit for Review
              </button>
            </form>
            <?php if (count($items) === 0): ?>
              <small class="text-muted text-center">Add at least one item before submitting.</small>
            <?php endif ?>
          <?php endif ?>

          <?php if ($submission->status === 'returned'): ?>
            <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'addItem', 'id' => $submission->id]) ?>"
               class="btn btn-outline-success">
              <i class="bi bi-plus-lg me-1"></i>Add More Items
            </a>
            <form method="post" action="<?php echo url_for(['module' => 'researcher', 'action' => 'resubmit', 'id' => $submission->id]) ?>">
              <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Resubmit for review?')">
                <i class="bi bi-send me-1"></i>Resubmit
              </button>
            </form>
          <?php endif ?>

          <?php if ($submission->status === 'approved' && $isAdmin): ?>
            <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'publish', 'id' => $submission->id]) ?>"
               class="btn btn-primary">
              <i class="bi bi-globe me-1"></i>Publish to AtoM
            </a>
          <?php endif ?>

          <?php if ($submission->status === 'published'): ?>
            <div class="alert alert-success mb-0">
              <i class="bi bi-check-circle me-1"></i>
              This submission has been published to AtoM to Heratio.
            </div>
          <?php endif ?>
        </div>
      </div>

      <!-- Access Points Legend -->
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-tags me-2"></i>Access Points Legend</h6>
        </div>
        <div class="card-body small">
          <span class="badge bg-info text-dark me-1">S</span> Subjects
          <span class="badge bg-warning text-dark me-1 ms-2">P</span> Places
          <span class="badge bg-secondary me-1 ms-2">G</span> Genre
          <span class="badge bg-primary me-1 ms-2">C</span> Creators
        </div>
      </div>

    </div>
  </div>

</div>
