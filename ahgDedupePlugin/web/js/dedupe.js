/**
 * ahgDedupePlugin JavaScript
 * Duplicate Detection Plugin for AtoM
 */

(function() {
    'use strict';

    // Configuration
    var config = {
        realtimeCheckDelay: 500,  // ms delay before checking
        realtimeMinLength: 5,     // minimum characters to trigger check
        warningDisplayTime: 10000 // ms to show warning
    };

    // State
    var checkTimeout = null;
    var lastCheckedValue = '';

    /**
     * Initialize dedupe functionality
     */
    function init() {
        // Real-time duplicate checking on title fields
        initRealtimeCheck();

        // Dismiss button handlers
        initDismissButtons();

        // Batch selection handlers
        initBatchSelection();

        // Primary record selection visual feedback
        initPrimarySelection();
    }

    /**
     * Real-time duplicate checking during data entry
     */
    function initRealtimeCheck() {
        var titleField = document.querySelector('input[name*="title"]');
        if (!titleField) return;

        titleField.addEventListener('input', function() {
            var value = this.value.trim();

            // Clear previous timeout
            if (checkTimeout) {
                clearTimeout(checkTimeout);
            }

            // Don't check if too short or same as last check
            if (value.length < config.realtimeMinLength || value === lastCheckedValue) {
                return;
            }

            // Debounce the check
            checkTimeout = setTimeout(function() {
                checkForDuplicates(value);
            }, config.realtimeCheckDelay);
        });
    }

    /**
     * Check for duplicates via API
     */
    function checkForDuplicates(title) {
        lastCheckedValue = title;

        fetch('/api/dedupe/realtime?title=' + encodeURIComponent(title), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.matches && data.matches.length > 0) {
                showDuplicateWarning(data.matches);
            } else {
                hideDuplicateWarning();
            }
        })
        .catch(function(error) {
            console.error('Duplicate check failed:', error);
        });
    }

    /**
     * Show duplicate warning
     */
    function showDuplicateWarning(matches) {
        // Remove existing warning
        hideDuplicateWarning();

        var warning = document.createElement('div');
        warning.className = 'dedupe-warning alert alert-warning';
        warning.id = 'dedupeWarning';

        var html = '<span class="close-btn">&times;</span>';
        html += '<h6><i class="fas fa-exclamation-triangle me-2"></i>Potential Duplicates Found</h6>';
        html += '<p class="small mb-2">Similar records exist in the system:</p>';
        html += '<ul class="list-unstyled small mb-0">';

        matches.forEach(function(match) {
            var score = Math.round(match.similarity * 100);
            html += '<li class="mb-1">';
            html += '<span class="badge bg-' + (score >= 90 ? 'danger' : 'warning') + ' me-1">' + score + '%</span>';
            html += '<a href="/' + match.slug + '" target="_blank">' + escapeHtml(match.title) + '</a>';
            html += '</li>';
        });

        html += '</ul>';

        warning.innerHTML = html;
        document.body.appendChild(warning);

        // Close button
        warning.querySelector('.close-btn').addEventListener('click', hideDuplicateWarning);

        // Auto-hide after timeout
        setTimeout(hideDuplicateWarning, config.warningDisplayTime);
    }

    /**
     * Hide duplicate warning
     */
    function hideDuplicateWarning() {
        var warning = document.getElementById('dedupeWarning');
        if (warning) {
            warning.remove();
        }
    }

    /**
     * Initialize dismiss buttons
     */
    function initDismissButtons() {
        document.querySelectorAll('.btn-dismiss').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                if (!confirm('Dismiss this duplicate pair as a false positive?')) {
                    return;
                }

                var id = this.dataset.id;
                var row = this.closest('tr');
                var button = this;

                button.disabled = true;

                fetch('/admin/dedupe/dismiss/' + id, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        if (row) {
                            row.style.transition = 'opacity 0.3s';
                            row.style.opacity = '0';
                            setTimeout(function() {
                                row.remove();
                            }, 300);
                        }
                    } else {
                        alert('Failed to dismiss duplicate');
                        button.disabled = false;
                    }
                })
                .catch(function(error) {
                    console.error('Dismiss failed:', error);
                    alert('Failed to dismiss duplicate');
                    button.disabled = false;
                });
            });
        });
    }

    /**
     * Initialize batch selection
     */
    function initBatchSelection() {
        var checkAll = document.getElementById('checkAll');
        var rowChecks = document.querySelectorAll('.row-check');
        var dismissSelected = document.getElementById('dismissSelected');
        var selectAllBtn = document.getElementById('selectAll');

        if (!checkAll || !rowChecks.length) return;

        function updateDismissButton() {
            var checked = document.querySelectorAll('.row-check:checked');
            if (dismissSelected) {
                dismissSelected.disabled = checked.length === 0;
            }
        }

        checkAll.addEventListener('change', function() {
            rowChecks.forEach(function(cb) {
                cb.checked = checkAll.checked;
            });
            updateDismissButton();
        });

        rowChecks.forEach(function(cb) {
            cb.addEventListener('change', updateDismissButton);
        });

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                rowChecks.forEach(function(cb) {
                    cb.checked = true;
                });
                checkAll.checked = true;
                updateDismissButton();
            });
        }

        if (dismissSelected) {
            dismissSelected.addEventListener('click', function() {
                var checked = document.querySelectorAll('.row-check:checked');
                if (checked.length === 0) return;

                if (!confirm('Dismiss ' + checked.length + ' duplicate pair(s) as false positives?')) {
                    return;
                }

                var ids = Array.from(checked).map(function(cb) {
                    return cb.dataset.id;
                });

                dismissSelected.disabled = true;

                // Dismiss each
                Promise.all(ids.map(function(id) {
                    return fetch('/admin/dedupe/dismiss/' + id, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                }))
                .then(function() {
                    location.reload();
                })
                .catch(function(error) {
                    console.error('Batch dismiss failed:', error);
                    dismissSelected.disabled = false;
                });
            });
        }
    }

    /**
     * Initialize primary record selection
     */
    function initPrimarySelection() {
        var optionA = document.getElementById('optionA');
        var optionB = document.getElementById('optionB');
        var radioA = document.getElementById('primaryA');
        var radioB = document.getElementById('primaryB');

        if (!optionA || !optionB || !radioA || !radioB) return;

        function updateSelection() {
            optionA.classList.toggle('selected', radioA.checked);
            optionB.classList.toggle('selected', radioB.checked);
        }

        radioA.addEventListener('change', updateSelection);
        radioB.addEventListener('change', updateSelection);

        optionA.addEventListener('click', function(e) {
            if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                radioA.checked = true;
                updateSelection();
            }
        });

        optionB.addEventListener('click', function(e) {
            if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                radioB.checked = true;
                updateSelection();
            }
        });

        // Initial state
        updateSelection();
    }

    /**
     * Escape HTML entities
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose public API
    window.AhgDedupe = {
        checkForDuplicates: checkForDuplicates,
        showWarning: showDuplicateWarning,
        hideWarning: hideDuplicateWarning
    };

})();
