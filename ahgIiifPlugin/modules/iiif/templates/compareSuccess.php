<?php
/*
 * IIIF comparison - Mirador in mosaic mode, mounted in its own document.
 *
 * WHY AN IFRAME RATHER THAN THIS PAGE
 *
 * Mirador 3 is React with emotion: it injects its Material-UI styles into the
 * document at runtime. AtoM's Content Security Policy is `style-src 'self'
 * 'nonce-...'` with no 'unsafe-inline', and a nonce cannot cover a stylesheet
 * the page writes after load - so every one of those injected rules is dropped.
 *
 * The result is not an error. Mirador mounts, fetches the manifest, fetches
 * tiles, and draws an unstyled workspace: black rectangles where the windows,
 * toolbars and thumbnails should be. Nothing fails, so nothing is reported.
 * That is what this page did, and the manifest and tile requests all returned
 * 200 the whole time.
 *
 * web/public/mirador/compare.html is a static file served by the web server
 * rather than rendered by AtoM, so it carries no CSP header and Mirador may
 * style itself. It already accepts repeated ?manifest= parameters. That is the
 * same reason ahgMiradorPlugin mounts its viewer in an iframe.
 *
 * @var array  $manifests   manifest URLs
 * @var string $baseUrl
 * @var string $pluginPath
 */
decorate_with(false);

// Unwrap the output escaper before touching it: Symfony hands templates their
// variables wrapped, so this is an object rather than an array, and array-
// functions on it are a fatal on PHP 8 - which previously truncated this
// response mid-script.
$manifestUrls = $manifests instanceof sfOutputEscaperArrayDecorator
    ? $manifests->getRawValue()
    : (array) $manifests;

$query = implode('&', array_map(
    static fn ($url) => 'manifest='.rawurlencode((string) $url),
    array_values($manifestUrls)
));

$src = $pluginPath.'/public/mirador/compare.html?'.$query;

$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? ' '.preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
?>
<!DOCTYPE html>
<html lang="<?php echo sfContext::getInstance()->getUser()->getCulture(); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo __('IIIF comparison'); ?></title>
<style<?php echo $nonceAttr; ?>>
  html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; background: #1e1e1e; }
  iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; display: block; }
</style>
</head>
<body>
<iframe src="<?php echo esc_specialchars($src); ?>" title="<?php echo __('IIIF comparison'); ?>"></iframe>
</body>
</html>
