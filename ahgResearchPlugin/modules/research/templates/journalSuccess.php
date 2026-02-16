<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1><i class="fas fa-book text-primary me-2"></i><?php echo __('Research Journal'); ?></h1>
  <div class="d-flex gap-2">
    <div class="dropdown">
      <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
        <i class="fas fa-file-export me-1"></i><?php echo __('Export Journal'); ?>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'exportJournal', 'format' => 'pdf']); ?>"><i class="fas fa-file-pdf me-2 text-danger"></i><?php echo __('PDF'); ?></a></li>
        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'exportJournal', 'format' => 'docx']); ?>"><i class="fas fa-file-word me-2 text-primary"></i><?php echo __('DOCX'); ?></a></li>
      </ul>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newEntryModal">
      <i class="fas fa-plus me-1"></i><?php echo __('New Entry'); ?>
    </button>
  </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$entries = isset($entries) && is_array($entries) ? $entries : (isset($entries) && method_exists($entries, 'getRawValue') ? $entries->getRawValue() : (isset($entries) && is_iterable($entries) ? iterator_to_array($entries) : []));
$projects = isset($projects) && is_array($projects) ? $projects : (isset($projects) && method_exists($projects, 'getRawValue') ? $projects->getRawValue() : (isset($projects) && is_iterable($projects) ? iterator_to_array($projects) : []));
?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Journal'); ?></li>
  </ol>
</nav>

<!-- Filter Bar -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Project'); ?></label>
        <select name="project_id" class="form-select form-select-sm">
          <option value=""><?php echo __('All Projects'); ?></option>
          <?php foreach ($projects as $project): ?>
            <option value="<?php echo $project->id; ?>" <?php echo ($sf_request->getParameter('project_id') == $project->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($project->title); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small"><?php echo __('Entry Type'); ?></label>
        <select name="entry_type" class="form-select form-select-sm">
          <option value=""><?php echo __('All Types'); ?></option>
          <?php foreach (['manual' => 'Manual', 'note' => 'Note', 'observation' => 'Observation', 'finding' => 'Finding', 'question' => 'Question', 'auto' => 'Auto-logged'] as $typeKey => $typeLabel): ?>
            <option value="<?php echo $typeKey; ?>" <?php echo ($sf_request->getParameter('entry_type') === $typeKey) ? 'selected' : ''; ?>><?php echo __($typeLabel); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small"><?php echo __('From'); ?></label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($sf_request->getParameter('date_from', '')); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small"><?php echo __('To'); ?></label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($sf_request->getParameter('date_to', '')); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small"><?php echo __('Search'); ?></label>
        <input type="text" name="search" class="form-control form-control-sm" placeholder="<?php echo __('Keywords...'); ?>" value="<?php echo htmlspecialchars($sf_request->getParameter('search', '')); ?>">
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-filter"></i></button>
      </div>
    </form>
  </div>
</div>

<!-- Time Tracking Summary -->
<?php if (!empty($timeByProject)): ?>
<div class="row mb-4">
  <?php foreach (array_slice($timeByProject, 0, 4) as $tp): ?>
  <div class="col-md-3">
    <div class="card text-center h-100">
      <div class="card-body py-2">
        <h5 class="text-primary mb-0"><?php echo floor(($tp->total_minutes ?? 0) / 60); ?>h <?php echo ($tp->total_minutes ?? 0) % 60; ?>m</h5>
        <small class="text-muted"><?php echo htmlspecialchars($tp->project_title ?? __('No Project')); ?></small>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Journal Entries -->
<?php if (!empty($entries)): ?>
  <?php
  $currentDate = '';
  foreach ($entries as $entry):
    $entryDate = date('l, F j, Y', strtotime($entry->entry_date));
    if ($entryDate !== $currentDate):
      $currentDate = $entryDate;
  ?>
    <h6 class="text-muted mt-4 mb-2 border-bottom pb-1"><i class="fas fa-calendar-day me-1"></i><?php echo $entryDate; ?></h6>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start">
        <div class="flex-grow-1">
          <h5 class="card-title mb-1">
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'journalEntry', 'id' => $entry->id]); ?>" class="text-decoration-none">
              <?php echo htmlspecialchars($entry->title ?: __('Untitled Entry')); ?>
            </a>
          </h5>
          <div class="mb-2">
            <?php if (!empty($entry->project_title)): ?>
              <span class="badge bg-info me-1"><i class="fas fa-project-diagram me-1"></i><?php echo htmlspecialchars($entry->project_title); ?></span>
            <?php endif; ?>
            <span class="badge bg-<?php echo match($entry->entry_type ?? 'manual') {
              'manual' => 'primary', 'note' => 'secondary', 'observation' => 'success',
              'finding' => 'warning', 'question' => 'danger', 'auto' => 'dark', default => 'light text-dark'
            }; ?>"><?php echo ucfirst($entry->entry_type ?? 'manual'); ?></span>
            <?php if (!empty($entry->time_spent_minutes)): ?>
              <span class="badge bg-outline-secondary border text-muted ms-1"><i class="fas fa-clock me-1"></i><?php echo floor($entry->time_spent_minutes / 60); ?>h <?php echo $entry->time_spent_minutes % 60; ?>m</span>
            <?php endif; ?>
          </div>
          <div class="text-muted small">
            <?php if (($entry->content_format ?? 'text') === 'html'): ?>
              <?php echo mb_substr(strip_tags($entry->content ?? ''), 0, 200); ?><?php echo mb_strlen(strip_tags($entry->content ?? '')) > 200 ? '...' : ''; ?>
            <?php else: ?>
              <?php echo htmlspecialchars(mb_substr($entry->content ?? '', 0, 200)); ?><?php echo mb_strlen($entry->content ?? '') > 200 ? '...' : ''; ?>
            <?php endif; ?>
          </div>
          <?php if (!empty($entry->tags)): ?>
            <div class="mt-2">
              <?php foreach (explode(',', $entry->tags) as $tag): ?>
                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(trim($tag)); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="text-end ms-3">
          <small class="text-muted"><?php echo date('H:i', strtotime($entry->created_at)); ?></small>
          <?php if (!empty($entry->is_private)): ?>
            <br><small class="text-muted"><i class="fas fa-lock" title="<?php echo __('Private'); ?>"></i></small>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="text-center py-5">
    <i class="fas fa-book fa-4x text-muted mb-3 opacity-50"></i>
    <h4 class="text-muted"><?php echo __('No journal entries yet'); ?></h4>
    <p class="text-muted"><?php echo __('Start documenting your research by creating your first journal entry.'); ?></p>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newEntryModal">
      <i class="fas fa-plus me-1"></i><?php echo __('Create First Entry'); ?>
    </button>
  </div>
