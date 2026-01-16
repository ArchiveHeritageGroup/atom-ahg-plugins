<?php use_helper('Date') ?>
<?php
$rawMappings = $sf_data->getRaw('savedMappings');
if (!is_array($rawMappings)) $rawMappings = [];
?>

<div class="container-fluid py-3">
  <!-- Header -->
  <div class="row mb-3">
    <div class="col">
      <h4>⇄ Field Mapping: <?php echo htmlspecialchars($filename) ?></h4>
    </div>
  </div>

  <!-- Stats Cards -->
  <div class="row g-2 mb-3">
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body py-2">
          <h6 class="text-muted mb-1">📄 File</h6>
          <strong><?php echo count($detection['rows'] ?? []) ?> rows</strong>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body py-2">
          <h6 class="text-muted mb-1">🎯 Target</h6>
          <strong><?php echo htmlspecialchars($targetTypeLabels[$targetType] ?? $targetType) ?></strong>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body py-2">
          <h6 class="text-muted mb-1">📋 Source Fields</h6>
          <strong><?php echo count($sourceFields) ?> columns</strong>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body py-2">
          <h6 class="text-muted mb-1">⚙ Output Mode</h6>
          <select id="outputMode" class="form-select form-select-sm">
            <option value="preview">Preview Only</option>
            <option value="import">Import to Database</option>
            <option value="csv">Export to AtoM CSV</option>
            <option value="ead">Export to EAD 2002</option>
          </select>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body py-2">
          <h6 class="text-muted mb-1">🎯 Target Sector (for CSV)</h6>
          <select id="targetSector" class="form-select form-select-sm">
            <option value="archives">Archives (ISAD-G)</option>
            <option value="museum">Museum (Spectrum)</option>
            <option value="library">Library (MARC)</option>
            <option value="gallery">Gallery (CCO)</option>
            <option value="dam">DAM (Dublin Core)</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Data Preview Section -->
  <?php $rawDetection = $sf_data->getRaw('detection'); ?>
  <?php $rawSourceFields = $sf_data->getRaw('sourceFields'); ?>
  <div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-info text-white">
      <h6 class="mb-0"><i class="bi bi-eye"></i> Source Data Preview</h6>
      <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#dataPreviewCollapse">
        Toggle Preview
      </button>
    </div>
    <div class="collapse show" id="dataPreviewCollapse">
      <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
          <table class="table table-sm table-bordered table-striped mb-0" style="font-size: 0.75rem;">
            <thead class="table-dark sticky-top">
              <tr>
                <th class="text-center" style="width: 40px;">#</th>
                <?php foreach ($rawSourceFields as $field): ?>
                <th class="text-nowrap"><?php echo htmlspecialchars($field) ?></th>
                <?php endforeach ?>
              </tr>
            </thead>
            <tbody>
              <?php 
              $previewRows = array_slice($rawDetection['rows'] ?? [], 0, 5);
              foreach ($previewRows as $rowIndex => $row): 
              ?>
              <tr>
                <td class="text-center text-muted"><?php echo $rowIndex + 1 ?></td>
                <?php foreach ($rawSourceFields as $colIndex => $field): ?>
                <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($row[$colIndex] ?? '') ?>">
                  <?php echo htmlspecialchars(mb_substr($row[$colIndex] ?? '', 0, 80)) ?>
                  <?php if (strlen($row[$colIndex] ?? '') > 80): ?>...<?php endif ?>
                </td>
                <?php endforeach ?>
              </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer py-1 small text-muted">
          Showing first <?php echo count($previewRows) ?> of <?php echo count($rawDetection['rows'] ?? []) ?> rows
        </div>
      </div>
    </div>
  </div>

  <!-- Mapping Controls -->
  <div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
      <h6 class="mb-0">🔧 Mapping Controls</h6>
      <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-primary" id="loadMappingBtn">
          📂 Load Mapping
        </button>
        <button type="button" class="btn btn-outline-success" id="saveMappingBtn">
          💾 Save Mapping
        </button>
        <button type="button" class="btn btn-outline-secondary" id="selectAllInclude">
          ✓ All
        </button>
        <button type="button" class="btn btn-outline-secondary" id="deselectAllInclude">
          ✗ None
        </button>
        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#digitalObjectHelpModal">
          📷 Digital Objects Help
        </button>
      </div>
    </div>
  </div>

  <!-- Mapping Form -->
  <form action="<?php echo url_for(['module' => 'dataMigration', 'action' => 'preview']) ?>" method="post" id="mappingForm">
    <input type="hidden" id="currentMappingId" name="current_mapping_id" value="<?php echo isset($loadedMappingId) ? $loadedMappingId : '' ?>">
    <input type="hidden" name="target_type" value="<?php echo htmlspecialchars($targetType) ?>">
    <input type="hidden" name="output_mode" id="outputModeInput" value="preview">
    <input type="hidden" name="target_sector" id="targetSectorInput" value="archives">

    <div class="card">
      <div class="card-header bg-primary text-white py-2">
        <div class="row align-items-center small">
          <div class="col-2"><strong>Source Field</strong></div>
          <div class="col-2"><strong>AtoM Field</strong></div>
          <div class="col-2"><strong>Constant</strong></div>
          <div class="col-1 text-center"><strong>Prepend</strong></div>
          <div class="col-1 text-center"><strong>Concat</strong></div>
          <div class="col-2"><strong>Symbol</strong></div>
          <div class="col-1 text-center"><strong>Include</strong></div>
          <div class="col-1"><strong>Transform</strong></div>
        </div>
      </div>
      <div class="card-body p-0" style="max-height: 60vh; overflow-y: auto;">
        <table class="table table-striped table-hover table-sm mb-0" id="mappingTable">
          <tbody>
            <?php foreach ($mappingRows as $i => $row): ?>
            <tr data-row="<?php echo $i ?>">
              <td style="width:16%">
                <input type="hidden" name="fields[<?php echo $i ?>][source_field]" value="<?php echo htmlspecialchars($row['source_field']) ?>">
                <code class="small"><?php echo htmlspecialchars($row['source_field']) ?></code>
              </td>
              <td style="width:16%">
                <select name="fields[<?php echo $i ?>][atom_field]" class="form-select form-select-sm atom-field-select">
                  <option value="">-- Skip --</option>
                  <?php foreach ($targetFields as $key => $label): ?>
                    <option value="<?php echo $key ?>" <?php echo ($row['atom_field'] === $key) ? 'selected' : '' ?>>
                      <?php echo htmlspecialchars($label) ?>
                    </option>
                  <?php endforeach ?>
                </select>
              </td>
              <td style="width:16%">
                <input type="text" name="fields[<?php echo $i ?>][constant_value]"
                       value="<?php echo htmlspecialchars($row['constant_value']) ?>"
                       class="form-control form-control-sm" placeholder="Constant...">
              </td>
              <td style="width:8%" class="text-center">
                <input type="checkbox" name="fields[<?php echo $i ?>][concat_constant]" value="1"
                       class="form-check-input" <?php echo $row['concat_constant'] ? 'checked' : '' ?>>
              </td>
              <td style="width:8%" class="text-center">
                <input type="checkbox" name="fields[<?php echo $i ?>][concatenate]" value="1"
                       class="form-check-input" <?php echo $row['concatenate'] ? 'checked' : '' ?>>
              </td>
              <td style="width:16%">
                <select name="fields[<?php echo $i ?>][concat_symbol]" class="form-select form-select-sm">
                  <option value="|" <?php echo ($row['concat_symbol'] === '|') ? 'selected' : '' ?>>| (Pipe)</option>
                  <option value="\n" <?php echo ($row['concat_symbol'] === '\n' || $row['concat_symbol'] === "\n") ? 'selected' : '' ?>>↵ (Newline)</option>
                  <option value="; " <?php echo ($row['concat_symbol'] === '; ') ? 'selected' : '' ?>>; (Semicolon)</option>
                  <option value=", " <?php echo ($row['concat_symbol'] === ', ') ? 'selected' : '' ?>>, (Comma)</option>
                  <option value=" - " <?php echo ($row['concat_symbol'] === ' - ') ? 'selected' : '' ?>>- (Dash)</option>
                  <option value=" " <?php echo ($row['concat_symbol'] === ' ') ? 'selected' : '' ?>>Space</option>
                </select>
              </td>
              <td style="width:8%" class="text-center">
                <input type="checkbox" name="fields[<?php echo $i ?>][include]" value="1"
                       class="form-check-input include-check" <?php echo $row['include'] ? 'checked' : '' ?>>
              </td>
              <td style="width:8%">
                <select name="fields[<?php echo $i ?>][transform]" class="form-select form-select-sm transform-select">
                  <option value="">None</option>
                  <option value="filename">Filename only</option>
                  <option value="lowercase">Lowercase</option>
                  <option value="prefix">Add prefix</option>
                  <option value="replace">Replace path</option>
                </select>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
      <div class="card-footer">
        <div class="d-flex justify-content-between">
          <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'index']) ?>" class="btn btn-secondary">
            ← Back
          </a>
          <button type="submit" class="btn btn-primary" name="action" value="preview">
            <i class="bi bi-play-fill me-1"></i> Preview / Import
          </button>
          <button type="button" class="btn btn-success" id="backgroundJobBtn" title="Process large files in background without timeout">
            <i class="bi bi-cloud-upload me-1"></i> Background Job
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- Load Mapping Modal -->
<div class="modal fade" id="loadMappingModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">📂 Load Saved Mapping</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th>Mapping Name</th>
              <th>Type</th>
              <th>Fields</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rawMappings)): ?>
              <tr><td colspan="4" class="text-muted text-center py-3">No saved mappings</td></tr>
            <?php else: ?>
              <?php foreach ($rawMappings as $mapping): 
                $fields = json_decode($mapping->field_mappings, true);
                $fieldCount = isset($fields['fields']) ? count($fields['fields']) : 0;
              ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($mapping->name) ?></strong></td>
                  <td><small class="text-muted"><?php echo htmlspecialchars($mapping->target_type ?? '') ?></small></td>
                  <td><span class="badge bg-info"><?php echo $fieldCount ?></span></td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-success load-mapping-btn" data-id="<?php echo $mapping->id ?>">
                        ✓ Load
                      </button>
                      <button type="button" class="btn btn-outline-secondary rename-btn" 
                              data-id="<?php echo $mapping->id ?>" 
                              data-name="<?php echo htmlspecialchars($mapping->name) ?>">
                        ✎
                      </button>
                      <button type="button" class="btn btn-outline-danger delete-btn" 
                              data-id="<?php echo $mapping->id ?>" 
                              data-name="<?php echo htmlspecialchars($mapping->name) ?>">
                        ✕
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach ?>
            <?php endif ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Save Mapping Modal -->
