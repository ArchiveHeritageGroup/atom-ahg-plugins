<?php
/**
 * Custom fields edit form partial.
 * Include in entity edit pages.
 *
 * Required variables: $entityType (string), $objectId (int)
 * Optional: $saveUrl (custom save URL, defaults to customField/saveValues)
 */

// Load services
$pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin';
require_once $pluginDir . '/lib/Repository/FieldDefinitionRepository.php';
require_once $pluginDir . '/lib/Repository/FieldValueRepository.php';
require_once $pluginDir . '/lib/Service/CustomFieldService.php';
require_once $pluginDir . '/lib/Service/CustomFieldRenderService.php';

$renderService = new \AhgCustomFieldsPlugin\Service\CustomFieldRenderService();
$html = $renderService->renderEditFields($entityType, $objectId);

if (empty($html)) {
    return;
}

$saveUrl = $saveUrl ?? url_for(['module' => 'customField', 'action' => 'saveValues']);
?>

<?php $cfEditId = 'cf-edit-' . preg_replace('/[^a-z0-9]+/i', '-', $entityType) . '-' . (int) $objectId; ?>
<section class="card mb-3 cf-edit-section">
    <div class="card-header d-flex justify-content-between align-items-center p-0">
        <?php // Collapsed by default, matching the view panel. The toggle and the
              // save button are siblings - a button cannot be nested in a button. ?>
        <button class="btn btn-link text-start text-decoration-none d-flex align-items-center gap-2 p-3 flex-grow-1 collapsed"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#<?php echo $cfEditId; ?>"
                aria-expanded="false"
                aria-controls="<?php echo $cfEditId; ?>">
            <i class="bi bi-input-cursor-text"></i>
            <span class="h5 mb-0"><?php echo __('Additional Fields'); ?></span>
            <i class="bi bi-chevron-down ms-auto" aria-hidden="true"></i>
        </button>
    </div>
    <div id="<?php echo $cfEditId; ?>" class="collapse">
        <div class="card-body">
            <input type="hidden" name="entity_type" value="<?php echo htmlspecialchars($entityType); ?>">
            <input type="hidden" name="object_id" value="<?php echo (int) $objectId; ?>">
            <?php echo $html; ?>
        </div>
    </div>
</section>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-save custom fields when parent form submits, or via standalone button
    var section = document.querySelector('.cf-edit-section');
    if (!section) return;

    var saveUrl = <?php echo json_encode($saveUrl); ?>;
    var entityType = <?php echo json_encode($entityType); ?>;
    var objectId = <?php echo json_encode((int) $objectId); ?>;

    /**
     * Collect custom field values from the section.
     */
    function collectFieldValues() {
        var values = {};
        section.querySelectorAll('[name^="cf["]').forEach(function(el) {
            var match = el.name.match(/^cf\[([^\]]+)\](\[\])?$/);
            if (!match) return;
            var key = match[0]; // full name for FormData
            // Let FormData handle it
        });
        return new FormData();
    }

    /**
     * Save custom fields via AJAX.
     */
    window.cfSaveFields = function() {
        var form = new FormData();
        form.append('entity_type', entityType);
        form.append('object_id', objectId);

        // Collect all cf[] fields
        section.querySelectorAll('[name^="cf["]').forEach(function(el) {
            if (el.type === 'checkbox') {
                if (el.type === 'checkbox' && el.name === el.name) {
                    // Hidden + checkbox pair: only append if checkbox value
                    if (el.value === '1' && el.checked) {
                        form.append(el.name, '1');
                    } else if (el.value === '0') {
                        // Skip hidden field for checkboxes — handled by checkbox itself
                    }
                }
            } else {
                form.append(el.name, el.value);
            }
        });

        return fetch(saveUrl, { method: 'POST', body: form })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    var msg = data.errors ? data.errors.join('\n') : (data.error || 'Save failed.');
                    alert('Custom fields: ' + msg);
                }
                return data;
            });
    };

    // Save with the record's own Save button - there is no separate one.
    //
    // The values POST to their own endpoint, so the record form must wait for
    // that request before navigating away, or the field values are lost. The
    // first submit is intercepted, the fields are saved, then the form is
    // submitted for real.
    var form = section.closest('form');
    if (!form) {
        return;   // no parent form (unlikely) - nothing sensible to hook
    }

    var passedThrough = false;

    form.addEventListener('submit', function(e) {
        if (passedThrough) {
            return;   // our own re-submit: let it go
        }
        e.preventDefault();
        passedThrough = true;

        // form.submit() does not carry the clicked button's name/value, which
        // AtoM uses to distinguish Save from Save-and-continue. Preserve it.
        var submitter = e.submitter;
        if (submitter && submitter.name) {
            var carry = document.createElement('input');
            carry.type = 'hidden';
            carry.name = submitter.name;
            carry.value = submitter.value || '';
            form.appendChild(carry);
        }

        // Save the fields first, then submit regardless of the outcome - a
        // failed custom-field save must not block saving the record itself.
        // cfSaveFields() already alerts the user if it fails.
        window.cfSaveFields()
            .catch(function () { /* reported inside cfSaveFields */ })
            .then(function () { form.submit(); });
    });
});
</script>
