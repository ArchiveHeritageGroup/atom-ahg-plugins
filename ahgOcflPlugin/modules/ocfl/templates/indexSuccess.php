<?php
$storageRootPath = $sf_data->getRaw('storageRootPath');
$storageLayout = $sf_data->getRaw('storageLayout');
$digestAlgorithm = $sf_data->getRaw('digestAlgorithm');
$exportPath = $sf_data->getRaw('exportPath');
$initialized = $sf_data->getRaw('initialized');
$objects = $sf_data->getRaw('objects') ?: [];
$nonce = sfConfig::get('csp_nonce', '');
$nonceAttr = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-box-archive me-2"></i><?php echo __('OCFL Preservation Storage'); ?></h1>
    <div>
      <?php if (!$initialized) { ?>
        <button type="button" class="btn btn-primary btn-sm" id="ocfl-init-btn">
          <i class="fas fa-power-off me-1"></i><?php echo __('Initialise storage root'); ?>
        </button>
      <?php } else { ?>
        <button type="button" class="btn btn-outline-info btn-sm" id="ocfl-verify-all-btn">
          <i class="fas fa-shield-halved me-1"></i><?php echo __('Verify all'); ?>
        </button>
      <?php } ?>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><?php echo __('Storage root'); ?></div>
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-3"><?php echo __('Path'); ?></dt>
        <dd class="col-sm-9"><code><?php echo htmlspecialchars((string) $storageRootPath); ?></code></dd>
        <dt class="col-sm-3"><?php echo __('Status'); ?></dt>
        <dd class="col-sm-9">
          <?php if ($initialized) { ?>
            <span class="badge bg-success"><?php echo __('Initialised (OCFL v1.1)'); ?></span>
          <?php } else { ?>
            <span class="badge bg-secondary"><?php echo __('Not initialised'); ?></span>
          <?php } ?>
        </dd>
        <dt class="col-sm-3"><?php echo __('Layout'); ?></dt>
        <dd class="col-sm-9"><?php echo htmlspecialchars((string) $storageLayout); ?></dd>
        <dt class="col-sm-3"><?php echo __('Digest algorithm'); ?></dt>
        <dd class="col-sm-9"><?php echo htmlspecialchars((string) $digestAlgorithm); ?></dd>
        <dt class="col-sm-3"><?php echo __('Export path'); ?></dt>
        <dd class="col-sm-9"><code><?php echo htmlspecialchars((string) $exportPath); ?></code></dd>
      </dl>
    </div>
  </div>

  <?php if ($initialized) { ?>
  <div class="card mb-4">
    <div class="card-header"><?php echo __('Add a record'); ?></div>
    <div class="card-body">
      <p class="text-muted small mb-2"><?php echo __("Snapshot a record's digital files into preservation storage. A record already stored gets a new version."); ?></p>
      <div class="row g-2 align-items-start">
        <div class="col-md-6">
          <input type="hidden" id="ocfl-io-id">
          <input type="text" class="form-control" id="ocfl-io-search" autocomplete="off" placeholder="<?php echo __('Type a title or identifier'); ?>" aria-label="<?php echo __('Record'); ?>">
          <div id="ocfl-io-results" class="list-group mt-1"></div>
        </div>
        <div class="col-md-4">
          <input type="text" class="form-control" id="ocfl-message" placeholder="<?php echo __('Note for this version (optional)'); ?>" aria-label="<?php echo __('Version note'); ?>">
        </div>
        <div class="col-md-2 d-grid">
          <button type="button" class="btn btn-primary" id="ocfl-ingest-btn" disabled><i class="fas fa-box-archive me-1"></i><?php echo __('Ingest'); ?></button>
        </div>
      </div>
    </div>
  </div>
  <?php } ?>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><?php echo __('OCFL objects'); ?> (<?php echo count($objects); ?>)</span>
    </div>
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead>
          <tr>
            <th><?php echo __('Record'); ?></th>
            <th><?php echo __('OCFL object id'); ?></th>
            <th><?php echo __('Head'); ?></th>
            <th><?php echo __('Versions'); ?></th>
            <th><?php echo __('Files'); ?></th>
            <th><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($objects)) { ?>
            <tr><td colspan="6" class="text-muted text-center py-3"><?php echo __('No OCFL objects in this storage root yet.'); ?></td></tr>
          <?php } else { ?>
            <?php foreach ($objects as $obj) { ?>
              <tr>
                <td><?php echo htmlspecialchars((string) ($obj['title'] ?? '')); ?></td>
                <td><code><?php echo htmlspecialchars((string) $obj['object_id']); ?></code></td>
                <td><span class="badge bg-primary"><?php echo htmlspecialchars((string) $obj['head']); ?></span></td>
                <td><?php echo (int) $obj['versions']; ?></td>
                <td><?php echo (int) $obj['files']; ?></td>
                <td>
                  <?php if (!empty($obj['io_id'])) { $io = (int) $obj['io_id']; ?>
                  <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-success" data-ocfl="verify" data-url="<?php echo url_for('@ocfl_api_verify?id='.$io); ?>"><?php echo __('Verify'); ?></button>
                    <button type="button" class="btn btn-outline-primary" data-ocfl="ingest" data-url="<?php echo url_for('@ocfl_api_ingest?id='.$io); ?>"><?php echo __('New version'); ?></button>
                    <button type="button" class="btn btn-outline-secondary" data-ocfl="export" data-url="<?php echo url_for('@ocfl_api_export?id='.$io); ?>"><?php echo __('Export'); ?></button>
                  </div>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script <?php echo $nonceAttr; ?>>
(function () {
  function post(url, onDone, body) {
    var x = new XMLHttpRequest();
    x.open('POST', url, true);
    x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    if (body) { x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded'); }
    x.onreadystatechange = function () {
      if (x.readyState === 4) {
        var data = {};
        try { data = JSON.parse(x.responseText); } catch (e) {}
        onDone(data, x.status);
      }
    };
    x.send(body || null);
  }

  var initBtn = document.getElementById('ocfl-init-btn');
  if (initBtn) {
    initBtn.addEventListener('click', function () {
      initBtn.disabled = true;
      post('<?php echo url_for(['module' => 'ocfl', 'action' => 'apiInit']); ?>', function (data) {
        alert(data.message || 'Done.');
        window.location.reload();
      });
    });
  }

  var verifyAllBtn = document.getElementById('ocfl-verify-all-btn');
  if (verifyAllBtn) {
    verifyAllBtn.addEventListener('click', function () {
      verifyAllBtn.disabled = true;
      post('<?php echo url_for(['module' => 'ocfl', 'action' => 'apiVerifyAll']); ?>', function (data) {
        if (data.success) {
          alert('All ' + (data.total || 0) + ' object(s) verified OK.');
        } else {
          alert((data.failed || 0) + ' of ' + (data.total || 0) + ' object(s) failed verification. See the browser console for detail.');
          if (window.console) { console.log('OCFL verify-all results', data.results); }
        }
        verifyAllBtn.disabled = false;
      });
    });
  }
  // Ingest, per-object verify, new version and export: the API had them, but the
  // dashboard offered only Initialise and Verify all.
  var ingestUrl = '<?php echo url_for('@ocfl_api_ingest?id=0'); ?>';
  var search = document.getElementById('ocfl-io-search');
  if (search) {
    var results = document.getElementById('ocfl-io-results');
    var idField = document.getElementById('ocfl-io-id');
    var ingestBtn = document.getElementById('ocfl-ingest-btn');
    var found = [], timer;
    search.addEventListener('input', function () {
      clearTimeout(timer); idField.value = ''; ingestBtn.disabled = true;
      var q = search.value.trim();
      if (q.length < 2) { results.innerHTML = ''; return; }
      timer = setTimeout(function () {
        fetch('/index.php/api/autocomplete/glam?q=' + encodeURIComponent(q))
          .then(function (r) { return r.json(); })
          .then(function (data) {
            found = data || []; results.innerHTML = '';
            found.slice(0, 10).forEach(function (item, i) {
              var b = document.createElement('button');
              b.type = 'button'; b.className = 'list-group-item list-group-item-action'; b.dataset.pick = i;
              b.textContent = item.value + (item.identifier ? ' [' + item.identifier + ']' : '');
              results.appendChild(b);
            });
          }).catch(function () { results.innerHTML = ''; });
      }, 300);
    });
    results.addEventListener('click', function (e) {
      var b = e.target.closest('[data-pick]'); if (!b) return;
      var item = found[b.dataset.pick];
      idField.value = item.id; search.value = item.value; results.innerHTML = ''; ingestBtn.disabled = false;
    });
    ingestBtn.addEventListener('click', function () {
      ingestBtn.disabled = true;
      var msg = document.getElementById('ocfl-message').value.trim();
      post(ingestUrl.replace(/0$/, idField.value), function (data) {
        alert(data.message || (data.success ? 'Stored as ' + data.object_id + ' ' + data.head : 'Failed'));
        window.location.reload();
      }, msg ? 'message=' + encodeURIComponent(msg) : null);
    });
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-ocfl]'); if (!b) return;
    var act = b.dataset.ocfl; b.disabled = true;
    post(b.dataset.url, function (data) {
      if (act === 'verify') {
        alert(data.success ? 'Verified OK: fixity and structure match.' : ('Verification failed:\n' + ((data.errors && data.errors.length) ? data.errors.join('\n') : (data.message || ''))));
      } else if (act === 'export') {
        alert(data.success ? 'Exported ' + (data.files || 0) + ' file(s) to ' + data.path : (data.message || 'Export failed'));
      } else {
        alert(data.message || (data.success ? 'New version ' + data.head : 'Failed'));
        window.location.reload(); return;
      }
      b.disabled = false;
    });
  });
})();
</script>
