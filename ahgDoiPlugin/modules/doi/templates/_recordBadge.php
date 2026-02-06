<?php
/**
 * DOI Record Badge Partial
 *
 * Displays DOI badge on information object view pages with one-click minting for admins.
 *
 * Usage: <?php include_partial('doi/recordBadge', ['resource' => $resource]) ?>
 */

// Get the resource ID
$objectId = $resource->id ?? null;
if (!$objectId) {
    return;
}

// Check for existing DOI
$existingDoi = \Illuminate\Database\Capsule\Manager::table('ahg_doi')
    ->where('information_object_id', $objectId)
    ->first();

// Check if user is admin
$isAdmin = sfContext::getInstance()->getUser()->isAuthenticated()
    && sfContext::getInstance()->getUser()->isAdministrator();
?>

<?php if ($existingDoi): ?>
    <!-- DOI Badge - has DOI -->
    <div class="doi-badge-container mb-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success">
                <i class="fas fa-link me-1"></i>DOI
            </span>
            <a href="https://doi.org/<?php echo htmlspecialchars($existingDoi->doi) ?>"
               target="_blank"
               class="text-decoration-none"
               title="<?php echo __('View on doi.org') ?>">
                <code><?php echo htmlspecialchars($existingDoi->doi) ?></code>
                <i class="fas fa-external-link-alt fa-xs ms-1"></i>
            </a>
            <?php if ($isAdmin): ?>
                <a href="<?php echo url_for(['module' => 'doi', 'action' => 'view', 'id' => $existingDoi->id]) ?>"
                   class="btn btn-sm btn-outline-secondary"
                   title="<?php echo __('Manage DOI') ?>">
                    <i class="fas fa-cog"></i>
                </a>
            <?php endif ?>
        </div>
        <small class="text-muted">
            <?php echo __('Minted: %1%', ['%1%' => date('Y-m-d', strtotime($existingDoi->minted_at))]) ?>
            <?php if ($existingDoi->status !== 'findable'): ?>
                <span class="badge bg-warning ms-1"><?php echo htmlspecialchars($existingDoi->status) ?></span>
            <?php endif ?>
        </small>
    </div>
<?php elseif ($isAdmin): ?>
    <!-- DOI Badge - no DOI (admin can mint) -->
    <div class="doi-badge-container mb-3" id="doi-mint-container-<?php echo $objectId ?>">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary">
                <i class="fas fa-link me-1"></i><?php echo __('No DOI') ?>
            </span>
            <button type="button"
                    class="btn btn-sm btn-primary doi-mint-btn"
                    data-object-id="<?php echo $objectId ?>"
                    title="<?php echo __('Mint DOI for this record') ?>">
                <i class="fas fa-plus me-1"></i><?php echo __('Mint DOI') ?>
            </button>
        </div>
    </div>

    <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
    document.addEventListener('DOMContentLoaded', function() {
        var mintBtn = document.querySelector('.doi-mint-btn[data-object-id="<?php echo $objectId ?>"]');
        if (mintBtn) {
            mintBtn.addEventListener('click', function() {
                var btn = this;
                var objectId = btn.getAttribute('data-object-id');
                var container = document.getElementById('doi-mint-container-' + objectId);

                // Disable button and show spinner
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i><?php echo __('Minting...') ?>';

                // Make AJAX request
                fetch('/api/doi/mint/' + objectId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        // Replace with success badge
                        container.innerHTML = '<div class="d-flex align-items-center gap-2">' +
                            '<span class="badge bg-success"><i class="fas fa-link me-1"></i>DOI</span>' +
                            '<a href="https://doi.org/' + data.doi + '" target="_blank" class="text-decoration-none">' +
                            '<code>' + data.doi + '</code> <i class="fas fa-external-link-alt fa-xs ms-1"></i></a>' +
                            '</div><small class="text-muted"><?php echo __('Just minted!') ?></small>';
                    } else {
                        // Show error
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-plus me-1"></i><?php echo __('Mint DOI') ?>';
                        alert('<?php echo __('Error minting DOI:') ?> ' + (data.error || '<?php echo __('Unknown error') ?>'));
                    }
                })
                .catch(function(error) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-plus me-1"></i><?php echo __('Mint DOI') ?>';
                    alert('<?php echo __('Error minting DOI:') ?> ' + error.message);
                });
            });
        }
    });
    </script>
<?php endif ?>
