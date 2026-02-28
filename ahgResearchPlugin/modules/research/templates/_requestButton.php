<?php
/**
 * "Request this item" button partial for information object view pages.
 *
 * Include via: include_partial('research/requestButton', ['objectId' => $resource->id])
 * Or inject via ahgResearchPlugin event hook.
 *
 * @var int $objectId  The information_object ID
 */
$objectId = $objectId ?? 0;
if (!$objectId) return;
?>
<div class="ahg-request-item-container mt-3 mb-3">
    <button type="button"
            class="btn btn-outline-primary btn-sm"
            id="requestItemBtn-<?php echo $objectId ?>"
            aria-label="<?php echo __('Request this item for reading room access') ?>"
            data-object-id="<?php echo $objectId ?>">
        <i class="fas fa-hand-holding me-1" aria-hidden="true"></i>
        <?php echo __('Request this Item') ?>
    </button>
    <div id="requestItemResult-<?php echo $objectId ?>" class="mt-2" role="status" aria-live="polite"></div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    var btn = document.getElementById('requestItemBtn-<?php echo $objectId ?>');
    if (!btn) return;

    btn.addEventListener('click', function() {
        var objectId = this.getAttribute('data-object-id');
        var resultDiv = document.getElementById('requestItemResult-' + objectId);
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1" aria-hidden="true"></i> <?php echo __('Requesting...') ?>';

        fetch('<?php echo url_for(['module' => 'research', 'action' => 'requestItemAjax']) ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: 'object_id=' + encodeURIComponent(objectId) + '&request_type=reading_room'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                resultDiv.innerHTML = '<div class="alert alert-success py-1 px-2 small"><i class="fas fa-check-circle me-1" aria-hidden="true"></i>' + (data.message || '<?php echo __('Item requested.') ?>') + '</div>';
                btn.innerHTML = '<i class="fas fa-check me-1" aria-hidden="true"></i> <?php echo __('Requested') ?>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-warning py-1 px-2 small"><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>' + (data.error || '<?php echo __('Could not request item.') ?>') + '</div>';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-hand-holding me-1" aria-hidden="true"></i> <?php echo __('Request this Item') ?>';
            }
        })
        .catch(function() {
            resultDiv.innerHTML = '<div class="alert alert-danger py-1 px-2 small"><?php echo __('Network error. Please try again.') ?></div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-hand-holding me-1" aria-hidden="true"></i> <?php echo __('Request this Item') ?>';
        });
    });
})();
</script>
