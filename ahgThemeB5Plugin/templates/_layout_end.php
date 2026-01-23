<?php include(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/ahgSettings/templates/_dynamicStyles.php'); ?>
<!-- D3.js for visualizations -->
<script src="https://d3js.org/d3.v7.min.js"></script>
<!-- AHG Media Player Scripts -->
<script src="/plugins/ahgCorePlugin/js/vendor/atom-media-player.js"></script>
<script src="/plugins/ahgCorePlugin/js/vendor/media-controls.js"></script>
<?php // Plugin protection script for admin pages ?>
<?php if ($sf_user->isAuthenticated() && (strpos($_SERVER['REQUEST_URI'] ?? '', 'ahg-settings/plugins') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', 'sfPluginAdminPlugin') !== false)): ?>
<script src="/plugins/ahgCorePlugin/js/vendor/plugin-protection.js"></script>
<?php endif; ?>
</body>
</html>
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
<script src="/plugins/ahgCorePlugin/js/vendor/levelSectorFilter.js"></script>
