<?php
/**
 * TIFF to PDF Merge Modal Component
 * Include this in templates where batch PDF creation is needed
 */
$informationObjectId = $informationObjectId ?? null;
?>

<!-- TIFF to PDF Merge Modal -->
<div class="modal fade" id="tpm-modal" tabindex="-1" aria-labelledby="tpm-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="tpm-modal-label">
                    <i class="fas fa-file-pdf me-2"></i>
                    Merge Images to PDF
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Alert area -->
                <div id="tpm-alert" class="alert" style="display: none;"></div>

                <!-- Hidden fields -->
                <input type="hidden" id="tpm-information-object-id" value="<?php echo $informationObjectId; ?>">
                <input type="hidden" id="tpm-job-id" value="">

                <!-- Settings Row -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="tpm-pdf-standard" class="form-label">PDF Standard</label>
                        <select id="tpm-pdf-standard" class="form-select form-select-sm">
                            <option value="pdfa-2b" selected>PDF/A-2b (Recommended)</option>
                            <option value="pdfa-1b">PDF/A-1b</option>
                            <option value="pdfa-3b">PDF/A-3b</option>
                            <option value="pdf">Standard PDF</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="tpm-dpi" class="form-label">Resolution (DPI)</label>
                        <select id="tpm-dpi" class="form-select form-select-sm">
                            <option value="150">150 DPI (Screen)</option>
                            <option value="300" selected>300 DPI (Print)</option>
                            <option value="400">400 DPI (High Quality)</option>
                            <option value="600">600 DPI (Archival)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="tpm-quality" class="form-label">Quality</label>
                        <select id="tpm-quality" class="form-select form-select-sm">
                            <option value="70">70% (Smaller file)</option>
                            <option value="85" selected>85% (Balanced)</option>
                            <option value="95">95% (High quality)</option>
                            <option value="100">100% (Maximum)</option>
                        </select>
                    </div>
                </div>

                <!-- Drop Zone -->
                <div id="tpm-drop-zone" class="border border-2 border-dashed rounded p-4 text-center mb-3 bg-light" 
                     style="cursor: pointer; min-height: 150px;">
                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                    <p class="mb-1">
                        <strong>Drag and drop images here</strong>
                    </p>
                    <p class="text-muted small mb-2">or click to browse</p>
                    <p class="text-muted small mb-0">
                        Supported: TIFF, JPEG, PNG, BMP, GIF
                    </p>
                    <input type="file" id="tpm-file-input" class="d-none" multiple 
                           accept=".tif,.tiff,.jpg,.jpeg,.png,.bmp,.gif,image/tiff,image/jpeg,image/png,image/bmp,image/gif">
                </div>

                <!-- Progress Bar -->
                <div id="tpm-progress-container" class="mb-3" style="display: none;">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Uploading...</small>
                        <small id="tpm-progress-text" class="text-muted"></small>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div id="tpm-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%"></div>
                    </div>
                </div>

                <!-- File List -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">
                            <i class="fas fa-list me-1"></i>
                            Files to Merge
                            <span id="tpm-file-count" class="badge bg-secondary ms-1">0</span>
                        </h6>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Drag to reorder pages
                        </small>
                    </div>
                    <div id="tpm-file-list" class="border rounded" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-muted text-center py-4">No files uploaded yet</div>
                    </div>
                </div>

                <!-- Options -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="tpm-preserve-originals" checked>
                            <label class="form-check-label" for="tpm-preserve-originals">
                                Keep original files after merge
                            </label>
                        </div>
                    </div>
                    <?php if ($informationObjectId): ?>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="tpm-attach-to-record" checked>
                            <label class="form-check-label" for="tpm-attach-to-record">
                                Attach PDF to this record
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="tpm-cancel-btn">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="tpm-create-btn" disabled>
                    <i class="fas fa-file-pdf me-1"></i> Create PDF
                </button>
            </div>
        </div>
    </div>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
#tpm-drop-zone.tpm-drag-over {
    border-color: #0d6efd !important;
    background-color: #e7f1ff !important;
}

.tpm-file-item {
    transition: background-color 0.2s;
}

.tpm-file-item:hover {
    background-color: #f8f9fa;
}

.tpm-drag-handle {
    cursor: grab;
}

.tpm-sortable-ghost {
    opacity: 0.4;
    background-color: #e7f1ff;
}
</style>
