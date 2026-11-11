<?php
/** @var \AhgShareLink\Services\AccessResult $result */
/** @var ?string $reason */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="referrer" content="no-referrer">
<title>Share link unavailable</title>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f7f7f8; color: #212529; }
  .sl-card { max-width: 540px; margin: 4rem auto; padding: 2rem; background: #fff; border-radius: .375rem; box-shadow: 0 1px 3px rgba(0,0,0,.05); text-align: center; }
  .sl-card .icon { font-size: 3rem; color: #dc3545; margin-bottom: 1rem; }
  .sl-card h1 { font-size: 1.5rem; margin: 0 0 1rem; }
  .sl-card p { color: #6c757d; line-height: 1.5; }
</style>
</head>
<body>
<div class="sl-card">
    <div class="icon">⚠</div>
    <h1>Share link unavailable</h1>
    <p><?php echo esc_entities($reason ?? 'This share link is no longer valid.') ?></p>
    <?php // The status code and log code are for staff (the link's access log), not the visitor. ?>
    <p class="text-muted small"><?php echo __('If you still need this record, ask the person who sent you the link.') ?></p>
</div>
</body>
</html>
