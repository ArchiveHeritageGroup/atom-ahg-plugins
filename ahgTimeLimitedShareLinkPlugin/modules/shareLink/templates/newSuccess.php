<?php
/**
 * Issue form rendered on GET /shareLink/issue?information_object_id=N.
 * POSTing the form re-enters executeIssue which dispatches to IssueService
 * and renders issueSuccess.php (or the JSON error path on failure).
 *
 * Variables expected from the action:
 *   $informationObjectId    int        FK to information_object.id
 *   $recordTitle            string     For breadcrumb / heading
 *   $recordSlug             ?string    For "back to record" link
 *   $defaultExpiryDays      int        ahg_settings share_link.default_expiry_days
 *   $maxExpiryDays          int        ahg_settings share_link.max_expiry_days
 *   $classificationLevel    ?int       NULL = unclassified
 *   $errorMessage           ?string    Set if previous POST failed
 *
 * @phase L (curator UI, 2026-05-17)
 */

decorate_with('layout_2col');
slot('sidebar');
include_partial('research/researchSidebar', ['active' => $sidebarActive ?? '', 'unreadNotifications' => $unreadNotifications ?? 0]);
end_slot();

$defaultExpiry = (new DateTime("+{$defaultExpiryDays} days"))->format('Y-m-d');
$maxExpiry     = (new DateTime("+{$maxExpiryDays} days"))->format('Y-m-d');
$nonce         = sfConfig::get('csp_nonce', '');
$nonceAttr     = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><?php echo __('Home'); ?></a></li>
        <?php if ($recordSlug): ?>
            <li class="breadcrumb-item"><a href="/index.php/<?php echo htmlspecialchars($recordSlug); ?>"><?php echo htmlspecialchars($recordTitle); ?></a></li>
        <?php else: ?>
            <li class="breadcrumb-item"><?php echo htmlspecialchars($recordTitle); ?></li>
        <?php endif; ?>
        <li class="breadcrumb-item active"><?php echo __('Issue share link'); ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h2 mb-0"><i class="fas fa-share-alt text-primary me-2"></i><?php echo __('Issue share link'); ?></h1>
    <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> <?php echo __('Cancel'); ?></a>
</div>

<p class="text-muted">
    <?php echo __('Issuing a share link for'); ?>
    <strong>
        <?php if ($recordSlug): ?>
            <a href="/index.php/<?php echo htmlspecialchars($recordSlug); ?>"><?php echo htmlspecialchars($recordTitle); ?></a>
        <?php else: ?>
            <?php echo htmlspecialchars($recordTitle); ?>
        <?php endif; ?>
    </strong>
</p>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

<?php if ($classificationLevel !== null): ?>
    <div class="alert alert-warning small">
        <i class="fas fa-shield-alt me-1"></i>
        <?php echo __('This record is classified (level'); ?> <?php echo (int) $classificationLevel; ?>).
        <?php echo __('Your clearance must meet or exceed this level to issue a link, and you need the'); ?>
        <code>share_link.create_classified</code> <?php echo __('permission.'); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo url_for(['module' => 'shareLink', 'action' => 'issue']); ?>" id="share-link-form">
            <input type="hidden" name="information_object_id" value="<?php echo (int) $informationObjectId; ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="sl-expires-at" class="form-label"><?php echo __('Expires on'); ?></label>
                    <input type="date" name="expires_at" id="sl-expires-at"
                           value="<?php echo htmlspecialchars($defaultExpiry); ?>"
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           max="<?php echo htmlspecialchars($maxExpiry); ?>"
                           class="form-control" required>
                    <small class="text-muted">
                        <?php echo __('Default'); ?>: <?php echo (int) $defaultExpiryDays; ?> <?php echo __('days'); ?>.
                        <?php echo __('Max'); ?>: <?php echo (int) $maxExpiryDays; ?> <?php echo __('days'); ?>
                        <?php echo __('(longer requires the unlimited-expiry permission)'); ?>.
                    </small>
                </div>

                <div class="col-md-6">
                    <label for="sl-max-access" class="form-label"><?php echo __('Max views (optional)'); ?></label>
                    <input type="number" name="max_access" id="sl-max-access" min="1" max="10000" class="form-control" placeholder="<?php echo __('Unlimited within window'); ?>">
                    <small class="text-muted"><?php echo __('Leave blank for unlimited views until expiry.'); ?></small>
                </div>

                <div class="col-md-6">
                    <label for="sl-email" class="form-label"><?php echo __('Recipient email (optional)'); ?></label>
                    <input type="email" name="recipient_email" id="sl-email" class="form-control" placeholder="researcher@example.com">
                    <small class="text-muted"><?php echo __('Informational only; the token itself grants access.'); ?></small>
                </div>

                <div class="col-md-6">
                    <label for="sl-note" class="form-label"><?php echo __('Note (optional)'); ?></label>
                    <input type="text" name="recipient_note" id="sl-note" maxlength="500" class="form-control" placeholder="<?php echo __('Why are you sharing this?'); ?>">
                    <small class="text-muted"><?php echo __('Captured in the audit trail.'); ?></small>
                </div>
            </div>

            <hr>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-link me-1"></i> <?php echo __('Issue link'); ?>
            </button>
            <a href="javascript:history.back()" class="btn btn-outline-secondary ms-1">
                <?php echo __('Cancel'); ?>
            </a>
        </form>
    </div>
</div>
