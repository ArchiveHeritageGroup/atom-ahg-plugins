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

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><?php echo __('OCFL objects'); ?> (<?php echo count($objects); ?>)</span>
    </div>
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead>
          <tr>
            <th><?php echo __('OCFL object id'); ?></th>
            <th><?php echo __('Head'); ?></th>
            <th><?php echo __('Versions'); ?></th>
            <th><?php echo __('Files'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($objects)) { ?>
            <tr><td colspan="4" class="text-muted text-center py-3"><?php echo __('No OCFL objects in this storage root yet.'); ?></td></tr>
          <?php } else { ?>
            <?php foreach ($objects as $obj) { ?>
              <tr>
                <td><code><?php echo htmlspecialchars((string) $obj['object_id']); ?></code></td>
                <td><span class="badge bg-primary"><?php echo htmlspecialchars((string) $obj['head']); ?></span></td>
                <td><?php echo (int) $obj['versions']; ?></td>
                <td><?php echo (int) $obj['files']; ?></td>
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
  function post(url, onDone) {
    var x = new XMLHttpRequest();
    x.open('POST', url, true);
    x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    x.onreadystatechange = function () {
      if (x.readyState === 4) {
        var data = {};
        try { data = JSON.parse(x.responseText); } catch (e) {}
        onDone(data, x.status);
      }
    };
    x.send();
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
})();
</script>
