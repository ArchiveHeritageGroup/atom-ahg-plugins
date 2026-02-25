/**
 * ahgRegistryPlugin - Discussion & Attachment Interactions
 *
 * Handles:
 * - Reply form toggling (inline reply-to-reply)
 * - Attachment drag-drop upload with preview
 * - Discussion view count tracking
 */
(function () {
    'use strict';

    window.RegistryDiscussions = {

        /**
         * Initialize discussion page interactions.
         */
        init: function () {
            this.initReplyButtons();
            this.initAttachmentUploads();
            this.initReplyQuoting();
        },

        /**
         * Toggle inline reply forms for nested replies.
         */
        initReplyButtons: function () {
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.btn-reply-toggle');
                if (!btn) return;

                e.preventDefault();

                var replyId = btn.getAttribute('data-reply-id');
                var formId = 'reply-form-' + replyId;
                var existing = document.getElementById(formId);

                // Close any other open reply forms
                var openForms = document.querySelectorAll('.inline-reply-form');
                for (var i = 0; i < openForms.length; i++) {
                    if (openForms[i].id !== formId) {
                        openForms[i].remove();
                    }
                }

                // Toggle
                if (existing) {
                    existing.remove();
                    return;
                }

                // Create inline reply form
                var container = btn.closest('.discussion-post');
                var discussionId = btn.getAttribute('data-discussion-id');
                var groupSlug = btn.getAttribute('data-group-slug');
                var actionUrl = '/registry/groups/' + encodeURIComponent(groupSlug) + '/discussions/' + encodeURIComponent(discussionId) + '/reply';

                var form = document.createElement('div');
                form.id = formId;
                form.className = 'inline-reply-form mt-3 p-3 border rounded bg-light';
                form.innerHTML =
                    '<form method="post" action="' + actionUrl + '" enctype="multipart/form-data">' +
                    '<input type="hidden" name="parent_reply_id" value="' + replyId + '">' +
                    '<div class="mb-2">' +
                    '<textarea class="form-control" name="content" rows="3" placeholder="Write your reply..." required></textarea>' +
                    '</div>' +
                    '<div class="d-flex justify-content-between align-items-center">' +
                    '<label class="btn btn-sm btn-outline-secondary mb-0">' +
                    '<i class="fas fa-paperclip me-1"></i> Attach' +
                    '<input type="file" name="attachments[]" multiple class="d-none" accept="image/*,.pdf,.doc,.docx,.txt,.log,.zip">' +
                    '</label>' +
                    '<div>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary me-1 btn-cancel-reply">Cancel</button>' +
                    '<button type="submit" class="btn btn-sm btn-primary">Reply</button>' +
                    '</div>' +
                    '</div>' +
                    '<div class="attachment-preview-list mt-2"></div>' +
                    '</form>';

                container.appendChild(form);

                // Focus textarea
                var textarea = form.querySelector('textarea');
                if (textarea) textarea.focus();

                // Cancel button
                form.querySelector('.btn-cancel-reply').addEventListener('click', function () {
                    form.remove();
                });

                // File input preview
                var fileInput = form.querySelector('input[type="file"]');
                var previewList = form.querySelector('.attachment-preview-list');
                if (fileInput) {
                    fileInput.addEventListener('change', function () {
                        RegistryDiscussions.showFilePreview(fileInput, previewList);
                    });
                }
            });
        },

        /**
         * Initialize attachment upload zones with drag-drop.
         */
        initAttachmentUploads: function () {
            var zones = document.querySelectorAll('.attachment-upload-zone');
            for (var i = 0; i < zones.length; i++) {
                this.setupDropZone(zones[i]);
            }
        },

        /**
         * Set up a drag-drop upload zone.
         */
        setupDropZone: function (zone) {
            var fileInput = zone.querySelector('input[type="file"]');
            var previewList = zone.closest('form') ? zone.closest('form').querySelector('.attachment-preview-list') : null;

            if (!fileInput) return;

            // Drag events
            ['dragenter', 'dragover'].forEach(function (eventName) {
                zone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                zone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.classList.remove('dragover');
                });
            });

            zone.addEventListener('drop', function (e) {
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });

            zone.addEventListener('click', function () {
                fileInput.click();
            });

            // Preview on file selection
            if (previewList) {
                fileInput.addEventListener('change', function () {
                    RegistryDiscussions.showFilePreview(fileInput, previewList);
                });
            }
        },

        /**
         * Show file previews for selected files.
         */
        showFilePreview: function (fileInput, previewContainer) {
            if (!previewContainer) return;
            previewContainer.innerHTML = '';

            var files = fileInput.files;
            if (!files || !files.length) return;

            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                var item = document.createElement('div');
                item.className = 'd-inline-flex align-items-center border rounded px-2 py-1 me-2 mb-2 bg-white';
                item.style.fontSize = '0.85rem';

                var icon = this.getFileIcon(file.type, file.name);
                var size = this.formatFileSize(file.size);

                // Image thumbnail
                if (file.type.startsWith('image/')) {
                    var thumb = document.createElement('img');
                    thumb.style.cssText = 'width:24px;height:24px;object-fit:cover;border-radius:3px;margin-right:6px;';
                    var reader = new FileReader();
                    reader.onload = (function (img) {
                        return function (e) { img.src = e.target.result; };
                    })(thumb);
                    reader.readAsDataURL(file);
                    item.appendChild(thumb);
                } else {
                    var iconSpan = document.createElement('i');
                    iconSpan.className = icon + ' me-1 text-muted';
                    item.appendChild(iconSpan);
                }

                var text = document.createElement('span');
                text.textContent = this.truncateFilename(file.name, 25) + ' (' + size + ')';
                item.appendChild(text);

                previewContainer.appendChild(item);
            }
        },

        /**
         * Get Font Awesome icon class for a file type.
         */
        getFileIcon: function (mimeType, filename) {
            if (!mimeType) mimeType = '';
            if (!filename) filename = '';

            if (mimeType.startsWith('image/')) return 'fas fa-file-image';
            if (mimeType === 'application/pdf') return 'fas fa-file-pdf';
            if (mimeType.indexOf('word') !== -1 || filename.match(/\.docx?$/i)) return 'fas fa-file-word';
            if (mimeType.indexOf('zip') !== -1 || filename.match(/\.(zip|tar|gz|rar)$/i)) return 'fas fa-file-archive';
            if (filename.match(/\.(log|txt)$/i)) return 'fas fa-file-alt';
            return 'fas fa-file';
        },

        /**
         * Format bytes to human-readable size.
         */
        formatFileSize: function (bytes) {
            if (bytes === 0) return '0 B';
            var units = ['B', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(1024));
            return (bytes / Math.pow(1024, i)).toFixed(i > 0 ? 1 : 0) + ' ' + units[i];
        },

        /**
         * Truncate a filename, preserving extension.
         */
        truncateFilename: function (name, maxLen) {
            if (name.length <= maxLen) return name;
            var ext = '';
            var dotIdx = name.lastIndexOf('.');
            if (dotIdx > 0) {
                ext = name.substring(dotIdx);
                name = name.substring(0, dotIdx);
            }
            var keep = maxLen - ext.length - 3;
            if (keep < 5) keep = 5;
            return name.substring(0, keep) + '...' + ext;
        },

        /**
         * Quote a reply's content into the reply form.
         */
        initReplyQuoting: function () {
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.btn-quote-reply');
                if (!btn) return;

                e.preventDefault();

                var replyId = btn.getAttribute('data-reply-id');
                var authorName = btn.getAttribute('data-author') || 'Unknown';
                var postEl = document.getElementById('reply-content-' + replyId);
                if (!postEl) return;

                // Get text content (strip HTML)
                var text = postEl.textContent.trim();
                if (text.length > 300) {
                    text = text.substring(0, 300) + '...';
                }

                var quote = '> **' + authorName + '** wrote:\n> ' + text.replace(/\n/g, '\n> ') + '\n\n';

                // Find or create reply form
                var formId = 'reply-form-' + replyId;
                var form = document.getElementById(formId);
                if (form) {
                    var textarea = form.querySelector('textarea');
                    if (textarea) {
                        textarea.value = quote + textarea.value;
                        textarea.focus();
                    }
                } else {
                    // Trigger reply button first
                    var replyBtn = document.querySelector('.btn-reply-toggle[data-reply-id="' + replyId + '"]');
                    if (replyBtn) {
                        replyBtn.click();
                        setTimeout(function () {
                            var newForm = document.getElementById(formId);
                            if (newForm) {
                                var ta = newForm.querySelector('textarea');
                                if (ta) {
                                    ta.value = quote;
                                    ta.focus();
                                }
                            }
                        }, 100);
                    }
                }
            });
        }
    };

    // Auto-init on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            RegistryDiscussions.init();
        });
    } else {
        RegistryDiscussions.init();
    }
})();
