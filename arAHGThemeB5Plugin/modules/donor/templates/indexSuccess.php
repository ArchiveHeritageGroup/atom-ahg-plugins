<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1 class="h3 mb-0">
    <?php echo __('View donor'); ?>
    <small class="text-muted d-block mt-1"><?php echo esc_entities($donor->authorizedFormOfName) ?: $donor->slug; ?></small>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<!-- Identity Area -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i><?php echo __('Identity area'); ?></h5>
  </div>
  <div class="card-body">
    <?php if ($donor->authorizedFormOfName) { ?>
      <div class="row mb-3">
        <div class="col-md-3 fw-semibold"><?php echo __('Authorized form of name'); ?></div>
        <div class="col-md-9"><?php echo esc_entities($donor->authorizedFormOfName); ?></div>
      </div>
    <?php } ?>
    <?php if (!empty($donor->descriptionIdentifier)) { ?>
      <div class="row mb-3">
        <div class="col-md-3 fw-semibold"><?php echo __('Identifier'); ?></div>
        <div class="col-md-9"><?php echo esc_entities($donor->descriptionIdentifier); ?></div>
      </div>
    <?php } ?>
  </div>
</div>

<!-- Contact Area -->
<div class="card mb-4" id="contactArea">
  <div class="card-header">
    <h5 class="mb-0"><i class="bi bi-telephone me-2"></i><?php echo __('Contact area'); ?></h5>
  </div>
  <div class="card-body">
    <?php if ($donor->contactInformations && count($donor->contactInformations) > 0) { ?>
      <?php foreach ($donor->contactInformations as $contact) { ?>
        <div class="border-bottom pb-3 mb-3">
          <?php if ($contact->primaryContact) { ?>
            <span class="badge bg-primary mb-2"><?php echo __('Primary contact'); ?></span>
          <?php } ?>
          
          <?php if (($contact->title ?? '') || ($contact->contactPerson ?? '')) { ?>
            <div class="row mb-1">
              <div class="col-md-3 text-muted"><?php echo __('Contact person'); ?></div>
              <div class="col-md-9">
                <?php echo esc_entities(trim(($contact->title ?? '').' '.($contact->contactPerson ?? ''))); ?>
                <?php if ($contact->role ?? null) { ?>
                  <span class="text-muted">- <?php echo esc_entities($contact->role); ?></span>
                <?php } ?>
              </div>
            </div>
          <?php } ?>
          
          <?php if ($contact->department ?? null) { ?>
            <div class="row mb-1">
              <div class="col-md-3 text-muted"><?php echo __('Department'); ?></div>
              <div class="col-md-9"><?php echo esc_entities($contact->department); ?></div>
            </div>
          <?php } ?>
          
          <?php if ($contact->email ?? null) { ?>
            <div class="row mb-1">
              <div class="col-md-3 text-muted"><?php echo __('Email'); ?></div>
              <div class="col-md-9"><a href="mailto:<?php echo esc_entities($contact->email); ?>"><?php echo esc_entities($contact->email); ?></a></div>
            </div>
          <?php } ?>
          
          <?php if ($contact->telephone ?? null) { ?>
            <div class="row mb-1">
              <div class="col-md-3 text-muted"><?php echo __('Telephone'); ?></div>
              <div class="col-md-9"><?php echo esc_entities($contact->telephone); ?></div>
            </div>
          <?php } ?>
          
          <?php if ($contact->cell ?? null) { ?>
            <div class="row mb-1">
              <div class="col-md-3 text-muted"><?php echo __('Cell/Mobile'); ?></div>
              <div class="col-md-9"><?php echo esc_entities($contact->cell); ?></div>
            </div>
          <?php } ?>
          
          <?php if ($contact->fax ?? null) { ?>
            <div class="row mb-1">
              <div class="col-md-3 text-muted"><?php echo __('Fax'); ?></div>
              <div class="col-md-9"><?php echo esc_entities($contact->fax); ?></div>
            </div>
          <?php } ?>
          
          <?php if ($contact->streetAddress ?? null) { ?>
            <div class="row mb-1">
              <div class="col-md-3 text-muted"><?php echo __('Address'); ?></div>
              <div class="col-md-9"><?php echo nl2br(esc_entities($contact->streetAddress)); ?></div>
            </div>
          <?php } ?>
          
          <?php
          $location = array_filter([
              $contact->city ?? null,
              $contact->region ?? null,
              $contact->postalCode ?? null,
          ]);
          if (!empty($location)) { ?>
            <div class="row mb-1">
              <div class="col-md-3 text-muted"><?php echo __('Location'); ?></div>
              <div class="col-md-9"><?php echo esc_entities(implode(', ', $location)); ?></div>
            </div>
          <?php } ?>
          
          <?php if ($contact->countryCode ?? null) { ?>
            <div class="row mb-1">
              <div class="col-md-3 text-muted"><?php echo __('Country'); ?></div>
              <div class="col-md-9"><?php echo format_country($contact->countryCode); ?></div>
            </div>
          <?php } ?>
          
          <?php if ($contact->website ?? null) { ?>
            <div class="row mb-1">
              <div class="col-md-3 text-muted"><?php echo __('Website'); ?></div>
              <div class="col-md-9"><a href="<?php echo esc_entities($contact->website); ?>" target="_blank"><?php echo esc_entities($contact->website); ?></a></div>
            </div>
          <?php } ?>
        </div>
      <?php } ?>
    <?php } else { ?>
      <p class="text-muted mb-0"><i class="bi bi-info-circle me-2"></i><?php echo __('No contact information available'); ?></p>
    <?php } ?>
  </div>
