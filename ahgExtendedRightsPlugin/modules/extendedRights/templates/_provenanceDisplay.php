<?php if ((!isset($provenance) || !is_array($provenance)) && (!isset($agreements) || !is_array($agreements))) return; $provenance = $provenance ?? []; $agreements = $agreements ?? []; ?>
<?php if (count($provenance) > 0 || count($agreements) > 0): ?>
<section id="provenance-area" class="card mb-3">
  <div class="card-header">
    <h4 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Provenance'); ?></h4>
  </div>
  <div class="card-body">
    
    <?php if (count($agreements) > 0): ?>
    <h6 class="text-muted"><?php echo __('Donor Agreements'); ?></h6>
    <ul class="list-unstyled mb-3">
      <?php foreach ($agreements as $agreement): ?>
        <li class="mb-2">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'slug' => $agreement->agreement_slug]); ?>">
                <?php echo esc_entities($agreement->agreement_title ?? $agreement->agreement_number); ?>
              </a>
              <span class="badge bg-secondary ms-1"><?php echo ucfirst(str_replace('_', ' ', $agreement->relationship_type)); ?></span>
              <br>
              <small class="text-muted">
                <?php echo __('Donor'); ?>: 
                <a href="<?php echo url_for(['module' => 'donor', 'action' => 'browse', 'slug' => $agreement->donor_slug]); ?>">
                  <?php echo esc_entities($agreement->donor_name); ?>
                </a>
              </small>
            </div>
            <small class="text-muted"><?php echo $agreement->agreement_date; ?></small>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <?php if (count($provenance) > 0): ?>
    <h6 class="text-muted"><?php echo __('Custody History'); ?></h6>
    <div class="provenance-timeline">
      <?php foreach ($provenance as $i => $record): ?>
        <div class="provenance-item d-flex mb-2">
          <div class="provenance-marker me-3">
            <span class="badge rounded-pill bg-<?php echo $i === 0 ? 'primary' : 'secondary'; ?>"><?php echo count($provenance) - $i; ?></span>
          </div>
          <div class="provenance-content flex-grow-1">
            <div class="d-flex justify-content-between">
              <div>
                <a href="<?php echo url_for(['module' => 'donor', 'action' => 'browse', 'slug' => $record->donor_slug]); ?>">
                  <strong><?php echo esc_entities($record->donor_name); ?></strong>
                </a>
                <span class="badge bg-info ms-1"><?php echo ucfirst($record->relationship_type); ?></span>
              </div>
              <small class="text-muted"><?php echo $record->provenance_date; ?></small>
            </div>
            <?php if ($record->notes): ?>
              <p class="small text-muted mb-0 mt-1"><?php echo esc_entities($record->notes); ?></p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>
<?php endif; ?>
