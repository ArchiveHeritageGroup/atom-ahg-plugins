<?php
/**
 * Audit Compare Modal - Shows old vs new values side by side
 *
 * @package ahgAuditTrailPlugin
 */
?>
<div class="modal fade" id="auditCompareModal" tabindex="-1" aria-labelledby="auditCompareModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="auditCompareModalLabel">
          <i class="fas fa-exchange-alt me-2"></i><?php echo __('Change Comparison') ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="audit-compare-header mb-3 p-3 bg-light rounded border">
          <div class="row">
            <div class="col-md-4">
              <strong><i class="fas fa-file-alt me-1"></i><?php echo __('Record') ?>:</strong>
              <span id="compareEntityTitle" class="ms-1">-</span>
            </div>
            <div class="col-md-4">
              <strong><i class="fas fa-user me-1"></i><?php echo __('Changed by') ?>:</strong>
              <span id="compareUsername" class="ms-1">-</span>
            </div>
            <div class="col-md-4">
              <strong><i class="fas fa-clock me-1"></i><?php echo __('Date') ?>:</strong>
              <span id="compareDate" class="ms-1">-</span>
            </div>
          </div>
        </div>
        
        <div class="row g-3">
          <div class="col-md-6">
            <div class="card border-danger h-100">
              <div class="card-header bg-danger text-white py-2">
                <i class="fas fa-minus-circle me-2"></i><?php echo __('Before (Old Values)') ?>
              </div>
              <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0" id="oldValuesTable">
                  <thead class="table-light">
                    <tr>
                      <th style="width: 35%;"><?php echo __('Field') ?></th>
                      <th><?php echo __('Value') ?></th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card border-success h-100">
              <div class="card-header bg-success text-white py-2">
                <i class="fas fa-plus-circle me-2"></i><?php echo __('After (New Values)') ?>
              </div>
              <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0" id="newValuesTable">
                  <thead class="table-light">
                    <tr>
                      <th style="width: 35%;"><?php echo __('Field') ?></th>
                      <th><?php echo __('Value') ?></th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        
        <div class="mt-3">
          <div class="card border-warning">
            <div class="card-header bg-warning py-2">
              <i class="fas fa-highlighter me-2"></i><?php echo __('Changed Fields') ?>
            </div>
            <div class="card-body py-2">
              <div id="changedFieldsList" class="d-flex flex-wrap gap-2">
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i><?php echo __('Close') ?>
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.audit-compare-changed {
  background-color: #fff3cd !important;
}
.audit-compare-added {
  background-color: #d1e7dd !important;
}
.audit-compare-removed {
  background-color: #f8d7da !important;
}
#oldValuesTable tbody tr.changed td:last-child,
#newValuesTable tbody tr.changed td:last-child {
  font-weight: 600;
}
.field-badge {
  padding: 0.35em 0.65em;
  font-size: 0.875em;
  border-radius: 0.25rem;
}
#auditCompareModal .table td {
  vertical-align: top;
  word-break: break-word;
}
#auditCompareModal pre {
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 150px;
  overflow-y: auto;
}
</style>

