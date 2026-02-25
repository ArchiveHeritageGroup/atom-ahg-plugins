/**
 * ahgRegistryPlugin - Leaflet.js Map Integration
 *
 * Renders institutions on an interactive map with clustering,
 * popups, and filter integration.
 */
(function () {
    'use strict';

    var map, markerLayer, markers = [];

    /**
     * Initialize the registry map.
     * @param {Object} config
     * @param {string} config.container  - DOM element ID (default: 'registry-map')
     * @param {number} config.lat        - Default latitude
     * @param {number} config.lng        - Default longitude
     * @param {number} config.zoom       - Default zoom level
     * @param {string} config.dataUrl    - URL to fetch institution JSON
     * @param {Array}  config.institutions - Pre-loaded institution data (optional)
     */
    window.RegistryMap = {
        init: function (config) {
            config = config || {};
            var containerId = config.container || 'registry-map';
            var defaultLat = config.lat || -30.5595;
            var defaultLng = config.lng || 22.9375;
            var defaultZoom = config.zoom || 5;

            var el = document.getElementById(containerId);
            if (!el) return;

            // Initialize Leaflet map
            map = L.map(containerId).setView([defaultLat, defaultLng], defaultZoom);

            // OpenStreetMap tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18
            }).addTo(map);

            // Marker cluster group (if plugin available)
            if (typeof L.markerClusterGroup === 'function') {
                markerLayer = L.markerClusterGroup({
                    maxClusterRadius: 50,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false
                });
            } else {
                markerLayer = L.layerGroup();
            }

            map.addLayer(markerLayer);

            // Load data
            if (config.institutions && config.institutions.length) {
                this.plotInstitutions(config.institutions);
            } else if (config.dataUrl) {
                this.fetchAndPlot(config.dataUrl);
            }

            return map;
        },

        /**
         * Fetch institutions from API and plot them.
         */
        fetchAndPlot: function (url) {
            var self = this;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        var institutions = data.institutions || data.data || data;
                        if (Array.isArray(institutions)) {
                            self.plotInstitutions(institutions);
                        }
                    } catch (e) {
                        console.error('Registry map: Failed to parse data', e);
                    }
                }
            };
            xhr.send();
        },

        /**
         * Plot institution markers on the map.
         * @param {Array} institutions
         */
        plotInstitutions: function (institutions) {
            markerLayer.clearLayers();
            markers = [];

            var bounds = [];

            for (var i = 0; i < institutions.length; i++) {
                var inst = institutions[i];
                var lat = parseFloat(inst.latitude);
                var lng = parseFloat(inst.longitude);

                if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) {
                    continue;
                }

                var marker = L.marker([lat, lng]);
                marker.institutionData = inst;

                // Build popup
                var popup = this.buildPopup(inst);
                marker.bindPopup(popup);

                markerLayer.addLayer(marker);
                markers.push(marker);
                bounds.push([lat, lng]);
            }

            // Fit bounds if we have markers
            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50], maxZoom: 12 });
            }
        },

        /**
         * Build HTML popup content for an institution.
         */
        buildPopup: function (inst) {
            var html = '<div class="registry-map-popup">';
            html += '<h6>' + this.escapeHtml(inst.name) + '</h6>';

            if (inst.institution_type) {
                html += '<span class="badge bg-info mb-1">' + this.escapeHtml(inst.institution_type.replace(/_/g, ' ')) + '</span> ';
            }

            if (inst.city || inst.country) {
                var loc = [];
                if (inst.city) loc.push(inst.city);
                if (inst.country) loc.push(inst.country);
                html += '<div class="text-muted" style="font-size:0.85rem">' + this.escapeHtml(loc.join(', ')) + '</div>';
            }

            if (inst.short_description) {
                html += '<p style="font-size:0.85rem;margin-top:0.5rem">' + this.escapeHtml(inst.short_description.substring(0, 150)) + '</p>';
            }

            if (inst.slug) {
                html += '<a href="/registry/institutions/' + encodeURIComponent(inst.slug) + '" class="btn btn-sm btn-primary mt-1">View Profile</a>';
            }

            html += '</div>';
            return html;
        },

        /**
         * Filter markers by type/country.
         */
        filterMarkers: function (filters) {
            markerLayer.clearLayers();

            for (var i = 0; i < markers.length; i++) {
                var inst = markers[i].institutionData;
                var show = true;

                if (filters.type && inst.institution_type !== filters.type) {
                    show = false;
                }
                if (filters.country && inst.country !== filters.country) {
                    show = false;
                }
                if (filters.sector) {
                    var sectors = inst.glam_sectors;
                    if (typeof sectors === 'string') {
                        try { sectors = JSON.parse(sectors); } catch (e) { sectors = []; }
                    }
                    if (!Array.isArray(sectors) || sectors.indexOf(filters.sector) === -1) {
                        show = false;
                    }
                }

                if (show) {
                    markerLayer.addLayer(markers[i]);
                }
            }
        },

        /**
         * Reset all filters — show all markers.
         */
        resetFilters: function () {
            markerLayer.clearLayers();
            for (var i = 0; i < markers.length; i++) {
                markerLayer.addLayer(markers[i]);
            }
        },

        /**
         * Get the Leaflet map instance.
         */
        getMap: function () {
            return map;
        },

        /**
         * Escape HTML entities.
         */
        escapeHtml: function (str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }
    };
})();
