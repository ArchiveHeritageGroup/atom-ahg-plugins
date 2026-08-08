<?php use_helper('Date'); ?>
<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .access-z-index-1000-max-height-280p-4549 { z-index:1000; max-height:280px; overflow-y:auto; }
</style>
<?php $n = sfConfig::get('csp_nonce', ''); $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>

<div class="container mt-4">
  <div class="row">
    <div class="col-lg-8 mx-auto">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for('security/my-requests'); ?>">My Requests</a></li>
          <li class="breadcrumb-item active">Request Access</li>
        </ol>
      </nav>

      <div class="card">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0"><i class="fas fa-key me-2"></i>Request Access</h4>
        </div>
        <div class="card-body">
          <?php /* Flash messages render globally via the theme layout (get_partial('alerts')); not repeated here. */ ?>

          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Current Clearance:</strong>
            <?php echo $currentClearance ? htmlspecialchars($currentClearance->classification_name) : 'None'; ?>
          </div>

          <form method="post" action="<?php echo url_for('@access_request_create'); ?>"
                data-search-url="<?php echo url_for('@access_request_search_objects'); ?>">

            <!-- ===== What do you want access to? ===== -->
            <div class="mb-4">
              <label class="form-label fw-bold">What are you requesting? <span class="text-danger">*</span></label>

              <div class="form-check ar-scope-opt">
                <input class="form-check-input" type="radio" name="scope" id="scope_clearance" value="clearance" checked>
                <label class="form-check-label" for="scope_clearance">
                  <strong>A higher clearance level</strong>
                  <br><small class="text-muted">Raise your overall security clearance across the archive.</small>
                </label>
              </div>

              <div class="form-check ar-scope-opt">
                <input class="form-check-input" type="radio" name="scope" id="scope_item" value="item">
                <label class="form-check-label" for="scope_item">
                  <strong>A specific item</strong>
                  <br><small class="text-muted">Access to a single record only.</small>
                </label>
              </div>

              <div class="form-check ar-scope-opt">
                <input class="form-check-input" type="radio" name="scope" id="scope_collection" value="collection">
                <label class="form-check-label" for="scope_collection">
                  <strong>A whole collection</strong>
                  <br><small class="text-muted">Access to a record and all of its child records.</small>
                </label>
              </div>

              <div class="form-check ar-scope-opt">
                <input class="form-check-input" type="radio" name="scope" id="scope_repository" value="repository">
                <label class="form-check-label" for="scope_repository">
                  <strong>All holdings of a repository</strong>
                  <br><small class="text-muted">Access to everything held by one institution / repository.</small>
                </label>
              </div>

              <div class="form-check ar-scope-opt">
                <input class="form-check-input" type="radio" name="scope" id="scope_all" value="all">
                <label class="form-check-label" for="scope_all">
                  <strong>The entire archive</strong>
                  <br><small class="text-muted">Access to all holdings across every repository.</small>
                </label>
              </div>
            </div>

            <!-- ===== Clearance-level panel ===== -->
            <div class="ar-panel" data-panel="clearance">
              <?php if ($pendingRequest): ?>
                <div class="alert alert-warning">
                  <i class="fas fa-clock me-2"></i>
                  You already have a pending clearance request for
                  <strong><?php echo $pendingRequest->requested_classification ?? 'elevated'; ?></strong> clearance.
                </div>
              <?php endif; ?>
              <div class="mb-3">
                <label for="classification_id" class="form-label">Requested Clearance Level</label>
                <select class="form-select" id="classification_id" name="classification_id">
                  <option value="">-- Select Level --</option>
                  <?php foreach ($classifications as $c): ?>
                    <?php $currentLevel = $currentClearance ? $currentClearance->level : -1; ?>
                    <?php if ($c->level > $currentLevel): ?>
                      <option value="<?php echo $c->id; ?>"><?php echo htmlspecialchars($c->name); ?> (Level <?php echo $c->level; ?>)</option>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- ===== Item / Collection picker panel ===== -->
            <div class="ar-panel d-none" data-panel="object">
              <div class="mb-3 position-relative">
                <label for="ar-object-search" class="form-label">Find the record</label>
                <input type="text" class="form-control" id="ar-object-search" autocomplete="off"
                       placeholder="Type at least 2 characters of a title...">
                <input type="hidden" name="object_id" id="ar-object-id" value="">
                <div id="ar-object-results" class="list-group position-absolute w-100 shadow-sm d-none access-z-index-1000-max-height-280p-4549"
                     ></div>
                <div id="ar-object-chosen" class="form-text mt-2 d-none">
                  <i class="fas fa-check-circle text-success me-1"></i>
                  <span id="ar-object-chosen-title"></span>
                  <button type="button" class="btn btn-link btn-sm p-0 ms-1" id="ar-object-clear">change</button>
                </div>
              </div>
            </div>

            <!-- ===== Repository picker panel ===== -->
            <div class="ar-panel d-none" data-panel="repository">
              <div class="mb-3">
                <label for="repository_id" class="form-label">Repository</label>
                <select class="form-select" id="repository_id" name="repository_id">
                  <option value="">-- Select Repository --</option>
                  <?php foreach ($repositories as $repo): ?>
                    <option value="<?php echo $repo['id']; ?>"><?php echo htmlspecialchars($repo['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- ===== Access level (object scopes only) ===== -->
            <div class="ar-panel d-none" data-panel="accesslevel">
              <div class="mb-3">
                <label for="access_level" class="form-label">Access Level Needed</label>
                <select class="form-select" id="access_level" name="access_level">
                  <option value="view">View - Read-only access</option>
                  <option value="download">Download - View and download files</option>
                  <option value="edit">Edit - Full access including modifications</option>
                </select>
              </div>
            </div>

            <!-- ===== Common fields ===== -->
            <div class="mb-3">
              <label for="urgency" class="form-label">Urgency</label>
              <select class="form-select" id="urgency" name="urgency">
                <option value="low">Low - No rush</option>
                <option value="normal" selected>Normal - Standard processing</option>
                <option value="high">High - Needed soon</option>
                <option value="critical">Critical - Urgent business need</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="reason" class="form-label">Reason for Request <span class="text-danger">*</span></label>
              <textarea class="form-control" id="reason" name="reason" rows="3" required
                        placeholder="Briefly explain why you need this access..."></textarea>
            </div>

            <div class="mb-3">
              <label for="justification" class="form-label">Business Justification</label>
              <textarea class="form-control" id="justification" name="justification" rows="4"
                        placeholder="Provide additional details about your role, project, or research needs..."></textarea>
            </div>

            <div class="d-flex justify-content-between">
              <a href="<?php echo url_for('security/my-requests'); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Cancel
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane me-1"></i> Submit Request
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script <?php echo $nonceAttr; ?>>
(function () {
  var form = document.querySelector('form[data-search-url]');
  if (!form) return;
  var searchUrl = form.getAttribute('data-search-url');

  var panels = {};
  form.querySelectorAll('.ar-panel').forEach(function (p) { panels[p.getAttribute('data-panel')] = p; });

  function show(el, on) { if (el) el.classList.toggle('d-none', !on); }

  // Which panels are visible for each scope.
  var map = {
    clearance:  { clearance: true,  object: false, repository: false, accesslevel: false },
    item:       { clearance: false, object: true,  repository: false, accesslevel: true  },
    collection: { clearance: false, object: true,  repository: false, accesslevel: true  },
    repository: { clearance: false, object: false, repository: true,  accesslevel: true  },
    all:        { clearance: false, object: false, repository: false, accesslevel: true  }
  };

  function applyScope(scope) {
    var cfg = map[scope] || map.clearance;
    show(panels.clearance, cfg.clearance);
    show(panels.object, cfg.object);
    show(panels.repository, cfg.repository);
    show(panels.accesslevel, cfg.accesslevel);
  }

  form.querySelectorAll('input[name="scope"]').forEach(function (r) {
    r.addEventListener('change', function () { if (r.checked) applyScope(r.value); });
  });
  var checked = form.querySelector('input[name="scope"]:checked');
  applyScope(checked ? checked.value : 'clearance');

  // ---- Object autocomplete ----
  var input = document.getElementById('ar-object-search');
  var hidden = document.getElementById('ar-object-id');
  var results = document.getElementById('ar-object-results');
  var chosen = document.getElementById('ar-object-chosen');
  var chosenTitle = document.getElementById('ar-object-chosen-title');
  var clearBtn = document.getElementById('ar-object-clear');
  var timer = null;

  function hideResults() { results.classList.add('d-none'); results.innerHTML = ''; }

  function pick(item) {
    hidden.value = item.id;
    chosenTitle.textContent = item.title + (item.children > 0 ? ' (' + item.children + ' child records)' : '');
    chosen.classList.remove('d-none');
    input.classList.add('d-none');
    hideResults();
  }

  function resetPick() {
    hidden.value = '';
    chosen.classList.add('d-none');
    input.classList.remove('d-none');
    input.value = '';
    input.focus();
  }

  if (input) {
    input.addEventListener('input', function () {
      var q = input.value.trim();
      if (timer) clearTimeout(timer);
      if (q.length < 2) { hideResults(); return; }
      timer = setTimeout(function () {
        fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (r) { return r.json(); })
          .then(function (rows) {
            results.innerHTML = '';
            if (!rows || !rows.length) {
              results.innerHTML = '<div class="list-group-item text-muted small">No matching records</div>';
              results.classList.remove('d-none');
              return;
            }
            rows.forEach(function (item) {
              var a = document.createElement('button');
              a.type = 'button';
              a.className = 'list-group-item list-group-item-action';
              var meta = [];
              if (item.identifier) meta.push(item.identifier);
              if (item.children > 0) meta.push(item.children + ' children');
              a.innerHTML = '<span>' + escapeHtml(item.title) + '</span>' +
                (meta.length ? ' <small class="text-muted">- ' + escapeHtml(meta.join(' · ')) + '</small>' : '');
              a.addEventListener('click', function () { pick(item); });
              results.appendChild(a);
            });
            results.classList.remove('d-none');
          })
          .catch(function () { hideResults(); });
      }, 250);
    });
  }

  if (clearBtn) clearBtn.addEventListener('click', resetPick);

  document.addEventListener('click', function (e) {
    if (input && !input.contains(e.target) && !results.contains(e.target)) hideResults();
  });

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
})();
</script>