<script>
function showAuditCompare(auditId) {
  // Show loading state
  const modal = new bootstrap.Modal(document.getElementById('auditCompareModal'));
  document.getElementById('compareEntityTitle').textContent = 'Loading...';
  document.getElementById('compareUsername').textContent = '-';
  document.getElementById('compareDate').textContent = '-';
  document.querySelector('#oldValuesTable tbody').innerHTML = '<tr><td colspan="2" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
  document.querySelector('#newValuesTable tbody').innerHTML = '<tr><td colspan="2" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
  document.getElementById('changedFieldsList').innerHTML = '';
  modal.show();

  // Fetch audit record data
  fetch('/index.php/ahgAuditTrailPlugin/compareData?id=' + auditId)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        alert('Error: ' + data.error);
        modal.hide();
        return;
      }
      
      // Set header info
      document.getElementById('compareEntityTitle').textContent = data.entity_title || data.entity_slug || 'ID: ' + data.entity_id;
      document.getElementById('compareUsername').textContent = data.username || 'Unknown';
      document.getElementById('compareDate').textContent = data.created_at;
      
      // Parse JSON values
      let oldValues = {};
      let newValues = {};
      let changedFields = [];
      
      try {
        oldValues = data.old_values ? JSON.parse(data.old_values) : {};
      } catch (e) {
        console.error('Error parsing old_values:', e);
      }
      
      try {
        newValues = data.new_values ? JSON.parse(data.new_values) : {};
      } catch (e) {
        console.error('Error parsing new_values:', e);
      }
      
      try {
        changedFields = data.changed_fields ? JSON.parse(data.changed_fields) : [];
      } catch (e) {
        console.error('Error parsing changed_fields:', e);
      }
      
      // Get all unique keys
      const allKeys = [...new Set([...Object.keys(oldValues), ...Object.keys(newValues)])].sort();
      
      // Build old values table
      const oldTbody = document.querySelector('#oldValuesTable tbody');
      oldTbody.innerHTML = '';
      
      // Build new values table
      const newTbody = document.querySelector('#newValuesTable tbody');
      newTbody.innerHTML = '';
      
      if (allKeys.length === 0) {
        oldTbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No data available</td></tr>';
        newTbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No data available</td></tr>';
      } else {
        allKeys.forEach(key => {
          const oldVal = oldValues[key];
          const newVal = newValues[key];
          const isChanged = changedFields.includes(key);
          const isRemoved = oldVal !== undefined && newVal === undefined;
          const isAdded = oldVal === undefined && newVal !== undefined;
          
          // Old value row
          const oldRow = document.createElement('tr');
          if (isChanged) oldRow.classList.add('changed', 'audit-compare-changed');
          if (isRemoved) oldRow.classList.add('audit-compare-removed');
          oldRow.innerHTML = '<td><code>' + escapeHtml(formatFieldName(key)) + '</code></td><td>' + formatValue(oldVal) + '</td>';
          oldTbody.appendChild(oldRow);
          
          // New value row
          const newRow = document.createElement('tr');
          if (isChanged) newRow.classList.add('changed', 'audit-compare-changed');
          if (isAdded) newRow.classList.add('audit-compare-added');
          newRow.innerHTML = '<td><code>' + escapeHtml(formatFieldName(key)) + '</code></td><td>' + formatValue(newVal) + '</td>';
          newTbody.appendChild(newRow);
        });
      }
      
      // Build changed fields list
      const changedList = document.getElementById('changedFieldsList');
      changedList.innerHTML = '';
      if (changedFields.length === 0) {
        changedList.innerHTML = '<span class="text-muted fst-italic">No changes detected</span>';
      } else {
        changedFields.forEach(field => {
          const badge = document.createElement('span');
          badge.className = 'badge bg-warning text-dark field-badge';
          badge.innerHTML = '<i class="fas fa-pencil-alt me-1"></i>' + escapeHtml(formatFieldName(field));
          changedList.appendChild(badge);
        });
      }
    })
    .catch(error => {
      console.error('Error fetching audit data:', error);
      alert('Error loading comparison data: ' + error.message);
      modal.hide();
    });
}

function formatFieldName(key) {
  // Convert snake_case or camelCase to Title Case
  return key
    .replace(/_/g, ' ')
    .replace(/([A-Z])/g, ' $1')
    .replace(/^./, str => str.toUpperCase())
    .trim();
}

function formatValue(val) {
  if (val === undefined || val === null) {
    return '<span class="text-muted fst-italic">—</span>';
  }
  if (typeof val === 'object') {
    return '<pre class="mb-0 small bg-light p-1 rounded">' + escapeHtml(JSON.stringify(val, null, 2)) + '</pre>';
  }
  if (typeof val === 'boolean') {
    return val ? '<span class="badge bg-success">true</span>' : '<span class="badge bg-danger">false</span>';
  }
  const strVal = String(val);
  if (strVal.length > 200) {
    return '<span class="text-break">' + escapeHtml(strVal.substring(0, 200)) + '<span class="text-muted">... [truncated]</span></span>';
  }
  return '<span class="text-break">' + escapeHtml(strVal) + '</span>';
}

function escapeHtml(text) {
  if (text === null || text === undefined) return '';
  const div = document.createElement('div');
  div.textContent = String(text);
  return div.innerHTML;
}
</script>
