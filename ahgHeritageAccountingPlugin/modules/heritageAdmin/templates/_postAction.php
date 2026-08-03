<?php

/**
 * Render a state-changing heritageAdmin action as a POST form (#262).
 *
 * These were plain <a href> links, so an administrator following a crafted link
 * - or loading a page with an <img src> pointing at one - deleted a standard,
 * a compliance rule or a regional configuration. The actions now require POST
 * plus a valid CSRF token; this partial produces a matching form.
 *
 * Confirmation is wired through a data attribute rather than an inline onsubmit
 * handler, because CSP blocks inline event handlers once it is enforced (#248).
 * Bind it once per template with _postActionScript.
 *
 * Parameters:
 *   action   string  heritageAdmin action name (required)
 *   params   array   extra route parameters, e.g. ['id' => 5]
 *   class    string  CSS classes for the button
 *   icon     string  Font Awesome classes, e.g. 'fas fa-trash'
 *   label    string  visible text (optional; icon-only buttons need `title`)
 *   title    string  tooltip / accessible name
 *   confirm  string  confirmation prompt; omit for no confirmation
 */
$url = url_for(array_merge(
    ['module' => 'heritageAdmin', 'action' => $action],
    $params ?? []
));

$token = class_exists('\AtomFramework\Services\CsrfService')
    ? \AtomFramework\Services\CsrfService::generateToken()
    : '';
?>
<form method="post" action="<?php echo $url; ?>" class="d-inline"
    <?php if (!empty($confirm)): ?>data-confirm="<?php echo htmlspecialchars($confirm, ENT_QUOTES); ?>"<?php endif; ?>>
  <input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES); ?>">
  <button type="submit" class="<?php echo $class ?? 'btn btn-sm btn-outline-secondary'; ?>"
      <?php if (!empty($title)): ?>title="<?php echo htmlspecialchars($title, ENT_QUOTES); ?>"<?php endif; ?>>
    <?php if (!empty($icon)): ?><i class="<?php echo $icon; ?>" aria-hidden="true"></i><?php endif; ?>
    <?php echo $label ?? ''; ?>
  </button>
</form>
