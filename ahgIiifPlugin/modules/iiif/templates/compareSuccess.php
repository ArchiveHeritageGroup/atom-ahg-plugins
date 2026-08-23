<?php
/*
 * IIIF comparison - Mirador in mosaic mode, rendered by this page.
 *
 * WHY THIS NO LONGER USES AN IFRAME
 *
 * It used to embed web/public/mirador/compare.html, on the reasoning that a
 * static file carries no CSP header and so Mirador may style itself. Two things
 * were wrong with that.
 *
 * First, it did not work. Stock AtoM's nginx serves only
 * a fixed list of static extensions under /plugins (css, png, jpg, js, svg,
 * ico, gif, pdf, woff, woff2, otf, ttf), so a .html file anywhere in the
 * plugin tree is 403 Forbidden on every standard install -
 * while mirador.min.js in the same directory loads perfectly. The iframe was
 * pointing at a forbidden URL.
 *
 * Second, the premise was false. A nonce cannot be attached to a stylesheet the
 * page writes after load - but Mirador does not need one attached from outside.
 * Mirador 3 styles itself through Material-UI's JSS, and JSS reads
 * <meta property="csp-nonce"> and stamps that value onto every sheet it creates.
 * With the tag present, Mirador styles itself correctly under a strict policy.
 * That is what fixed the embedded viewer, and it works here for the same reason.
 *
 * The selector is JSS's, not ours: it must be exactly meta[property="csp-nonce"].
 *
 * @var array  $manifests   manifest URLs
 * @var string $baseUrl
 * @var string $pluginPath
 */
decorate_with(false);

// Unwrap the output escaper before touching it: Symfony hands templates their
// variables wrapped, so this is an object rather than an array, and array
// functions on it are a fatal on PHP 8 - which previously truncated this
// response mid-script.
$manifestUrls = $manifests instanceof sfOutputEscaperArrayDecorator
    ? $manifests->getRawValue()
    : (array) $manifests;

$manifestUrls = array_values(array_filter(array_map('strval', $manifestUrls)));

$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? ' '.preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
$nonceValue = $n ? trim(preg_replace('/^nonce=/', '', $n), '"\'') : '';
?>
<!DOCTYPE html>
<html lang="<?php echo sfContext::getInstance()->getUser()->getCulture(); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php if ('' !== $nonceValue) { ?>
  <?php // JSS looks for exactly this tag and nonces every sheet Mirador creates. ?>
  <meta property="csp-nonce" content="<?php echo esc_specialchars($nonceValue); ?>">
<?php } ?>
<title><?php echo __('IIIF comparison'); ?></title>
<style<?php echo $nonceAttr; ?>>
  html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; background: #1e1e1e; }
  #mirador-compare { position: absolute; top: 0; left: 0; right: 0; bottom: 0; }
  .iiif-compare-message { color: #fff; font-family: Arial, Helvetica, sans-serif; padding: 2rem; }
</style>
</head>
<body>

<?php if (!$manifestUrls) { ?>
  <div class="iiif-compare-message">
    <h1><?php echo __('IIIF comparison'); ?></h1>
    <p><?php echo __('No manifests were supplied to compare.'); ?></p>
  </div>
<?php } else { ?>

  <div id="mirador-compare"></div>

  <script src="<?php echo esc_specialchars($pluginPath); ?>/public/mirador/mirador.min.js"<?php echo $nonceAttr; ?>></script>
  <script<?php echo $nonceAttr; ?>>
  (function () {
      var manifests = <?php echo json_encode($manifestUrls, JSON_UNESCAPED_SLASHES); ?>;

      if (!window.Mirador || !window.Mirador.viewer) {
          document.getElementById('mirador-compare').innerHTML =
              '<div class="iiif-compare-message">Mirador failed to load.</div>';
          return;
      }

      Mirador.viewer({
          id: 'mirador-compare',
          windows: manifests.map(function (url) {
              return { manifestId: url, canvasIndex: 0 };
          }),
          window: {
              allowClose: true,
              allowMaximize: true,
              allowFullscreen: true,
              sideBarOpenByDefault: false,
              // Comparison is where annotations matter most - the reason to put
              // two images side by side is usually to point at something in both.
              annotations: true
          },
          workspace: {
              showZoomControls: true,
              // Mosaic is correct here, unlike the embedded single-manifest
              // viewer: comparison exists to show several windows at once.
              type: 'mosaic',
              allowNewWindows: true
          },
          workspaceControlPanel: {
              enabled: true
          },
          catalog: manifests.map(function (url) {
              return { manifestId: url };
          })
      });
  })();
  </script>

<?php } ?>

</body>
</html>
