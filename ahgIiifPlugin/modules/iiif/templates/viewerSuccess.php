<?php $n = sfConfig::get('csp_nonce', ''); $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php echo get_partial('layout_start', ['title' => 'IIIF Viewer - ' . htmlspecialchars($objectTitle)]) ?>

<div class="container-fluid py-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <?php if (!empty($objectSlug)): ?>
                <li class="breadcrumb-item"><a href="/<?php echo htmlspecialchars($objectSlug); ?>">Record</a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active">IIIF Viewer</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><?php echo htmlspecialchars($objectTitle); ?></h4>
        <div class="btn-group btn-group-sm">
            <?php if (!empty($objectSlug)): ?>
                <a href="/<?php echo htmlspecialchars($objectSlug); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Record
                </a>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($manifestUrl); ?>" class="btn btn-outline-info" target="_blank">
                <i class="fas fa-file-code me-1"></i>Manifest JSON
            </a>
            <button id="btn-fullscreen" class="btn btn-outline-primary" title="Fullscreen">
                <i class="fas fa-expand"></i>
            </button>
        </div>
    </div>

    <!-- Viewer toggle buttons -->
    <div class="btn-group btn-group-sm mb-2" role="group">
        <button id="btn-osd" class="btn btn-outline-secondary active" title="OpenSeadragon">
            <i class="fas fa-search-plus me-1"></i>Deep Zoom
        </button>
        <button id="btn-mirador" class="btn btn-outline-secondary" title="Mirador">
            <i class="fas fa-columns me-1"></i>Mirador
        </button>
    </div>

    <!-- OpenSeadragon container -->
    <div id="iiif-osd-viewer" style="width:100%;height:75vh;background:#1a1a1a;border-radius:8px;"></div>

    <!-- Mirador container (hidden by default) -->
    <div id="iiif-mirador-viewer" style="width:100%;height:75vh;border-radius:8px;display:none;"></div>
</div>

<script <?php echo $nonceAttr; ?>>
(function() {
    var manifestUrl = <?php echo json_encode($manifestUrl); ?>;
    var pluginPath = <?php echo json_encode($pluginPath); ?>;
    var osdContainer = document.getElementById('iiif-osd-viewer');
    var miradorContainer = document.getElementById('iiif-mirador-viewer');
    var osdViewer = null;
    var miradorInstance = null;
    var currentViewer = 'osd';

    // Load OpenSeadragon
    function loadScript(src) {
        return new Promise(function(resolve, reject) {
            if (document.querySelector('script[src="' + src + '"]')) {
                resolve();
                return;
            }
            var s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    function initOSD() {
        if (osdViewer) {
            osdContainer.style.display = 'block';
            miradorContainer.style.display = 'none';
            return;
        }
        loadScript(pluginPath + '/js/vendor/openseadragon.min.js').then(function() {
            // Fetch manifest and extract tile sources
            fetch(manifestUrl).then(function(r) { return r.json(); }).then(function(manifest) {
                var tileSources = [];
                var sequences = manifest.sequences || [];
                if (sequences.length > 0) {
                    var canvases = sequences[0].canvases || [];
                    canvases.forEach(function(canvas) {
                        var images = canvas.images || [];
                        images.forEach(function(img) {
                            var svc = img.resource && img.resource.service;
                            if (svc) {
                                tileSources.push(svc['@id'] + '/info.json');
                            }
                        });
                    });
                }
                if (tileSources.length === 0) {
                    osdContainer.innerHTML = '<div class="alert alert-warning m-3">No IIIF tile sources found in manifest.</div>';
                    return;
                }
                osdViewer = OpenSeadragon({
                    id: 'iiif-osd-viewer',
                    tileSources: tileSources,
                    sequenceMode: tileSources.length > 1,
                    showNavigator: true,
                    navigatorPosition: 'BOTTOM_RIGHT',
                    showReferenceStrip: tileSources.length > 1,
                    referenceStripScroll: 'horizontal',
                    prefixUrl: 'https://cdn.jsdelivr.net/npm/openseadragon@3.1.0/build/openseadragon/images/',
                    gestureSettingsMouse: { clickToZoom: true },
                    animationTime: 0.5,
                    zoomPerClick: 1.5,
                    maxZoomPixelRatio: 4,
                    visibilityRatio: 0.5,
                    constrainDuringPan: true
                });
            });
        });
        osdContainer.style.display = 'block';
        miradorContainer.style.display = 'none';
    }

    function initMirador() {
        osdContainer.style.display = 'none';
        miradorContainer.style.display = 'block';
        if (miradorInstance) return;

        var miradorJs = pluginPath + '/public/mirador/mirador.min.js';
        loadScript(miradorJs).then(function() {
            if (typeof Mirador !== 'undefined') {
                miradorInstance = Mirador.viewer({
                    id: 'iiif-mirador-viewer',
                    windows: [{ manifestId: manifestUrl }],
                    window: { allowClose: false, allowMaximize: false }
                });
            } else {
                miradorContainer.innerHTML = '<div class="alert alert-warning m-3">Mirador viewer not available. Please use Deep Zoom viewer.</div>';
            }
        }).catch(function() {
            miradorContainer.innerHTML = '<div class="alert alert-warning m-3">Could not load Mirador. Please use Deep Zoom viewer.</div>';
        });
    }

    // Toggle buttons
    document.getElementById('btn-osd').addEventListener('click', function() {
        currentViewer = 'osd';
        this.classList.add('active');
        document.getElementById('btn-mirador').classList.remove('active');
        initOSD();
    });

    document.getElementById('btn-mirador').addEventListener('click', function() {
        currentViewer = 'mirador';
        this.classList.add('active');
        document.getElementById('btn-osd').classList.remove('active');
        initMirador();
    });

    // Fullscreen
    document.getElementById('btn-fullscreen').addEventListener('click', function() {
        var el = currentViewer === 'osd' ? osdContainer : miradorContainer;
        if (el.requestFullscreen) el.requestFullscreen();
        else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
    });

    // Auto-init OpenSeadragon
    initOSD();
})();
</script>

<?php echo get_partial('layout_end') ?>
