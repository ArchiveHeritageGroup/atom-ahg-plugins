<?php
/** @var object $tokenRow */
/** @var string $issuerName */
/** @var ?string $issuerEmail */
/** @var string $ioTitle */
/** @var \Illuminate\Support\Collection $accessLog */
/** @var string $status */

$statusBadges = [
    'active'    => ['bg-success', __('Active')],
    'expired'   => ['bg-warning text-dark', __('Expired')],
    'revoked'   => ['bg-secondary', __('Revoked')],
    'exhausted' => ['bg-info text-dark', __('Exhausted')],
];
[$badgeCls, $badgeLabel] = $statusBadges[$status] ?? ['bg-light text-dark', $status];

$accessIcons = [
    'view'           => ['fa-check text-success',  __('Viewed')],
    'denied_expired' => ['fa-clock text-warning',  __('Denied — expired')],
    'denied_revoked' => ['fa-ban text-secondary',  __('Denied — revoked')],
    'denied_quota'   => ['fa-stop text-info',      __('Denied — quota exhausted')],
    'denied_unknown' => ['fa-question text-muted', __('Denied — unknown')],
];

$publicUrl = $request->getUriPrefix() . '/share/' . $tokenRow->token;
?>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  .sl-show .label { color: #6c757d; font-size: .85rem; }
  .sl-show .value { font-size: 1rem; }
  .sl-show code.token { word-break: break-all; }
  .sl-show .table-access td, .sl-show .table-access th { font-size: .9rem; }
</style>

<p>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for(['module' => 'shareLink', 'action' => 'admin']) ?>">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to list') ?>
  </a>
</p>

<div class="d-flex justify-content-between align-items-center">
  <h1 class="mb-0">
    <i class="fas fa-share-alt me-1"></i><?php echo __('Share link') ?>
    <span class="badge <?php echo $badgeCls ?> ms-2"><?php echo esc_entities($badgeLabel) ?></span>
  </h1>
  <?php if ($status === 'active' || $status === 'exhausted'): ?>
    <form action="<?php echo url_for(['module' => 'shareLink', 'action' => 'revoke', 'id' => $tokenRow->id]) ?>" method="post"
          onsubmit="return confirm('<?php echo esc_entities(__('Revoke this share link? Recipients will no longer be able to view the record.')) ?>');">
      <button type="submit" class="btn btn-outline-danger btn-sm">
        <i class="fas fa-ban me-1"></i><?php echo __('Revoke') ?>
      </button>
    </form>
  <?php endif ?>
</div>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success mt-2"><?php echo esc_entities($sf_user->getFlash('success')) ?></div>
<?php endif ?>
<?php if ($sf_user->hasFlash('info')): ?>
  <div class="alert alert-info mt-2"><?php echo esc_entities($sf_user->getFlash('info')) ?></div>
<?php endif ?>

<div class="row sl-show">
  <div class="col-md-6">
    <p><span class="label"><?php echo __('Record') ?></span><br>
       <span class="value"><?php echo esc_entities($ioTitle) ?>
         <small class="text-muted">#<?php echo (int) $tokenRow->information_object_id ?></small>
       </span></p>
    <p><span class="label"><?php echo __('Issuer') ?></span><br>
       <span class="value"><?php echo esc_entities($issuerName) ?>
         <?php if ($issuerEmail): ?><small class="text-muted">(<?php echo esc_entities($issuerEmail) ?>)</small><?php endif ?>
       </span></p>
    <p><span class="label"><?php echo __('Recipient') ?></span><br>
       <span class="value"><?php echo $tokenRow->recipient_email ? esc_entities($tokenRow->recipient_email) : '<em class="text-muted">' . __('Any holder of link') . '</em>' ?></span></p>
    <?php if (!empty($tokenRow->recipient_note)): ?>
      <p><span class="label"><?php echo __('Note') ?></span><br>
         <span class="value"><?php echo nl2br(esc_entities($tokenRow->recipient_note)) ?></span></p>
    <?php endif ?>
  </div>
  <div class="col-md-6">
    <p><span class="label"><?php echo __('Issued') ?></span><br>
       <span class="value"><?php echo esc_entities($tokenRow->created_at) ?></span></p>
    <p><span class="label"><?php echo __('Expires') ?></span><br>
       <span class="value"><?php echo esc_entities($tokenRow->expires_at) ?></span></p>
    <p><span class="label"><?php echo __('Visits') ?></span><br>
       <span class="value"><?php echo (int) $tokenRow->access_count ?><?php if ($tokenRow->max_access !== null) echo ' / ' . (int) $tokenRow->max_access ?></span></p>
    <?php if (!empty($tokenRow->revoked_at)): ?>
      <p><span class="label"><?php echo __('Revoked at') ?></span><br>
         <span class="value"><?php echo esc_entities($tokenRow->revoked_at) ?></span></p>
    <?php endif ?>
    <?php if ($tokenRow->classification_level_at_issuance !== null): ?>
      <p><span class="label"><?php echo __('Classification level at issuance') ?></span><br>
         <span class="value"><?php echo (int) $tokenRow->classification_level_at_issuance ?></span></p>
    <?php endif ?>
  </div>
</div>

<h5 class="mt-3"><?php echo __('Public URL') ?></h5>
<p><code class="token sl-show"><?php echo esc_entities($publicUrl) ?></code></p>

<h5 class="mt-4"><?php echo sprintf(__('Access log (%d)'), count($accessLog)) ?></h5>
<?php if (count($accessLog) === 0): ?>
  <div class="alert alert-secondary"><?php echo __('No access attempts recorded yet.') ?></div>
<?php else: ?>
<table class="table table-sm table-hover table-access sl-show">
  <thead>
    <tr>
      <th><?php echo __('When') ?></th>
      <th><?php echo __('Outcome') ?></th>
      <th><?php echo __('IP') ?></th>
      <th><?php echo __('User agent') ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($accessLog as $a): ?>
      <?php [$icon, $label] = $accessIcons[$a->action] ?? ['fa-question', $a->action]; ?>
      <tr>
        <td><?php echo esc_entities($a->accessed_at) ?></td>
        <td><i class="fas <?php echo $icon ?> me-1"></i><?php echo esc_entities($label) ?></td>
        <td><?php echo esc_entities($a->ip_address ?? '—') ?></td>
        <td class="text-truncate" style="max-width:300px;"><?php echo esc_entities($a->user_agent ?? '—') ?></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>
<?php endif ?>