<div class="modal fade" id="saveMappingModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2 bg-success text-white">
        <h6 class="modal-title"><i class="bi bi-save"></i> Save Mapping</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Mapping Name <span class="text-danger">*</span></label>
          <input type="text" id="mappingName" class="form-control" placeholder="e.g., NG Church DBText Export">
        </div>
        <div class="mb-3">
          <label class="form-label">Category</label>
          <select id="mappingCategory" class="form-select">
            <option value="Custom">Custom</option>
            <option value="ArchivesSpace">ArchivesSpace</option>
            <option value="Preservica">Preservica</option>
            <option value="Vernon">Vernon CMS</option>
            <option value="PSIS">PSIS</option>
            <option value="DAM">DAM / Digital Assets</option>
            <option value="WDB">WDB</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Or select existing to overwrite</label>
          <select id="existingMapping" class="form-select">
            <option value="">-- Create New --</option>
            <?php 
            $groupedMappings = [];
            foreach ($rawMappings as $m) {
                $cat = $m->category ?? 'Custom';
                if (!isset($groupedMappings[$cat])) $groupedMappings[$cat] = [];
                $groupedMappings[$cat][] = $m;
            }
            ksort($groupedMappings);
            foreach ($groupedMappings as $category => $mappings): ?>
              <optgroup label="<?php echo htmlspecialchars($category) ?>">
              <?php foreach ($mappings as $mapping): ?>
                <option value="<?php echo $mapping->id ?>" data-default="<?php echo $mapping->is_default ?>">
                  <?php echo htmlspecialchars($mapping->name) ?><?php if ($mapping->is_default): ?> [Default]<?php endif ?>
                </option>
              <?php endforeach ?>
              </optgroup>
            <?php endforeach ?>
          </select>
          <small class="text-muted">[Default] = System mapping - changes affect all users</small>
        </div>
        <div class="form-check">
          <input type="checkbox" id="overwriteExisting" class="form-check-input">
          <label class="form-check-label" for="overwriteExisting">Overwrite if exists</label>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success btn-sm" id="confirmSaveMapping"><i class="bi bi-save"></i> Save</button>
      </div>
    </div>
  </div>
