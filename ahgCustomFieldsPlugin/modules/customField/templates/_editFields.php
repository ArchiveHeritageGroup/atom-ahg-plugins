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

<section class="card mb-3 cf-edit-section">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-input-cursor-text"></i> Additional Fields</h5>
        <button type="button" class="btn btn-sm btn-outline-success cf-save-fields-btn" style="display:none">
            <i class="bi bi-check-circle"></i> Save Fields
        </button>
    </div>
    <div class="card-body">
        <input type="hidden" name="entity_type" value="<?php echo htmlspecialchars($entityType); ?>">
        <input type="hidden" name="object_id" value="<?php echo (int) $objectId; ?>">
        <?php echo $html; ?>
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

    // Show standalone save button if present
    var saveBtn = section.querySelector('.cf-save-fields-btn');
    if (saveBtn) {
        saveBtn.style.display = '';
        saveBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
            var btn = this;
            window.cfSaveFields().then(function(data) {
                btn.disabled = false;
                btn.innerHTML = data.success
                    ? '<i class="bi bi-check-circle"></i> Saved!'
                    : '<i class="bi bi-check-circle"></i> Save Fields';
                if (data.success) {
                    setTimeout(function() {
                        btn.innerHTML = '<i class="bi bi-check-circle"></i> Save Fields';
                    }, 2000);
                }
            }).catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Save Fields';
            });
        });
    }
});
</script>
