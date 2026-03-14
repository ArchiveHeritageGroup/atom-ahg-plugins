<?php
/**
 * IIIF Comparison Viewer
 * Uses Mirador in mosaic workspace mode to compare multiple manifests side-by-side.
 *
 * @var array $manifests  Array of manifest URLs
 * @var string $baseUrl
 * @var string $pluginPath
 */
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
?>
<style<?php echo $nonceAttr; ?>>
  html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; background: #1e1e1e; }
  #mirador-compare { position: absolute; top: 0; left: 0; right: 0; bottom: 0; }
  .compare-loading {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.9); display: flex; flex-direction: column;
    justify-content: center; align-items: center; z-index: 9999;
    transition: opacity 0.3s;
  }
  .compare-loading.hidden { opacity: 0; pointer-events: none; }
  .compare-spinner { width: 50px; height: 50px; border: 5px solid #333; border-top-color: #fff; border-radius: 50%; animation: spin 1s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>

<div class="compare-loading" id="compare-loading">
  <div class="compare-spinner"></div>
  <div style="color:white;margin-top:15px;font-family:Arial,sans-serif;">Loading comparison view...</div>
</div>

<div id="mirador-compare"></div>

<link rel="stylesheet" href="<?php echo $pluginPath; ?>/public/mirador/mirador.min.css">
<script src="<?php echo $pluginPath; ?>/public/mirador/mirador.min.js"<?php echo $nonceAttr; ?>></script>
<script<?php echo $nonceAttr; ?>>
(function() {
  var manifests = <?php echo json_encode(array_values($manifests), JSON_UNESCAPED_SLASHES); ?>;

  var windows = manifests.map(function(url) {
    return { manifestId: url, canvasIndex: 0 };
  });

  Mirador.viewer({
    id: 'mirador-compare',
    windows: windows,
    window: {
      allowClose: true,
      allowMaximize: true,
      allowFullscreen: true,
      sideBarOpenByDefault: false
    },
    workspace: {
      showZoomControls: true,
      type: 'mosaic',
      allowNewWindows: true
    },
    workspaceControlPanel: { enabled: true },
    catalog: manifests.map(function(url) { return { manifestId: url }; })
  });

  setTimeout(function() {
    document.getElementById('compare-loading').classList.add('hidden');
  }, 2500);
})();
</script>
