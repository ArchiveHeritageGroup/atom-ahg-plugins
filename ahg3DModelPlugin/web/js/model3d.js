/**
 * ahg3DModelPlugin JavaScript Utilities
 * 
 * Helper functions for 3D model viewing, hotspot management, and AR features
 * 
 * @package ahg3DModelPlugin
 * @author Johan Pieterse - The Archive and Heritage Group
 */

(function(window) {
    'use strict';

    // Namespace
    window.Model3D = window.Model3D || {};

    /**
     * Initialize all model viewers on the page
     */
    Model3D.initViewers = function() {
        document.querySelectorAll('model-viewer').forEach(function(viewer) {
            Model3D.setupViewer(viewer);
        });
    };

    /**
     * Setup a single model viewer
     * @param {HTMLElement} viewer - The model-viewer element
     */
    Model3D.setupViewer = function(viewer) {
        const containerId = viewer.id;
        const container = viewer.closest('.model-viewer-container') || viewer.parentElement;

        // Fullscreen button
        const fullscreenBtn = container.querySelector('[data-action="fullscreen"], #' + containerId + '-fullscreen');
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', function() {
                Model3D.toggleFullscreen(container);
            });
        }

        // Reset camera button
        const resetBtn = container.querySelector('[data-action="reset"]');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                viewer.cameraOrbit = viewer.getAttribute('camera-orbit') || '0deg 75deg 105%';
                viewer.fieldOfView = viewer.getAttribute('field-of-view') || '30deg';
            });
        }

        // Toggle auto-rotate button
        const rotateBtn = container.querySelector('[data-action="rotate"]');
        if (rotateBtn) {
            rotateBtn.addEventListener('click', function() {
                if (viewer.hasAttribute('auto-rotate')) {
                    viewer.removeAttribute('auto-rotate');
                    this.classList.remove('active');
                } else {
                    viewer.setAttribute('auto-rotate', '');
                    this.classList.add('active');
                }
            });
        }

        // Hotspot click handling
        viewer.querySelectorAll('.hotspot[data-link]').forEach(function(hotspot) {
            hotspot.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.dataset.link;
                const target = this.dataset.target || '_blank';
                window.open(url, target);
            });
        });

        // Progress tracking
        viewer.addEventListener('progress', function(event) {
            const progressBar = container.querySelector('.update-bar');
            if (progressBar) {
                const progress = Math.round(event.detail.totalProgress * 100);
                progressBar.style.width = progress + '%';
            }
        });

        // Load complete
        viewer.addEventListener('load', function() {
            const progressBar = container.querySelector('.model-viewer-progress-bar, .progress-bar');
            if (progressBar) {
                progressBar.style.display = 'none';
            }
        });

        // AR status
        viewer.addEventListener('ar-status', function(event) {
            if (event.detail.status === 'failed') {
                console.warn('AR session failed:', event.detail.message);
                Model3D.showARFallback(viewer);
            }
        });
    };

    /**
     * Toggle fullscreen mode
     * @param {HTMLElement} element - Element to make fullscreen
     */
    Model3D.toggleFullscreen = function(element) {
        if (!document.fullscreenElement) {
            if (element.requestFullscreen) {
                element.requestFullscreen();
            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    };

    /**
     * Show AR fallback message
     * @param {HTMLElement} viewer - The model-viewer element
     */
    Model3D.showARFallback = function(viewer) {
        const arButton = viewer.querySelector('[slot="ar-button"]');
        if (arButton) {
            arButton.innerHTML = '<i class="fas fa-exclamation-triangle"></i> AR Not Available';
            arButton.disabled = true;
            arButton.style.background = '#6c757d';
        }
    };

    /**
     * Check if AR is supported
     * @returns {boolean}
     */
    Model3D.isARSupported = function() {
        // Check for WebXR
        if (navigator.xr) {
            return true;
        }
        // Check for iOS Quick Look
        const a = document.createElement('a');
        if (a.relList && a.relList.supports && a.relList.supports('ar')) {
            return true;
        }
        // Check for Android Scene Viewer
        if (/android/i.test(navigator.userAgent)) {
            return true;
        }
        return false;
    };

    /**
     * Add a hotspot to a model viewer
     * @param {HTMLElement} viewer - The model-viewer element
     * @param {Object} hotspotData - Hotspot configuration
     * @returns {HTMLElement} The created hotspot button
     */
    Model3D.addHotspot = function(viewer, hotspotData) {
        const button = document.createElement('button');
        button.className = 'hotspot';
        button.slot = 'hotspot-' + (hotspotData.id || Date.now());
        button.dataset.position = hotspotData.position_x + 'm ' + hotspotData.position_y + 'm ' + hotspotData.position_z + 'm';
        button.dataset.normal = (hotspotData.normal_x || 0) + 'm ' + (hotspotData.normal_y || 1) + 'm ' + (hotspotData.normal_z || 0) + 'm';
        button.dataset.type = hotspotData.hotspot_type || 'annotation';
        button.style.setProperty('--hotspot-color', hotspotData.color || '#1a73e8');

        const annotation = document.createElement('div');
        annotation.className = 'hotspot-annotation';
        
        if (hotspotData.title) {
            const title = document.createElement('strong');
            title.textContent = hotspotData.title;
            annotation.appendChild(title);
        }
        
        if (hotspotData.description) {
            const desc = document.createElement('p');
            desc.textContent = hotspotData.description;
            annotation.appendChild(desc);
        }
        
        button.appendChild(annotation);
        viewer.appendChild(button);

        return button;
    };

    /**
     * Remove a hotspot from a model viewer
     * @param {HTMLElement} viewer - The model-viewer element
     * @param {string} hotspotId - The hotspot ID to remove
     */
    Model3D.removeHotspot = function(viewer, hotspotId) {
        const hotspot = viewer.querySelector('[slot="hotspot-' + hotspotId + '"]');
        if (hotspot) {
            hotspot.remove();
        }
    };

    /**
     * Get camera position for a hotspot focus
     * @param {Object} position - 3D position {x, y, z}
     * @param {number} distance - Camera distance
     * @returns {string} Camera orbit string
     */
    Model3D.getCameraOrbitForPosition = function(position, distance) {
        distance = distance || 2;
        const theta = Math.atan2(position.x, position.z) * (180 / Math.PI);
        const phi = Math.atan2(position.y, Math.sqrt(position.x * position.x + position.z * position.z)) * (180 / Math.PI);
        return theta + 'deg ' + (90 - phi) + 'deg ' + (distance * 100) + '%';
    };

    /**
     * Focus camera on a hotspot
     * @param {HTMLElement} viewer - The model-viewer element
     * @param {string} hotspotId - The hotspot ID to focus on
     */
    Model3D.focusHotspot = function(viewer, hotspotId) {
        const hotspot = viewer.querySelector('[slot="hotspot-' + hotspotId + '"]');
        if (!hotspot) return;

        const position = hotspot.dataset.position.split(' ').map(function(v) {
            return parseFloat(v);
        });
        
        const orbit = Model3D.getCameraOrbitForPosition({
            x: position[0],
            y: position[1],
            z: position[2]
        });
        
        viewer.cameraOrbit = orbit;
    };

    /**
     * Load 3D model via AJAX
     * @param {number} modelId - The model ID
     * @param {Function} callback - Callback with model data
     */
    Model3D.loadModel = function(modelId, callback) {
        fetch('/api/3d/models/' + modelId)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                callback(null, data);
            })
            .catch(function(error) {
                callback(error, null);
            });
    };

    /**
     * Load hotspots for a model via AJAX
     * @param {number} modelId - The model ID
     * @param {Function} callback - Callback with hotspots data
     */
    Model3D.loadHotspots = function(modelId, callback) {
        fetch('/api/3d/hotspots/' + modelId)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                callback(null, data);
            })
            .catch(function(error) {
                callback(error, null);
            });
    };

    /**
     * Save a new hotspot via AJAX
     * @param {number} modelId - The model ID
     * @param {Object} hotspotData - Hotspot data
     * @param {Function} callback - Callback with result
     */
    Model3D.saveHotspot = function(modelId, hotspotData, callback) {
        fetch('/index.php/ar3DModel/addHotspot/' + modelId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(hotspotData)
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            callback(null, data);
        })
        .catch(function(error) {
            callback(error, null);
        });
    };

    /**
     * Delete a hotspot via AJAX
     * @param {number} hotspotId - The hotspot ID
     * @param {Function} callback - Callback with result
     */
    Model3D.deleteHotspot = function(hotspotId, callback) {
        fetch('/index.php/ar3DModel/deleteHotspot/' + hotspotId, {
            method: 'POST'
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            callback(null, data);
        })
        .catch(function(error) {
            callback(error, null);
        });
    };

    /**
     * Format file size for display
     * @param {number} bytes - File size in bytes
     * @returns {string} Formatted size
     */
    Model3D.formatBytes = function(bytes) {
        // DEPRECATED: Use AhgHelpers.formatBytes() instead
        if (typeof AhgHelpers !== 'undefined') return AhgHelpers.formatBytes(bytes);
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    /**
     * Get supported 3D file extensions
     * @returns {Array} Array of extensions
     */
    Model3D.getSupportedExtensions = function() {
        return ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'usdz', 'splat', 'ksplat'];
    };

    /**
     * Check if a file is a supported 3D format
     * @param {string} filename - The filename to check
     * @returns {boolean}
     */
    Model3D.isSupportedFormat = function(filename) {
        var ext = filename.split('.').pop().toLowerCase();
        return Model3D.getSupportedExtensions().indexOf(ext) !== -1;
    };

    /**
     * Initialize Gaussian Splat viewer
     * @param {HTMLElement} container - Container element
     * @param {string} splatUrl - URL to .splat/.ksplat/.ply file
     * @param {Object} options - Viewer options
     * @returns {Object} Viewer instance
     */
    Model3D.initSplatViewer = function(container, splatUrl, options) {
        options = options || {};

        // Check if GaussianSplats3D is loaded
        if (typeof GaussianSplats3D === 'undefined') {
            console.error('GaussianSplats3D library not loaded');
            container.innerHTML = '<div class="alert alert-danger">Gaussian Splat viewer not available</div>';
            return null;
        }

        var viewer = new GaussianSplats3D.Viewer({
            cameraUp: options.cameraUp || [0, 1, 0],
            initialCameraPosition: options.cameraPosition || [0, 0, 5],
            initialCameraLookAt: options.cameraLookAt || [0, 0, 0],
            rootElement: container,
            selfDrivenMode: true,
            useBuiltInControls: true,
            dynamicScene: false,
            sharedMemoryForWorkers: false
        });

        viewer.addSplatScene(splatUrl, {
            splatAlphaRemovalThreshold: options.alphaThreshold || 5,
            showLoadingUI: true,
            position: options.position || [0, 0, 0],
            rotation: options.rotation || [0, 0, 0, 1],
            scale: options.scale || [1, 1, 1]
        }).then(function() {
            viewer.start();
            if (options.onLoad) options.onLoad(viewer);
        }).catch(function(err) {
            console.error('Failed to load splat:', err);
            if (options.onError) options.onError(err);
        });

        return viewer;
    };

    /**
     * Check if extension is a Gaussian Splat format
     * @param {string} ext - File extension
     * @returns {boolean}
     */
    Model3D.isSplatFormat = function(ext) {
        return ['splat', 'ksplat', 'ply'].indexOf(ext.toLowerCase()) !== -1;
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', Model3D.initViewers);
    } else {
        Model3D.initViewers();
    }

})(window);
