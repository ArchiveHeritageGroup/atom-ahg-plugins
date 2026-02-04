<?php use_helper('Date') ?>

<div class="container-fluid py-3">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'federation', 'action' => 'index']) ?>">Federation</a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'federation', 'action' => 'peers']) ?>">Peers</a></li>
          <li class="breadcrumb-item active">Harvest</li>
        </ol>
      </nav>
      <h4 class="mb-0">
        <i class="bi bi-download me-2"></i>
        Harvest: <?php echo esc_specialchars($peer->name) ?>
      </h4>
    </div>
    <div>
      <a href="<?php echo esc_specialchars($peer->base_url) ?>?verb=Identify" target="_blank" class="btn btn-outline-secondary">
        <i class="bi bi-box-arrow-up-right me-1"></i> View Endpoint
      </a>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <!-- Harvest Form -->
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Harvest Options</h6>
        </div>
        <div class="card-body">
          <form id="harvestForm">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="metadata_prefix" class="form-label">Metadata Format</label>
                  <select class="form-select" id="metadata_prefix" name="metadata_prefix">
                    <?php foreach ($formats as $format): ?>
                    <option value="<?php echo esc_specialchars($format['metadataPrefix']) ?>"
                            <?php echo $format['metadataPrefix'] === ($peer->default_metadata_prefix ?? 'oai_dc') ? 'selected' : '' ?>>
                      <?php echo esc_specialchars($format['metadataPrefix']) ?>
                    </option>
                    <?php endforeach ?>
                    <?php if (empty($formats)): ?>
                    <option value="oai_dc">Dublin Core (oai_dc)</option>
                    <option value="oai_heritage">Heritage Platform (oai_heritage)</option>
                    <?php endif ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="set" class="form-label">Set (optional)</label>
                  <select class="form-select" id="set" name="set">
                    <option value="">All records</option>
                    <?php foreach ($sets as $set): ?>
                    <option value="<?php echo esc_specialchars($set['setSpec']) ?>"
                            <?php echo $set['setSpec'] === $peer->default_set ? 'selected' : '' ?>>
                      <?php echo esc_specialchars($set['setName'] ?: $set['setSpec']) ?>
                    </option>
                    <?php endforeach ?>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="from" class="form-label">From Date (optional)</label>
                  <input type="date" class="form-control" id="from" name="from">
                  <div class="form-text">Only harvest records updated after this date</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="until" class="form-label">Until Date (optional)</label>
                  <input type="date" class="form-control" id="until" name="until">
                </div>
              </div>
            </div>

            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="full_harvest" name="full_harvest">
              <label class="form-check-label" for="full_harvest">
                Full harvest (ignore last harvest date)
              </label>
              <div class="form-text">By default, only records updated since the last harvest are fetched</div>
            </div>

            <div class="d-flex gap-2">
              <button type="button" class="btn btn-primary" id="startHarvestBtn" onclick="startHarvest()">
                <i class="bi bi-play-fill me-1"></i> Start Harvest
              </button>
              <button type="button" class="btn btn-outline-secondary" disabled id="stopHarvestBtn" onclick="stopHarvest()" style="display: none;">
                <i class="bi bi-stop-fill me-1"></i> Stop
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Progress -->
      <div class="card mb-4" id="progressCard" style="display: none;">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>Harvest Progress</h6>
        </div>
        <div class="card-body">
          <div class="progress mb-3" style="height: 25px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar" style="width: 0%">
              <span id="progressText">Starting...</span>
            </div>
          </div>
          <div id="progressStats" class="row text-center">
            <div class="col">
              <h4 id="statTotal" class="mb-0">0</h4>
              <small class="text-muted">Total</small>
            </div>
            <div class="col">
              <h4 id="statCreated" class="mb-0 text-success">0</h4>
              <small class="text-muted">Created</small>
            </div>
            <div class="col">
              <h4 id="statUpdated" class="mb-0 text-info">0</h4>
              <small class="text-muted">Updated</small>
            </div>
            <div class="col">
              <h4 id="statSkipped" class="mb-0 text-warning">0</h4>
              <small class="text-muted">Skipped</small>
            </div>
            <div class="col">
              <h4 id="statErrors" class="mb-0 text-danger">0</h4>
              <small class="text-muted">Errors</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Result -->
      <div class="card mb-4" id="resultCard" style="display: none;">
        <div class="card-header" id="resultHeader">
          <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Harvest Complete</h6>
        </div>
        <div class="card-body" id="resultBody">
        </div>
      </div>

      <!-- Harvest History -->
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Harvest History</h6>
        </div>
        <div class="card-body p-0">
          <?php if (!empty($sessions)): ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Format</th>
                  <th>Records</th>
                  <th>Details</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($sessions as $session): ?>
                <tr>
                  <td>
                    <?php echo format_date($session->started_at, 'g') ?>
                    <?php if ($session->completed_at): ?>
                    <br><small class="text-muted">
                      Duration: <?php
                        $start = new DateTime($session->started_at);
                        $end = new DateTime($session->completed_at);
                        $diff = $start->diff($end);
                        if ($diff->h > 0) echo $diff->h . 'h ';
                        if ($diff->i > 0) echo $diff->i . 'm ';
                        echo $diff->s . 's';
                      ?>
                    </small>
                    <?php endif ?>
                  </td>
                  <td>
                    <?php
                      $statusClass = match($session->status) {
                        'completed' => 'success',
                        'running' => 'primary',
                        'failed' => 'danger',
                        'cancelled' => 'warning',
                        default => 'secondary'
                      };
                    ?>
                    <span class="badge bg-<?php echo $statusClass ?>"><?php echo ucfirst($session->status) ?></span>
                  </td>
                  <td><code><?php echo esc_specialchars($session->metadata_prefix) ?></code></td>
                  <td>
                    <?php echo number_format($session->records_total) ?> total<br>
                    <small class="text-success"><?php echo $session->records_created ?> new</small>,
                    <small class="text-info"><?php echo $session->records_updated ?> updated</small>
                    <?php if ($session->records_errors > 0): ?>
                    , <small class="text-danger"><?php echo $session->records_errors ?> errors</small>
                    <?php endif ?>
                  </td>
                  <td>
                    <?php if ($session->is_full_harvest): ?>
                    <span class="badge bg-info">Full</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Incremental</span>
                    <?php endif ?>
                    <?php if ($session->harvest_set): ?>
                    <br><small>Set: <?php echo esc_specialchars($session->harvest_set) ?></small>
                    <?php endif ?>
                  </td>
                </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="p-4 text-center text-muted">
            <i class="bi bi-clock fs-1 d-block mb-2"></i>
            <p class="mb-0">No harvest history for this peer yet.</p>
          </div>
          <?php endif ?>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <!-- Peer Info -->
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Peer Information</h6>
        </div>
        <div class="card-body">
          <p class="mb-2">
            <strong>Name:</strong> <?php echo esc_specialchars($peer->name) ?>
          </p>
          <p class="mb-2">
            <strong>URL:</strong><br>
            <a href="<?php echo esc_specialchars($peer->base_url) ?>?verb=Identify" target="_blank" class="text-break">
              <?php echo esc_specialchars($peer->base_url) ?>
            </a>
          </p>
          <?php if ($peer->description): ?>
          <p class="mb-2">
            <strong>Description:</strong><br>
            <?php echo esc_specialchars($peer->description) ?>
          </p>
          <?php endif ?>
          <p class="mb-2">
            <strong>Status:</strong>
            <?php if ($peer->is_active): ?>
              <span class="badge bg-success">Active</span>
            <?php else: ?>
              <span class="badge bg-secondary">Inactive</span>
            <?php endif ?>
          </p>
          <?php if ($peer->last_harvest_at): ?>
          <p class="mb-0">
            <strong>Last Harvest:</strong><br>
            <?php echo format_date($peer->last_harvest_at, 'f') ?>
          </p>
          <?php endif ?>
        </div>
        <div class="card-footer">
          <a href="<?php echo url_for(['module' => 'federation', 'action' => 'editPeer', 'id' => $peer->id]) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Edit Peer
          </a>
        </div>
      </div>

      <!-- Available Sets -->
      <?php if (!empty($sets)): ?>
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-collection me-2"></i>Available Sets (<?php echo count($sets) ?>)</h6>
        </div>
        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
          <ul class="list-group list-group-flush">
            <?php foreach (array_slice($sets, 0, 20) as $set): ?>
            <li class="list-group-item py-2">
              <code class="small"><?php echo esc_specialchars($set['setSpec']) ?></code><br>
              <small><?php echo esc_specialchars($set['setName'] ?: 'No name') ?></small>
            </li>
            <?php endforeach ?>
            <?php if (count($sets) > 20): ?>
            <li class="list-group-item text-center text-muted py-2">
              <small>+ <?php echo count($sets) - 20 ?> more sets</small>
            </li>
            <?php endif ?>
          </ul>
        </div>
      </div>
      <?php endif ?>
    </div>
  </div>
