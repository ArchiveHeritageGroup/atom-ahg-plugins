/**
 * NER PDF Overlay Display (Issue #20)
 *
 * Displays PDF documents with approved/linked NER entities highlighted.
 * Uses PDF.js for rendering and text layer search for highlighting.
 *
 * @author AHG Development
 * @version 1.0.0
 */
class NerPdfOverlay {
    constructor(options = {}) {
        this.options = Object.assign({
            containerId: 'pdf-viewer-area',
            pdfUrl: null,
            objectId: null,
            pageCount: 1,
            apiUrl: null,
            initialScale: 1.2,
        }, options);

        // State
        this.pdfDoc = null;
        this.currentPage = 1;
        this.totalPages = this.options.pageCount;
        this.scale = this.options.initialScale;
        this.entities = [];
        this.entityTypes = {};
        this.visibleTypes = new Set();
        this.highlights = [];
        this.isLoading = false;

        // DOM Elements (cached after init)
        this.container = null;
        this.canvas = null;
        this.textLayer = null;
        this.highlightLayer = null;

        // Entity type colors
        this.typeColors = {
            'PERSON': { bg: 'rgba(78, 121, 167, 0.35)', border: '#4e79a7', label: 'Person' },
            'PER': { bg: 'rgba(78, 121, 167, 0.35)', border: '#4e79a7', label: 'Person' },
            'ORG': { bg: 'rgba(89, 161, 79, 0.35)', border: '#59a14f', label: 'Organization' },
            'GPE': { bg: 'rgba(225, 87, 89, 0.35)', border: '#e15759', label: 'Place' },
            'LOC': { bg: 'rgba(225, 87, 89, 0.35)', border: '#e15759', label: 'Location' },
            'DATE': { bg: 'rgba(176, 122, 161, 0.35)', border: '#b07aa1', label: 'Date' },
            'TIME': { bg: 'rgba(176, 122, 161, 0.35)', border: '#b07aa1', label: 'Time' },
            'EVENT': { bg: 'rgba(118, 183, 178, 0.35)', border: '#76b7b2', label: 'Event' },
            'WORK_OF_ART': { bg: 'rgba(255, 157, 167, 0.35)', border: '#ff9da7', label: 'Work' },
        };
    }

    async init() {
        this.cacheElements();
        this.bindEvents();

        try {
            this.setStatus('Loading entities...');
            await this.loadEntities();

            this.setStatus('Loading PDF...');
            await this.loadPdf();

            this.setStatus('Ready');
        } catch (error) {
            console.error('NER PDF Overlay init error:', error);
            this.setStatus('Error: ' + error.message);
        }
    }

    cacheElements() {
        this.container = document.getElementById(this.options.containerId);
        this.canvas = document.getElementById('pdf-canvas');
        this.textLayer = document.getElementById('text-layer');
        this.highlightLayer = document.getElementById('highlight-layer');
    }

