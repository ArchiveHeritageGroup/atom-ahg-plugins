<?php use_helper('Date') ?>

<div class="container-fluid py-3">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) ?>">Browse</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for([$resource, 'module' => 'informationobject']) ?>"><?php echo $resource->__toString() ?></a></li>
      <li class="breadcrumb-item active">Provenance</li>
    </ol>
  </nav>

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1"><i class="bi bi-clock-history me-2"></i>Provenance & Chain of Custody</h4>
      <p class="text-muted mb-0"><?php echo $resource->__toString() ?></p>
    </div>
    <?php if ($sf_user->isAuthenticated()): ?>
    <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'edit', 'slug' => $resource->slug]) ?>" class="btn btn-primary">
      <i class="bi bi-pencil me-1"></i> Edit Provenance
    </a>
    <?php endif ?>
  </div>

  <?php if (!$provenance['exists']): ?>
  <!-- No provenance recorded -->
  <div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    No provenance information has been recorded for this item.
    <?php if ($sf_user->isAuthenticated()): ?>
    <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'edit', 'slug' => $resource->slug]) ?>" class="alert-link">Add provenance information</a>
    <?php endif ?>
  </div>
  <?php else: ?>
  
  <?php $record = $provenance['record']; ?>
  
  <div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">
      
      <!-- Provenance Summary -->
      <div class="card mb-4">
        <div class="card-header bg-primary text-white">
          <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Provenance Summary</h6>
        </div>
        <div class="card-body">
          <p class="lead mb-0"><?php echo nl2br(htmlspecialchars($provenance['summary'])) ?></p>
        </div>
      </div>

      <!-- Timeline -->
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Chain of Custody Timeline</h6>
        </div>
        <div class="card-body">
          <?php if (empty($provenance['timeline'])): ?>
          <p class="text-muted mb-0">No events recorded in the chain of custody.</p>
          <?php else: ?>
          <div class="timeline">
            <?php foreach ($provenance['timeline'] as $i => $event): ?>
            <div class="timeline-item">
              <div class="timeline-marker <?php echo $event['certainty'] === 'certain' ? 'bg-success' : ($event['certainty'] === 'uncertain' ? 'bg-warning' : 'bg-secondary') ?>"></div>
              <div class="timeline-content">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <span class="badge bg-primary me-2"><?php echo htmlspecialchars($event['type_label']) ?></span>
                    <span class="text-muted small"><?php echo htmlspecialchars($event['date_display']) ?></span>
                  </div>
                  <span class="badge bg-<?php echo $event['certainty'] === 'certain' ? 'success' : ($event['certainty'] === 'uncertain' ? 'warning' : 'secondary') ?>">
                    <?php echo ucfirst($event['certainty']) ?>
                  </span>
                </div>
                <div class="mt-2">
                  <?php if ($event['from']): ?>
                  <span class="text-muted">From:</span> <strong><?php echo htmlspecialchars($event['from']) ?></strong>
                  <?php endif ?>
                  <?php if ($event['from'] && $event['to']): ?> → <?php endif ?>
                  <?php if ($event['to']): ?>
                  <span class="text-muted">To:</span> <strong><?php echo htmlspecialchars($event['to']) ?></strong>
                  <?php endif ?>
                </div>
                <?php if ($event['location']): ?>
                <div class="small text-muted mt-1">
                  <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($event['location']) ?>
                </div>
                <?php endif ?>
                <?php if ($event['description']): ?>
                <div class="mt-2 small"><?php echo nl2br(htmlspecialchars($event['description'])) ?></div>
                <?php endif ?>
              </div>
            </div>
            <?php endforeach ?>
          </div>
          <?php endif ?>
        </div>
      </div>

      <!-- Supporting Documents -->
      <?php if (!empty($provenance['documents'])): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-file-earmark me-2"></i>Supporting Documents</h6>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach ($provenance['documents'] as $doc): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <i class="bi bi-file-pdf me-2"></i>
              <strong><?php echo htmlspecialchars($doc->title ?: $doc->original_filename) ?></strong>
              <span class="badge bg-secondary ms-2"><?php echo ucfirst(str_replace('_', ' ', $doc->document_type)) ?></span>
            </div>
            <?php if ($doc->file_path): ?>
            <a href="<?php echo $doc->file_path ?>" class="btn btn-sm btn-outline-primary" target="_blank">
              <i class="bi bi-download"></i>
            </a>
            <?php endif ?>
          </div>
          <?php endforeach ?>
        </div>
      </div>
      <?php endif ?>

    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
      
      <!-- Status Card -->
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Status</h6>
        </div>
        <div class="card-body">
          <table class="table table-sm mb-0">
            <tr>
              <td class="text-muted">Current Status</td>
              <td><span class="badge bg-info"><?php echo ucfirst($record->current_status ?? 'Unknown') ?></span></td>
            </tr>
            <tr>
              <td class="text-muted">Custody Type</td>
              <td><?php echo ucfirst($record->custody_type ?? 'Unknown') ?></td>
            </tr>
            <tr>
              <td class="text-muted">Acquisition Type</td>
              <td><?php echo ucfirst($record->acquisition_type ?? 'Unknown') ?></td>
            </tr>
            <tr>
              <td class="text-muted">Certainty Level</td>
              <td>
                <span class="badge bg-<?php echo $record->certainty_level === 'certain' ? 'success' : ($record->certainty_level === 'uncertain' ? 'warning' : 'secondary') ?>">
                  <?php echo ucfirst($record->certainty_level ?? 'Unknown') ?>
                </span>
              </td>
            </tr>
            <?php if ($record->has_gaps): ?>
            <tr>
              <td class="text-muted">Gaps</td>
              <td><span class="badge bg-warning">Has Gaps</span></td>
            </tr>
            <?php endif ?>
            <tr>
              <td class="text-muted">Research Status</td>
              <td><?php echo ucfirst(str_replace('_', ' ', $record->research_status ?? 'Not started')) ?></td>
            </tr>
            <tr>
              <td class="text-muted">Complete</td>
              <td>
                <?php if ($record->is_complete): ?>
                <span class="text-success"><i class="bi bi-check-circle"></i> Yes</span>
                <?php else: ?>
                <span class="text-warning"><i class="bi bi-clock"></i> In Progress</span>
                <?php endif ?>
              </td>
            </tr>
          </table>
        </div>
      </div>

      <!-- Nazi-Era Provenance (for museums) -->
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-shield-check me-2"></i>Nazi-Era Provenance Check</h6>
        </div>
        <div class="card-body">
          <?php if ($record->nazi_era_provenance_checked): ?>
            <?php if ($record->nazi_era_provenance_clear): ?>
            <div class="alert alert-success mb-0">
              <i class="bi bi-check-circle me-2"></i>Checked and cleared
            </div>
            <?php else: ?>
            <div class="alert alert-warning mb-0">
              <i class="bi bi-exclamation-triangle me-2"></i>Requires further investigation
            </div>
            <?php endif ?>
          <?php else: ?>
          <div class="alert alert-secondary mb-0">
            <i class="bi bi-hourglass me-2"></i>Not yet checked
          </div>
          <?php endif ?>
        </div>
      </div>

      <!-- Cultural Property -->
      <?php if ($record->cultural_property_status && $record->cultural_property_status !== 'none'): ?>
      <div class="card mb-4 border-warning">
        <div class="card-header bg-warning">
          <h6 class="mb-0"><i class="bi bi-globe me-2"></i>Cultural Property Status</h6>
        </div>
        <div class="card-body">
          <span class="badge bg-<?php echo $record->cultural_property_status === 'disputed' ? 'danger' : 'warning' ?> fs-6">
            <?php echo ucfirst($record->cultural_property_status) ?>
          </span>
          <?php if ($record->cultural_property_notes): ?>
          <p class="mt-2 mb-0 small"><?php echo nl2br(htmlspecialchars($record->cultural_property_notes)) ?></p>
          <?php endif ?>
        </div>
      </div>
      <?php endif ?>

      <!-- Current Owner/Holder -->
      <?php if ($record->current_agent_name): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-person me-2"></i>Current Owner/Holder</h6>
        </div>
        <div class="card-body">
          <strong><?php echo htmlspecialchars($record->current_agent_name) ?></strong>
          <br><span class="text-muted small"><?php echo ucfirst($record->current_agent_type ?? 'Unknown') ?></span>
        </div>
      </div>
      <?php endif ?>

    </div>
  </div>

  <?php endif ?>
</div>

<style>
.timeline {
  position: relative;
  padding-left: 30px;
}
.timeline::before {
  content: '';
  position: absolute;
  left: 10px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #dee2e6;
}
.timeline-item {
  position: relative;
  padding-bottom: 20px;
}
.timeline-marker {
  position: absolute;
  left: -24px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid #fff;
}
.timeline-content {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
}
</style>
