<?php if ($provenance['exists']): ?>
<?php $record = $provenance['record']; ?>
<div class="card mb-3 provenance-display">
  <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
    <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Provenance</h6>
    <?php if ($sf_user->isAuthenticated()): ?>
    <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'view', 'slug' => QubitInformationObject::getById($objectId)->slug]) ?>" class="btn btn-sm btn-outline-primary">
      View Details
    </a>
    <?php endif ?>
  </div>
  <div class="card-body">
    <!-- Summary -->
    <p class="mb-3"><?php echo nl2br(htmlspecialchars($provenance['summary'])) ?></p>
    
    <!-- Quick Stats -->
    <div class="row g-2 small">
      <div class="col-auto">
        <span class="badge bg-<?php echo $record->certainty_level === 'certain' ? 'success' : ($record->certainty_level === 'uncertain' ? 'warning' : 'secondary') ?>">
          <?php echo ucfirst($record->certainty_level ?? 'Unknown') ?> certainty
        </span>
      </div>
      <?php if ($record->has_gaps): ?>
      <div class="col-auto">
        <span class="badge bg-warning">Has Gaps</span>
      </div>
      <?php endif ?>
      <?php if ($record->nazi_era_provenance_checked): ?>
      <div class="col-auto">
        <span class="badge bg-<?php echo $record->nazi_era_provenance_clear ? 'success' : 'danger' ?>">
          <i class="bi bi-shield-check me-1"></i>Nazi-era <?php echo $record->nazi_era_provenance_clear ? 'Clear' : 'Flagged' ?>
        </span>
      </div>
      <?php endif ?>
    </div>
    
    <!-- Mini Timeline -->
    <?php if (!empty($provenance['timeline']) && count($provenance['timeline']) <= 5): ?>
    <div class="mt-3 pt-3 border-top">
      <small class="text-muted d-block mb-2">Chain of Custody:</small>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($provenance['timeline'] as $i => $event): ?>
        <span class="badge bg-light text-dark border">
          <?php echo htmlspecialchars($event['date_display']) ?>: <?php echo $event['type_label'] ?>
          <?php if ($event['to']): ?> → <?php echo htmlspecialchars($event['to']) ?><?php endif ?>
        </span>
        <?php endforeach ?>
      </div>
    </div>
    <?php elseif (!empty($provenance['timeline'])): ?>
    <div class="mt-3 pt-3 border-top">
      <small class="text-muted"><?php echo count($provenance['timeline']) ?> events in chain of custody</small>
    </div>
    <?php endif ?>
  </div>
</div>
<?php endif ?>