    bindEvents() {
        // Page navigation
        document.getElementById('pdf-prev')?.addEventListener('click', () => this.goToPage(this.currentPage - 1));
        document.getElementById('pdf-next')?.addEventListener('click', () => this.goToPage(this.currentPage + 1));

        // Zoom controls
        document.getElementById('pdf-zoom-in')?.addEventListener('click', () => this.zoom(1.25));
        document.getElementById('pdf-zoom-out')?.addEventListener('click', () => this.zoom(0.8));

        // Entity type toggles
        document.querySelectorAll('.entity-type-toggle').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.toggleEntityType(e.target.dataset.type, e.target.checked);
            });
        });

        // Show all / Hide all buttons
        document.getElementById('btn-show-all')?.addEventListener('click', () => this.setAllTypesVisible(true));
        document.getElementById('btn-hide-all')?.addEventListener('click', () => this.setAllTypesVisible(false));

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

            if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
                e.preventDefault();
                this.goToPage(this.currentPage - 1);
            } else if (e.key === 'ArrowRight' || e.key === 'PageDown') {
                e.preventDefault();
                this.goToPage(this.currentPage + 1);
            } else if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                this.zoom(1.25);
            } else if (e.key === '-') {
                e.preventDefault();
                this.zoom(0.8);
            }
        });
    }

    async loadEntities() {
        const response = await fetch(this.options.apiUrl);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Failed to load entities');
        }

        // Store entity types and flat list of all entities
        this.entityTypes = {};
        this.entities = [];

        for (const typeData of (data.entity_types || [])) {
            this.entityTypes[typeData.type] = typeData;
            this.visibleTypes.add(typeData.type);

            for (const entity of typeData.entities) {
                this.entities.push({
                    ...entity,
                    type: typeData.type,
                    color: typeData.color,
                    borderColor: typeData.borderColor,
                });
            }
        }

        console.log('Loaded entities:', this.entities.length, 'types:', Object.keys(this.entityTypes));
    }

    async loadPdf() {
        if (!window.pdfjsLib) {
            throw new Error('PDF.js library not loaded');
        }

        this.pdfDoc = await pdfjsLib.getDocument(this.options.pdfUrl).promise;
        this.totalPages = this.pdfDoc.numPages;

        console.log('PDF loaded, pages:', this.totalPages);

        // Render first page
        await this.renderPage(1);
        this.renderEntityList();
    }

    async renderPage(pageNum) {
        if (!this.pdfDoc || pageNum < 1 || pageNum > this.totalPages) return;

        this.isLoading = true;
        this.currentPage = pageNum;
        this.updatePageInfo();

        const page = await this.pdfDoc.getPage(pageNum);
        const viewport = page.getViewport({ scale: this.scale });

        // Set canvas dimensions
        this.canvas.width = viewport.width;
        this.canvas.height = viewport.height;

        // Set wrapper dimensions
        const wrapper = document.getElementById('pdf-page-wrapper');
        wrapper.style.width = viewport.width + 'px';
        wrapper.style.height = viewport.height + 'px';

        // Render PDF page
        const ctx = this.canvas.getContext('2d');
        await page.render({
            canvasContext: ctx,
            viewport: viewport,
        }).promise;

        // Render text layer
        await this.renderTextLayer(page, viewport);

        // Highlight entities
        this.highlightEntities();

        this.isLoading = false;
    }

    async renderTextLayer(page, viewport) {
        // Clear existing
        this.textLayer.innerHTML = '';
        this.textLayer.style.width = viewport.width + 'px';
        this.textLayer.style.height = viewport.height + 'px';

        const textContent = await page.getTextContent();

        // Use PDF.js text layer rendering
        pdfjsLib.renderTextLayer({
            textContentSource: textContent,
            container: this.textLayer,
            viewport: viewport,
            textDivs: [],
        });
    }

    highlightEntities() {
        // Clear existing highlights
        this.highlightLayer.innerHTML = '';
        this.highlights = [];

        // Get all text spans from the text layer
        const textSpans = this.textLayer.querySelectorAll('span');
        if (textSpans.length === 0) {
            console.log('No text spans found in text layer');
            return;
        }

        // Build a map of text content for searching
        const textMap = [];
        textSpans.forEach((span, index) => {
            const text = span.textContent || '';
            if (text.trim()) {
                textMap.push({
                    span: span,
                    index: index,
                    text: text,
                    textLower: text.toLowerCase(),
                });
            }
        });

        // For each visible entity, find matching text and highlight
        for (const entity of this.entities) {
            if (!this.visibleTypes.has(entity.type)) continue;

            const entityValue = entity.value;
            const entityLower = entityValue.toLowerCase();

            // Search for entity in text spans
            for (const item of textMap) {
                const matchIndex = item.textLower.indexOf(entityLower);
                if (matchIndex !== -1) {
                    // Found a match - create highlight
                    this.createHighlight(item.span, entity, matchIndex, entityValue.length);
                }
            }

            // Also try multi-span matching for entities that span multiple text items
            this.findMultiSpanMatches(textMap, entity);
        }

        console.log('Created highlights:', this.highlights.length);
    }

    findMultiSpanMatches(textMap, entity) {
        const entityWords = entity.value.split(/\s+/);
        if (entityWords.length < 2) return;

        // Try to find consecutive spans that match entity words
        for (let i = 0; i < textMap.length - entityWords.length + 1; i++) {
            let match = true;
            let matchedSpans = [];

            for (let j = 0; j < entityWords.length && match; j++) {
                const word = entityWords[j].toLowerCase();
                const item = textMap[i + j];

                if (!item || !item.textLower.includes(word)) {
                    match = false;
                } else {
                    matchedSpans.push(item.span);
                }
            }

            if (match && matchedSpans.length > 1) {
                this.createMultiSpanHighlight(matchedSpans, entity);
            }
        }
    }

    createHighlight(span, entity, matchIndex, matchLength) {
        const rect = span.getBoundingClientRect();
        const containerRect = this.highlightLayer.parentElement.getBoundingClientRect();

        // Calculate position relative to container
        const left = rect.left - containerRect.left;
        const top = rect.top - containerRect.top;

        // Estimate character width for partial matches
        const charWidth = rect.width / (span.textContent?.length || 1);
        const highlightLeft = left + (matchIndex * charWidth);
        const highlightWidth = Math.min(matchLength * charWidth, rect.width - (matchIndex * charWidth));

        const highlight = document.createElement('div');
        highlight.className = `ner-highlight ner-highlight-${entity.type.toLowerCase()}`;
        highlight.style.cssText = `
            position: absolute;
            left: ${highlightLeft}px;
            top: ${top}px;
            width: ${highlightWidth}px;
            height: ${rect.height}px;
            background: ${entity.color};
            border-bottom: 2px solid ${entity.borderColor};
            pointer-events: auto;
            cursor: pointer;
            z-index: 10;
        `;

        highlight.dataset.entityId = entity.id;
        highlight.dataset.entityType = entity.type;
        highlight.dataset.entityValue = entity.value;
        highlight.title = `${this.typeColors[entity.type]?.label || entity.type}: ${entity.value}`;

        // Click handler
        highlight.addEventListener('click', () => this.onHighlightClick(entity));

        this.highlightLayer.appendChild(highlight);
        this.highlights.push({ element: highlight, entity: entity });
    }

    createMultiSpanHighlight(spans, entity) {
        const containerRect = this.highlightLayer.parentElement.getBoundingClientRect();

        // Get bounding box of all spans
        let minLeft = Infinity, minTop = Infinity, maxRight = 0, maxBottom = 0;

        spans.forEach(span => {
            const rect = span.getBoundingClientRect();
            const left = rect.left - containerRect.left;
            const top = rect.top - containerRect.top;
            minLeft = Math.min(minLeft, left);
            minTop = Math.min(minTop, top);
            maxRight = Math.max(maxRight, left + rect.width);
            maxBottom = Math.max(maxBottom, top + rect.height);
        });

        const highlight = document.createElement('div');
        highlight.className = `ner-highlight ner-highlight-${entity.type.toLowerCase()} ner-highlight-multispan`;
        highlight.style.cssText = `
            position: absolute;
            left: ${minLeft}px;
            top: ${minTop}px;
            width: ${maxRight - minLeft}px;
            height: ${maxBottom - minTop}px;
            background: ${entity.color};
            border-bottom: 2px solid ${entity.borderColor};
            pointer-events: auto;
            cursor: pointer;
            z-index: 10;
        `;

        highlight.dataset.entityId = entity.id;
        highlight.dataset.entityType = entity.type;
        highlight.dataset.entityValue = entity.value;
        highlight.title = `${this.typeColors[entity.type]?.label || entity.type}: ${entity.value}`;

        highlight.addEventListener('click', () => this.onHighlightClick(entity));

        this.highlightLayer.appendChild(highlight);
        this.highlights.push({ element: highlight, entity: entity });
    }

    onHighlightClick(entity) {
        // Highlight entity in panel
        this.highlightEntityInPanel(entity.id);

        // Show entity details (could open a modal or expand panel item)
        console.log('Entity clicked:', entity);
    }

    highlightEntityInPanel(entityId) {
        // Remove existing highlight
        document.querySelectorAll('.entity-panel-item.active').forEach(el => {
            el.classList.remove('active');
        });

        // Add highlight to clicked entity
        const item = document.querySelector(`.entity-panel-item[data-entity-id="${entityId}"]`);
        if (item) {
            item.classList.add('active');
            item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    renderEntityList() {
        const listEl = document.getElementById('entity-list');
        if (!listEl) return;

        // Remove loading indicator
        const loadingEl = document.getElementById('entity-loading');
        if (loadingEl) loadingEl.remove();

        if (this.entities.length === 0) {
            listEl.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <p class="mb-0">No approved entities found.<br>Review and approve entities first.</p>
                </div>
            `;
            return;
        }

        // Group by type for display
        let html = '';
        for (const type of Object.keys(this.entityTypes)) {
            const typeData = this.entityTypes[type];
            const typeEntities = this.entities.filter(e => e.type === type);

            if (typeEntities.length === 0) continue;

            html += `
                <div class="entity-type-group">
                    <div class="entity-type-header" style="border-left: 3px solid ${typeData.borderColor};">
                        <span class="badge" style="background: ${typeData.borderColor};">${typeData.label}</span>
                        <span class="badge bg-secondary ms-1">${typeEntities.length}</span>
                    </div>
                    <div class="entity-type-items">
            `;

            for (const entity of typeEntities) {
                const linkedInfo = entity.linkedName
                    ? `<small class="text-success d-block"><i class="fas fa-link me-1"></i>${this.escapeHtml(entity.linkedName)}</small>`
                    : '';

                html += `
                    <div class="entity-panel-item" data-entity-id="${entity.id}" data-entity-type="${type}">
                        <div class="entity-value">${this.escapeHtml(entity.value)}</div>
                        ${linkedInfo}
                        <small class="text-muted">${Math.round(entity.confidence * 100)}% confidence</small>
                    </div>
                `;
            }

            html += '</div></div>';
        }

        listEl.innerHTML = html;

        // Bind click events
        listEl.querySelectorAll('.entity-panel-item').forEach(item => {
            item.addEventListener('click', () => {
                const entityId = item.dataset.entityId;
                this.scrollToEntity(entityId);
            });
        });
    }

    scrollToEntity(entityId) {
        // Find highlight for this entity
        const highlight = this.highlights.find(h => h.entity.id == entityId);
        if (highlight && highlight.element) {
            highlight.element.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Flash effect
            highlight.element.classList.add('ner-highlight-flash');
            setTimeout(() => {
                highlight.element.classList.remove('ner-highlight-flash');
            }, 1500);

            // Highlight in panel
            this.highlightEntityInPanel(entityId);
        }
    }

    toggleEntityType(type, visible) {
        if (visible) {
            this.visibleTypes.add(type);
        } else {
            this.visibleTypes.delete(type);
        }
        this.highlightEntities();
    }

    setAllTypesVisible(visible) {
        document.querySelectorAll('.entity-type-toggle').forEach(checkbox => {
            checkbox.checked = visible;
            const type = checkbox.dataset.type;
            if (visible) {
                this.visibleTypes.add(type);
            } else {
                this.visibleTypes.delete(type);
            }
        });
        this.highlightEntities();
    }

    goToPage(pageNum) {
        if (pageNum < 1 || pageNum > this.totalPages) return;
        this.renderPage(pageNum);
    }

    updatePageInfo() {
        const pageInfoEl = document.getElementById('pdf-page-info');
        if (pageInfoEl) {
            pageInfoEl.textContent = `Page ${this.currentPage} of ${this.totalPages}`;
        }

        const prevBtn = document.getElementById('pdf-prev');
        const nextBtn = document.getElementById('pdf-next');
        if (prevBtn) prevBtn.disabled = this.currentPage <= 1;
        if (nextBtn) nextBtn.disabled = this.currentPage >= this.totalPages;
    }

    zoom(factor) {
        this.scale *= factor;
        this.scale = Math.max(0.5, Math.min(3, this.scale));

        const zoomEl = document.getElementById('zoom-level');
        if (zoomEl) {
            zoomEl.textContent = Math.round(this.scale * 100) + '%';
        }

        this.renderPage(this.currentPage);
    }

    setStatus(text) {
        const statusEl = document.getElementById('status-text');
        if (statusEl) {
            statusEl.textContent = text;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NerPdfOverlay;
}
