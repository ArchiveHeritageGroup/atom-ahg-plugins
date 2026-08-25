<?php
/**
 * Accessibility Helpers — Skip nav, aria-live region, focus management
 * Issue #182: WCAG 2.1 AA compliance
 *
 * Include at the top of every research/workflow template:
 *   include_partial('research/accessibilityHelpers')
 */
?>
<!-- Focus indicator + screen-reader-only helper. A stylesheet, not an injected
     <style> element: an element built in JavaScript carries no nonce, and CSP
     refuses it under style-src. See web/css/accessibility.css. -->
<link rel="stylesheet" href="/plugins/ahgResearchPlugin/web/css/accessibility.css">

<!-- Skip Navigation -->
<a href="#main-content" class="visually-hidden-focusable position-absolute top-0 start-0 p-2 bg-primary text-white z-3">
    <?php echo __('Skip to main content') ?>
</a>

<!-- ARIA Live Region for AJAX announcements -->
<div id="ahgLiveRegion" class="visually-hidden" aria-live="polite" aria-atomic="true" role="status"></div>

<!-- Focus management + keyboard shortcuts -->
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    'use strict';

    /**
     * Announce a message to screen readers via the live region.
     * @param {string} message
     * @param {string} priority  'polite' or 'assertive'
     */
    window.ahgAnnounce = function(message, priority) {
        var region = document.getElementById('ahgLiveRegion');
        if (!region) return;
        region.setAttribute('aria-live', priority || 'polite');
        region.textContent = '';
        // Use setTimeout so the SR picks up the change
        setTimeout(function() { region.textContent = message; }, 100);
    };

    /**
     * Move focus to an element by selector.
     * @param {string} selector
     */
    window.ahgFocusTo = function(selector) {
        var el = document.querySelector(selector);
        if (el) {
            el.setAttribute('tabindex', '-1');
            el.focus();
        }
    };

    // Focus indicator CSS now ships as web/css/accessibility.css, linked above.

    // Keyboard: Escape closes modals / dropdowns opened by JS
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var openModal = document.querySelector('.modal.show');
            if (openModal) {
                var closeBtn = openModal.querySelector('[data-bs-dismiss="modal"]');
                if (closeBtn) closeBtn.click();
            }
        }
    });

    // Auto-label batch checkboxes that lack aria-label
    document.querySelectorAll('input[type="checkbox"][name="request_ids[]"]').forEach(function(cb) {
        if (!cb.getAttribute('aria-label')) {
            var row = cb.closest('tr');
            if (row) {
                var text = row.querySelector('td:nth-child(2), td:nth-child(3)');
                if (text) {
                    cb.setAttribute('aria-label', 'Select ' + text.textContent.trim() + ' for batch action');
                }
            }
        }
    });
})();
</script>
