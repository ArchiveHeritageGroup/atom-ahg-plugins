<?php
// Include dynamic CSS variables from AHG Settings
$dynamicStylesPath = sfConfig::get('sf_plugins_dir').'/ahgSettingsPlugin/modules/ahgSettings/templates/_dynamicStyles.php';
if (file_exists($dynamicStylesPath)) {
    include($dynamicStylesPath);
}
?>
<!-- D3.js for visualizations -->
<script src="/plugins/ahgThemeB5Plugin/web/js/d3.v7.min.js"></script>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
/* Override mediaelement CSS that hides native video controls */
.ahg-media-player video::-webkit-media-controls,
.ahg-media-player video::-webkit-media-controls-panel,
.ahg-media-player video::-webkit-media-controls-start-playback-button,
#content video::-webkit-media-controls,
video.ahg-native-video::-webkit-media-controls {
    -webkit-appearance: media-controls-container !important;
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
}
</style>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Force show native video controls
document.addEventListener('DOMContentLoaded', function() {
    var videos = document.querySelectorAll('.ahg-media-player video');
    videos.forEach(function(video) {
        video.setAttribute('controls', 'controls');
        video.controls = true;
        // Remove any inline styles that might hide controls
        video.style.setProperty('pointer-events', 'auto', 'important');
    });
});
</script>

<?php // Voice Commands - only load the UI, styles and engine when the feature
      // is switched on (Admin > AHG Settings > Voice AI). Otherwise nothing
      // loads, so there is no mic icon and no speech/listening at all. The
      // client-side getSettings self-disable was unreliable (the engine could
      // start before it ran), so gate it server-side. ?>
<?php if (\AtomExtensions\Services\AhgSettingsService::getBool('voice_enabled', false)) { ?>
  <link rel="stylesheet" href="/plugins/ahgThemeB5Plugin/css/voiceCommands.css" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>

  <?php include(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/templates/_voiceCommands.php'); ?>

  <script src="/plugins/ahgThemeB5Plugin/js/voiceCommandRegistry.js?v=<?php echo time(); ?>" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
  <script src="/plugins/ahgThemeB5Plugin/js/voiceCommandTranslations.js?v=<?php echo time(); ?>" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
  <script src="/plugins/ahgThemeB5Plugin/js/voiceCommands.js?v=<?php echo time(); ?>" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
<?php } ?>

<?php // Theme overrides (custom.css). The webpack bundle is the only theme
      // stylesheet the layout emits, so custom.css was never loaded and every
      // override in it - including the record-view carousel styling added in
      // v3.88.10 - had no effect on any page. Load it after the bundle so the
      // overrides win. ?>
<link rel="stylesheet" href="/plugins/ahgThemeB5Plugin/web/css/custom.css?v=3.88.19" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>

<?php // Online help (ahgHelpPlugin). The plugin registers these through
      // $response->addJavascript()/addStylesheet(), but this theme never calls
      // include_javascripts()/include_stylesheets(), so they were never emitted:
      // contextual help, F1 and help-page search were all dead. Load them here
      // directly, the same way the voice assets are loaded above.
      // help-chatbot.js is deliberately not loaded - it adds a floating chat
      // widget to every page, which is a separate decision. ?>
<?php if (in_array('ahgHelpPlugin', sfProjectConfiguration::getActive()->getPlugins())) { ?>
  <link rel="stylesheet" href="/plugins/ahgHelpPlugin/css/help.css?v=1.1.0" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>

  <script src="/plugins/ahgHelpPlugin/js/help-context.js?v=1.1.0" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
  <script src="/plugins/ahgHelpPlugin/js/help-search.js?v=1.1.0" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
<?php } ?>

</body>
</html>
