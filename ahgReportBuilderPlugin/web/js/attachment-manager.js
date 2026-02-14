/**
 * Attachment Manager for Report Builder
 * Upload UI with drag-drop, image gallery, reordering.
 */
(function() {
    'use strict';

    var MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    var ALLOWED_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    var ALLOWED_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.pdf', '.doc', '.docx', '.xls', '.xlsx'];

    var AttachmentManager = {
        reportId: null,
        sectionId: null,
        apiUrls: {},

        /**
         * Initialize the attachment manager.
         *
         * @param {number} reportId   The current report ID
         * @param {number|null} sectionId  Optional section ID
         * @param {object} apiUrls    API endpoints: { upload, delete, reorder, list }
         */
        init: function(reportId, sectionId, apiUrls) {
            this.reportId = reportId;
            this.sectionId = sectionId || null;
            this.apiUrls = apiUrls || {};
        },

        /**
         * Render the upload drop zone into a container.
         *
         * @param {HTMLElement|string} container  Container element or its ID
         */
        renderUploadZone: function(container) {
            var self = this;
            if (typeof container === 'string') {
                container = document.getElementById(container);
            }
            if (!container) return;

            container.innerHTML =
                '<div class="upload-zone border border-2 border-dashed rounded p-4 text-center" id="am-drop-zone" ' +
                    'style="cursor:pointer;transition:background-color 0.2s;">' +
                    '<i class="bi bi-cloud-arrow-up fs-1 text-muted d-block mb-2"></i>' +
                    '<p class="mb-1">Drag and drop files here, or click to browse</p>' +
                    '<small class="text-muted">Max 10MB per file. Supported: images, PDF, DOC, XLSX</small>' +
                    '<input type="file" id="am-file-input" multiple style="display:none;" ' +
                        'accept="' + ALLOWED_EXTENSIONS.join(',') + '">' +
                '</div>' +
                '<div id="am-upload-progress" class="mt-2"></div>';

            var dropZone = document.getElementById('am-drop-zone');
            var fileInput = document.getElementById('am-file-input');

            // Click to open file dialog
            dropZone.addEventListener('click', function() {
                fileInput.click();
            });

            // File input change
            fileInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    self._handleFiles(Array.from(this.files));
                    this.value = ''; // Reset to allow re-selecting same file
                }
            });

            // Drag and drop events
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.backgroundColor = 'rgba(13, 110, 253, 0.05)';
                this.classList.add('border-primary');
            });

            dropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.backgroundColor = '';
                this.classList.remove('border-primary');
            });

            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.backgroundColor = '';
                this.classList.remove('border-primary');

                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    self._handleFiles(Array.from(e.dataTransfer.files));
                }
            });
        },

        /**
         * Render the attachment gallery into a container.
         *
         * @param {HTMLElement|string} container    Container element or its ID
         * @param {Array} attachments  Array of attachment objects
         */
        renderGallery: function(container, attachments) {
            var self = this;
            if (typeof container === 'string') {
                container = document.getElementById(container);
            }
            if (!container) return;

            if (!attachments || attachments.length === 0) {
                container.innerHTML =
                    '<div class="text-center text-muted py-3">' +
                        '<i class="bi bi-images fs-3 d-block mb-2"></i>' +
                        '<small>No attachments yet.</small>' +
                    '</div>';
                return;
            }

            var html =
                '<div class="row g-3" id="am-gallery-grid">';

            attachments.forEach(function(att) {
                var isImage = att.mime_type && att.mime_type.indexOf('image/') === 0;
                var icon = self._getFileIcon(att.mime_type || att.filename);

                html +=
                    '<div class="col-6 col-md-4 col-lg-3 am-gallery-item" data-attachment-id="' + att.id + '">' +
                        '<div class="card h-100">' +
                            '<div class="position-relative">' +
                                (isImage
                                    ? '<img src="' + self._escAttr(att.url || att.thumbnail_url || '') + '" class="card-img-top" alt="' + self._escAttr(att.filename || '') + '" style="height:140px;object-fit:cover;">'
                                    : '<div class="d-flex align-items-center justify-content-center bg-light" style="height:140px;"><i class="bi ' + icon + ' fs-1 text-muted"></i></div>'
                                ) +
                                '<div class="position-absolute top-0 end-0 p-1">' +
                                    '<button class="btn btn-sm btn-danger am-delete-btn" data-attachment-id="' + att.id + '" title="Delete">' +
                                        '<i class="bi bi-trash"></i>' +
                                    '</button>' +
                                '</div>' +
                                '<div class="position-absolute top-0 start-0 p-1">' +
                                    '<span class="badge bg-dark bg-opacity-75" style="cursor:grab;"><i class="bi bi-grip-vertical am-drag-handle"></i></span>' +
                                '</div>' +
                            '</div>' +
                            '<div class="card-body p-2">' +
                                '<p class="card-text small mb-0 text-truncate" title="' + self._escAttr(att.filename || '') + '">' +
                                    self._escHtml(att.filename || 'Unnamed') +
                                '</p>' +
                                (att.file_size ? '<small class="text-muted">' + self._formatSize(att.file_size) + '</small>' : '') +
                            '</div>' +
                        '</div>' +
                    '</div>';
            });

            html += '</div>';
            container.innerHTML = html;

            // Bind delete buttons
            container.querySelectorAll('.am-delete-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var attId = parseInt(this.dataset.attachmentId, 10);
                    self.deleteAttachment(attId).then(function(result) {
                        if (result && result.success) {
                            var item = container.querySelector('[data-attachment-id="' + attId + '"]');
                            if (item) item.remove();
                            // Check if gallery is now empty
                            if (container.querySelectorAll('.am-gallery-item').length === 0) {
                                self.renderGallery(container, []);
                            }
                        }
                    });
                });
            });

            // Initialize drag-to-reorder with SortableJS
            self._initSortable(container);
        },

        /**
         * Upload a file.
         *
         * @param {File} file  The file to upload
         * @returns {Promise}
         */
        upload: function(file) {
            var self = this;
            if (!self.apiUrls.upload) {
                console.error('AttachmentManager: upload URL not configured');
                return Promise.reject(new Error('Upload URL not configured'));
            }

            var formData = new FormData();
            formData.append('file', file);
            formData.append('report_id', self.reportId);
            if (self.sectionId) {
                formData.append('section_id', self.sectionId);
            }

            return fetch(self.apiUrls.upload, {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (!result.success) {
                    throw new Error(result.error || 'Upload failed');
                }
                return result;
            });
        },

        /**
         * Delete an attachment by ID.
         *
         * @param {number} id  The attachment ID
         * @returns {Promise}
         */
        deleteAttachment: function(id) {
            var self = this;
            if (!confirm('Delete this attachment?')) {
                return Promise.resolve();
            }

            if (!self.apiUrls.delete) {
                console.error('AttachmentManager: delete URL not configured');
                return Promise.reject(new Error('Delete URL not configured'));
            }

            return fetch(self.apiUrls.delete, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (!result.success) {
                    alert('Error deleting attachment: ' + (result.error || 'Unknown error'));
                }
                return result;
            })
            .catch(function(err) {
                console.error('AttachmentManager: delete error', err);
            });
        },

        /**
         * Reorder attachments by sending a new order of IDs.
         *
         * @param {Array<number>} ids  Ordered array of attachment IDs
         * @returns {Promise}
         */
        reorderAttachments: function(ids) {
            var self = this;
            if (!self.apiUrls.reorder) {
                console.warn('AttachmentManager: reorder URL not configured');
                return Promise.resolve();
            }

            return fetch(self.apiUrls.reorder, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: ids })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (!result.success) {
                    console.warn('Reorder failed:', result.error);
                }
                return result;
            })
            .catch(function(err) {
                console.error('AttachmentManager: reorder error', err);
            });
        },

        /**
         * Process files for upload with validation.
         * @private
         * @param {Array<File>} files
         */
        _handleFiles: function(files) {
            var self = this;
            var progressContainer = document.getElementById('am-upload-progress');

            files.forEach(function(file) {
                // Validate file size
                if (file.size > MAX_FILE_SIZE) {
                    self._showUploadError(progressContainer, file.name, 'File exceeds 10MB limit (' + self._formatSize(file.size) + ')');
                    return;
                }

                // Validate file type
                if (ALLOWED_TYPES.indexOf(file.type) === -1) {
                    var ext = '.' + file.name.split('.').pop().toLowerCase();
                    if (ALLOWED_EXTENSIONS.indexOf(ext) === -1) {
                        self._showUploadError(progressContainer, file.name, 'File type not supported');
                        return;
                    }
                }

                // Show progress bar
                var progressId = 'progress-' + Date.now() + '-' + Math.random().toString(36).substring(7);
                var progressHtml =
                    '<div class="d-flex align-items-center mb-2" id="' + progressId + '">' +
                        '<small class="text-truncate me-2" style="max-width:200px;">' + self._escHtml(file.name) + '</small>' +
                        '<div class="progress flex-grow-1" style="height:6px;">' +
                            '<div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>' +
                        '</div>' +
                    '</div>';
                progressContainer.insertAdjacentHTML('beforeend', progressHtml);

                // Upload
                self.upload(file)
                .then(function(result) {
                    var el = document.getElementById(progressId);
                    if (el) {
                        el.innerHTML =
                            '<small class="text-truncate me-2 text-success" style="max-width:200px;">' +
                                '<i class="bi bi-check-circle me-1"></i>' + self._escHtml(file.name) +
                            '</small>';
                        setTimeout(function() { el.remove(); }, 3000);
                    }
                })
                .catch(function(err) {
                    var el = document.getElementById(progressId);
                    if (el) {
                        el.innerHTML =
                            '<small class="text-truncate me-2 text-danger" style="max-width:200px;">' +
                                '<i class="bi bi-x-circle me-1"></i>' + self._escHtml(file.name) +
                                ' - ' + self._escHtml(err.message) +
                            '</small>';
                        setTimeout(function() { el.remove(); }, 5000);
                    }
                });
            });
        },

        /**
         * Show an upload error message.
         * @private
         */
        _showUploadError: function(container, filename, message) {
            if (!container) return;
            var errHtml =
                '<div class="alert alert-danger alert-dismissible py-1 mb-2 small">' +
                    '<i class="bi bi-exclamation-triangle me-1"></i>' +
                    '<strong>' + this._escHtml(filename) + ':</strong> ' + this._escHtml(message) +
                    '<button type="button" class="btn-close py-1" data-bs-dismiss="alert"></button>' +
                '</div>';
            container.insertAdjacentHTML('beforeend', errHtml);
        },

        /**
         * Initialize SortableJS on the gallery grid.
         * @private
         */
        _initSortable: function(container) {
            var self = this;
            var grid = container.querySelector('#am-gallery-grid');
            if (!grid) return;

            if (typeof Sortable === 'undefined') {
                setTimeout(function() { self._initSortable(container); }, 200);
                return;
            }

            if (grid._sortableInstance) {
                grid._sortableInstance.destroy();
            }

            grid._sortableInstance = new Sortable(grid, {
                animation: 150,
                handle: '.am-drag-handle',
                ghostClass: 'opacity-50',
                onEnd: function() {
                    var ids = [];
                    grid.querySelectorAll('.am-gallery-item').forEach(function(item) {
                        var id = parseInt(item.dataset.attachmentId, 10);
                        if (id) ids.push(id);
                    });
                    self.reorderAttachments(ids);
                }
            });
        },

        /**
         * Get a Bootstrap icon class for a file MIME type.
         * @private
         * @param {string} mimeOrFilename
         * @returns {string}
         */
        _getFileIcon: function(mimeOrFilename) {
            if (!mimeOrFilename) return 'bi-file-earmark';
            var str = mimeOrFilename.toLowerCase();
            if (str.indexOf('pdf') !== -1) return 'bi-filetype-pdf';
            if (str.indexOf('word') !== -1 || str.indexOf('.doc') !== -1) return 'bi-filetype-doc';
            if (str.indexOf('excel') !== -1 || str.indexOf('sheet') !== -1 || str.indexOf('.xls') !== -1) return 'bi-filetype-xlsx';
            if (str.indexOf('image') !== -1) return 'bi-file-image';
            return 'bi-file-earmark';
        },

        /**
         * Format a file size in bytes to a human-readable string.
         * @private
         * @param {number} bytes
         * @returns {string}
         */
        _formatSize: function(bytes) {
            if (!bytes) return '0 B';
            var units = ['B', 'KB', 'MB', 'GB'];
            var i = 0;
            while (bytes >= 1024 && i < units.length - 1) {
                bytes /= 1024;
                i++;
            }
            return bytes.toFixed(i > 0 ? 1 : 0) + ' ' + units[i];
        },

        /**
         * Escape HTML entities.
         * @param {string} str
         * @returns {string}
         */
        _escHtml: function(str) {
            if (str === null || str === undefined) return '';
            var div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        },

        /**
         * Escape for HTML attribute.
         * @param {string} str
         * @returns {string}
         */
        _escAttr: function(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
    };

    window.AttachmentManager = AttachmentManager;
})();
