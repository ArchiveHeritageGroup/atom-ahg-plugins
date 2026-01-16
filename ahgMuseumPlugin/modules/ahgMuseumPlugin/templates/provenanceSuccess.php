<?php use_helper('Text') ?>

<div class="row">
  <div class="col-md-12">
    <h1 class="h3 mb-4">
      <i class="fas fa-history me-2"></i>
      <?php echo __('Provenance & Custody History') ?>
    </h1>
    
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
          <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $sf_request->getParameter('slug')]) ?>">
            <?php echo esc_entities($resource->title ?? $sf_request->getParameter('slug')) ?>
          </a>
        </li>
        <li class="breadcrumb-item active"><?php echo __('Provenance') ?></li>
      </ol>
    </nav>
    
    <div class="mb-4">
      <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $sf_request->getParameter('slug')]) ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i><?php echo __('Back') ?>
      </a>

<?php if ($sf_user->isAuthenticated()): ?>
<!-- Edit Actions -->
<div class="row mb-4">
  <div class="col-md-12">
    <div class="card shadow-sm border-primary">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
          <i class="fas fa-edit me-2"></i>
          <?php echo __('Edit Provenance Data') ?>
        </h5>
      </div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
          <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'edit', 'slug' => $sf_request->getParameter('slug')]) ?>#context-collapse" class="btn btn-outline-primary">
            <i class="fas fa-archive me-1"></i> <?php echo __('Edit Archival/Custodial History') ?>
          </a>
          <a href="<?php echo url_for(['module' => 'cco', 'action' => 'provenance', 'slug' => $sf_request->getParameter('slug')]) ?>" class="btn btn-outline-success">
            <i class="fas fa-plus me-1"></i> <?php echo __('Add Detailed Provenance') ?>
          </a>
          <?php if (in_array('ahgProvenancePlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
          <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'edit', 'slug' => $sf_request->getParameter('slug')]) ?>" class="btn btn-outline-info">
            <i class="bi bi-clock-history me-1"></i> <?php echo __('Chain of Custody') ?>
          </a>
          <?php endif ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
    </div>
  </div>
</div>

<div class="row">
  <!-- Timeline Visualization -->
  <div class="col-md-12 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
          <i class="fas fa-stream me-2"></i>
          <?php echo __('Provenance Timeline') ?>
        </h5>
      </div>
      <div class="card-body">
        <div id="timeline-container" style="width: 100%; min-height: 400px; overflow-x: auto;">
          <svg id="provenance-timeline"></svg>
        </div>
        
        <div class="mt-3">
          <div class="d-flex flex-wrap gap-3">
            <span class="badge bg-primary"><?php echo __('Creation') ?></span>
            <span class="badge bg-secondary"><?php echo __('Accumulation') ?></span>
            <span class="badge bg-info"><?php echo __('Collection') ?></span>
            <span class="badge" style="background-color: #6610f2;"><?php echo __('Contribution') ?></span>
            <span class="badge bg-success"><?php echo __('Verified Custody') ?></span>
            <span class="badge bg-warning text-dark"><?php echo __('Unverified Custody') ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Archival History -->
  <div class="col-md-6 mb-4">
    <div class="card shadow-sm h-100">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="fas fa-archive me-2"></i>
          <?php echo __('Archival History') ?>
        </h5>
      </div>
      <div class="card-body">
        <?php if (!empty($archivalHistory)): ?>
          <div class="provenance-text"><?php echo nl2br(esc_entities($archivalHistory)) ?></div>
        <?php else: ?>
          <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('No archival history recorded.') ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- Custodial History -->
  <div class="col-md-6 mb-4">
    <div class="card shadow-sm h-100">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="fas fa-hands me-2"></i>
          <?php echo __('Custodial History') ?>
        </h5>
      </div>
      <div class="card-body">
        <?php if (!empty($custodialHistory)): ?>
          <div class="provenance-text"><?php echo nl2br(esc_entities($custodialHistory)) ?></div>
        <?php else: ?>
          <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('No custodial history recorded.') ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Source of Acquisition -->
  <div class="col-md-6 mb-4">
    <div class="card shadow-sm h-100">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="fas fa-file-import me-2"></i>
          <?php echo __('Immediate Source of Acquisition') ?>
        </h5>
      </div>
      <div class="card-body">
        <?php if (!empty($immediateSourceOfAcquisition)): ?>
          <div class="provenance-text"><?php echo nl2br(esc_entities($immediateSourceOfAcquisition)) ?></div>
        <?php else: ?>
          <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('No acquisition information recorded.') ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- Provenance Events -->
  <div class="col-md-6 mb-4">
    <div class="card shadow-sm h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <i class="fas fa-calendar-alt me-2"></i>
          <?php echo __('Provenance Events') ?>
        </h5>
        <span class="badge bg-secondary"><?php echo count($provenanceEvents) ?></span>
      </div>
      <div class="card-body">
        <?php if (!empty($provenanceEvents)): ?>
          <div class="list-group list-group-flush">
            <?php foreach ($provenanceEvents as $event): ?>
              <div class="list-group-item px-0">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1">
                    <span class="badge bg-primary me-2"><?php echo esc_entities($event['type']) ?></span>
                    <?php echo esc_entities($event['actor'] ?? __('Unknown')) ?>
                  </h6>
                  <small class="text-muted">
                    <?php echo esc_entities($event['date'] ?: $event['startDate']) ?>
                  </small>
                </div>
                <?php if ($event['description']): ?>
                  <p class="mb-1 small"><?php echo esc_entities(truncate_text($event['description'], 150)) ?></p>
                <?php endif; ?>
                <?php if ($event['place']): ?>
                  <small class="text-muted">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <?php echo esc_entities($event['place']) ?>
                  </small>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('No provenance events recorded.') ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Detailed Custody History Table -->
