<?php $n = sfConfig::get('csp_nonce', ''); $nattr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<?php
  // Resolve parent record title for display
  $parentTitle = '';
  if ($submission->parent_object_id) {
    $parentTitle = \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
      ->where('id', $submission->parent_object_id)
      ->where('culture', 'en')
      ->value('title') ?? ('ID: ' . $submission->parent_object_id);
  }
?>

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

            <?php if (!empty($projects)): ?>
            <div class="mb-3">
              <label class="form-label fw-bold">Linked Research Project</label>
              <select name="project_id" class="form-select">
                <option value="">-- None --</option>
                <?php foreach ($projects as $proj): ?>
                  <option value="<?php echo $proj->id ?>" <?php echo (int) ($submission->project_id ?? 0) === (int) $proj->id ? 'selected' : '' ?>>
                    <?php echo htmlspecialchars($proj->title) ?> (<?php echo ucfirst($proj->status) ?>)
                  </option>
                <?php endforeach ?>
              </select>
              <small class="text-muted">Link this submission to an existing research project.</small>
            </div>
            <?php endif ?>

            <div class="mb-3">
              <label class="form-label fw-bold">Parent Record (optional)</label>
              <input type="hidden" name="parent_object_id" id="parentObjectId" value="<?php echo $submission->parent_object_id ?? '' ?>">
              <input type="text" class="form-control" id="parentSearch" placeholder="Type to search for a parent record..." autocomplete="off"
                     value="<?php echo htmlspecialchars($parentTitle) ?>">
              <small class="text-muted">Place this submission under an existing archival record. Leave blank for root level.</small>
              <div id="parentResults" class="list-group mt-1" style="display:none; position:absolute; z-index:999; max-height:200px; overflow-y:auto;"></div>
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

<script <?php echo $nattr ?>>
(function() {
  var searchInput = document.getElementById('parentSearch');
  var hiddenInput = document.getElementById('parentObjectId');
  var resultsDiv = document.getElementById('parentResults');
  var debounceTimer = null;

  searchInput.addEventListener('input', function() {
    var q = this.value.trim();
    clearTimeout(debounceTimer);
    if (q.length < 2) { resultsDiv.style.display = 'none'; return; }

    // Clear hidden ID when user types new text
    hiddenInput.value = '';

    debounceTimer = setTimeout(function() {
      fetch('/index.php/informationobject/autocomplete?query=' + encodeURIComponent(q) + '&limit=10')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          var results = data.results || data;
          if (!results || results.length === 0) {
            resultsDiv.innerHTML = '<div class="list-group-item text-muted small">No results found</div>';
            resultsDiv.style.display = '';
            return;
          }
          var html = '';
          results.forEach(function(item) {
            var id = item.identifier || item.id || '';
            var title = item.title || item.name || item.label || '';
            var objectId = item.id || item.object_id || '';
            html += '<a href="#" class="list-group-item list-group-item-action small parent-result" data-id="' + objectId + '">'
              + '<strong>' + title + '</strong>'
              + (id ? ' <span class="text-muted">(' + id + ')</span>' : '')
              + '</a>';
          });
          resultsDiv.innerHTML = html;
          resultsDiv.style.display = '';
        })
        .catch(function() { resultsDiv.style.display = 'none'; });
    }, 300);
  });

  resultsDiv.addEventListener('click', function(e) {
    var item = e.target.closest('.parent-result');
    if (!item) return;
    e.preventDefault();
    hiddenInput.value = item.getAttribute('data-id');
    searchInput.value = item.textContent.trim();
    resultsDiv.style.display = 'none';
  });

  searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { resultsDiv.style.display = 'none'; }
    if (e.key === 'Backspace' && this.value === '') { hiddenInput.value = ''; }
  });

  document.addEventListener('click', function(e) {
    if (!resultsDiv.contains(e.target) && e.target !== searchInput) {
      resultsDiv.style.display = 'none';
    }
  });
})();
</script>
