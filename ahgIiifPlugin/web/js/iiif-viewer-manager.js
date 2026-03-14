/**
 * IIIF Viewer Manager
 *
 * Manages multiple viewer types and switches between them:
 * - OpenSeadragon for IIIF images
 * - Mirador 3 for rich IIIF viewing
 * - PDF.js for PDF documents
 * - Model Viewer for 3D models
 * - Annotorious for annotations
 *
 * Supports both IIIF Presentation API 2.1 and 3.0 manifests.
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 * @version 1.1.0
 */

export class IiifViewerManager {
    constructor(viewerId, options = {}) {
        this.viewerId = viewerId;
        this.options = {
            objectId: null,
            manifestUrl: null,
            baseUrl: (typeof window !== 'undefined' ? window.location.origin : ''),
            cantaloupeUrl: (typeof window !== 'undefined' ? window.location.origin + '/iiif/2' : '/iiif/2'),
            pluginPath: '/plugins/ahgIiifPlugin',
            defaultViewer: 'openseadragon',
            flags: {},
            osdConfig: {},
            miradorConfig: {},
            embedded: false,
            ...options
        };

        this.currentViewer = null;
        this.osdViewer = null;
        this.miradorInstance = null;
        this.pdfDoc = null;
        this.pdfPage = 1;
        this.pdfScale = 1.5;
        this.annotorious = null;
        this.annotations = [];

        this.loaded = {
            osd: false,
            mirador: false,
            pdfjs: false,
            modelViewer: false,
            annotorious: false
        };
    }

    async init() {
        // Content-specific viewers should not be overridden by localStorage
        const contentViewers = ['pdfjs', 'av', 'model-viewer'];
        const isContentSpecific = contentViewers.includes(this.options.defaultViewer);

        // Use content-specific viewer if set, otherwise check localStorage
        const savedViewer = localStorage.getItem('iiif_viewer_pref');

        // Only use saved viewer if it's a valid image viewer type
        const validImageViewers = ['openseadragon', 'mirador'];
        const effectiveSavedViewer = validImageViewers.includes(savedViewer) ? savedViewer : null;

        this.currentViewer = isContentSpecific
            ? this.options.defaultViewer
            : (effectiveSavedViewer || this.options.defaultViewer);

        // Bind events
        this.bindEvents();

        // Load initial viewer with fallback
        try {
            await this.showViewer(this.currentViewer);
        } catch (error) {
            console.error('Initial viewer failed, falling back to openseadragon:', error);
            // Reset localStorage preference on failure
            localStorage.removeItem('iiif_viewer_pref');
            if (this.currentViewer !== 'openseadragon') {
                await this.showViewer('openseadragon');
            }
        }

        // Load annotations if enabled
        if (this.options.flags.enableAnnotations) {
            await this.loadAnnotations();
        }

        // Store instance in registry keyed by container ID
        if (typeof window !== 'undefined') {
            window.iiifViewerInstances = window.iiifViewerInstances || {};
            window.iiifViewerInstances[this.viewerId] = this;
            // Keep backward-compatible global reference (last initialized)
            window.iiifViewerManager = this;
        }
    }

