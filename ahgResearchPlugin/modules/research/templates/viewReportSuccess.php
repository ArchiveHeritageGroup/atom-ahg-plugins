<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1><i class="fas fa-file-alt text-primary me-2"></i><?php echo htmlspecialchars($report->title); ?></h1>
  <div class="d-flex gap-2">
    <div class="dropdown">
      <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
        <i class="fas fa-file-export me-1"></i><?php echo __('Export'); ?>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'exportReport', 'id' => $report->id, 'format' => 'pdf']); ?>"><i class="fas fa-file-pdf me-2 text-danger"></i><?php echo __('PDF'); ?></a></li>
        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'exportReport', 'id' => $report->id, 'format' => 'docx']); ?>"><i class="fas fa-file-word me-2 text-primary"></i><?php echo __('DOCX'); ?></a></li>
      </ul>
    </div>
  </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$sections = isset($report->sections) && is_array($report->sections) ? $report->sections : [];
$reviews = isset($reviews) && is_array($reviews) ? $reviews : (isset($reviews) && method_exists($reviews, 'getRawValue') ? $reviews->getRawValue() : (isset($reviews) && is_iterable($reviews) ? iterator_to_array($reviews) : []));
$collaborators = isset($collaborators) && is_array($collaborators) ? $collaborators : (isset($collaborators) && method_exists($collaborators, 'getRawValue') ? $collaborators->getRawValue() : (isset($collaborators) && is_iterable($collaborators) ? iterator_to_array($collaborators) : []));
?>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('success'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'reports']); ?>"><?php echo __('Reports'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo htmlspecialchars($report->title); ?></li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-8">
    <!-- Report Header -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="post" class="d-flex align-items-center gap-3 mb-2" id="reportHeaderForm">
          <input type="hidden" name="form_action" value="update_header">
          <div class="flex-grow-1">
            <label class="form-label small text-muted mb-0"><?php echo __('Status'); ?></label>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
              <?php foreach (['draft' => 'Draft', 'in_progress' => 'In Progress', 'review' => 'Review', 'completed' => 'Completed'] as $sKey => $sLabel): ?>
                <option value="<?php echo $sKey; ?>" <?php echo ($report->status ?? 'draft') === $sKey ? 'selected' : ''; ?>><?php echo __($sLabel); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label small text-muted mb-0"><?php echo __('Template'); ?></label>
            <span class="badge bg-<?php echo match($report->template_type ?? 'custom') {
              'research_summary' => 'primary', 'genealogical' => 'success', 'historical' => 'info',
              'source_analysis' => 'warning', 'finding_aid' => 'secondary', default => 'dark'
            }; ?> d-block mt-1"><?php echo ucwords(str_replace('_', ' ', $report->template_type ?? 'custom')); ?></span>
          </div>
        </form>
        <?php if (!empty($report->description)): ?>
          <p class="text-muted mb-0"><?php echo htmlspecialchars($report->description); ?></p>
        <?php endif; ?>
        <?php if (!empty($report->project_title)): ?>
          <small class="text-muted"><i class="fas fa-project-diagram me-1"></i><?php echo __('Project:'); ?> <?php echo htmlspecialchars($report->project_title); ?></small>
        <?php endif; ?>
      </div>
    </div>

    <!-- Report Sections -->
    <div id="reportSections">
      <?php if (!empty($sections)): ?>
        <?php foreach ($sections as $index => $section): ?>
        <div class="card mb-3 report-section" data-section-id="<?php echo $section->id; ?>">
          <div class="card-header d-flex justify-content-between align-items-center py-2">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $section->section_type ?? 'text')); ?></span>
              <strong><?php echo htmlspecialchars($section->title ?? __('Untitled Section')); ?></strong>
            </div>
            <div class="d-flex gap-1">
              <!-- Move Up/Down Buttons -->
              <?php if ($index > 0): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="form_action" value="move_section">
                <input type="hidden" name="section_id" value="<?php echo $section->id; ?>">
                <input type="hidden" name="direction" value="up">
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Move Up'); ?>"><i class="fas fa-arrow-up"></i></button>
              </form>
              <?php endif; ?>
              <?php if ($index < count($sections) - 1): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="form_action" value="move_section">
                <input type="hidden" name="section_id" value="<?php echo $section->id; ?>">
                <input type="hidden" name="direction" value="down">
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Move Down'); ?>"><i class="fas fa-arrow-down"></i></button>
              </form>
              <?php endif; ?>
              <button type="button" class="btn btn-sm btn-outline-primary edit-section-btn" data-section-id="<?php echo $section->id; ?>" title="<?php echo __('Edit'); ?>"><i class="fas fa-edit"></i></button>
              <form method="post" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this section?'); ?>');">
                <input type="hidden" name="form_action" value="delete_section">
                <input type="hidden" name="section_id" value="<?php echo $section->id; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Delete'); ?>"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </div>
          <div class="card-body">
            <!-- Display Mode -->
            <div class="section-display" id="display-<?php echo $section->id; ?>">
              <?php if (($section->content_format ?? 'html') === 'html' && !empty($section->content)): ?>
                <?php echo $section->content; ?>
              <?php elseif (!empty($section->content)): ?>
                <p><?php echo nl2br(htmlspecialchars($section->content)); ?></p>
              <?php else: ?>
                <p class="text-muted fst-italic"><?php echo __('Click edit to add content to this section.'); ?></p>
              <?php endif; ?>
            </div>
            <!-- Edit Mode (hidden by default) -->
            <div class="section-edit d-none" id="edit-<?php echo $section->id; ?>">
              <form method="post" class="section-edit-form">
                <input type="hidden" name="form_action" value="update_section">
                <input type="hidden" name="section_id" value="<?php echo $section->id; ?>">
                <input type="hidden" name="content" class="section-content-hidden" value="">
                <div class="mb-2">
                  <input type="text" name="title" class="form-control form-control-sm" value="<?php echo htmlspecialchars($section->title ?? ''); ?>" placeholder="<?php echo __('Section title...'); ?>">
                </div>
                <div class="section-editor" data-section-id="<?php echo $section->id; ?>" style="height: 200px;"></div>
                <div class="mt-2 d-flex gap-2">
                  <button type="submit" class="btn btn-sm btn-primary save-section-btn"><i class="fas fa-save me-1"></i><?php echo __('Save'); ?></button>
                  <button type="button" class="btn btn-sm btn-secondary cancel-edit-btn" data-section-id="<?php echo $section->id; ?>"><?php echo __('Cancel'); ?></button>
                </div>
              </form>
            </div>
          </div>

          <!-- Comments per section -->
          <?php
          $sectionComments = isset($commentsBySection[$section->id]) ? $commentsBySection[$section->id] : [];
          ?>
          <?php if (!empty($sectionComments) || true): ?>
          <div class="card-footer bg-transparent">
            <details>
              <summary class="text-muted small" style="cursor: pointer;">
                <i class="fas fa-comments me-1"></i><?php echo __('Comments'); ?> (<?php echo count($sectionComments); ?>)
              </summary>
              <div class="mt-2">
                <?php foreach ($sectionComments as $comment): ?>
                <div class="d-flex mb-2 ms-2">
                  <div class="flex-grow-1">
                    <strong class="small"><?php echo htmlspecialchars(($comment->first_name ?? '') . ' ' . ($comment->last_name ?? '')); ?></strong>
                    <small class="text-muted ms-2"><?php echo date('M j, H:i', strtotime($comment->created_at)); ?></small>
                    <?php if (!empty($comment->is_resolved)): ?>
                      <span class="badge bg-success ms-1"><?php echo __('Resolved'); ?></span>
                    <?php endif; ?>
                    <p class="mb-0 small"><?php echo htmlspecialchars($comment->content ?? ''); ?></p>
                  </div>
                </div>
                <?php endforeach; ?>
                <form method="post" class="mt-2">
                  <input type="hidden" name="form_action" value="add_comment">
                  <input type="hidden" name="section_id" value="<?php echo $section->id; ?>">
                  <div class="input-group input-group-sm">
                    <input type="text" name="comment_content" class="form-control" placeholder="<?php echo __('Add a comment...'); ?>">
                    <button type="submit" class="btn btn-outline-primary"><i class="fas fa-paper-plane"></i></button>
                  </div>
                </form>
              </div>
            </details>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="text-center py-4 text-muted">
          <i class="fas fa-file fa-2x mb-2 opacity-50"></i>
          <p><?php echo __('No sections yet. Add a section to start building your report.'); ?></p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Add Section -->
    <div class="card border-dashed mb-4">
      <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
          <input type="hidden" name="form_action" value="add_section">
          <div class="col-md-4">
            <label class="form-label small"><?php echo __('Section Type'); ?></label>
            <select name="section_type" class="form-select form-select-sm">
              <option value="text"><?php echo __('Text'); ?></option>
              <option value="heading"><?php echo __('Heading'); ?></option>
              <option value="title_page"><?php echo __('Title Page'); ?></option>
              <option value="toc"><?php echo __('Table of Contents'); ?></option>
              <option value="bibliography"><?php echo __('Bibliography'); ?></option>
              <option value="collection_list"><?php echo __('Collection List'); ?></option>
              <option value="annotation_list"><?php echo __('Annotation List'); ?></option>
              <option value="timeline"><?php echo __('Timeline'); ?></option>
              <option value="custom"><?php echo __('Custom'); ?></option>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label small"><?php echo __('Title'); ?></label>
            <input type="text" name="title" class="form-control form-control-sm" placeholder="<?php echo __('Section title...'); ?>">
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-plus me-1"></i><?php echo __('Add Section'); ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <!-- Report Info -->
    <div class="card mb-4">
      <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Report Info'); ?></h6></div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Author'); ?></span>
          <span><?php echo htmlspecialchars(($report->first_name ?? '') . ' ' . ($report->last_name ?? '')); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Created'); ?></span>
          <span><?php echo date('M j, Y', strtotime($report->created_at)); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Updated'); ?></span>
          <span><?php echo date('M j, Y H:i', strtotime($report->updated_at)); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Sections'); ?></span>
          <span class="badge bg-primary"><?php echo count($sections); ?></span>
        </li>
      </ul>
    </div>

    <!-- Peer Review -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-user-check me-2"></i><?php echo __('Peer Review'); ?></h6>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#requestReviewModal">
          <i class="fas fa-plus"></i>
        </button>
      </div>
      <?php if (!empty($reviews)): ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($reviews as $review): ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong class="small"><?php echo htmlspecialchars(($review->reviewer_first_name ?? '') . ' ' . ($review->reviewer_last_name ?? '')); ?></strong>
                <br><small class="text-muted"><?php echo __('Requested'); ?> <?php echo date('M j, Y', strtotime($review->requested_at)); ?></small>
              </div>
              <span class="badge bg-<?php echo match($review->status ?? 'pending') {
                'pending' => 'warning', 'in_progress' => 'primary', 'completed' => 'success', 'declined' => 'danger', default => 'secondary'
              }; ?>"><?php echo ucfirst($review->status ?? 'pending'); ?></span>
            </div>
            <?php if (!empty($review->feedback)): ?>
              <small class="text-muted d-block mt-1"><?php echo htmlspecialchars(mb_substr($review->feedback, 0, 100)); ?></small>
            <?php endif; ?>
            <?php if (!empty($review->rating)): ?>
              <small class="text-warning"><?php echo str_repeat('<i class="fas fa-star"></i>', (int) $review->rating); ?><?php echo str_repeat('<i class="far fa-star"></i>', 5 - (int) $review->rating); ?></small>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="card-body text-muted small"><?php echo __('No reviews requested yet.'); ?></div>
      <?php endif; ?>
    </div>

    <!-- Delete Report -->
    <div class="card border-danger">
      <div class="card-body">
        <h6 class="card-title text-danger"><i class="fas fa-trash me-1"></i><?php echo __('Delete Report'); ?></h6>
        <p class="small text-muted"><?php echo __('This will permanently delete the report and all its sections.'); ?></p>
        <form method="post" onsubmit="return confirm('<?php echo __('Are you sure? This cannot be undone.'); ?>');">
          <input type="hidden" name="form_action" value="delete_report">
          <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash me-1"></i><?php echo __('Delete Report'); ?></button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Request Review Modal -->
