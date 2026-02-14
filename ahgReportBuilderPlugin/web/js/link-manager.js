/**
 * Link Manager for Report Builder
 * Manages internal (AtoM entity) and external links with OpenGraph preview.
 */
(function() {
    'use strict';

    var LinkManager = {
        reportId: null,
        apiUrls: {},
        _debounceTimer: null,

        /**
         * Initialize the link manager.
         *
         * @param {number} reportId  The current report ID
         * @param {object} apiUrls   API endpoint URLs: { save, delete, ogPreview, entitySearch, list }
         */
        init: function(reportId, apiUrls) {
            this.reportId = reportId;
            this.apiUrls = apiUrls || {};
        },

        /**
         * Open the add/edit link modal for a given section.
         *
         * @param {number|null} sectionId  Optional section ID to associate the link with
         * @param {object|null} existing   Optional existing link data for editing
         */
        addLink: function(sectionId, existing) {
            var self = this;
            var isEdit = existing && existing.id;

            // Remove any existing modal
            var oldModal = document.getElementById('linkManagerModal');
            if (oldModal) oldModal.remove();

            var modal = document.createElement('div');
            modal.id = 'linkManagerModal';
            modal.className = 'modal fade';
            modal.tabIndex = -1;
            modal.innerHTML =
                '<div class="modal-dialog modal-lg">' +
                    '<div class="modal-content">' +
                        '<div class="modal-header">' +
                            '<h5 class="modal-title"><i class="bi bi-link-45deg me-2"></i>' + (isEdit ? 'Edit Link' : 'Add Link') + '</h5>' +
                            '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
                        '</div>' +
                        '<div class="modal-body">' +
                            '<div class="mb-3">' +
                                '<label class="form-label">Link Type</label>' +
                                '<select class="form-select" id="lm-link-type">' +
                                    '<option value="external"' + (existing && existing.link_type === 'external' ? ' selected' : '') + '>External URL</option>' +
                                    '<option value="internal"' + (existing && existing.link_type === 'internal' ? ' selected' : '') + '>Internal (AtoM Entity)</option>' +
                                '</select>' +
                            '</div>' +
                            '<div id="lm-external-fields"' + (existing && existing.link_type === 'internal' ? ' style="display:none"' : '') + '>' +
                                '<div class="mb-3">' +
                                    '<label class="form-label">URL</label>' +
                                    '<div class="input-group">' +
                                        '<input type="url" class="form-control" id="lm-url" placeholder="https://example.com" value="' + self._escAttr(existing ? existing.url : '') + '">' +
                                        '<button class="btn btn-outline-secondary" type="button" id="lm-fetch-og"><i class="bi bi-search me-1"></i>Fetch</button>' +
                                    '</div>' +
                                    '<div class="form-text">Paste a URL and click Fetch to auto-fill title and description from OpenGraph metadata.</div>' +
                                '</div>' +
                                '<div id="lm-og-preview" class="mb-3" style="display:none;">' +
                                    '<div class="card">' +
                                        '<div class="row g-0">' +
                                            '<div class="col-md-4" id="lm-og-image-col" style="display:none;">' +
                                                '<img id="lm-og-image" class="img-fluid rounded-start" alt="Preview" style="max-height:150px;object-fit:cover;width:100%;">' +
                                            '</div>' +
                                            '<div class="col" id="lm-og-text-col">' +
                                                '<div class="card-body py-2">' +
                                                    '<h6 class="card-title mb-1" id="lm-og-title"></h6>' +
                                                    '<p class="card-text small text-muted mb-0" id="lm-og-desc"></p>' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div id="lm-internal-fields"' + (existing && existing.link_type === 'internal' ? '' : ' style="display:none"') + '>' +
                                '<div class="mb-3">' +
                                    '<label class="form-label">Search AtoM Entities</label>' +
                                    '<input type="text" class="form-control" id="lm-entity-search" placeholder="Type to search descriptions, actors, repositories..." autocomplete="off">' +
                                    '<div id="lm-entity-results" class="list-group mt-1" style="max-height:200px;overflow-y:auto;display:none;"></div>' +
                                    '<input type="hidden" id="lm-entity-id" value="' + self._escAttr(existing ? existing.entity_id : '') + '">' +
                                    '<input type="hidden" id="lm-entity-type" value="' + self._escAttr(existing ? existing.entity_type : '') + '">' +
                                '</div>' +
                            '</div>' +
                            '<div class="mb-3">' +
                                '<label class="form-label">Title</label>' +
                                '<input type="text" class="form-control" id="lm-title" value="' + self._escAttr(existing ? existing.title : '') + '">' +
                            '</div>' +
                            '<div class="mb-3">' +
                                '<label class="form-label">Description</label>' +
                                '<textarea class="form-control" id="lm-description" rows="2">' + self._escHtml(existing ? existing.description : '') + '</textarea>' +
                            '</div>' +
                            '<div class="mb-3">' +
                                '<label class="form-label">Category</label>' +
                                '<select class="form-select" id="lm-category">' +
                                    '<option value="">-- None --</option>' +
                                    '<option value="reference"' + (existing && existing.category === 'reference' ? ' selected' : '') + '>Reference</option>' +
                                    '<option value="related"' + (existing && existing.category === 'related' ? ' selected' : '') + '>Related</option>' +
                                    '<option value="source"' + (existing && existing.category === 'source' ? ' selected' : '') + '>Source</option>' +
                                    '<option value="appendix"' + (existing && existing.category === 'appendix' ? ' selected' : '') + '>Appendix</option>' +
                                '</select>' +
                            '</div>' +
                        '</div>' +
                        '<div class="modal-footer">' +
                            '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>' +
                            '<button type="button" class="btn btn-primary" id="lm-save-btn"><i class="bi bi-check-lg me-1"></i>' + (isEdit ? 'Update' : 'Add') + ' Link</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            document.body.appendChild(modal);

            var bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            // Toggle external/internal fields
            var linkTypeSelect = document.getElementById('lm-link-type');
            linkTypeSelect.addEventListener('change', function() {
                var isExternal = this.value === 'external';
                document.getElementById('lm-external-fields').style.display = isExternal ? '' : 'none';
                document.getElementById('lm-internal-fields').style.display = isExternal ? 'none' : '';
            });

            // Fetch OG preview
            document.getElementById('lm-fetch-og').addEventListener('click', function() {
                var url = document.getElementById('lm-url').value.trim();
                if (url) {
                    self.fetchOgPreview(url);
                }
            });

            // Entity search with debounce
            var entitySearchInput = document.getElementById('lm-entity-search');
            entitySearchInput.addEventListener('input', function() {
                var query = this.value.trim();
                if (self._debounceTimer) clearTimeout(self._debounceTimer);
                if (query.length < 2) {
                    document.getElementById('lm-entity-results').style.display = 'none';
                    return;
                }
                self._debounceTimer = setTimeout(function() {
                    self.searchEntities(query, null);
                }, 300);
            });

            // Save button
            document.getElementById('lm-save-btn').addEventListener('click', function() {
                var linkType = document.getElementById('lm-link-type').value;
                var data = {
                    report_id: self.reportId,
                    section_id: sectionId || null,
                    link_type: linkType,
                    url: linkType === 'external' ? document.getElementById('lm-url').value.trim() : null,
                    entity_id: linkType === 'internal' ? document.getElementById('lm-entity-id').value : null,
                    entity_type: linkType === 'internal' ? document.getElementById('lm-entity-type').value : null,
                    title: document.getElementById('lm-title').value.trim(),
                    description: document.getElementById('lm-description').value.trim(),
                    category: document.getElementById('lm-category').value
                };

                if (isEdit) {
                    data.id = existing.id;
                }

                self.saveLink(data).then(function() {
                    bsModal.hide();
                });
            });

            // Clean up on close
            modal.addEventListener('hidden.bs.modal', function() {
                modal.remove();
            });
        },

        /**
         * Save a link via API.
         *
         * @param {object} data  Link data to save
         * @returns {Promise}
         */
        saveLink: function(data) {
            var self = this;
            if (!self.apiUrls.save) {
                console.error('LinkManager: save URL not configured');
                return Promise.reject(new Error('Save URL not configured'));
            }

            return fetch(self.apiUrls.save, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (!result.success) {
                    alert('Error saving link: ' + (result.error || 'Unknown error'));
                    throw new Error(result.error || 'Save failed');
                }
                return result;
            })
            .catch(function(err) {
                console.error('LinkManager save error:', err);
                throw err;
            });
        },

        /**
         * Delete a link by ID.
         *
         * @param {number} linkId  The link ID to delete
         * @returns {Promise}
         */
        deleteLink: function(linkId) {
            var self = this;
            if (!confirm('Are you sure you want to remove this link?')) {
                return Promise.resolve();
            }
            if (!self.apiUrls.delete) {
                console.error('LinkManager: delete URL not configured');
                return Promise.reject(new Error('Delete URL not configured'));
            }

            return fetch(self.apiUrls.delete, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: linkId })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (!result.success) {
                    alert('Error deleting link: ' + (result.error || 'Unknown error'));
                }
                return result;
            })
            .catch(function(err) {
                console.error('LinkManager delete error:', err);
            });
        },

        /**
         * Fetch OpenGraph metadata for a URL and populate the modal fields.
         *
         * @param {string} url  The URL to fetch OG data for
         */
        fetchOgPreview: function(url) {
            var self = this;
            var fetchBtn = document.getElementById('lm-fetch-og');
            if (fetchBtn) {
                fetchBtn.disabled = true;
                fetchBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Fetching...';
            }

            if (!self.apiUrls.ogPreview) {
                console.warn('LinkManager: ogPreview URL not configured, skipping fetch');
                if (fetchBtn) {
                    fetchBtn.disabled = false;
                    fetchBtn.innerHTML = '<i class="bi bi-search me-1"></i>Fetch';
                }
                return;
            }

            fetch(self.apiUrls.ogPreview, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url: url })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success && result.data) {
                    var ogData = result.data;

                    // Populate title and description fields
                    var titleInput = document.getElementById('lm-title');
                    var descInput = document.getElementById('lm-description');
                    if (ogData.title && !titleInput.value) {
                        titleInput.value = ogData.title;
                    }
                    if (ogData.description && !descInput.value) {
                        descInput.value = ogData.description;
                    }

                    // Show OG preview card
                    var previewDiv = document.getElementById('lm-og-preview');
                    previewDiv.style.display = '';

                    document.getElementById('lm-og-title').textContent = ogData.title || url;
                    document.getElementById('lm-og-desc').textContent = ogData.description || '';

                    if (ogData.image) {
                        document.getElementById('lm-og-image').src = ogData.image;
                        document.getElementById('lm-og-image-col').style.display = '';
                        document.getElementById('lm-og-text-col').className = 'col-md-8';
                    } else {
                        document.getElementById('lm-og-image-col').style.display = 'none';
                        document.getElementById('lm-og-text-col').className = 'col';
                    }
                }
            })
            .catch(function(err) {
                console.error('OG fetch error:', err);
            })
            .finally(function() {
                if (fetchBtn) {
                    fetchBtn.disabled = false;
                    fetchBtn.innerHTML = '<i class="bi bi-search me-1"></i>Fetch';
                }
            });
        },

        /**
         * Search AtoM entities with autocomplete.
         *
         * @param {string} query  The search query
         * @param {string|null} type  Optional entity type filter (e.g. 'informationobject', 'actor', 'repository')
         */
        searchEntities: function(query, type) {
            var self = this;
            var resultsContainer = document.getElementById('lm-entity-results');
            if (!resultsContainer) return;

            if (!self.apiUrls.entitySearch) {
                console.warn('LinkManager: entitySearch URL not configured');
                return;
            }

            var params = new URLSearchParams({ q: query });
            if (type) params.append('type', type);

            fetch(self.apiUrls.entitySearch + '?' + params.toString())
            .then(function(response) { return response.json(); })
            .then(function(result) {
                resultsContainer.innerHTML = '';
                var entities = result.data || result.results || [];

                if (entities.length === 0) {
                    resultsContainer.innerHTML = '<div class="list-group-item text-muted small">No results found</div>';
                    resultsContainer.style.display = '';
                    return;
                }

                entities.forEach(function(entity) {
                    var item = document.createElement('a');
                    item.href = '#';
                    item.className = 'list-group-item list-group-item-action py-2';

                    var typeIcon = 'bi-file-earmark';
                    if (entity.type === 'actor') typeIcon = 'bi-person';
                    else if (entity.type === 'repository') typeIcon = 'bi-building';
                    else if (entity.type === 'informationobject') typeIcon = 'bi-archive';
                    else if (entity.type === 'term') typeIcon = 'bi-tag';

                    item.innerHTML =
                        '<div class="d-flex align-items-center">' +
                            '<i class="bi ' + typeIcon + ' text-muted me-2"></i>' +
                            '<div>' +
                                '<div class="small fw-bold">' + self._escHtml(entity.title || entity.name || '') + '</div>' +
                                '<small class="text-muted">' + self._escHtml(entity.type || '') + (entity.identifier ? ' | ' + self._escHtml(entity.identifier) : '') + '</small>' +
                            '</div>' +
                        '</div>';

                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        document.getElementById('lm-entity-id').value = entity.id;
                        document.getElementById('lm-entity-type').value = entity.type || '';
                        document.getElementById('lm-entity-search').value = entity.title || entity.name || '';
                        if (!document.getElementById('lm-title').value) {
                            document.getElementById('lm-title').value = entity.title || entity.name || '';
                        }
                        resultsContainer.style.display = 'none';
                    });

                    resultsContainer.appendChild(item);
                });

                resultsContainer.style.display = '';
            })
            .catch(function(err) {
                console.error('Entity search error:', err);
                resultsContainer.innerHTML = '<div class="list-group-item text-danger small">Search error</div>';
                resultsContainer.style.display = '';
            });
        },

        /**
         * Render a list of links into a container element.
         *
         * @param {HTMLElement|string} container  Container element or its ID
         * @param {Array} links  Array of link objects
         */
        renderLinks: function(container, links) {
            var self = this;
            if (typeof container === 'string') {
                container = document.getElementById(container);
            }
            if (!container) return;

            if (!links || links.length === 0) {
                container.innerHTML =
                    '<div class="text-center text-muted py-3">' +
                        '<i class="bi bi-link-45deg fs-3 d-block mb-2"></i>' +
                        '<small>No links added yet.</small>' +
                    '</div>';
                return;
            }

            var html = '<div class="list-group">';
            links.forEach(function(link) {
                var icon = link.link_type === 'internal' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-up-right';
                var categoryBadge = link.category
                    ? '<span class="badge bg-secondary ms-2">' + self._escHtml(link.category) + '</span>'
                    : '';

                html +=
                    '<div class="list-group-item" data-link-id="' + link.id + '">' +
                        '<div class="d-flex justify-content-between align-items-start">' +
                            '<div class="flex-grow-1">' +
                                '<div class="d-flex align-items-center mb-1">' +
                                    '<i class="bi ' + icon + ' text-primary me-2"></i>' +
                                    '<strong class="small">' + self._escHtml(link.title || link.url || 'Untitled') + '</strong>' +
                                    categoryBadge +
                                '</div>' +
                                (link.description ? '<p class="mb-1 small text-muted">' + self._escHtml(link.description) + '</p>' : '') +
                                (link.url ? '<a href="' + self._escAttr(link.url) + '" class="small text-decoration-none" target="_blank">' + self._escHtml(link.url) + '</a>' : '') +
                            '</div>' +
                            '<div class="btn-group btn-group-sm ms-2">' +
                                '<button class="btn btn-outline-secondary btn-edit-link" data-link-id="' + link.id + '" title="Edit"><i class="bi bi-pencil"></i></button>' +
                                '<button class="btn btn-outline-danger btn-delete-link" data-link-id="' + link.id + '" title="Delete"><i class="bi bi-trash"></i></button>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
            });
            html += '</div>';

            container.innerHTML = html;

            // Attach edit handlers
            container.querySelectorAll('.btn-edit-link').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var linkId = parseInt(this.dataset.linkId, 10);
                    var linkData = links.find(function(l) { return l.id === linkId; });
                    if (linkData) {
                        self.addLink(linkData.section_id, linkData);
                    }
                });
            });

            // Attach delete handlers
            container.querySelectorAll('.btn-delete-link').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var linkId = parseInt(this.dataset.linkId, 10);
                    self.deleteLink(linkId).then(function(result) {
                        if (result && result.success) {
                            var item = container.querySelector('[data-link-id="' + linkId + '"]');
                            if (item) item.remove();
                        }
                    });
                });
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
        },

        /**
         * Escape a string for use in an HTML attribute.
         * @param {string} str
         * @returns {string}
         */
        _escAttr: function(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
    };

    window.LinkManager = LinkManager;
})();