</div>

<script>
const peerId = <?php echo $peer->id ?>;
let harvestRunning = false;

function startHarvest() {
  if (harvestRunning) return;

  harvestRunning = true;
  document.getElementById('startHarvestBtn').disabled = true;
  document.getElementById('progressCard').style.display = 'block';
  document.getElementById('resultCard').style.display = 'none';

  const form = document.getElementById('harvestForm');
  const formData = new FormData(form);
  const params = new URLSearchParams(formData).toString();

  fetch('<?php echo url_for(['module' => 'federation', 'action' => 'runHarvest', 'peerId' => $peer->id]) ?>', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: params
  })
  .then(response => response.json())
  .then(data => {
    harvestRunning = false;
    document.getElementById('startHarvestBtn').disabled = false;

    if (data.success) {
      showResult(data.result, true);
    } else {
      showResult({ stats: { errors: 1, errorMessages: [data.error] } }, false);
    }
  })
  .catch(error => {
    harvestRunning = false;
    document.getElementById('startHarvestBtn').disabled = false;
    showResult({ stats: { errors: 1, errorMessages: [error.message] } }, false);
  });
}

function showResult(result, success) {
  const stats = result.stats || {};

  // Update progress stats
  document.getElementById('statTotal').textContent = stats.total || 0;
  document.getElementById('statCreated').textContent = stats.created || 0;
  document.getElementById('statUpdated').textContent = stats.updated || 0;
  document.getElementById('statSkipped').textContent = stats.skipped || 0;
  document.getElementById('statErrors').textContent = stats.errors || 0;

  // Update progress bar
  const progressBar = document.getElementById('progressBar');
  progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
  progressBar.style.width = '100%';
  progressBar.classList.add(success ? 'bg-success' : 'bg-danger');
  document.getElementById('progressText').textContent = success ? 'Complete' : 'Failed';

  // Show result card
  const resultCard = document.getElementById('resultCard');
  const resultHeader = document.getElementById('resultHeader');
  const resultBody = document.getElementById('resultBody');

  resultCard.style.display = 'block';

  if (success) {
    resultHeader.innerHTML = '<h6 class="mb-0"><i class="bi bi-check-circle text-success me-2"></i>Harvest Complete</h6>';
    let html = '<p>' + (result.summary || 'Harvest completed successfully.') + '</p>';
    if (stats.created > 0 || stats.updated > 0) {
      html += '<p class="mb-0"><a href="<?php echo url_for(['module' => 'federation', 'action' => 'log']) ?>?peer_id=' + peerId + '">View harvested records</a></p>';
    }
    resultBody.innerHTML = html;
  } else {
    resultHeader.innerHTML = '<h6 class="mb-0"><i class="bi bi-x-circle text-danger me-2"></i>Harvest Failed</h6>';
    let html = '<div class="alert alert-danger mb-0">';
    if (stats.errorMessages && stats.errorMessages.length > 0) {
      html += stats.errorMessages.slice(0, 5).map(e => '<p class="mb-1">' + escapeHtml(e) + '</p>').join('');
      if (stats.errorMessages.length > 5) {
        html += '<p class="mb-0 text-muted">+ ' + (stats.errorMessages.length - 5) + ' more errors</p>';
      }
    } else {
      html += '<p class="mb-0">An unknown error occurred.</p>';
    }
    html += '</div>';
    resultBody.innerHTML = html;
  }
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
</script>