</div>

<!-- Accession Area -->
<div class="card mb-4" id="accessionArea">
  <div class="card-header">
    <h5 class="mb-0"><i class="bi bi-archive me-2"></i><?php echo __('Accession area'); ?></h5>
  </div>
  <div class="card-body">
    <h6 class="fw-semibold"><?php echo __('Related accession(s)'); ?></h6>
    <?php if ($donor->relatedAccessions && count($donor->relatedAccessions) > 0) { ?>
      <ul class="list-unstyled mb-0">
        <?php foreach ($donor->relatedAccessions as $accession) { ?>
          <li class="mb-1">
            <i class="bi bi-link-45deg me-1"></i>
            <a href="<?php echo url_for(['module' => 'accession', 'slug' => $accession->slug]); ?>" class="text-decoration-none">
              <?php echo esc_entities($accession->identifier); ?>
            </a>
          </li>
        <?php } ?>
      </ul>
    <?php } else { ?>
      <p class="text-muted mb-0"><i class="bi bi-info-circle me-2"></i><?php echo __('No related accessions'); ?></p>
    <?php } ?>
  </div>
</div>

<!-- Donor Agreements Area -->
<?php if ($donor->donorAgreements && count($donor->donorAgreements) > 0) { ?>
<div class="card mb-4" id="agreementArea">
  <div class="card-header">
    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i><?php echo __('Donor agreements'); ?></h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th><?php echo __('Agreement number'); ?></th>
            <th><?php echo __('Title'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Period'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($donor->donorAgreements as $agreement) { ?>
            <tr>
              <td>
                <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreement->id]); ?>" class="text-decoration-none">
                  <?php echo esc_entities($agreement->agreementNumber) ?: __('N/A'); ?>
                </a>
              </td>
              <td><?php echo esc_entities($agreement->title) ?: __('Untitled'); ?></td>
              <td><?php echo esc_entities($agreement->agreementType) ?: '-'; ?></td>
              <td>
                <?php if ($agreement->status) { ?>
                  <?php
                  $statusClasses = [
                      'draft' => 'bg-secondary',
                      'pending_review' => 'bg-warning text-dark',
                      'pending_signature' => 'bg-info',
                      'active' => 'bg-success',
                      'expired' => 'bg-danger',
                      'terminated' => 'bg-dark',
                      'superseded' => 'bg-secondary',
                  ];
                  $statusClass = $statusClasses[$agreement->status] ?? 'bg-secondary';
                  ?>
                  <span class="badge <?php echo $statusClass; ?>"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $agreement->status))); ?></span>
                <?php } else { ?>
                  -
                <?php } ?>
              </td>
              <td>
                <?php
                $period = [];
                if ($agreement->startDate) {
                    $period[] = date('Y-m-d', strtotime($agreement->startDate));
                }
                if ($agreement->endDate) {
                    $period[] = date('Y-m-d', strtotime($agreement->endDate));
                }
                echo $period ? implode(' - ', $period) : '-';
                ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php } ?>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <div class="d-flex flex-wrap gap-2 mt-4">
    <?php if ($canEdit) { ?>
      <a href="<?php echo url_for(['module' => 'donor', 'action' => 'edit', 'slug' => $donor->slug]); ?>" class="btn btn-primary">
        <i class="bi bi-pencil me-1"></i><?php echo __('Edit'); ?>
      </a>
    <?php } ?>
    <?php if ($canDelete) { ?>
      <a href="<?php echo url_for(['module' => 'donor', 'action' => 'delete', 'slug' => $donor->slug]); ?>" class="btn btn-outline-danger">
        <i class="bi bi-trash me-1"></i><?php echo __('Delete'); ?>
      </a>
    <?php } ?>
    <?php if ($canCreate) { ?>
      <a href="<?php echo url_for(['module' => 'donor', 'action' => 'add']); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-plus-lg me-1"></i><?php echo __('Add new'); ?>
      </a>
    <?php } ?>
    <a href="<?php echo url_for(['module' => 'donor', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-list me-1"></i><?php echo __('Browse donors'); ?>
    </a>
  </div>
<?php end_slot(); ?>
