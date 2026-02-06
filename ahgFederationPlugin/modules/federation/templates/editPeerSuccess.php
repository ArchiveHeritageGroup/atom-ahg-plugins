<?php use_helper('Date') ?>

<div class="container-fluid py-3">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'federation', 'action' => 'index']) ?>">Federation</a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'federation', 'action' => 'peers']) ?>">Peers</a></li>
          <li class="breadcrumb-item active"><?php echo $isNew ? 'Add Peer' : 'Edit Peer' ?></li>
        </ol>
      </nav>
      <h4 class="mb-0">
        <i class="bi bi-<?php echo $isNew ? 'plus-circle' : 'pencil' ?> me-2"></i>
        <?php echo $isNew ? 'Add Federation Peer' : 'Edit: ' . esc_specialchars($peer->name) ?>
      </h4>
    </div>
  </div>

  <?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?php echo $sf_user->getFlash('error') ?>
  </div>
  <?php endif ?>

  <form method="post" action="">
    <div class="row">
      <div class="col-md-8">
        <!-- Basic Information -->
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="name" name="name"
                     value="<?php echo esc_specialchars($peer->name ?? '') ?>" required>
              <div class="form-text">A descriptive name for this peer repository</div>
            </div>

            <div class="mb-3">
              <label for="base_url" class="form-label">OAI-PMH Base URL <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="url" class="form-control" id="base_url" name="base_url"
                       value="<?php echo esc_specialchars($peer->base_url ?? '') ?>"
                       placeholder="https://example.org/;oai" required>
                <button type="button" class="btn btn-outline-secondary" onclick="testUrl()">
                  <i class="bi bi-plug me-1"></i> Test
                </button>
              </div>
              <div class="form-text">The base URL of the remote OAI-PMH endpoint (without query parameters)</div>
            </div>

            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea class="form-control" id="description" name="description" rows="3"><?php echo esc_specialchars($peer->description ?? '') ?></textarea>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="oai_identifier" class="form-label">OAI Repository Identifier</label>
                  <input type="text" class="form-control" id="oai_identifier" name="oai_identifier"
                         value="<?php echo esc_specialchars($peer->oai_identifier ?? '') ?>">
                  <div class="form-text">Optional identifier from the Identify response</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="contact_email" class="form-label">Contact Email</label>
                  <input type="email" class="form-control" id="contact_email" name="contact_email"
                         value="<?php echo esc_specialchars($peer->contact_email ?? '') ?>">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Harvesting Settings -->
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Harvesting Settings</h6>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="default_metadata_prefix" class="form-label">Default Metadata Format</label>
                  <select class="form-select" id="default_metadata_prefix" name="default_metadata_prefix">
                    <option value="oai_dc" <?php echo ($peer->default_metadata_prefix ?? 'oai_dc') === 'oai_dc' ? 'selected' : '' ?>>
                      Dublin Core (oai_dc)
                    </option>
                    <option value="oai_heritage" <?php echo ($peer->default_metadata_prefix ?? '') === 'oai_heritage' ? 'selected' : '' ?>>
                      Heritage Platform (oai_heritage)
                    </option>
                    <option value="oai_ead" <?php echo ($peer->default_metadata_prefix ?? '') === 'oai_ead' ? 'selected' : '' ?>>
                      EAD (oai_ead)
                    </option>
                  </select>
                  <div class="form-text">Preferred metadata format for harvesting</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="harvest_interval_hours" class="form-label">Harvest Interval (hours)</label>
                  <input type="number" class="form-control" id="harvest_interval_hours" name="harvest_interval_hours"
                         value="<?php echo esc_specialchars($peer->harvest_interval_hours ?? 24) ?>" min="1">
                  <div class="form-text">How often to automatically harvest (for scheduled harvests)</div>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label for="default_set" class="form-label">Default Set (optional)</label>
              <input type="text" class="form-control" id="default_set" name="default_set"
                     value="<?php echo esc_specialchars($peer->default_set ?? '') ?>">
              <div class="form-text">OAI setSpec to harvest by default (leave empty for all records)</div>
            </div>

            <div class="mb-0">
              <label for="api_key" class="form-label">API Key (optional)</label>
              <input type="password" class="form-control" id="api_key" name="api_key"
                     value="<?php echo esc_specialchars($peer->api_key ?? '') ?>"
                     autocomplete="new-password">
              <div class="form-text">If the peer requires authentication</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <!-- Status -->
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-toggle-on me-2"></i>Status</h6>
          </div>
          <div class="card-body">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                     <?php echo ($peer->is_active ?? 1) ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_active">Active</label>
            </div>
            <div class="form-text">Inactive peers won't be included in scheduled harvests</div>
          </div>
        </div>

        <!-- Test Results -->
        <div class="card mb-4" id="testResultCard" style="display: none;">
          <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Connection Test</h6>
          </div>
          <div class="card-body" id="testResultBody">
          </div>
        </div>

        <?php if (!$isNew && $peer->last_harvest_at): ?>
        <!-- Last Harvest Info -->
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Last Harvest</h6>
          </div>
          <div class="card-body">
            <p class="mb-1"><strong>Date:</strong> <?php echo format_date($peer->last_harvest_at, 'f') ?></p>
            <?php if ($peer->last_harvest_status): ?>
            <p class="mb-1">
              <strong>Status:</strong>
              <span class="badge bg-<?php echo $peer->last_harvest_status === 'success' ? 'success' : ($peer->last_harvest_status === 'partial' ? 'warning' : 'danger') ?>">
                <?php echo ucfirst($peer->last_harvest_status) ?>
              </span>
            </p>
            <?php endif ?>
            <p class="mb-0"><strong>Records:</strong> <?php echo number_format($peer->last_harvest_records ?? 0) ?></p>
          </div>
        </div>
        <?php endif ?>

        <!-- Actions -->
        <div class="card">
          <div class="card-body">
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>
                <?php echo $isNew ? 'Create Peer' : 'Save Changes' ?>
              </button>
              <a href="<?php echo url_for(['module' => 'federation', 'action' => 'peers']) ?>" class="btn btn-outline-secondary">
                Cancel
              </a>
              <?php if (!$isNew): ?>
              <hr>
              <button type="submit" name="delete" value="1" class="btn btn-outline-danger"
                      onclick="return confirm('Are you sure you want to delete this peer? This will NOT delete harvested records.')">
                <i class="bi bi-trash me-1"></i> Delete Peer
              </button>
              <?php endif ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function testUrl() {
  const baseUrl = document.getElementById('base_url').value;
  if (!baseUrl) {
    alert('Please enter a base URL first');
    return;
  }

  const resultCard = document.getElementById('testResultCard');
  const resultBody = document.getElementById('testResultBody');

  resultCard.style.display = 'block';
  resultBody.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Testing...</div>';

  fetch('<?php echo url_for(['module' => 'federation', 'action' => 'testPeer']) ?>', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'base_url=' + encodeURIComponent(baseUrl)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      let html = '<div class="alert alert-success mb-2 py-2"><i class="bi bi-check-circle me-1"></i> Connected!</div>';
      html += '<small>';
      html += '<strong>Repository:</strong> ' + escapeHtml(data.identify.repositoryName) + '<br>';
      html += '<strong>Formats:</strong> ' + data.formats.map(f => f.metadataPrefix).join(', ');
      html += '</small>';

      // Auto-fill name if empty
      if (!document.getElementById('name').value && data.identify.repositoryName) {
        document.getElementById('name').value = data.identify.repositoryName;
      }
      if (!document.getElementById('oai_identifier').value && data.identify.description?.oaiIdentifier?.repositoryIdentifier) {
        document.getElementById('oai_identifier').value = data.identify.description.oaiIdentifier.repositoryIdentifier;
      }
      if (!document.getElementById('contact_email').value && data.identify.adminEmail) {
        document.getElementById('contact_email').value = data.identify.adminEmail;
      }

      resultBody.innerHTML = html;
    } else {
      resultBody.innerHTML = '<div class="alert alert-danger mb-0 py-2"><i class="bi bi-x-circle me-1"></i> ' + escapeHtml(data.error) + '</div>';
    }
  })
  .catch(error => {
    resultBody.innerHTML = '<div class="alert alert-danger mb-0 py-2"><i class="bi bi-x-circle me-1"></i> Request failed</div>';
  });
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
</script>
