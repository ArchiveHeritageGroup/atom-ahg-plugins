<?php use_helper('Date') ?>

<div class="container-fluid py-3">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1"><i class="bi bi-globe me-2"></i>OAI-PMH Federation</h4>
      <p class="text-muted mb-0">Manage federation peers and harvest records from remote OAI-PMH repositories</p>
    </div>
    <div>
      <a href="<?php echo url_for(['module' => 'federation', 'action' => 'addPeer']) ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Add Peer
      </a>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card bg-primary text-white h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h2 class="mb-0"><?php echo count($peers) ?></h2>
              <small>Federation Peers</small>
            </div>
            <i class="bi bi-diagram-3 fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h2 class="mb-0"><?php echo number_format($stats['totalFederatedRecords'] ?? 0) ?></h2>
              <small>Harvested Records</small>
            </div>
            <i class="bi bi-file-earmark-text fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <?php
                $activePeers = array_filter($peers, function($p) { return $p->is_active; });
              ?>
              <h2 class="mb-0"><?php echo count($activePeers) ?></h2>
              <small>Active Peers</small>
            </div>
            <i class="bi bi-check-circle fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-warning text-dark h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <?php
                $runningSessions = array_filter($recentSessions, function($s) { return $s->status === 'running'; });
              ?>
              <h2 class="mb-0"><?php echo count($runningSessions) ?></h2>
              <small>Running Harvests</small>
            </div>
            <i class="bi bi-arrow-repeat fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Peers List -->
    <div class="col-md-8">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Federation Peers</h6>
          <a href="<?php echo url_for(['module' => 'federation', 'action' => 'peers']) ?>" class="btn btn-sm btn-outline-secondary">
            View All
          </a>
        </div>
        <div class="card-body p-0">
          <?php if (!empty($peers)): ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Name</th>
                  <th>Status</th>
                  <th>Last Harvest</th>
                  <th>Records</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($peers, 0, 5) as $peer): ?>
                <tr>
                  <td>
                    <strong><?php echo esc_specialchars($peer->name) ?></strong>
                    <br><small class="text-muted"><?php echo esc_specialchars($peer->base_url) ?></small>
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
                      <?php echo format_date($peer->last_harvest_at, 'g') ?>
                      <?php if ($peer->last_harvest_status): ?>
                        <br><small class="text-<?php echo $peer->last_harvest_status === 'success' ? 'success' : ($peer->last_harvest_status === 'partial' ? 'warning' : 'danger') ?>">
                          <?php echo ucfirst($peer->last_harvest_status) ?>
                        </small>
                      <?php endif ?>
                    <?php else: ?>
                      <span class="text-muted">Never</span>
                    <?php endif ?>
                  </td>
                  <td><?php echo number_format($peer->last_harvest_records ?? 0) ?></td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?php echo url_for(['module' => 'federation', 'action' => 'harvest', 'peerId' => $peer->id]) ?>"
                         class="btn btn-outline-primary" title="Harvest">
                        <i class="bi bi-download"></i>
                      </a>
                      <a href="<?php echo url_for(['module' => 'federation', 'action' => 'editPeer', 'id' => $peer->id]) ?>"
                         class="btn btn-outline-secondary" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="p-4 text-center text-muted">
            <i class="bi bi-diagram-3 fs-1 d-block mb-2"></i>
            <p>No federation peers configured yet.</p>
            <a href="<?php echo url_for(['module' => 'federation', 'action' => 'addPeer']) ?>" class="btn btn-primary">
              <i class="bi bi-plus-circle me-1"></i> Add Your First Peer
            </a>
          </div>
          <?php endif ?>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-md-4">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Harvests</h6>
          <a href="<?php echo url_for(['module' => 'federation', 'action' => 'log']) ?>" class="btn btn-sm btn-outline-secondary">
            View Log
          </a>
        </div>
        <div class="card-body p-0">
          <?php if (!empty($recentSessions)): ?>
          <ul class="list-group list-group-flush">
            <?php foreach (array_slice($recentSessions, 0, 5) as $session): ?>
            <li class="list-group-item">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <strong><?php echo esc_specialchars($session->peer_name ?? 'Unknown Peer') ?></strong>
                  <br>
                  <small class="text-muted"><?php echo format_date($session->started_at, 'g') ?></small>
                </div>
                <div class="text-end">
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
                  <?php if ($session->status === 'completed'): ?>
                  <br><small class="text-muted"><?php echo $session->records_created ?> new, <?php echo $session->records_updated ?> updated</small>
                  <?php endif ?>
                </div>
              </div>
            </li>
            <?php endforeach ?>
          </ul>
          <?php else: ?>
          <div class="p-4 text-center text-muted">
            <i class="bi bi-clock fs-1 d-block mb-2"></i>
            <p class="mb-0">No harvest activity yet.</p>
          </div>
          <?php endif ?>
        </div>
      </div>

      <!-- Records by Peer -->
      <?php if (!empty($stats['recordsByPeer'])): ?>
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Records by Source</h6>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <?php foreach ($stats['recordsByPeer'] as $peerStats): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <?php echo esc_specialchars($peerStats->peer_name ?? 'Peer #' . $peerStats->peer_id) ?>
              <span class="badge bg-primary rounded-pill"><?php echo number_format($peerStats->record_count) ?></span>
            </li>
            <?php endforeach ?>
          </ul>
        </div>
      </div>
      <?php endif ?>
    </div>
  </div>

  <!-- Info Panel -->
  <div class="card">
    <div class="card-header">
      <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>About OAI-PMH Federation</h6>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h6>What is Federation?</h6>
          <p class="text-muted small">
            OAI-PMH Federation allows you to harvest archival descriptions from other institutions that expose their
            collections via the Open Archives Initiative Protocol for Metadata Harvesting (OAI-PMH). This enables
            creating unified access points across distributed heritage collections.
          </p>
        </div>
        <div class="col-md-6">
          <h6>Key Features</h6>
          <ul class="text-muted small mb-0">
            <li>Harvest records from peer OAI-PMH repositories</li>
            <li>Support for multiple metadata formats (DC, Heritage, EAD)</li>
            <li>Incremental harvesting based on date ranges</li>
            <li>Full provenance tracking for all harvested records</li>
            <li>Export your collections in Heritage Platform format</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
