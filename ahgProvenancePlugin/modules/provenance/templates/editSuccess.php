<?php use_helper('Date') ?>

<div class="container-fluid py-3">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resource->slug]) ?>"><?php echo $resource->title ?? $resource->slug ?></a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'provenance', 'action' => 'view', 'slug' => $resource->slug]) ?>">Provenance</a></li>
      <li class="breadcrumb-item active">Edit</li>
    </ol>
  </nav>

  <form method="post" id="provenanceForm" enctype="multipart/form-data">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1"><i class="bi bi-clock-history me-2"></i>Edit Provenance</h4>
        <p class="text-muted mb-0"><?php echo $resource->title ?? $resource->slug ?></p>
      </div>
      <div>
        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resource->slug]) ?>" class="btn btn-outline-primary me-2"><i class="bi bi-arrow-left me-1"></i>Back to Record</a>
        <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'view', 'slug' => $resource->slug]) ?>" class="btn btn-outline-secondary me-2">Cancel</a>
        <button type="submit" class="btn btn-success">
          <i class="bi bi-check-lg me-1"></i> Save Provenance
        </button>
      </div>
    </div>

    <?php $record = $provenance['record']; ?>

    <div class="row">
      <!-- Main Form -->
      <div class="col-lg-8">
        
        <!-- Provenance Summary -->
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Provenance Summary</h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Provenance Statement</label>
              <textarea name="provenance_summary" class="form-control" rows="4" placeholder="Enter a human-readable summary of the item's provenance..."><?php echo htmlspecialchars($record->provenance_summary ?? $record->summary_i18n ?? '') ?></textarea>
              <small class="text-muted">This summary will be displayed publicly. Leave blank to auto-generate from events.</small>
            </div>
          </div>
        </div>

        <!-- Acquisition Details -->
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-cart-check me-2"></i>Acquisition Details</h6>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Acquisition Type</label>
                <select name="acquisition_type" class="form-select">
                  <?php foreach ($acquisitionTypes as $value => $label): ?>
                  <option value="<?php echo $value ?>" <?php echo ($record->acquisition_type ?? '') === $value ? 'selected' : '' ?>><?php echo $label ?></option>
                  <?php endforeach ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Acquisition Date</label>
                <input type="date" name="acquisition_date" class="form-control" value="<?php echo $record->acquisition_date ?? '' ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Date (Text)</label>
                <input type="text" name="acquisition_date_text" class="form-control" placeholder="e.g., circa 1950" value="<?php echo htmlspecialchars($record->acquisition_date_text ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Price</label>
                <input type="number" name="acquisition_price" class="form-control" step="0.01" value="<?php echo $record->acquisition_price ?? '' ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Currency</label>
                <select name="acquisition_currency" class="form-select">
                  <option value="">-- Select --</option>
                  <option value="ZAR" <?php echo ($record->acquisition_currency ?? '') === 'ZAR' ? 'selected' : '' ?>>ZAR - South African Rand</option>
                  <option value="USD" <?php echo ($record->acquisition_currency ?? '') === 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                  <option value="GBP" <?php echo ($record->acquisition_currency ?? '') === 'GBP' ? 'selected' : '' ?>>GBP - British Pound</option>
                  <option value="EUR" <?php echo ($record->acquisition_currency ?? '') === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Acquisition Notes</label>
                <textarea name="acquisition_notes" class="form-control" rows="2"><?php echo htmlspecialchars($record->acquisition_notes ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Chain of Custody Events -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Chain of Custody Events</h6>
            <button type="button" class="btn btn-sm btn-success" id="addEventBtn">
              <i class="bi bi-plus-lg me-1"></i> Add Event
            </button>
          </div>
          <div class="card-body" id="eventsContainer">
            <?php if (!empty($provenance['events'])): ?>
              <?php foreach ($provenance['events'] as $i => $event): ?>
              <div class="event-entry card bg-light mb-3">
                <div class="card-body">
                  <div class="row g-2">
                    <div class="col-md-3">
                      <label class="form-label small">Event Type</label>
                      <select name="event_type[]" class="form-select form-select-sm">
                        <?php foreach ($eventTypes as $group => $types): ?>
                        <optgroup label="<?php echo $group ?>">
                          <?php foreach ($types as $value => $label): ?>
                          <option value="<?php echo $value ?>" <?php echo $event->event_type === $value ? 'selected' : '' ?>><?php echo $label ?></option>
                          <?php endforeach ?>
                        </optgroup>
                        <?php endforeach ?>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label small">Date</label>
                      <input type="date" name="event_date[]" class="form-control form-control-sm" value="<?php echo $event->event_date ?? '' ?>">
                    </div>
                    <div class="col-md-2">
                      <label class="form-label small">Date Text</label>
                      <input type="text" name="event_date_text[]" class="form-control form-control-sm" placeholder="circa 1920" value="<?php echo htmlspecialchars($event->event_date_text ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                      <label class="form-label small">Certainty</label>
                      <select name="event_certainty[]" class="form-select form-select-sm">
                        <option value="certain" <?php echo ($event->certainty ?? '') === 'certain' ? 'selected' : '' ?>>Certain</option>
                        <option value="probable" <?php echo ($event->certainty ?? '') === 'probable' ? 'selected' : '' ?>>Probable</option>
                        <option value="possible" <?php echo ($event->certainty ?? '') === 'possible' ? 'selected' : '' ?>>Possible</option>
                        <option value="uncertain" <?php echo ($event->certainty ?? '') === 'uncertain' ? 'selected' : '' ?>>Uncertain</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label small">From (Agent)</label>
                      <input type="text" name="from_agent[]" class="form-control form-control-sm agent-autocomplete" placeholder="Previous owner..." value="<?php echo htmlspecialchars($event->from_agent_name ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label small">To (Agent)</label>
                      <input type="text" name="to_agent[]" class="form-control form-control-sm agent-autocomplete" placeholder="New owner..." value="<?php echo htmlspecialchars($event->to_agent_name ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label small">Location</label>
                      <input type="text" name="event_location[]" class="form-control form-control-sm" placeholder="City, Country" value="<?php echo htmlspecialchars($event->event_location ?? '') ?>">
                    </div>
                    <div class="col-md-5">
                      <label class="form-label small">Notes</label>
                      <input type="text" name="event_notes[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($event->notes ?? $event->notes_i18n ?? '') ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                      <button type="button" class="btn btn-sm btn-outline-danger remove-event-btn w-100">
                        X
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <?php endforeach ?>
            <?php endif ?>
          </div>
        </div>

        <!-- Research Notes -->
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i>Research Notes</h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Research Status</label>
              <select name="research_status" class="form-select">
                <option value="not_started" <?php echo ($record->research_status ?? '') === 'not_started' ? 'selected' : '' ?>>Not Started</option>
                <option value="in_progress" <?php echo ($record->research_status ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="complete" <?php echo ($record->research_status ?? '') === 'complete' ? 'selected' : '' ?>>Complete</option>
                <option value="inconclusive" <?php echo ($record->research_status ?? '') === 'inconclusive' ? 'selected' : '' ?>>Inconclusive</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Research Notes</label>
              <textarea name="research_notes" class="form-control" rows="3" placeholder="Document your research findings, sources consulted, etc."><?php echo htmlspecialchars($record->research_notes ?? $record->research_notes_i18n ?? '') ?></textarea>
            </div>
            <div class="form-check mb-3">
              <input type="checkbox" name="has_gaps" class="form-check-input" id="hasGaps" value="1" <?php echo ($record->has_gaps ?? 0) ? 'checked' : '' ?>>
              <label class="form-check-label" for="hasGaps">There are gaps in the provenance chain</label>
            </div>
            <div class="mb-0" id="gapDescriptionGroup" style="<?php echo ($record->has_gaps ?? 0) ? '' : 'display:none' ?>">
              <label class="form-label">Gap Description</label>
              <textarea name="gap_description" class="form-control" rows="2" placeholder="Describe the gaps in provenance..."><?php echo htmlspecialchars($record->gap_description ?? '') ?></textarea>
            </div>
          </div>
        </div>


        <!-- Supporting Documents -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-file-earmark me-2"></i>Supporting Documents</h6>
            <button type="button" class="btn btn-sm btn-success" id="addDocumentBtn">
              <i class="bi bi-plus me-1"></i>Add Document
            </button>
          </div>
          <div class="card-body">
            <!-- Existing Documents -->
            <?php if (!empty($documents)): ?>
            <div class="mb-3">
              <label class="form-label text-muted small">Existing Documents</label>
              <?php foreach ($documents as $doc): ?>
              <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
                <div>
                  <i class="bi bi-file-earmark me-2"></i>
                  <strong><?php echo htmlspecialchars($doc->title ?: $doc->original_filename) ?></strong>
                  <span class="badge bg-secondary ms-2"><?php echo ucfirst(str_replace('_', ' ', $doc->document_type)) ?></span>
                </div>
                <div>
                  <?php if ($doc->file_path): ?>
                  <a href="<?php echo $doc->file_path ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="bi bi-download"></i> View</a>
                  <?php endif ?>
                  <button type="button" class="btn btn-sm btn-outline-danger delete-doc-btn" data-doc-id="<?php echo $doc->id ?>"><i class="bi bi-trash"></i> Delete</button>
                </div>
              </div>
              <?php endforeach ?>
            </div>
            <?php endif ?>
            
            <!-- New Documents Container -->
            <div id="documentsContainer"></div>
            <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Click "Add Document" to add supporting documents. Documents will be uploaded when you save the form.</p>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="col-lg-4">
        
        <!-- Status -->
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-sliders me-2"></i>Status</h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Current Status</label>
              <select name="current_status" class="form-select">
                <option value="owned" <?php echo ($record->current_status ?? '') === 'owned' ? 'selected' : '' ?>>Owned</option>
                <option value="on_loan" <?php echo ($record->current_status ?? '') === 'on_loan' ? 'selected' : '' ?>>On Loan</option>
                <option value="deposited" <?php echo ($record->current_status ?? '') === 'deposited' ? 'selected' : '' ?>>Deposited</option>
                <option value="unknown" <?php echo ($record->current_status ?? '') === 'unknown' ? 'selected' : '' ?>>Unknown</option>
                <option value="disputed" <?php echo ($record->current_status ?? '') === 'disputed' ? 'selected' : '' ?>>Disputed</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Custody Type</label>
              <select name="custody_type" class="form-select">
                <option value="permanent" <?php echo ($record->custody_type ?? '') === 'permanent' ? 'selected' : '' ?>>Permanent</option>
                <option value="temporary" <?php echo ($record->custody_type ?? '') === 'temporary' ? 'selected' : '' ?>>Temporary</option>
                <option value="loan" <?php echo ($record->custody_type ?? '') === 'loan' ? 'selected' : '' ?>>Loan</option>
                <option value="deposit" <?php echo ($record->custody_type ?? '') === 'deposit' ? 'selected' : '' ?>>Deposit</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Certainty Level</label>
              <select name="certainty_level" class="form-select">
                <?php foreach ($certaintyLevels as $value => $label): ?>
                <option value="<?php echo $value ?>" <?php echo ($record->certainty_level ?? '') === $value ? 'selected' : '' ?>><?php echo $label ?></option>
                <?php endforeach ?>
              </select>
            </div>
            <div class="form-check mb-2">
              <input type="checkbox" name="is_complete" class="form-check-input" id="isComplete" value="1" <?php echo ($record->is_complete ?? 0) ? 'checked' : '' ?>>
              <label class="form-check-label" for="isComplete">Provenance research is complete</label>
            </div>
            <div class="form-check">
              <input type="checkbox" name="is_public" class="form-check-input" id="isPublic" value="1" <?php echo ($record->is_public ?? 1) ? 'checked' : '' ?>>
              <label class="form-check-label" for="isPublic">Display provenance publicly</label>
            </div>
          </div>
        </div>

        <!-- Current Owner -->
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-person me-2"></i>Current Owner/Holder</h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Name</label>
              <input type="text" name="current_agent_name" class="form-control agent-autocomplete" value="<?php echo htmlspecialchars($record->current_agent_name ?? '') ?>">
            </div>
            <div class="mb-0">
              <label class="form-label">Type</label>
              <select name="current_agent_type" class="form-select">
                <option value="person" <?php echo ($record->current_agent_type ?? '') === 'person' ? 'selected' : '' ?>>Person</option>
                <option value="organization" <?php echo ($record->current_agent_type ?? '') === 'organization' ? 'selected' : '' ?>>Organization</option>
                <option value="family" <?php echo ($record->current_agent_type ?? '') === 'family' ? 'selected' : '' ?>>Family</option>
                <option value="unknown" <?php echo ($record->current_agent_type ?? '') === 'unknown' ? 'selected' : '' ?>>Unknown</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Nazi-Era Provenance -->
        <div class="card mb-4">
          <div class="card-header bg-warning">
            <h6 class="mb-0"><i class="bi bi-shield-check me-2"></i>Nazi-Era Provenance</h6>
          </div>
          <div class="card-body">
            <div class="form-check mb-3">
              <input type="checkbox" name="nazi_era_provenance_checked" class="form-check-input" id="naziEraChecked" value="1" <?php echo ($record->nazi_era_provenance_checked ?? 0) ? 'checked' : '' ?>>
              <label class="form-check-label" for="naziEraChecked">Nazi-era provenance has been checked</label>
            </div>
            <div id="naziEraClearGroup" style="<?php echo ($record->nazi_era_provenance_checked ?? 0) ? '' : 'display:none' ?>">
              <div class="mb-3">
                <label class="form-label">Result</label>
                <select name="nazi_era_provenance_clear" class="form-select">
                  <option value="">-- Select --</option>
                  <option value="1" <?php echo ($record->nazi_era_provenance_clear ?? '') === '1' ? 'selected' : '' ?>>Clear - No issues found</option>
                  <option value="0" <?php echo ($record->nazi_era_provenance_clear ?? '') === '0' ? 'selected' : '' ?>>Requires investigation</option>
                </select>
              </div>
              <div class="mb-0">
                <label class="form-label">Notes</label>
                <textarea name="nazi_era_notes" class="form-control" rows="2"><?php echo htmlspecialchars($record->nazi_era_notes ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Cultural Property -->
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-globe me-2"></i>Cultural Property</h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="cultural_property_status" class="form-select">
                <option value="none" <?php echo ($record->cultural_property_status ?? '') === 'none' ? 'selected' : '' ?>>None / Not Applicable</option>
                <option value="claimed" <?php echo ($record->cultural_property_status ?? '') === 'claimed' ? 'selected' : '' ?>>Claimed</option>
                <option value="disputed" <?php echo ($record->cultural_property_status ?? '') === 'disputed' ? 'selected' : '' ?>>Disputed</option>
                <option value="repatriated" <?php echo ($record->cultural_property_status ?? '') === 'repatriated' ? 'selected' : '' ?>>Repatriated</option>
                <option value="cleared" <?php echo ($record->cultural_property_status ?? '') === 'cleared' ? 'selected' : '' ?>>Cleared</option>
              </select>
            </div>
            <div class="mb-0">
              <label class="form-label">Notes</label>
              <textarea name="cultural_property_notes" class="form-control" rows="2"><?php echo htmlspecialchars($record->cultural_property_notes ?? '') ?></textarea>
            </div>
          </div>
        </div>

      </div>
    </div>
  </form>
</div>

<!-- Event Template -->
<template id="eventTemplate">
  <div class="event-entry card bg-light mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-3">
          <label class="form-label small">Event Type</label>
          <select name="event_type[]" class="form-select form-select-sm">
            <?php foreach ($eventTypes as $group => $types): ?>
            <optgroup label="<?php echo $group ?>">
              <?php foreach ($types as $value => $label): ?>
              <option value="<?php echo $value ?>"><?php echo $label ?></option>
              <?php endforeach ?>
            </optgroup>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small">Date</label>
          <input type="date" name="event_date[]" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <label class="form-label small">Date Text</label>
          <input type="text" name="event_date_text[]" class="form-control form-control-sm" placeholder="circa 1920">
        </div>
        <div class="col-md-2">
          <label class="form-label small">Certainty</label>
          <select name="event_certainty[]" class="form-select form-select-sm">
            <option value="certain">Certain</option>
            <option value="probable">Probable</option>
            <option value="possible">Possible</option>
            <option value="uncertain" selected>Uncertain</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">From (Agent)</label>
          <input type="text" name="from_agent[]" class="form-control form-control-sm agent-autocomplete" placeholder="Previous owner...">
        </div>
        <div class="col-md-3">
          <label class="form-label small">To (Agent)</label>
          <input type="text" name="to_agent[]" class="form-control form-control-sm agent-autocomplete" placeholder="New owner...">
        </div>
        <div class="col-md-3">
          <label class="form-label small">Location</label>
          <input type="text" name="event_location[]" class="form-control form-control-sm" placeholder="City, Country">
        </div>
        <div class="col-md-5">
          <label class="form-label small">Notes</label>
          <input type="text" name="event_notes[]" class="form-control form-control-sm">
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button type="button" class="btn btn-sm btn-outline-danger remove-event-btn w-100">
            X
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<template id="documentTemplate">
  <div class="document-entry border rounded p-3 mb-2">
    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label small">Document Type</label>
        <select name="doc_type[]" class="form-select form-select-sm">
          <option value="deed_of_gift">Deed of Gift</option>
          <option value="bill_of_sale">Bill of Sale</option>
          <option value="invoice">Invoice</option>
          <option value="receipt">Receipt</option>
          <option value="auction_catalog">Auction Catalog</option>
          <option value="exhibition_catalog">Exhibition Catalog</option>
          <option value="inventory">Inventory</option>
          <option value="insurance_record">Insurance Record</option>
          <option value="photograph">Photograph</option>
          <option value="correspondence">Correspondence</option>
          <option value="certificate">Certificate</option>
          <option value="customs_document">Customs Document</option>
          <option value="export_license">Export License</option>
          <option value="import_permit">Import Permit</option>
          <option value="appraisal">Appraisal</option>
          <option value="condition_report">Condition Report</option>
          <option value="newspaper_clipping">Newspaper Clipping</option>
          <option value="publication">Publication</option>
          <option value="oral_history">Oral History</option>
          <option value="affidavit">Affidavit</option>
          <option value="legal_document">Legal Document</option>
          <option value="other" selected>Other</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small">Title</label>
        <input type="text" name="doc_title[]" class="form-control form-control-sm" placeholder="Document title...">
      </div>
      <div class="col-md-3">
        <label class="form-label small">Date</label>
        <input type="date" name="doc_date[]" class="form-control form-control-sm">
      </div>
      <div class="col-md-1 d-flex align-items-end">
        <button type="button" class="btn btn-sm btn-outline-danger remove-doc-btn w-100">
          X
        </button>
      </div>
      <div class="col-md-6">
        <label class="form-label small">File Upload</label>
        <input type="file" name="doc_file[]" class="form-control form-control-sm">
      </div>
      <div class="col-md-6">
        <label class="form-label small">Or External URL</label>
        <input type="text" name="doc_url[]" class="form-control form-control-sm" placeholder="https://...">
      </div>
      <div class="col-12">
        <label class="form-label small">Description</label>
        <input type="text" name="doc_description[]" class="form-control form-control-sm" placeholder="Brief description...">
      </div>
    </div>
  </div>
</template>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  // Add event
  document.getElementById('addEventBtn').addEventListener('click', function() {
    var template = document.getElementById('eventTemplate');
    var clone = template.content.cloneNode(true);
    document.getElementById('eventsContainer').appendChild(clone);
  });

  // Remove event
  document.getElementById('eventsContainer').addEventListener('click', function(e) {
    if (e.target.closest('.remove-event-btn')) {
      e.target.closest('.event-entry').remove();
    }
  });

  // Toggle gap description
  document.getElementById('hasGaps').addEventListener('change', function() {
    document.getElementById('gapDescriptionGroup').style.display = this.checked ? '' : 'none';
  });

  // Toggle Nazi-era clear
  document.getElementById('naziEraChecked').addEventListener('change', function() {
    document.getElementById('naziEraClearGroup').style.display = this.checked ? '' : 'none';
  });

  // Add document
  document.getElementById('addDocumentBtn').addEventListener('click', function() {
    var template = document.getElementById('documentTemplate');
    var clone = template.content.cloneNode(true);
    document.getElementById('documentsContainer').appendChild(clone);
  });

  // Remove document
  document.getElementById('documentsContainer').addEventListener('click', function(e) {
    if (e.target.closest('.remove-doc-btn')) {
      e.target.closest('.document-entry').remove();
    }
  });

  // Delete existing document
  document.querySelectorAll('.delete-doc-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (confirm('Delete this document?')) {
        var docId = this.dataset.docId;
        fetch('/index.php/provenance/deleteDocument/' + docId, { method: 'POST' })
          .then(function() { btn.closest('.d-flex').remove(); });
      }
    });
  });
});
</script>
