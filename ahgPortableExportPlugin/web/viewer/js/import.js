/**
 * Portable Catalogue Viewer — Import / Edit Mode
 *
 * When the viewer is in "editable" mode, this module enables:
 * - Adding notes to descriptions
 * - Importing additional files via drag-drop or file picker
 * - Exporting all changes as researcher-exchange.json (ZIP)
 *
 * All data is stored in-memory (JavaScript objects). Nothing is persisted
 * to disk until the user explicitly exports.
 */
(function () {
  'use strict';

  var ImportModule = {
    app: null,
    notes: {},         // { descriptionId: "note text" }
    importedFiles: [], // [{ id, filename, data (base64), caption, collectionTitle }]
    nextFileId: 1,

    /**
     * Initialize the import module.
     *
     * @param {Object} app Reference to the main App object
     */
    init: function (app) {
      this.app = app;
      this.showEditToolbar();
      this.renderImportPanel();
      this.bindToolbarButtons();
    },

    /**
     * Show the floating edit toolbar at the bottom of the screen.
     */
    showEditToolbar: function () {
      var toolbar = document.getElementById('edit-toolbar');
      if (toolbar) toolbar.style.display = '';
      document.body.classList.add('edit-mode');
    },

    /**
     * Update the changes counter badge.
     */
    updateChangesCount: function () {
      var badge = document.getElementById('edit-changes-count');
      if (!badge) return;
      var noteCount = Object.keys(this.notes).length;
      var fileCount = this.importedFiles.length;
      var newCount = this.newItems ? this.newItems.length : 0;
      var total = noteCount + fileCount + newCount;
      badge.textContent = total + ' change' + (total !== 1 ? 's' : '');
    },

    /**
     * Bind the floating toolbar buttons (New Item + Save & Export).
     */
    bindToolbarButtons: function () {
      var self = this;

      // Save & Export button
      var saveBtn = document.getElementById('btn-save-export');
      if (saveBtn) {
        saveBtn.addEventListener('click', function () {
          self.exportChanges();
        });
      }

      // New Item button — open modal
      var newBtn = document.getElementById('btn-new-item');
      if (newBtn) {
        newBtn.addEventListener('click', function () {
          var modal = new bootstrap.Modal(document.getElementById('newItemModal'));
          modal.show();
        });
      }

      // Add New Item form submit
      var addBtn = document.getElementById('btn-add-new-item');
      if (addBtn) {
        addBtn.addEventListener('click', function () {
          self.addNewItem();
        });
      }
    },

    /** @var array New items created in edit mode */
    newItems: [],

    /**
     * Render the import panel UI.
     */
    renderImportPanel: function () {
      var panel = document.getElementById('import-panel');
      if (!panel) return;

      var html = '';

      // Header
      html += '<div class="card mb-4">';
      html += '<div class="card-header bg-warning bg-opacity-25">';
      html += '<h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Mode</h5>';
      html += '</div>';
      html += '<div class="card-body">';
      html += '<p class="text-muted">You can add notes to descriptions, import additional files, and export your changes for submission.</p>';

      // Import dropzone
      html += '<div class="import-dropzone" id="file-dropzone">';
      html += '<i class="bi bi-cloud-arrow-up d-block mb-2"></i>';
      html += '<p class="mb-1">Drag & drop files here or click to browse</p>';
      html += '<small class="text-muted">Images, PDFs, and documents accepted</small>';
      html += '<input type="file" id="file-input" multiple style="display:none;">';
      html += '</div>';

      // Imported files list
      html += '<div id="imported-files-list" class="mt-3"></div>';

      html += '</div></div>';

      // Notes summary
      html += '<div class="card mb-4">';
      html += '<div class="card-header"><h6 class="mb-0"><i class="bi bi-journal-text me-2"></i>Notes Summary</h6></div>';
      html += '<div class="card-body" id="notes-summary">';
      html += '<p class="text-muted small">Notes added to descriptions will appear here.</p>';
      html += '</div></div>';

      // Export button
      html += '<div class="card">';
      html += '<div class="card-body text-center">';
      html += '<button class="btn btn-lg btn-success" id="btn-export-changes">';
      html += '<i class="bi bi-download me-2"></i>Export Changes (researcher-exchange.json)';
      html += '</button>';
      html += '<p class="text-muted small mt-2">Downloads a ZIP file that can be imported into the archive system.</p>';
      html += '</div></div>';

      panel.innerHTML = html;

      this.bindDropzone();
      this.bindExportButton();
    },

    /**
     * Bind drag-drop and file picker events.
     */
    bindDropzone: function () {
      var self = this;
      var dropzone = document.getElementById('file-dropzone');
      var fileInput = document.getElementById('file-input');

      if (!dropzone || !fileInput) return;

      dropzone.addEventListener('click', function () {
        fileInput.click();
      });

      dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        this.classList.add('drag-over');
      });

      dropzone.addEventListener('dragleave', function () {
        this.classList.remove('drag-over');
      });

      dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        self.handleFiles(e.dataTransfer.files);
      });

      fileInput.addEventListener('change', function () {
        self.handleFiles(this.files);
        this.value = '';
      });
    },

    /**
     * Handle imported files.
     */
    handleFiles: function (fileList) {
      var self = this;

      for (var i = 0; i < fileList.length; i++) {
        (function (file) {
          var reader = new FileReader();
          reader.onload = function (e) {
            var imported = {
              id: self.nextFileId++,
              filename: file.name,
              data: e.target.result, // base64 data URL
              size: file.size,
              type: file.type,
              caption: '',
              collectionTitle: 'Imported Files'
            };
            self.importedFiles.push(imported);
            self.renderImportedFiles();
            self.updateChangesCount();
          };
          reader.readAsDataURL(file);
        })(fileList[i]);
      }
    },

    /**
     * Render the list of imported files.
     */
    renderImportedFiles: function () {
      var container = document.getElementById('imported-files-list');
      if (!container) return;

      if (this.importedFiles.length === 0) {
        container.innerHTML = '';
        return;
      }

      var html = '<h6><i class="bi bi-files me-1"></i>Imported Files (' + this.importedFiles.length + ')</h6>';
      html += '<div class="list-group">';

      for (var i = 0; i < this.importedFiles.length; i++) {
        var f = this.importedFiles[i];
        var isImage = f.type && f.type.indexOf('image/') === 0;
        var sizeTxt = this.formatSize(f.size);

        html += '<div class="list-group-item">';
        html += '<div class="d-flex align-items-start">';

        // Thumbnail preview for images
        if (isImage && f.data) {
          html += '<img src="' + f.data + '" class="me-3 rounded" style="width:60px; height:60px; object-fit:cover;">';
        } else {
          html += '<div class="me-3 bg-light rounded d-flex align-items-center justify-content-center" style="width:60px; height:60px;">';
          html += '<i class="bi bi-file-earmark text-muted" style="font-size:1.5rem;"></i>';
          html += '</div>';
        }

        html += '<div class="flex-grow-1">';
        html += '<div class="d-flex justify-content-between">';
        html += '<strong>' + this.esc(f.filename) + '</strong>';
        html += '<button class="btn btn-sm btn-outline-danger" data-remove-file="' + f.id + '"><i class="bi bi-x"></i></button>';
        html += '</div>';
        html += '<small class="text-muted">' + sizeTxt + '</small>';
        html += '<div class="mt-1">';
        html += '<input type="text" class="form-control form-control-sm" placeholder="Caption (optional)" data-caption-id="' + f.id + '" value="' + this.esc(f.caption) + '">';
        html += '</div>';
        html += '</div></div></div>';
      }

      html += '</div>';
      container.innerHTML = html;

      // Bind events
      var self = this;
      container.querySelectorAll('[data-remove-file]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var id = parseInt(this.getAttribute('data-remove-file'), 10);
          self.importedFiles = self.importedFiles.filter(function (f) { return f.id !== id; });
          self.renderImportedFiles();
          self.updateChangesCount();
        });
      });

      container.querySelectorAll('[data-caption-id]').forEach(function (input) {
        input.addEventListener('input', function () {
          var id = parseInt(this.getAttribute('data-caption-id'), 10);
          for (var j = 0; j < self.importedFiles.length; j++) {
            if (self.importedFiles[j].id === id) {
              self.importedFiles[j].caption = this.value;
              break;
            }
          }
        });
      });
    },

    /**
     * Add a new item from the modal form.
     */
    addNewItem: function () {
      var titleEl = document.getElementById('new-item-title');
      var title = titleEl ? titleEl.value.trim() : '';
      if (!title) {
        alert('Please enter a title for the new item.');
        return;
      }

      var levelEl = document.getElementById('new-item-level');
      var identEl = document.getElementById('new-item-identifier');
      var scopeEl = document.getElementById('new-item-scope');
      var filesEl = document.getElementById('new-item-files');

      var item = {
        id: 'new-' + Date.now(),
        title: title,
        level_of_description: levelEl ? levelEl.value : 'file',
        identifier: identEl ? identEl.value.trim() : '',
        scope_and_content: scopeEl ? scopeEl.value.trim() : '',
        files: []
      };

      // Read attached files
      var self = this;
      var pendingReads = 0;

      if (filesEl && filesEl.files.length > 0) {
        pendingReads = filesEl.files.length;
        for (var i = 0; i < filesEl.files.length; i++) {
          (function (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
              item.files.push({
                filename: file.name,
                data: e.target.result,
                mime_type: file.type,
                size: file.size
              });
              pendingReads--;
              if (pendingReads === 0) {
                finalize();
              }
            };
            reader.readAsDataURL(file);
          })(filesEl.files[i]);
        }
      } else {
        finalize();
      }

      function finalize() {
        self.newItems.push(item);
        self.updateChangesCount();

        // Clear form
        if (titleEl) titleEl.value = '';
        if (identEl) identEl.value = '';
        if (scopeEl) scopeEl.value = '';
        if (filesEl) filesEl.value = '';
        if (levelEl) levelEl.value = 'file';

        // Close modal
        var modalEl = document.getElementById('newItemModal');
        if (modalEl) {
          var modal = bootstrap.Modal.getInstance(modalEl);
          if (modal) modal.hide();
        }

        // Show confirmation
        self.showToast('Item "' + item.title + '" added (' + self.newItems.length + ' new item' + (self.newItems.length !== 1 ? 's' : '') + ')');
      }
    },

    /**
     * Show a temporary toast message.
     */
    showToast: function (message) {
      var toast = document.createElement('div');
      toast.className = 'position-fixed top-0 start-50 translate-middle-x mt-3 alert alert-success shadow';
      toast.style.zIndex = '9999';
      toast.textContent = message;
      document.body.appendChild(toast);
      setTimeout(function () {
        toast.remove();
      }, 3000);
    },

    /**
     * Save a note for a description.
     */
    saveNote: function (descriptionId, text) {
      if (text && text.trim()) {
        this.notes[descriptionId] = text.trim();
      } else {
        delete this.notes[descriptionId];
      }
      this.updateNotesSummary();
      this.updateChangesCount();
    },

    /**
     * Get notes for a description.
     */
    getNotes: function (descriptionId) {
      return this.notes[descriptionId] || '';
    },

    /**
     * Update the notes summary display.
     */
    updateNotesSummary: function () {
      var container = document.getElementById('notes-summary');
      if (!container) return;

      var noteIds = Object.keys(this.notes);
      if (noteIds.length === 0) {
        container.innerHTML = '<p class="text-muted small">Notes added to descriptions will appear here.</p>';
        return;
      }

      var html = '<div class="list-group">';
      for (var i = 0; i < noteIds.length; i++) {
        var id = parseInt(noteIds[i], 10);
        var desc = this.app.catalogueById[id];
        var title = desc ? desc.title : 'Description #' + id;

        html += '<div class="list-group-item">';
        html += '<strong>' + this.esc(title) + '</strong>';
        html += '<p class="mb-0 small">' + this.esc(this.notes[id]) + '</p>';
        html += '</div>';
      }
      html += '</div>';
      container.innerHTML = html;
    },

    /**
     * Bind the export button.
     */
    bindExportButton: function () {
      var self = this;
      var btn = document.getElementById('btn-export-changes');
      if (!btn) return;

      btn.addEventListener('click', function () {
        self.exportChanges();
      });
    },

    /**
     * Export all changes as researcher-exchange.json.
     *
     * Creates a JSON package following the exchange format v1.0 spec
     * and triggers a download.
     */
    exportChanges: function () {
      var noteIds = Object.keys(this.notes);
      var newCount = this.newItems ? this.newItems.length : 0;

      if (noteIds.length === 0 && this.importedFiles.length === 0 && newCount === 0) {
        alert('No changes to export. Add notes, import files, or create new items first.');
        return;
      }

      // Build exchange format
      var exchange = {
        format_version: '1.0',
        source: 'portable-viewer',
        exported_at: new Date().toISOString(),
        source_config: {
          title: this.app.config.title || 'Portable Catalogue',
          scope_type: this.app.config.scope_type || 'unknown',
          culture: this.app.config.culture || 'en'
        },
        collections: []
      };

      // Add notes as a collection
      if (noteIds.length > 0) {
        var notesCollection = {
          title: 'Research Notes',
          type: 'notes',
          items: []
        };

        for (var i = 0; i < noteIds.length; i++) {
          var id = parseInt(noteIds[i], 10);
          var desc = this.app.catalogueById[id];

          notesCollection.items.push({
            reference_id: id,
            reference_slug: desc ? desc.slug : null,
            reference_identifier: desc ? desc.identifier : null,
            title: desc ? desc.title : 'Description #' + id,
            level_of_description: desc ? desc.level_of_description : null,
            note: this.notes[id]
          });
        }

        exchange.collections.push(notesCollection);
      }

      // Add imported files as a collection
      if (this.importedFiles.length > 0) {
        var filesCollection = {
          title: 'Imported Files',
          type: 'files',
          items: []
        };

        for (var j = 0; j < this.importedFiles.length; j++) {
          var f = this.importedFiles[j];
          filesCollection.items.push({
            title: f.caption || f.filename,
            level_of_description: 'item',
            scope_and_content: f.caption || '',
            files: [{
              filename: f.filename,
              caption: f.caption || '',
              mime_type: f.type,
              size: f.size
            }]
          });
        }

        exchange.collections.push(filesCollection);
      }

      // Add new items as a collection
      if (newCount > 0) {
        var newItemsCollection = {
          title: 'New Items',
          type: 'new_items',
          items: []
        };

        for (var k = 0; k < this.newItems.length; k++) {
          var ni = this.newItems[k];
          var exportItem = {
            title: ni.title,
            level_of_description: ni.level_of_description || 'file',
            identifier: ni.identifier || '',
            scope_and_content: ni.scope_and_content || '',
            files: []
          };

          if (ni.files && ni.files.length > 0) {
            for (var fi = 0; fi < ni.files.length; fi++) {
              exportItem.files.push({
                filename: ni.files[fi].filename,
                caption: '',
                mime_type: ni.files[fi].mime_type,
                size: ni.files[fi].size
              });
            }
          }

          newItemsCollection.items.push(exportItem);
        }

        exchange.collections.push(newItemsCollection);
      }

      // Create and download JSON
      var json = JSON.stringify(exchange, null, 2);
      var blob = new Blob([json], { type: 'application/json' });
      var url = URL.createObjectURL(blob);

      var a = document.createElement('a');
      a.href = url;
      a.download = 'researcher-exchange.json';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);

      this.showToast('Exported ' + (noteIds.length + this.importedFiles.length + newCount) + ' changes as researcher-exchange.json');
    },

    /**
     * Format file size for display.
     */
    formatSize: function (bytes) {
      if (bytes < 1024) return bytes + ' B';
      if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
      return (bytes / 1048576).toFixed(1) + ' MB';
    },

    /**
     * Escape HTML.
     */
    esc: function (str) {
      if (!str) return '';
      var div = document.createElement('div');
      div.appendChild(document.createTextNode(String(str)));
      return div.innerHTML;
    }
  };

  window.ImportModule = ImportModule;
})();
