<?php
/**
 * Donor Agreement Form Partial
 * Variables: $agreement, $types, $statuses, $donor, $donorId, $title, $action, $documents
 */
$isEdit = isset($agreement) && $agreement && isset($agreement->id);
?>
<link href="/plugins/ahgThemeB5Plugin/css/tom-select.bootstrap5.min.css" rel="stylesheet">

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col">
      <h1 class="h3">
        <i class="fas fa-file-contract me-2"></i>
        <?php echo $title ?>
      </h1>
    </div>
  </div>

  <form method="post" action="<?php echo $action ?>" enctype="multipart/form-data">
    <div class="row">
      <div class="col-lg-8">

        <!-- Main Details -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><?php echo __('Agreement Details') ?></h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Agreement Number') ?></label>
                <input type="text" name="agreement[agreement_number]" class="form-control"
                       value="<?php echo esc_entities($agreement->agreement_number ?? '') ?>"
                       placeholder="<?php echo __('Auto-generated if blank') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Agreement Type') ?> <span class="text-danger">*</span></label>
                <select name="agreement[agreement_type_id]" class="form-select" required>
                  <option value=""><?php echo __('Select type...') ?></option>
                  <?php foreach ($types as $type): ?>
                    <option value="<?php echo $type->id ?>" <?php echo ($agreement->agreement_type_id ?? '') == $type->id ? 'selected' : '' ?>>
                      <?php echo esc_entities($type->name) ?>
                    </option>
                  <?php endforeach ?>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label"><?php echo __('Title') ?></label>
                <input type="text" name="agreement[title]" class="form-control" value="<?php echo esc_entities($agreement->title ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label"><?php echo __('Description') ?></label>
                <textarea name="agreement[description]" class="form-control" rows="3"><?php echo esc_entities($agreement->description ?? '') ?></textarea>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input type="checkbox" name="agreement[is_template]" id="is_template" class="form-check-input" value="1"
                         <?php echo ($agreement->is_template ?? 0) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="is_template"><?php echo __('Save as Template') ?></label>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Supersedes Agreement') ?></label>
                <input type="text" name="agreement[supersedes_agreement_id]" class="form-control"
                       value="<?php echo esc_entities($agreement->supersedes_agreement_id ?? '') ?>"
                       placeholder="<?php echo __('Agreement ID if replacing another') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- Donor Selection -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><?php echo __('Donor Information') ?></h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label"><?php echo __('Select Donor') ?></label>
                <select id="donor-select" name="agreement[donor_id]" class="form-select">
                  <option value=""><?php echo __('-- Select a donor --') ?></option>
                  <?php foreach ($donors ?? [] as $d): ?>
                    <option value="<?php echo $d->id ?>" <?php echo (isset($agreement->donor_id) && $agreement->donor_id == $d->id) ? 'selected' : '' ?>>
                      <?php echo esc_entities($d->name) ?>
                    </option>
                  <?php endforeach ?>
                </select>
                <small class="text-muted"><?php echo __('Type to search or select from list') ?></small>
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Donor Name (Override)') ?></label>
                <input type="text" name="agreement[donor_name]" class="form-control"
                       value="<?php echo esc_entities($agreement->donor_name ?? '') ?>"
                       placeholder="<?php echo __('Use if different from linked donor') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Donor Contact Info') ?></label>
                <textarea name="agreement[donor_contact_info]" class="form-control" rows="2"><?php echo esc_entities($agreement->donor_contact_info ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Institution/Repository Information -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-building me-2"></i><?php echo __('Institution Information') ?></h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Institution Name') ?></label>
                <input type="text" name="agreement[institution_name]" class="form-control"
                       value="<?php echo esc_entities($agreement->institution_name ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Institution Contact Info') ?></label>
                <textarea name="agreement[institution_contact_info]" class="form-control" rows="2"><?php echo esc_entities($agreement->institution_contact_info ?? '') ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Repository Representative') ?></label>
                <input type="text" name="agreement[repository_representative]" class="form-control"
                       value="<?php echo esc_entities($agreement->repository_representative ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Representative Title') ?></label>
                <input type="text" name="agreement[repository_representative_title]" class="form-control"
                       value="<?php echo esc_entities($agreement->repository_representative_title ?? '') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- Legal Representative -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i><?php echo __('Legal Representative') ?></h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Name') ?></label>
                <input type="text" name="agreement[legal_representative]" class="form-control"
                       value="<?php echo esc_entities($agreement->legal_representative ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Title') ?></label>
                <input type="text" name="agreement[legal_representative_title]" class="form-control"
                       value="<?php echo esc_entities($agreement->legal_representative_title ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Contact') ?></label>
                <textarea name="agreement[legal_representative_contact]" class="form-control" rows="2"><?php echo esc_entities($agreement->legal_representative_contact ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Contract Document Upload Section -->
        <div class="card mb-4">
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i><?php echo __('Contract Documents') ?></h5>
            <button type="button" class="btn btn-sm btn-light" id="add-document-btn">
              <i class="fas fa-plus me-1"></i><?php echo __('Add More') ?>
            </button>
          </div>
          <div class="card-body">
            <?php if ($isEdit && !empty($documents)): ?>
              <div class="mb-4">
                <label class="form-label fw-bold"><?php echo __('Existing Documents') ?></label>
                <ul class="list-group">
                  <?php foreach ($documents as $doc): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <i class="fas fa-file-pdf me-2 text-danger"></i>
                        <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'downloadDocument', 'id' => $doc->id]) ?>" target="_blank">
                          <?php echo esc_entities($doc->original_filename) ?>
                        </a>
                        <small class="text-muted ms-2">(<?php echo number_format($doc->file_size / 1024, 1) ?> KB)</small>
                        <span class="badge bg-secondary ms-2"><?php echo esc_entities(ucfirst($doc->document_type)) ?></span>
                      </div>
                    </li>
                  <?php endforeach ?>
                </ul>
              </div>
              <hr>
              <label class="form-label fw-bold"><?php echo __('Upload New Documents') ?></label>
            <?php endif ?>

            <div id="document-entries">
              <div class="document-entry mb-3">
                <div class="card bg-light border">
                  <div class="card-body py-3">
                    <div class="row g-2 align-items-end">
                      <div class="col-md-3">
                        <label class="form-label small"><?php echo __('Document Type') ?></label>
                        <select name="document_types[]" class="form-select form-select-sm">
                          <option value="signed_agreement"><?php echo __('Signed Agreement') ?></option>
                          <option value="draft"><?php echo __('Draft') ?></option>
                          <option value="amendment"><?php echo __('Amendment') ?></option>
                          <option value="addendum"><?php echo __('Addendum') ?></option>
                          <option value="correspondence"><?php echo __('Correspondence') ?></option>
                          <option value="inventory"><?php echo __('Inventory List') ?></option>
                          <option value="provenance_evidence"><?php echo __('Provenance Evidence') ?></option>
                          <option value="other"><?php echo __('Other') ?></option>
                        </select>
                      </div>
                      <div class="col-md-5">
                        <label class="form-label small"><?php echo __('Select File') ?></label>
                        <input type="file" name="documents[]" class="form-control form-control-sm"
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.tif,.tiff">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small"><?php echo __('Description') ?></label>
                        <input type="text" name="document_descriptions[]" class="form-control form-control-sm"
                               placeholder="<?php echo __('Optional...') ?>">
                      </div>
                      <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-document-btn" title="<?php echo __('Remove') ?>">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="alert alert-info py-2 mb-0">
              <small><i class="fas fa-info-circle me-1"></i><?php echo __('Accepted formats: PDF, DOC, DOCX, JPG, PNG, TIFF. Maximum 20MB per file.') ?></small>
            </div>
          </div>
        </div>

        <!-- Linked Records -->
        <?php if ($isEdit): ?>
        <div class="card mb-4">
          <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i><?php echo __('Linked Records') ?></h5>
            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#linkedRecordsSection">
              <i class="fas fa-chevron-down"></i>
            </button>
          </div>
          <div class="collapse show" id="linkedRecordsSection">
            <div class="card-body">
              <?php if (!empty($linkedRecords)): ?>
                <div class="mb-3">
                  <label class="form-label fw-bold"><?php echo __('Currently Linked') ?></label>
                  <ul class="list-group mb-3">
                    <?php foreach ($linkedRecords as $rec): ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                          <i class="fas fa-archive me-2 text-muted"></i>
                          <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $rec->slug]) ?>" target="_blank">
                            <?php echo esc_entities($rec->title ?: $rec->identifier) ?>
                          </a>
                          <small class="text-muted ms-2">(<?php echo esc_entities($rec->identifier) ?>)</small>
                        </div>
                        <div>
                          <input type="checkbox" name="remove_records[]" value="<?php echo $rec->id ?>" class="form-check-input" title="<?php echo __('Remove') ?>">
                          <label class="form-check-label small text-danger"><?php echo __('Remove') ?></label>
                        </div>
                      </li>
                    <?php endforeach ?>
                  </ul>
                </div>
              <?php endif ?>
              <div class="row g-2 align-items-end">
                <div class="col-md-6">
                  <label class="form-label"><?php echo __('Link Archival Description') ?></label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="record-search" class="form-control" placeholder="<?php echo __('Search by title or identifier...') ?>" autocomplete="off" data-url="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'autocompleteRecords']) ?>">
                    <input type="hidden" name="new_record_id" id="new-record-id">
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label"><?php echo __('Relationship') ?></label>
                  <select name="new_record_relationship" id="new-record-relationship" class="form-select">
                    <option value="covers"><?php echo __('Covers') ?></option>
                    <option value="partially_covers"><?php echo __('Partially Covers') ?></option>
                    <option value="references"><?php echo __('References') ?></option>
                  </select>
                </div>
                <div class="col-md-2">
                  <button type="button" id="add-record-btn" class="btn btn-success w-100">
                    <i class="fas fa-plus me-1"></i><?php echo __('Add') ?>
                  </button>
                </div>
              </div>
              <div id="new-records-inputs"></div>
              <div id="new-records-display" class="mt-2"></div>
            </div>
          </div>
        </div>
        <?php endif ?>

        <!-- Linked Accessions -->
        <?php if ($isEdit): ?>
        <div class="card mb-4">
          <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-inbox me-2"></i><?php echo __('Linked Accessions') ?></h5>
            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#linkedAccessionsSection">
              <i class="fas fa-chevron-down"></i>
            </button>
          </div>
          <div class="collapse show" id="linkedAccessionsSection">
            <div class="card-body">
              <?php if (!empty($linkedAccessions)): ?>
                <div class="mb-3">
                  <label class="form-label fw-bold"><?php echo __('Currently Linked') ?></label>
                  <ul class="list-group mb-3">
                    <?php foreach ($linkedAccessions as $acc): ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                          <i class="fas fa-inbox me-2 text-muted"></i>
                          <a href="<?php echo url_for(['module' => 'accession', 'slug' => $acc->slug]) ?>" target="_blank">
                            <?php echo esc_entities($acc->title ?: $acc->identifier) ?>
                          </a>
                          <small class="text-muted ms-2">(<?php echo esc_entities($acc->identifier) ?>)</small>
                        </div>
                        <div>
                          <input type="checkbox" name="remove_accessions[]" value="<?php echo $acc->id ?>" class="form-check-input" title="<?php echo __('Remove') ?>">
                          <label class="form-check-label small text-danger"><?php echo __('Remove') ?></label>
                        </div>
                      </li>
                    <?php endforeach ?>
                  </ul>
                </div>
              <?php endif ?>
              <div class="row g-2 align-items-end">
                <div class="col-md-8">
                  <label class="form-label"><?php echo __('Link Accession') ?></label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="accession-search" class="form-control" placeholder="<?php echo __('Search by accession number or title...') ?>" autocomplete="off" data-url="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'autocompleteAccessions']) ?>">
                    <input type="hidden" name="new_accession_id" id="new-accession-id">
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="form-check">
                    <input type="checkbox" name="new_accession_primary" id="new-accession-primary" class="form-check-input">
                    <label class="form-check-label" for="new-accession-primary"><?php echo __('Primary') ?></label>
                  </div>
                </div>
                <div class="col-md-2">
                  <button type="button" id="add-accession-btn" class="btn btn-info w-100">
                    <i class="fas fa-plus me-1"></i><?php echo __('Add') ?>
                  </button>
                </div>
              </div>
              <div id="new-accessions-inputs"></div>
              <div id="new-accessions-display" class="mt-2"></div>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          <?php echo __('Save the agreement first, then edit to link Archival Descriptions and Accessions.') ?>
        </div>
        <?php endif ?>

        <!-- Scope & Transfer -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-boxes me-2"></i><?php echo __('Scope & Transfer') ?></h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label"><?php echo __('Scope Description') ?></label>
                <textarea name="agreement[scope_description]" class="form-control" rows="3"
                          placeholder="<?php echo __('Describe what materials are covered by this agreement') ?>"><?php echo esc_entities($agreement->scope_description ?? '') ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Extent Statement') ?></label>
                <input type="text" name="agreement[extent_statement]" class="form-control"
                       value="<?php echo esc_entities($agreement->extent_statement ?? '') ?>"
                       placeholder="<?php echo __('e.g., 5 boxes, 2.5 linear metres') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Transfer Method') ?></label>
                <select name="agreement[transfer_method]" class="form-select">
                  <option value=""><?php echo __('Select...') ?></option>
                  <option value="in_person" <?php echo ($agreement->transfer_method ?? '') == 'in_person' ? 'selected' : '' ?>><?php echo __('In Person') ?></option>
                  <option value="courier" <?php echo ($agreement->transfer_method ?? '') == 'courier' ? 'selected' : '' ?>><?php echo __('Courier') ?></option>
                  <option value="mail" <?php echo ($agreement->transfer_method ?? '') == 'mail' ? 'selected' : '' ?>><?php echo __('Mail/Post') ?></option>
                  <option value="digital_transfer" <?php echo ($agreement->transfer_method ?? '') == 'digital_transfer' ? 'selected' : '' ?>><?php echo __('Digital Transfer') ?></option>
                  <option value="pickup" <?php echo ($agreement->transfer_method ?? '') == 'pickup' ? 'selected' : '' ?>><?php echo __('Pickup by Repository') ?></option>
                  <option value="other" <?php echo ($agreement->transfer_method ?? '') == 'other' ? 'selected' : '' ?>><?php echo __('Other') ?></option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Transfer Date') ?></label>
                <input type="date" name="agreement[transfer_date]" class="form-control"
                       value="<?php echo isset($agreement->transfer_date) && $agreement->transfer_date ? date('Y-m-d', strtotime($agreement->transfer_date)) : '' ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Received By') ?></label>
                <input type="text" name="agreement[received_by]" class="form-control"
                       value="<?php echo esc_entities($agreement->received_by ?? '') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- Financial Terms -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i><?php echo __('Financial Terms') ?></h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12">
                <div class="form-check">
                  <input type="checkbox" name="agreement[has_financial_terms]" id="has_financial_terms" class="form-check-input" value="1"
                         <?php echo ($agreement->has_financial_terms ?? 0) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="has_financial_terms"><?php echo __('This agreement includes financial terms') ?></label>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Purchase Amount') ?></label>
                <input type="number" step="0.01" name="agreement[purchase_amount]" class="form-control"
                       value="<?php echo esc_entities($agreement->purchase_amount ?? '') ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label"><?php echo __('Currency') ?></label>
                <select name="agreement[currency]" class="form-select">
                  <option value="ZAR" <?php echo ($agreement->currency ?? 'ZAR') == 'ZAR' ? 'selected' : '' ?>>ZAR</option>
                  <option value="USD" <?php echo ($agreement->currency ?? '') == 'USD' ? 'selected' : '' ?>>USD</option>
                  <option value="EUR" <?php echo ($agreement->currency ?? '') == 'EUR' ? 'selected' : '' ?>>EUR</option>
                  <option value="GBP" <?php echo ($agreement->currency ?? '') == 'GBP' ? 'selected' : '' ?>>GBP</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Payment Terms') ?></label>
                <textarea name="agreement[payment_terms]" class="form-control" rows="2"><?php echo esc_entities($agreement->payment_terms ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Terms & Conditions -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-gavel me-2"></i><?php echo __('Terms & Conditions') ?></h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label"><?php echo __('General Terms') ?></label>
                <textarea name="agreement[general_terms]" class="form-control" rows="4"><?php echo esc_entities($agreement->general_terms ?? '') ?></textarea>
              </div>
              <div class="col-12">
                <label class="form-label"><?php echo __('Special Conditions') ?></label>
                <textarea name="agreement[special_conditions]" class="form-control" rows="3"
                          placeholder="<?php echo __('Any special conditions or restrictions') ?>"><?php echo esc_entities($agreement->special_conditions ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Important Dates -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i><?php echo __('Important Dates') ?></h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Agreement Date') ?></label>
                <input type="date" name="agreement[agreement_date]" class="form-control"
                       value="<?php echo isset($agreement->agreement_date) && $agreement->agreement_date ? date('Y-m-d', strtotime($agreement->agreement_date)) : '' ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Effective Date') ?></label>
                <input type="date" name="agreement[effective_date]" class="form-control"
                       value="<?php echo isset($agreement->effective_date) && $agreement->effective_date ? date('Y-m-d', strtotime($agreement->effective_date)) : '' ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Expiry Date') ?></label>
                <input type="date" name="agreement[expiry_date]" class="form-control"
                       value="<?php echo isset($agreement->expiry_date) && $agreement->expiry_date ? date('Y-m-d', strtotime($agreement->expiry_date)) : '' ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Review Date') ?></label>
                <input type="date" name="agreement[review_date]" class="form-control"
                       value="<?php echo isset($agreement->review_date) && $agreement->review_date ? date('Y-m-d', strtotime($agreement->review_date)) : '' ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Termination Date') ?></label>
                <input type="date" name="agreement[termination_date]" class="form-control"
                       value="<?php echo isset($agreement->termination_date) && $agreement->termination_date ? date('Y-m-d', strtotime($agreement->termination_date)) : '' ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Termination Reason') ?></label>
                <input type="text" name="agreement[termination_reason]" class="form-control"
                       value="<?php echo esc_entities($agreement->termination_reason ?? '') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- Signatures -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-signature me-2"></i><?php echo __('Signatures') ?></h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <h6 class="text-muted"><?php echo __('Donor Signature') ?></h6>
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label small"><?php echo __('Name') ?></label>
                    <input type="text" name="agreement[donor_signature_name]" class="form-control"
                           value="<?php echo esc_entities($agreement->donor_signature_name ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small"><?php echo __('Date') ?></label>
                    <input type="date" name="agreement[donor_signature_date]" class="form-control"
                           value="<?php echo isset($agreement->donor_signature_date) && $agreement->donor_signature_date ? date('Y-m-d', strtotime($agreement->donor_signature_date)) : '' ?>">
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <h6 class="text-muted"><?php echo __('Repository Signature') ?></h6>
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label small"><?php echo __('Name') ?></label>
                    <input type="text" name="agreement[repository_signature_name]" class="form-control"
                           value="<?php echo esc_entities($agreement->repository_signature_name ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small"><?php echo __('Date') ?></label>
                    <input type="date" name="agreement[repository_signature_date]" class="form-control"
                           value="<?php echo isset($agreement->repository_signature_date) && $agreement->repository_signature_date ? date('Y-m-d', strtotime($agreement->repository_signature_date)) : '' ?>">
                  </div>
                </div>
              </div>
              <div class="col-12">
                <h6 class="text-muted"><?php echo __('Witness') ?></h6>
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label small"><?php echo __('Witness Name') ?></label>
                    <input type="text" name="agreement[witness_name]" class="form-control"
                           value="<?php echo esc_entities($agreement->witness_name ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small"><?php echo __('Witness Date') ?></label>
                    <input type="date" name="agreement[witness_date]" class="form-control"
                           value="<?php echo isset($agreement->witness_date) && $agreement->witness_date ? date('Y-m-d', strtotime($agreement->witness_date)) : '' ?>">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Reminders -->
        <div class="card mb-4">
          <div class="card-header bg-warning d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-bell me-2"></i><?php echo __('Reminders') ?></h5>
            <button type="button" class="btn btn-sm btn-dark" id="add-reminder-btn">
              <i class="fas fa-plus me-1"></i><?php echo __('Add Reminder') ?>
            </button>
          </div>
          <div class="card-body">
            <?php if ($isEdit && !empty($reminders)): ?>
              <div class="mb-4">
                <label class="form-label fw-bold"><?php echo __('Existing Reminders') ?></label>
                <ul class="list-group mb-3">
                  <?php foreach ($reminders as $rem): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <span class="badge bg-<?php echo $rem->reminder_type == 'expiry_warning' ? 'danger' : ($rem->reminder_type == 'review_due' ? 'primary' : 'info') ?> me-2">
                          <?php echo esc_entities(ucwords(str_replace('_', ' ', $rem->reminder_type))) ?>
                        </span>
                        <?php echo esc_entities($rem->title) ?>
                        <small class="text-muted ms-2"><?php echo date('d M Y', strtotime($rem->reminder_date)) ?></small>
                      </div>
                      <div>
                        <input type="checkbox" name="delete_reminders[]" value="<?php echo $rem->id ?>" class="form-check-input">
                        <label class="form-check-label small text-danger"><?php echo __('Delete') ?></label>
                      </div>
                    </li>
                  <?php endforeach ?>
                </ul>
              </div>
              <hr>
            <?php endif ?>
            <div id="reminders-container">
              <div class="reminder-entry mb-3">
                <div class="row g-2 align-items-end">
                  <div class="col-md-2">
                    <label class="form-label small"><?php echo __('Type') ?></label>
                    <select name="reminders[0][reminder_type]" class="form-select form-select-sm">
                      <option value="review_due"><?php echo __('Review Due') ?></option>
                      <option value="expiry_warning"><?php echo __('Expiry Warning') ?></option>
                      <option value="renewal_required"><?php echo __('Renewal') ?></option>
                      <option value="donor_contact"><?php echo __('Follow-up') ?></option>
                      <option value="custom"><?php echo __('Custom') ?></option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label small"><?php echo __('Date') ?></label>
                    <input type="date" name="reminders[0][reminder_date]" class="form-control form-control-sm">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small"><?php echo __('Notify Email') ?></label>
                    <input type="email" name="reminders[0][notify_email]" class="form-control form-control-sm">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small"><?php echo __('Message') ?></label>
                    <input type="text" name="reminders[0][message]" class="form-control form-control-sm">
                  </div>
                  <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-reminder-btn">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <small class="text-muted"><i class="fas fa-info-circle me-1"></i><?php echo __('Leave date blank to skip.') ?></small>
          </div>
        </div>

        <!-- Internal Notes -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i><?php echo __('Internal Notes') ?></h5>
          </div>
          <div class="card-body">
            <textarea name="agreement[internal_notes]" class="form-control" rows="4"
                      placeholder="<?php echo __('Notes for internal use only - not visible to donors') ?>"><?php echo esc_entities($agreement->internal_notes ?? '') ?></textarea>
          </div>
        </div>

      </div>

      <!-- Sidebar -->
      <div class="col-lg-4">
        <div class="card mb-4 sticky-top" style="top: 1rem;">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-cog me-2"></i><?php echo __('Status & Actions') ?></h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label"><?php echo __('Status') ?></label>
              <select name="agreement[status]" class="form-select">
                <?php foreach ($statuses as $key => $label): ?>
                  <option value="<?php echo $key ?>" <?php echo ($agreement->status ?? 'draft') == $key ? 'selected' : '' ?>>
                    <?php echo __($label) ?>
                  </option>
                <?php endforeach ?>
              </select>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-save me-2"></i><?php echo $isEdit ? __('Update Agreement') : __('Create Agreement') ?>
              </button>
              <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'browse']) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i><?php echo __('Cancel') ?>
              </a>
            </div>
          </div>
          <?php if ($isEdit): ?>
          <div class="card-footer">
            <small class="text-muted">
              <?php echo __('Created') ?>: <?php echo $agreement->created_at ?? 'N/A' ?><br>
              <?php echo __('Updated') ?>: <?php echo $agreement->updated_at ?? 'N/A' ?>
            </small>
          </div>
          <?php endif ?>
        </div>
      </div>
    </div>
  </form>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Document Upload Management
    var docContainer = document.getElementById('document-entries');
    document.getElementById('add-document-btn').addEventListener('click', function() {
        var newEntry = document.createElement('div');
        newEntry.className = 'document-entry mb-3';
        newEntry.innerHTML = '<div class="card bg-light border"><div class="card-body py-3"><div class="row g-2 align-items-end"><div class="col-md-3"><label class="form-label small"><?php echo __('Document Type') ?></label><select name="document_types[]" class="form-select form-select-sm"><option value="signed_agreement"><?php echo __('Signed Agreement') ?></option><option value="draft"><?php echo __('Draft') ?></option><option value="amendment"><?php echo __('Amendment') ?></option><option value="addendum"><?php echo __('Addendum') ?></option><option value="correspondence"><?php echo __('Correspondence') ?></option><option value="inventory"><?php echo __('Inventory List') ?></option><option value="provenance_evidence"><?php echo __('Provenance Evidence') ?></option><option value="other"><?php echo __('Other') ?></option></select></div><div class="col-md-5"><label class="form-label small"><?php echo __('Select File') ?></label><input type="file" name="documents[]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.tif,.tiff"></div><div class="col-md-3"><label class="form-label small"><?php echo __('Description') ?></label><input type="text" name="document_descriptions[]" class="form-control form-control-sm"></div><div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100 remove-document-btn"><i class="fas fa-times"></i></button></div></div></div></div>';
        docContainer.appendChild(newEntry);
    });
    docContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-document-btn')) {
            var entry = e.target.closest('.document-entry');
            if (docContainer.querySelectorAll('.document-entry').length > 1) entry.remove();
        }
    });

    // Reminder Management
    var reminderContainer = document.getElementById('reminders-container');
    var reminderIndex = 1;
    document.getElementById('add-reminder-btn').addEventListener('click', function() {
        var newReminder = document.createElement('div');
        newReminder.className = 'reminder-entry mb-3';
        newReminder.innerHTML = '<div class="row g-2 align-items-end"><div class="col-md-2"><select name="reminders['+reminderIndex+'][reminder_type]" class="form-select form-select-sm"><option value="review_due"><?php echo __('Review Due') ?></option><option value="expiry_warning"><?php echo __('Expiry Warning') ?></option><option value="renewal_required"><?php echo __('Renewal') ?></option><option value="donor_contact"><?php echo __('Follow-up') ?></option><option value="custom"><?php echo __('Custom') ?></option></select></div><div class="col-md-2"><input type="date" name="reminders['+reminderIndex+'][reminder_date]" class="form-control form-control-sm"></div><div class="col-md-3"><input type="email" name="reminders['+reminderIndex+'][notify_email]" class="form-control form-control-sm"></div><div class="col-md-4"><input type="text" name="reminders['+reminderIndex+'][message]" class="form-control form-control-sm"></div><div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100 remove-reminder-btn"><i class="fas fa-times"></i></button></div></div>';
        reminderContainer.appendChild(newReminder);
        reminderIndex++;
    });
    reminderContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-reminder-btn')) {
            var entry = e.target.closest('.reminder-entry');
            if (reminderContainer.querySelectorAll('.reminder-entry').length > 1) entry.remove();
        }
    });

    // Donor Search Autocomplete
    var searchInput = document.getElementById('donor-search');
    var donorIdInput = document.getElementById('donor-id');
    var resultsDiv = document.getElementById('donor-results');
    if (searchInput) {
        var searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            var query = this.value.trim();
            if (query.length < 2) { resultsDiv.style.display = 'none'; return; }
            searchTimeout = setTimeout(function() {
                fetch('<?php echo url_for(['module' => 'donor', 'action' => 'autocomplete']) ?>?query=' + encodeURIComponent(query))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        resultsDiv.innerHTML = '';
                        if (data.length === 0) {
                            resultsDiv.innerHTML = '<div class="list-group-item text-muted"><?php echo __('No donors found') ?></div>';
                        } else {
                            data.forEach(function(d) {
                                var item = document.createElement('a');
                                item.href = '#';
                                item.className = 'list-group-item list-group-item-action';
                                item.textContent = d.name;
                                item.dataset.id = d.id;
                                item.onclick = function(e) {
                                    e.preventDefault();
                                    searchInput.value = d.name;
                                    donorIdInput.value = d.id;
                                    resultsDiv.style.display = 'none';
                                };
                                resultsDiv.appendChild(item);
                            });
                        }
                        resultsDiv.style.display = 'block';
                    });
            }, 300);
        });
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) resultsDiv.style.display = 'none';
        });
    }
});
</script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var selectedRecordId = null, selectedRecordTitle = '', selectedAccessionId = null, selectedAccessionTitle = '';
    var newRecordCount = 0, newAccessionCount = 0;

    var recordInput = document.getElementById('record-search');
    if (recordInput) {
        var recordTimeout;
        recordInput.addEventListener('input', function() {
            clearTimeout(recordTimeout);
            var q = this.value.trim();
            if (q.length < 2) return;
            recordTimeout = setTimeout(function() {
                fetch(recordInput.dataset.url + '?query=' + encodeURIComponent(q))
                    .then(r => r.json()).then(data => showResults(data, recordInput, 'record'));
            }, 300);
        });
    }

    var accessionInput = document.getElementById('accession-search');
    if (accessionInput) {
        var accessionTimeout;
        accessionInput.addEventListener('input', function() {
            clearTimeout(accessionTimeout);
            var q = this.value.trim();
            if (q.length < 2) return;
            accessionTimeout = setTimeout(function() {
                fetch(accessionInput.dataset.url + '?query=' + encodeURIComponent(q))
                    .then(r => r.json()).then(data => showResults(data, accessionInput, 'accession'));
            }, 300);
        });
    }

    function showResults(data, input, type) {
        var container = document.createElement('div');
        container.className = 'list-group position-absolute w-100 shadow';
        container.style.zIndex = '1050';
        container.style.maxHeight = '200px';
        container.style.overflowY = 'auto';
        var results = data.results || data;
        results.forEach(function(item) {
            var a = document.createElement('a');
            a.href = '#';
            a.className = 'list-group-item list-group-item-action';
            a.innerHTML = '<i class="fas fa-'+(type=='record'?'archive':'inbox')+' me-2"></i>' + (item.title || item.identifier);
            a.onclick = function(e) {
                e.preventDefault();
                if (type == 'record') { selectedRecordId = item.id; selectedRecordTitle = item.title || item.identifier; }
                else { selectedAccessionId = item.id; selectedAccessionTitle = item.title || item.identifier; }
                input.value = item.title || item.identifier;
                container.remove();
            };
            container.appendChild(a);
        });
        var parent = input.parentElement;
        parent.querySelectorAll('.list-group').forEach(el => el.remove());
        parent.style.position = 'relative';
        parent.appendChild(container);
    }

    var addRecordBtn = document.getElementById('add-record-btn');
    if (addRecordBtn) {
        addRecordBtn.addEventListener('click', function() {
            if (!selectedRecordId) return;
            var rel = document.getElementById('new-record-relationship').value;
            newRecordCount++;
            document.getElementById('new-records-inputs').innerHTML += '<input type="hidden" name="link_records['+newRecordCount+'][id]" value="'+selectedRecordId+'"><input type="hidden" name="link_records['+newRecordCount+'][relationship]" value="'+rel+'">';
            document.getElementById('new-records-display').innerHTML += '<span class="badge bg-success me-2 mb-1">'+selectedRecordTitle+' <i class="fas fa-check"></i></span>';
            selectedRecordId = null; selectedRecordTitle = ''; recordInput.value = '';
        });
    }

    var addAccessionBtn = document.getElementById('add-accession-btn');
    if (addAccessionBtn) {
        addAccessionBtn.addEventListener('click', function() {
            if (!selectedAccessionId) return;
            var isPrimary = document.getElementById('new-accession-primary').checked ? '1' : '0';
            newAccessionCount++;
            document.getElementById('new-accessions-inputs').innerHTML += '<input type="hidden" name="link_accessions['+newAccessionCount+'][id]" value="'+selectedAccessionId+'"><input type="hidden" name="link_accessions['+newAccessionCount+'][primary]" value="'+isPrimary+'">';
            document.getElementById('new-accessions-display').innerHTML += '<span class="badge bg-info me-2 mb-1">'+selectedAccessionTitle+' <i class="fas fa-check"></i></span>';
            selectedAccessionId = null; selectedAccessionTitle = ''; accessionInput.value = ''; document.getElementById('new-accession-primary').checked = false;
        });
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#record-search') && !e.target.closest('#accession-search') && !e.target.closest('.list-group')) {
            document.querySelectorAll('.list-group').forEach(function(el) { if (el.closest('.input-group')) el.remove(); });
        }
    });
});
</script>
<script src="/plugins/ahgThemeB5Plugin/js/tom-select.complete.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var donorSelect = document.getElementById('donor-select');
    if (donorSelect) {
        new TomSelect(donorSelect, {
            placeholder: '<?php echo __("Type to search donors...") ?>',
            allowEmptyOption: true,
            create: false
        });
    }
});
</script>
