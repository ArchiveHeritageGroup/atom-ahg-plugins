/**
 * Workflow Manager for Report Builder
 * Status transitions, comment UI, and approval actions.
 */
(function() {
    'use strict';

    /**
     * Valid status transitions map.
     * Key = current status, value = array of allowed next statuses.
     */
    var TRANSITIONS = {
        draft:      ['in_review', 'archived'],
        in_review:  ['draft', 'approved', 'archived'],
        approved:   ['published', 'in_review', 'archived'],
        published:  ['archived'],
        archived:   ['draft']
    };

    var STATUS_META = {
        draft:      { label: 'Draft',      badge: 'bg-secondary',  icon: 'bi-pencil' },
        in_review:  { label: 'In Review',  badge: 'bg-warning text-dark', icon: 'bi-eye' },
        approved:   { label: 'Approved',   badge: 'bg-info',       icon: 'bi-check-circle' },
        published:  { label: 'Published',  badge: 'bg-success',    icon: 'bi-globe' },
        archived:   { label: 'Archived',   badge: 'bg-dark',       icon: 'bi-archive' }
    };

    var WorkflowManager = {
        reportId: null,
        currentStatus: 'draft',
        apiUrls: {},

        /**
         * Initialize the workflow manager.
         *
         * @param {number} reportId       The current report ID
         * @param {string} currentStatus  The current workflow status
         * @param {object} apiUrls        API endpoints: { transition, comments, addComment, resolveComment, history }
         */
        init: function(reportId, currentStatus, apiUrls) {
            this.reportId = reportId;
            this.currentStatus = currentStatus || 'draft';
            this.apiUrls = apiUrls || {};
        },

        /**
         * Render the status bar into a container.
         *
         * @param {HTMLElement|string} container  Container element or its ID
         */
        renderStatusBar: function(container) {
            var self = this;
            if (typeof container === 'string') {
                container = document.getElementById(container);
            }
            if (!container) return;

            var meta = STATUS_META[self.currentStatus] || STATUS_META.draft;
            var transitions = TRANSITIONS[self.currentStatus] || [];

            var html =
                '<div class="d-flex align-items-center flex-wrap gap-2">' +
                    '<span class="badge ' + meta.badge + ' fs-6 py-2 px-3">' +
                        '<i class="bi ' + meta.icon + ' me-1"></i>' + meta.label +
                    '</span>';

            if (transitions.length > 0) {
                html += '<span class="text-muted mx-1"><i class="bi bi-arrow-right"></i></span>';
                transitions.forEach(function(nextStatus) {
                    var nextMeta = STATUS_META[nextStatus] || { label: nextStatus, badge: 'bg-secondary', icon: 'bi-circle' };
                    html +=
                        '<button class="btn btn-sm btn-outline-secondary workflow-transition-btn" data-status="' + nextStatus + '">' +
                            '<i class="bi ' + nextMeta.icon + ' me-1"></i>' + nextMeta.label +
                        '</button>';
                });
            }

            html += '</div>';
            container.innerHTML = html;

            // Bind transition buttons
            container.querySelectorAll('.workflow-transition-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var newStatus = this.dataset.status;
                    var nextMeta = STATUS_META[newStatus] || { label: newStatus };
                    if (confirm('Transition report status to "' + nextMeta.label + '"?')) {
                        self.transitionStatus(newStatus).then(function() {
                            self.renderStatusBar(container);
                        });
                    }
                });
            });
        },

        /**
         * Render the comment panel into a container.
         *
         * @param {HTMLElement|string} container  Container element or its ID
         */
        renderCommentPanel: function(container) {
            var self = this;
            if (typeof container === 'string') {
                container = document.getElementById(container);
            }
            if (!container) return;

            container.innerHTML =
                '<div class="card">' +
                    '<div class="card-header py-2 d-flex justify-content-between align-items-center">' +
                        '<span><i class="bi bi-chat-dots me-1"></i>Comments</span>' +
                        '<button class="btn btn-sm btn-outline-secondary" id="wf-refresh-comments"><i class="bi bi-arrow-clockwise"></i></button>' +
                    '</div>' +
                    '<div class="card-body p-0">' +
                        '<div id="wf-comments-list" style="max-height:400px;overflow-y:auto;"></div>' +
                    '</div>' +
                    '<div class="card-footer">' +
                        '<div class="input-group">' +
                            '<input type="text" class="form-control form-control-sm" id="wf-comment-input" placeholder="Add a comment...">' +
                            '<button class="btn btn-sm btn-primary" id="wf-add-comment"><i class="bi bi-send"></i></button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            // Load comments
            self.loadComments();

            // Bind refresh
            document.getElementById('wf-refresh-comments').addEventListener('click', function() {
                self.loadComments();
            });

            // Bind add comment
            document.getElementById('wf-add-comment').addEventListener('click', function() {
                var input = document.getElementById('wf-comment-input');
                var content = input.value.trim();
                if (content) {
                    self.addComment(content, null).then(function() {
                        input.value = '';
                        self.loadComments();
                    });
                }
            });

            // Allow Enter key to submit
            document.getElementById('wf-comment-input').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('wf-add-comment').click();
                }
            });
        },

        /**
         * Load and render comments from the API.
         */
        loadComments: function() {
            var self = this;
            var listEl = document.getElementById('wf-comments-list');
            if (!listEl) return;

            if (!self.apiUrls.comments) {
                listEl.innerHTML = '<div class="text-center text-muted py-3 small">Comments not configured</div>';
                return;
            }

            listEl.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></div>';

            fetch(self.apiUrls.comments + '?report_id=' + self.reportId)
            .then(function(response) { return response.json(); })
            .then(function(result) {
                var comments = result.data || result.comments || [];
                if (comments.length === 0) {
                    listEl.innerHTML = '<div class="text-center text-muted py-3 small">No comments yet</div>';
                    return;
                }

                var html = '';
                comments.forEach(function(comment) {
                    var resolved = comment.is_resolved || comment.resolved;
                    html +=
                        '<div class="border-bottom p-2' + (resolved ? ' bg-light' : '') + '">' +
                            '<div class="d-flex justify-content-between align-items-start">' +
                                '<div>' +
                                    '<strong class="small">' + self._escHtml(comment.user_name || 'Unknown') + '</strong>' +
                                    '<small class="text-muted ms-2">' + self._escHtml(comment.created_at || '') + '</small>' +
                                    (comment.section_id ? '<span class="badge bg-light text-dark ms-2 small">Section #' + comment.section_id + '</span>' : '') +
                                '</div>' +
                                '<div>' +
                                    (resolved
                                        ? '<span class="badge bg-success"><i class="bi bi-check"></i> Resolved</span>'
                                        : '<button class="btn btn-sm btn-outline-success py-0 px-1 wf-resolve-btn" data-comment-id="' + comment.id + '" title="Resolve"><i class="bi bi-check"></i></button>'
                                    ) +
                                '</div>' +
                            '</div>' +
                            '<p class="mb-0 small mt-1' + (resolved ? ' text-decoration-line-through text-muted' : '') + '">' +
                                self._escHtml(comment.content || comment.text || '') +
                            '</p>' +
                        '</div>';
                });

                listEl.innerHTML = html;

                // Bind resolve buttons
                listEl.querySelectorAll('.wf-resolve-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var commentId = parseInt(this.dataset.commentId, 10);
                        self.resolveComment(commentId).then(function() {
                            self.loadComments();
                        });
                    });
                });
            })
            .catch(function(err) {
                listEl.innerHTML = '<div class="text-center text-danger py-3 small">Failed to load comments</div>';
                console.error('WorkflowManager: load comments error', err);
            });
        },

        /**
         * Add a comment to the report.
         *
         * @param {string} content     The comment text
         * @param {number|null} sectionId  Optional section ID for section-specific comments
         * @returns {Promise}
         */
        addComment: function(content, sectionId) {
            var self = this;
            if (!self.apiUrls.addComment) {
                console.error('WorkflowManager: addComment URL not configured');
                return Promise.reject(new Error('addComment URL not configured'));
            }

            return fetch(self.apiUrls.addComment, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    report_id: self.reportId,
                    content: content,
                    section_id: sectionId
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (!result.success) {
                    alert('Error adding comment: ' + (result.error || 'Unknown error'));
                }
                return result;
            })
            .catch(function(err) {
                console.error('WorkflowManager: add comment error', err);
            });
        },

        /**
         * Mark a comment as resolved.
         *
         * @param {number} commentId  The comment ID to resolve
         * @returns {Promise}
         */
        resolveComment: function(commentId) {
            var self = this;
            if (!self.apiUrls.resolveComment) {
                console.error('WorkflowManager: resolveComment URL not configured');
                return Promise.reject(new Error('resolveComment URL not configured'));
            }

            return fetch(self.apiUrls.resolveComment, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: commentId })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (!result.success) {
                    alert('Error resolving comment: ' + (result.error || 'Unknown error'));
                }
                return result;
            })
            .catch(function(err) {
                console.error('WorkflowManager: resolve comment error', err);
            });
        },

        /**
         * Transition the report to a new status.
         *
         * @param {string} newStatus  The target status
         * @returns {Promise}
         */
        transitionStatus: function(newStatus) {
            var self = this;
            var allowed = TRANSITIONS[self.currentStatus] || [];

            if (allowed.indexOf(newStatus) === -1) {
                alert('Cannot transition from "' + self.currentStatus + '" to "' + newStatus + '".');
                return Promise.reject(new Error('Invalid transition'));
            }

            if (!self.apiUrls.transition) {
                console.error('WorkflowManager: transition URL not configured');
                return Promise.reject(new Error('Transition URL not configured'));
            }

            return fetch(self.apiUrls.transition, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    report_id: self.reportId,
                    from_status: self.currentStatus,
                    to_status: newStatus
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    self.currentStatus = newStatus;
                } else {
                    alert('Transition failed: ' + (result.error || 'Unknown error'));
                }
                return result;
            })
            .catch(function(err) {
                console.error('WorkflowManager: transition error', err);
            });
        },

        /**
         * Load and display version history in a container.
         *
         * @param {HTMLElement|string} container  Container element or its ID
         */
        loadVersionHistory: function(container) {
            var self = this;
            if (typeof container === 'string') {
                container = document.getElementById(container);
            }
            if (!container) return;

            if (!self.apiUrls.history) {
                container.innerHTML = '<div class="text-center text-muted py-3 small">Version history not configured</div>';
                return;
            }

            container.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span> Loading history...</div>';

            fetch(self.apiUrls.history + '?report_id=' + self.reportId)
            .then(function(response) { return response.json(); })
            .then(function(result) {
                var versions = result.data || result.versions || [];
                if (versions.length === 0) {
                    container.innerHTML = '<div class="text-center text-muted py-3 small">No version history</div>';
                    return;
                }

                var html = '<div class="list-group list-group-flush">';
                versions.forEach(function(version, index) {
                    html +=
                        '<div class="list-group-item">' +
                            '<div class="d-flex justify-content-between align-items-center">' +
                                '<div>' +
                                    '<strong>v' + self._escHtml(String(version.version_number || (versions.length - index))) + '</strong>' +
                                    '<small class="text-muted ms-2">' + self._escHtml(version.created_at || '') + '</small>' +
                                    ' <small class="text-muted">by ' + self._escHtml(version.user_name || 'Unknown') + '</small>' +
                                '</div>' +
                                '<div class="btn-group btn-group-sm">' +
                                    '<button class="btn btn-outline-secondary wf-restore-version" data-version-id="' + version.id + '"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button>' +
                                    '<button class="btn btn-outline-secondary wf-compare-version" data-version-id="' + version.id + '"><i class="bi bi-arrows-angle-expand me-1"></i>Compare</button>' +
                                '</div>' +
                            '</div>' +
                            (version.summary ? '<p class="mb-0 small mt-1 text-muted">' + self._escHtml(version.summary) + '</p>' : '') +
                        '</div>';
                });
                html += '</div>';

                container.innerHTML = html;
            })
            .catch(function(err) {
                container.innerHTML = '<div class="text-center text-danger py-3 small">Failed to load history</div>';
                console.error('WorkflowManager: load history error', err);
            });
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
        }
    };

    window.WorkflowManager = WorkflowManager;
})();
