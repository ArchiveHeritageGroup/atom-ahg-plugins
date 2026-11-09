<?php
$s = $sf_data->getRaw('schedule');
$isNew = $sf_data->getRaw('isNew');
$repositories = $sf_data->getRaw('repositories') ?: [];
$scopeRecordTitle = $sf_data->getRaw('scopeRecordTitle');
$scope = $s->scope_type ?? 'global';
// Every field the save action reads has an input here. It used to send only five, so
// each save switched the schedule off, cleared its notifications, reset the batch to
// 200 and dropped any repository or hierarchy scope.
$v = function ($field, $default) use ($s) {
    return htmlspecialchars((string) ($s->{$field} ?? $default));
};
$checked = function ($field, $default) use ($s) {
    return ($s ? !empty($s->{$field}) : $default) ? 'checked' : '';
};
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-edit me-2"></i><?php echo $isNew ? __('New Schedule') : __('Edit Schedule'); ?></h1>
    <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'schedules']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Schedules'); ?>
    </a>
  </div>

  <form method="post" action="<?php echo url_for(['module' => 'integrity', 'action' => 'scheduleEdit', 'id' => $s->id ?? '']); ?>">
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><?php echo __('General'); ?></h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="name"><?php echo __('Schedule Name'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo $v('name', ''); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="description"><?php echo __('Description'); ?></label>
            <input type="text" class="form-control" id="description" name="description" value="<?php echo $v('description', ''); ?>">
          </div>
          <div class="col-12">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?php echo $checked('is_enabled', true); ?>>
              <label class="form-check-label" for="is_enabled"><?php echo __('Enabled: run this schedule when it is due'); ?></label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Scope & Algorithm'); ?></h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="scope_type"><?php echo __('Scope'); ?></label>
            <select class="form-select" id="scope_type" name="scope_type">
              <option value="global" <?php echo 'global' === $scope ? 'selected' : ''; ?>><?php echo __('Global: every master file'); ?></option>
              <option value="repository" <?php echo 'repository' === $scope ? 'selected' : ''; ?>><?php echo __('Repository'); ?></option>
              <option value="hierarchy" <?php echo 'hierarchy' === $scope ? 'selected' : ''; ?>><?php echo __('Hierarchy: one record and everything below it'); ?></option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="algorithm"><?php echo __('Hash Algorithm'); ?></label>
            <select class="form-select" id="algorithm" name="algorithm">
              <option value="sha256" <?php echo ($s->algorithm ?? 'sha256') === 'sha256' ? 'selected' : ''; ?>>SHA-256</option>
              <option value="sha512" <?php echo ($s->algorithm ?? '') === 'sha512' ? 'selected' : ''; ?>>SHA-512</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="frequency"><?php echo __('Frequency'); ?></label>
            <select class="form-select" id="frequency" name="frequency">
              <option value="daily" <?php echo ($s->frequency ?? '') === 'daily' ? 'selected' : ''; ?>><?php echo __('Daily'); ?></option>
              <option value="weekly" <?php echo ($s->frequency ?? 'weekly') === 'weekly' ? 'selected' : ''; ?>><?php echo __('Weekly'); ?></option>
              <option value="monthly" <?php echo ($s->frequency ?? '') === 'monthly' ? 'selected' : ''; ?>><?php echo __('Monthly'); ?></option>
              <option value="ad_hoc" <?php echo ($s->frequency ?? '') === 'ad_hoc' ? 'selected' : ''; ?>><?php echo __('Ad hoc'); ?></option>
            </select>
          </div>

          <div class="col-md-6" id="scope-repository" <?php echo 'repository' === $scope ? '' : 'hidden'; ?>>
            <label class="form-label" for="repository_id"><?php echo __('Repository'); ?></label>
            <select class="form-select" id="repository_id" name="repository_id">
              <option value=""><?php echo __('Choose a repository'); ?></option>
              <?php foreach ($repositories as $r) { ?>
                <option value="<?php echo (int) $r->id; ?>" <?php echo (int) ($s->repository_id ?? 0) === (int) $r->id ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $r->name); ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="col-md-8" id="scope-hierarchy" <?php echo 'hierarchy' === $scope ? '' : 'hidden'; ?>>
            <label class="form-label" for="scope-record-search"><?php echo __('Top record'); ?></label>
            <input type="hidden" id="information_object_id" name="information_object_id" value="<?php echo (int) ($s->information_object_id ?? 0) ?: ''; ?>">
            <input type="text" class="form-control" id="scope-record-search" autocomplete="off"
                   placeholder="<?php echo __('Type a title or identifier'); ?>"
                   value="<?php echo htmlspecialchars((string) ($scopeRecordTitle ?? '')); ?>">
            <div id="scope-record-results" class="list-group mt-1"></div>
            <div class="form-text"><?php echo __('The check covers this record and every record below it.'); ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Batch & Limits'); ?></h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label" for="batch_size"><?php echo __('Files per run'); ?></label>
            <input type="number" min="0" class="form-control" id="batch_size" name="batch_size" value="<?php echo $v('batch_size', 200); ?>">
            <div class="form-text"><?php echo __('0 checks every file in scope.'); ?></div>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="io_throttle_ms"><?php echo __('Pause between files (ms)'); ?></label>
            <input type="number" min="0" class="form-control" id="io_throttle_ms" name="io_throttle_ms" value="<?php echo $v('io_throttle_ms', 10); ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label" for="max_memory_mb"><?php echo __('Memory (MB)'); ?></label>
            <input type="number" min="64" class="form-control" id="max_memory_mb" name="max_memory_mb" value="<?php echo $v('max_memory_mb', 512); ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label" for="max_runtime_minutes"><?php echo __('Max run (min)'); ?></label>
            <input type="number" min="1" class="form-control" id="max_runtime_minutes" name="max_runtime_minutes" value="<?php echo $v('max_runtime_minutes', 120); ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label" for="max_concurrent_runs"><?php echo __('Runs at once'); ?></label>
            <input type="number" min="1" class="form-control" id="max_concurrent_runs" name="max_concurrent_runs" value="<?php echo $v('max_concurrent_runs', 1); ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="cron_expression"><?php echo __('Cron expression (optional)'); ?></label>
            <input type="text" class="form-control" id="cron_expression" name="cron_expression" value="<?php echo $v('cron_expression', ''); ?>" placeholder="0 2 * * *">
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Notifications'); ?></h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="notify_on_failure" name="notify_on_failure" value="1" <?php echo $checked('notify_on_failure', true); ?>>
              <label class="form-check-label" for="notify_on_failure"><?php echo __('When a run fails'); ?></label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="notify_on_mismatch" name="notify_on_mismatch" value="1" <?php echo $checked('notify_on_mismatch', true); ?>>
              <label class="form-check-label" for="notify_on_mismatch"><?php echo __('When a file has changed'); ?></label>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="notify_email"><?php echo __('Email to'); ?></label>
            <input type="email" class="form-control" id="notify_email" name="notify_email" value="<?php echo $v('notify_email', ''); ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'schedules']); ?>" class="btn btn-secondary"><?php echo __('Cancel'); ?></a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save Schedule'); ?></button>
    </div>
  </form>
</main>

<script <?php echo $nonceAttr; ?>>
(function () {
  var scope = document.getElementById('scope_type');
  var repo = document.getElementById('scope-repository');
  var hier = document.getElementById('scope-hierarchy');
  scope.addEventListener('change', function () {
    repo.hidden = scope.value !== 'repository';
    hier.hidden = scope.value !== 'hierarchy';
  });

  var search = document.getElementById('scope-record-search');
  var results = document.getElementById('scope-record-results');
  var idField = document.getElementById('information_object_id');
  var found = [];
  var timer;
  search.addEventListener('input', function () {
    clearTimeout(timer);
    idField.value = '';
    var q = search.value.trim();
    if (q.length < 2) { results.innerHTML = ''; return; }
    timer = setTimeout(function () {
      fetch('/index.php/api/autocomplete/glam?q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (data) {
          found = data || [];
          results.innerHTML = '';
          found.slice(0, 10).forEach(function (item, i) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'list-group-item list-group-item-action';
            b.dataset.pick = i;
            b.textContent = item.value + (item.identifier ? ' [' + item.identifier + ']' : '');
            results.appendChild(b);
          });
        })
        .catch(function () { results.innerHTML = ''; });
    }, 300);
  });
  results.addEventListener('click', function (e) {
    var b = e.target.closest('[data-pick]');
    if (!b) return;
    var item = found[b.dataset.pick];
    idField.value = item.id;
    search.value = item.value;
    results.innerHTML = '';
  });
})();
</script>
