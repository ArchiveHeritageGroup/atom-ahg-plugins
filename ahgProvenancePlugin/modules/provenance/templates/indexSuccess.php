<?php use_helper('Date') ?>

<div class="container-fluid py-3">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1"><i class="bi bi-clock-history me-2"></i>Provenance Management</h4>
      <p class="text-muted mb-0">Track chain of custody and ownership history</p>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card bg-primary text-white h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h2 class="mb-0"><?php echo number_format($stats['total']) ?></h2>
              <small>Total Records</small>
            </div>
            <i class="bi bi-collection fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h2 class="mb-0"><?php echo number_format($stats['complete']) ?></h2>
              <small>Complete</small>
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
              <h2 class="mb-0"><?php echo number_format($stats['has_gaps']) ?></h2>
              <small>With Gaps</small>
            </div>
            <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h2 class="mb-0"><?php echo number_format($stats['nazi_era_checked']) ?></h2>
              <small>Nazi-Era Checked</small>
            </div>
            <i class="bi bi-shield-check fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- By Acquisition Type -->
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>By Acquisition Type</h6>
        </div>
        <div class="card-body">
          <?php if (!empty($stats['by_acquisition_type'])): ?>
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <tbody>
                <?php foreach ($stats['by_acquisition_type'] as $type => $count): ?>
                <tr>
                  <td>
                    <span class="badge bg-secondary me-2"><?php echo ucfirst($type) ?></span>
                  </td>
                  <td class="text-end">
                    <strong><?php echo number_format($count) ?></strong>
                  </td>
                  <td style="width: 50%">
                    <div class="progress" style="height: 20px;">
                      <div class="progress-bar" style="width: <?php echo $stats['total'] > 0 ? round($count / $stats['total'] * 100) : 0 ?>%"></div>
                    </div>
                  </td>
                </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <p class="text-muted mb-0">No data available</p>
          <?php endif ?>
        </div>
      </div>
    </div>

    <!-- By Certainty Level -->
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-speedometer me-2"></i>By Certainty Level</h6>
        </div>
        <div class="card-body">
          <?php if (!empty($stats['by_certainty'])): ?>
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <tbody>
                <?php 
                $certaintyColors = [
                  'certain' => 'success',
                  'probable' => 'info',
                  'possible' => 'warning',
                  'uncertain' => 'danger',
                  'unknown' => 'secondary'
                ];
                foreach ($stats['by_certainty'] as $level => $count): 
                  $color = $certaintyColors[$level] ?? 'secondary';
                ?>
                <tr>
                  <td>
                    <span class="badge bg-<?php echo $color ?> me-2"><?php echo ucfirst($level) ?></span>
                  </td>
                  <td class="text-end">
                    <strong><?php echo number_format($count) ?></strong>
                  </td>
                  <td style="width: 50%">
                    <div class="progress" style="height: 20px;">
                      <div class="progress-bar bg-<?php echo $color ?>" style="width: <?php echo $stats['total'] > 0 ? round($count / $stats['total'] * 100) : 0 ?>%"></div>
                    </div>
                  </td>
                </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <p class="text-muted mb-0">No data available</p>
          <?php endif ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="card mb-4">
    <div class="card-header">
      <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <div class="d-grid">
            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) ?>" class="btn btn-outline-primary">
              <i class="bi bi-search me-2"></i>Browse Records to Add Provenance
            </a>
          </div>
        </div>
        <div class="col-md-4">
          <div class="d-grid">
            <a href="#" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#incompleteModal">
              <i class="bi bi-exclamation-circle me-2"></i>View Incomplete (<?php echo $stats['incomplete'] ?>)
            </a>
          </div>
        </div>
        <div class="col-md-4">
          <div class="d-grid">
            <a href="#" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#naziEraModal">
              <i class="bi bi-shield me-2"></i>Nazi-Era Unchecked (<?php echo $stats['nazi_era_unchecked'] ?>)
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Info Panel -->
  <div class="card">
    <div class="card-header">
      <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>About Provenance Tracking</h6>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h6>What is Provenance?</h6>
          <p class="text-muted small">
            Provenance is the chronological history of ownership, custody, or location of a historical object. 
            Proper provenance research is essential for museums, archives, and libraries to establish 
            authenticity, legal ownership, and cultural significance of their collections.
          </p>
        </div>
        <div class="col-md-6">
          <h6>Key Features</h6>
          <ul class="text-muted small mb-0">
            <li>Chain of custody timeline with event tracking</li>
            <li>Certainty levels for each provenance claim</li>
            <li>Nazi-era provenance checking (1933-1945)</li>
            <li>Cultural property status tracking</li>
            <li>Supporting document management</li>
            <li>Integration with donor agreements</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