</div>
<!-- Rename Modal -->
<div class="modal fade" id="renameModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">✎ Rename Mapping</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="renameId">
        <input type="text" id="renameName" class="form-control" placeholder="New name...">
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="confirmRename">Rename</button>
      </div>
    </div>
  </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
  <div class="bg-white rounded p-4 text-center shadow">
    <div class="spinner-border text-primary mb-2"></div>
    <div id="loadingText">Processing...</div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var loadModal = new bootstrap.Modal(document.getElementById('loadMappingModal'));
  var saveModal = new bootstrap.Modal(document.getElementById('saveMappingModal'));
  var renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
  
  // Output mode sync
  document.getElementById('outputMode').addEventListener('change', function() {
    document.getElementById('outputModeInput').value = this.value;
  });
  
  // Target sector sync
  document.getElementById('targetSector').addEventListener('change', function() {
    document.getElementById('targetSectorInput').value = this.value;
  });
  
  // Open Load Mapping Modal
  document.getElementById('loadMappingBtn').addEventListener('click', function() {
    loadModal.show();
  });
  
  // Open Save Mapping Modal
  document.getElementById('saveMappingBtn').addEventListener('click', function() {
    document.getElementById('mappingName').value = '';
    document.getElementById('existingMapping').value = '';
    document.getElementById('overwriteExisting').checked = false;
    saveModal.show();
  });
  
  // When selecting existing mapping, fill in name
  
  // Select All / Deselect All
  document.getElementById('selectAllInclude').addEventListener('click', function() {
    document.querySelectorAll('.include-check').forEach(function(cb) { cb.checked = true; });
  });
  document.getElementById('deselectAllInclude').addEventListener('click', function() {
    document.querySelectorAll('.include-check').forEach(function(cb) { cb.checked = false; });
  });
  
  // Load Mapping
  document.querySelectorAll('.load-mapping-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = this.dataset.id;
      showLoading('Loading mapping...');
      
      fetch('<?php echo url_for(['module' => 'dataMigration', 'action' => 'loadMapping']) ?>?id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
          console.log('Load result:', data);
          if (data.success && data.mapping && data.mapping.fields) {
            var appliedCount = applyMapping(data.mapping.fields);
            hideLoading();
            loadModal.hide();
            var total = data.mapping.fields.length;
            var msg = 'Applied ' + appliedCount + ' of ' + total + ' field mappings';
            if (appliedCount < total) {
              msg += ' (some source fields not in current file or atom fields empty)';
            }
            showToast(msg, appliedCount > 0 ? 'success' : 'warning');
          } else {
            hideLoading();
            showToast('Error: ' + (data.error || 'No fields found'), 'danger');
          }
        })
        .catch(function(e) {
          hideLoading();
          showToast('Error loading mapping: ' + e.message, 'danger');
        });
    });
  });
  
  // Save Mapping
  // Handle existing mapping selection - auto-fill name when selected
  document.getElementById('existingMapping').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    if (this.value) {
      // Overwriting existing - use selected mapping's name
      document.getElementById('mappingName').value = selected.text.replace(' [Default]', '').trim();
      document.getElementById('overwriteExisting').checked = true;
    } else {
      document.getElementById('overwriteExisting').checked = false;
    }
  });

  document.getElementById('confirmSaveMapping').addEventListener('click', function() {
    var name = document.getElementById('mappingName').value.trim();
    var category = document.getElementById('mappingCategory').value;
    var overwrite = document.getElementById('overwriteExisting').checked ? '1' : '0';
    var existingId = document.getElementById('existingMapping').value;
    
    if (!name && !existingId) {
      alert('Please enter a mapping name or select an existing mapping to overwrite');
      return;
    }
    
    // Warn if overwriting a default mapping
    if (existingId && overwrite === '1') {
      var selected = document.getElementById('existingMapping').options[document.getElementById('existingMapping').selectedIndex];
      if (selected.dataset.default === '1') {
        if (!confirm('Warning: This is a default/system mapping. Changes will affect all users. Continue?')) {
          return;
        }
      }
    }
    
    showLoading('Saving mapping...');
    var formData = new FormData(document.getElementById('mappingForm'));
    formData.append('mapping_name', name);
    formData.append('category', category);
    formData.append('overwrite', overwrite);
    formData.append('existing_id', existingId);
    
    fetch('<?php echo url_for(['module' => 'dataMigration', 'action' => 'saveMapping']) ?>', {
      method: 'POST',
      body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      console.log('Save result:', data);
      hideLoading();
      if (data.success) {
        var msg = data.updated ? 'Mapping updated' : 'Mapping saved';
        showToast(msg + '! (' + data.field_count + ' fields)', 'success');
        saveModal.hide();
        setTimeout(function() { location.reload(); }, 1000);
      } else if (data.error === 'exists') {
        if (confirm(data.message)) {
          document.getElementById('overwriteExisting').checked = true;
          document.getElementById('confirmSaveMapping').click();
        }
      } else {
        showToast('Error: ' + (data.error || 'Unknown'), 'danger');
      }
    })
    .catch(function(e) {
      hideLoading();
      showToast('Error saving: ' + e.message, 'danger');
    });
  });

  // Rename
  document.querySelectorAll('.rename-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('renameId').value = this.dataset.id;
      document.getElementById('renameName').value = this.dataset.name;
      renameModal.show();
    });
  });
  
  document.getElementById('confirmRename').addEventListener('click', function() {
    var id = document.getElementById('renameId').value;
    var name = document.getElementById('renameName').value.trim();
    if (!name) { alert('Please enter a name'); return; }
    
    fetch('<?php echo url_for(['module' => 'dataMigration', 'action' => 'renameMapping']) ?>?id=' + id + '&name=' + encodeURIComponent(name))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          location.reload();
        } else {
          alert('Error: ' + (data.error || 'Unknown'));
        }
      });
  });
  
  // Delete
  document.querySelectorAll('.delete-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      if (!confirm('Delete "' + this.dataset.name + '"?')) return;
      
      fetch('<?php echo url_for(['module' => 'dataMigration', 'action' => 'deleteMapping']) ?>?id=' + this.dataset.id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.success) {
            location.reload();
          } else {
            alert('Error: ' + (data.error || 'Unknown'));
          }
        });
    });
  });
});