    bindEvents() {
        const vid = this.viewerId;

        // Viewer toggle buttons
        this.on(`btn-osd-${vid}`, 'click', () => this.showViewer('openseadragon'));
        this.on(`btn-mirador-${vid}`, 'click', () => this.showViewer('mirador'));
        this.on(`btn-pdf-${vid}`, 'click', () => this.showViewer('pdfjs'));
        this.on(`btn-3d-${vid}`, 'click', () => this.showViewer('model-viewer'));
        this.on(`btn-av-${vid}`, 'click', () => this.showViewer('av'));

        // Close mirador
        this.on(`close-mirador-${vid}`, 'click', () => this.showViewer('openseadragon'));

        // Control buttons
        this.on(`btn-fullscreen-${vid}`, 'click', () => this.toggleFullscreen());
        this.on(`btn-newwin-${vid}`, 'click', () => this.openInNewWindow());
        this.on(`btn-download-${vid}`, 'click', () => this.downloadImage());
        this.on(`btn-annotations-${vid}`, 'click', () => this.toggleAnnotations());
        this.on(`btn-manifest-${vid}`, 'click', () => this.copyManifestUrl());

        // PDF controls
        this.on(`pdf-prev-${vid}`, 'click', () => this.pdfPrevPage());
        this.on(`pdf-next-${vid}`, 'click', () => this.pdfNextPage());
        this.on(`pdf-zoom-in-${vid}`, 'click', () => this.pdfZoom(0.25));
        this.on(`pdf-zoom-out-${vid}`, 'click', () => this.pdfZoom(-0.25));

        // Thumbnail strip
        const thumbs = document.querySelectorAll(`#thumbs-${vid} .thumb-item`);
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', () => {
                const index = parseInt(thumb.dataset.index);
                this.goToPage(index);

                // Update active state
                thumbs.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            });
        });
    }

    on(elementId, event, handler) {
        const el = document.getElementById(elementId);
        if (el) {
            el.addEventListener(event, handler);
        } else if (elementId.startsWith('btn-')) {
            console.debug(`IiifViewerManager: DOM element #${elementId} not found (button may not be rendered)`);
        }
    }

    // ========================================================================
    // Viewer Switching
    // ========================================================================

    async showViewer(viewerType) {
        const vid = this.viewerId;

        // Hide all viewers
        this.hideElement(`osd-${vid}`);
        this.hideElement(`mirador-wrapper-${vid}`);
        this.hideElement(`pdf-wrapper-${vid}`);
        this.hideElement(`model-wrapper-${vid}`);
        this.hideElement(`av-wrapper-${vid}`);

        // Update button states
        this.updateButtonStates(viewerType);

        // Show selected viewer
        switch (viewerType) {
            case 'openseadragon':
                await this.initOpenSeadragon();
                this.showElement(`osd-${vid}`);
                break;

            case 'mirador':
                await this.initMirador();
                this.showElement(`mirador-wrapper-${vid}`);
                break;

            case 'pdfjs':
                await this.initPdfJs();
                this.showElement(`pdf-wrapper-${vid}`);
                break;

            case 'model-viewer':
                await this.initModelViewer();
                this.showElement(`model-wrapper-${vid}`);
                break;

            case 'av':
                this.showElement(`av-wrapper-${vid}`);
                break;
        }

        this.currentViewer = viewerType;
        localStorage.setItem('iiif_viewer_pref', viewerType);

        // Re-init annotorious for OSD
        if (viewerType === 'openseadragon' && this.options.flags.enableAnnotations) {
            await this.initAnnotorious();
        }
    }

    updateButtonStates(activeViewer) {
        const vid = this.viewerId;
        const buttons = {
            'openseadragon': `btn-osd-${vid}`,
            'mirador': `btn-mirador-${vid}`,
            'pdfjs': `btn-pdf-${vid}`,
            'model-viewer': `btn-3d-${vid}`,
            'av': `btn-av-${vid}`
        };

        Object.entries(buttons).forEach(([viewer, btnId]) => {
            const btn = document.getElementById(btnId);
            if (btn) {
                if (viewer === activeViewer) {
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-primary');
                } else {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                }
            }
        });
    }

    // ========================================================================
    // OpenSeadragon
    // ========================================================================

    async initOpenSeadragon() {
        if (this.osdViewer) return;

        const vid = this.viewerId;
        const containerId = `osd-${vid}`;

        try {
            // Load OSD if not loaded
            if (!window.OpenSeadragon) {
                await this.loadScript(this.options.pluginPath + '/js/vendor/openseadragon.min.js');
            }

            // Verify OpenSeadragon loaded
            if (!window.OpenSeadragon) {
                console.error('OpenSeadragon failed to load');
                this.showError(containerId, 'Failed to load image viewer');
                return;
            }

            // Fetch manifest to get tile sources
            const manifest = await this.fetchManifest();

            if (!manifest) {
                console.error('Failed to fetch manifest from:', this.options.manifestUrl);
                this.showError(containerId, 'Failed to load image manifest');
                return;
            }

            const tileSources = this.extractTileSources(manifest);

            if (tileSources.length === 0) {
                console.error('No tile sources found in manifest:', manifest);
                this.showError(containerId, 'No images found in manifest');
                return;
            }

            // Filter out any undefined or invalid tile sources
            const validTileSources = tileSources.filter(ts => ts && typeof ts === 'string' && ts.startsWith('http'));

            if (validTileSources.length === 0) {
                console.error('No valid tile sources after filtering:', tileSources);
                this.showError(containerId, 'Invalid image sources');
                return;
            }

            console.log('IIIF tile sources:', validTileSources);

            const config = {
                id: containerId,
                prefixUrl: '/plugins/ahgIiifPlugin/web/images/',
                tileSources: validTileSources,
                showNavigator: true,
                navigatorPosition: 'BOTTOM_RIGHT',
                showRotationControl: true,
                showFlipControl: true,
                gestureSettingsMouse: { scrollToZoom: true },
                crossOriginPolicy: 'Anonymous',
                ajaxWithCredentials: this._authToken ? true : false,
                ...(this._authToken ? { ajaxHeaders: { 'Authorization': 'Bearer ' + this._authToken } } : {}),
                ...this.options.osdConfig
            };

            // Multi-image mode
            if (validTileSources.length > 1) {
                config.sequenceMode = true;
                config.showReferenceStrip = true;
                config.referenceStripScroll = 'horizontal';
            }

            this.osdViewer = OpenSeadragon(config);

            // Add error handler for debugging
            this.osdViewer.addHandler('open-failed', (event) => {
                console.error('OpenSeadragon open-failed:', {
                    message: event.message,
                    source: event.source,
                    eventSource: event.eventSource ? 'present' : 'none'
                });
            });

            this.osdViewer.addHandler('tile-load-failed', (event) => {
                console.error('OpenSeadragon tile-load-failed:', {
                    message: event.message,
                    tile: event.tile ? event.tile.url : 'unknown'
                });
            });

            // Sync Annotorious overlay with OSD rotation to prevent upside-down annotations
            this.osdViewer.addHandler('rotate', (event) => {
                const overlay = document.querySelector(`#osd-${vid} .a9s-annotationlayer`);
                if (overlay) {
                    overlay.style.transform = `rotate(${event.degrees}deg)`;
                }
            });

            this.loaded.osd = true;
        } catch (error) {
            console.error('OpenSeadragon initialization failed:', error);
            this.showError(containerId, 'Failed to initialize image viewer');
        }
    }

    /**
     * Show error message in viewer container
     */
    showError(containerId, message) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `
                <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#fff;text-align:center;padding:20px;">
                    <div>
                        <i class="fas fa-exclamation-triangle" style="font-size:48px;color:#ffc107;margin-bottom:16px;"></i>
                        <p style="margin:0;font-size:16px;">${message}</p>
                        <p style="margin:8px 0 0;font-size:14px;opacity:0.7;">Check browser console for details</p>
                    </div>
                </div>
            `;
        }
    }

    /**
     * Extract tile sources from IIIF manifest
     * Supports both IIIF Presentation API 2.1 and 3.0
     */
    extractTileSources(manifest) {
        const tileSources = [];

        if (!manifest) return tileSources;

        // IIIF Presentation API 3.0
        if (manifest.items && Array.isArray(manifest.items)) {
            manifest.items.forEach(canvas => {
                if (canvas.items && canvas.items[0] && canvas.items[0].items) {
                    canvas.items[0].items.forEach(annotation => {
                        if (annotation.body && annotation.body.service) {
                            const service = Array.isArray(annotation.body.service)
                                ? annotation.body.service[0]
                                : annotation.body.service;

                            if (service && service.id) {
                                // Pass just the URL - OpenSeadragon will auto-detect IIIF
                                tileSources.push(service.id + '/info.json');
                            }
                        }
                    });
                }
            });
        }

        // IIIF Presentation API 2.1
        if (manifest.sequences && Array.isArray(manifest.sequences)) {
            manifest.sequences.forEach(sequence => {
                if (sequence.canvases && Array.isArray(sequence.canvases)) {
                    sequence.canvases.forEach(canvas => {
                        if (canvas.images && Array.isArray(canvas.images)) {
                            canvas.images.forEach(image => {
                                if (image.resource && image.resource.service) {
                                    const service = image.resource.service;
                                    const serviceId = service['@id'] || service.id;

                                    if (serviceId) {
                                        // Pass just the URL - OpenSeadragon will auto-detect IIIF
                                        tileSources.push(serviceId + '/info.json');
                                    }
                                }
                            });
                        }
                    });
                }
            });
        }

        return tileSources;
    }

    // ========================================================================
    // Mirador 3
    // ========================================================================

    async initMirador() {
        if (this.miradorInstance) return;

        const vid = this.viewerId;
        const path = this.options.pluginPath;

        try {
            // Load Mirador CSS (styles are bundled in JS, this is just for any overrides)
            if (!document.getElementById('mirador-css')) {
                const link = document.createElement('link');
                link.id = 'mirador-css';
                link.rel = 'stylesheet';
                link.href = `${path}/public/mirador/mirador.min.css`;
                document.head.appendChild(link);
            }

            // Load Mirador JS
            if (!window.Mirador) {
                await this.loadScript(`${path}/public/mirador/mirador.min.js`);
            }

            // Verify Mirador loaded
            if (!window.Mirador || !window.Mirador.viewer) {
                console.error('Mirador failed to load');
                // Fall back to OpenSeadragon
                await this.showViewer('openseadragon');
                return;
            }

            // Pre-fetch manifest to validate it (supports both v2.1 and v3.0)
            const manifest = await this.fetchManifest();
            const hasV2 = manifest?.sequences?.[0]?.canvases?.length > 0;
            const hasV3 = manifest?.items?.length > 0;
            if (!manifest || (!hasV2 && !hasV3)) {
                console.error('Invalid manifest for Mirador (no canvases in v2.1 or v3.0):', manifest);
                await this.showViewer('openseadragon');
                return;
            }

            console.log('Initializing Mirador with manifest:', this.options.manifestUrl);

            this.miradorInstance = Mirador.viewer({
                id: `mirador-${vid}`,
                windows: [{ manifestId: this.options.manifestUrl }],
                window: {
                    allowClose: false,
                    allowMaximize: true,
                    defaultSideBarPanel: 'info',
                    sideBarOpenByDefault: false,
                    panels: {
                        info: true,
                        attribution: true,
                        canvas: true,
                        annotations: true,
                        search: true
                    }
                },
                workspace: {
                    showZoomControls: true
                },
                osdConfig: {
                    crossOriginPolicy: 'Anonymous',
                    ajaxWithCredentials: false
                },
                ...this.options.miradorConfig
            });

            this.loaded.mirador = true;
        } catch (error) {
            console.error('Mirador initialization failed:', error);
            // Fall back to OpenSeadragon on error
            await this.showViewer('openseadragon');
        }
    }

    // ========================================================================
    // PDF.js
    // ========================================================================

    async initPdfJs() {
        if (this.pdfDoc) return;

        // Load PDF.js
        if (!window.pdfjsLib) {
            await this.loadScript(this.options.pluginPath + '/js/vendor/pdf.min.js');
            pdfjsLib.GlobalWorkerOptions.workerSrc = this.options.pluginPath + '/js/vendor/pdf.worker.min.js';
        }

        // Use direct PDF URL if provided, otherwise get from manifest
        let pdfUrl = this.options.flags.pdfUrl || null;

        if (!pdfUrl) {
            const manifest = await this.fetchManifest();

            if (manifest && manifest.items) {
                for (const canvas of manifest.items) {
                    if (canvas.rendering) {
                        const rendering = Array.isArray(canvas.rendering) ? canvas.rendering[0] : canvas.rendering;
                        if (rendering.format === 'application/pdf') {
                            pdfUrl = rendering.id;
                            break;
                        }
                    }
                }
            }
        }

        if (!pdfUrl) return;

        const loadingTask = pdfjsLib.getDocument(pdfUrl);
        this.pdfDoc = await loadingTask.promise;

        this.pdfPage = 1;
        await this.renderPdfPage();

        this.loaded.pdfjs = true;
    }

    async renderPdfPage() {
        if (!this.pdfDoc) return;

        const vid = this.viewerId;
        const page = await this.pdfDoc.getPage(this.pdfPage);
        const viewport = page.getViewport({ scale: this.pdfScale });

        const canvas = document.getElementById(`pdf-canvas-${vid}`);
        const context = canvas.getContext('2d');

        canvas.height = viewport.height;
        canvas.width = viewport.width;

        await page.render({
            canvasContext: context,
            viewport: viewport
        }).promise;

        // Update page display
        const pageDisplay = document.getElementById(`pdf-page-${vid}`);
        if (pageDisplay) {
            pageDisplay.textContent = `${this.pdfPage} / ${this.pdfDoc.numPages}`;
        }
    }

    pdfPrevPage() {
        if (this.pdfPage > 1) {
            this.pdfPage--;
            this.renderPdfPage();
        }
    }

    pdfNextPage() {
        if (this.pdfDoc && this.pdfPage < this.pdfDoc.numPages) {
            this.pdfPage++;
            this.renderPdfPage();
        }
    }

    pdfZoom(delta) {
        this.pdfScale = Math.max(0.5, Math.min(3, this.pdfScale + delta));
        this.renderPdfPage();
    }

    // ========================================================================
    // Model Viewer (3D)
    // ========================================================================

    async initModelViewer() {
        // Load model-viewer if not loaded
        if (!customElements.get('model-viewer')) {
            await this.loadScript(this.options.pluginPath + '/js/vendor/model-viewer.min.js', 'module');
        }

        this.loaded.modelViewer = true;
    }

    // ========================================================================
    // Annotorious
    // ========================================================================

    async initAnnotorious() {
        if (!this.osdViewer || this.annotorious) return;

        const path = this.options.pluginPath;

        // Load Annotorious CSS
        if (!document.getElementById('annotorious-css')) {
            const link = document.createElement('link');
            link.id = 'annotorious-css';
            link.rel = 'stylesheet';
            link.href = `${path}/public/viewers/annotorious/annotorious.min.css`;
            document.head.appendChild(link);
        }

        // Load Annotorious
        if (!window.Annotorious) {
            await this.loadScript(`${path}/public/viewers/annotorious/openseadragon-annotorious.min.js`);
        }

        // Initialize
        this.annotorious = OpenSeadragon.Annotorious(this.osdViewer, {
            locale: 'auto',
            allowEmpty: true,
            widgets: ['COMMENT', 'TAG']
        });

        // Load existing annotations
        if (this.annotations.length > 0) {
            this.annotorious.setAnnotations(this.annotations);
        }

        // Bind events
        this.annotorious.on('createAnnotation', async (annotation) => {
            await this.saveAnnotation(annotation);
        });

        this.annotorious.on('updateAnnotation', async (annotation, previous) => {
            await this.updateAnnotation(annotation);
        });

        this.annotorious.on('deleteAnnotation', async (annotation) => {
            await this.deleteAnnotation(annotation);
        });

        this.loaded.annotorious = true;
    }

    async loadAnnotations() {
        try {
            const response = await fetch(
                `${this.options.baseUrl}/iiif/annotations/object/${this.options.objectId}`
            );

            if (response.ok) {
                const data = await response.json();
                this.annotations = data.items || [];

                if (this.annotorious && this.annotations.length > 0) {
                    this.annotorious.setAnnotations(this.annotations);
                }
            }
        } catch (error) {
            console.error('Failed to load annotations:', error);
        }
    }

    async saveAnnotation(annotation) {
        try {
            const response = await fetch(`${this.options.baseUrl}/iiif/annotations`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ...annotation,
                    object_id: this.options.objectId
                })
            });

            if (response.ok) {
                const saved = await response.json();
                annotation.id = saved.id;
            }
        } catch (error) {
            console.error('Failed to save annotation:', error);
        }
    }

    async updateAnnotation(annotation) {
        const id = annotation.id.replace('#', '');

        try {
            await fetch(`${this.options.baseUrl}/iiif/annotations/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(annotation)
            });
        } catch (error) {
            console.error('Failed to update annotation:', error);
        }
    }

    async deleteAnnotation(annotation) {
        const id = annotation.id.replace('#', '');

        try {
            await fetch(`${this.options.baseUrl}/iiif/annotations/${id}`, {
                method: 'DELETE'
            });
        } catch (error) {
            console.error('Failed to delete annotation:', error);
        }
    }

    toggleAnnotations() {
        if (this.annotorious) {
            const readOnly = this.annotorious.readOnly;
            this.annotorious.setVisible(!readOnly);
        }
    }

    // ========================================================================
    // Controls
    // ========================================================================

    toggleFullscreen() {
        const vid = this.viewerId;
        let element;

        switch (this.currentViewer) {
            case 'mirador':
                element = document.getElementById(`mirador-wrapper-${vid}`);
                break;
            case 'pdfjs':
                element = document.getElementById(`pdf-wrapper-${vid}`);
                break;
            case 'model-viewer':
                element = document.getElementById(`model-wrapper-${vid}`);
                break;
            default:
                element = document.getElementById(`osd-${vid}`);
        }

        if (!document.fullscreenElement) {
            element?.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    }

    openInNewWindow() {
        const path = this.options.pluginPath;
        const manifest = encodeURIComponent(this.options.manifestUrl);

        if (this.currentViewer === 'mirador') {
            window.open(`${path}/public/mirador/viewer.html?manifest=${manifest}`, '_blank');
        } else {
            window.open(`${path}/public/openseadragon/viewer.html?manifest=${manifest}`, '_blank');
        }
    }

    downloadImage() {
        if (this.osdViewer) {
            const tiledImage = this.osdViewer.world.getItemAt(0);
            if (tiledImage) {
                const source = tiledImage.source;
                const downloadUrl = source['@id'] || source.id;
                if (downloadUrl) {
                    window.open(downloadUrl.replace('/info.json', '/full/full/0/default.jpg'), '_blank');
                }
            }
        }
    }

    copyManifestUrl() {
        const vid = this.viewerId;
        const btn = document.getElementById(`btn-manifest-${vid}`);
        const url = btn?.dataset.url || this.options.manifestUrl;

        navigator.clipboard.writeText(url).then(() => {
            if (btn) {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-secondary');

                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-secondary');
                }, 2000);
            }
        });
    }

    goToPage(index) {
        if (this.osdViewer && this.osdViewer.world.getItemCount() > 1) {
            this.osdViewer.goToPage(index);
        } else if (this.pdfDoc) {
            this.pdfPage = index + 1;
            this.renderPdfPage();
        }
    }

    // ========================================================================
    // Utilities
    // ========================================================================

    async fetchManifest() {
        if (this._manifest) return this._manifest;

        try {
            const headers = {};
            if (this._authToken) {
                headers['Authorization'] = 'Bearer ' + this._authToken;
            }
            const response = await fetch(this.options.manifestUrl, { headers });
            this._manifest = await response.json();

            // Check for Auth 2.0 services in the manifest
            await this.handleAuth2(this._manifest);

            return this._manifest;
        } catch (error) {
            console.error('Failed to fetch manifest:', error);
            return null;
        }
    }

    // ========================================================================
    // IIIF Auth API 2.0
    // ========================================================================

    /**
     * Handle Auth 2.0 probe service pattern.
     * Checks manifest for AuthProbeService2, probes for access,
     * and triggers auth flow if needed.
     */
    async handleAuth2(manifest) {
        if (!manifest || !manifest.service) return;

        // Find AuthProbeService2 in manifest services
        const services = Array.isArray(manifest.service) ? manifest.service : [manifest.service];
        const probeService = services.find(s => s.type === 'AuthProbeService2');

        if (!probeService) return;

        console.log('Auth 2.0: Found probe service:', probeService.id);

        // Probe for access (no token initially)
        const probeResult = await this.probeAuth2(probeService.id);

        if (probeResult && probeResult.status === 200) {
            console.log('Auth 2.0: Access granted');
            return;
        }

        console.log('Auth 2.0: Access denied, status:', probeResult?.status);

        // Find access service
        const accessServices = probeService.service || [];
        const accessService = accessServices.find(s => s.type === 'AuthAccessService2');

        if (!accessService) {
            console.error('Auth 2.0: No access service found');
            return;
        }

        // Try token service first (may already have session from prior auth)
        const tokenService = (accessService.service || []).find(s => s.type === 'AuthAccessTokenService2');

        if (tokenService) {
            const tokenResult = await this.requestAuth2Token(tokenService.id);

            if (tokenResult && tokenResult.accessToken) {
                this._authToken = tokenResult.accessToken;
                console.log('Auth 2.0: Token obtained from existing session');
                // Re-probe with token
                const reProbe = await this.probeAuth2(probeService.id);
                if (reProbe && reProbe.status === 200) {
                    // Invalidate cached manifest to re-fetch with auth
                    this._manifest = null;
                    return;
                }
            }
        }

        // Show auth prompt to user
        await this.showAuth2Prompt(accessService, probeService, tokenService, probeResult);
    }

    /**
     * Call Auth 2.0 probe endpoint.
     */
    async probeAuth2(probeUrl) {
        try {
            const headers = {};
            if (this._authToken) {
                headers['Authorization'] = 'Bearer ' + this._authToken;
            }
            // Append object ID as query param
            const url = new URL(probeUrl, window.location.origin);
            if (this.options.objectId) {
                url.searchParams.set('id', this.options.objectId);
            }
            const response = await fetch(url.toString(), {
                headers,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Auth 2.0: Probe failed:', error);
            return null;
        }
    }

    /**
     * Request Auth 2.0 access token via postMessage iframe.
     */
    requestAuth2Token(tokenServiceUrl) {
        return new Promise((resolve) => {
            const messageId = 'auth2-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

            // Build token URL with params
            const url = new URL(tokenServiceUrl, window.location.origin);
            url.searchParams.set('messageId', messageId);
            url.searchParams.set('origin', window.location.origin);

            // Listen for postMessage response
            const handler = (event) => {
                const data = event.data;
                if (data && data.messageId === messageId) {
                    window.removeEventListener('message', handler);
                    iframe.remove();
                    clearTimeout(timeout);

                    if (data.type === 'AuthAccessToken2' && data.accessToken) {
                        resolve(data);
                    } else {
                        resolve(null);
                    }
                }
            };

            window.addEventListener('message', handler);

            // Create hidden iframe
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url.toString();
            document.body.appendChild(iframe);

            // Timeout after 5 seconds
            const timeout = setTimeout(() => {
                window.removeEventListener('message', handler);
                iframe.remove();
                resolve(null);
            }, 5000);
        });
    }

    /**
     * Show Auth 2.0 prompt modal.
     */
    async showAuth2Prompt(accessService, probeService, tokenService, probeResult) {
        const vid = this.viewerId;
        const label = this.extractLabel(accessService.label);
        const heading = this.extractLabel(accessService.heading || probeResult?.heading);
        const note = this.extractLabel(accessService.note || probeResult?.note);

        // Build modal HTML
        const modalId = `auth2-modal-${vid}`;
        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${heading || label || 'Authentication Required'}</h5>
                        </div>
                        <div class="modal-body">
                            <p>${note || 'This resource requires authentication to access.'}</p>
                            ${probeResult?.substitute ? '<p class="text-muted"><small>A lower-resolution preview may be available.</small></p>' : ''}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" id="${modalId}-login">${label || 'Log In'}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Inject modal
        const wrapper = document.createElement('div');
        wrapper.innerHTML = modalHtml;
        document.body.appendChild(wrapper);

        // Show modal
        let modal;
        if (window.bootstrap && window.bootstrap.Modal) {
            modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();
        } else {
            document.getElementById(modalId).style.display = 'block';
            document.getElementById(modalId).classList.add('show');
        }

        // Handle login button
        const loginBtn = document.getElementById(`${modalId}-login`);
        if (loginBtn) {
            loginBtn.addEventListener('click', async () => {
                // Open access service in new tab
                const authWindow = window.open(accessService.id, '_blank');

                // Poll for tab closure
                const pollInterval = setInterval(async () => {
                    if (authWindow && authWindow.closed) {
                        clearInterval(pollInterval);

                        // Try to get token via iframe postMessage
                        if (tokenService) {
                            const tokenResult = await this.requestAuth2Token(tokenService.id);
                            if (tokenResult && tokenResult.accessToken) {
                                this._authToken = tokenResult.accessToken;
                                console.log('Auth 2.0: Token obtained after login');

                                // Close modal and reload viewer
                                if (modal) modal.hide();
                                wrapper.remove();

                                // Re-init viewer with auth
                                this._manifest = null;
                                this.osdViewer = null;
                                this.miradorInstance = null;
                                this.loaded.osd = false;
                                this.loaded.mirador = false;
                                await this.showViewer(this.currentViewer);
                                return;
                            }
                        }

                        // Token retrieval failed
                        if (modal) modal.hide();
                        wrapper.remove();
                        console.error('Auth 2.0: Failed to obtain token after login');
                    }
                }, 500);
            });
        }
    }

    /**
     * Extract a label string from a IIIF language map.
     */
    extractLabel(langMap) {
        if (!langMap) return null;
        if (typeof langMap === 'string') return langMap;

        // Try current language, then 'en', then 'none', then first available
        const lang = document.documentElement.lang || 'en';
        const values = langMap[lang] || langMap['en'] || langMap['none'] || Object.values(langMap)[0];
        return Array.isArray(values) ? values[0] : values;
    }

    loadScript(src, type = 'text/javascript') {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.type = type;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    showElement(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'block';
    }

    hideElement(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }
}

// Export for non-module usage
if (typeof window !== 'undefined') {
    window.IiifViewerManager = IiifViewerManager;
    window.iiifViewerInstances = window.iiifViewerInstances || {};
    window.iiifViewerManager = null;
}