<?php endif; ?>

<!-- New Entry Modal -->
<div class="modal fade" id="newEntryModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="form_action" value="create">
        <input type="hidden" name="content" id="journalContentHidden" value="">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-plus me-2"></i><?php echo __('New Journal Entry'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-8">
              <label class="form-label"><?php echo __('Title'); ?></label>
              <input type="text" name="title" class="form-control" placeholder="<?php echo __('Entry title...'); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Date'); ?></label>
              <input type="date" name="entry_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Content'); ?> *</label>
            <div id="journalEditor" style="height: 200px;"></div>
          </div>
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Project'); ?></label>
              <select name="project_id" class="form-select">
                <option value=""><?php echo __('No Project'); ?></option>
                <?php foreach ($projects as $project): ?>
                  <option value="<?php echo $project->id; ?>"><?php echo htmlspecialchars($project->title); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Entry Type'); ?></label>
              <select name="entry_type" class="form-select">
                <option value="manual"><?php echo __('Manual'); ?></option>
                <option value="note"><?php echo __('Note'); ?></option>
                <option value="observation"><?php echo __('Observation'); ?></option>
                <option value="finding"><?php echo __('Finding'); ?></option>
                <option value="question"><?php echo __('Question'); ?></option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Time Spent (minutes)'); ?></label>
              <input type="number" name="time_spent_minutes" class="form-control" min="0" placeholder="0">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-8">
              <label class="form-label"><?php echo __('Tags'); ?></label>
              <input type="text" name="tags" class="form-control" placeholder="<?php echo __('Comma-separated tags...'); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check">
                <input type="checkbox" name="is_private" value="1" class="form-check-input" id="entryPrivate" checked>
                <label class="form-check-label" for="entryPrivate"><?php echo __('Private'); ?></label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary" id="saveEntryBtn"><i class="fas fa-save me-1"></i><?php echo __('Save Entry'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Quill.js CSS -->
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
#journalEditor { background: #fff; color: #212529; }
#journalEditor .ql-editor { min-height: 150px; }
.ql-toolbar.ql-snow { background: #f8f9fa; border-color: #dee2e6; }
.ql-container.ql-snow { border-color: #dee2e6; }
.modal-content { background-color: #fff !important; color: #212529 !important; }
.modal-body .form-label { color: #212529 !important; }
.modal-body .form-control, .modal-body .form-select { background-color: #fff !important; color: #212529 !important; }
</style>

<!-- Quill.js -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var quill = null;
  var modal = document.getElementById('newEntryModal');

  modal.addEventListener('shown.bs.modal', function() {
    if (!quill) {
      quill = new Quill('#journalEditor', {
        theme: 'snow',
        modules: {
          toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            ['blockquote', 'link'],
            ['clean']
          ]
        },
        placeholder: '<?php echo __("Write your journal entry..."); ?>'
      });
    }
  });

  document.getElementById('saveEntryBtn').addEventListener('click', function(e) {
    if (quill) {
      document.getElementById('journalContentHidden').value = quill.root.innerHTML;
    }
  });

  var form = modal.querySelector('form');
  form.addEventListener('submit', function() {
    if (quill) {
      document.getElementById('journalContentHidden').value = quill.root.innerHTML;
    }
  });
});
</script>
<?php end_slot() ?>
