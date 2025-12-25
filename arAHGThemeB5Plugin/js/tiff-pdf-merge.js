/**
 * TIFF to PDF Merge Module
 * Handles batch upload of images and merging into PDF/A documents
 */

const TiffPdfMerge = (function() {
    'use strict';

    const API_BASE = '/api/tiff-pdf-merge';

    let currentJob = null;
    let uploadedFiles = [];
    let sortable = null;

    /**
     * Initialize the module
     */
    function init(options = {}) {
        bindEvents();
        
        if (options.informationObjectId) {
            document.getElementById('tpm-information-object-id').value = options.informationObjectId;
        }
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Open modal button
        document.querySelectorAll('[data-tpm-open]').forEach(btn => {
            btn.addEventListener('click', openModal);
        });

        // File input change
        const fileInput = document.getElementById('tpm-file-input');
        if (fileInput) {
            fileInput.addEventListener('change', handleFileSelect);
        }

        // Drop zone
        const dropZone = document.getElementById('tpm-drop-zone');
        if (dropZone) {
            dropZone.addEventListener('dragover', handleDragOver);
            dropZone.addEventListener('dragleave', handleDragLeave);
            dropZone.addEventListener('drop', handleDrop);
            dropZone.addEventListener('click', () => fileInput?.click());
        }

        // Create PDF button
        const createBtn = document.getElementById('tpm-create-btn');
        if (createBtn) {
            createBtn.addEventListener('click', createPdf);
        }

        // Cancel button
        const cancelBtn = document.getElementById('tpm-cancel-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', cancelJob);
        }

        // Settings form
        const settingsForm = document.getElementById('tpm-settings-form');
        if (settingsForm) {
            settingsForm.addEventListener('submit', saveSettings);
        }
    }

    /**
     * Open the merge modal
     */
    async function openModal(e) {
        e?.preventDefault();

        const modal = document.getElementById('tpm-modal');
        if (!modal) return;

        // Reset state
        resetState();

        // Create new job
        try {
            const infoObjId = document.getElementById('tpm-information-object-id')?.value;
            const response = await apiRequest('POST', '/jobs', {
                job_name: 'Merged PDF ' + new Date().toISOString().slice(0, 19).replace('T', ' '),
                information_object_id: infoObjId || null,
                pdf_standard: document.getElementById('tpm-pdf-standard')?.value || 'pdfa-2b',
                dpi: parseInt(document.getElementById('tpm-dpi')?.value || '300'),
                compression_quality: parseInt(document.getElementById('tpm-quality')?.value || '85'),
            });

            if (response.success) {
                currentJob = response.job_id;
                updateJobId(currentJob);
            }
        } catch (error) {
            showAlert('Failed to create job: ' + error.message, 'danger');
            return;
        }

        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        // Initialize sortable for file list
        initSortable();
    }

    /**
     * Reset state
     */
    function resetState() {
        currentJob = null;
        uploadedFiles = [];
        updateFileList();
        updateProgress(0, 0);
        hideAlert();
        document.getElementById('tpm-create-btn')?.setAttribute('disabled', 'disabled');
    }

    /**
     * Initialize sortable file list
     */
    function initSortable() {
        const fileList = document.getElementById('tpm-file-list');
        if (!fileList) return;

        if (sortable) {
            sortable.destroy();
        }

        sortable = new Sortable(fileList, {
            animation: 150,
            ghostClass: 'tpm-sortable-ghost',
            onEnd: handleReorder
        });
    }

    /**
     * Handle file selection
     */
    function handleFileSelect(e) {
        const files = Array.from(e.target.files);
        processFiles(files);
        e.target.value = ''; // Reset input
    }

    /**
     * Handle drag over
     */
    function handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.add('tpm-drag-over');
    }

    /**
     * Handle drag leave
     */
    function handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.remove('tpm-drag-over');
    }

    /**
     * Handle file drop
     */
    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.remove('tpm-drag-over');

        const files = Array.from(e.dataTransfer.files);
        processFiles(files);
    }

    /**
     * Process selected files
     */
    async function processFiles(files) {
        if (!currentJob) {
            showAlert('No active job. Please try again.', 'danger');
            return;
        }

        // Filter valid files
        const validExtensions = ['tif', 'tiff', 'jpg', 'jpeg', 'png', 'bmp', 'gif'];
        const validFiles = files.filter(file => {
            const ext = file.name.split('.').pop().toLowerCase();
            return validExtensions.includes(ext);
        });

        if (validFiles.length === 0) {
            showAlert('No valid image files selected. Supported formats: TIFF, JPEG, PNG, BMP, GIF', 'warning');
            return;
        }

        // Upload files
        let uploaded = 0;
        const total = validFiles.length;

        updateProgress(uploaded, total);
        showProgress(true);

        for (const file of validFiles) {
            try {
                const formData = new FormData();
                formData.append('file', file);

                const response = await fetch(`${API_BASE}/jobs/${currentJob}/upload`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    uploadedFiles.push({
                        id: result.file_id,
                        name: file.name,
                        size: file.size,
                        image_info: result.image_info
                    });
                } else {
                    console.error('Upload failed:', result.error);
                }
            } catch (error) {
                console.error('Upload error:', error);
            }

            uploaded++;
            updateProgress(uploaded, total);
        }

        showProgress(false);
        updateFileList();

        if (uploadedFiles.length > 0) {
            document.getElementById('tpm-create-btn')?.removeAttribute('disabled');
        }
    }

    /**
     * Update file list display
     */
    function updateFileList() {
        const listContainer = document.getElementById('tpm-file-list');
        if (!listContainer) return;

        if (uploadedFiles.length === 0) {
            listContainer.innerHTML = '<div class="text-muted text-center py-4">No files uploaded yet</div>';
            return;
        }

        listContainer.innerHTML = uploadedFiles.map((file, index) => `
            <div class="tpm-file-item d-flex align-items-center p-2 border-bottom" data-file-id="${file.id}">
                <i class="fas fa-grip-vertical text-muted me-3 tpm-drag-handle"></i>
                <span class="badge bg-secondary me-2">${index + 1}</span>
                <i class="fas fa-file-image text-primary me-2"></i>
                <div class="flex-grow-1">
                    <div class="fw-medium">${escapeHtml(file.name)}</div>
                    <small class="text-muted">
                        ${formatFileSize(file.size)}
                        ${file.image_info?.width ? ` • ${file.image_info.width}×${file.image_info.height}px` : ''}
                    </small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="TiffPdfMerge.removeFile(${file.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');

        // Update count badge
        const countBadge = document.getElementById('tpm-file-count');
        if (countBadge) {
            countBadge.textContent = uploadedFiles.length;
        }
    }

    /**
     * Handle file reorder
     */
    async function handleReorder(evt) {
        // Update local array
        const movedFile = uploadedFiles.splice(evt.oldIndex, 1)[0];
        uploadedFiles.splice(evt.newIndex, 0, movedFile);

        // Update server
        const fileOrder = uploadedFiles.map(f => f.id);

        try {
            await apiRequest('POST', `/jobs/${currentJob}/reorder`, { file_order: fileOrder });
        } catch (error) {
            console.error('Reorder failed:', error);
        }

        updateFileList();
    }

    /**
     * Remove a file
     */
    async function removeFile(fileId) {
        if (!confirm('Remove this file from the merge list?')) return;

        try {
            await apiRequest('DELETE', `/jobs/${currentJob}/files/${fileId}`);
            uploadedFiles = uploadedFiles.filter(f => f.id !== fileId);
            updateFileList();

            if (uploadedFiles.length === 0) {
                document.getElementById('tpm-create-btn')?.setAttribute('disabled', 'disabled');
            }
        } catch (error) {
            showAlert('Failed to remove file: ' + error.message, 'danger');
        }
    }

    /**
     * Create the merged PDF
     */
    async function createPdf() {
        if (!currentJob || uploadedFiles.length === 0) {
            showAlert('No files to merge', 'warning');
            return;
        }

        const createBtn = document.getElementById('tpm-create-btn');
        const originalText = createBtn?.innerHTML;

        try {
            createBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Creating PDF...';
            createBtn.setAttribute('disabled', 'disabled');

            const response = await apiRequest('POST', `/jobs/${currentJob}/process`);

            if (response.success) {
                showAlert(`PDF created successfully with ${response.pages} page(s)!`, 'success');

                // Offer download
                const downloadBtn = document.createElement('a');
                downloadBtn.href = `${API_BASE}/jobs/${currentJob}/download`;
                downloadBtn.className = 'btn btn-success ms-2';
                downloadBtn.innerHTML = '<i class="fas fa-download me-1"></i> Download PDF';
                downloadBtn.target = '_blank';

                document.getElementById('tpm-alert')?.appendChild(downloadBtn);

                // Refresh page if attached to record
                if (response.digital_object_id) {
                    setTimeout(() => {
                        if (confirm('PDF has been attached to the record. Reload page to see it?')) {
                            location.reload();
                        }
                    }, 1000);
                }
            } else {
                showAlert('Failed to create PDF: ' + response.error, 'danger');
            }
        } catch (error) {
            showAlert('Failed to create PDF: ' + error.message, 'danger');
        } finally {
            if (createBtn) {
                createBtn.innerHTML = originalText;
            }
        }
    }

    /**
     * Cancel current job
     */
    async function cancelJob() {
        if (!currentJob) {
            closeModal();
            return;
        }

        if (uploadedFiles.length > 0 && !confirm('Cancel and discard uploaded files?')) {
            return;
        }

        try {
            await apiRequest('DELETE', `/jobs/${currentJob}`);
        } catch (error) {
            console.error('Cancel error:', error);
        }

        closeModal();
    }

    /**
     * Close the modal
     */
    function closeModal() {
        const modal = document.getElementById('tpm-modal');
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            bsModal?.hide();
        }
        resetState();
    }

    /**
     * Save settings
     */
    async function saveSettings(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const settings = Object.fromEntries(formData.entries());

        try {
            const response = await apiRequest('POST', '/settings', { settings });
            if (response.success) {
                showAlert('Settings saved successfully', 'success');
            } else {
                showAlert('Failed to save settings: ' + response.error, 'danger');
            }
        } catch (error) {
            showAlert('Failed to save settings: ' + error.message, 'danger');
        }
    }

    /**
     * API request helper
     */
    async function apiRequest(method, endpoint, data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(API_BASE + endpoint, options);
        return response.json();
    }

    /**
     * Update progress display
     */
    function updateProgress(current, total) {
        const progressBar = document.getElementById('tpm-progress-bar');
        const progressText = document.getElementById('tpm-progress-text');

        if (progressBar && total > 0) {
            const percent = Math.round((current / total) * 100);
            progressBar.style.width = percent + '%';
            progressBar.setAttribute('aria-valuenow', percent);
        }

        if (progressText) {
            progressText.textContent = total > 0 ? `${current} of ${total} files` : '';
        }
    }

    /**
     * Show/hide progress
     */
    function showProgress(show) {
        const container = document.getElementById('tpm-progress-container');
        if (container) {
            container.style.display = show ? 'block' : 'none';
        }
    }

    /**
     * Show alert message
     */
    function showAlert(message, type = 'info') {
        const alert = document.getElementById('tpm-alert');
        if (alert) {
            alert.className = `alert alert-${type}`;
            alert.innerHTML = message;
            alert.style.display = 'block';
        }
    }

    /**
     * Hide alert
     */
    function hideAlert() {
        const alert = document.getElementById('tpm-alert');
        if (alert) {
            alert.style.display = 'none';
            alert.innerHTML = '';
        }
    }

    /**
     * Update job ID display
     */
    function updateJobId(jobId) {
        const element = document.getElementById('tpm-job-id');
        if (element) {
            element.textContent = jobId;
        }
    }

    /**
     * Format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Public API
    return {
        init,
        openModal,
        removeFile,
        createPdf,
        cancelJob
    };
})();

// Auto-initialize if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => TiffPdfMerge.init());
} else {
    TiffPdfMerge.init();
}
