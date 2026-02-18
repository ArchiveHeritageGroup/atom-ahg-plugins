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

// Extract values with defaults - Primary theme colors
$primary = $settings['ahg_primary_color'] ?? '#005837';
$secondary = $settings['ahg_secondary_color'] ?? '#37A07F';
$cardHeaderBg = $settings['ahg_card_header_bg'] ?? '#005837';
$cardHeaderText = $settings['ahg_card_header_text'] ?? '#ffffff';
$buttonBg = $settings['ahg_button_bg'] ?? '#005837';
$buttonText = $settings['ahg_button_text'] ?? '#ffffff';
$linkColor = $settings['ahg_link_color'] ?? '#005837';
$sidebarBg = $settings['ahg_sidebar_bg'] ?? '#f8f9fa';
$sidebarText = $settings['ahg_sidebar_text'] ?? '#333333';

// Extended theme colors (Bootstrap-compatible)
$success = $settings['ahg_success_color'] ?? '#28a745';
$warning = $settings['ahg_warning_color'] ?? '#ffc107';
$danger = $settings['ahg_danger_color'] ?? '#dc3545';
$info = $settings['ahg_info_color'] ?? '#17a2b8';
$light = $settings['ahg_light_color'] ?? '#f8f9fa';
$dark = $settings['ahg_dark_color'] ?? '#343a40';
$muted = $settings['ahg_muted_color'] ?? '#6c757d';
$borderColor = $settings['ahg_border_color'] ?? '#dee2e6';
$bodyBg = $settings['ahg_body_bg'] ?? '#ffffff';
$bodyText = $settings['ahg_body_text'] ?? '#212529';

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
<style id="ahg-theme-variables" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
:root {
    /* Primary theme colors */
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

    /* Extended colors (Bootstrap-compatible) */
    --ahg-success: <?= $success ?>;
    --ahg-warning: <?= $warning ?>;
    --ahg-danger: <?= $danger ?>;
    --ahg-info: <?= $info ?>;
    --ahg-light: <?= $light ?>;
    --ahg-dark: <?= $dark ?>;
    --ahg-muted: <?= $muted ?>;
    --ahg-border: <?= $borderColor ?>;
    --ahg-body-bg: <?= $bodyBg ?>;
    --ahg-body-text: <?= $bodyText ?>;

    /* Computed variants */
    --ahg-primary-rgb: <?= implode(',', array_map('hexdec', str_split(ltrim($primary, '#'), 2))) ?>;
    --ahg-secondary-rgb: <?= implode(',', array_map('hexdec', str_split(ltrim($secondary, '#'), 2))) ?>;
    --ahg-success-rgb: <?= implode(',', array_map('hexdec', str_split(ltrim($success, '#'), 2))) ?>;
    --ahg-danger-rgb: <?= implode(',', array_map('hexdec', str_split(ltrim($danger, '#'), 2))) ?>;
    --ahg-warning-rgb: <?= implode(',', array_map('hexdec', str_split(ltrim($warning, '#'), 2))) ?>;
    --ahg-info-rgb: <?= implode(',', array_map('hexdec', str_split(ltrim($info, '#'), 2))) ?>;

    /* Webpack bundle variable aliases — sync with dynamic values */
    --ahg-primary-color: <?= $primary ?>;
    --ahg-primary-dark: <?= $primary ?>;
    --ahg-accent-color: <?= $secondary ?>;
    --ahg-accent-dark: <?= $secondary ?>;
    --ahg-button-bg: <?= $buttonBg ?>;
    --ahg-button-text: <?= $buttonText ?>;
    --ahg-text-white: #ffffff;
}

/* Main navbar — Primary colour */
.navbar, header.navbar, #top-bar.navbar {
    background: <?= $primary ?> !important;
}

/* All buttons — use Button Background, override webpack gradient */
.btn.btn-primary,
.btn.btn-outline-secondary,
.btn.btn-outline-primary,
a.btn-primary,
a.btn-outline-secondary,
a.btn-outline-primary,
button.btn-primary,
input.btn-primary {
    background: <?= $buttonBg ?> !important;
    border-color: <?= $buttonBg ?> !important;
    color: <?= $buttonText ?> !important;
}
.btn-primary:hover, .btn-primary:focus,
.btn-outline-secondary:hover, .btn-outline-secondary:focus,
.btn-outline-primary:hover, .btn-outline-primary:focus {
    filter: brightness(0.9);
}

/* Apply to Bootstrap components */
.card-header {
    background-color: var(--ahg-card-header-bg) !important;
    color: var(--ahg-card-header-text) !important;
}
.card-header * { color: var(--ahg-card-header-text) !important; }

a:not(.btn):not(.nav-link):not(.dropdown-item) {
    color: var(--ahg-link-color);
}

/* Edit Form Accordion Headers - use theme primary color */
#editForm .accordion-button {
    background-color: var(--ahg-primary) !important;
    color: var(--ahg-card-header-text) !important;
}
#editForm .accordion-button:not(.collapsed) {
    background-color: var(--ahg-primary) !important;
    color: var(--ahg-card-header-text) !important;
    box-shadow: none;
}
#editForm .accordion-button:focus {
    box-shadow: 0 0 0 0.25rem var(--ahg-input-focus);
}
#editForm .accordion-button::after,
#editForm .accordion-button:not(.collapsed)::after {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
}
#editForm .accordion-item {
    border: 1px solid #dee2e6;
    margin-bottom: 0.5rem;
    border-radius: 0.375rem;
    overflow: hidden;
}

/* Gallery/GLAM/CCO form accordions - also use theme colors */
.gallery-cataloguing-form .accordion-button,
.gallery-cataloguing-form .accordion-button:not(.collapsed),
.gallery-cataloguing-form .accordion-button.collapsed,
.cco-cataloguing-form .accordion-button,
.cco-cataloguing-form .accordion-button:not(.collapsed),
.cco-cataloguing-form .accordion-button.collapsed {
    background-color: var(--ahg-primary) !important;
    color: var(--ahg-card-header-text) !important;
}
.gallery-cataloguing-form .accordion-button::after,
.cco-cataloguing-form .accordion-button::after {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
}
</style>
