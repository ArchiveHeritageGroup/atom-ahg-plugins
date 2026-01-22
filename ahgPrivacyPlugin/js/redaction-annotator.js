/**
 * Redaction Annotator
 *
 * Visual redaction editor for PDFs and images.
 * Uses PDF.js for PDF rendering and Fabric.js for canvas manipulation.
 */
class RedactionAnnotator {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);

        if (!this.container) {
            throw new Error(`Container #${containerId} not found`);
        }

        this.options = Object.assign({
            objectId: null,
            documentUrl: null,
            isPdf: true,
            readonly: false,
            showToolbar: true,
            showPanel: true,
            saveUrl: '/privacyAdmin/saveVisualRedaction',
            getUrl: '/privacyAdmin/getVisualRedactions',
            deleteUrl: '/privacyAdmin/deleteVisualRedaction',
            getNerUrl: '/privacyAdmin/getNerEntitiesForPage',
            applyUrl: '/privacyAdmin/applyVisualRedactions',
            docInfoUrl: '/privacyAdmin/getDocumentInfo',
        }, options);

        // State
        this.canvas = null;
        this.pdfDoc = null;
        this.currentPage = 1;
        this.totalPages = 1;
        this.scale = 1.0;
        this.currentTool = 'select';
        this.isDrawing = false;
        this.startPoint = null;
        this.tempRect = null;
        this.regions = [];
        this.nerEntities = [];
        this.isDirty = false;
        this.isLoading = false;

        // PDF.js and canvas elements
        this.pdfCanvas = null;
        this.fabricCanvas = null;
        this.pageWrapper = null;

        this.init();
    }

    init() {
        this.createUI();
        this.bindEvents();
        if (this.options.objectId) {
            this.loadDocument();
        }
    }

    createUI() {
        this.container.innerHTML = `
            <div class="redaction-annotator">
                ${this.options.showToolbar && !this.options.readonly ? this.createToolbar() : ''}
                <div class="redaction-main">
                    <div class="redaction-canvas-container" id="${this.containerId}-canvas-container">
                        <div class="loading-overlay" id="${this.containerId}-loading">
                            <div class="spinner-border text-light" role="status"></div>
                            <span>Loading document...</span>
                        </div>
                        <div class="pdf-page-wrapper" id="${this.containerId}-page-wrapper">
                            <canvas id="${this.containerId}-pdf-canvas"></canvas>
                            <canvas id="${this.containerId}-fabric-canvas" class="redaction-canvas-overlay"></canvas>
                        </div>
                    </div>
                    ${this.options.showPanel ? this.createPanel() : ''}
                </div>
                ${this.createStatusBar()}
                ${this.createActionButtons()}
            </div>
        `;
    }

    createToolbar() {
        return `
            <div class="redaction-toolbar">
                <div class="btn-group" role="group">
                    <button type="button" class="tool-btn btn btn-sm btn-secondary active" data-tool="select" title="Select (V)">
                        <i class="fas fa-mouse-pointer"></i>
                    </button>
                    <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="rect" title="Draw Rectangle (R)">
                        <i class="far fa-square"></i>
                    </button>
                </div>
                <div class="divider"></div>
                <div class="page-nav">
                    <button type="button" class="btn btn-sm btn-outline-light" id="${this.containerId}-prev-page" title="Previous Page">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="page-info" id="${this.containerId}-page-info">Page 1 of 1</span>
                    <button type="button" class="btn btn-sm btn-outline-light" id="${this.containerId}-next-page" title="Next Page">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="divider"></div>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-light" id="${this.containerId}-zoom-out" title="Zoom Out">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" id="${this.containerId}-zoom-fit" title="Fit to Width">
                        <i class="fas fa-expand"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" id="${this.containerId}-zoom-in" title="Zoom In">
                        <i class="fas fa-search-plus"></i>
                    </button>
                </div>
                <div class="divider"></div>
                <button type="button" class="btn btn-sm btn-outline-danger" id="${this.containerId}-delete-selected" title="Delete Selected (Del)">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <div class="ms-auto">
                    <button type="button" class="btn btn-sm btn-primary" id="${this.containerId}-save" title="Save Regions (Ctrl+S)">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </div>
        `;
    }

    createPanel() {
        return `
            <div class="redaction-panel">
                <div class="panel-header">
                    <span><i class="fas fa-mask me-2"></i>Redactions</span>
                    <span class="badge bg-secondary" id="${this.containerId}-region-count">0</span>
                </div>
                <div class="panel-tabs">
                    <button class="tab-btn active" data-tab="regions">Regions</button>
                    <button class="tab-btn" data-tab="ner">Detected PII</button>
                </div>
                <div class="tab-content active" id="${this.containerId}-tab-regions">
                    <div class="empty-state" id="${this.containerId}-regions-empty">
                        <i class="fas fa-draw-polygon"></i>
                        <p>No redaction regions yet.<br>Use the rectangle tool to draw.</p>
                    </div>
                    <div id="${this.containerId}-region-list"></div>
                </div>
                <div class="tab-content" id="${this.containerId}-tab-ner">
                    <div class="empty-state" id="${this.containerId}-ner-empty">
                        <i class="fas fa-robot"></i>
                        <p>No PII detected on this page.</p>
                    </div>
                    <div id="${this.containerId}-ner-list"></div>
                </div>
            </div>
        `;
    }

    createStatusBar() {
        return `
            <div class="redaction-status-bar">
                <div class="status-text">
                    <span class="status-indicator"></span>
                    <span id="${this.containerId}-status">Ready</span>
                </div>
                <span class="zoom-info" id="${this.containerId}-zoom-info">100%</span>
            </div>
        `;
    }

    createActionButtons() {
        if (this.options.readonly) return '';
        return `
            <div class="action-buttons">
                <button type="button" class="btn btn-outline-secondary" id="${this.containerId}-cancel">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-warning" id="${this.containerId}-apply">
                    <i class="fas fa-check-double me-1"></i>Apply Redactions
                </button>
            </div>
        `;
    }

    bindEvents() {
        const self = this;

        // Tool buttons
        this.container.querySelectorAll('.tool-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                self.setTool(btn.dataset.tool);
                self.container.querySelectorAll('.tool-btn').forEach(b => {
                    b.classList.remove('active');
                    b.classList.add('btn-secondary');
                    b.classList.remove('btn-primary');
                });
                btn.classList.add('active');
                btn.classList.remove('btn-secondary');
            });
        });

        // Page navigation
        this.bindButton('prev-page', () => this.goToPage(this.currentPage - 1));
        this.bindButton('next-page', () => this.goToPage(this.currentPage + 1));

        // Zoom
        this.bindButton('zoom-in', () => this.zoom(1.25));
        this.bindButton('zoom-out', () => this.zoom(0.8));
        this.bindButton('zoom-fit', () => this.zoomFit());

        // Actions
        this.bindButton('delete-selected', () => this.deleteSelected());
        this.bindButton('save', () => this.save());
        this.bindButton('apply', () => this.applyRedactions());
        this.bindButton('cancel', () => this.close());

        // Tab switching
        this.container.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                self.switchTab(btn.dataset.tab);
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', e => {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;
            if (!self.container.closest('.modal')?.classList.contains('show')) return;

            if (e.key === 'Delete' || e.key === 'Backspace') {
                e.preventDefault();
                self.deleteSelected();
            } else if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                self.save();
            } else if (e.key === 'v' || e.key === 'V') {
                self.setTool('select');
            } else if (e.key === 'r' || e.key === 'R') {
                self.setTool('rect');
            }
        });
    }

    bindButton(id, handler) {
        const btn = document.getElementById(`${this.containerId}-${id}`);
        if (btn) btn.addEventListener('click', handler);
    }

    setTool(tool) {
        this.currentTool = tool;
        const container = document.getElementById(`${this.containerId}-canvas-container`);
        container.classList.remove('drawing', 'selecting');
        container.classList.add(tool === 'rect' ? 'drawing' : 'selecting');

        if (this.fabricCanvas) {
            this.fabricCanvas.isDrawingMode = false;
            this.fabricCanvas.selection = tool === 'select';
            this.fabricCanvas.getObjects().forEach(obj => {
                obj.selectable = tool === 'select';
                obj.evented = tool === 'select';
            });
            this.fabricCanvas.renderAll();
        }

        this.setStatus(`Tool: ${tool === 'select' ? 'Select' : 'Rectangle'}`);
    }

    switchTab(tabName) {
        this.container.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });
        this.container.querySelectorAll('.tab-content').forEach(content => {
            content.classList.toggle('active', content.id.endsWith(`-tab-${tabName}`));
        });
    }

    // Document Loading
    async loadDocument() {
        this.showLoading(true);
        this.setStatus('Loading document...');

        try {
            // Get document info
            const docInfo = await this.fetchDocumentInfo();
            if (!docInfo) {
                throw new Error('Failed to get document info');
            }

            this.options.isPdf = docInfo.is_pdf;
            this.totalPages = docInfo.page_count || 1;
            this.options.documentUrl = docInfo.url;

            this.updatePageInfo();

            if (this.options.isPdf) {
                await this.loadPdf();
            } else {
                await this.loadImage();
            }

            // Load saved regions
            await this.loadRegions();

            // Load NER entities
            await this.loadNerEntities();

            this.showLoading(false);
            this.setStatus('Ready');
        } catch (error) {
            console.error('Document load error:', error);
            this.setStatus('Error: ' + error.message, true);
            this.showLoading(false);
        }
    }

    async fetchDocumentInfo() {
        const response = await fetch(`${this.options.docInfoUrl}?id=${this.options.objectId}`);
        const data = await response.json();
        if (data.success) {
            return data.document;
        }
        return null;
    }

    async loadPdf() {
        if (!window.pdfjsLib) {
            throw new Error('PDF.js library not loaded');
        }

        const loadingTask = pdfjsLib.getDocument(this.options.documentUrl);
        this.pdfDoc = await loadingTask.promise;
        this.totalPages = this.pdfDoc.numPages;
        this.updatePageInfo();

        await this.renderPage(1);
    }

    async loadImage() {
        const self = this;
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function() {
                self.initCanvasForImage(img);
                resolve();
            };
            img.onerror = () => reject(new Error('Failed to load image'));
            img.src = self.options.documentUrl;
        });
    }

    async renderPage(pageNum) {
        if (!this.pdfDoc) return;

        this.currentPage = pageNum;
        this.updatePageInfo();
        this.showLoading(true);

        const page = await this.pdfDoc.getPage(pageNum);
        const viewport = page.getViewport({ scale: this.scale });

        // Get or create PDF canvas
        this.pdfCanvas = document.getElementById(`${this.containerId}-pdf-canvas`);
        const ctx = this.pdfCanvas.getContext('2d');

        this.pdfCanvas.width = viewport.width;
        this.pdfCanvas.height = viewport.height;

        // Render PDF page
        await page.render({
            canvasContext: ctx,
            viewport: viewport
        }).promise;

        // Initialize Fabric canvas overlay
        this.initFabricCanvas(viewport.width, viewport.height);

        // Load regions for this page
        await this.loadRegions();
        await this.loadNerEntities();

        this.showLoading(false);
    }

    initFabricCanvas(width, height) {
        const fabricCanvasEl = document.getElementById(`${this.containerId}-fabric-canvas`);
        fabricCanvasEl.width = width;
        fabricCanvasEl.height = height;

        if (this.fabricCanvas) {
            this.fabricCanvas.dispose();
        }

        this.fabricCanvas = new fabric.Canvas(fabricCanvasEl, {
            selection: this.currentTool === 'select',
            preserveObjectStacking: true
        });

        this.bindCanvasEvents();
        this.renderRegions();
    }

    initCanvasForImage(img) {
        const container = document.getElementById(`${this.containerId}-canvas-container`);
        const maxWidth = container.clientWidth - 40;
        const maxHeight = container.clientHeight - 40;

        this.scale = Math.min(maxWidth / img.width, maxHeight / img.height, 1);

        const width = img.width * this.scale;
        const height = img.height * this.scale;

        // Set PDF canvas as image background
        this.pdfCanvas = document.getElementById(`${this.containerId}-pdf-canvas`);
        this.pdfCanvas.width = width;
        this.pdfCanvas.height = height;
        const ctx = this.pdfCanvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);

        // Initialize Fabric canvas
        this.initFabricCanvas(width, height);
    }

    bindCanvasEvents() {
        const self = this;

        this.fabricCanvas.on('mouse:down', function(e) {
            if (self.options.readonly || self.currentTool !== 'rect') return;
            if (e.target) return; // Clicked on existing object

            self.isDrawing = true;
            const pointer = self.fabricCanvas.getPointer(e.e);
            self.startPoint = pointer;
        });

        this.fabricCanvas.on('mouse:move', function(e) {
            if (!self.isDrawing) return;

            const pointer = self.fabricCanvas.getPointer(e.e);

            if (self.tempRect) {
                self.fabricCanvas.remove(self.tempRect);
            }

            self.tempRect = new fabric.Rect({
                left: Math.min(self.startPoint.x, pointer.x),
                top: Math.min(self.startPoint.y, pointer.y),
                width: Math.abs(pointer.x - self.startPoint.x),
                height: Math.abs(pointer.y - self.startPoint.y),
                fill: 'rgba(0, 0, 0, 0.3)',
                stroke: '#ff0000',
                strokeWidth: 2,
                strokeDashArray: [5, 5],
                selectable: false,
                evented: false
            });

            self.fabricCanvas.add(self.tempRect);
        });

        this.fabricCanvas.on('mouse:up', function(e) {
            if (!self.isDrawing) return;
            self.isDrawing = false;

            if (self.tempRect) {
                self.fabricCanvas.remove(self.tempRect);
            }

            const pointer = self.fabricCanvas.getPointer(e.e);
            const width = Math.abs(pointer.x - self.startPoint.x);
            const height = Math.abs(pointer.y - self.startPoint.y);

            // Minimum size
            if (width < 10 || height < 10) {
                self.tempRect = null;
                return;
            }

            // Create permanent region
            self.addRegion({
                x: Math.min(self.startPoint.x, pointer.x),
                y: Math.min(self.startPoint.y, pointer.y),
                width: width,
                height: height
            });

            self.tempRect = null;
        });

        this.fabricCanvas.on('selection:created', function(e) {
            self.highlightRegionItem(e.selected[0]);
        });

        this.fabricCanvas.on('selection:cleared', function() {
            self.highlightRegionItem(null);
        });

        this.fabricCanvas.on('object:modified', function(e) {
            self.updateRegionFromCanvas(e.target);
        });
    }

    // Region Management
    addRegion(coords) {
        const canvasWidth = this.fabricCanvas.getWidth();
        const canvasHeight = this.fabricCanvas.getHeight();

        // Normalize coordinates
        const region = {
            id: 'new_' + Date.now(),
            page_number: this.currentPage,
            region_type: 'rectangle',
            coordinates: {
                x: coords.x / canvasWidth,
                y: coords.y / canvasHeight,
                width: coords.width / canvasWidth,
                height: coords.height / canvasHeight
            },
            normalized: true,
            source: 'manual',
            status: 'pending',
            label: `Region ${this.regions.length + 1}`
        };

        this.regions.push(region);
        this.isDirty = true;

        // Add to canvas
        const rect = this.createRegionRect(region);
        this.fabricCanvas.add(rect);
        this.fabricCanvas.setActiveObject(rect);
        this.fabricCanvas.renderAll();

        this.updateRegionList();
        this.setStatus('Region added');
    }

    createRegionRect(region) {
        const canvasWidth = this.fabricCanvas.getWidth();
        const canvasHeight = this.fabricCanvas.getHeight();
        const coords = region.coordinates;

        const rect = new fabric.Rect({
            left: coords.x * canvasWidth,
            top: coords.y * canvasHeight,
            width: coords.width * canvasWidth,
            height: coords.height * canvasHeight,
            fill: region.status === 'applied' ? 'rgba(0, 0, 0, 0.9)' : 'rgba(0, 0, 0, 0.5)',
            stroke: region.status === 'applied' ? '#28a745' : (region.status === 'approved' ? '#17a2b8' : '#ff0000'),
            strokeWidth: 2,
            strokeDashArray: region.status === 'pending' ? [5, 5] : null,
            selectable: !this.options.readonly,
            evented: !this.options.readonly,
            transparentCorners: false,
            cornerColor: '#fff',
            cornerStrokeColor: '#333',
            borderColor: '#0d6efd',
            cornerSize: 8,
        });

        rect.regionData = region;
        return rect;
    }

    updateRegionFromCanvas(canvasObj) {
        if (!canvasObj.regionData) return;

        const region = canvasObj.regionData;
        const canvasWidth = this.fabricCanvas.getWidth();
        const canvasHeight = this.fabricCanvas.getHeight();

        region.coordinates = {
            x: canvasObj.left / canvasWidth,
            y: canvasObj.top / canvasHeight,
            width: (canvasObj.width * canvasObj.scaleX) / canvasWidth,
            height: (canvasObj.height * canvasObj.scaleY) / canvasHeight
        };

        this.isDirty = true;
        this.updateRegionList();
    }

    deleteSelected() {
        const activeObjects = this.fabricCanvas.getActiveObjects();
        if (activeObjects.length === 0) return;

        activeObjects.forEach(obj => {
            if (obj.regionData) {
                const idx = this.regions.findIndex(r => r.id === obj.regionData.id);
                if (idx > -1) {
                    this.regions.splice(idx, 1);
                }
            }
            this.fabricCanvas.remove(obj);
        });

        this.fabricCanvas.discardActiveObject();
        this.fabricCanvas.renderAll();
        this.isDirty = true;
        this.updateRegionList();
        this.setStatus('Region deleted');
    }

    renderRegions() {
        if (!this.fabricCanvas) return;

        // Clear existing region objects
        const objects = this.fabricCanvas.getObjects().slice();
        objects.forEach(obj => {
            if (obj.regionData) {
                this.fabricCanvas.remove(obj);
            }
        });

        // Add regions for current page
        const pageRegions = this.regions.filter(r => r.page_number === this.currentPage);
        pageRegions.forEach(region => {
            const rect = this.createRegionRect(region);
            this.fabricCanvas.add(rect);
        });

        this.fabricCanvas.renderAll();
        this.updateRegionList();
    }

    updateRegionList() {
        const listEl = document.getElementById(`${this.containerId}-region-list`);
        const emptyEl = document.getElementById(`${this.containerId}-regions-empty`);
        const countEl = document.getElementById(`${this.containerId}-region-count`);

        const pageRegions = this.regions.filter(r => r.page_number === this.currentPage);

        if (countEl) countEl.textContent = this.regions.length;

        if (pageRegions.length === 0) {
            if (emptyEl) emptyEl.style.display = 'block';
            if (listEl) listEl.innerHTML = '';
            return;
        }

        if (emptyEl) emptyEl.style.display = 'none';

        const self = this;
        listEl.innerHTML = pageRegions.map((region, idx) => {
            const statusBadge = {
                pending: '<span class="badge bg-warning text-dark">Pending</span>',
                approved: '<span class="badge bg-info">Approved</span>',
                applied: '<span class="badge bg-success">Applied</span>'
            }[region.status] || '';

            return `
                <div class="region-item" data-id="${region.id}" style="border-left-color: ${region.status === 'applied' ? '#28a745' : '#ff0000'}">
                    <div class="region-body">
                        <div class="region-label">${region.label || 'Region ' + (idx + 1)}</div>
                        <div class="region-meta">
                            ${statusBadge}
                            <span class="ms-2">${Math.round(region.coordinates.width * 100)}% x ${Math.round(region.coordinates.height * 100)}%</span>
                        </div>
                    </div>
                    <div class="region-actions">
                        <button class="btn btn-outline-danger btn-sm region-delete" data-id="${region.id}" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        // Bind events
        listEl.querySelectorAll('.region-item').forEach(item => {
            item.addEventListener('click', e => {
                if (e.target.closest('.region-delete')) {
                    self.deleteRegionById(item.dataset.id);
                } else {
                    self.selectRegionById(item.dataset.id);
                }
            });
        });
    }

    selectRegionById(id) {
        const obj = this.fabricCanvas.getObjects().find(o => o.regionData?.id === id);
        if (obj) {
            this.fabricCanvas.setActiveObject(obj);
            this.fabricCanvas.renderAll();
        }
    }

    deleteRegionById(id) {
        const obj = this.fabricCanvas.getObjects().find(o => o.regionData?.id === id);
        if (obj) {
            this.fabricCanvas.remove(obj);
        }
        const idx = this.regions.findIndex(r => r.id === id);
        if (idx > -1) {
            this.regions.splice(idx, 1);
        }
        this.fabricCanvas.renderAll();
        this.isDirty = true;
        this.updateRegionList();
    }

    highlightRegionItem(canvasObj) {
        this.container.querySelectorAll('.region-item').forEach(item => {
            item.classList.remove('selected');
        });

        if (canvasObj && canvasObj.regionData) {
            const item = this.container.querySelector(`.region-item[data-id="${canvasObj.regionData.id}"]`);
            if (item) item.classList.add('selected');
        }
    }

    // NER Entities
    async loadNerEntities() {
        try {
            const response = await fetch(`${this.options.getNerUrl}?id=${this.options.objectId}&page=${this.currentPage}`);
            const data = await response.json();
            if (data.success) {
                this.nerEntities = data.entities || [];
                this.updateNerList();
            }
        } catch (error) {
            console.error('Failed to load NER entities:', error);
        }
    }

    updateNerList() {
        const listEl = document.getElementById(`${this.containerId}-ner-list`);
        const emptyEl = document.getElementById(`${this.containerId}-ner-empty`);

        if (this.nerEntities.length === 0) {
            if (emptyEl) emptyEl.style.display = 'block';
            if (listEl) listEl.innerHTML = '';
            return;
        }

        if (emptyEl) emptyEl.style.display = 'none';

        const self = this;
        listEl.innerHTML = this.nerEntities.map(entity => {
            const typeClass = {
                PERSON: 'person',
                EMAIL: 'email',
                PHONE_SA: 'phone',
                SA_ID: 'id',
                BANK_ACCOUNT: 'bank'
            }[entity.type] || '';

            const converted = this.regions.some(r => r.linked_entity_id === entity.id);

            return `
                <div class="ner-entity-item ${converted ? 'converted' : ''}" data-id="${entity.id}">
                    <div class="region-body">
                        <span class="ner-type ${typeClass}">${entity.type}</span>
                        <div class="region-label mt-1">${self.escapeHtml(entity.text)}</div>
                        <div class="region-meta">
                            Confidence: ${Math.round(entity.confidence * 100)}%
                            ${converted ? '<span class="badge bg-success ms-2">Added</span>' : ''}
                        </div>
                    </div>
                    ${!converted ? `
                        <div class="region-actions">
                            <button class="btn btn-outline-warning btn-sm ner-convert" data-id="${entity.id}" title="Add as Redaction">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');

        listEl.querySelectorAll('.ner-convert').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                self.convertNerToRegion(btn.dataset.id);
            });
        });
    }

    convertNerToRegion(entityId) {
        const entity = this.nerEntities.find(e => e.id == entityId);
        if (!entity || !entity.x || !entity.y) {
            this.setStatus('Cannot add: no bounding box data');
            return;
        }

        const region = {
            id: 'ner_' + entityId,
            page_number: this.currentPage,
            region_type: 'rectangle',
            coordinates: {
                x: entity.x,
                y: entity.y,
                width: entity.width,
                height: entity.height
            },
            normalized: true,
            source: 'auto_ner',
            linked_entity_id: entity.id,
            status: 'approved',
            label: `${entity.type}: ${entity.text.substring(0, 20)}`
        };

        this.regions.push(region);
        this.isDirty = true;

        const rect = this.createRegionRect(region);
        this.fabricCanvas.add(rect);
        this.fabricCanvas.renderAll();

        this.updateRegionList();
        this.updateNerList();
        this.setStatus('NER entity added as redaction');
    }

    // Page Navigation
    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        if (page === this.currentPage) return;

        this.currentPage = page;
        if (this.options.isPdf) {
            this.renderPage(page);
        }
    }

    updatePageInfo() {
        const el = document.getElementById(`${this.containerId}-page-info`);
        if (el) {
            el.textContent = `Page ${this.currentPage} of ${this.totalPages}`;
        }

        // Update button states
        const prevBtn = document.getElementById(`${this.containerId}-prev-page`);
        const nextBtn = document.getElementById(`${this.containerId}-next-page`);
        if (prevBtn) prevBtn.disabled = this.currentPage <= 1;
        if (nextBtn) nextBtn.disabled = this.currentPage >= this.totalPages;
    }

    // Zoom
    zoom(factor) {
        this.scale *= factor;
        this.scale = Math.max(0.25, Math.min(3, this.scale));

        if (this.options.isPdf && this.currentPage) {
            this.renderPage(this.currentPage);
        }

        this.updateZoomInfo();
    }

    zoomFit() {
        const container = document.getElementById(`${this.containerId}-canvas-container`);
        if (!this.pdfCanvas) return;

        const containerWidth = container.clientWidth - 40;
        this.scale = containerWidth / (this.pdfCanvas.width / this.scale);

        if (this.options.isPdf && this.currentPage) {
            this.renderPage(this.currentPage);
        }

        this.updateZoomInfo();
    }

    updateZoomInfo() {
        const el = document.getElementById(`${this.containerId}-zoom-info`);
        if (el) {
            el.textContent = `${Math.round(this.scale * 100)}%`;
        }
    }

    // Data Operations
    async loadRegions() {
        try {
            const response = await fetch(`${this.options.getUrl}?id=${this.options.objectId}&page=${this.currentPage}`);
            const data = await response.json();
            if (data.success) {
                // Merge with existing regions (keep unsaved ones)
                const savedRegions = data.regions || [];
                const unsavedRegions = this.regions.filter(r => r.id.startsWith('new_'));
                this.regions = [...savedRegions, ...unsavedRegions];
                this.renderRegions();
            }
        } catch (error) {
            console.error('Failed to load regions:', error);
        }
    }

    async save() {
        if (!this.isDirty) {
            this.setStatus('No changes to save');
            return;
        }

        this.setStatus('Saving...', false, true);

        try {
            const response = await fetch(this.options.saveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    object_id: this.options.objectId,
                    page: this.currentPage,
                    regions: this.regions.filter(r => r.page_number === this.currentPage)
                })
            });

            const data = await response.json();
            if (data.success) {
                this.isDirty = false;
                // Update region IDs from server
                if (data.regions) {
                    this.regions = this.regions.filter(r => r.page_number !== this.currentPage);
                    this.regions.push(...data.regions);
                }
                this.renderRegions();
                this.setStatus('Saved successfully');
            } else {
                throw new Error(data.error || 'Save failed');
            }
        } catch (error) {
            console.error('Save error:', error);
            this.setStatus('Save failed: ' + error.message, true);
        }
    }

    async applyRedactions() {
        if (this.isDirty) {
            await this.save();
        }

        if (!confirm('Apply all redactions and generate redacted document?')) {
            return;
        }

        this.showLoading(true, 'Applying redactions...');

        try {
            const response = await fetch(this.options.applyUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ object_id: this.options.objectId })
            });

            const data = await response.json();
            if (data.success) {
                this.setStatus('Redactions applied successfully');
                await this.loadRegions();
                if (data.download_url) {
                    window.open(data.download_url, '_blank');
                }
            } else {
                throw new Error(data.error || 'Apply failed');
            }
        } catch (error) {
            console.error('Apply error:', error);
            this.setStatus('Apply failed: ' + error.message, true);
        } finally {
            this.showLoading(false);
        }
    }

    close() {
        if (this.isDirty && !confirm('You have unsaved changes. Discard them?')) {
            return;
        }

        // Close modal if in modal
        const modal = this.container.closest('.modal');
        if (modal && bootstrap?.Modal) {
            bootstrap.Modal.getInstance(modal)?.hide();
        }
    }

    // UI Helpers
    showLoading(show, message = 'Loading...') {
        const el = document.getElementById(`${this.containerId}-loading`);
        if (el) {
            el.style.display = show ? 'flex' : 'none';
            const span = el.querySelector('span');
            if (span) span.textContent = message;
        }
        this.isLoading = show;
    }

    setStatus(text, isError = false, isSaving = false) {
        const el = document.getElementById(`${this.containerId}-status`);
        const indicator = this.container.querySelector('.status-indicator');

        if (el) el.textContent = text;
        if (indicator) {
            indicator.classList.remove('error', 'saving');
            if (isError) indicator.classList.add('error');
            if (isSaving) indicator.classList.add('saving');
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    destroy() {
        if (this.fabricCanvas) {
            this.fabricCanvas.dispose();
        }
        this.container.innerHTML = '';
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RedactionAnnotator;
}
