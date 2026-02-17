<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php slot('title') ?>
<h1><i class="fas fa-book text-primary me-2"></i><?php echo __('Journal Entry'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$projects = isset($projects) && is_array($projects) ? $projects : (isset($projects) && method_exists($projects, 'getRawValue') ? $projects->getRawValue() : (isset($projects) && is_iterable($projects) ? iterator_to_array($projects) : []));
?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'journal']); ?>"><?php echo __('Journal'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo htmlspecialchars($entry->title ?? __('Entry')); ?></li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo htmlspecialchars($entry->title ?? __('Untitled Entry')); ?></h5>
        <div>
          <?php if (!empty($entry->project_title)): ?>
            <span class="badge bg-info"><i class="fas fa-project-diagram me-1"></i><?php echo htmlspecialchars($entry->project_title); ?></span>
          <?php endif; ?>
          <span class="badge bg-<?php echo match($entry->entry_type ?? 'manual') {
            'manual' => 'primary', 'note' => 'secondary', 'observation' => 'success',
            'finding' => 'warning', 'question' => 'danger', 'auto' => 'dark', default => 'light text-dark'
          }; ?>"><?php echo ucfirst($entry->entry_type ?? 'manual'); ?></span>
        </div>
      </div>
      <div class="card-body">
        <form method="post" id="entryForm">
          <input type="hidden" name="form_action" value="update">
          <input type="hidden" name="content" id="entryContentHidden" value="">

          <div class="row mb-3">
            <div class="col-md-8">
              <label class="form-label"><?php echo __('Title'); ?></label>
              <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($entry->title ?? ''); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Date'); ?></label>
              <input type="date" name="entry_date" class="form-control" value="<?php echo $entry->entry_date ?? date('Y-m-d'); ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Content'); ?></label>
            <div id="entryEditor" style="height: 300px;"></div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Project'); ?></label>
              <select name="project_id" class="form-select">
                <option value=""><?php echo __('No Project'); ?></option>
                <?php foreach ($projects as $project): ?>
                  <option value="<?php echo $project->id; ?>" <?php echo (($entry->project_id ?? '') == $project->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($project->title); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Entry Type'); ?></label>
              <select name="entry_type" class="form-select">
                <?php foreach (['manual' => 'Manual', 'note' => 'Note', 'observation' => 'Observation', 'finding' => 'Finding', 'question' => 'Question'] as $typeKey => $typeLabel): ?>
                  <option value="<?php echo $typeKey; ?>" <?php echo (($entry->entry_type ?? 'manual') === $typeKey) ? 'selected' : ''; ?>><?php echo __($typeLabel); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Time Spent (minutes)'); ?></label>
              <input type="number" name="time_spent_minutes" class="form-control" min="0" value="<?php echo (int) ($entry->time_spent_minutes ?? 0); ?>">
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-8">
              <label class="form-label"><?php echo __('Tags'); ?></label>
              <input type="text" name="tags" class="form-control" value="<?php echo htmlspecialchars($entry->tags ?? ''); ?>" placeholder="<?php echo __('Comma-separated tags...'); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check">
                <input type="checkbox" name="is_private" value="1" class="form-check-input" id="entryPrivate" <?php echo ($entry->is_private ?? 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="entryPrivate"><?php echo __('Private'); ?></label>
              </div>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" id="saveEntryBtn"><i class="fas fa-save me-1"></i><?php echo __('Save Changes'); ?></button>
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'journal']); ?>" class="btn btn-secondary"><?php echo __('Back to Journal'); ?></a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <!-- Entry Metadata -->
    <div class="card mb-4">
      <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Details'); ?></h6></div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Created'); ?></span>
          <span><?php echo date('M j, Y H:i', strtotime($entry->created_at)); ?></span>
        </li>
        <?php if (!empty($entry->updated_at)): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Updated'); ?></span>
          <span><?php echo date('M j, Y H:i', strtotime($entry->updated_at)); ?></span>
        </li>
        <?php endif; ?>
        <?php if (!empty($entry->time_spent_minutes)): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Time Spent'); ?></span>
          <span><?php echo floor($entry->time_spent_minutes / 60); ?>h <?php echo $entry->time_spent_minutes % 60; ?>m</span>
        </li>
        <?php endif; ?>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Visibility'); ?></span>
          <span><?php echo ($entry->is_private ?? 1) ? '<i class="fas fa-lock"></i> ' . __('Private') : '<i class="fas fa-globe"></i> ' . __('Public'); ?></span>
        </li>
      </ul>
    </div>

    <!-- Delete -->
    <div class="card border-danger">
      <div class="card-body">
        <h6 class="card-title text-danger"><i class="fas fa-trash me-1"></i><?php echo __('Delete Entry'); ?></h6>
        <p class="small text-muted"><?php echo __('This action cannot be undone.'); ?></p>
        <form method="post" onsubmit="return confirm('<?php echo __('Are you sure you want to delete this entry?'); ?>');">
          <input type="hidden" name="form_action" value="delete">
          <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash me-1"></i><?php echo __('Delete Entry'); ?></button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Quill.js CSS -->
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
#entryEditor { background: #fff; color: #212529; }
#entryEditor .ql-editor { min-height: 250px; }
.ql-toolbar.ql-snow { background: #f8f9fa; border-color: #dee2e6; }
.ql-container.ql-snow { border-color: #dee2e6; }
.ql-editor img { max-width: 100%; height: auto; max-height: 300px; border-radius: 4px; margin: 4px 0; }
</style>

<!-- Quill.js -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var quill = new Quill('#entryEditor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ 'header': [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        ['blockquote', 'link', 'image'],
        [{ 'color': [] }, { 'background': [] }],
        ['clean']
      ]
    },
    placeholder: '<?php echo __("Write your journal entry..."); ?>'
  });

  // Load existing content
  var existingContent = <?php echo json_encode($entry->content ?? ''); ?>;
  if (existingContent) {
    quill.root.innerHTML = existingContent;
  }

  // Sync content before form submit
  var form = document.getElementById('entryForm');
  form.addEventListener('submit', function() {
    document.getElementById('entryContentHidden').value = quill.root.innerHTML;
  });

  document.getElementById('saveEntryBtn').addEventListener('click', function() {
    document.getElementById('entryContentHidden').value = quill.root.innerHTML;
  });
});
</script>
<?php end_slot() ?>
