<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>
    <i class="fas fa-server me-2"></i>
    <?php echo $targetId > 0 ? __('Edit Z39.50 Target') : __('Add Z39.50 Target'); ?>
  </h1>
<?php end_slot(); ?>

<?php
  $target    = $sf_data->getRaw('target');
  $targetId  = $sf_data->getRaw('targetId') ?? 0;
  $yazLoaded = $sf_data->getRaw('yazLoaded');
?>

<div class="card mb-4">
  <div class="card-header fw-bold">
    <?php echo __('Z39.50 / SRU Target Configuration'); ?>
  </div>
  <div class="card-body">
    <form method="post" action="<?php echo url_for(['module' => 'z3950', 'action' => 'edit']); ?>"
          class="row g-3">

      <?php if ($targetId > 0): ?>
        <input type="hidden" name="id" value="<?php echo (int) $targetId; ?>">
      <?php endif; ?>

      <div class="col-md-6">
        <label class="form-label fw-bold" for="f-name">
          <?php echo __('Name'); ?> <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control" id="f-name" name="name"
               value="<?php echo esc_entities($target['name'] ?? ''); ?>"
               required placeholder="e.g. Library of Congress">
      </div>

      <div class="col-md-6">
        <label class="form-label fw-bold" for="f-active">
          <?php echo __('Status'); ?>
        </label>
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" id="f-active"
                 name="is_active" value="1"
                 <?php echo !empty($target['is_active']) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="f-active">
            <?php echo __('Active (enable this target)'); ?>
          </label>
        </div>
      </div>

      <div class="col-12">
        <hr>
        <h5 class="mb-3"><?php echo __('Connection'); ?></h5>
      </div>

      <div class="col-md-5">
        <label class="form-label fw-bold" for="f-host">
          <?php echo __('Host'); ?> <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control" id="f-host" name="host"
               value="<?php echo esc_entities($target['host'] ?? ''); ?>"
               required placeholder="e.g. z regards.loc.gov">
      </div>

      <div class="col-md-2">
        <label class="form-label fw-bold" for="f-port">
          <?php echo __('Port'); ?> <span class="text-danger">*</span>
        </label>
        <input type="number" class="form-control" id="f-port" name="port"
               value="<?php echo esc_entities($target['port'] ?? 210); ?>"
               min="1" max="65535" required>
      </div>

      <div class="col-md-5">
        <label class="form-label fw-bold" for="f-database">
          <?php echo __('Database'); ?> <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control" id="f-database" name="database"
               value="<?php echo esc_entities($target['database'] ?? ''); ?>"
               required placeholder="e.g. Voyager">
      </div>

      <div class="col-md-4">
        <label class="form-label fw-bold" for="f-syntax">
          <?php echo __('Record Syntax'); ?>
        </label>
        <select class="form-select" id="f-syntax" name="syntax">
          <option value="marc21"  <?php echo ($target['syntax'] ?? 'marc21') === 'marc21'  ? 'selected' : ''; ?>>MARC-21 / USMARC</option>
          <option value="usmarc"  <?php echo ($target['syntax'] ?? 'marc21') === 'usmarc'  ? 'selected' : ''; ?>>USMARC</option>
          <option value="xml"     <?php echo ($target['syntax'] ?? 'marc21') === 'xml'     ? 'selected' : ''; ?>>MARCXML</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label fw-bold" for="f-timeout">
          <?php echo __('Timeout (seconds)'); ?>
        </label>
        <input type="number" class="form-control" id="f-timeout" name="timeout"
               value="<?php echo (int) ($target['timeout'] ?? 15); ?>"
               min="1" max="120">
      </div>

      <div class="col-12">
        <hr>
        <h5 class="mb-3"><?php echo __('Authentication'); ?></h5>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-bold" for="f-username">
          <?php echo __('Username'); ?>
        </label>
        <input type="text" class="form-control" id="f-username" name="username"
               value="<?php echo esc_entities($target['username'] ?? ''); ?>"
               autocomplete="off">
      </div>

      <div class="col-md-6">
        <label class="form-label fw-bold" for="f-password">
          <?php echo __('Password'); ?>
          <?php if (!empty($target)): ?>
            <span class="text-muted fw-normal"> — <?php echo __('leave blank to keep current'); ?></span>
          <?php endif; ?>
        </label>
        <input type="password" class="form-control" id="f-password" name="password"
               autocomplete="new-password"
               placeholder="<?php echo !empty($target) ? '••••••••' : ''; ?>">
      </div>

      <div class="col-12">
        <hr>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>
            <?php echo $targetId > 0 ? __('Update Target') : __('Save Target'); ?>
          </button>
          <a href="<?php echo url_for(['module' => 'z3950', 'action' => 'index']); ?>"
             class="btn btn-outline-secondary">
            <?php echo __('Cancel'); ?>
          </a>

          <?php if ($targetId > 0): ?>
            <button type="button" class="btn btn-outline-danger ms-auto"
                    onclick="if(confirm('Delete this target?')) { window.location.href='<?php echo url_for(['module' => 'z3950', 'action' => 'delete', 'id' => $targetId]); ?>'; }">
              <i class="fas fa-trash me-1"></i><?php echo __('Delete Target'); ?>
            </button>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header fw-bold"><?php echo __('Test Connection'); ?></div>
  <div class="card-body">
    <p class="small text-muted mb-2">
      <?php echo __('Enter host/port/database and click "Test" to verify connectivity '
        . 'before saving. For saved targets, use the "Test" button on the index page.'); ?>
    </p>
    <div class="input-group" style="max-width:500px;">
      <input type="text" class="form-control" id="test-host" placeholder="Host"
             value="<?php echo esc_entities($target['host'] ?? ''); ?>">
      <input type="number" class="form-control" id="test-port" placeholder="Port" style="max-width:100px;"
             value="<?php echo esc_entities($target['port'] ?? 210); ?>">
      <input type="text" class="form-control" id="test-db" placeholder="Database"
             value="<?php echo esc_entities($target['database'] ?? ''); ?>">
      <button type="button" class="btn btn-outline-info" id="test-btn"
              onclick="testZ3950()">
        <i class="fas fa-plug me-1"></i><?php echo __('Test'); ?>
      </button>
    </div>
    <div id="test-result" class="mt-2 small" style="display:none;"></div>
  </div>
</div>

<script>
function testZ3950() {
  var host = document.getElementById('test-host').value;
  var port = document.getElementById('test-port').value;
  var db   = document.getElementById('test-db').value;
  var btn  = document.getElementById('test-btn');
  var div  = document.getElementById('test-result');

  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Testing…';
  div.style.display = 'none';

  fetch('/library/z3950/test?host=' + encodeURIComponent(host)
    + '&port=' + encodeURIComponent(port)
    + '&database=' + encodeURIComponent(db))
    .then(function(r) { return r.json(); })
    .then(function(data) {
      div.style.display = 'block';
      if (data.ok) {
        div.className = 'mt-2 small alert alert-success';
        div.innerHTML = '<i class="fas fa-check me-1"></i>' + data.error
          + (data.elapsed_ms ? ' (' + data.elapsed_ms + ' ms)' : '');
      } else {
        div.className = 'mt-2 small alert alert-danger';
        div.innerHTML = '<i class="fas fa-times me-1"></i>' + data.error;
      }
    })
    .catch(function(err) {
      div.style.display = 'block';
      div.className = 'mt-2 small alert alert-danger';
      div.innerHTML = '<i class="fas fa-times me-1"></i>Request failed: ' + err.message;
    })
    .finally(function() {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-plug me-1"></i><?php echo __('Test'); ?>';
    });
}
</script>
