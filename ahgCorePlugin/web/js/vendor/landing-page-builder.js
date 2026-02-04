/**
 * Landing Page Builder - Main JavaScript
 *
 * Handles drag-and-drop page building functionality:
 * - Sortable.js initialization on blocks container
 * - Drag from palette to canvas
 * - Click-to-add blocks
 * - Block edit/delete/duplicate/visibility actions
 * - Config panel form handling
 * - Save draft / publish
 * - Version restore
 */
(function () {
  'use strict';

  // Ensure LandingPageBuilder config exists
  if (typeof window.LandingPageBuilder === 'undefined') {
    console.error('LandingPageBuilder: Configuration not found');
    return;
  }

  const Config = window.LandingPageBuilder;
  let blocksSortable = null;

  /**
   * Initialize the builder
   */
  function init() {
    initBlocksSortable();
    initPaletteDrag();
    initPaletteClick();
    initBlockActions();
    initToolbarActions();
    initPageSettings();
    initVersionRestore();
  }

  /**
   * Initialize Sortable.js on the blocks container
   */
  function initBlocksSortable() {
    const container = document.getElementById('blocks-container');
    if (!container || typeof Sortable === 'undefined') {
      console.warn('LandingPageBuilder: Sortable.js not available or container not found');
      return;
    }

    blocksSortable = new Sortable(container, {
      animation: 150,
      handle: '.block-handle',
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      dragClass: 'sortable-drag',
      filter: '.empty-canvas',
      group: {
        name: 'blocks',
        pull: true,
        put: true
      },
      onEnd: function (evt) {
        saveBlockOrder();
      }
    });
  }

  /**
   * Initialize drag from palette to canvas
   */
  function initPaletteDrag() {
    const palette = document.getElementById('block-palette');
    if (!palette) return;

    const blockItems = palette.querySelectorAll('.block-type-item');
    blockItems.forEach(function (item) {
      item.addEventListener('dragstart', handlePaletteDragStart);
      item.addEventListener('dragend', handlePaletteDragEnd);
    });

    // Set up drop zone
    const container = document.getElementById('blocks-container');
    if (container) {
      container.addEventListener('dragover', handleCanvasDragOver);
      container.addEventListener('drop', handleCanvasDrop);
      container.addEventListener('dragleave', handleCanvasDragLeave);
    }
  }

  /**
   * Handle palette drag start
   */
  function handlePaletteDragStart(e) {
    e.dataTransfer.effectAllowed = 'copy';
    e.dataTransfer.setData('text/plain', JSON.stringify({
      typeId: this.dataset.typeId,
      machineName: this.dataset.machineName
    }));
    this.classList.add('dragging');
  }

  /**
   * Handle palette drag end
   */
  function handlePaletteDragEnd(e) {
    this.classList.remove('dragging');
    const container = document.getElementById('blocks-container');
    if (container) {
      container.classList.remove('drag-over');
    }
  }

  /**
   * Handle canvas drag over
   */
  function handleCanvasDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'copy';
    this.classList.add('drag-over');
  }

  /**
   * Handle canvas drag leave
   */
  function handleCanvasDragLeave(e) {
    this.classList.remove('drag-over');
  }

  /**
   * Handle canvas drop
   */
  function handleCanvasDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');

    try {
      const data = JSON.parse(e.dataTransfer.getData('text/plain'));
      if (data.typeId) {
        addBlock(data.typeId);
      }
    } catch (err) {
      // Not a palette drag, ignore
    }
  }

  /**
   * Initialize click-to-add from palette
   */
  function initPaletteClick() {
    const palette = document.getElementById('block-palette');
    if (!palette) return;

    palette.addEventListener('click', function (e) {
      const item = e.target.closest('.block-type-item .card-body');
      if (item) {
        const blockItem = item.closest('.block-type-item');
        addBlock(blockItem.dataset.typeId);
      }
    });
  }

  /**
   * Initialize block action buttons
   */
  function initBlockActions() {
    const container = document.getElementById('blocks-container');
    if (!container) return;

    container.addEventListener('click', function (e) {
      const blockCard = e.target.closest('.block-card');
      if (!blockCard) return;

      const blockId = blockCard.dataset.blockId;

      // Edit button
      if (e.target.closest('.btn-edit')) {
        openConfigPanel(blockId);
        return;
      }

      // Delete button
      if (e.target.closest('.btn-delete')) {
        deleteBlock(blockId, blockCard);
        return;
      }

      // Duplicate button
      if (e.target.closest('.btn-duplicate')) {
        duplicateBlock(blockId);
        return;
      }

      // Visibility toggle
      if (e.target.closest('.btn-visibility')) {
        toggleVisibility(blockId, blockCard);
        return;
      }
    });
  }

  /**
   * Initialize toolbar actions
   */
  function initToolbarActions() {
    // Preview button
    const previewBtn = document.getElementById('btn-preview');
    if (previewBtn) {
      previewBtn.addEventListener('click', function () {
        window.open(this.dataset.url, '_blank');
      });
    }

    // Save draft button
    const draftBtn = document.getElementById('btn-save-draft');
    if (draftBtn) {
      draftBtn.addEventListener('click', saveDraft);
    }

    // Publish button
    const publishBtn = document.getElementById('btn-publish');
    if (publishBtn) {
      publishBtn.addEventListener('click', publish);
    }

    // Collapse all
    const collapseBtn = document.getElementById('btn-collapse-all');
    if (collapseBtn) {
      collapseBtn.addEventListener('click', function () {
        document.querySelectorAll('.block-card .card-body').forEach(function (body) {
          body.style.display = 'none';
        });
      });
    }

    // Expand all
    const expandBtn = document.getElementById('btn-expand-all');
    if (expandBtn) {
      expandBtn.addEventListener('click', function () {
        document.querySelectorAll('.block-card .card-body').forEach(function (body) {
          body.style.display = '';
        });
      });
    }

    // Close config panel
    const closeConfig = document.getElementById('close-config');
    if (closeConfig) {
      closeConfig.addEventListener('click', closeConfigPanel);
    }
  }

  /**
   * Initialize page settings form
   */
  function initPageSettings() {
    const form = document.getElementById('page-settings-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      savePageSettings(new FormData(this));
    });

    // Update slug preview
    const slugInput = form.querySelector('[name="slug"]');
    const slugPreview = document.getElementById('slug-preview');
    if (slugInput && slugPreview) {
      slugInput.addEventListener('input', function () {
        slugPreview.textContent = this.value;
      });
    }

    // Delete page button
    const deleteBtn = document.getElementById('btn-delete-page');
    if (deleteBtn) {
      deleteBtn.addEventListener('click', deletePage);
    }
  }

  /**
   * Initialize version restore
   */
  function initVersionRestore() {
    document.querySelectorAll('.version-restore').forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        const versionId = this.dataset.versionId;
        restoreVersion(versionId);
      });
    });
  }

  /**
   * Add a new block
   */
  function addBlock(blockTypeId, options) {
    options = options || {};

    const formData = new FormData();
    formData.append('page_id', Config.pageId);
    formData.append('block_type_id', blockTypeId);

    if (options.parentBlockId) {
      formData.append('parent_block_id', options.parentBlockId);
    }
    if (options.columnSlot) {
      formData.append('column_slot', options.columnSlot);
    }
    if (options.config) {
      formData.append('config', JSON.stringify(options.config));
    }

    fetch(Config.urls.addBlock, {
      method: 'POST',
      body: formData
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          // Reload page to show new block
          window.location.reload();
        } else {
          showNotification('Error adding block: ' + result.error, 'error');
        }
      })
      .catch(function (error) {
        showNotification('Network error: ' + error.message, 'error');
      });
  }

  /**
   * Delete a block
   */
  function deleteBlock(blockId, blockCard) {
    if (!confirm('Delete this block?')) return;

    const formData = new FormData();
    formData.append('block_id', blockId);

    fetch(Config.urls.deleteBlock, {
      method: 'POST',
      body: formData
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          blockCard.remove();
          updateBlockCount();
          showNotification('Block deleted', 'success');
        } else {
          showNotification('Error: ' + result.error, 'error');
        }
      })
      .catch(function (error) {
        showNotification('Network error: ' + error.message, 'error');
      });
  }

  /**
   * Duplicate a block
   */
  function duplicateBlock(blockId) {
    const formData = new FormData();
    formData.append('block_id', blockId);

    fetch(Config.urls.duplicateBlock, {
      method: 'POST',
      body: formData
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          window.location.reload();
        } else {
          showNotification('Error: ' + result.error, 'error');
        }
      })
      .catch(function (error) {
        showNotification('Network error: ' + error.message, 'error');
      });
  }

  /**
   * Toggle block visibility
   */
  function toggleVisibility(blockId, blockCard) {
    const formData = new FormData();
    formData.append('block_id', blockId);

    fetch(Config.urls.toggleVisibility, {
      method: 'POST',
      body: formData
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          blockCard.classList.toggle('block-hidden');
          const btn = blockCard.querySelector('.btn-visibility');
          if (btn) {
            btn.classList.toggle('btn-outline-secondary');
            btn.classList.toggle('btn-warning');
            btn.innerHTML = result.is_visible ? '\uD83D\uDC41' : '\uD83D\uDEAB';
            btn.title = result.is_visible ? 'Hide' : 'Show';
          }
        } else {
          showNotification('Error: ' + result.error, 'error');
        }
      })
      .catch(function (error) {
        showNotification('Network error: ' + error.message, 'error');
      });
  }

  /**
   * Save block order after drag
   */
  function saveBlockOrder() {
    const container = document.getElementById('blocks-container');
    if (!container) return;

    const blockIds = [];
    container.querySelectorAll('.block-card').forEach(function (card) {
      if (card.dataset.blockId) {
        blockIds.push(card.dataset.blockId);
      }
    });

    const formData = new FormData();
    formData.append('page_id', Config.pageId);
    formData.append('order', JSON.stringify(blockIds));

    fetch(Config.urls.reorderBlocks, {
      method: 'POST',
      body: formData
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (!result.success) {
          showNotification('Error reordering: ' + result.error, 'error');
        }
      })
      .catch(function (error) {
        showNotification('Network error: ' + error.message, 'error');
      });
  }

  /**
   * Open config panel for a block
   */
  function openConfigPanel(blockId) {
    const panel = document.getElementById('config-panel');
    const container = document.getElementById('config-form-container');
    const title = document.getElementById('config-title');

    if (!panel || !container) return;

    // Show panel
    panel.style.display = 'block';

    // Load block config
    fetch(Config.urls.getBlockConfig + '?block_id=' + blockId)
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          title.textContent = result.block.type_label + ' Settings';
          container.innerHTML = buildConfigForm(result.block);
          initConfigForm(result.block.id);
        } else {
          container.innerHTML = '<div class="alert alert-danger">' + result.error + '</div>';
        }
      })
      .catch(function (error) {
        container.innerHTML = '<div class="alert alert-danger">Error loading config</div>';
      });
  }

  /**
   * Close config panel
   */
  function closeConfigPanel() {
    const panel = document.getElementById('config-panel');
    if (panel) {
      panel.style.display = 'none';
    }
  }

  /**
   * Build config form HTML from schema
   */
  function buildConfigForm(block) {
    const schema = block.config_schema || {};
    const config = block.config || {};
    let html = '<form id="block-config-form" data-block-id="' + block.id + '">';

    // Title field (always present)
    html += '<div class="mb-3">';
    html += '<label class="form-label">Block Title</label>';
    html += '<input type="text" name="title" class="form-control" value="' + escapeHtml(block.title || '') + '" placeholder="Optional title">';
    html += '</div>';

    // Dynamic fields from schema
    if (schema.properties) {
      for (var key in schema.properties) {
        var prop = schema.properties[key];
        var value = config[key] !== undefined ? config[key] : (prop.default || '');

        html += '<div class="mb-3">';
        html += '<label class="form-label">' + escapeHtml(prop.title || key) + '</label>';

        switch (prop.type) {
          case 'string':
            if (prop.enum) {
              html += '<select name="config[' + key + ']" class="form-select">';
              prop.enum.forEach(function (opt) {
                var selected = opt === value ? ' selected' : '';
                html += '<option value="' + escapeHtml(opt) + '"' + selected + '>' + escapeHtml(opt) + '</option>';
              });
              html += '</select>';
            } else if (prop.format === 'textarea' || prop.format === 'html') {
              html += '<textarea name="config[' + key + ']" class="form-control" rows="4">' + escapeHtml(value) + '</textarea>';
            } else if (prop.format === 'color') {
              html += '<input type="color" name="config[' + key + ']" class="form-control form-control-color" value="' + escapeHtml(value) + '">';
            } else {
              html += '<input type="text" name="config[' + key + ']" class="form-control" value="' + escapeHtml(value) + '">';
            }
            break;

          case 'number':
          case 'integer':
            html += '<input type="number" name="config[' + key + ']" class="form-control" value="' + escapeHtml(value) + '"';
            if (prop.minimum !== undefined) html += ' min="' + prop.minimum + '"';
            if (prop.maximum !== undefined) html += ' max="' + prop.maximum + '"';
            html += '>';
            break;

          case 'boolean':
            var checked = value ? ' checked' : '';
            html += '<div class="form-check">';
            html += '<input type="checkbox" name="config[' + key + ']" class="form-check-input" value="1"' + checked + '>';
            html += '<label class="form-check-label">Enabled</label>';
            html += '</div>';
            break;

          default:
            html += '<input type="text" name="config[' + key + ']" class="form-control" value="' + escapeHtml(typeof value === 'object' ? JSON.stringify(value) : value) + '">';
        }

        if (prop.description) {
          html += '<div class="form-text">' + escapeHtml(prop.description) + '</div>';
        }
        html += '</div>';
      }
    }

    // Style settings
    html += '<hr><h6 class="text-muted">Style Settings</h6>';

    html += '<div class="mb-3">';
    html += '<label class="form-label">Container</label>';
    html += '<select name="container_type" class="form-select">';
    ['container', 'container-fluid', 'none'].forEach(function (opt) {
      var selected = opt === block.container_type ? ' selected' : '';
      html += '<option value="' + opt + '"' + selected + '>' + opt + '</option>';
    });
    html += '</select>';
    html += '</div>';

    html += '<div class="row">';
    html += '<div class="col-6 mb-3">';
    html += '<label class="form-label">Background</label>';
    html += '<input type="color" name="background_color" class="form-control form-control-color" value="' + escapeHtml(block.background_color || '#ffffff') + '">';
    html += '</div>';
    html += '<div class="col-6 mb-3">';
    html += '<label class="form-label">Text Color</label>';
    html += '<input type="color" name="text_color" class="form-control form-control-color" value="' + escapeHtml(block.text_color || '#212529') + '">';
    html += '</div>';
    html += '</div>';

    html += '<div class="mb-3">';
    html += '<label class="form-label">CSS Classes</label>';
    html += '<input type="text" name="css_classes" class="form-control" value="' + escapeHtml(block.css_classes || '') + '" placeholder="e.g., my-custom-class">';
    html += '</div>';

    html += '<div class="d-grid gap-2 mt-4">';
    html += '<button type="submit" class="btn btn-primary">Save Changes</button>';
    html += '</div>';

    html += '</form>';
    return html;
  }

  /**
   * Initialize config form submission
   */
  function initConfigForm(blockId) {
    const form = document.getElementById('block-config-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      saveBlockConfig(blockId, new FormData(this));
    });
  }

  /**
   * Save block configuration
   */
  function saveBlockConfig(blockId, formData) {
    // Build config object from form data
    const config = {};
    for (var pair of formData.entries()) {
      if (pair[0].startsWith('config[')) {
        var key = pair[0].replace('config[', '').replace(']', '');
        config[key] = pair[1];
      }
    }

    const postData = new FormData();
    postData.append('block_id', blockId);
    postData.append('config', JSON.stringify(config));
    postData.append('title', formData.get('title') || '');
    postData.append('container_type', formData.get('container_type') || 'container');
    postData.append('background_color', formData.get('background_color') || '');
    postData.append('text_color', formData.get('text_color') || '');
    postData.append('css_classes', formData.get('css_classes') || '');

    fetch(Config.urls.updateBlock, {
      method: 'POST',
      body: postData
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          showNotification('Block updated', 'success');
          // Reload to show changes
          window.location.reload();
        } else {
          showNotification('Error: ' + result.error, 'error');
        }
      })
      .catch(function (error) {
        showNotification('Network error: ' + error.message, 'error');
      });
  }

  /**
   * Save page settings
   */
  function savePageSettings(formData) {
    fetch(Config.urls.updateSettings, {
      method: 'POST',
      body: formData
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          showNotification('Settings saved', 'success');
          // Close offcanvas
          var offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('pageSettingsPanel'));
          if (offcanvas) offcanvas.hide();
        } else {
          showNotification('Error: ' + result.error, 'error');
        }
      })
      .catch(function (error) {
        showNotification('Network error: ' + error.message, 'error');
      });
  }

  /**
   * Delete page
   */
  function deletePage() {
    if (!confirm('Are you sure you want to delete this page? This cannot be undone.')) return;

    const formData = new FormData();
    formData.append('id', Config.pageId);

    fetch(Config.urls.deletePage, {
      method: 'POST',
      body: formData
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          window.location.href = Config.urls.listPage;
        } else {
          showNotification('Error: ' + result.error, 'error');
        }
      })
      .catch(function (error) {
        showNotification('Network error: ' + error.message, 'error');
      });
  }

  /**
   * Save draft
   */
  function saveDraft() {
    const formData = new FormData();
    formData.append('page_id', Config.pageId);

    fetch(Config.urls.saveDraft, {
      method: 'POST',
      body: formData
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          showNotification('Draft saved', 'success');
        } else {
          showNotification('Error: ' + result.error, 'error');
        }
      })
      .catch(function (error) {
        showNotification('Network error: ' + error.message, 'error');
      });
  }

  /**
   * Publish page
   */
  function publish() {
    const formData = new FormData();
    formData.append('page_id', Config.pageId);

    fetch(Config.urls.publish, {
      method: 'POST',
      body: formData
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          showNotification('Page published!', 'success');
        } else {
          showNotification('Error: ' + result.error, 'error');
        }
      })
      .catch(function (error) {
        showNotification('Network error: ' + error.message, 'error');
      });
  }

  /**
   * Restore version
   */
  function restoreVersion(versionId) {
    if (!confirm('Restore this version? Current changes will be replaced.')) return;

    const formData = new FormData();
    formData.append('version_id', versionId);

    fetch(Config.urls.restoreVersion, {
      method: 'POST',
      body: formData
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          showNotification('Version restored', 'success');
          window.location.reload();
        } else {
          showNotification('Error: ' + result.error, 'error');
        }
      })
      .catch(function (error) {
        showNotification('Network error: ' + error.message, 'error');
      });
  }

  /**
   * Update block count display
   */
  function updateBlockCount() {
    const counter = document.getElementById('block-count');
    const container = document.getElementById('blocks-container');
    if (counter && container) {
      var count = container.querySelectorAll('.block-card').length;
      counter.textContent = '(' + count + ' blocks)';

      // Show/hide empty message
      var emptyMsg = document.getElementById('empty-message');
      if (emptyMsg) {
        emptyMsg.style.display = count === 0 ? 'block' : 'none';
      }
    }
  }

  /**
   * Show notification toast
   */
  function showNotification(message, type) {
    type = type || 'info';

    // Create toast container if not exists
    var toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
      toastContainer = document.createElement('div');
      toastContainer.id = 'toast-container';
      toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      toastContainer.style.zIndex = '9999';
      document.body.appendChild(toastContainer);
    }

    var bgClass = type === 'error' ? 'bg-danger' : (type === 'success' ? 'bg-success' : 'bg-info');

    var toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white ' + bgClass + ' border-0';
    toast.setAttribute('role', 'alert');
    toast.innerHTML = '<div class="d-flex"><div class="toast-body">' + escapeHtml(message) + '</div>' +
      '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';

    toastContainer.appendChild(toast);

    var bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', function () {
      toast.remove();
    });
  }

  /**
   * Escape HTML entities
   */
  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Expose for column builder
  window.LandingPageBuilder.addBlock = addBlock;
  window.LandingPageBuilder.openConfigPanel = openConfigPanel;
  window.LandingPageBuilder.showNotification = showNotification;

  // Initialize when DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
