/**
 * Condition Photo Annotator
 * Uses Fabric.js for canvas-based image annotation
 * With legend panel and proper button styling
 */

class ConditionAnnotator {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);

        if (!this.container) {
            throw new Error(`Container #${containerId} not found`);
        }

        this.options = Object.assign({
            photoId: null,
            imageUrl: null,
            readonly: false,
            showToolbar: true,
            showLegend: true,
            onSave: null,
            saveUrl: '/condition/annotation/save',
            getUrl: '/condition/annotation/get',
        }, options);

        this.canvas = null;
        this.currentTool = 'select';
        this.currentColor = '#FF0000';
        this.currentCategory = 'damage';
        this.isDrawing = false;
        this.startPoint = null;
        this.tempShape = null;
        this.annotationsVisible = true;
        this.imageLoaded = false;
        this.originalImageSize = { width: 0, height: 0 };
        this.scale = 1;
        this.isDirty = false;

        this.categories = {
            damage: { color: '#FF0000', label: 'Damage' },
            crack: { color: '#FF4500', label: 'Crack' },
            stain: { color: '#DAA520', label: 'Stain' },
            tear: { color: '#DC143C', label: 'Tear' },
            loss: { color: '#9400D3', label: 'Loss/Missing' },
            mould: { color: '#8B0000', label: 'Mould/Fungus' },
            pest: { color: '#006400', label: 'Pest Damage' },
            water: { color: '#1E90FF', label: 'Water Damage' },
            abrasion: { color: '#FF8C00', label: 'Abrasion/Wear' },
            corrosion: { color: '#2F4F4F', label: 'Corrosion' },
            note: { color: '#4169E1', label: 'General Note' },
        };

        this.init();
    }

    init() {
        this.createUI();
        this.initCanvas();
        this.bindEvents();

        if (this.options.imageUrl) {
            setTimeout(() => this.loadImage(this.options.imageUrl), 300);
        }
    }

    createUI() {
        this.container.innerHTML = `
            <div class="condition-annotator" style="height: 100%; display: flex; flex-direction: column;">
                ${this.options.showToolbar && !this.options.readonly ? this.createToolbar() : ''}
                <div style="flex: 1; display: flex; overflow: hidden; min-height: 450px;">
                    <div class="annotator-canvas-container" style="flex: 1; background: #2a2a2a; overflow: auto; display: flex; justify-content: center; align-items: center; padding: 20px;">
                        <canvas id="${this.containerId}-canvas"></canvas>
                    </div>
                    ${this.options.showLegend ? this.createLegend() : ''}
                </div>
                <div class="annotator-status-bar" style="display: flex; justify-content: space-between; padding: 8px 15px; background: #f8f9fa; border-top: 1px solid #dee2e6; font-size: 0.85em;">
                    <span class="status-text">Ready</span>
                    <span class="annotation-count">0 annotations</span>
                </div>
            </div>
        `;
    }

    createToolbar() {
        return `
            <div class="annotator-toolbar" style="display: flex; flex-wrap: wrap; gap: 8px; padding: 10px 15px; background: #e9ecef; border-bottom: 1px solid #dee2e6; align-items: center;">
                <div class="btn-group" role="group">
                    <button type="button" class="tool-btn btn btn-sm btn-primary active" data-tool="select" title="Select (V)">
                        <i class="fas fa-mouse-pointer"></i>
                    </button>
                    <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="rect" title="Rectangle (R)">
                        <i class="far fa-square"></i>
                    </button>
                    <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="circle" title="Circle (C)">
                        <i class="far fa-circle"></i>
                    </button>
                    <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="arrow" title="Arrow (A)">
                        <i class="fas fa-long-arrow-alt-right"></i>
                    </button>
                    <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="freehand" title="Freehand (F)">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                    <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="text" title="Text (T)">
                        <i class="fas fa-font"></i>
                    </button>
                    <button type="button" class="tool-btn btn btn-sm btn-secondary" data-tool="marker" title="Marker (M)">
                        <i class="fas fa-map-marker-alt"></i>
                    </button>
                </div>

                <div style="display: flex; align-items: center; gap: 5px; margin-left: 15px;">
                    <label style="margin: 0; font-size: 0.85em; font-weight: 500;">Category:</label>
                    <select id="${this.containerId}-category" class="form-select form-select-sm" style="width: 140px;">
                        ${Object.entries(this.categories).map(([key, cat]) =>
                            `<option value="${key}" data-color="${cat.color}">${cat.label}</option>`
                        ).join('')}
                    </select>
                </div>

                <div style="display: flex; align-items: center; gap: 5px;">
                    <label style="margin: 0; font-size: 0.85em; font-weight: 500;">Color:</label>
                    <input type="color" id="${this.containerId}-color" value="${this.currentColor}" style="width: 40px; height: 28px; padding: 1px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">
                </div>

                <div class="btn-group ms-auto" role="group">
                    <button type="button" class="btn btn-sm btn-info" id="${this.containerId}-toggle" title="Show/Hide Annotations">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" id="${this.containerId}-delete" title="Delete Selected (Del)">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" id="${this.containerId}-undo" title="Undo (Ctrl+Z)">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>

                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-secondary" id="${this.containerId}-zoom-in" title="Zoom In">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" id="${this.containerId}-zoom-out" title="Zoom Out">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" id="${this.containerId}-zoom-fit" title="Fit to View">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            </div>
        `;
    }

    createLegend() {
        return `
            <div class="annotator-legend" id="${this.containerId}-legend" style="width: 240px; background: #f8f9fa; border-left: 1px solid #dee2e6; display: flex; flex-direction: column;">
                <div style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; background: #495057; color: white;">
                    <strong><i class="fas fa-list-ul me-2"></i>Annotations</strong>
                </div>
                <div class="legend-items" style="flex: 1; overflow-y: auto; padding: 10px;">
                    <div class="text-muted text-center py-4" style="font-size: 0.85em;">
                        <i class="fas fa-draw-polygon fa-2x mb-2 d-block opacity-50"></i>
                        No annotations yet.<br>
                        <small>Use tools above to annotate.</small>
                    </div>
                </div>
            </div>
        `;
    }

    initCanvas() {
        const canvasEl = document.getElementById(`${this.containerId}-canvas`);
        this.canvas = new fabric.Canvas(canvasEl, {
            selection: !this.options.readonly,
            preserveObjectStacking: true,
        });
    }

    loadImage(url) {
        this.setStatus('Loading image...');

        const containerEl = this.container.querySelector('.annotator-canvas-container');
        let maxWidth = containerEl.clientWidth - 40;
        let maxHeight = containerEl.clientHeight - 40;
        
        if (maxWidth < 400) maxWidth = 600;
        if (maxHeight < 300) maxHeight = 400;

        this.loadImageWithOrientation(url).then(({ width, height, dataUrl }) => {
            if (width === 0 || height === 0) {
                this.setStatus('Invalid image dimensions');
                return;
            }

            this.originalImageSize = { width, height };

            const scaleX = maxWidth / width;
            const scaleY = maxHeight / height;
            this.scale = Math.min(scaleX, scaleY, 1);
            if (this.scale < 0.1) this.scale = 0.1;

            const canvasWidth = Math.round(width * this.scale);
            const canvasHeight = Math.round(height * this.scale);

            this.canvas.setWidth(canvasWidth);
            this.canvas.setHeight(canvasHeight);

            fabric.Image.fromURL(dataUrl, (fabricImg) => {
                if (!fabricImg) {
                    this.setStatus('Failed to create fabric image');
                    return;
                }

                fabricImg.set({
                    scaleX: this.scale,
                    scaleY: this.scale,
                    left: 0,
                    top: 0,
                    originX: 'left',
                    originY: 'top',
                });

                this.canvas.setBackgroundImage(fabricImg, () => {
                    this.canvas.renderAll();
                    this.imageLoaded = true;
                    this.setStatus(`Loaded ${width}x${height} at ${Math.round(this.scale * 100)}%`);

                    if (this.options.photoId) {
                        setTimeout(() => this.loadAnnotations(), 100);
                    }
                });
            });
        }).catch(err => {
            console.error('Image load error:', err);
            this.setStatus('Failed to load image');
        });
    }

    loadImageWithOrientation(url) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                const width = img.naturalWidth || img.width;
                const height = img.naturalHeight || img.height;
                
                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(img, 0, 0);
                
                const dataUrl = canvas.toDataURL('image/jpeg', 0.95);
                resolve({ width, height, dataUrl });
            };
            
            img.onerror = () => reject(new Error('Failed to load image'));
            img.src = url + (url.includes('?') ? '&' : '?') + '_t=' + Date.now();
        });
    }

    bindEvents() {
        this.container.querySelectorAll('.tool-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.setTool(btn.dataset.tool);
                this.container.querySelectorAll('.tool-btn').forEach(b => {
                    b.classList.remove('active', 'btn-primary');
                    b.classList.add('btn-secondary');
                });
                btn.classList.remove('btn-secondary');
                btn.classList.add('active', 'btn-primary');
            });
        });

        const categorySelect = document.getElementById(`${this.containerId}-category`);
        if (categorySelect) {
            categorySelect.addEventListener('change', (e) => {
                this.currentCategory = e.target.value;
                const option = e.target.options[e.target.selectedIndex];
                this.currentColor = option.dataset.color;
                document.getElementById(`${this.containerId}-color`).value = this.currentColor;
            });
        }

        const colorPicker = document.getElementById(`${this.containerId}-color`);
        if (colorPicker) {
            colorPicker.addEventListener('change', (e) => {
                this.currentColor = e.target.value;
            });
        }

        this.bindButton('toggle', () => this.toggleAnnotations());
        this.bindButton('delete', () => this.deleteSelected());
        this.bindButton('undo', () => this.undo());
        this.bindButton('zoom-in', () => this.zoom(1.25));
        this.bindButton('zoom-out', () => this.zoom(0.8));
        this.bindButton('zoom-fit', () => this.zoomFit());

        this.canvas.on('mouse:down', (e) => this.onMouseDown(e));
        this.canvas.on('mouse:move', (e) => this.onMouseMove(e));
        this.canvas.on('mouse:up', (e) => this.onMouseUp(e));
        this.canvas.on('object:modified', () => this.markDirty());
        this.canvas.on('selection:created', (e) => this.highlightLegendItem(e));
        this.canvas.on('selection:updated', (e) => this.highlightLegendItem(e));
        this.canvas.on('selection:cleared', () => this.highlightLegendItem(null));

        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
            if (e.key === 'Delete' || e.key === 'Backspace') this.deleteSelected();
            else if (e.ctrlKey && e.key === 'z') this.undo();
            else if (e.ctrlKey && e.key === 's') { e.preventDefault(); this.save(); }
        });
    }

    highlightLegendItem(e) {
        this.container.querySelectorAll('.legend-item').forEach(item => item.classList.remove('selected'));
        
        if (e && e.selected && e.selected.length > 0) {
            const obj = e.selected[0];
            if (obj.annotationData) {
                const item = this.container.querySelector(`.legend-item[data-id="${obj.annotationData.id}"]`);
                if (item) {
                    item.classList.add('selected');
                    item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        }
    }

    bindButton(id, handler) {
        const btn = document.getElementById(`${this.containerId}-${id}`);
        if (btn) btn.addEventListener('click', handler);
    }

    setTool(tool) {
        this.currentTool = tool;
        const cursors = { select: 'default', rect: 'crosshair', circle: 'crosshair', arrow: 'crosshair', freehand: 'crosshair', text: 'text', marker: 'crosshair' };
        this.canvas.defaultCursor = cursors[tool] || 'default';
        this.canvas.hoverCursor = tool === 'select' ? 'move' : cursors[tool];
        this.canvas.isDrawingMode = (tool === 'freehand');
        if (tool === 'freehand') {
            this.canvas.freeDrawingBrush.color = this.currentColor;
            this.canvas.freeDrawingBrush.width = 3;
        }
        this.setStatus(`Tool: ${tool}`);
    }

    onMouseDown(e) {
        if (this.options.readonly || this.currentTool === 'select' || this.currentTool === 'freehand') return;
        const pointer = this.canvas.getPointer(e.e);
        this.isDrawing = true;
        this.startPoint = pointer;

        if (this.currentTool === 'text') { this.addTextLabel(pointer); this.isDrawing = false; }
        else if (this.currentTool === 'marker') { this.addMarker(pointer); this.isDrawing = false; }
    }

    onMouseMove(e) {
        if (!this.isDrawing || this.options.readonly) return;
        const pointer = this.canvas.getPointer(e.e);
        if (this.tempShape) this.canvas.remove(this.tempShape);
        this.tempShape = this.createShape(this.startPoint, pointer, true);
        if (this.tempShape) this.canvas.add(this.tempShape);
    }

    onMouseUp(e) {
        if (!this.isDrawing || this.options.readonly) return;
        const pointer = this.canvas.getPointer(e.e);
        this.isDrawing = false;
        if (this.tempShape) { this.canvas.remove(this.tempShape); this.tempShape = null; }

        const w = Math.abs(pointer.x - this.startPoint.x), h = Math.abs(pointer.y - this.startPoint.y);
        if (w < 5 && h < 5) return;

        const shape = this.createShape(this.startPoint, pointer, false);
        if (shape) {
            shape.annotationData = {
                id: 'ann_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                type: this.currentTool,
                category: this.currentCategory,
                label: this.categories[this.currentCategory]?.label || 'Note',
                color: this.currentColor,
                created_at: new Date().toISOString(),
            };
            this.canvas.add(shape);
            this.canvas.setActiveObject(shape);
            this.markDirty();
            this.updateLegend();
            
            setTimeout(() => {
                const notes = prompt(`Add notes for this ${shape.annotationData.label}:`);
                if (notes) {
                    shape.annotationData.notes = notes;
                    this.updateLegend();
                }
            }, 100);
        }
    }

    createShape(start, end, isTemp) {
        const opts = {
            stroke: this.currentColor,
            strokeWidth: isTemp ? 1 : 3,
            fill: isTemp ? 'rgba(255,0,0,0.1)' : 'transparent',
            strokeDashArray: isTemp ? [5, 5] : null,
            selectable: !isTemp && !this.options.readonly,
            evented: !isTemp && !this.options.readonly,
        };

        switch (this.currentTool) {
            case 'rect':
                return new fabric.Rect({
                    left: Math.min(start.x, end.x), top: Math.min(start.y, end.y),
                    width: Math.abs(end.x - start.x), height: Math.abs(end.y - start.y), ...opts
                });
            case 'circle':
                return new fabric.Ellipse({
                    left: Math.min(start.x, end.x), top: Math.min(start.y, end.y),
                    rx: Math.abs(end.x - start.x) / 2, ry: Math.abs(end.y - start.y) / 2, ...opts
                });
            case 'arrow':
                const angle = Math.atan2(end.y - start.y, end.x - start.x);
                const line = new fabric.Line([start.x, start.y, end.x, end.y], { ...opts, fill: null });
                const head = new fabric.Triangle({
                    left: end.x, top: end.y, width: 15, height: 15, fill: this.currentColor,
                    angle: (angle * 180 / Math.PI) + 90, originX: 'center', originY: 'center', selectable: false, evented: false,
                });
                return new fabric.Group([line, head], { selectable: !this.options.readonly, evented: !this.options.readonly });
            default: return null;
        }
    }

    addTextLabel(pointer) {
        const text = prompt('Enter annotation text:');
        if (!text) return;
        const label = new fabric.IText(text, {
            left: pointer.x, top: pointer.y, fontSize: 16, fill: this.currentColor,
            backgroundColor: 'rgba(255,255,255,0.9)', padding: 5, selectable: !this.options.readonly, evented: !this.options.readonly,
        });
        label.annotationData = { id: 'ann_' + Date.now(), type: 'text', category: this.currentCategory, label: text, color: this.currentColor, created_at: new Date().toISOString() };
        this.canvas.add(label);
        this.markDirty();
        this.updateLegend();
    }

    addMarker(pointer) {
        const notes = prompt('Enter marker note:');
        const idx = this.getAnnotationObjects().length + 1;
        const circle = new fabric.Circle({ radius: 14, fill: this.currentColor, originX: 'center', originY: 'center' });
        const number = new fabric.Text(idx.toString(), { fontSize: 14, fill: '#FFF', fontWeight: 'bold', originX: 'center', originY: 'center' });
        const marker = new fabric.Group([circle, number], { left: pointer.x, top: pointer.y, selectable: !this.options.readonly, evented: !this.options.readonly });
        marker.annotationData = { id: 'ann_' + Date.now(), type: 'marker', category: this.currentCategory, label: `Marker ${idx}`, notes: notes || '', color: this.currentColor, created_at: new Date().toISOString() };
        this.canvas.add(marker);
        this.markDirty();
        this.updateLegend();
    }

    toggleAnnotations() {
        this.annotationsVisible = !this.annotationsVisible;
        this.getAnnotationObjects().forEach(obj => { obj.visible = this.annotationsVisible; });
        this.canvas.renderAll();
        this.setStatus(this.annotationsVisible ? 'Annotations visible' : 'Annotations hidden');
    }

    deleteSelected() {
        const objs = this.canvas.getActiveObjects();
        if (!objs.length) return;
        objs.forEach(obj => { if (obj.annotationData) this.canvas.remove(obj); });
        this.canvas.discardActiveObject();
        this.markDirty();
        this.updateLegend();
    }

    undo() {
        const objs = this.getAnnotationObjects();
        if (objs.length) { 
            this.canvas.remove(objs[objs.length - 1]); 
            this.markDirty(); 
            this.updateLegend(); 
        }
    }

    zoom(factor) {
        const z = this.canvas.getZoom() * factor;
        if (z >= 0.1 && z <= 5) {
            this.canvas.setZoom(z);
            this.canvas.setWidth(this.originalImageSize.width * this.scale * z);
            this.canvas.setHeight(this.originalImageSize.height * this.scale * z);
            this.setStatus(`Zoom: ${Math.round(z * this.scale * 100)}%`);
        }
    }

    zoomFit() {
        if (!this.imageLoaded) return;
        this.canvas.setZoom(1);
        this.canvas.setWidth(this.originalImageSize.width * this.scale);
        this.canvas.setHeight(this.originalImageSize.height * this.scale);
        this.canvas.renderAll();
        this.setStatus(`Fit: ${Math.round(this.scale * 100)}%`);
    }

    getAnnotationObjects() { 
        return this.canvas.getObjects().filter(obj => obj.annotationData); 
    }

    markDirty() { this.isDirty = true; }
    markClean() { this.isDirty = false; }

    setStatus(text) {
        const el = this.container.querySelector('.status-text');
        if (el) el.textContent = text;
    }

    updateLegend() {
        const annotations = this.getAnnotationObjects();
        const count = annotations.length;
        
        const countEl = this.container.querySelector('.annotation-count');
        if (countEl) countEl.textContent = `${count} annotation${count !== 1 ? 's' : ''}`;
        
        const legendItems = this.container.querySelector('.legend-items');
        if (!legendItems) return;
        
        if (count === 0) {
            legendItems.innerHTML = `
                <div class="text-muted text-center py-4" style="font-size: 0.85em;">
                    <i class="fas fa-draw-polygon fa-2x mb-2 d-block opacity-50"></i>
                    No annotations yet.<br>
                    <small>Use tools above to annotate.</small>
                </div>
            `;
            return;
        }
        
        let html = '';
        annotations.forEach((obj, idx) => {
            const ann = obj.annotationData;
            const color = ann.color || obj.stroke || '#FF0000';
            const category = this.categories[ann.category] || { label: ann.category || 'Note' };
            const typeIcon = this.getTypeIcon(ann.type);
            
            html += `
                <div class="legend-item" data-id="${ann.id}" style="
                    padding: 10px 12px;
                    margin-bottom: 8px;
                    background: #fff;
                    border-radius: 6px;
                    border-left: 5px solid ${color};
                    cursor: pointer;
                    font-size: 0.85em;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    transition: all 0.2s ease;
                ">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <strong style="color: ${color};">
                            <span style="display: inline-block; width: 20px; height: 20px; line-height: 20px; text-align: center; background: ${color}; color: #fff; border-radius: 50%; font-size: 0.75em; margin-right: 6px;">${idx + 1}</span>
                            ${category.label}
                        </strong>
                        <span style="font-size: 0.9em; color: #666;">${typeIcon}</span>
                    </div>
                    ${ann.notes ? `<div style="margin: 6px 0; color: #444; padding-left: 26px; font-size: 0.95em;">"${this.escapeHtml(ann.notes)}"</div>` : ''}
                    <div style="color: #999; font-size: 0.75em; padding-left: 26px;">
                        <i class="far fa-clock me-1"></i>${ann.created_at ? new Date(ann.created_at).toLocaleTimeString() : 'Just now'}
                    </div>
                </div>
            `;
        });
        
        legendItems.innerHTML = html;
        
        legendItems.querySelectorAll('.legend-item').forEach(item => {
            item.addEventListener('click', () => {
                const id = item.dataset.id;
                const obj = annotations.find(a => a.annotationData.id === id);
                if (obj) {
                    this.canvas.setActiveObject(obj);
                    this.canvas.renderAll();
                }
            });
            
            item.addEventListener('mouseenter', () => {
                item.style.transform = 'translateX(3px)';
                item.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
            });
            item.addEventListener('mouseleave', () => {
                item.style.transform = '';
                item.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            });
        });
    }

    getTypeIcon(type) {
        const icons = {
            rect: '<i class="far fa-square"></i>',
            circle: '<i class="far fa-circle"></i>',
            arrow: '<i class="fas fa-long-arrow-alt-right"></i>',
            text: '<i class="fas fa-font"></i>',
            marker: '<i class="fas fa-map-marker-alt"></i>',
            freehand: '<i class="fas fa-pencil-alt"></i>',
        };
        return icons[type] || '<i class="fas fa-draw-polygon"></i>';
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    toJSON() {
        return this.getAnnotationObjects().map(obj => ({
            ...obj.annotationData,
            fabricData: {
                type: obj.type, 
                left: obj.left / this.scale, 
                top: obj.top / this.scale,
                width: (obj.width || 0) * (obj.scaleX || 1) / this.scale,
                height: (obj.height || 0) * (obj.scaleY || 1) / this.scale,
                stroke: obj.stroke, 
                strokeWidth: obj.strokeWidth, 
                fill: obj.fill,
                rx: obj.rx ? obj.rx / this.scale : undefined,
                ry: obj.ry ? obj.ry / this.scale : undefined,
                text: obj.text, 
                fontSize: obj.fontSize,
            }
        }));
    }

    fromJSON(annotations) {
        if (!Array.isArray(annotations)) return;

        annotations.forEach(ann => {
            const fd = ann.fabricData || ann;
            let obj = null;
            const opts = {
                left: (fd.left || 0) * this.scale,
                top: (fd.top || 0) * this.scale,
                stroke: fd.stroke || ann.color || '#FF0000',
                strokeWidth: fd.strokeWidth || 3,
                fill: fd.fill || 'transparent',
                selectable: !this.options.readonly,
                evented: !this.options.readonly,
            };

            if (fd.type === 'rect') {
                obj = new fabric.Rect({ ...opts, width: (fd.width || 50) * this.scale, height: (fd.height || 50) * this.scale });
            } else if (fd.type === 'ellipse' || fd.type === 'circle') {
                obj = new fabric.Ellipse({ ...opts, rx: (fd.rx || 25) * this.scale, ry: (fd.ry || 25) * this.scale });
            } else if (fd.type === 'i-text' || fd.type === 'text') {
                obj = new fabric.IText(fd.text || ann.label || 'Note', { ...opts, fontSize: fd.fontSize || 16, fill: fd.stroke || ann.color || '#FF0000', backgroundColor: 'rgba(255,255,255,0.9)' });
            } else if (fd.type === 'group' || fd.type === 'marker') {
                const c = new fabric.Circle({ radius: 14, fill: fd.stroke || ann.color || '#FF0000', originX: 'center', originY: 'center' });
                const n = new fabric.Text(ann.label && ann.label.replace ? ann.label.replace('Marker ', '') : '?', { fontSize: 14, fill: '#FFF', fontWeight: 'bold', originX: 'center', originY: 'center' });
                obj = new fabric.Group([c, n], { left: opts.left, top: opts.top, selectable: opts.selectable, evented: opts.evented });
            }

            if (obj) {
                obj.annotationData = { 
                    id: ann.id, 
                    type: ann.type || fd.type, 
                    category: ann.category || 'note', 
                    label: ann.label || 'Annotation', 
                    notes: ann.notes || '', 
                    color: ann.color || fd.stroke, 
                    created_at: ann.created_at 
                };
                this.canvas.add(obj);
            }
        });

        this.canvas.renderAll();
        this.updateLegend();
    }

    async loadAnnotations() {
        if (!this.options.photoId) return;

        const url = `${this.options.getUrl}?photo_id=${this.options.photoId}`;

        try {
            const res = await fetch(url);
            const data = await res.json();

            if (data.success && data.annotations && data.annotations.length > 0) {
                this.fromJSON(data.annotations);
                this.setStatus(`Loaded ${data.annotations.length} annotations`);
            } else {
                this.setStatus('Ready - no saved annotations');
            }
        } catch (e) {
            console.error('Load annotations failed:', e);
            this.setStatus('Ready');
        }
    }

    async save() {
        if (!this.options.photoId) {
            this.setStatus('Error: No photo ID');
            return Promise.reject('No photo ID');
        }

        this.setStatus('Saving...');
        const annotations = this.toJSON();

        try {
            const res = await fetch(this.options.saveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ photo_id: this.options.photoId, annotations: annotations }),
            });
            const data = await res.json();

            if (data.success) {
                this.markClean();
                this.setStatus(`Saved ${annotations.length} annotations`);
                return Promise.resolve();
            }
            throw new Error(data.error || 'Save failed');
        } catch (e) {
            this.setStatus('Save failed: ' + e.message);
            return Promise.reject(e);
        }
    }

    destroy() {
        if (this.canvas) this.canvas.dispose();
        this.container.innerHTML = '';
    }
}

if (typeof module !== 'undefined' && module.exports) module.exports = ConditionAnnotator;