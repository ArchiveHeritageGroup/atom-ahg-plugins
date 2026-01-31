/**
 * ahgCDPAPlugin JavaScript
 * Zimbabwe Cyber and Data Protection Act Compliance
 */

(function() {
    'use strict';

    /**
     * Initialize CDPA functionality
     */
    function init() {
        initDeadlineCountdowns();
        initBreachNotificationWarning();
        initConsentWithdrawal();
    }

    /**
     * Show countdown for request deadlines
     */
    function initDeadlineCountdowns() {
        document.querySelectorAll('[data-due-date]').forEach(function(el) {
            var dueDate = new Date(el.dataset.dueDate);
            var now = new Date();
            var diff = dueDate - now;
            var days = Math.ceil(diff / (1000 * 60 * 60 * 24));

            if (days < 0) {
                el.innerHTML = '<span class="text-danger fw-bold">OVERDUE by ' + Math.abs(days) + ' days</span>';
            } else if (days === 0) {
                el.innerHTML = '<span class="text-danger fw-bold">Due TODAY</span>';
            } else if (days <= 7) {
                el.innerHTML = '<span class="text-warning">' + days + ' days remaining</span>';
            } else {
                el.innerHTML = days + ' days remaining';
            }
        });
    }

    /**
     * Warning for breach notification (72-hour deadline)
     */
    function initBreachNotificationWarning() {
        document.querySelectorAll('[data-breach-discovery]').forEach(function(el) {
            var discoveryDate = new Date(el.dataset.breachDiscovery);
            var now = new Date();
            var hoursSince = (now - discoveryDate) / (1000 * 60 * 60);
            var hoursRemaining = 72 - hoursSince;

            if (!el.dataset.notified) {
                if (hoursRemaining < 0) {
                    el.innerHTML = '<span class="text-danger fw-bold">POTRAZ notification OVERDUE!</span>';
                } else if (hoursRemaining < 24) {
                    el.innerHTML = '<span class="text-danger">' + Math.floor(hoursRemaining) + ' hours remaining to notify POTRAZ</span>';
                } else {
                    el.innerHTML = '<span class="text-warning">' + Math.floor(hoursRemaining) + ' hours remaining to notify POTRAZ</span>';
                }
            }
        });
    }

    /**
     * Consent withdrawal confirmation
     */
    function initConsentWithdrawal() {
        document.querySelectorAll('.btn-withdraw-consent').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to withdraw this consent? This action will be logged.')) {
                    e.preventDefault();
                }
            });
        });
    }

    /**
     * Calculate license tier based on data subjects count
     */
    window.calculateLicenseTier = function(count) {
        if (count <= 1000) return 'tier1';
        if (count <= 10000) return 'tier2';
        if (count <= 500000) return 'tier3';
        return 'tier4';
    };

    /**
     * Validate Form DP2 date
     */
    window.validateDP2Date = function(appointmentDate, dp2Date) {
        var appointment = new Date(appointmentDate);
        var dp2 = new Date(dp2Date);
        return dp2 >= appointment;
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
