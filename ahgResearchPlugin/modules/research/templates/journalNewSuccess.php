<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-plus-circle text-primary me-2"></i><?php echo __('New Journal Entry'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$projects = isset($projects) && is_array($projects) ? $projects : (isset($projects) && method_exists($projects, 'getRawValue') ? $projects->getRawValue() : (isset($projects) && is_iterable($projects) ? iterator_to_array($projects) : []));
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'journal']); ?>"><?php echo __('Journal'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('New Entry'); ?></li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-book me-2"></i><?php echo __('New Journal Entry'); ?></h5>
      </div>
      <div class="card-body">
        <form method="post" id="entryForm">
          <input type="hidden" name="content" id="entryContentHidden" value="">

          <div class="row mb-3">
            <div class="col-md-8">
              <label class="form-label"><?php echo __('Title'); ?></label>
              <input type="text" name="title" class="form-control" placeholder="<?php echo __('Entry title...'); ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Date'); ?></label>
              <input type="date" name="entry_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
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
              <input type="number" name="time_spent_minutes" class="form-control" min="0" value="0">
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

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" id="saveEntryBtn"><i class="fas fa-save me-1"></i><?php echo __('Create Entry'); ?></button>
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'journal']); ?>" class="btn btn-secondary"><?php echo __('Cancel'); ?></a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i><?php echo __('Tips'); ?></h6></div>
      <div class="card-body">
        <ul class="small text-muted mb-0">
          <li class="mb-2"><?php echo __('Use the rich text editor to format your entry with headings, lists, and links.'); ?></li>
          <li class="mb-2"><?php echo __('Link entries to projects to track research progress.'); ?></li>
          <li class="mb-2"><?php echo __('Add tags to organize and filter your journal entries.'); ?></li>
          <li><?php echo __('Track time spent to monitor your research effort.'); ?></li>
        </ul>
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

  // Custom image upload handler
  quill.getModule('toolbar').addHandler('image', function() {
    var input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.click();
    input.onchange = function() {
      var file = input.files[0];
      if (!file) return;
      if (file.size > 5 * 1024 * 1024) {
        alert('<?php echo __("Image must be under 5MB"); ?>');
        return;
      }
      var formData = new FormData();
      formData.append('image', file);
      fetch('<?php echo url_for(["module" => "research", "action" => "uploadNoteImage"]); ?>', {
        method: 'POST',
        body: formData
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.url) {
          var range = quill.getSelection(true);
          quill.insertEmbed(range.index, 'image', data.url);
          quill.setSelection(range.index + 1);
        } else {
          alert(data.error || '<?php echo __("Upload failed"); ?>');
        }
      })
      .catch(function() { alert('<?php echo __("Upload failed"); ?>'); });
    };
  });

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
