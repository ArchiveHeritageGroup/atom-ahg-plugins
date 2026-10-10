<?php

use AtomFramework\Http\Controllers\AhgApiController;

/**
 * GET /api/v2/docs — Swagger-UI rendering of /api/v2/openapi.json (#129).
 * Public, HTML. Loads Swagger-UI from jsDelivr (whitelisted in app.yml CSP);
 * the inline init carries the CSP nonce.
 */
class apiv2DocsAction extends AhgApiController
{
    public function GET($request)
    {
        $n = \sfConfig::get('csp_nonce', '');
        $nonce = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
        $this->getResponse()->setHttpHeader('Content-Type', 'text/html; charset=utf-8');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AtoM Heratio API — v2 reference</title>
  <link rel="stylesheet" href="/plugins/ahgThemeB5Plugin/web/css/swagger-ui.css">
</head>
<body>
  <div style="background:#10373E;color:#F2EDE6;padding:10px 20px;font:15px Arial,sans-serif">Requests need an API key in the X-API-Key header. <a href="/api/keys" style="color:#E1B34F;font-weight:bold">Get or manage your API keys</a></div>
  <div id="swagger-ui"></div>
  <script src="/plugins/ahgThemeB5Plugin/web/js/swagger-ui-bundle.js"></script>
  <script{$nonce}>
    window.onload = function () {
      window.ui = SwaggerUIBundle({
        url: '/api/v2/openapi', // the route has no .json; that path 404s
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [SwaggerUIBundle.presets.apis]
      });
    };
  </script>
</body>
</html>
HTML;

        return $this->renderText($html);
    }
}
