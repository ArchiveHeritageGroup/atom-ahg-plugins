<?php use_helper('Date') ?>

<div class="container-fluid py-3">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'federation', 'action' => 'index']) ?>">Federation</a></li>
          <li class="breadcrumb-item active">Harvest Log</li>
        </ol>
      </nav>
      <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Harvest Log</h4>
    </div>
  </div>

  <!-- Filters -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="get" class="row g-3">
        <div class="col-md-4">
          <label for="peer_id" class="form-label">Peer</label>
          <select class="form-select" id="peer_id" name="peer_id">
            <option value="">All Peers</option>
            <?php foreach ($peers as $peer): ?>
            <option value="<?php echo $peer->id ?>" <?php echo $filterPeerId == $peer->id ? 'selected' : '' ?>>
              <?php echo esc_specialchars($peer->name) ?>
            </option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="filter_action" class="form-label">Action</label>
          <select class="form-select" id="filter_action" name="filter_action">
            <option value="">All Actions</option>
            <option value="created" <?php echo $filterAction === 'created' ? 'selected' : '' ?>>Created</option>
            <option value="updated" <?php echo $filterAction === 'updated' ? 'selected' : '' ?>>Updated</option>
            <option value="deleted" <?php echo $filterAction === 'deleted' ? 'selected' : '' ?>>Deleted</option>
          </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-primary me-2">
            <i class="bi bi-funnel me-1"></i> Filter
          </button>
          <a href="<?php echo url_for(['module' => 'federation', 'action' => 'log']) ?>" class="btn btn-outline-secondary">
            Clear
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Results -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0">
        <i class="bi bi-list me-2"></i>
        <?php echo number_format($total) ?> log entries
      </h6>
      <?php if ($pages > 1): ?>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?php echo $page - 1 ?>&peer_id=<?php echo $filterPeerId ?>&filter_action=<?php echo $filterAction ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
          <?php endif ?>

          <?php
          $start = max(1, $page - 2);
          $end = min($pages, $page + 2);
          for ($i = $start; $i <= $end; $i++):
          ?>
          <li class="page-item <?php echo $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?php echo $i ?>&peer_id=<?php echo $filterPeerId ?>&filter_action=<?php echo $filterAction ?>">
              <?php echo $i ?>
            </a>
          </li>
          <?php endfor ?>

          <?php if ($page < $pages): ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?php echo $page + 1 ?>&peer_id=<?php echo $filterPeerId ?>&filter_action=<?php echo $filterAction ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
          <?php endif ?>
        </ul>
      </nav>
      <?php endif ?>
    </div>
    <div class="card-body p-0">
      <?php if (!empty($logs)): ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Peer</th>
              <th>Record</th>
              <th>Source ID</th>
              <th>Action</th>
              <th>Format</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
              <td>
                <span title="<?php echo $log->harvest_date ?>">
                  <?php echo format_date($log->harvest_date, 'g') ?>
                </span>
              </td>
              <td>
                <?php if ($log->peer_name): ?>
                <a href="<?php echo url_for(['module' => 'federation', 'action' => 'harvest', 'peerId' => $log->peer_id]) ?>">
                  <?php echo esc_specialchars($log->peer_name) ?>
                </a>
                <?php else: ?>
                <span class="text-muted">Peer #<?php echo $log->peer_id ?></span>
                <?php endif ?>
              </td>
              <td>
                <?php if ($log->object_title && $log->object_slug): ?>
                <a href="<?php echo url_for([$log->object_slug]) ?>">
                  <?php echo esc_specialchars(mb_substr($log->object_title, 0, 50)) ?>
                  <?php if (mb_strlen($log->object_title) > 50): ?>...<?php endif ?>
                </a>
                <?php else: ?>
                <span class="text-muted">Record #<?php echo $log->information_object_id ?></span>
                <?php endif ?>
              </td>
              <td>
                <code class="small"><?php echo esc_specialchars(mb_substr($log->source_oai_identifier, 0, 40)) ?>
                <?php if (mb_strlen($log->source_oai_identifier) > 40): ?>...<?php endif ?></code>
              </td>
              <td>
                <?php
                  $actionClass = match($log->action) {
                    'created' => 'success',
                    'updated' => 'info',
                    'deleted' => 'danger',
                    default => 'secondary'
                  };
                ?>
                <span class="badge bg-<?php echo $actionClass ?>"><?php echo ucfirst($log->action) ?></span>
              </td>
              <td><code class="small"><?php echo esc_specialchars($log->metadata_format) ?></code></td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="p-5 text-center">
        <i class="bi bi-journal fs-1 text-muted d-block mb-3"></i>
        <h5>No Log Entries</h5>
        <p class="text-muted mb-0">
          <?php if ($filterPeerId || $filterAction): ?>
          No entries match your filter criteria.
          <?php else: ?>
          No harvest activity has been logged yet.
          <?php endif ?>
        </p>
      </div>
      <?php endif ?>
    </div>
    <?php if ($pages > 1): ?>
    <div class="card-footer text-muted">
      Showing page <?php echo $page ?> of <?php echo $pages ?>
    </div>
    <?php endif ?>
  </div>
</div>