function applyMapping(fields) {
  console.log('=== APPLYING MAPPING ===');
  console.log('Fields received:', fields.length);
  console.log('First 3 fields:', fields.slice(0, 3));
  
  // Build lookup by source field
  var map = {};
  fields.forEach(function(f) {
    var key = f.source_field || f.source;
    if (key) {
      map[key] = f;
    }
  });
  
  console.log('Map keys:', Object.keys(map));
  
  // Get all source fields from current form
  var formSourceFields = [];
  document.querySelectorAll('#mappingTable tbody tr input[type="hidden"]').forEach(function(input) {
    formSourceFields.push(input.value);
  });
  console.log('Form source fields:', formSourceFields.slice(0, 5), '... total:', formSourceFields.length);
  
  var applied = 0;
  var notFound = [];
  
  document.querySelectorAll('#mappingTable tbody tr').forEach(function(row) {
    var input = row.querySelector('input[type="hidden"]');
    if (!input) return;
    
    var source = input.value;
    var m = map[source];
    
    if (m) {
      // Apply atom field
      var atomField = m.atom_field || m.atom || '';
      var atomSelect = row.querySelector('.atom-field-select');
      if (atomSelect) {
        if (atomField) {
          atomSelect.value = atomField;
          if (atomSelect.value === atomField) {
            applied++;
            row.classList.add('table-success'); // Highlight applied rows
            row.style.backgroundColor = '#d4edda';
          } else {
            console.warn('Could not set', source, 'to', atomField, '- value not in dropdown');
            row.classList.add('table-warning');
          }
        }
      }
      
      // Apply constant
      var constInput = row.querySelector('input[name*="constant_value"]');
      if (constInput) constInput.value = m.constant_value || m.constant || '';
      
      // Apply prepend checkbox
      var prependCb = row.querySelector('input[name*="concat_constant"]');
      if (prependCb) prependCb.checked = (m.concat_constant === true || m.concat_constant === 'true' || m.concat_constant === 1);
      
      // Apply concatenate checkbox  
      var concatCb = row.querySelector('input[name*="concatenate"]');
      if (concatCb) concatCb.checked = (m.concatenate === true || m.concatenate === 'true' || m.concatenate === 1);
      
      // Apply concat symbol
      var symbolSel = row.querySelector('select[name*="concat_symbol"]');
      if (symbolSel && m.concat_symbol) symbolSel.value = m.concat_symbol;
      
      // Apply include checkbox
      var includeCb = row.querySelector('.include-check');
      if (includeCb) includeCb.checked = (m.include !== false && m.include !== 'false' && m.include !== 0);
    } else {
      notFound.push(source);
    }
  });
  
  console.log('Applied to', applied, 'fields');
  if (notFound.length > 0 && notFound.length < 20) {
    console.log('Not found in mapping:', notFound);
  } else if (notFound.length >= 20) {
    console.log('Not found in mapping:', notFound.length, 'fields (source fields dont match saved mapping)');
  }
  console.log('=== END APPLYING MAPPING ===');
  
  return applied;
}

