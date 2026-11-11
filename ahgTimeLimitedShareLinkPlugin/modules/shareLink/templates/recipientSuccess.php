<?php
/**
 * The page a share-link recipient sees. A fragment in the site layout (it was a
 * whole HTML document nested inside the layout), in the AHG palette, with the
 * record's dates, creators, extent, repository and reference image.
 *
 * @var array    $record     see AhgShareLink\Services\RecordView::build()
 * @var string   $issuerName
 * @var string   $expiresAt
 */
$r = $sf_data->getRaw('record');
$e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$ts = strtotime((string) $sf_data->getRaw('expiresAt'));
$expires = $ts ? date('j F Y, H:i', $ts) : (string) $sf_data->getRaw('expiresAt');
$nonce = preg_replace('/^nonce=/', '', (string) sfConfig::get('csp_nonce', ''));
$facts = array_filter([
    __('Reference code') => $r['identifier'] ?? null,
    __('Level of description') => $r['level'] ?? null,
    __('Dates') => $r['dates'] ? implode('; ', $r['dates']) : null,
    __('Creator') => $r['creators'] ? implode('; ', $r['creators']) : null,
    __('Extent') => $r['extent'] ?? null,
    __('Repository') => $r['repository'] ?? null,
]);
?>
<style <?php echo $nonce ? 'nonce="' . $e($nonce) . '"' : ''; ?>>
  .sl-banner { background: #10373E; color: #F2EDE6; padding: .75rem 1.25rem; border-radius: .375rem .375rem 0 0; }
  .sl-banner strong { color: #E1B34F; }
  .sl-card { background: #fff; border: 1px solid #C3CDCE; border-top: 0; border-radius: 0 0 .375rem .375rem; padding: 1.5rem 2rem; }
  .sl-card h1 { margin-top: 0; }
  .sl-facts dt { color: #6c757d; font-weight: normal; }
  .sl-facts dd { margin-bottom: .6rem; }
  .sl-scope { white-space: pre-wrap; line-height: 1.55; }
  .sl-image img { max-width: 100%; height: auto; border: 1px solid #C3CDCE; border-radius: .25rem; }
  .sl-footer { color: #6c757d; font-size: .85rem; margin: 1rem 0 2rem; }
</style>

<div class="sl-banner">
  <strong><i class="fas fa-link me-2" aria-hidden="true"></i><?php echo __('Shared record'); ?></strong>
  &middot; <?php echo __('Shared by %1%', ['%1%' => $e($sf_data->getRaw('issuerName'))]); ?>
  &middot; <?php echo __('Link expires %1%', ['%1%' => $e($expires)]); ?>
</div>

<div class="sl-card">
  <h1><?php echo $e($r['title']); ?></h1>
  <div class="row g-4">
    <div class="<?php echo $r['image'] ? 'col-md-7' : 'col-12'; ?>">
      <?php if ($facts): ?>
        <dl class="sl-facts row mb-3">
          <?php foreach ($facts as $label => $value): ?>
            <dt class="col-sm-4"><?php echo $e($label); ?></dt>
            <dd class="col-sm-8"><?php echo $e($value); ?></dd>
          <?php endforeach; ?>
        </dl>
      <?php endif; ?>
      <h2 class="h5"><?php echo __('Scope and content'); ?></h2>
      <?php if (!empty($r['scope'])): ?>
        <div class="sl-scope"><?php echo $e($r['scope']); ?></div>
      <?php else: ?>
        <p class="text-muted"><?php echo __('No scope and content recorded.'); ?></p>
      <?php endif; ?>
      <?php if (!empty($r['imageWithheld'])): ?>
        <p class="text-muted mt-3"><i class="fas fa-eye-slash me-1" aria-hidden="true"></i><?php echo __('The image of this record is not shown: its rights do not allow display.'); ?></p>
      <?php endif; ?>
    </div>
    <?php if ($r['image']): ?>
      <div class="col-md-5 sl-image">
        <img src="<?php echo $e($r['image']); ?>" alt="<?php echo $e(__('Reference image of %1%', ['%1%' => $r['title']])); ?>">
      </div>
    <?php endif; ?>
  </div>
</div>

<p class="sl-footer"><?php echo __('This is a time-limited share link. It stops working on %1%. Every visit is logged.', ['%1%' => $e($expires)]); ?></p>
