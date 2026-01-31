/**
 * ahgIPSASPlugin JavaScript
 * IPSAS Heritage Asset Management
 */

(function() {
    'use strict';

    function init() {
        initValueCalculations();
        initDepreciationCalculator();
        initInsuranceExpiry();
    }

    /**
     * Calculate value changes
     */
    function initValueCalculations() {
        var previousValue = document.getElementById('previous_value');
        var newValue = document.getElementById('new_value');
        var changeDisplay = document.getElementById('change_display');

        if (!previousValue || !newValue) return;

        function calculateChange() {
            var prev = parseFloat(previousValue.value) || 0;
            var curr = parseFloat(newValue.value) || 0;
            var change = curr - prev;
            var percent = prev > 0 ? ((change / prev) * 100).toFixed(2) : 0;

            if (changeDisplay) {
                var sign = change >= 0 ? '+' : '';
                var colorClass = change >= 0 ? 'text-success' : 'text-danger';
                changeDisplay.innerHTML = '<span class="' + colorClass + '">' +
                    sign + change.toFixed(2) + ' (' + sign + percent + '%)</span>';
            }
        }

        previousValue.addEventListener('input', calculateChange);
        newValue.addEventListener('input', calculateChange);
    }

    /**
     * Depreciation calculator
     */
    function initDepreciationCalculator() {
        var policy = document.getElementById('depreciation_policy');
        var usefulLife = document.getElementById('useful_life_years');
        var acquisitionCost = document.getElementById('acquisition_cost');
        var residualValue = document.getElementById('residual_value');
        var depreciationDisplay = document.getElementById('depreciation_display');

        if (!policy) return;

        function calculateDepreciation() {
            if (policy.value === 'none') {
                if (depreciationDisplay) {
                    depreciationDisplay.textContent = 'Heritage assets: No depreciation';
                }
                if (usefulLife) usefulLife.disabled = true;
                if (residualValue) residualValue.disabled = true;
                return;
            }

            if (usefulLife) usefulLife.disabled = false;
            if (residualValue) residualValue.disabled = false;

            var cost = parseFloat(acquisitionCost?.value) || 0;
            var residual = parseFloat(residualValue?.value) || 0;
            var years = parseInt(usefulLife?.value) || 1;

            if (policy.value === 'straight_line') {
                var annual = (cost - residual) / years;
                if (depreciationDisplay) {
                    depreciationDisplay.textContent = 'Annual depreciation: $' + annual.toFixed(2);
                }
            } else if (policy.value === 'reducing_balance') {
                var rate = (1 - Math.pow(residual / cost, 1 / years)) * 100;
                if (depreciationDisplay) {
                    depreciationDisplay.textContent = 'Depreciation rate: ' + rate.toFixed(2) + '% p.a.';
                }
            }
        }

        policy.addEventListener('change', calculateDepreciation);
        if (usefulLife) usefulLife.addEventListener('input', calculateDepreciation);
        if (acquisitionCost) acquisitionCost.addEventListener('input', calculateDepreciation);
        if (residualValue) residualValue.addEventListener('input', calculateDepreciation);

        calculateDepreciation();
    }

    /**
     * Insurance expiry warnings
     */
    function initInsuranceExpiry() {
        document.querySelectorAll('[data-expiry-date]').forEach(function(el) {
            var expiryDate = new Date(el.dataset.expiryDate);
            var now = new Date();
            var diff = expiryDate - now;
            var days = Math.ceil(diff / (1000 * 60 * 60 * 24));

            if (days < 0) {
                el.classList.add('insurance-expired');
                el.innerHTML += ' <span class="badge bg-danger">EXPIRED</span>';
            } else if (days <= 30) {
                el.classList.add('insurance-expiring');
                el.innerHTML += ' <span class="badge bg-warning text-dark">' + days + ' days</span>';
            }
        });
    }

    /**
     * Format currency value
     */
    window.formatCurrency = function(value, currency) {
        currency = currency || 'USD';
        return currency + ' ' + parseFloat(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    /**
     * Calculate impairment
     */
    window.calculateImpairment = function(carryingAmount, recoverableAmount) {
        var carrying = parseFloat(carryingAmount) || 0;
        var recoverable = parseFloat(recoverableAmount) || 0;
        return Math.max(0, carrying - recoverable);
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
