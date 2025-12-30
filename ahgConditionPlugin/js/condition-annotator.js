/**
 * Condition Photo Annotator
 * Uses Fabric.js for canvas-based image annotation
 * Supports: rectangles, circles, arrows, freehand, text labels
 * Spectrum 5.0 compliant condition documentation
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
            onSave: null,
            onAnnotationAdded: null,
            onAnnotationRemoved: null,
        }, options);

        this.canvas = null;
        this.currentTool = 'select';
        this.currentColor = '#FF0000';
        this.currentCategory = 'damage';
        this.isDrawing = false;
        this.startPoint = null;
        this.annotations = [];
        this.annotationsVisible = true;
        this.imageLoaded = false;
        this.originalImageSize = { width: 0, height: 0 };
        this.scale = 1;

        // Damage categories with colors
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
            ai_detection: { color: '#FF1493', label: 'AI Detected' },
        };

        this.init();
    }

    init() {
        this.createUI();
        this.initCanvas();
        this.bindEvents();
        
        if (this.options.imageUrl) {
            this.loadImage(this.options.imageUrl);
        }
    }

    createUI() {
        this.container.innerHTML = `
            <div class="condition-annotator">
                ${this.options.showToolbar && !this.options.readonly ? this.createToolbar() : ''}
                <div class="annotator-canvas-container">
                    <canvas id="${this.containerId}-canvas"></canvas>
                </div>
                <div class="annotator-status-bar">
                    <span class="status-text">Ready</span>
                    <span class="annotation-count">0 annotations</span>
                </div>
            </div>
        `;
    }

    createToolbar() {
        return `
            <div class="annotator-toolbar">
                <div class="toolbar-group tools">
                    <button type="button" class="tool-btn active" data-tool="select" title="Select/Move">
                        <i class="fas fa-mouse-pointer"></i>
                    </button>
                    <button type="button" class="tool-btn" data-tool="rect" title="Rectangle">
                        <i class="far fa-square"></i>
                    </button>
                    <button type="button" class="tool-btn" data-tool="circle" title="Circle/Ellipse">
                        <i class="far fa-circle"></i>
                    </button>
                    <button type="button" class="tool-btn" data-tool="arrow" title="Arrow">
                        <i class="fas fa-long-arrow-alt-right"></i>
                    </button>
                    <button type="button" class="tool-btn" data-tool="freehand" title="Freehand Draw">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                    <button type="button" class="tool-btn" data-tool="text" title="Add Text Label">
                        <i class="fas fa-font"></i>
                    </button>
                    <button type="button" class="tool-btn" data-tool="marker" title="Point Marker">
                        <i class="fas fa-map-marker-alt"></i>
                    </button>
                </div>
                
                <div class="toolbar-group category">
                    <label>Category:</label>
                    <select id="${this.containerId}-category" class="form-select form-select-sm">
                        ${Object.entries(this.categories).map(([key, cat]) => 
                            `<option value="${key}" data-color="${cat.color}">${cat.label}</option>`
                        ).join('')}
                    </select>
                </div>
                
                <div class="toolbar-group color">
                    <label>Color:</label>
                    <input type="color" id="${this.containerId}-color" value="${this.currentColor}" class="form-control form-control-sm">
                </div>
                
                <div class="toolbar-group actions">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="${this.containerId}-toggle" title="Show/Hide Annotations">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="${this.containerId}-delete" title="Delete Selected">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="${this.containerId}-undo" title="Undo">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="${this.containerId}-save" title="Save Annotations">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
                
                <div class="toolbar-group zoom">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="${this.containerId}-zoom-in" title="Zoom In">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="${this.containerId}-zoom-out" title="Zoom Out">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="${this.containerId}-zoom-fit" title="Fit to View">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            </div>
        `;
    }

    initCanvas() {
        const canvasEl = document.getElementById(`${this.containerId}-canvas`);
        
        this.canvas = new fabric.Canvas(canvasEl, {
            selection: !this.options.readonly,
            preserveObjectStacking: true,
            renderOnAddRemove: true,
        });

        // Set initial size
        this.resizeCanvas();

        // Disable controls if readonly
        if (this.options.readonly) {
            this.canvas.selection = false;
            this.canvas.forEachObject(obj => {
                obj.selectable = false;
                obj.evented = false;
            });
        }
    }

    resizeCanvas() {
        const container = this.container.querySelector('.annotator-canvas-container');
        const width = container.clientWidth || 800;
        const height = container.clientHeight || 600;

        this.canvas.setWidth(width);
        this.canvas.setHeight(height);
        this.canvas.renderAll();
    }

    loadImage(url) {
        this.setStatus('Loading image...');
        
        fabric.Image.fromURL(url, (img) => {
            if (!img) {
                this.setStatus('Failed to load image');
                return;
            }

            this.originalImageSize = {
                width: img.width,
                height: img.height
            };

            // Calculate scale to fit
            const container = this.container.querySelector('.annotator-canvas-container');
            const maxWidth = container.clientWidth - 20;
            const maxHeight = container.clientHeight - 20 || 600;

            const scaleX = maxWidth / img.width;
            const scaleY = maxHeight / img.height;
            this.scale = Math.min(scaleX, scaleY, 1);

            // Set canvas size
            this.canvas.setWidth(img.width * this.scale);
            this.canvas.setHeight(img.height * this.scale);

            // Set background image
            this.canvas.setBackgroundImage(img, this.canvas.renderAll.bind(this.canvas), {
                scaleX: this.scale,
                scaleY: this.scale,
                originX: 'left',
                originY: 'top',
            });

            this.imageLoaded = true;
            this.setStatus('Image loaded');

            // Load existing annotations if any
            if (this.options.photoId) {
                this.loadAnnotations();
            }
        }, { crossOrigin: 'anonymous' });
    }

    bindEvents() {
        // Tool buttons
        this.container.querySelectorAll('.tool-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setTool(btn.dataset.tool);
                this.container.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });

        // Category select
        const categorySelect = document.getElementById(`${this.containerId}-category`);
        if (categorySelect) {
            categorySelect.addEventListener('change', (e) => {
                this.currentCategory = e.target.value;
                const option = e.target.options[e.target.selectedIndex];
                this.currentColor = option.dataset.color;
                document.getElementById(`${this.containerId}-color`).value = this.currentColor;
            });
        }

        // Color picker
        const colorPicker = document.getElementById(`${this.containerId}-color`);
        if (colorPicker) {
            colorPicker.addEventListener('change', (e) => {
                this.currentColor = e.target.value;
            });
        }

        // Action buttons
        this.bindButton('toggle', () => this.toggleAnnotations());
        this.bindButton('delete', () => this.deleteSelected());
        this.bindButton('undo', () => this.undo());
        this.bindButton('save', () => this.save());
        this.bindButton('zoom-in', () => this.zoom(1.2));
        this.bindButton('zoom-out', () => this.zoom(0.8));
        this.bindButton('zoom-fit', () => this.zoomFit());

        // Canvas events
        this.canvas.on('mouse:down', (e) => this.onMouseDown(e));
        this.canvas.on('mouse:move', (e) => this.onMouseMove(e));
        this.canvas.on('mouse:up', (e) => this.onMouseUp(e));
        this.canvas.on('object:modified', () => this.markDirty());
        this.canvas.on('selection:created', (e) => this.onSelectionCreated(e));

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            if (e.key === 'Delete' || e.key === 'Backspace') {
                this.deleteSelected();
            } else if (e.ctrlKey && e.key === 'z') {
                this.undo();
            } else if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                this.save();
            }
        });

        // Resize handler
        window.addEventListener('resize', () => {
            // Debounce resize
            clearTimeout(this.resizeTimeout);
            this.resizeTimeout = setTimeout(() => {
                if (this.imageLoaded) {
                    this.zoomFit();
                }
            }, 250);
        });
    }

    bindButton(id, handler) {
        const btn = document.getElementById(`${this.containerId}-${id}`);
        if (btn) {
            btn.addEventListener('click', handler);
        }
    }

    setTool(tool) {
        this.currentTool = tool;
        
        // Set cursor
        const cursors = {
            select: 'default',
            rect: 'crosshair',
            circle: 'crosshair',
            arrow: 'crosshair',
            freehand: 'crosshair',
            text: 'text',
            marker: 'crosshair',
        };
        
        this.canvas.defaultCursor = cursors[tool] || 'default';
        this.canvas.hoverCursor = cursors[tool] || 'move';

        // Enable/disable drawing mode
        this.canvas.isDrawingMode = (tool === 'freehand');
        
        if (tool === 'freehand') {
            this.canvas.freeDrawingBrush.color = this.currentColor;
            this.canvas.freeDrawingBrush.width = 2;
        }

        this.setStatus(`Tool: ${tool}`);
    }

    onMouseDown(e) {
        if (this.options.readonly || this.currentTool === 'select' || this.currentTool === 'freehand') {
            return;
        }

        const pointer = this.canvas.getPointer(e.e);
        this.isDrawing = true;
        this.startPoint = pointer;

        if (this.currentTool === 'text') {
            this.addTextLabel(pointer);
            this.isDrawing = false;
        } else if (this.currentTool === 'marker') {
            this.addMarker(pointer);
            this.isDrawing = false;
        }
    }

    onMouseMove(e) {
        if (!this.isDrawing || this.options.readonly) return;

        const pointer = this.canvas.getPointer(e.e);

        // Remove temp shape
        if (this.tempShape) {
            this.canvas.remove(this.tempShape);
        }

        // Create temp shape
        this.tempShape = this.createShape(this.startPoint, pointer, true);
        if (this.tempShape) {
            this.canvas.add(this.tempShape);
        }
    }

    onMouseUp(e) {
        if (!this.isDrawing || this.options.readonly) return;

        const pointer = this.canvas.getPointer(e.e);
        this.isDrawing = false;

        // Remove temp shape
        if (this.tempShape) {
            this.canvas.remove(this.tempShape);
            this.tempShape = null;
        }

        // Create final shape
        const shape = this.createShape(this.startPoint, pointer, false);
        if (shape) {
            // Store metadata
            shape.annotationData = {
                id: this.generateId(),
                type: this.currentTool,
                category: this.currentCategory,
                label: this.categories[this.currentCategory]?.label || 'Note',
                created_at: new Date().toISOString(),
            };

            this.canvas.add(shape);
            this.canvas.setActiveObject(shape);
            this.markDirty();
            this.updateAnnotationCount();

            // Prompt for notes
            this.promptForNotes(shape);

            if (this.options.onAnnotationAdded) {
                this.options.onAnnotationAdded(shape.annotationData);
            }
        }
    }

    createShape(start, end, isTemp) {
        const opts = {
            stroke: this.currentColor,
            strokeWidth: isTemp ? 1 : 2,
            fill: isTemp ? 'rgba(255,0,0,0.1)' : 'transparent',
            strokeDashArray: isTemp ? [5, 5] : null,
            selectable: !isTemp && !this.options.readonly,
            evented: !isTemp && !this.options.readonly,
        };

        const width = end.x - start.x;
        const height = end.y - start.y;

        switch (this.currentTool) {
            case 'rect':
                return new fabric.Rect({
                    left: Math.min(start.x, end.x),
                    top: Math.min(start.y, end.y),
                    width: Math.abs(width),
                    height: Math.abs(height),
                    ...opts
                });

            case 'circle':
                return new fabric.Ellipse({
                    left: Math.min(start.x, end.x),
                    top: Math.min(start.y, end.y),
                    rx: Math.abs(width) / 2,
                    ry: Math.abs(height) / 2,
                    ...opts
                });

            case 'arrow':
                return this.createArrow(start, end, opts);

            default:
                return null;
        }
    }

    createArrow(start, end, opts) {
        const angle = Math.atan2(end.y - start.y, end.x - start.x);
        const headLength = 15;

        const points = [
            start.x, start.y,
            end.x, end.y
        ];

        const line = new fabric.Line(points, {
            ...opts,
            fill: null,
        });

        // Arrow head
        const head = new fabric.Triangle({
            left: end.x,
            top: end.y,
            width: headLength,
            height: headLength,
            fill: this.currentColor,
            angle: (angle * 180 / Math.PI) + 90,
            originX: 'center',
            originY: 'center',
            selectable: false,
            evented: false,
        });

        // Group line and head
        const group = new fabric.Group([line, head], {
            selectable: !this.options.readonly,
            evented: !this.options.readonly,
        });

        return group;
    }

    addTextLabel(pointer) {
        const text = prompt('Enter annotation text:');
        if (!text) return;

        const label = new fabric.IText(text, {
            left: pointer.x,
            top: pointer.y,
            fontSize: 14,
            fill: this.currentColor,
            backgroundColor: 'rgba(255,255,255,0.8)',
            padding: 5,
            selectable: !this.options.readonly,
            evented: !this.options.readonly,
        });

        label.annotationData = {
            id: this.generateId(),
            type: 'text',
            category: this.currentCategory,
            label: text,
            created_at: new Date().toISOString(),
        };

        this.canvas.add(label);
        this.markDirty();
        this.updateAnnotationCount();
    }

    addMarker(pointer) {
        const notes = prompt('Enter marker note:');
        
        // Create marker (circle with number)
        const markerIndex = this.getAnnotationObjects().length + 1;
        
        const circle = new fabric.Circle({
            radius: 12,
            fill: this.currentColor,
            originX: 'center',
            originY: 'center',
        });

        const number = new fabric.Text(markerIndex.toString(), {
            fontSize: 12,
            fill: '#FFFFFF',
            fontWeight: 'bold',
            originX: 'center',
            originY: 'center',
        });

        const marker = new fabric.Group([circle, number], {
            left: pointer.x,
            top: pointer.y,
            selectable: !this.options.readonly,
            evented: !this.options.readonly,
        });

        marker.annotationData = {
            id: this.generateId(),
            type: 'marker',
            category: this.currentCategory,
            label: `Marker ${markerIndex}`,
            notes: notes || '',
            created_at: new Date().toISOString(),
        };

        this.canvas.add(marker);
        this.markDirty();
        this.updateAnnotationCount();
    }

    promptForNotes(shape) {
        // Simple prompt for now - could be a modal
        setTimeout(() => {
            const notes = prompt(`Add notes for this ${shape.annotationData.label}:`);
            if (notes) {
                shape.annotationData.notes = notes;
            }
        }, 100);
    }

    toggleAnnotations() {
        this.annotationsVisible = !this.annotationsVisible;
        
        this.getAnnotationObjects().forEach(obj => {
            obj.visible = this.annotationsVisible;
        });
        
        this.canvas.renderAll();

        const toggleBtn = document.getElementById(`${this.containerId}-toggle`);
        if (toggleBtn) {
            toggleBtn.innerHTML = this.annotationsVisible 
                ? '<i class="fas fa-eye"></i>' 
                : '<i class="fas fa-eye-slash"></i>';
        }

        this.setStatus(this.annotationsVisible ? 'Annotations visible' : 'Annotations hidden');
    }

    deleteSelected() {
        const activeObjects = this.canvas.getActiveObjects();
        if (activeObjects.length === 0) {
            this.setStatus('Nothing selected');
            return;
        }

        activeObjects.forEach(obj => {
            if (obj.annotationData) {
                this.canvas.remove(obj);
                if (this.options.onAnnotationRemoved) {
                    this.options.onAnnotationRemoved(obj.annotationData);
                }
            }
        });

        this.canvas.discardActiveObject();
        this.markDirty();
        this.updateAnnotationCount();
        this.setStatus(`Deleted ${activeObjects.length} annotation(s)`);
    }

    undo() {
        // Simple undo - remove last added object
        const objects = this.getAnnotationObjects();
        if (objects.length > 0) {
            const lastObj = objects[objects.length - 1];
            this.canvas.remove(lastObj);
            this.markDirty();
            this.updateAnnotationCount();
            this.setStatus('Undone');
        }
    }

    zoom(factor) {
        const currentZoom = this.canvas.getZoom();
        const newZoom = currentZoom * factor;
        
        if (newZoom >= 0.25 && newZoom <= 4) {
            this.canvas.setZoom(newZoom);
            this.setStatus(`Zoom: ${Math.round(newZoom * 100)}%`);
        }
    }

    zoomFit() {
        if (!this.imageLoaded) return;

        const container = this.container.querySelector('.annotator-canvas-container');
        const maxWidth = container.clientWidth - 20;
        const maxHeight = 600;

        const scaleX = maxWidth / this.originalImageSize.width;
        const scaleY = maxHeight / this.originalImageSize.height;
        const scale = Math.min(scaleX, scaleY, 1);

        this.canvas.setZoom(1);
        this.canvas.setWidth(this.originalImageSize.width * scale);
        this.canvas.setHeight(this.originalImageSize.height * scale);

        // Update background image scale
        const bgImage = this.canvas.backgroundImage;
        if (bgImage) {
            bgImage.scaleX = scale;
            bgImage.scaleY = scale;
        }

        this.scale = scale;
        this.canvas.renderAll();
        this.setStatus('Fit to view');
    }

    getAnnotationObjects() {
        return this.canvas.getObjects().filter(obj => obj.annotationData);
    }

    generateId() {
        return 'ann_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    markDirty() {
        this.isDirty = true;
        const saveBtn = document.getElementById(`${this.containerId}-save`);
        if (saveBtn) {
            saveBtn.classList.add('btn-warning');
            saveBtn.classList.remove('btn-primary');
        }
    }

    markClean() {
        this.isDirty = false;
        const saveBtn = document.getElementById(`${this.containerId}-save`);
        if (saveBtn) {
            saveBtn.classList.remove('btn-warning');
            saveBtn.classList.add('btn-primary');
        }
    }

    setStatus(text) {
        const statusEl = this.container.querySelector('.status-text');
        if (statusEl) {
            statusEl.textContent = text;
        }
    }

    updateAnnotationCount() {
        const count = this.getAnnotationObjects().length;
        const countEl = this.container.querySelector('.annotation-count');
        if (countEl) {
            countEl.textContent = `${count} annotation${count !== 1 ? 's' : ''}`;
        }
    }

    onSelectionCreated(e) {
        const obj = e.selected?.[0];
        if (obj && obj.annotationData) {
            this.setStatus(`Selected: ${obj.annotationData.label}`);
        }
    }

    // Serialization methods
    toJSON() {
        const annotations = [];
        
        this.getAnnotationObjects().forEach(obj => {
            const data = {
                ...obj.annotationData,
                // Fabric.js object properties (scaled back to original image size)
                fabricData: {
                    type: obj.type,
                    left: obj.left / this.scale,
                    top: obj.top / this.scale,
                    width: (obj.width || 0) / this.scale,
                    height: (obj.height || 0) / this.scale,
                    scaleX: obj.scaleX,
                    scaleY: obj.scaleY,
                    angle: obj.angle,
                    stroke: obj.stroke,
                    strokeWidth: obj.strokeWidth,
                    fill: obj.fill,
                }
            };
            
            // Handle specific types
            if (obj.type === 'ellipse') {
                data.fabricData.rx = obj.rx / this.scale;
                data.fabricData.ry = obj.ry / this.scale;
            } else if (obj.type === 'i-text' || obj.type === 'text') {
                data.fabricData.text = obj.text;
                data.fabricData.fontSize = obj.fontSize;
            }
            
            annotations.push(data);
        });

        return annotations;
    }

    fromJSON(annotations) {
        if (!Array.isArray(annotations)) return;

        annotations.forEach(ann => {
            const fd = ann.fabricData || ann;
            let obj = null;

            const baseOpts = {
                left: (fd.left || 0) * this.scale,
                top: (fd.top || 0) * this.scale,
                stroke: fd.stroke || ann.stroke || '#FF0000',
                strokeWidth: fd.strokeWidth || 2,
                fill: fd.fill || 'transparent',
                selectable: !this.options.readonly,
                evented: !this.options.readonly,
            };

            switch (fd.type || ann.shape || ann.type) {
                case 'rect':
                    obj = new fabric.Rect({
                        ...baseOpts,
                        width: (fd.width || 50) * this.scale,
                        height: (fd.height || 50) * this.scale,
                    });
                    break;

                case 'ellipse':
                case 'circle':
                    obj = new fabric.Ellipse({
                        ...baseOpts,
                        rx: (fd.rx || 25) * this.scale,
                        ry: (fd.ry || 25) * this.scale,
                    });
                    break;

                case 'i-text':
                case 'text':
                    obj = new fabric.IText(fd.text || ann.label || 'Note', {
                        ...baseOpts,
                        fontSize: fd.fontSize || 14,
                        fill: fd.stroke || '#FF0000',
                        backgroundColor: 'rgba(255,255,255,0.8)',
                    });
                    break;

                case 'group':
                case 'marker':
                    // Recreate marker
                    const circle = new fabric.Circle({
                        radius: 12,
                        fill: fd.stroke || '#FF0000',
                        originX: 'center',
                        originY: 'center',
                    });
                    const number = new fabric.Text(ann.label?.replace('Marker ', '') || '?', {
                        fontSize: 12,
                        fill: '#FFFFFF',
                        fontWeight: 'bold',
                        originX: 'center',
                        originY: 'center',
                    });
                    obj = new fabric.Group([circle, number], {
                        left: baseOpts.left,
                        top: baseOpts.top,
                        selectable: baseOpts.selectable,
                        evented: baseOpts.evented,
                    });
                    break;
            }

            if (obj) {
                obj.annotationData = {
                    id: ann.id || this.generateId(),
                    type: ann.type || fd.type,
                    category: ann.category || 'note',
                    label: ann.label || 'Annotation',
                    notes: ann.notes || '',
                    created_at: ann.created_at,
                    ai_generated: ann.ai_generated || false,
                };
                
                this.canvas.add(obj);
            }
        });

        this.canvas.renderAll();
        this.updateAnnotationCount();
    }

    // API methods
    async loadAnnotations() {
        if (!this.options.photoId) return;

        try {
            const response = await fetch(`/condition/annotation/get?photo_id=${this.options.photoId}`);
            const data = await response.json();
            
            if (data.success && data.annotations) {
                this.fromJSON(data.annotations);
                this.setStatus(`Loaded ${data.annotations.length} annotations`);
            }
        } catch (error) {
            console.error('Failed to load annotations:', error);
            this.setStatus('Failed to load annotations');
        }
    }

    async save() {
        if (!this.options.photoId) {
            this.setStatus('No photo ID specified');
            return;
        }

        this.setStatus('Saving...');

        try {
            const annotations = this.toJSON();
            
            const response = await fetch('/condition/annotation/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    photo_id: this.options.photoId,
                    annotations: annotations,
                }),
            });

            const data = await response.json();
            
            if (data.success) {
                this.markClean();
                this.setStatus('Saved successfully');
                
                if (this.options.onSave) {
                    this.options.onSave(annotations);
                }
            } else {
                throw new Error(data.error || 'Save failed');
            }
        } catch (error) {
            console.error('Failed to save:', error);
            this.setStatus('Failed to save: ' + error.message);
        }
    }

    // Public API
    addAiDetection(detection) {
        // Add AI-detected annotation
        const baseOpts = {
            left: detection.bbox.x * this.scale,
            top: detection.bbox.y * this.scale,
            width: detection.bbox.width * this.scale,
            height: detection.bbox.height * this.scale,
            stroke: this.categories[detection.category]?.color || '#FF1493',
            strokeWidth: 2,
            strokeDashArray: [5, 5],
            fill: 'rgba(255, 20, 147, 0.1)',
            selectable: !this.options.readonly,
            evented: !this.options.readonly,
        };

        const rect = new fabric.Rect(baseOpts);
        rect.annotationData = {
            id: this.generateId(),
            type: 'ai_detection',
            category: detection.category || 'anomaly',
            label: detection.label || 'AI Detection',
            confidence: detection.confidence || 0,
            notes: `Confidence: ${Math.round((detection.confidence || 0) * 100)}%`,
            ai_generated: true,
            created_at: new Date().toISOString(),
        };

        this.canvas.add(rect);
        this.markDirty();
        this.updateAnnotationCount();
    }

    destroy() {
        if (this.canvas) {
            this.canvas.dispose();
        }
        this.container.innerHTML = '';
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ConditionAnnotator;
}
