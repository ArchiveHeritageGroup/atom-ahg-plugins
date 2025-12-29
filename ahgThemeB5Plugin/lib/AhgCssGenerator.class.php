<?php
/**
 * Generate static CSS file from ahg_settings
 */
class AhgCssGenerator
{
    public static function generate()
    {
        try {
            $conn = Propel::getConnection();
            $sql = "SELECT setting_key, setting_value FROM ahg_settings WHERE setting_group = 'general'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            $primary = $settings['ahg_primary_color'] ?? '#005837';
            $secondary = $settings['ahg_secondary_color'] ?? '#37A07F';
            $cardHeaderBg = $settings['ahg_card_header_bg'] ?? '#005837';
            $cardHeaderText = $settings['ahg_card_header_text'] ?? '#ffffff';
            $buttonBg = $settings['ahg_button_bg'] ?? '#005837';
            $buttonText = $settings['ahg_button_text'] ?? '#ffffff';
            $linkColor = $settings['ahg_link_color'] ?? '#005837';
            $sidebarBg = $settings['ahg_sidebar_bg'] ?? '#f8f9fa';
            $sidebarText = $settings['ahg_sidebar_text'] ?? '#333333';
            
            $css = <<<CSS
/* AHG Theme - Generated CSS */
/* Do not edit - regenerated when settings saved */
:root {
    --ahg-primary: {$primary};
    --ahg-secondary: {$secondary};
    --ahg-card-header-bg: {$cardHeaderBg};
    --ahg-card-header-text: {$cardHeaderText};
    --ahg-btn-bg: {$buttonBg};
    --ahg-btn-text: {$buttonText};
    --ahg-link-color: {$linkColor};
    --ahg-sidebar-bg: {$sidebarBg};
    --ahg-sidebar-text: {$sidebarText};
}
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
.sidebar, #sidebar-content {
    background-color: var(--ahg-sidebar-bg) !important;
    color: var(--ahg-sidebar-text) !important;
}
CSS;
            
            $cssPath = sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin/css/ahg-generated.css';
            file_put_contents($cssPath, $css);
            
            return true;
        } catch (Exception $e) {
            error_log("AhgCssGenerator error: " . $e->getMessage());
            return false;
        }
    }
}
