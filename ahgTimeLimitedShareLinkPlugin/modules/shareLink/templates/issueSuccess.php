<?php
/**
 * Success page after a POST to /shareLink/issue from a browser.
 * Shows the generated public URL with copy-to-clipboard, expiry summary,
 * and "back to record" / "issue another" actions.
 *
 * Variables expected from the action:
 *   $publicUrl              string
 *   $token                  string
 *   $tokenId                int
 *   $expiresAt              string  (Y-m-d H:i:s)
 *   $informationObjectId    int
 *   $recordTitle            string
 *   $recordSlug             ?string
 *   $recipientEmail         ?string
 *   $maxAccess              ?int
 *
 * @phase L (curator UI, 2026-05-17)
 */

decorate_with('layout_2col');
slot('sidebar');
include_partial('research/researchSidebar', ['active' => $sidebarActive ?? '', 'unreadNotifications' => $unreadNotifications ?? 0]);
end_slot();

$nonce     = sfConfig::get('csp_nonce', '');
$nonceAttr = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><?php echo __('Home'); ?></a></li>
        <?php if ($recordSlug): ?>
            <li class="breadcrumb-item"><a href="/index.php/<?php echo htmlspecialchars($recordSlug); ?>"><?php echo htmlspecialchars($recordTitle); ?></a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active"><?php echo __('Share link issued'); ?></li>
    </ol>
</nav>

<div class="alert alert-success d-flex align-items-center" role="alert">
    <i class="fas fa-check-circle fa-2x me-3"></i>
    <div>
        <strong><?php echo __('Share link issued.'); ?></strong>
        <?php echo __('Send the URL below to the recipient. They will see the record without logging in.'); ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Public URL'); ?></h5></div>
    <div class="card-body">
        <div class="input-group mb-3">
            <input type="text" id="share-link-url" class="form-control font-monospace"
                   value="<?php echo htmlspecialchars($publicUrl); ?>" readonly>
            <button class="btn btn-primary" type="button" id="share-link-copy">
                <i class="fas fa-copy me-1"></i> <?php echo __('Copy'); ?>
            </button>
        </div>

        <table class="table table-sm table-borderless mb-0">
            <tr>
                <th width="180"><?php echo __('Expires'); ?></th>
                <td><?php echo htmlspecialchars($expiresAt); ?> SAST</td>
            </tr>
            <?php if (!empty($recipientEmail)): ?>
                <tr><th><?php echo __('Recipient'); ?></th><td><?php echo htmlspecialchars($recipientEmail); ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($maxAccess)): ?>
                <tr><th><?php echo __('Max views'); ?></th><td><?php echo (int) $maxAccess; ?></td></tr>
            <?php endif; ?>
            <tr>
                <th><?php echo __('Token'); ?></th>
                <td><code><?php echo htmlspecialchars($token); ?></code></td>
            </tr>
            <tr>
                <th><?php echo __('Manage'); ?></th>
                <td>
                    <a href="<?php echo url_for(['module' => 'shareLink', 'action' => 'adminShow', 'id' => $tokenId]); ?>">
                        <?php echo __('View details and access log'); ?> &raquo;
                    </a>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="d-flex gap-2">
    <a href="<?php echo url_for(['module' => 'shareLink', 'action' => 'issue']) . '?information_object_id=' . (int) $informationObjectId; ?>" class="btn btn-outline-primary">
        <i class="fas fa-plus me-1"></i> <?php echo __('Issue another'); ?>
    </a>
    <?php if ($recordSlug): ?>
        <a href="/index.php/<?php echo htmlspecialchars($recordSlug); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to record'); ?>
        </a>
    <?php endif; ?>
    <a href="<?php echo url_for(['module' => 'shareLink', 'action' => 'admin']); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-list me-1"></i> <?php echo __('All share links'); ?>
    </a>
</div>

<script <?php echo $nonceAttr; ?>>
(function () {
    var btn = document.getElementById('share-link-copy');
    var input = document.getElementById('share-link-url');
    if (!btn || !input) return;
    btn.addEventListener('click', function () {
        input.select();
        input.setSelectionRange(0, 99999);
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value);
            } else {
                document.execCommand('copy');
            }
            var orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-1"></i> Copied!';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-primary');
            setTimeout(function () {
                btn.innerHTML = orig;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-primary');
            }, 1800);
        } catch (e) {}
    });
})();
</script>
