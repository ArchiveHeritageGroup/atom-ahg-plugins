<?php

namespace AhgShareLink\Listeners;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ViewLinkInjector — injects a "Share record" button + modal on the legacy
 * information_object view page. Hooks `response.filter_content`.
 *
 * The injection only fires when:
 *   - It's an IO view (canonical informationobject or a descriptive-standard
 *     plugin module: sfIsadPlugin, sfRadPlugin, sfDcPlugin, sfModsPlugin, sfDacsPlugin)
 *   - Request method is GET
 *   - Response Content-Type starts with text/html
 *   - User is authenticated AND has share_link.create ACL
 *
 * The injected modal posts to /shareLink/issue and on success displays the
 * generated public URL with a copy-to-clipboard button. Bootstrap 5 modal
 * markup is used (Heratio theme is Bootstrap 5).
 *
 * @phase E
 */
final class ViewLinkInjector
{
    private const MODULE_MAP = [
        'informationobject' => true,
        'sfIsadPlugin'      => true,
        'sfRadPlugin'       => true,
        'sfDcPlugin'        => true,
        'sfModsPlugin'      => true,
        'sfDacsPlugin'      => true,
    ];

    private const VIEW_ACTIONS = ['index', 'view', 'show'];

    public function onResponseFilterContent(\sfEvent $event, string $content): string
    {
        try {
            $modified = $this->maybeInject($content);
            return $modified ?? $content;
        } catch (\Throwable $e) {
            error_log('ahgTimeLimitedShareLinkPlugin ViewLinkInjector error: ' . $e->getMessage());
            return $content;
        }
    }

    private function maybeInject(string $content): ?string
    {
        // Idempotency guard: response.filter_content can fire more than once per
        // request, which would inject the share button/modal twice. Never inject
        // if the modal is already present in the content.
        if (str_contains($content, 'ahgShareLinkModal')) {
            return null;
        }
        if (!\sfContext::hasInstance()) {
            return null;
        }
        $context = \sfContext::getInstance();
        $request = $context->getRequest();
        $module  = $context->getModuleName();
        $action  = $context->getActionName();

        if (!isset(self::MODULE_MAP[$module])) {
            return null;
        }
        if (!in_array($action, self::VIEW_ACTIONS, true)) {
            return null;
        }
        if (!$request->isMethod('GET') || $request->isXmlHttpRequest()) {
            return null;
        }
        $response = $context->getResponse();
        $contentType = method_exists($response, 'getContentType') ? $response->getContentType() : '';
        if ($contentType !== '' && !str_starts_with($contentType, 'text/html')) {
            return null;
        }

        $user = $context->getUser();
        if (!$user || !$user->isAuthenticated()) {
            return null;
        }
        $userId = (int) $user->getUserID();
        if ($userId <= 0) {
            return null;
        }

        if (!$this->userCanCreate($userId)) {
            return null;
        }

        $entityId = $this->resolveEntityId($request);
        if ($entityId === null || $entityId <= 0) {
            return null;
        }

        $issueUrl = \url_for(['module' => 'shareLink', 'action' => 'issue']);
        $modal = $this->buildModal($entityId, $issueUrl);

        $patterns = [
            '#(<div[^>]+id=["\']main-column["\'][^>]*>)#i',
            '#(<main[^>]*>)#i',
            '#(<div[^>]+class=["\'][^"\']*content[^"\']*["\'][^>]*>)#i',
        ];
        foreach ($patterns as $p) {
            $new = preg_replace($p, '$1' . $modal, $content, 1, $count);
            if ($count > 0 && is_string($new)) {
                return $new;
            }
        }
        return null;
    }

