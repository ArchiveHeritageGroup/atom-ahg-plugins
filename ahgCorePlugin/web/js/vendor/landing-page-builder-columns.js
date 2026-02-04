/**
 * Landing Page Builder - Column Drag & Drop
 *
 * Handles column layout functionality:
 * - Initialize Sortable.js on column drop zones
 * - Drag blocks between columns
 * - Nested block edit/delete
 * - Add blocks to specific columns
 */
(function () {
  'use strict';

  // Ensure LandingPageBuilder config exists
  if (typeof window.LandingPageBuilder === 'undefined') {
    console.error('LandingPageBuilderColumns: Main builder not found');
    return;
  }

  const Config = window.LandingPageBuilder;
  const columnSortables = [];

  /**
   * Initialize column functionality
   */
  function init() {
    initColumnSortables();
    initNestedBlockActions();
    initColumnAddButtons();
  }

  /**
   * Initialize Sortable.js on each column drop zone
   */
  function initColumnSortables() {
    if (typeof Sortable === 'undefined') {
      console.warn('LandingPageBuilderColumns: Sortable.js not available');
      return;
    }

    const dropZones = document.querySelectorAll('.column-drop-zone');

    dropZones.forEach(function (zone) {
      const sortable = new Sortable(zone, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        group: {
          name: 'column-blocks',
          pull: true,
          put: true
        },
        filter: '.empty-column',
        onAdd: function (evt) {
          handleBlockMoved(evt);
        },
        onEnd: function (evt) {
          // If moved within same column, just update order
          if (evt.from === evt.to) {
            saveColumnBlockOrder(evt.to);
          }
        }
      });

      columnSortables.push(sortable);

      // Setup drop zone for palette items
      zone.addEventListener('dragover', handleColumnDragOver);
      zone.addEventListener('drop', handleColumnDrop);
      zone.addEventListener('dragleave', handleColumnDragLeave);
    });
  }

  /**
   * Handle block moved between columns
   */
  function handleBlockMoved(evt) {
    const blockEl = evt.item;
    const blockId = blockEl.dataset.blockId;
    const newColumn = evt.to;
    const parentBlockId = newColumn.dataset.parentBlock;
    const columnSlot = newColumn.dataset.column;

    if (!blockId) return;

    // Remove empty column placeholder
    const emptyPlaceholder = newColumn.querySelector('.empty-column');
    if (emptyPlaceholder) {
      emptyPlaceholder.style.display = 'none';
    }

    // Call API to move block
    const formData = new FormData();
    formData.append('block_id', blockId);
    formData.append('parent_block_id', parentBlockId || '');
    formData.append('column_slot', columnSlot || '');

    fetch(getMoveToColumnUrl(), {
      method: 'POST',
      body: formData
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (!result.success) {
          Config.showNotification('Error moving block: ' + (result.error || 'Unknown error'), 'error');
          // Revert by reloading
          window.location.reload();
        }
      })
      .catch(function (error) {
        Config.showNotification('Network error: ' + error.message, 'error');
        window.location.reload();
      });
  }

  /**
   * Get moveToColumn URL
   */
  function getMoveToColumnUrl() {
    if (Config.urls.moveToColumn) {
      return Config.urls.moveToColumn;
    }
    // Fallback: build URL based on pattern
    if (Config.urls.reorderBlocks) {
      return Config.urls.reorderBlocks.replace('reorderBlocks', 'moveToColumn');
    }
    return '/landingPageBuilder/moveToColumn';
  }

  /**
   * Handle drag over column
   */
  function handleColumnDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    e.dataTransfer.dropEffect = 'copy';
    this.classList.add('drag-over');
  }

  /**
   * Handle drag leave column
   */
  function handleColumnDragLeave(e) {
    this.classList.remove('drag-over');
  }

  /**
   * Handle drop on column (from palette)
   */
  function handleColumnDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    this.classList.remove('drag-over');

    try {
      const data = JSON.parse(e.dataTransfer.getData('text/plain'));
      if (data.typeId) {
        const parentBlockId = this.dataset.parentBlock;
        const columnSlot = this.dataset.column;

        Config.addBlock(data.typeId, {
          parentBlockId: parentBlockId,
          columnSlot: columnSlot
        });
      }
    } catch (err) {
      // Not a palette item, let Sortable handle it
    }
  }

  /**
   * Save column block order (positions within column)
   */
  function saveColumnBlockOrder(columnZone) {
    const parentBlockId = columnZone.dataset.parentBlock;
    const blockIds = [];

    columnZone.querySelectorAll('.nested-block').forEach(function (block) {
      if (block.dataset.blockId) {
        blockIds.push(block.dataset.blockId);
      }
    });

    // The order is maintained per-column via position field
    // For simplicity, we rely on the moveToColumn handling position
  }

  /**
   * Initialize nested block action buttons
   */
  function initNestedBlockActions() {
    document.addEventListener('click', function (e) {
      const nestedBlock = e.target.closest('.nested-block');
      if (!nestedBlock) return;

      const blockId = nestedBlock.dataset.blockId;
      if (!blockId) return;

      // Edit nested block
      if (e.target.closest('.btn-edit-nested')) {
        e.preventDefault();
        e.stopPropagation();
        Config.openConfigPanel(blockId);
        return;
      }

      // Delete nested block
      if (e.target.closest('.btn-delete-nested')) {
        e.preventDefault();
        e.stopPropagation();
        deleteNestedBlock(blockId, nestedBlock);
        return;
      }
    });
  }

  /**
   * Delete a nested block
   */
  function deleteNestedBlock(blockId, blockEl) {
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
          const columnZone = blockEl.closest('.column-drop-zone');
          blockEl.remove();

          // Show empty placeholder if column is now empty
          if (columnZone && columnZone.querySelectorAll('.nested-block').length === 0) {
            const emptyPlaceholder = columnZone.querySelector('.empty-column');
            if (emptyPlaceholder) {
              emptyPlaceholder.style.display = '';
            }
          }

          Config.showNotification('Block deleted', 'success');
        } else {
          Config.showNotification('Error: ' + result.error, 'error');
        }
      })
      .catch(function (error) {
        Config.showNotification('Network error: ' + error.message, 'error');
      });
  }

  /**
   * Initialize click-to-add buttons for columns
   */
  function initColumnAddButtons() {
    // Allow clicking on empty column placeholder to add block
    document.addEventListener('click', function (e) {
      const emptyCol = e.target.closest('.empty-column');
      if (!emptyCol) return;

      const columnZone = emptyCol.closest('.column-drop-zone');
      if (!columnZone) return;

      const parentBlockId = columnZone.dataset.parentBlock;
      const columnSlot = columnZone.dataset.column;

      // Show block type picker modal or use default
      showBlockPicker(parentBlockId, columnSlot);
    });
  }

  /**
   * Show block type picker for adding to column
   */
  function showBlockPicker(parentBlockId, columnSlot) {
    // Check if modal already exists
    let modal = document.getElementById('block-picker-modal');

    if (!modal) {
      // Create modal
      modal = document.createElement('div');
      modal.id = 'block-picker-modal';
      modal.className = 'modal fade';
      modal.innerHTML = buildBlockPickerHTML();
      document.body.appendChild(modal);
    }

    // Update hidden fields
    modal.querySelector('#picker-parent-block-id').value = parentBlockId || '';
    modal.querySelector('#picker-column-slot').value = columnSlot || '';

    // Show modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    // Setup click handlers
    modal.querySelectorAll('.block-picker-item').forEach(function (item) {
      item.onclick = function () {
        const typeId = this.dataset.typeId;
        const pBlockId = modal.querySelector('#picker-parent-block-id').value;
        const pColSlot = modal.querySelector('#picker-column-slot').value;

        bsModal.hide();

        Config.addBlock(typeId, {
          parentBlockId: pBlockId || null,
          columnSlot: pColSlot || null
        });
      };
    });
  }

  /**
   * Build block picker modal HTML
   */
  function buildBlockPickerHTML() {
    let html = '<div class="modal-dialog modal-dialog-centered">';
    html += '<div class="modal-content">';
    html += '<div class="modal-header">';
    html += '<h5 class="modal-title">Add Block to Column</h5>';
    html += '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
    html += '</div>';
    html += '<div class="modal-body">';
    html += '<input type="hidden" id="picker-parent-block-id">';
    html += '<input type="hidden" id="picker-column-slot">';
    html += '<div class="row g-2">';

    // Add available block types (exclude layout blocks)
    const excludeTypes = ['header_section', 'footer_section', 'row_1_col', 'row_2_col', 'row_3_col'];

    if (Config.blockTypes) {
      Config.blockTypes.forEach(function (type) {
        if (excludeTypes.indexOf(type.machine_name) === -1) {
          html += '<div class="col-6">';
          html += '<div class="block-picker-item card h-100" data-type-id="' + type.id + '" style="cursor:pointer;">';
          html += '<div class="card-body py-2 px-3">';
          html += '<small class="fw-medium">' + escapeHtml(type.label) + '</small>';
          html += '</div>';
          html += '</div>';
          html += '</div>';
        }
      });
    }

    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';

    return html;
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

  // Initialize when DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
