/**
 * ahgNAZPlugin JavaScript
 * National Archives of Zimbabwe Act [Chapter 25:06]
 */

(function() {
    'use strict';

    /**
     * Initialize NAZ functionality
     */
    function init() {
        initClosureCalculator();
        initPermitFeeCalculator();
        initTransferStatusWorkflow();
        initDateValidation();
    }

    /**
     * Calculate closure end date based on type and years
     */
    function initClosureCalculator() {
        var closureTypeSelect = document.getElementById('closure_type');
        var yearsInput = document.getElementById('years');
        var startDateInput = document.getElementById('start_date');
        var endDateDisplay = document.getElementById('end_date_display');

        if (!closureTypeSelect || !yearsInput || !startDateInput) return;

        function calculateEndDate() {
            var type = closureTypeSelect.value;
            var years = parseInt(yearsInput.value) || 25;
            var startDate = new Date(startDateInput.value);

            if (type === 'indefinite') {
                if (endDateDisplay) {
                    endDateDisplay.textContent = 'Indefinite (no end date)';
                    endDateDisplay.className = 'text-danger';
                }
                yearsInput.disabled = true;
                return;
            }

            yearsInput.disabled = false;

            if (!isNaN(startDate.getTime())) {
                var endDate = new Date(startDate);
                endDate.setFullYear(endDate.getFullYear() + years);
                if (endDateDisplay) {
                    endDateDisplay.textContent = endDate.toISOString().split('T')[0];
                    endDateDisplay.className = 'text-muted';
                }
            }
        }

        closureTypeSelect.addEventListener('change', calculateEndDate);
        yearsInput.addEventListener('input', calculateEndDate);
        startDateInput.addEventListener('change', calculateEndDate);

        // Initial calculation
        calculateEndDate();
    }

    /**
     * Calculate permit fee based on researcher type
     */
    function initPermitFeeCalculator() {
        var researcherSelect = document.getElementById('researcher_id');
        var feeDisplay = document.getElementById('fee_display');

        if (!researcherSelect || !feeDisplay) return;

        researcherSelect.addEventListener('change', function() {
            var option = researcherSelect.options[researcherSelect.selectedIndex];
            var type = option.dataset.type || 'local';

            if (type === 'foreign') {
                feeDisplay.innerHTML = '<span class="badge bg-info">Fee: US$200</span>';
            } else {
                feeDisplay.innerHTML = '<span class="badge bg-success">No fee required</span>';
            }
        });
    }

    /**
     * Transfer status workflow visualization
     */
    function initTransferStatusWorkflow() {
        var statusBadges = document.querySelectorAll('.transfer-status-badge');
        var statuses = ['proposed', 'scheduled', 'in_transit', 'received', 'accessioned'];

        statusBadges.forEach(function(badge) {
            var currentStatus = badge.dataset.status;
            var currentIndex = statuses.indexOf(currentStatus);

            // Add tooltip showing next status
            if (currentIndex < statuses.length - 1 && currentIndex >= 0) {
                badge.title = 'Next: ' + statuses[currentIndex + 1].replace('_', ' ');
            }
        });
    }

    /**
     * Validate dates in forms
     */
    function initDateValidation() {
        // Ensure end date is after start date
        var forms = document.querySelectorAll('form');
        forms.forEach(function(form) {
            var startDate = form.querySelector('[name="start_date"]');
            var endDate = form.querySelector('[name="end_date"]');

            if (startDate && endDate) {
                endDate.addEventListener('change', function() {
                    if (startDate.value && endDate.value) {
                        if (new Date(endDate.value) < new Date(startDate.value)) {
                            alert('End date must be after start date');
                            endDate.value = '';
                        }
                    }
                });
            }
        });

        // Proposed date should be in the future
        var proposedDate = document.querySelector('[name="proposed_date"]');
        if (proposedDate) {
            proposedDate.addEventListener('change', function() {
                if (new Date(proposedDate.value) < new Date()) {
                    if (!confirm('The proposed date is in the past. Continue anyway?')) {
                        proposedDate.value = '';
                    }
                }
            });
        }
    }

    /**
     * Calculate days until closure expiry
     */
    window.calculateClosureDaysRemaining = function(endDate) {
        if (!endDate) return null;
        var end = new Date(endDate);
        var now = new Date();
        var diff = end - now;
        return Math.ceil(diff / (1000 * 60 * 60 * 24));
    };

    /**
     * Format permit validity period
     */
    window.formatPermitValidity = function(startDate, endDate) {
        var start = new Date(startDate);
        var end = new Date(endDate);
        var months = (end.getFullYear() - start.getFullYear()) * 12;
        months += end.getMonth() - start.getMonth();
        return months + ' months';
    };

    /**
     * Check if researcher type requires fee
     */
    window.requiresFee = function(researcherType) {
        return researcherType === 'foreign';
    };

    /**
     * Get closure type label with color
     */
    window.getClosureTypeLabel = function(type) {
        var labels = {
            'standard': { text: 'Standard (25 years)', class: 'bg-primary' },
            'extended': { text: 'Extended', class: 'bg-warning text-dark' },
            'indefinite': { text: 'Indefinite', class: 'bg-danger' },
            'ministerial': { text: 'Ministerial Order', class: 'bg-dark' }
        };
        return labels[type] || { text: type, class: 'bg-secondary' };
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
