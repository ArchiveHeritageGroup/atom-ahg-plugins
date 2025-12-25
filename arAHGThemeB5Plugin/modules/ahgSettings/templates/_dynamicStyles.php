<?php
/**
 * Dynamic Theme CSS Variables
 * Sets CSS custom properties from ahg_settings database
 */

$settings = [];
try {
    if (!defined('ATOM_FRAMEWORK_DB_INITIALIZED')) {
        $bootstrapPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrapPath)) {
            require_once $bootstrapPath;
        }
    }
    
    if (class_exists('Illuminate\Database\Capsule\Manager')) {
        $rows = \Illuminate\Database\Capsule\Manager::table('ahg_settings')
            ->where('setting_group', 'general')
            ->get();
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
    }
} catch (Exception $e) {
    // Use defaults
}

// Extract values with defaults
$primary = $settings['ahg_primary_color'] ?? '#005837';
$secondary = $settings['ahg_secondary_color'] ?? '#37A07F';
$cardHeaderBg = $settings['ahg_card_header_bg'] ?? '#005837';
$cardHeaderText = $settings['ahg_card_header_text'] ?? '#ffffff';
$buttonBg = $settings['ahg_button_bg'] ?? '#005837';
$buttonText = $settings['ahg_button_text'] ?? '#ffffff';
$linkColor = $settings['ahg_link_color'] ?? '#005837';
$sidebarBg = $settings['ahg_sidebar_bg'] ?? '#f8f9fa';
$sidebarText = $settings['ahg_sidebar_text'] ?? '#333333';

// Helper function
if (!function_exists('ahgHexToRgba')) {
    function ahgHexToRgba($hex, $alpha = 1) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) return "rgba(0,88,55,$alpha)";
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "rgba($r,$g,$b,$alpha)";
    }
}
?>
<style id="ahg-theme-variables">
:root {
    --ahg-primary: <?= $primary ?>;
    --ahg-secondary: <?= $secondary ?>;
    --ahg-card-header-bg: <?= $cardHeaderBg ?>;
    --ahg-card-header-text: <?= $cardHeaderText ?>;
    --ahg-btn-bg: <?= $buttonBg ?>;
    --ahg-btn-text: <?= $buttonText ?>;
    --ahg-link-color: <?= $linkColor ?>;
    --ahg-sidebar-bg: <?= $sidebarBg ?>;
    --ahg-sidebar-text: <?= $sidebarText ?>;
    --ahg-input-focus: <?= ahgHexToRgba($primary, 0.25) ?>;
}

/* Apply to Bootstrap components */
.card-header {
    background-color: var(--ahg-card-header-bg) !important;
    color: var(--ahg-card-header-text) !important;
}
.card-header * { color: var(--ahg-card-header-text) !important; }

.btn-primary {
    background-color: var(--ahg-btn-bg) !important;
    border-color: var(--ahg-btn-bg) !important;
    color: var(--ahg-btn-text) !important;
}
.btn-primary:hover, .btn-primary:focus {
    filter: brightness(0.9);
}

a:not(.btn):not(.nav-link):not(.dropdown-item) {
    color: var(--ahg-link-color);
}
</style>