function showLoading(text) {
  document.getElementById('loadingText').textContent = text || 'Processing...';
  document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideLoading() {
  document.getElementById('loadingOverlay').style.display = 'none';
}

function showToast(message, type) {
  var existing = document.querySelector('.toast-container');
  if (existing) existing.remove();
  
  var div = document.createElement('div');
  div.className = 'toast-container position-fixed top-0 end-0 p-3';
  div.style.zIndex = '10000';
  div.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show mb-0">' +
    message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
  document.body.appendChild(div);
  setTimeout(function() { div.remove(); }, 4000);
}
</script>

<!-- Path Transform Modal -->
<div class="modal fade" id="pathTransformModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">🔄 Path Transformation Settings</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted">Transform file paths from source system format to AtoM format.</p>
        
        <div class="mb-3">
          <label class="form-label">Transform Type</label>
          <select id="transformType" class="form-select">
            <option value="">No transformation</option>
            <option value="filename">Extract filename only</option>
            <option value="replace_prefix">Replace path prefix</option>
            <option value="add_prefix">Add prefix to path</option>
            <option value="lowercase">Convert to lowercase</option>
            <option value="extension">Change file extension</option>
          </select>
        </div>
        
        <div id="transformOptions" class="d-none">
          <div class="mb-3" id="replacePrefixGroup">
            <label class="form-label">Find (path to remove)</label>
            <input type="text" id="transformFind" class="form-control" placeholder="C:\Vernon\Images\">
            <small class="text-muted">e.g., C:\Vernon\Images\ or /mnt/media/</small>
          </div>
          
          <div class="mb-3" id="addPrefixGroup">
            <label class="form-label">Replace with / Add prefix</label>
            <input type="text" id="transformReplace" class="form-control" placeholder="images/">
            <small class="text-muted">e.g., images/ or media/photos/</small>
          </div>
          
          <div class="mb-3" id="extensionGroup" class="d-none">
            <label class="form-label">New extension</label>
            <input type="text" id="transformExtension" class="form-control" placeholder=".jpg">
          </div>
        </div>
        
        <div class="card bg-light mt-3">
          <div class="card-body py-2">
            <h6 class="small mb-2">Preview</h6>
            <div class="small">
              <strong>Before:</strong> <code id="transformBefore">C:\Vernon\Images\photo001.tif</code><br>
              <strong>After:</strong> <code id="transformAfter">images/photo001.tif</code>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="applyTransform">Apply to Selected Fields</button>
      </div>
    </div>
  </div>
</div>

<script>
// Path transformation logic
document.getElementById('transformType')?.addEventListener('change', function() {
  var opts = document.getElementById('transformOptions');
  var replaceGroup = document.getElementById('replacePrefixGroup');
  var addGroup = document.getElementById('addPrefixGroup');
  var extGroup = document.getElementById('extensionGroup');
  
  opts.classList.remove('d-none');
  replaceGroup.classList.add('d-none');
  addGroup.classList.add('d-none');
  extGroup.classList.add('d-none');
  
  switch(this.value) {
    case 'replace_prefix':
      replaceGroup.classList.remove('d-none');
      addGroup.classList.remove('d-none');
      break;
    case 'add_prefix':
      addGroup.classList.remove('d-none');
      break;
    case 'extension':
      extGroup.classList.remove('d-none');
      break;
    case 'filename':
    case 'lowercase':
    case '':
      opts.classList.add('d-none');
      break;
  }
  updateTransformPreview();
});

document.getElementById('transformFind')?.addEventListener('input', updateTransformPreview);
document.getElementById('transformReplace')?.addEventListener('input', updateTransformPreview);
document.getElementById('transformExtension')?.addEventListener('input', updateTransformPreview);

function updateTransformPreview() {
  var type = document.getElementById('transformType').value;
  var find = document.getElementById('transformFind').value;
  var replace = document.getElementById('transformReplace').value;
  var ext = document.getElementById('transformExtension').value;
  
  var before = 'C:\\Vernon\\Images\\photo001.tif';
  var after = before;
  
  switch(type) {
    case 'filename':
      after = before.split(/[\\\/]/).pop();
      break;
    case 'replace_prefix':
      if (find) {
        after = before.replace(find, replace || '');
      }
      break;
    case 'add_prefix':
      var filename = before.split(/[\\\/]/).pop();
      after = (replace || '') + filename;
      break;
    case 'lowercase':
      after = before.toLowerCase();
      break;
    case 'extension':
      if (ext) {
        after = before.replace(/\.[^.]+$/, ext);
      }
      break;
  }
  
  // Normalize slashes
  after = after.replace(/\\/g, '/');
  
  document.getElementById('transformBefore').textContent = before;
  document.getElementById('transformAfter').textContent = after;
}

// Store transform settings per field
var fieldTransforms = {};

document.querySelectorAll('.transform-select')?.forEach(function(sel) {
  sel.addEventListener('change', function() {
    if (this.value === 'replace' || this.value === 'prefix') {
      // Open modal for detailed settings
      var row = this.closest('tr');
      var fieldName = row.querySelector('input[type="hidden"]').value;
      document.getElementById('currentTransformField').value = fieldName;
      new bootstrap.Modal(document.getElementById('pathTransformModal')).show();
    }
  });
});
</script>

<!-- Path Transform Modal -->
<div class="modal fade" id="pathTransformModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">🔄 Path Transformation Settings</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted">Transform file paths from source system format to AtoM format.</p>
        
        <div class="mb-3">
          <label class="form-label">Transform Type</label>
          <select id="transformType" class="form-select">
            <option value="">No transformation</option>
            <option value="filename">Extract filename only</option>
            <option value="replace_prefix">Replace path prefix</option>
            <option value="add_prefix">Add prefix to path</option>
            <option value="lowercase">Convert to lowercase</option>
            <option value="extension">Change file extension</option>
          </select>
        </div>
        
        <div id="transformOptions" class="d-none">
          <div class="mb-3" id="replacePrefixGroup">
            <label class="form-label">Find (path to remove)</label>
            <input type="text" id="transformFind" class="form-control" placeholder="C:\Vernon\Images\">
            <small class="text-muted">e.g., C:\Vernon\Images\ or /mnt/media/</small>
          </div>
          
          <div class="mb-3" id="addPrefixGroup">
            <label class="form-label">Replace with / Add prefix</label>
            <input type="text" id="transformReplace" class="form-control" placeholder="images/">
            <small class="text-muted">e.g., images/ or media/photos/</small>
          </div>
          
          <div class="mb-3" id="extensionGroup" class="d-none">
            <label class="form-label">New extension</label>
            <input type="text" id="transformExtension" class="form-control" placeholder=".jpg">
          </div>
        </div>
        
        <div class="card bg-light mt-3">
          <div class="card-body py-2">
            <h6 class="small mb-2">Preview</h6>
            <div class="small">
              <strong>Before:</strong> <code id="transformBefore">C:\Vernon\Images\photo001.tif</code><br>
              <strong>After:</strong> <code id="transformAfter">images/photo001.tif</code>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="applyTransform">Apply to Selected Fields</button>
      </div>
    </div>
  </div>
</div>

<script>
// Path transformation logic
document.getElementById('transformType')?.addEventListener('change', function() {
  var opts = document.getElementById('transformOptions');
  var replaceGroup = document.getElementById('replacePrefixGroup');
  var addGroup = document.getElementById('addPrefixGroup');
  var extGroup = document.getElementById('extensionGroup');
  
  opts.classList.remove('d-none');
  replaceGroup.classList.add('d-none');
  addGroup.classList.add('d-none');
  extGroup.classList.add('d-none');
  
  switch(this.value) {
    case 'replace_prefix':
      replaceGroup.classList.remove('d-none');
      addGroup.classList.remove('d-none');
      break;
    case 'add_prefix':
      addGroup.classList.remove('d-none');
      break;
    case 'extension':
      extGroup.classList.remove('d-none');
      break;
    case 'filename':
    case 'lowercase':
    case '':
      opts.classList.add('d-none');
      break;
  }
  updateTransformPreview();
});

document.getElementById('transformFind')?.addEventListener('input', updateTransformPreview);
document.getElementById('transformReplace')?.addEventListener('input', updateTransformPreview);
document.getElementById('transformExtension')?.addEventListener('input', updateTransformPreview);

function updateTransformPreview() {
  var type = document.getElementById('transformType').value;
  var find = document.getElementById('transformFind').value;
  var replace = document.getElementById('transformReplace').value;
  var ext = document.getElementById('transformExtension').value;
  
  var before = 'C:\\Vernon\\Images\\photo001.tif';
  var after = before;
  
  switch(type) {
    case 'filename':
      after = before.split(/[\\\/]/).pop();
      break;
    case 'replace_prefix':
      if (find) {
        after = before.replace(find, replace || '');
      }
      break;
    case 'add_prefix':
      var filename = before.split(/[\\\/]/).pop();
      after = (replace || '') + filename;
      break;
    case 'lowercase':
      after = before.toLowerCase();
      break;
    case 'extension':
      if (ext) {
        after = before.replace(/\.[^.]+$/, ext);
      }
      break;
  }
  
  // Normalize slashes
  after = after.replace(/\\/g, '/');
  
  document.getElementById('transformBefore').textContent = before;
  document.getElementById('transformAfter').textContent = after;
}

// Store transform settings per field
var fieldTransforms = {};

document.querySelectorAll('.transform-select')?.forEach(function(sel) {
  sel.addEventListener('change', function() {
    if (this.value === 'replace' || this.value === 'prefix') {
      // Open modal for detailed settings
      var row = this.closest('tr');
      var fieldName = row.querySelector('input[type="hidden"]').value;
      document.getElementById('currentTransformField').value = fieldName;
      new bootstrap.Modal(document.getElementById('pathTransformModal')).show();
    }
  });
});
</script>

<!-- Background Job Modal -->
<div class="modal fade" id="backgroundJobModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2 bg-success text-white">
        <h6 class="modal-title"><i class="bi bi-cloud-upload me-2"></i>Queue Background Import Job</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small">
          Background jobs process imports without browser timeout. Ideal for large files (1000+ rows) or imports with digital objects.
        </p>
        
        <div class="mb-3">
          <label class="form-label">Repository (optional)</label>
          <select id="bgJobRepository" class="form-select">
            <option value="">-- Select Repository --</option>
            <?php
              $repos = QubitRepository::getAll();
              foreach ($repos as $repo):
                if ($repo->id == QubitRepository::ROOT_ID) continue;
            ?>
              <option value="<?php echo $repo->id ?>"><?php echo htmlspecialchars($repo->__toString()) ?></option>
            <?php endforeach ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Parent Record (optional)</label>
          <input type="text" id="bgJobParentId" class="form-control" placeholder="Enter parent ID (e.g., 12345)">
          <small class="text-muted">Leave empty to import at top level</small>
        </div>

        <div class="mb-3">
          <label class="form-label">Culture / Language</label>
          <select id="bgJobCulture" class="form-select">
            <option value="en" selected>English</option>
            <option value="af">Afrikaans</option>
            <option value="fr">French</option>
            <option value="de">German</option>
            <option value="es">Spanish</option>
            <option value="pt">Portuguese</option>
            <option value="nl">Dutch</option>
          </select>
        </div>

        <div class="mb-3">
          <div class="form-check">
            <input type="checkbox" id="bgJobUpdate" class="form-check-input">
            <label class="form-check-label" for="bgJobUpdate">Update existing records (match by Legacy ID)</label>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Digital Objects Path (optional)</label>
          <input type="text" id="bgJobImagePath" class="form-control" placeholder="/path/to/images/">
          <small class="text-muted">Server path where digital objects are located</small>
        </div>

      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="submitBackgroundJob">
          <i class="bi bi-cloud-upload me-1"></i> Queue Job
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Background Job handling
document.getElementById('backgroundJobBtn')?.addEventListener('click', function() {
  new bootstrap.Modal(document.getElementById('backgroundJobModal')).show();
});

document.getElementById('submitBackgroundJob')?.addEventListener('click', function() {
  // Collect mapping data from form
  var form = document.getElementById('mappingForm');
  var formData = new FormData(form);
  
  // Add background job options
  formData.append('repository_id', document.getElementById('bgJobRepository').value);
  formData.append('parent_id', document.getElementById('bgJobParentId').value);
  formData.append('culture', document.getElementById('bgJobCulture').value);
  formData.append('update_existing', document.getElementById('bgJobUpdate').checked ? '1' : '0');
  formData.append('image_path', document.getElementById('bgJobImagePath').value);
  
  // Get saved mapping ID if loaded
  var savedMappingId = document.getElementById('currentMappingId')?.value || '';
  formData.append('saved_mapping_id', savedMappingId);
  
  // Disable button
  var btn = this;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Queueing...';
  
  // Submit to queue action
  fetch('<?php echo url_for(['module' => 'dataMigration', 'action' => 'queueJob']) ?>', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    // Follow redirect
    if (response.redirected) {
      window.location.href = response.url;
    } else {
      return response.text();
    }
  })
  .then(html => {
    if (html) {
      // If not redirected, might be error - reload with message
      window.location.href = '<?php echo url_for(['module' => 'dataMigration', 'action' => 'jobs']) ?>';
    }
  })
  .catch(err => {
    alert('Error queueing job: ' + err.message);
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Queue Job';
  });
});
</script>
