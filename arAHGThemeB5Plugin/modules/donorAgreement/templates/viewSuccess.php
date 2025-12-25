<?php
$statusInfo = $statuses[$agreement->status] ?? ['label' => ucfirst($agreement->status), 'class' => 'secondary'];
?>
<?php slot('title') ?>
  <?php echo __('Agreement') ?>: <?php echo esc_entities($agreement->agreement_number ?: $agreement->title) ?>
<?php end_slot() ?>

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">
          <i class="fas fa-file-contract me-2"></i>
          <?php echo esc_entities($agreement->title ?: $agreement->agreement_number) ?>
        </h1>
        <div>
          <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'edit', 'id' => $agreement->id]) ?>" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i><?php echo __('Edit') ?>
          </a>
          <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'browse']) ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back') ?>
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8">

      <!-- Agreement Details -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Agreement Details') ?></h5>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4"><?php echo __('Agreement Number') ?></dt>
            <dd class="col-sm-8"><strong><?php echo esc_entities($agreement->agreement_number) ?></strong></dd>

            <dt class="col-sm-4"><?php echo __('Type') ?></dt>
            <dd class="col-sm-8">
              <?php if (!empty($agreement->agreement_type_color)): ?>
                <span class="badge" style="background-color: <?php echo $agreement->agreement_type_color ?>"><?php echo esc_entities($agreement->agreement_type_name) ?></span>
              <?php else: ?>
                <?php echo esc_entities($agreement->agreement_type_name ?? '-') ?>
              <?php endif ?>
            </dd>

            <dt class="col-sm-4"><?php echo __('Status') ?></dt>
            <dd class="col-sm-8">
              <span class="badge bg-<?php echo $statusInfo['class'] ?>"><?php echo __($statusInfo['label']) ?></span>
            </dd>

            <dt class="col-sm-4"><?php echo __('Donor') ?></dt>
            <dd class="col-sm-8">
              <?php if ($agreement->donor_id): ?>
                <a href="<?php echo url_for(['module' => 'donor', 'action' => 'index', 'slug' => $agreement->donor_slug]) ?>">
                  <i class="fas fa-user me-1"></i><?php echo esc_entities($agreement->donor_name) ?>
                </a>
              <?php else: ?>
                <span class="text-muted"><?php echo __('Not assigned') ?></span>
              <?php endif ?>
            </dd>

            <?php if (!empty($agreement->description)): ?>
              <dt class="col-sm-4"><?php echo __('Description') ?></dt>
              <dd class="col-sm-8"><?php echo nl2br(esc_entities($agreement->description)) ?></dd>
            <?php endif ?>

            <?php if ($agreement->is_template): ?>
              <dt class="col-sm-4"><?php echo __('Template') ?></dt>
              <dd class="col-sm-8"><span class="badge bg-info"><?php echo __('Yes - This is a template') ?></span></dd>
            <?php endif ?>

            <?php if (!empty($agreement->supersedes_agreement_id)): ?>
              <dt class="col-sm-4"><?php echo __('Supersedes') ?></dt>
              <dd class="col-sm-8">
                <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreement->supersedes_agreement_id]) ?>">
                  Agreement #<?php echo $agreement->supersedes_agreement_id ?>
                </a>
              </dd>
            <?php endif ?>
          </dl>
        </div>
      </div>

      <!-- Donor Information (if override data exists) -->
      <?php if (!empty($agreement->donor_name) || !empty($agreement->donor_contact_info)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-user me-2"></i><?php echo __('Donor Information') ?></h5>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <?php if (!empty($agreement->donor_name)): ?>
              <dt class="col-sm-4"><?php echo __('Donor Name') ?></dt>
              <dd class="col-sm-8"><?php echo esc_entities($agreement->donor_name) ?></dd>
            <?php endif ?>
            <?php if (!empty($agreement->donor_contact_info)): ?>
              <dt class="col-sm-4"><?php echo __('Contact Info') ?></dt>
              <dd class="col-sm-8"><?php echo nl2br(esc_entities($agreement->donor_contact_info)) ?></dd>
            <?php endif ?>
          </dl>
        </div>
      </div>
      <?php endif ?>

      <!-- Institution Information -->
      <?php if (!empty($agreement->institution_name) || !empty($agreement->repository_representative)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-building me-2"></i><?php echo __('Institution Information') ?></h5>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <?php if (!empty($agreement->institution_name)): ?>
              <dt class="col-sm-4"><?php echo __('Institution Name') ?></dt>
              <dd class="col-sm-8"><?php echo esc_entities($agreement->institution_name) ?></dd>
            <?php endif ?>
            <?php if (!empty($agreement->institution_contact_info)): ?>
              <dt class="col-sm-4"><?php echo __('Contact Info') ?></dt>
              <dd class="col-sm-8"><?php echo nl2br(esc_entities($agreement->institution_contact_info)) ?></dd>
            <?php endif ?>
            <?php if (!empty($agreement->repository_representative)): ?>
              <dt class="col-sm-4"><?php echo __('Representative') ?></dt>
              <dd class="col-sm-8">
                <?php echo esc_entities($agreement->repository_representative) ?>
                <?php if (!empty($agreement->repository_representative_title)): ?>
                  <span class="text-muted">(<?php echo esc_entities($agreement->repository_representative_title) ?>)</span>
                <?php endif ?>
              </dd>
            <?php endif ?>
          </dl>
        </div>
      </div>
      <?php endif ?>

      <!-- Legal Representative -->
      <?php if (!empty($agreement->legal_representative)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i><?php echo __('Legal Representative') ?></h5>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4"><?php echo __('Name') ?></dt>
            <dd class="col-sm-8">
              <?php echo esc_entities($agreement->legal_representative) ?>
              <?php if (!empty($agreement->legal_representative_title)): ?>
                <span class="text-muted">(<?php echo esc_entities($agreement->legal_representative_title) ?>)</span>
              <?php endif ?>
            </dd>
            <?php if (!empty($agreement->legal_representative_contact)): ?>
              <dt class="col-sm-4"><?php echo __('Contact') ?></dt>
              <dd class="col-sm-8"><?php echo nl2br(esc_entities($agreement->legal_representative_contact)) ?></dd>
            <?php endif ?>
          </dl>
        </div>
      </div>
      <?php endif ?>

      <!-- Scope & Transfer -->
      <?php if (!empty($agreement->scope_description) || !empty($agreement->extent_statement) || !empty($agreement->transfer_method)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-boxes me-2"></i><?php echo __('Scope & Transfer') ?></h5>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <?php if (!empty($agreement->scope_description)): ?>
              <dt class="col-sm-4"><?php echo __('Scope Description') ?></dt>
              <dd class="col-sm-8"><?php echo nl2br(esc_entities($agreement->scope_description)) ?></dd>
            <?php endif ?>
            <?php if (!empty($agreement->extent_statement)): ?>
              <dt class="col-sm-4"><?php echo __('Extent') ?></dt>
              <dd class="col-sm-8"><?php echo esc_entities($agreement->extent_statement) ?></dd>
            <?php endif ?>
            <?php if (!empty($agreement->transfer_method)): ?>
              <dt class="col-sm-4"><?php echo __('Transfer Method') ?></dt>
              <dd class="col-sm-8"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $agreement->transfer_method))) ?></dd>
            <?php endif ?>
            <?php if (!empty($agreement->transfer_date)): ?>
              <dt class="col-sm-4"><?php echo __('Transfer Date') ?></dt>
              <dd class="col-sm-8"><?php echo date('d M Y', strtotime($agreement->transfer_date)) ?></dd>
            <?php endif ?>
            <?php if (!empty($agreement->received_by)): ?>
              <dt class="col-sm-4"><?php echo __('Received By') ?></dt>
              <dd class="col-sm-8"><?php echo esc_entities($agreement->received_by) ?></dd>
            <?php endif ?>
          </dl>
        </div>
      </div>
      <?php endif ?>

      <!-- Financial Terms -->
      <?php if ($agreement->has_financial_terms || !empty($agreement->purchase_amount)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i><?php echo __('Financial Terms') ?></h5>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <?php if (!empty($agreement->purchase_amount)): ?>
              <dt class="col-sm-4"><?php echo __('Purchase Amount') ?></dt>
              <dd class="col-sm-8">
                <strong><?php echo esc_entities($agreement->currency ?? 'ZAR') ?> <?php echo number_format($agreement->purchase_amount, 2) ?></strong>
              </dd>
            <?php endif ?>
            <?php if (!empty($agreement->payment_terms)): ?>
              <dt class="col-sm-4"><?php echo __('Payment Terms') ?></dt>
              <dd class="col-sm-8"><?php echo nl2br(esc_entities($agreement->payment_terms)) ?></dd>
            <?php endif ?>
          </dl>
        </div>
      </div>
      <?php endif ?>

      <!-- Terms & Conditions -->
      <?php if (!empty($agreement->general_terms) || !empty($agreement->special_conditions)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-gavel me-2"></i><?php echo __('Terms & Conditions') ?></h5>
        </div>
        <div class="card-body">
          <?php if (!empty($agreement->general_terms)): ?>
            <h6 class="text-muted"><?php echo __('General Terms') ?></h6>
            <p><?php echo nl2br(esc_entities($agreement->general_terms)) ?></p>
          <?php endif ?>
          <?php if (!empty($agreement->special_conditions)): ?>
            <h6 class="text-muted"><?php echo __('Special Conditions') ?></h6>
            <p class="mb-0"><?php echo nl2br(esc_entities($agreement->special_conditions)) ?></p>
          <?php endif ?>
        </div>
      </div>
      <?php endif ?>

      <!-- Important Dates -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i><?php echo __('Important Dates') ?></h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <?php if (!empty($agreement->agreement_date)): ?>
            <div class="col-md-4">
              <div class="text-center p-3 bg-light rounded">
                <small class="text-muted d-block"><?php echo __('Agreement Date') ?></small>
                <strong><?php echo date('d M Y', strtotime($agreement->agreement_date)) ?></strong>
              </div>
            </div>
            <?php endif ?>
            <div class="col-md-4">
              <div class="text-center p-3 bg-light rounded">
                <small class="text-muted d-block"><?php echo __('Effective Date') ?></small>
                <strong><?php echo $agreement->effective_date ? date('d M Y', strtotime($agreement->effective_date)) : '-' ?></strong>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center p-3 bg-light rounded">
                <small class="text-muted d-block"><?php echo __('Review Date') ?></small>
                <strong><?php echo $agreement->review_date ? date('d M Y', strtotime($agreement->review_date)) : '-' ?></strong>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center p-3 <?php echo ($agreement->expiry_date && strtotime($agreement->expiry_date) < time()) ? 'bg-danger text-white' : 'bg-light' ?> rounded">
                <small class="<?php echo ($agreement->expiry_date && strtotime($agreement->expiry_date) < time()) ? '' : 'text-muted' ?> d-block"><?php echo __('Expiry Date') ?></small>
                <strong><?php echo $agreement->expiry_date ? date('d M Y', strtotime($agreement->expiry_date)) : '-' ?></strong>
              </div>
            </div>
            <?php if (!empty($agreement->termination_date)): ?>
            <div class="col-md-4">
              <div class="text-center p-3 bg-dark text-white rounded">
                <small class="d-block"><?php echo __('Termination Date') ?></small>
                <strong><?php echo date('d M Y', strtotime($agreement->termination_date)) ?></strong>
              </div>
            </div>
            <?php endif ?>
          </div>
          <?php if (!empty($agreement->termination_reason)): ?>
            <div class="mt-3">
              <strong><?php echo __('Termination Reason') ?>:</strong>
              <?php echo esc_entities($agreement->termination_reason) ?>
            </div>
          <?php endif ?>
        </div>
      </div>

      <!-- Signatures -->
      <?php if (!empty($agreement->donor_signature_name) || !empty($agreement->repository_signature_name) || !empty($agreement->witness_name)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-signature me-2"></i><?php echo __('Signatures') ?></h5>
        </div>
        <div class="card-body">
          <div class="row">
            <?php if (!empty($agreement->donor_signature_name)): ?>
            <div class="col-md-4">
              <div class="border rounded p-3 text-center">
                <small class="text-muted d-block mb-2"><?php echo __('Donor Signature') ?></small>
                <strong><?php echo esc_entities($agreement->donor_signature_name) ?></strong>
                <?php if (!empty($agreement->donor_signature_date)): ?>
                  <br><small class="text-muted"><?php echo date('d M Y', strtotime($agreement->donor_signature_date)) ?></small>
                <?php endif ?>
              </div>
            </div>
            <?php endif ?>
            <?php if (!empty($agreement->repository_signature_name)): ?>
            <div class="col-md-4">
              <div class="border rounded p-3 text-center">
                <small class="text-muted d-block mb-2"><?php echo __('Repository Signature') ?></small>
                <strong><?php echo esc_entities($agreement->repository_signature_name) ?></strong>
                <?php if (!empty($agreement->repository_signature_date)): ?>
                  <br><small class="text-muted"><?php echo date('d M Y', strtotime($agreement->repository_signature_date)) ?></small>
                <?php endif ?>
              </div>
            </div>
            <?php endif ?>
            <?php if (!empty($agreement->witness_name)): ?>
            <div class="col-md-4">
              <div class="border rounded p-3 text-center">
                <small class="text-muted d-block mb-2"><?php echo __('Witness') ?></small>
                <strong><?php echo esc_entities($agreement->witness_name) ?></strong>
                <?php if (!empty($agreement->witness_date)): ?>
                  <br><small class="text-muted"><?php echo date('d M Y', strtotime($agreement->witness_date)) ?></small>
                <?php endif ?>
              </div>
            </div>
            <?php endif ?>
          </div>
        </div>
      </div>
      <?php endif ?>

      <!-- Documents -->
      <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i><?php echo __('Contract Documents') ?></h5>
          <span class="badge bg-light text-primary"><?php echo count($documents) ?></span>
        </div>
        <div class="card-body">
          <?php if (empty($documents)): ?>
            <p class="text-muted mb-0"><i class="fas fa-info-circle me-1"></i><?php echo __('No documents uploaded yet.') ?></p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th><?php echo __('Document') ?></th>
                    <th><?php echo __('Type') ?></th>
                    <th><?php echo __('Size') ?></th>
                    <th><?php echo __('Uploaded') ?></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($documents as $doc): ?>
                    <tr>
                      <td>
                        <i class="fas fa-file-pdf text-danger me-2"></i>
                        <?php echo esc_entities($doc->original_filename) ?>
                        <?php if (!empty($doc->description)): ?>
                          <br><small class="text-muted"><?php echo esc_entities($doc->description) ?></small>
                        <?php endif ?>
                      </td>
                      <td><span class="badge bg-secondary"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $doc->document_type))) ?></span></td>
                      <td><?php echo number_format($doc->file_size / 1024, 1) ?> KB</td>
                      <td><?php echo date('d M Y', strtotime($doc->created_at)) ?></td>
                      <td>
                        <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'downloadDocument', 'id' => $doc->id]) ?>"
                           class="btn btn-sm btn-outline-primary" target="_blank">
                          <i class="fas fa-download"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            </div>
          <?php endif ?>
        </div>
      </div>

      <!-- Linked Records -->
      <?php if (!empty($linkedRecords)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i><?php echo __('Linked Records') ?></h5>
        </div>
        <div class="card-body">
          <ul class="list-group list-group-flush">
            <?php foreach ($linkedRecords as $record): ?>
              <li class="list-group-item">
                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $record->slug]) ?>">
                  <?php echo esc_entities($record->title ?: $record->identifier) ?>
                </a>
                <?php if ($record->identifier): ?>
                  <small class="text-muted">(<?php echo esc_entities($record->identifier) ?>)</small>
                <?php endif ?>
              </li>
            <?php endforeach ?>
          </ul>
        </div>
      </div>
      <?php endif ?>

      <!-- Linked Accessions -->
      <?php if (!empty($linkedAccessions)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-inbox me-2"></i><?php echo __('Linked Accessions') ?></h5>
        </div>
        <div class="card-body">
          <ul class="list-group list-group-flush">
            <?php foreach ($linkedAccessions as $acc): ?>
              <li class="list-group-item">
                <a href="<?php echo url_for(['module' => 'accession', 'slug' => $acc->slug]) ?>">
                  <?php echo esc_entities($acc->title ?: $acc->identifier) ?>
                </a>
                <small class="text-muted">(<?php echo esc_entities($acc->identifier) ?>)</small>
              </li>
            <?php endforeach ?>
          </ul>
        </div>
      </div>
      <?php endif ?>

      <!-- Internal Notes -->
      <?php if (!empty($agreement->internal_notes)): ?>
      <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
          <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i><?php echo __('Internal Notes') ?></h5>
        </div>
        <div class="card-body">
          <p class="mb-0"><?php echo nl2br(esc_entities($agreement->internal_notes)) ?></p>
        </div>
      </div>
      <?php endif ?>

    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">

      <!-- Reminders -->
      <div class="card mb-4">
        <div class="card-header bg-warning">
          <h5 class="mb-0"><i class="fas fa-bell me-2"></i><?php echo __('Reminders') ?></h5>
        </div>
        <div class="card-body">
          <?php if (empty($reminders)): ?>
            <p class="text-muted mb-0"><?php echo __('No reminders set.') ?></p>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($reminders as $reminder): ?>
                <?php
                $isPast = strtotime($reminder->reminder_date) < time();
                $isSent = !empty($reminder->is_sent);
                ?>
                <li class="list-group-item px-0 <?php echo $isPast && !$isSent ? 'bg-danger bg-opacity-10' : '' ?>">
                  <div class="d-flex justify-content-between">
                    <div>
                      <span class="badge bg-<?php echo $isSent ? 'success' : ($isPast ? 'danger' : 'info') ?> me-2">
                        <?php echo ucfirst(str_replace('_', ' ', $reminder->reminder_type)) ?>
                      </span>
                      <?php if (!empty($reminder->title)): ?>
                        <small><?php echo esc_entities($reminder->title) ?></small>
                      <?php endif ?>
                    </div>
                    <small class="text-muted"><?php echo date('d M Y', strtotime($reminder->reminder_date)) ?></small>
                  </div>
                </li>
              <?php endforeach ?>
            </ul>
          <?php endif ?>
        </div>
      </div>

      <!-- Audit History -->
      <?php if (!empty($history)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('History') ?></h5>
        </div>
        <div class="card-body" style="max-height: 300px; overflow-y: auto;">
          <ul class="list-group list-group-flush">
            <?php foreach ($history as $entry): ?>
              <li class="list-group-item px-0 py-2">
                <small>
                  <strong><?php echo esc_entities(ucfirst($entry->action)) ?></strong>
                  <br>
                  <span class="text-muted"><?php echo date('d M Y H:i', strtotime($entry->created_at)) ?></span>
                </small>
              </li>
            <?php endforeach ?>
          </ul>
        </div>
      </div>
      <?php endif ?>

      <!-- Metadata -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Metadata') ?></h5>
        </div>
        <div class="card-body">
          <small>
            <dl class="row mb-0">
              <dt class="col-5"><?php echo __('ID') ?></dt>
              <dd class="col-7"><?php echo $agreement->id ?></dd>

              <dt class="col-5"><?php echo __('Created') ?></dt>
              <dd class="col-7"><?php echo $agreement->created_at ? date('d M Y H:i', strtotime($agreement->created_at)) : '-' ?></dd>

              <dt class="col-5"><?php echo __('Updated') ?></dt>
              <dd class="col-7"><?php echo $agreement->updated_at ? date('d M Y H:i', strtotime($agreement->updated_at)) : '-' ?></dd>
            </dl>
          </small>
        </div>
      </div>

    </div>
  </div>
</div>