    private function userCanCreate(int $userId): bool
    {
        require_once __DIR__ . '/../Services/AclCheck.php';
        try {
            return (new \AhgShareLink\Services\AclCheck())
                ->canUserDo($userId, \AhgShareLink\Services\AclCheck::ACTION_CREATE);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolveEntityId(\sfWebRequest $request): ?int
    {
        $id = $request->getParameter('id');
        if (is_numeric($id) && (int) $id > 0) {
            return (int) $id;
        }
        $slug = $request->getParameter('slug');
        if (is_string($slug) && $slug !== '') {
            try {
                $row = DB::table('slug')->where('slug', $slug)->first();
            } catch (\Throwable $e) {
                return null;
            }
            if ($row && !empty($row->object_id)) {
                return (int) $row->object_id;
            }
        }
        return null;
    }

    private function buildModal(int $entityId, string $issueUrl): string
    {
        $n = $this->cspNonceAttr();
        $defaultExpiry = (new \DateTimeImmutable('+14 days'))->format('Y-m-d');
        $maxExpiry = (new \DateTimeImmutable('+90 days'))->format('Y-m-d');
        $issueUrlEsc = htmlspecialchars($issueUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Button banner
        $banner = '<div class="ahg-share-link-banner mb-2">'
            . '<button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ahgShareLinkModal">'
            . '<i class="fas fa-share-alt me-1"></i>' . htmlspecialchars(\__('Share this record'), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . '</button></div>';

        // Modal HTML
        $tShare = htmlspecialchars(\__('Share record'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tClose = htmlspecialchars(\__('Close'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tCancel = htmlspecialchars(\__('Cancel'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tCreate = htmlspecialchars(\__('Create share link'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tExpires = htmlspecialchars(\__('Expires on'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tEmail = htmlspecialchars(\__('Recipient email'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tNote = htmlspecialchars(\__('Note for recipient'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tMax = htmlspecialchars(\__('Max visits'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tCopy = htmlspecialchars(\__('Copy link'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tCopied = htmlspecialchars(\__('Copied'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tHelp = htmlspecialchars(\__('Anyone with this link can view the record until it expires.'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tMaxNote = htmlspecialchars(\__('Maximum expiry is 90 days unless your account has an extended-expiry permission.'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $modal = <<<HTML
<div class="modal fade" id="ahgShareLinkModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-share-alt me-1"></i>{$tShare}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{$tClose}"></button>
      </div>
      <div class="modal-body">
        <form id="ahgShareLinkForm" data-issue-url="{$issueUrlEsc}" data-io-id="{$entityId}">
          <div class="mb-3">
            <label for="ahgShareExpiresAt" class="form-label">{$tExpires}</label>
            <input type="date" class="form-control" id="ahgShareExpiresAt" name="expires_at" value="{$defaultExpiry}" max="{$maxExpiry}" required>
            <div class="form-text">{$tMaxNote}</div>
          </div>
          <div class="mb-3">
            <label for="ahgShareEmail" class="form-label">{$tEmail}</label>
            <input type="email" class="form-control" id="ahgShareEmail" name="recipient_email" placeholder="name@example.com">
          </div>
          <div class="mb-3">
            <label for="ahgShareNote" class="form-label">{$tNote}</label>
            <textarea class="form-control" id="ahgShareNote" name="recipient_note" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label for="ahgShareMax" class="form-label">{$tMax}</label>
            <input type="number" min="1" class="form-control" id="ahgShareMax" name="max_access">
          </div>
          <div id="ahgShareLinkAlert" class="alert d-none" role="alert"></div>
          <div id="ahgShareLinkResult" class="d-none">
            <div class="input-group">
              <input type="text" class="form-control" id="ahgShareLinkUrl" readonly>
              <button class="btn btn-outline-secondary" type="button" id="ahgShareLinkCopy">
                <i class="fas fa-copy me-1"></i><span>{$tCopy}</span>
              </button>
            </div>
            <div class="form-text mt-2">{$tHelp}</div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{$tCancel}</button>
        <button type="button" class="btn btn-primary" id="ahgShareLinkSubmit">{$tCreate}</button>
      </div>
    </div>
  </div>
</div>
<script {$n}>
(function () {
  if (window.ahgShareLinkInit) { return; } window.ahgShareLinkInit = true;
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('ahgShareLinkForm');
    if (!form) return;
    var submit = document.getElementById('ahgShareLinkSubmit');
    var alertEl = document.getElementById('ahgShareLinkAlert');
    var resultEl = document.getElementById('ahgShareLinkResult');
    var urlEl = document.getElementById('ahgShareLinkUrl');
    var copyBtn = document.getElementById('ahgShareLinkCopy');
    var ioId = form.getAttribute('data-io-id');
    var issueUrl = form.getAttribute('data-issue-url');

    function showError(msg) {
      alertEl.className = 'alert alert-danger';
      alertEl.textContent = msg;
      alertEl.classList.remove('d-none');
    }
    function reset() {
      alertEl.classList.add('d-none');
      alertEl.textContent = '';
      resultEl.classList.add('d-none');
      urlEl.value = '';
    }

    submit.addEventListener('click', function () {
      reset();
      var data = new FormData(form);
      data.append('information_object_id', ioId);
      submit.disabled = true;
      fetch(issueUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: data
      })
      .then(function (r) { return r.json().then(function (j) { return { status: r.status, body: j }; }); })
      .then(function (resp) {
        submit.disabled = false;
        if (!resp.body || resp.body.ok !== true) {
          var msg = resp.body && resp.body.error && resp.body.error.message
            ? resp.body.error.message
            : ('Error ' + resp.status);
          showError(msg);
          return;
        }
        urlEl.value = resp.body.public_url || '';
        resultEl.classList.remove('d-none');
      })
      .catch(function (e) {
        submit.disabled = false;
        showError(e && e.message ? e.message : 'Network error');
      });
    });

    copyBtn.addEventListener('click', function () {
      if (!urlEl.value) return;
      urlEl.select();
      try { document.execCommand('copy'); } catch (e) {}
      var span = copyBtn.querySelector('span');
      if (span) {
        var prev = span.textContent;
        span.textContent = '{$tCopied}';
        setTimeout(function () { span.textContent = prev; }, 1500);
      }
    });
  });
})();
</script>
HTML;

        return $banner . $modal;
    }

    private function cspNonceAttr(): string
    {
        $n = \sfConfig::get('csp_nonce', '');
        if (!is_string($n) || $n === '') {
            return '';
        }
        // sfConfig stores it as `nonce=<hex>` — we need `nonce="<hex>"`.
        if (str_starts_with($n, 'nonce=')) {
            return 'nonce="' . htmlspecialchars(substr($n, 6), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
        }
        return 'nonce="' . htmlspecialchars($n, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
    }
}
