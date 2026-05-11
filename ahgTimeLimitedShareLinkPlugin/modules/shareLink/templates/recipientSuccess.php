<?php
/** @var string $title */
/** @var ?string $scopeAndContent */
/** @var ?string $identifier */
/** @var int $informationObjectId */
/** @var string $issuerName */
/** @var string $expiresAt */
/** @var stdClass $tokenRow */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="referrer" content="no-referrer">
<title><?php echo esc_entities($title) ?> · Shared record</title>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f7f7f8; color: #212529; }
  .sl-banner { background: #0d6efd; color: white; padding: .75rem 1.25rem; }
  .sl-banner a { color: #fff; text-decoration: underline; }
  .sl-content { max-width: 800px; margin: 1.5rem auto; padding: 1.5rem 2rem; background: #fff; border-radius: .375rem; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
  .sl-content h1 { margin-top: 0; }
  .sl-meta { color: #6c757d; font-size: .9rem; margin-bottom: 1.25rem; }
  .sl-meta code { font-family: monospace; }
  .sl-scope { white-space: pre-wrap; line-height: 1.5; }
  .sl-footer { max-width: 800px; margin: 1.5rem auto; padding: 1rem 2rem; color: #6c757d; font-size: .85rem; }
</style>
</head>
<body>

<div class="sl-banner">
    <strong><i class="fas fa-link me-2"></i>Shared record</strong> ·
    Shared by <?php echo esc_entities($issuerName) ?> ·
    Link expires <?php echo esc_entities($expiresAt) ?>
</div>

<div class="sl-content">
    <h1><?php echo esc_entities($title) ?></h1>
    <?php if ($identifier): ?>
        <div class="sl-meta">Reference code: <code><?php echo esc_entities($identifier) ?></code></div>
    <?php endif ?>

    <?php if ($scopeAndContent): ?>
        <h2>Scope and content</h2>
        <div class="sl-scope"><?php echo nl2br(esc_entities($scopeAndContent)) ?></div>
    <?php else: ?>
        <p class="text-muted">No descriptive scope or content recorded.</p>
    <?php endif ?>
</div>

<div class="sl-footer">
    This is a time-limited share link. The link will stop working on <?php echo esc_entities($expiresAt) ?>.
    All accesses are logged for audit purposes.
</div>

</body>
</html>