<div class="modal fade" id="requestReviewModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="form_action" value="request_review">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-check me-2"></i><?php echo __('Request Peer Review'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Reviewer'); ?></label>
            <select name="reviewer_id" class="form-select" required>
              <option value=""><?php echo __('Select a collaborator...'); ?></option>
              <?php foreach ($collaborators as $collab): ?>
                <option value="<?php echo $collab->id; ?>"><?php echo htmlspecialchars(($collab->first_name ?? '') . ' ' . ($collab->last_name ?? '')); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i><?php echo __('Send Request'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Quill.js CSS -->
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.section-editor { background: #fff; color: #212529; }
.section-editor .ql-editor { min-height: 150px; }
.ql-toolbar.ql-snow { background: #f8f9fa; border-color: #dee2e6; }
.ql-container.ql-snow { border-color: #dee2e6; }
.border-dashed { border-style: dashed !important; }
.modal-content { background-color: #fff !important; color: #212529 !important; }
.modal-body .form-label { color: #212529 !important; }
.modal-body .form-control, .modal-body .form-select { background-color: #fff !important; color: #212529 !important; }
</style>

<!-- Quill.js -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var quillInstances = {};

  // Edit section button
  document.querySelectorAll('.edit-section-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var sectionId = this.dataset.sectionId;
      var displayEl = document.getElementById('display-' + sectionId);
      var editEl = document.getElementById('edit-' + sectionId);

      displayEl.classList.add('d-none');
      editEl.classList.remove('d-none');

      // Initialize Quill if not yet
      if (!quillInstances[sectionId]) {
        var editorEl = editEl.querySelector('.section-editor');
        quillInstances[sectionId] = new Quill(editorEl, {
          theme: 'snow',
          modules: {
            toolbar: [
              [{ 'header': [1, 2, 3, false] }],
              ['bold', 'italic', 'underline'],
              [{ 'list': 'ordered' }, { 'list': 'bullet' }],
              ['blockquote', 'link'],
              ['clean']
            ]
          }
        });
        // Load existing content from display
        var existingHtml = displayEl.innerHTML.trim();
        if (existingHtml && !existingHtml.includes('fst-italic')) {
          quillInstances[sectionId].root.innerHTML = existingHtml;
        }
      }
    });
  });

  // Cancel edit button
  document.querySelectorAll('.cancel-edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var sectionId = this.dataset.sectionId;
      document.getElementById('display-' + sectionId).classList.remove('d-none');
      document.getElementById('edit-' + sectionId).classList.add('d-none');
    });
  });

  // Save section - sync Quill content
  document.querySelectorAll('.section-edit-form').forEach(function(form) {
    form.addEventListener('submit', function() {
      var sectionId = form.querySelector('[name="section_id"]').value;
      if (quillInstances[sectionId]) {
        form.querySelector('.section-content-hidden').value = quillInstances[sectionId].root.innerHTML;
      }
    });
  });
});
</script>
<?php end_slot() ?>
