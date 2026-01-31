/**
 * ahgFormsPlugin JavaScript
 * Form builder and dynamic form functionality
 */

(function() {
    'use strict';

    // Form Builder Module
    window.FormBuilder = {
        templateId: null,
        fields: [],
        draggedItem: null,

        /**
         * Initialize the form builder
         */
        init: function(templateId, fields) {
            this.templateId = templateId;
            this.fields = fields || [];
            this.bindEvents();
            this.renderFields();
        },

        /**
         * Bind drag and drop events
         */
        bindEvents: function() {
            var self = this;
            var canvas = document.getElementById('form-canvas');
            var palette = document.getElementById('field-palette');

            if (!canvas || !palette) return;

            // Palette items draggable
            palette.querySelectorAll('.palette-item').forEach(function(item) {
                item.setAttribute('draggable', true);
                item.addEventListener('dragstart', function(e) {
                    self.draggedItem = {
                        type: 'new',
                        fieldType: this.dataset.fieldType
                    };
                    this.classList.add('dragging');
                });
                item.addEventListener('dragend', function(e) {
                    this.classList.remove('dragging');
                });
            });

            // Canvas drop zone
            canvas.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });

            canvas.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });

            canvas.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                if (self.draggedItem && self.draggedItem.type === 'new') {
                    self.addField(self.draggedItem.fieldType);
                }
                self.draggedItem = null;
            });

            // Save button
            document.getElementById('save-fields')?.addEventListener('click', function() {
                self.saveFields();
            });
        },

        /**
         * Render all fields in the canvas
         */
        renderFields: function() {
            var canvas = document.getElementById('form-canvas');
            if (!canvas) return;

            canvas.innerHTML = '';

            if (this.fields.length === 0) {
                canvas.innerHTML = '<div class="text-center text-muted py-5">' +
                    '<i class="fas fa-arrow-down fa-2x mb-2"></i>' +
                    '<p>Drag fields from the palette to add them</p>' +
                    '</div>';
                return;
            }

            var self = this;
            var currentSection = null;
            var currentTab = null;

            this.fields.forEach(function(field, index) {
                // Tab header
                if (field.tab_name && field.tab_name !== currentTab) {
                    currentTab = field.tab_name;
                    var tabHeader = document.createElement('div');
                    tabHeader.className = 'tab-header';
                    tabHeader.textContent = currentTab;
                    canvas.appendChild(tabHeader);
                }

                // Section header
                if (field.section_name && field.section_name !== currentSection) {
                    currentSection = field.section_name;
                    var sectionHeader = document.createElement('div');
                    sectionHeader.className = 'section-header';
                    sectionHeader.textContent = currentSection;
                    canvas.appendChild(sectionHeader);
                }

                // Field item
                var item = self.createFieldItem(field, index);
                canvas.appendChild(item);
            });
        },

        /**
         * Create a field item element
         */
        createFieldItem: function(field, index) {
            var self = this;
            var item = document.createElement('div');
            item.className = 'field-item';
            item.dataset.index = index;
            item.setAttribute('draggable', true);

            var required = field.is_required ? '<span class="text-danger">*</span>' : '';
            var typeIcons = {
                'text': 'fa-font',
                'textarea': 'fa-align-left',
                'richtext': 'fa-paragraph',
                'date': 'fa-calendar',
                'daterange': 'fa-calendar-alt',
                'select': 'fa-list',
                'multiselect': 'fa-list-ul',
                'autocomplete': 'fa-search',
                'checkbox': 'fa-check-square',
                'radio': 'fa-dot-circle',
                'file': 'fa-file-upload',
                'hidden': 'fa-eye-slash',
                'heading': 'fa-heading',
                'divider': 'fa-minus'
            };

            var icon = typeIcons[field.field_type] || 'fa-question';

            item.innerHTML = '<div class="d-flex justify-content-between align-items-start">' +
                '<div>' +
                    '<div class="field-label">' +
                        '<i class="fas ' + icon + ' me-2 text-muted"></i>' +
                        field.label + required +
                    '</div>' +
                    '<div class="field-type">' + field.field_name + ' (' + field.field_type + ')</div>' +
                '</div>' +
                '<div class="field-actions">' +
                    '<button type="button" class="btn btn-sm btn-outline-primary edit-field" title="Edit">' +
                        '<i class="fas fa-edit"></i>' +
                    '</button> ' +
                    '<button type="button" class="btn btn-sm btn-outline-danger delete-field" title="Delete">' +
                        '<i class="fas fa-trash"></i>' +
                    '</button>' +
                '</div>' +
            '</div>';

            // Edit button
            item.querySelector('.edit-field').addEventListener('click', function(e) {
                e.stopPropagation();
                self.editField(index);
            });

            // Delete button
            item.querySelector('.delete-field').addEventListener('click', function(e) {
                e.stopPropagation();
                if (confirm('Delete this field?')) {
                    self.deleteField(index);
                }
            });

            // Drag events for reordering
            item.addEventListener('dragstart', function(e) {
                self.draggedItem = {
                    type: 'reorder',
                    index: index
                };
                this.classList.add('dragging');
            });

            item.addEventListener('dragend', function() {
                this.classList.remove('dragging');
            });

            item.addEventListener('dragover', function(e) {
                e.preventDefault();
                if (self.draggedItem && self.draggedItem.type === 'reorder' && self.draggedItem.index !== index) {
                    var rect = this.getBoundingClientRect();
                    var midY = rect.top + rect.height / 2;
                    if (e.clientY < midY) {
                        this.style.borderTop = '2px solid #0d6efd';
                        this.style.borderBottom = '';
                    } else {
                        this.style.borderTop = '';
                        this.style.borderBottom = '2px solid #0d6efd';
                    }
                }
            });

            item.addEventListener('dragleave', function() {
                this.style.borderTop = '';
                this.style.borderBottom = '';
            });

            item.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderTop = '';
                this.style.borderBottom = '';

                if (self.draggedItem && self.draggedItem.type === 'reorder') {
                    var fromIndex = self.draggedItem.index;
                    var toIndex = index;
                    self.moveField(fromIndex, toIndex);
                }
                self.draggedItem = null;
            });

            return item;
        },

        /**
         * Add a new field
         */
        addField: function(fieldType) {
            var label = this.getDefaultLabel(fieldType);
            var fieldName = 'field_' + Date.now();

            this.fields.push({
                field_name: fieldName,
                field_type: fieldType,
                label: label,
                is_required: false,
                is_repeatable: false,
                sort_order: this.fields.length + 1
            });

            this.renderFields();
            this.editField(this.fields.length - 1);
        },

        /**
         * Get default label for field type
         */
        getDefaultLabel: function(fieldType) {
            var labels = {
                'text': 'Text Field',
                'textarea': 'Text Area',
                'richtext': 'Rich Text',
                'date': 'Date',
                'daterange': 'Date Range',
                'select': 'Select',
                'multiselect': 'Multi-Select',
                'autocomplete': 'Autocomplete',
                'checkbox': 'Checkbox',
                'radio': 'Radio Buttons',
                'file': 'File Upload',
                'hidden': 'Hidden Field',
                'heading': 'Section Heading',
                'divider': 'Divider'
            };
            return labels[fieldType] || 'New Field';
        },

        /**
         * Edit a field
         */
        editField: function(index) {
            var field = this.fields[index];
            // Open modal editor (simplified version - would use Bootstrap modal in production)
            var newLabel = prompt('Field Label:', field.label);
            if (newLabel !== null) {
                field.label = newLabel;
                this.renderFields();
            }
        },

        /**
         * Delete a field
         */
        deleteField: function(index) {
            this.fields.splice(index, 1);
            // Update sort orders
            this.fields.forEach(function(f, i) {
                f.sort_order = i + 1;
            });
            this.renderFields();
        },

        /**
         * Move a field
         */
        moveField: function(fromIndex, toIndex) {
            var field = this.fields.splice(fromIndex, 1)[0];
            this.fields.splice(toIndex, 0, field);
            // Update sort orders
            this.fields.forEach(function(f, i) {
                f.sort_order = i + 1;
            });
            this.renderFields();
        },

        /**
         * Save fields to server
         */
        saveFields: function() {
            var self = this;
            fetch('/api/forms/template/' + this.templateId + '/fields', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(this.fields)
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    alert('Fields saved successfully!');
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function(error) {
                alert('Error saving fields: ' + error);
            });
        }
    };

    // Autosave Module
    window.FormAutosave = {
        templateId: null,
        objectType: null,
        objectId: null,
        interval: null,
        lastSaved: null,

        /**
         * Initialize autosave
         */
        init: function(templateId, objectType, objectId) {
            this.templateId = templateId;
            this.objectType = objectType;
            this.objectId = objectId;

            // Autosave every 30 seconds
            var self = this;
            this.interval = setInterval(function() {
                self.save();
            }, 30000);

            // Save on page unload
            window.addEventListener('beforeunload', function() {
                self.save();
            });
        },

        /**
         * Save draft
         */
        save: function() {
            var form = document.querySelector('form[data-autosave]');
            if (!form) return;

            var formData = new FormData(form);
            var data = {};
            formData.forEach(function(value, key) {
                data[key] = value;
            });

            fetch('/api/forms/autosave', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    template_id: this.templateId,
                    object_type: this.objectType,
                    object_id: this.objectId,
                    form_data: data
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    var indicator = document.getElementById('autosave-indicator');
                    if (indicator) {
                        indicator.textContent = 'Saved at ' + result.saved_at;
                    }
                }
            })
            .catch(function(error) {
                console.error('Autosave error:', error);
            });
        },

        /**
         * Stop autosave
         */
        stop: function() {
            if (this.interval) {
                clearInterval(this.interval);
            }
        }
    };

})();
