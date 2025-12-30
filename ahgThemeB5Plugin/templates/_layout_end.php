<?php include(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/ahgSettings/templates/_dynamicStyles.php'); ?>

<!-- D3.js for visualizations -->
<script src="https://d3js.org/d3.v7.min.js"></script>

<!-- AHG Media Player Scripts -->
<script src="/plugins/ahgThemeB5Plugin/js/atom-media-player.js"></script>
<script src="/plugins/ahgThemeB5Plugin/js/media-controls.js"></script>

</body>
</html>

<style>
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

<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
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
<script src="/plugins/ahgThemeB5Plugin/js/levelSectorFilter.js"></script>
