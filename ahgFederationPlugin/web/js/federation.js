/**
 * Federation Plugin JavaScript
 */

(function() {
    'use strict';

    // Federation namespace
    window.AhgFederation = window.AhgFederation || {};

    /**
     * Test OAI-PMH endpoint connection
     */
    AhgFederation.testConnection = function(baseUrl, callback) {
        fetch('/admin/federation/api/test-peer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'base_url=' + encodeURIComponent(baseUrl)
        })
        .then(response => response.json())
        .then(data => {
            if (callback) callback(null, data);
        })
        .catch(error => {
            if (callback) callback(error, null);
        });
    };

    /**
     * Format bytes to human readable
     */
    AhgFederation.formatBytes = function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    /**
     * Format duration in seconds to human readable
     */
    AhgFederation.formatDuration = function(seconds) {
        if (seconds < 60) return Math.round(seconds) + 's';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'm ' + Math.round(seconds % 60) + 's';
        return Math.floor(seconds / 3600) + 'h ' + Math.floor((seconds % 3600) / 60) + 'm';
    };

    /**
     * Escape HTML for safe display
     */
    AhgFederation.escapeHtml = function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    /**
     * Show notification
     */
    AhgFederation.notify = function(message, type) {
        type = type || 'info';
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';

        const alert = document.createElement('div');
        alert.className = 'alert ' + alertClass + ' alert-dismissible fade show position-fixed';
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
        alert.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';

        document.body.appendChild(alert);

        setTimeout(function() {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    };

    /**
     * Copy text to clipboard
     */
    AhgFederation.copyToClipboard = function(text, successMessage) {
        navigator.clipboard.writeText(text).then(function() {
            AhgFederation.notify(successMessage || 'Copied to clipboard', 'success');
        }).catch(function() {
            AhgFederation.notify('Failed to copy', 'error');
        });
    };

    /**
     * Harvest status poller
     */
    AhgFederation.HarvestPoller = function(peerId, options) {
        this.peerId = peerId;
        this.options = options || {};
        this.interval = null;
        this.pollInterval = options.pollInterval || 2000;
    };

    AhgFederation.HarvestPoller.prototype.start = function() {
        var self = this;
        this.poll();
        this.interval = setInterval(function() {
            self.poll();
        }, this.pollInterval);
    };

    AhgFederation.HarvestPoller.prototype.stop = function() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    };

    AhgFederation.HarvestPoller.prototype.poll = function() {
        var self = this;
        fetch('/admin/federation/harvest/' + this.peerId + '/status')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (self.options.onUpdate) {
                    self.options.onUpdate(data);
                }
                if (data.status !== 'running') {
                    self.stop();
                    if (self.options.onComplete) {
                        self.options.onComplete(data);
                    }
                }
            })
            .catch(function(error) {
                console.error('Poll error:', error);
            });
    };

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        // Add click handlers for copy buttons
        document.querySelectorAll('[data-copy]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                AhgFederation.copyToClipboard(this.dataset.copy, this.dataset.copyMessage);
            });
        });

        // Initialize tooltips if Bootstrap is available
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                new bootstrap.Tooltip(el);
            });
        }
    });

})();
