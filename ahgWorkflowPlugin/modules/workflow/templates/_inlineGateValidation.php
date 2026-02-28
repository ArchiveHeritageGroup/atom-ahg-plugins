<?php
/**
 * Inline Gate Validation Partial
 *
 * Include in editor forms to show publish gate status per field.
 * Requires: $objectId, $templateId (set by the including template)
 *
 * Usage in editor template:
 *   <?php include_partial('workflow/inlineGateValidation', ['objectId' => $objectId, 'templateId' => $templateId]); ?>
 */
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
?>

<style <?php echo $nonceAttr; ?>>
.gate-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-left: 6px;
    vertical-align: middle;
    cursor: help;
}
.gate-indicator.gate-passed { background-color: #28a745; }
.gate-indicator.gate-failed-blocker { background-color: #dc3545; }
.gate-indicator.gate-failed-warning { background-color: #ffc107; }
.gate-message {
    font-size: 0.8rem;
    margin-top: 2px;
    padding: 2px 8px;
    border-radius: 3px;
}
.gate-message.blocker { color: #dc3545; }
.gate-message.warning { color: #856404; }
</style>

<script <?php echo $nonceAttr; ?>>
(function() {
    'use strict';

    var objectId = <?php echo (int) ($objectId ?? 0); ?>;
    var templateId = <?php echo (int) ($templateId ?? 0); ?>;

    if (!objectId || !templateId) return;

    // Fetch gate validation data
    fetch('/workflow/api/gate-validation?object_id=' + objectId + '&template_id=' + templateId, {
        credentials: 'same-origin'
    })
    .then(function(resp) { return resp.json(); })
    .catch(function() { return { fields: [] }; })
    .then(function(data) {
        if (!data.fields || !data.fields.length) return;

        data.fields.forEach(function(field) {
            // Find the form field by name
            var el = document.querySelector('[name="' + field.field_name + '"]');
            if (!el) return;

            var label = el.closest('.form-group, .mb-3');
            if (!label) label = el.parentElement;
            if (!label) return;

            var labelEl = label.querySelector('label, .control-label');

            // Add gate indicator dot
            var dot = document.createElement('span');
            dot.className = 'gate-indicator';
            if (field.passes) {
                dot.classList.add('gate-passed');
                dot.title = 'Publish gate: passed';
            } else {
                var sev = field.gate_severity || 'warning';
                dot.classList.add('gate-failed-' + sev);
                dot.title = 'Publish gate: ' + sev;
            }

            if (labelEl) {
                labelEl.appendChild(dot);
            }

            // Add messages if failed
            if (!field.passes && field.messages && field.messages.length) {
                field.messages.forEach(function(msg) {
                    var msgEl = document.createElement('div');
                    msgEl.className = 'gate-message ' + (msg.severity || 'warning');
                    msgEl.textContent = msg.message;
                    el.parentNode.insertBefore(msgEl, el.nextSibling);
                });
            }
        });
    });
})();
</script>