<?php if (!empty($custodyHistory)): ?>
<div class="row">
  <div class="col-md-12 mb-4">
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <i class="fas fa-table me-2"></i>
          <?php echo __('Detailed Custody History') ?>
        </h5>
        <span class="badge bg-secondary"><?php echo count($custodyHistory) ?> <?php echo __('records') ?></span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Period') ?></th>
                <th><?php echo __('Custodian') ?></th>
                <th><?php echo __('Type') ?></th>
                <th><?php echo __('Location') ?></th>
                <th><?php echo __('Method') ?></th>
                <th><?php echo __('Status') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($custodyHistory as $custody): ?>
                <tr>
                  <td class="text-nowrap">
                    <?php echo esc_entities($custody['startDate']) ?>
                    <?php if ($custody['endDate']): ?>
                      <br><small class="text-muted">to <?php echo esc_entities($custody['endDate']) ?></small>
                    <?php else: ?>
                      <br><small class="text-muted"><?php echo __('to present') ?></small>
                    <?php endif; ?>
                  </td>
                  <td><strong><?php echo esc_entities($custody['custodian']) ?></strong></td>
                  <td><?php echo esc_entities($custody['custodianType']) ?></td>
                  <td><?php echo esc_entities($custody['location']) ?></td>
                  <td><?php echo esc_entities($custody['acquisitionMethod']) ?></td>
                  <td>
                    <?php if ($custody['verified']): ?>
                      <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Verified') ?></span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark"><i class="fas fa-question me-1"></i><?php echo __('Unverified') ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- D3.js Timeline -->
<script src="https://d3js.org/d3.v7.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  const timelineData = <?php echo $timelineData ?>;
  
  if (timelineData.length === 0) {
    document.getElementById('timeline-container').innerHTML = 
      '<div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('No dated provenance events to display.') ?></div>';
    return;
  }
  
  const parseDate = d3.timeParse('%Y-%m-%d');
  timelineData.forEach(d => {
    d.startDateParsed = parseDate(d.startDate);
    d.endDateParsed = d.endDate ? parseDate(d.endDate) : new Date();
  });
  
  const validData = timelineData.filter(d => d.startDateParsed);
  
  if (validData.length === 0) {
    document.getElementById('timeline-container').innerHTML = 
      '<div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('No valid dates for timeline.') ?></div>';
    return;
  }
  
  const container = document.getElementById('timeline-container');
  const margin = { top: 40, right: 40, bottom: 60, left: 120 };
  const width = Math.max(800, container.clientWidth) - margin.left - margin.right;
  const height = 350 - margin.top - margin.bottom;
  
  const svg = d3.select('#prov