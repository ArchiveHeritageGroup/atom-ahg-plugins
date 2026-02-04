<?php use_helper('Date') ?>

<div class="container-fluid py-3">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'federation', 'action' => 'index']) ?>">Federation</a></li>
          <li class="breadcrumb-item active">Peers</li>
        </ol>
      </nav>
      <h4 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Federation Peers</h4>
    </div>
    <div>
      <a href="<?php echo url_for(['module' => 'federation', 'action' => 'addPeer']) ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Add Peer
      </a>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <?php if (!empty($peers)): ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Base URL</th>
              <th>Default Format</th>
              <th>Status</th>
              <th>Last Harvest</th>
              <th>Records</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($peers as $peer): ?>
            <tr>
              <td>
                <strong><?php echo esc_specialchars($peer->name) ?></strong>
                <?php if ($peer->description): ?>
                <br><small class="text-muted"><?php echo esc_specialchars(mb_substr($peer->description, 0, 60)) ?>...</small>
                <?php endif ?>
              </td>
              <td>
                <a href="<?php echo esc_specialchars($peer->base_url) ?>?verb=Identify" target="_blank" class="text-decoration-none">
                  <?php echo esc_specialchars($peer->base_url) ?>
                  <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                </a>
              </td>
              <td>
                <code><?php echo esc_specialchars($peer->default_metadata_prefix ?? 'oai_dc') ?></code>
              </td>
              <td>
                <?php if ($peer->is_active): ?>
                  <span class="badge bg-success">Active</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Inactive</span>
                <?php endif ?>
              </td>
              <td>
                <?php if ($peer->last_harvest_at): ?>
                  <?php echo format_date($peer->last_harvest_at, 'f') ?>
                  <?php if ($peer->last_harvest_status): ?>
                    <br>
                    <span class="badge bg-<?php echo $peer->last_harvest_status === 'success' ? 'success' : ($peer->last_harvest_status === 'partial' ? 'warning' : 'danger') ?>">
                      <?php echo ucfirst($peer->last_harvest_status) ?>
                    </span>
                  <?php endif ?>
                <?php else: ?>
                  <span class="text-muted">Never</span>
                <?php endif ?>
              </td>
              <td>
                <strong><?php echo number_format($recordCounts[$peer->id] ?? 0) ?></strong>
                <br><small class="text-muted">harvested</small>
              </td>
              <td class="text-end">
                <div class="btn-group">
                  <a href="<?php echo url_for(['module' => 'federation', 'action' => 'harvest', 'peerId' => $peer->id]) ?>"
                     class="btn btn-sm btn-primary" title="Harvest Records">
                    <i class="bi bi-download me-1"></i> Harvest
                  </a>
                  <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                    <span class="visually-hidden">Toggle Dropdown</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="<?php echo url_for(['module' => 'federation', 'action' => 'editPeer', 'id' => $peer->id]) ?>">
                        <i class="bi bi-pencil me-2"></i> Edit
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="#" onclick="testConnection(<?php echo $peer->id ?>, '<?php echo esc_specialchars($peer->base_url) ?>')">
                        <i class="bi bi-plug me-2"></i> Test Connection
                      </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <a class="dropdown-item text-danger" href="<?php echo url_for(['module' => 'federation', 'action' => 'editPeer', 'id' => $peer->id]) ?>"
                         onclick="return confirm('Are you sure you want to delete this peer?')">
                        <i class="bi bi-trash me-2"></i> Delete
                      </a>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="p-5 text-center">
        <i class="bi bi-diagram-3 fs-1 text-muted d-block mb-3"></i>
        <h5>No Federation Peers</h5>
        <p class="text-muted mb-4">You haven't configured any OAI-PMH peers yet.</p>
        <a href="<?php echo url_for(['module' => 'federation', 'action' => 'addPeer']) ?>" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i> Add Your First Peer
        </a>
      </div>
      <?php endif ?>
    </div>
  </div>
</div>

<!-- Test Connection Modal -->
<div class="modal fade" id="testModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-plug me-2"></i>Connection Test</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="testLoading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 text-muted">Testing connection...</p>
        </div>
        <div id="testResult" style="display: none;"></div>
      </div>
    </div>
  </div>
</div>

<script>
function testConnection(peerId, baseUrl) {
  const modal = new bootstrap.Modal(document.getElementById('testModal'));
  document.getElementById('testLoading').style.display = 'block';
  document.getElementById('testResult').style.display = 'none';
  modal.show();

  fetch('<?php echo url_for(['module' => 'federation', 'action' => 'testPeer']) ?>', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'base_url=' + encodeURIComponent(baseUrl)
  })
  .then(response => response.json())
  .then(data => {
    document.getElementById('testLoading').style.display = 'none';
    const resultDiv = document.getElementById('testResult');
    resultDiv.style.display = 'block';

    if (data.success) {
      let html = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Connection successful!</div>';
      html += '<h6>Repository Information</h6>';
      html += '<table class="table table-sm">';
      html += '<tr><td><strong>Name</strong></td><td>' + escapeHtml(data.identify.repositoryName) + '</td></tr>';
      html += '<tr><td><strong>Base URL</strong></td><td>' + escapeHtml(data.identify.baseURL) + '</td></tr>';
      html += '<tr><td><strong>Protocol</strong></td><td>' + escapeHtml(data.identify.protocolVersion) + '</td></tr>';
      html += '<tr><td><strong>Admin Email</strong></td><td>' + escapeHtml(data.identify.adminEmail) + '</td></tr>';
      html += '<tr><td><strong>Earliest Date</strong></td><td>' + escapeHtml(data.identify.earliestDatestamp) + '</td></tr>';
      html += '</table>';

      html += '<h6>Available Metadata Formats</h6>';
      html += '<ul class="list-group">';
      data.formats.forEach(function(format) {
        html += '<li class="list-group-item"><code>' + escapeHtml(format.metadataPrefix) + '</code></li>';
      });
      html += '</ul>';

      resultDiv.innerHTML = html;
    } else {
      resultDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i>' + escapeHtml(data.error) + '</div>';
    }
  })
  .catch(error => {
    document.getElementById('testLoading').style.display = 'none';
    document.getElementById('testResult').style.display = 'block';
    document.getElementById('testResult').innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i>Request failed: ' + escapeHtml(error.message) + '</div>';
  });
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
</script>
