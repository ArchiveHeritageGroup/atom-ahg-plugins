/**
 * Column Drop Zone Handling for Landing Page Builder
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initColumnDropZones();
    });

    function initColumnDropZones() {
        // Find all column drop zones
        const dropZones = document.querySelectorAll('.column-drop-zone');
        
        if (dropZones.length === 0) {
            console.log('No column drop zones found');
            return;
        }

        console.log('Initializing', dropZones.length, 'column drop zones');

        dropZones.forEach(function(zone) {
            // Prevent default drag behaviors
            zone.addEventListener('dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = '#0d6efd';
                this.style.backgroundColor = '#e7f1ff';
            });

            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.dataTransfer.dropEffect = 'copy';
            });

            zone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = '';
                this.style.backgroundColor = '#fff';
            });

            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                this.style.borderColor = '';
                this.style.backgroundColor = '#fff';

                const parentBlockId = this.dataset.parentBlock;
                const columnSlot = this.dataset.column;

                console.log('Drop on column:', parentBlockId, columnSlot);

                // Check for block type from palette
                const blockTypeId = e.dataTransfer.getData('block-type-id');
                if (blockTypeId) {
                    console.log('Adding new block type:', blockTypeId, 'to column:', columnSlot);
                    addBlockToColumn(blockTypeId, parentBlockId, columnSlot);
                    return;
                }

                // Check for existing block being moved
                const blockId = e.dataTransfer.getData('block-id');
                if (blockId) {
                    console.log('Moving block:', blockId, 'to column:', columnSlot);
                    moveBlockToColumn(blockId, parentBlockId, columnSlot);
                }
            });
        });

        // Make palette items set data on drag
        const paletteItems = document.querySelectorAll('.block-type-item');
        paletteItems.forEach(function(item) {
            item.addEventListener('dragstart', function(e) {
                const typeId = this.dataset.typeId;
                console.log('Dragging block type:', typeId);
                e.dataTransfer.setData('block-type-id', typeId);
                e.dataTransfer.effectAllowed = 'copy';
            });
        });

        // Make nested blocks draggable
        const nestedBlocks = document.querySelectorAll('.nested-block');
        nestedBlocks.forEach(function(block) {
            block.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('block-id', this.dataset.blockId);
                e.dataTransfer.effectAllowed = 'move';
                this.style.opacity = '0.5';
            });

            block.addEventListener('dragend', function() {
                this.style.opacity = '1';
            });
        });
    }

    function addBlockToColumn(blockTypeId, parentBlockId, columnSlot) {
        if (!window.LandingPageBuilder) {
            console.error('LandingPageBuilder config not found');
            return;
        }

        const formData = new FormData();
        formData.append('page_id', LandingPageBuilder.pageId);
        formData.append('block_type_id', blockTypeId);
        formData.append('parent_block_id', parentBlockId);
        formData.append('column_slot', columnSlot);

        console.log('Sending addBlock request:', {
            page_id: LandingPageBuilder.pageId,
            block_type_id: blockTypeId,
            parent_block_id: parentBlockId,
            column_slot: columnSlot
        });

        fetch(LandingPageBuilder.urls.addBlock, {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            console.log('addBlock result:', result);
            if (result.success) {
                // Reload to show the nested block
                location.reload();
            } else {
                alert(result.error || 'Failed to add block to column');
            }
        })
        .catch(function(error) {
            console.error('addBlock error:', error);
            alert('Failed to add block: ' + error.message);
        });
    }

    function moveBlockToColumn(blockId, parentBlockId, columnSlot) {
        const formData = new FormData();
        formData.append('block_id', blockId);
        formData.append('parent_block_id', parentBlockId);
        formData.append('column_slot', columnSlot);

        fetch('/admin/landing-pages/ajax/move-to-column', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to move block');
            }
        })
        .catch(function(error) {
            console.error('moveBlock error:', error);
        });
    }

    // Expose for debugging
    window.ColumnDropZones = {
        init: initColumnDropZones,
        addBlockToColumn: addBlockToColumn,
        moveBlockToColumn: moveBlockToColumn
    };
})();
