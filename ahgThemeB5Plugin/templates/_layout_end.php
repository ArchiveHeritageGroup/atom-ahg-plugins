<?php
// Include dynamic CSS variables from AHG Settings
$dynamicStylesPath = sfConfig::get('sf_plugins_dir').'/ahgSettingsPlugin/modules/ahgSettings/templates/_dynamicStyles.php';
if (file_exists($dynamicStylesPath)) {
    include($dynamicStylesPath);
}
?>
<!-- D3.js for visualizations -->
<script src="https://d3js.org/d3.v7.min.js"></script>
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

<?php // Voice Commands CSS ?>
<link rel="stylesheet" href="/plugins/ahgThemeB5Plugin/css/voiceCommands.css">

<?php // Voice Commands UI (partial) ?>
<?php include(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/templates/_voiceCommands.php'); ?>

<?php // Voice Commands JS ?>
<script src="/plugins/ahgThemeB5Plugin/js/voiceCommandRegistry.js"></script>
<script src="/plugins/ahgThemeB5Plugin/js/voiceCommands.js"></script>
</body>
</html>
