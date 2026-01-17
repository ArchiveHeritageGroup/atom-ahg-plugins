<?php use_helper('Date') ?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="bi bi-cloud-upload me-2"></i>Data Migration Tool</h5>
        <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'jobs']) ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-list-task me-1"></i>View Jobs</a>
        </div>
        <div class="card-body">
          
          <?php if ($sf_user->hasFlash('error')): ?>
            <div class="alert alert-danger"><?php echo $sf_user->getFlash('error') ?></div>
          <?php endif ?>
          <?php if ($sf_user->hasFlash('success')): ?>
            <div class="alert alert-success"><?php echo $sf_user->getFlash('success') ?></div>
          <?php endif ?>
          
          <form action="<?php echo url_for(['module' => 'dataMigration', 'action' => 'upload']) ?>" 
                method="post" enctype="multipart/form-data" id="uploadForm">
            
            <!-- Step 1: File Upload -->
            <div class="mb-4">
              <h6 class="text-primary"><span class="badge bg-primary me-2">1</span>Select File</h6>
              <div class="border rounded p-4 bg-light text-center" id="dropZone">
                <input type="file" name="import_file" id="importFile" class="d-none" 
                       accept=".csv,.xls,.xlsx,.xml,.json,.opex,.pax,.zip">
                <div id="dropText">
                  <p class="mb-2"><i class="bi bi-file-earmark-arrow-up" style="font-size: 3rem;"></i></p>
                  <p class="mb-2">Drag & drop file here or <a href="#" onclick="document.getElementById('importFile').click(); return false;">browse</a></p>
                  <small class="text-muted">Supported: CSV, Excel (XLS/XLSX), XML, JSON, OPEX, PAX</small>
                </div>
                <div id="fileInfo" class="d-none">
                  <p class="mb-1"><strong id="fileName"></strong></p>
                  <small class="text-muted" id="fileSize"></small>
                  <br><a href="#" onclick="clearFile(); return false;" class="text-danger small">Remove</a>
                </div>
              </div>
            </div>
            
            <!-- Step 2: File Options (shown after file selected) -->
            <div class="mb-4 d-none" id="fileOptions">
              <h6 class="text-primary"><span class="badge bg-primary me-2">2</span>File Options</h6>
              <div class="row g-3">
                
                <!-- Excel Sheet Selection (only for Excel files) -->
                <div class="col-md-6 d-none" id="sheetSelectGroup">
                  <label class="form-label">Excel Sheet</label>
                  <select name="sheet_index" id="sheetSelect" class="form-select">
                    <option value="0">Loading sheets...</option>
                  </select>
                  <small class="text-muted">Select which sheet to import</small>
                </div>
                
                <!-- Header Row Option -->
                <div class="col-md-6">
                  <label class="form-label">First Row Contains</label>
                  <select name="first_row_header" id="firstRowHeader" class="form-select">
                    <option value="1" selected>Column Headers (skip first row)</option>
                    <option value="0">Data (no headers - use column letters)</option>
                  </select>
                </div>
                
                <!-- CSV Delimiter (only for CSV files) -->
                <div class="col-md-6 d-none" id="delimiterGroup">
                  <label class="form-label">CSV Delimiter</label>
                  <select name="delimiter" id="delimiter" class="form-select">
                    <option value="auto">Auto-detect</option>
                    <option value=",">Comma (,)</option>
                    <option value=";">Semicolon (;)</option>
                    <option value="\t">Tab</option>
                    <option value="|">Pipe (|)</option>
                  </select>
                </div>
                
                <!-- Encoding -->
                <div class="col-md-6">
                  <label class="form-label">File Encoding</label>
                  <select name="encoding" id="encoding" class="form-select">
                    <option value="auto">Auto-detect</option>
                    <option value="UTF-8">UTF-8</option>
                    <option value="ISO-8859-1">ISO-8859-1 (Latin-1)</option>
                    <option value="Windows-1252">Windows-1252</option>
                  </select>
                </div>
              </div>
            </div>
            
            <!-- Step 3: Target & Mapping -->
            <div class="mb-4">
              <h6 class="text-primary"><span class="badge bg-primary me-2">3</span>Import Target & Mapping</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Target Record Type</label>
                  <select name="target_type" id="targetType" class="form-select">
                    <option value="archives">Archives (ISAD-G)</option>
                    <option value="library">Library</option>
                    <option value="museum">Museum (Spectrum)</option>
                    <option value="gallery">Gallery (CCO)</option>
                    <option value="dam">Digital Assets (DAM)</option>
                    <option value="accession">Accession Records</option>
                    <option value="actor">Authority Records (ISAAR)</option>
                    <option value="repository">Repositories (ISDIAH)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Load Saved Mapping (Optional)</label>
                  <select name="saved_mapping" id="savedMapping" class="form-select">
                    <option value="">-- None (map manually) --</option>
                    <?php foreach ($savedMappings as $mapping): ?>
                      <option value="<?php echo $mapping->id ?>"><?php echo htmlspecialchars($mapping->name) ?> (<?php echo $mapping->field_count ?> fields)</option>
                    <?php endforeach ?>
                  </select>
                  <small class="text-muted">Pre-load field mappings from a saved template</small>
                </div>
              </div>
            </div>
            
            <!-- File Preview -->
            <div class="mb-4 d-none" id="previewSection">
              <h6 class="text-primary"><span class="badge bg-primary me-2">4</span>Preview</h6>
              <div class="table-responsive border rounded" style="max-height: 200px; overflow: auto;">
                <table class="table table-sm table-striped mb-0" id="previewTable">
                  <thead class="table-light sticky-top" id="previewHead"></thead>
                  <tbody id="previewBody"></tbody>
                </table>
              </div>
              <small class="text-muted">Showing first 5 rows</small>
            </div>
            
            <!-- Submit -->
            <div class="d-flex justify-content-between">
              <a href="<?php echo url_for(['module' => 'admin', 'action' => 'index']) ?>" class="btn btn-secondary">
                Cancel
              </a>
              <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                <i class="bi bi-arrow-right me-1"></i>Continue to Field Mapping
              </button>
            </div>
            
          </form>
        </div>
      </div>
      
      <!-- Recent Imports -->
      <?php if (!empty($recentImports)): ?>
      <div class="card mt-4">
        <div class="card-header">
          <h6 class="mb-0">Recent Imports</h6>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach ($recentImports as $import): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <strong><?php echo htmlspecialchars($import->filename) ?></strong>
                <br><small class="text-muted"><?php echo $import->row_count ?> rows • <?php echo date('Y-m-d H:i', strtotime($import->created_at)) ?></small>
              </div>
              <span class="badge bg-<?php echo $import->status === 'completed' ? 'success' : 'warning' ?>">
                <?php echo $import->status ?>
              </span>
            </div>
          <?php endforeach ?>
        </div>
      </div>
      <?php endif ?>
      
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var fileInput = document.getElementById('importFile');
  var dropZone = document.getElementById('dropZone');
  var submitBtn = document.getElementById('submitBtn');
  var currentFile = null;
  
  // Drag and drop
  dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    dropZone.classList.add('border-primary', 'bg-light');
  });
  
  dropZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    dropZone.classList.remove('border-primary');
  });
  
  dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    dropZone.classList.remove('border-primary');
    if (e.dataTransfer.files.length) {
      fileInput.files = e.dataTransfer.files;
      handleFileSelect(e.dataTransfer.files[0]);
    }
  });
  
  // File input change
  fileInput.addEventListener('change', function() {
    if (this.files.length) {
      handleFileSelect(this.files[0]);
    }
  });
  
  function handleFileSelect(file) {
    currentFile = file;
    var ext = file.name.split('.').pop().toLowerCase();
    
    // Show file info
    document.getElementById('dropText').classList.add('d-none');
    document.getElementById('fileInfo').classList.remove('d-none');
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatBytes(file.size);
    
    // Show file options
    document.getElementById('fileOptions').classList.remove('d-none');
    
    // Show/hide Excel sheet selector
    if (ext === 'xls' || ext === 'xlsx') {
      console.log('Excel file detected, showing sheet selector');
      document.getElementById('sheetSelectGroup').classList.remove('d-none');
      document.getElementById('delimiterGroup').classList.add('d-none');
      detectExcelSheets(file);
    } else if (ext === 'csv' || ext === 'txt') {
      console.log('CSV file detected');
      document.getElementById('sheetSelectGroup').classList.add('d-none');
      document.getElementById('delimiterGroup').classList.remove('d-none');
      generatePreview(file, 0);
    } else {
      document.getElementById('sheetSelectGroup').classList.add('d-none');
      document.getElementById('delimiterGroup').classList.add('d-none');
      generatePreview(file, 0);
    }
    
    // Enable submit
    submitBtn.disabled = false;
    
    // Generate preview
    generatePreview(file);
  }
  
  function detectExcelSheets(file) {
    var formData = new FormData();
    formData.append('file', file);
    
    var sheetSelect = document.getElementById('sheetSelect');
    sheetSelect.innerHTML = '<option>Detecting sheets...</option>';
    
    console.log('Detecting sheets for:', file.name);
    
    fetch('<?php echo url_for(['module' => 'dataMigration', 'action' => 'detectSheets']) ?>', {
      method: 'POST',
      body: formData
    })
    .then(function(r) { 
      console.log('Response status:', r.status);
      return r.json(); 
    })
    .then(function(data) {
      console.log('Sheet detection result:', data);
      if (data.success && data.sheets && data.sheets.length > 0) {
        sheetSelect.innerHTML = '';
        data.sheets.forEach(function(sheet) {
          var opt = document.createElement('option');
          opt.value = sheet.index;
          opt.textContent = sheet.name + ' (' + sheet.rows + ' rows)';
          sheetSelect.appendChild(opt);
        });
        
        // Show sheet selector
        document.getElementById('sheetSelectGroup').classList.remove('d-none');
        
        // Re-generate preview when sheet changes
        sheetSelect.onchange = function() {
          generatePreview(currentFile, this.value);
        };
        
        // Generate initial preview
        generatePreview(file, 0);
      } else {
        console.error('Sheet detection failed:', data.error);
        sheetSelect.innerHTML = '<option value="0">Sheet 1 (default)</option>';
      }
    })
    .catch(function(e) {
      console.error('Sheet detection error:', e);
      sheetSelect.innerHTML = '<option value="0">Sheet 1 (default)</option>';
    });
  }
  
  function generatePreview(file, sheetIndex) {
    var formData = new FormData();
    formData.append('file', file);
    formData.append('sheet_index', sheetIndex || 0);
    formData.append('first_row_header', document.getElementById('firstRowHeader').value);
    formData.append('delimiter', document.getElementById('delimiter').value);
    
    fetch('<?php echo url_for(['module' => 'dataMigration', 'action' => 'preview']) ?>', {
      method: 'POST',
      body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success && data.headers && data.rows) {
        document.getElementById('previewSection').classList.remove('d-none');
        
        // Build header
        var thead = '<tr>';
        data.headers.forEach(function(h) {
          thead += '<th class="small">' + escapeHtml(h) + '</th>';
        });
        thead += '</tr>';
        document.getElementById('previewHead').innerHTML = thead;
        
        // Build body
        var tbody = '';
        data.rows.slice(0, 5).forEach(function(row) {
          tbody += '<tr>';
          data.headers.forEach(function(h) {
            tbody += '<td class="small">' + escapeHtml(row[h] || '') + '</td>';
          });
          tbody += '</tr>';
        });
        document.getElementById('previewBody').innerHTML = tbody;
      }
    })
    .catch(function(e) {
      console.error('Preview error:', e);
    });
  }
  
  // Re-generate preview when header option changes
  document.getElementById('firstRowHeader').addEventListener('change', function() {
    if (currentFile) {
      generatePreview(currentFile, document.getElementById('sheetSelect').value);
    }
  });
  
  window.clearFile = function() {
    fileInput.value = '';
    currentFile = null;
    document.getElementById('dropText').classList.remove('d-none');
    document.getElementById('fileInfo').classList.add('d-none');
    document.getElementById('fileOptions').classList.add('d-none');
    document.getElementById('previewSection').classList.add('d-none');
    submitBtn.disabled = true;
  };
  
  function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }
  
  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
});
</script>
