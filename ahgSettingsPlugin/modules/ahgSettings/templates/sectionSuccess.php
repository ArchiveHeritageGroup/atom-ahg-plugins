<?php
/**
 * AHG Settings Template
 * 
 * Centralized settings management for AHG theme and plugins
 */

$title = __('AHG Settings');
slot('title', $title);
?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
/* Page header bar — Primary colour */
.ahg-settings-page .page-header {
    background-color: var(--ahg-primary, #005837) !important;
    color: #fff !important;
    padding: 1rem 1.5rem;
    border-radius: 0.375rem;
}
.ahg-settings-page .page-header * { color: #fff !important; }
.ahg-settings-page .page-header .text-muted { color: rgba(255,255,255,0.75) !important; }
/* Sidebar active item — Secondary colour */
.ahg-settings-page .list-group-item.active {
    background-color: var(--ahg-secondary, #37A07F) !important;
    border-color: var(--ahg-secondary, #37A07F) !important;
    color: #fff !important;
}
/* Sidebar column background and text */
.ahg-settings-page .col-md-3 .card {
    background-color: var(--ahg-sidebar-bg, #f8f9fa);
}
.ahg-settings-page .col-md-3 .list-group-item:not(.active) {
    color: var(--ahg-sidebar-text, #333) !important;
    background-color: var(--ahg-sidebar-bg, #f8f9fa) !important;
}
/* Card headers */
.ahg-settings-page .card-header {
    background-color: var(--ahg-card-header-bg, #005837) !important;
    color: var(--ahg-card-header-text, #fff) !important;
}
.ahg-settings-page .card-header * {
    color: var(--ahg-card-header-text, #fff) !important;
}
/* Buttons — use background shorthand to override webpack gradients */
.ahg-settings-page .btn-outline-secondary,
.ahg-settings-page a.btn-outline-secondary,
.ahg-settings-page .btn-primary,
.ahg-settings-page a.btn-primary {
    background: var(--ahg-button-bg, var(--ahg-btn-bg, #005837)) !important;
    border-color: var(--ahg-button-bg, var(--ahg-btn-bg, #005837)) !important;
    color: var(--ahg-button-text, var(--ahg-btn-text, #fff)) !important;
}
.ahg-settings-page .btn-outline-danger,
.ahg-settings-page a.btn-outline-danger {
    background: var(--ahg-danger, #dc3545) !important;
    border-color: var(--ahg-danger, #dc3545) !important;
    color: #fff !important;
}
.ahg-settings-page .btn:hover { filter: brightness(0.9); }
/* Sample preview button */
.ahg-settings-page #preview-button {
    background: var(--ahg-button-bg, var(--ahg-btn-bg, #005837)) !important;
    color: var(--ahg-button-text, var(--ahg-btn-text, #fff)) !important;
}
</style>
<div class="ahg-settings-page">
    <!-- Back to Overview Link -->
    <div class="mb-3">
        <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Settings Overview') ?>
        </a>
    </div>
    
    <!-- Page Header -->
    <div class="page-header mb-4">
        <h1><i class="fas fa-cogs"></i> <?php echo $title; ?></h1>
        <p class="text-muted"><?php echo __('Configure AHG theme and plugin settings'); ?></p>
    </div>
    
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> <?php echo __('Settings Sections'); ?></h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($sections as $sectionKey => $sectionInfo): ?>
                        <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'section', 'section' => $sectionKey]); ?>" 
                           class="list-group-item list-group-item-action <?php echo $currentSection === $sectionKey ? 'active' : ''; ?>">
                            <i class="fas <?php echo $sectionInfo['icon']; ?> fa-fw mr-2"></i>
                            <?php echo __($sectionInfo['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> <?php echo __('Quick Actions'); ?></h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'export']); ?>" class="btn btn-outline-secondary btn-sm btn-block mb-2">
                        <i class="fas fa-download"></i> <?php echo __('Export Settings'); ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'import']); ?>" class="btn btn-outline-secondary btn-sm btn-block mb-2">
                        <i class="fas fa-upload"></i> <?php echo __('Import Settings'); ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'reset', 'section' => $currentSection]); ?>" 
                       class="btn btn-outline-danger btn-sm btn-block"
                       onclick="return confirm('<?php echo __('Reset all settings in this section to defaults?'); ?>');">
                        <i class="fas fa-undo"></i> <?php echo __('Reset to Defaults'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas <?php echo $sections[$currentSection]['icon']; ?>"></i>
                        <?php echo __($sections[$currentSection]['label']); ?>
                    </h4>
                    <small class="text-muted"><?php echo __($sections[$currentSection]['description']); ?></small>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'section', 'section' => $currentSection]); ?>" id="settings-form">
                        
                        <?php switch ($currentSection): 
                            case 'general': ?>
                                <!-- General Settings -->
                                <fieldset class="mb-4">
                                    <legend><?php echo __('Theme Configuration'); ?></legend>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Enable AHG Theme'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="ahg_theme_enabled" name="settings[ahg_theme_enabled]" value="true" <?php echo $settings['ahg_theme_enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="ahg_theme_enabled"><?php echo __('Use AHG theme customizations'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_logo_path"><?php echo __('Custom Logo'); ?></label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="ahg_logo_path" name="settings[ahg_logo_path]" value="<?php echo htmlspecialchars($settings['ahg_logo_path'] ?? ''); ?>" placeholder="/uploads/logo.png">
                                            <small class="form-text text-muted"><?php echo __('Path to custom logo image'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_primary_color"><?php echo __('Primary Color'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_primary_color_picker" value="<?php echo htmlspecialchars($settings['ahg_primary_color'] ?? '#1a5f7a'); ?>" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_primary_color" name="settings[ahg_primary_color]" value="<?php echo htmlspecialchars($settings['ahg_primary_color'] ?? '#1a5f7a'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_secondary_color"><?php echo __('Secondary Color'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_secondary_color_picker" value="<?php echo htmlspecialchars($settings['ahg_secondary_color'] ?? '#57837b'); ?>" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_secondary_color" name="settings[ahg_secondary_color]" value="<?php echo htmlspecialchars($settings['ahg_secondary_color'] ?? '#57837b'); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    <h6 class="text-muted mb-3"><i class="fas fa-palette me-2"></i><?php echo __('Extended Color Options'); ?></h6>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_card_header_bg"><?php echo __('Card Header Background'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_card_header_bg_picker" value="<?php echo htmlspecialchars($settings['ahg_card_header_bg'] ?? '#1a5f2a'); ?>" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_card_header_bg" name="settings[ahg_card_header_bg]" value="<?php echo htmlspecialchars($settings['ahg_card_header_bg'] ?? '#1a5f2a'); ?>">
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Background color for card headers'); ?></small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_card_header_text"><?php echo __('Card Header Text'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_card_header_text_picker" value="<?php echo htmlspecialchars($settings['ahg_card_header_text'] ?? '#ffffff'); ?>" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_card_header_text" name="settings[ahg_card_header_text]" value="<?php echo htmlspecialchars($settings['ahg_card_header_text'] ?? '#ffffff'); ?>">
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Text color for card headers'); ?></small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_button_bg"><?php echo __('Button Background'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_button_bg_picker" value="<?php echo htmlspecialchars($settings['ahg_button_bg'] ?? '#1a5f2a'); ?>" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_button_bg" name="settings[ahg_button_bg]" value="<?php echo htmlspecialchars($settings['ahg_button_bg'] ?? '#1a5f2a'); ?>">
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Background color for primary buttons'); ?></small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_button_text"><?php echo __('Button Text'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_button_text_picker" value="<?php echo htmlspecialchars($settings['ahg_button_text'] ?? '#ffffff'); ?>" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_button_text" name="settings[ahg_button_text]" value="<?php echo htmlspecialchars($settings['ahg_button_text'] ?? '#ffffff'); ?>">
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Text color for primary buttons'); ?></small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_link_color"><?php echo __('Link Color'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_link_color_picker" value="<?php echo htmlspecialchars($settings['ahg_link_color'] ?? '#1a5f2a'); ?>" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_link_color" name="settings[ahg_link_color]" value="<?php echo htmlspecialchars($settings['ahg_link_color'] ?? '#1a5f2a'); ?>">
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Color for hyperlinks'); ?></small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_sidebar_bg"><?php echo __('Sidebar Background'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_sidebar_bg_picker" value="<?php echo htmlspecialchars($settings['ahg_sidebar_bg'] ?? '#f8f9fa'); ?>" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_sidebar_bg" name="settings[ahg_sidebar_bg]" value="<?php echo htmlspecialchars($settings['ahg_sidebar_bg'] ?? '#f8f9fa'); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_sidebar_text"><?php echo __('Sidebar Text'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_sidebar_text_picker" value="<?php echo htmlspecialchars($settings['ahg_sidebar_text'] ?? '#333333'); ?>" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_sidebar_text" name="settings[ahg_sidebar_text]" value="<?php echo htmlspecialchars($settings['ahg_sidebar_text'] ?? '#333333'); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Preview Box -->
                                    <div class="form-group row mt-4">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Preview'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="card" id="theme-preview-card">
                                                <div class="card-header" id="preview-card-header" style="background-color: <?php echo htmlspecialchars($settings['ahg_card_header_bg'] ?? '#1a5f2a'); ?>; color: <?php echo htmlspecialchars($settings['ahg_card_header_text'] ?? '#ffffff'); ?>;">
                                                    <h6 class="mb-0" style="color: inherit;"><i class="fas fa-eye me-2"></i>Preview Header</h6>
                                                </div>
                                                <div class="card-body">
                                                    <p>Sample text with <a href="#" id="preview-link" style="color: <?php echo htmlspecialchars($settings['ahg_link_color'] ?? '#1a5f2a'); ?>;">a link</a>.</p>
                                                    <button type="button" class="btn" id="preview-button" style="background-color: <?php echo htmlspecialchars($settings['ahg_button_bg'] ?? '#1a5f2a'); ?>; color: <?php echo htmlspecialchars($settings['ahg_button_text'] ?? '#ffffff'); ?>;">Sample Button</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_footer_text"><?php echo __('Footer Text'); ?></label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="ahg_footer_text" name="settings[ahg_footer_text]" value="<?php echo htmlspecialchars($settings['ahg_footer_text'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Show Branding'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="ahg_show_branding" name="settings[ahg_show_branding]" value="true" <?php echo ($settings['ahg_show_branding'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="ahg_show_branding"><?php echo __('Display AHG branding'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_custom_css"><?php echo __('Custom CSS'); ?></label>
                                        <div class="col-sm-9">
                                            <textarea class="form-control font-monospace" id="ahg_custom_css" name="settings[ahg_custom_css]" rows="6" placeholder="/* Custom CSS styles */"><?php echo htmlspecialchars($settings['ahg_custom_css'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </fieldset>
                            <?php break; ?>
                            
                            <?php case 'spectrum': ?>
                                <!-- Spectrum Settings -->
                                <fieldset class="mb-4">
                                    <legend><?php echo __('Collections Management'); ?></legend>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Enable Spectrum'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="spectrum_enabled" name="settings[spectrum_enabled]" value="true" <?php echo ($settings['spectrum_enabled'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="spectrum_enabled"><?php echo __('Enable Spectrum collections management'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="spectrum_default_currency"><?php echo __('Default Currency'); ?></label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="spectrum_default_currency" name="settings[spectrum_default_currency]">
                                                <?php foreach (['ZAR' => 'South African Rand (ZAR)', 'USD' => 'US Dollar (USD)', 'EUR' => 'Euro (EUR)', 'GBP' => 'British Pound (GBP)'] as $code => $name): ?>
                                                    <option value="<?php echo $code; ?>" <?php echo ($settings['spectrum_default_currency'] ?? 'ZAR') === $code ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="spectrum_valuation_reminder_days"><?php echo __('Valuation Reminder'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="spectrum_valuation_reminder_days" name="settings[spectrum_valuation_reminder_days]" value="<?php echo $settings['spectrum_valuation_reminder_days'] ?? 365; ?>" min="30" max="1825">
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><?php echo __('days'); ?></span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Remind to re-value after this many days'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="spectrum_loan_default_period"><?php echo __('Default Loan Period'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="spectrum_loan_default_period" name="settings[spectrum_loan_default_period]" value="<?php echo $settings['spectrum_loan_default_period'] ?? 90; ?>" min="1" max="365">
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><?php echo __('days'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="spectrum_condition_check_interval"><?php echo __('Condition Check Interval'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="spectrum_condition_check_interval" name="settings[spectrum_condition_check_interval]" value="<?php echo $settings['spectrum_condition_check_interval'] ?? 180; ?>" min="30" max="730">
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><?php echo __('days'); ?></span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Recommended interval between condition checks'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Auto-create Movements'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="spectrum_auto_create_movement" name="settings[spectrum_auto_create_movement]" value="true" <?php echo ($settings['spectrum_auto_create_movement'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="spectrum_auto_create_movement"><?php echo __('Automatically create movement records on location change'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Require Photos'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="spectrum_require_photos" name="settings[spectrum_require_photos]" value="true" <?php echo ($settings['spectrum_require_photos'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="spectrum_require_photos"><?php echo __('Require at least one photo for condition reports'); ?></label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Email Notifications'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="spectrum_email_notifications" name="settings[spectrum_email_notifications]" value="true" <?php echo ($settings['spectrum_email_notifications'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="spectrum_email_notifications"><?php echo __('Send email notifications for task assignments and state transitions'); ?></label>
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Requires SMTP to be configured in Email settings'); ?></small>
                                        </div>
                                    </div>
                                </fieldset>
                            <?php break; ?>
                            
                            <?php case 'media': ?>
                                <!-- Media Player Settings -->
                                <fieldset class="mb-4">
                                    <legend><?php echo __('Media Player Configuration'); ?></legend>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="media_player_type"><?php echo __('Player Type'); ?></label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="media_player_type" name="settings[media_player_type]">
                                                <option value="basic" <?php echo ($settings['media_player_type'] ?? 'enhanced') === 'basic' ? 'selected' : ''; ?>><?php echo __('Basic HTML5 Player'); ?></option>
                                                <option value="enhanced" <?php echo ($settings['media_player_type'] ?? 'enhanced') === 'enhanced' ? 'selected' : ''; ?>><?php echo __('Enhanced Player (Recommended)'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Auto-play'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="media_autoplay" name="settings[media_autoplay]" value="true" <?php echo ($settings['media_autoplay'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="media_autoplay"><?php echo __('Auto-play media on load'); ?></label>
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Note: Most browsers block autoplay with sound'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Show Controls'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="media_show_controls" name="settings[media_show_controls]" value="true" <?php echo ($settings['media_show_controls'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="media_show_controls"><?php echo __('Display player controls'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Loop Playback'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="media_loop" name="settings[media_loop]" value="true" <?php echo ($settings['media_loop'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="media_loop"><?php echo __('Loop media automatically'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="media_default_volume"><?php echo __('Default Volume'); ?></label>
                                        <div class="col-sm-9">
                                            <input type="range" class="form-control-range" id="media_default_volume" name="settings[media_default_volume]" min="0" max="1" step="0.1" value="<?php echo $settings['media_default_volume'] ?? 0.8; ?>">
                                            <small class="form-text text-muted"><?php echo __('Default volume level (0-100%)'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Show Download'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="media_show_download" name="settings[media_show_download]" value="true" <?php echo ($settings['media_show_download'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="media_show_download"><?php echo __('Show download button'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            <?php break; ?>
                            
                            <?php case 'photos': ?>
                                <!-- Photo Settings -->
                                <fieldset class="mb-4">
                                    <legend><?php echo __('Photo Upload Settings'); ?></legend>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="photo_upload_path"><?php echo __('Upload Path'); ?></label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="photo_upload_path" name="settings[photo_upload_path]" value="<?php echo htmlspecialchars($settings['photo_upload_path'] ?? sfConfig::get('sf_root_dir') . '/uploads/condition_photos'); ?>">
                                            <small class="form-text text-muted"><?php echo __('Absolute path for condition photo storage'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="photo_max_upload_size"><?php echo __('Max Upload Size'); ?></label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="photo_max_upload_size" name="settings[photo_max_upload_size]">
                                                <option value="5242880" <?php echo ($settings['photo_max_upload_size'] ?? 10485760) == 5242880 ? 'selected' : ''; ?>>5 MB</option>
                                                <option value="10485760" <?php echo ($settings['photo_max_upload_size'] ?? 10485760) == 10485760 ? 'selected' : ''; ?>>10 MB</option>
                                                <option value="20971520" <?php echo ($settings['photo_max_upload_size'] ?? 10485760) == 20971520 ? 'selected' : ''; ?>>20 MB</option>
                                                <option value="52428800" <?php echo ($settings['photo_max_upload_size'] ?? 10485760) == 52428800 ? 'selected' : ''; ?>>50 MB</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Create Thumbnails'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="photo_create_thumbnails" name="settings[photo_create_thumbnails]" value="true" <?php echo ($settings['photo_create_thumbnails'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="photo_create_thumbnails"><?php echo __('Auto-create thumbnails on upload'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Thumbnail Sizes'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="row">
                                                <div class="col-4">
                                                    <label class="small"><?php echo __('Small'); ?></label>
                                                    <input type="number" class="form-control" name="settings[photo_thumbnail_small]" value="<?php echo $settings['photo_thumbnail_small'] ?? 150; ?>" min="50" max="300">
                                                </div>
                                                <div class="col-4">
                                                    <label class="small"><?php echo __('Medium'); ?></label>
                                                    <input type="number" class="form-control" name="settings[photo_thumbnail_medium]" value="<?php echo $settings['photo_thumbnail_medium'] ?? 300; ?>" min="100" max="600">
                                                </div>
                                                <div class="col-4">
                                                    <label class="small"><?php echo __('Large'); ?></label>
                                                    <input type="number" class="form-control" name="settings[photo_thumbnail_large]" value="<?php echo $settings['photo_thumbnail_large'] ?? 600; ?>" min="300" max="1200">
                                                </div>
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Maximum dimension in pixels'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="photo_jpeg_quality"><?php echo __('JPEG Quality'); ?></label>
                                        <div class="col-sm-9">
                                            <input type="range" class="form-control-range" id="photo_jpeg_quality" name="settings[photo_jpeg_quality]" min="60" max="100" value="<?php echo $settings['photo_jpeg_quality'] ?? 85; ?>">
                                            <small class="form-text text-muted"><?php echo __('Quality for JPEG thumbnails (60-100)'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Extract EXIF'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="photo_extract_exif" name="settings[photo_extract_exif]" value="true" <?php echo ($settings['photo_extract_exif'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="photo_extract_exif"><?php echo __('Extract camera info from EXIF data'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Auto-rotate'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="photo_auto_rotate" name="settings[photo_auto_rotate]" value="true" <?php echo ($settings['photo_auto_rotate'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="photo_auto_rotate"><?php echo __('Auto-rotate based on EXIF orientation'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            <?php break; ?>
                            
                            <?php case 'data_protection': ?>
                                <!-- Data Protection Settings -->
                                <fieldset class="mb-4">
                                    <legend><?php echo __('Data Protection Compliance'); ?></legend>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Enable Module'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="dp_enabled" name="settings[dp_enabled]" value="true" <?php echo ($settings['dp_enabled'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="dp_enabled"><?php echo __('Enable data protection module'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="dp_default_regulation"><?php echo __('Default Regulation'); ?></label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="dp_default_regulation" name="settings[dp_default_regulation]">
                                                <option value="popia" <?php echo ($settings['dp_default_regulation'] ?? 'popia') === 'popia' ? 'selected' : ''; ?>>POPIA (South Africa)</option>
                                                <option value="gdpr" <?php echo ($settings['dp_default_regulation'] ?? 'popia') === 'gdpr' ? 'selected' : ''; ?>>GDPR (European Union)</option>
                                                <option value="paia" <?php echo ($settings['dp_default_regulation'] ?? 'popia') === 'paia' ? 'selected' : ''; ?>>PAIA (South Africa)</option>
                                                <option value="ccpa" <?php echo ($settings['dp_default_regulation'] ?? 'popia') === 'ccpa' ? 'selected' : ''; ?>>CCPA (California)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Notify Overdue'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="dp_notify_overdue" name="settings[dp_notify_overdue]" value="true" <?php echo ($settings['dp_notify_overdue'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="dp_notify_overdue"><?php echo __('Send email notifications for overdue requests'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="dp_notify_email"><?php echo __('Notification Email'); ?></label>
                                        <div class="col-sm-9">
                                            <input type="email" class="form-control" id="dp_notify_email" name="settings[dp_notify_email]" value="<?php echo htmlspecialchars($settings['dp_notify_email'] ?? ''); ?>" placeholder="dpo@example.com">
                                        </div>
                                    </div>
                                </fieldset>
                                
                                <fieldset class="mb-4">
                                    <legend><?php echo __('POPIA/PAIA Settings'); ?></legend>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="dp_popia_fee"><?php echo __('POPIA Request Fee'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">R</span>
                                                </div>
                                                <input type="number" class="form-control" id="dp_popia_fee" name="settings[dp_popia_fee]" value="<?php echo $settings['dp_popia_fee'] ?? 50; ?>" min="0" step="0.01">
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Standard request fee (R50 per regulation)'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="dp_popia_fee_special"><?php echo __('Special Category Fee'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">R</span>
                                                </div>
                                                <input type="number" class="form-control" id="dp_popia_fee_special" name="settings[dp_popia_fee_special]" value="<?php echo $settings['dp_popia_fee_special'] ?? 140; ?>" min="0" step="0.01">
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Fee for special categories of personal info (R140)'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="dp_popia_response_days"><?php echo __('Response Days'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="dp_popia_response_days" name="settings[dp_popia_response_days]" value="<?php echo $settings['dp_popia_response_days'] ?? 30; ?>" min="1" max="90">
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><?php echo __('days'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            <?php break; ?>
                            
                            <?php case 'iiif': ?>
                                <!-- IIIF Settings -->
                                <fieldset class="mb-4">
                                    <legend><?php echo __('IIIF Image Viewer'); ?></legend>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Enable IIIF'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="iiif_enabled" name="settings[iiif_enabled]" value="true" <?php echo ($settings['iiif_enabled'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="iiif_enabled"><?php echo __('Enable IIIF viewer'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="iiif_viewer"><?php echo __('Viewer Library'); ?></label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="iiif_viewer" name="settings[iiif_viewer]">
                                                <option value="openseadragon" <?php echo ($settings['iiif_viewer'] ?? 'openseadragon') === 'openseadragon' ? 'selected' : ''; ?>>OpenSeadragon</option>
                                                <option value="mirador" <?php echo ($settings['iiif_viewer'] ?? 'openseadragon') === 'mirador' ? 'selected' : ''; ?>>Mirador</option>
                                                <option value="leaflet" <?php echo ($settings['iiif_viewer'] ?? 'openseadragon') === 'leaflet' ? 'selected' : ''; ?>>Leaflet-IIIF</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="iiif_server_url"><?php echo __('IIIF Server URL'); ?></label>
                                        <div class="col-sm-9">
                                            <input type="url" class="form-control" id="iiif_server_url" name="settings[iiif_server_url]" value="<?php echo htmlspecialchars($settings['iiif_server_url'] ?? ''); ?>" placeholder="https://iiif.example.com">
                                            <small class="form-text text-muted"><?php echo __('External IIIF server URL (leave blank to use built-in)'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Show Navigator'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="iiif_show_navigator" name="settings[iiif_show_navigator]" value="true" <?php echo ($settings['iiif_show_navigator'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="iiif_show_navigator"><?php echo __('Show mini-map navigator'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Enable Rotation'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="iiif_show_rotation" name="settings[iiif_show_rotation]" value="true" <?php echo ($settings['iiif_show_rotation'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="iiif_show_rotation"><?php echo __('Allow image rotation'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="iiif_max_zoom"><?php echo __('Max Zoom Level'); ?></label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control" id="iiif_max_zoom" name="settings[iiif_max_zoom]" value="<?php echo $settings['iiif_max_zoom'] ?? 10; ?>" min="1" max="20">
                                        </div>
                                    </div>
                                </fieldset>
                            <?php break; ?>
                            
                            <?php case 'jobs': ?>
                                <!-- Jobs Settings -->
                                <fieldset class="mb-4">
                                    <legend><?php echo __('Background Job Settings'); ?></legend>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Enable Jobs'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="jobs_enabled" name="settings[jobs_enabled]" value="true" <?php echo ($settings['jobs_enabled'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="jobs_enabled"><?php echo __('Enable background job processing'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="jobs_max_concurrent"><?php echo __('Max Concurrent Jobs'); ?></label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control" id="jobs_max_concurrent" name="settings[jobs_max_concurrent]" value="<?php echo $settings['jobs_max_concurrent'] ?? 2; ?>" min="1" max="10">
                                            <small class="form-text text-muted"><?php echo __('Maximum number of jobs to run simultaneously'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="jobs_timeout"><?php echo __('Job Timeout'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="jobs_timeout" name="settings[jobs_timeout]" value="<?php echo $settings['jobs_timeout'] ?? 3600; ?>" min="60" max="86400">
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><?php echo __('seconds'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="jobs_retry_attempts"><?php echo __('Retry Attempts'); ?></label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control" id="jobs_retry_attempts" name="settings[jobs_retry_attempts]" value="<?php echo $settings['jobs_retry_attempts'] ?? 3; ?>" min="0" max="10">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="jobs_cleanup_days"><?php echo __('Cleanup After'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="jobs_cleanup_days" name="settings[jobs_cleanup_days]" value="<?php echo $settings['jobs_cleanup_days'] ?? 30; ?>" min="1" max="365">
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><?php echo __('days'); ?></span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Delete completed jobs after this many days'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Notify on Failure'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="jobs_notify_on_failure" name="settings[jobs_notify_on_failure]" value="true" <?php echo ($settings['jobs_notify_on_failure'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="jobs_notify_on_failure"><?php echo __('Send email when jobs fail'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="jobs_notify_email"><?php echo __('Notification Email'); ?></label>
                                        <div class="col-sm-9">
                                            <input type="email" class="form-control" id="jobs_notify_email" name="settings[jobs_notify_email]" value="<?php echo htmlspecialchars($settings['jobs_notify_email'] ?? ''); ?>" placeholder="admin@example.com">
                                        </div>
                                    </div>
                                </fieldset>
                                
                                <!-- Job Status -->
                                <fieldset class="mb-4">
                                    <legend><?php echo __('Job Queue Status'); ?></legend>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <a href="<?php echo url_for(['module' => 'jobs', 'action' => 'browse']); ?>"><?php echo __('View all jobs in Job Manager'); ?></a>
                                    </div>
                                </fieldset>
                            <?php break; ?>
                            

                            <?php case 'fuseki': ?>
                                <!-- Fuseki Connection Settings -->
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fa fa-server me-2"></i><?php echo __('Fuseki Connection'); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-8">
                                                <label for="fuseki_endpoint" class="form-label"><?php echo __('Fuseki SPARQL Endpoint'); ?></label>
                                                <input type="url" class="form-control" id="fuseki_endpoint" name="settings[fuseki_endpoint]"
                                                       value="<?php echo esc_specialchars($settings['fuseki_endpoint'] ?? sfConfig::get('app_ric_fuseki_endpoint', 'http://localhost:3030/ric')); ?>"
                                                       placeholder="http://localhost:3030/ric">
                                                <div class="form-text"><?php echo __('Full URL to Fuseki SPARQL endpoint (e.g., http://localhost:3030/ric)'); ?></div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="button" class="btn btn-outline-secondary d-block w-100" id="test-fuseki-btn">
                                                    <i class="fa fa-plug me-1"></i><?php echo __('Test Connection'); ?>
                                                </button>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="fuseki_username" class="form-label"><?php echo __('Username'); ?></label>
                                                <input type="text" class="form-control" id="fuseki_username" name="settings[fuseki_username]" 
                                                       value="<?php echo esc_specialchars($settings['fuseki_username'] ?? 'admin'); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="fuseki_password" class="form-label"><?php echo __('Password'); ?></label>
                                                <input type="password" class="form-control" id="fuseki_password" name="settings[fuseki_password]" 
                                                       value="<?php echo esc_specialchars($settings['fuseki_password'] ?? ''); ?>" 
                                                       placeholder="<?php echo __('Leave blank to keep current'); ?>">
                                            </div>
                                            <div class="col-12">
                                                <div id="fuseki-test-result" class="alert d-none"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- RIC Sync Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fa fa-sync-alt me-2"></i><?php echo __('RIC Sync Settings'); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="fuseki_sync_enabled" 
                                                           name="settings[fuseki_sync_enabled]" value="1"
                                                           <?php echo ($settings['fuseki_sync_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="fuseki_sync_enabled">
                                                        <strong><?php echo __('Enable Automatic Sync'); ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Master switch for all RIC sync operations'); ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="fuseki_queue_enabled" 
                                                           name="settings[fuseki_queue_enabled]" value="1"
                                                           <?php echo ($settings['fuseki_queue_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="fuseki_queue_enabled">
                                                        <strong><?php echo __('Use Async Queue'); ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Queue sync operations for background processing'); ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="fuseki_sync_on_save" 
                                                           name="settings[fuseki_sync_on_save]" value="1"
                                                           <?php echo ($settings['fuseki_sync_on_save'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="fuseki_sync_on_save">
                                                        <?php echo __('Sync on Record Save'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Automatically sync to Fuseki when records are created/updated'); ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="fuseki_sync_on_delete" 
                                                           name="settings[fuseki_sync_on_delete]" value="1"
                                                           <?php echo ($settings['fuseki_sync_on_delete'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="fuseki_sync_on_delete">
                                                        <?php echo __('Sync on Record Delete'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Remove from Fuseki when records are deleted in AtoM'); ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="fuseki_cascade_delete" 
                                                           name="settings[fuseki_cascade_delete]" value="1"
                                                           <?php echo ($settings['fuseki_cascade_delete'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="fuseki_cascade_delete">
                                                        <?php echo __('Cascade Delete References'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Also remove triples where deleted record is the object'); ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="fuseki_batch_size" class="form-label"><?php echo __('Batch Size'); ?></label>
                                                <input type="number" class="form-control" id="fuseki_batch_size" 
                                                       name="settings[fuseki_batch_size]" min="10" max="1000" step="10"
                                                       value="<?php echo esc_specialchars($settings['fuseki_batch_size'] ?? '100'); ?>">
                                                <div class="form-text"><?php echo __('Records per batch for bulk sync operations'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Integrity Check Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fa fa-check-double me-2"></i><?php echo __('Integrity Check Settings'); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="fuseki_integrity_schedule" class="form-label"><?php echo __('Check Schedule'); ?></label>
                                                <select class="form-select" id="fuseki_integrity_schedule" name="settings[fuseki_integrity_schedule]">
                                                    <option value="daily" <?php echo ($settings['fuseki_integrity_schedule'] ?? '') === 'daily' ? 'selected' : ''; ?>>
                                                        <?php echo __('Daily'); ?>
                                                    </option>
                                                    <option value="weekly" <?php echo ($settings['fuseki_integrity_schedule'] ?? 'weekly') === 'weekly' ? 'selected' : ''; ?>>
                                                        <?php echo __('Weekly'); ?>
                                                    </option>
                                                    <option value="monthly" <?php echo ($settings['fuseki_integrity_schedule'] ?? '') === 'monthly' ? 'selected' : ''; ?>>
                                                        <?php echo __('Monthly'); ?>
                                                    </option>
                                                    <option value="disabled" <?php echo ($settings['fuseki_integrity_schedule'] ?? '') === 'disabled' ? 'selected' : ''; ?>>
                                                        <?php echo __('Disabled'); ?>
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="fuseki_orphan_retention_days" class="form-label"><?php echo __('Orphan Retention (days)'); ?></label>
                                                <input type="number" class="form-control" id="fuseki_orphan_retention_days" 
                                                       name="settings[fuseki_orphan_retention_days]" min="1" max="365"
                                                       value="<?php echo esc_specialchars($settings['fuseki_orphan_retention_days'] ?? '30'); ?>">
                                                <div class="form-text"><?php echo __('Days to retain orphaned triples before cleanup'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="card mb-4">
                                    <div class="card-header bg-secondary text-white">
                                        <h5 class="mb-0"><i class="fa fa-bolt me-2"></i><?php echo __('Quick Actions'); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']); ?>" class="btn btn-outline-primary">
                                                <i class="fa fa-tachometer-alt me-1"></i><?php echo __('RIC Dashboard'); ?>
                                            </a>
                                            <a href="https://www.ica.org/standards/RiC/ontology" target="_blank" class="btn btn-outline-info">
                                                <i class="fa fa-book me-1"></i><?php echo __('RiC-O Reference'); ?>
                                            </a>
<?php $fusekiAdmin = preg_replace('#/[^/]+$#', '/', $settings['fuseki_endpoint'] ?? sfConfig::get('app_ric_fuseki_endpoint', 'http://localhost:3030/ric')); ?>
                                            <a href="<?php echo esc_specialchars($fusekiAdmin); ?>" target="_blank" class="btn btn-outline-secondary">
                                                <i class="fa fa-database me-1"></i><?php echo __('Fuseki Admin'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fuseki Test Connection Script -->
                                <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
                                document.getElementById('test-fuseki-btn')?.addEventListener('click', function() {
                                    const btn = this;
                                    const resultDiv = document.getElementById('fuseki-test-result');
                                    const endpoint = document.getElementById('fuseki_endpoint').value;
                                    const username = document.getElementById('fuseki_username').value;
                                    const password = document.getElementById('fuseki_password').value;
                                    
                                    btn.disabled = true;
                                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> <?php echo __("Testing..."); ?>';
                                    resultDiv.classList.add('d-none');
                                    
                                    // Test via AJAX endpoint
                                    fetch('<?php echo url_for(['module' => 'ahgSettings', 'action' => 'fusekiTest']); ?>', {
                                        method: 'POST',
                                        headers: {'Content-Type': 'application/json'},
                                        body: JSON.stringify({endpoint, username, password})
                                    })
                                    .then(r => r.json())
                                    .then(data => {
                                        btn.disabled = false;
                                        btn.innerHTML = '<i class="fa fa-plug me-1"></i><?php echo __("Test Connection"); ?>';
                                        resultDiv.classList.remove('d-none', 'alert-success', 'alert-danger');
                                        
                                        if (data.success) {
                                            resultDiv.classList.add('alert-success');
                                            resultDiv.innerHTML = '<i class="fa fa-check-circle me-2"></i>' +
                                                '<?php echo __("Connection successful!"); ?> ' +
                                                '<?php echo __("Triple count"); ?>: ' + (data.triple_count || 'N/A');
                                        } else {
                                            resultDiv.classList.add('alert-danger');
                                            resultDiv.innerHTML = '<i class="fa fa-times-circle me-2"></i>' +
                                                '<?php echo __("Connection failed"); ?>: ' + (data.error || '<?php echo __("Unknown error"); ?>');
                                        }
                                    })
                                    .catch(err => {
                                        btn.disabled = false;
                                        btn.innerHTML = '<i class="fa fa-plug me-1"></i><?php echo __("Test Connection"); ?>';
                                        resultDiv.classList.remove('d-none', 'alert-success');
                                        resultDiv.classList.add('alert-danger');
                                        resultDiv.innerHTML = '<i class="fa fa-times-circle me-2"></i><?php echo __("Error"); ?>: ' + err.message;
                                    });
                                });
                                </script>
                                <?php break; ?>

                            <?php case 'metadata': ?>
                                <fieldset class="mb-4">
                                    <legend><?php echo __('Metadata Extraction'); ?></legend>
                                    <div class="alert alert-info"><i class="fas fa-info-circle"></i> <?php echo __('Configure automatic metadata extraction.'); ?></div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Extract on Upload'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="meta_extract_on_upload" name="settings[meta_extract_on_upload]" value="true" <?php echo ($settings['meta_extract_on_upload'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="meta_extract_on_upload"><?php echo __('Auto-extract metadata'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Auto-Populate'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="meta_auto_populate" name="settings[meta_auto_populate]" value="true" <?php echo ($settings['meta_auto_populate'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="meta_auto_populate"><?php echo __('Populate description fields'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <fieldset class="mb-4">
                                    <legend><?php echo __('File Types'); ?></legend>
                                    <div class="row"><div class="col-md-6">
                                        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="meta_images" name="settings[meta_images]" value="true" <?php echo ($settings['meta_images'] ?? 'true') === 'true' ? 'checked' : ''; ?>><label class="custom-control-label" for="meta_images"><i class="fas fa-image text-success"></i> Images</label></div>
                                        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="meta_pdf" name="settings[meta_pdf]" value="true" <?php echo ($settings['meta_pdf'] ?? 'true') === 'true' ? 'checked' : ''; ?>><label class="custom-control-label" for="meta_pdf"><i class="fas fa-file-pdf text-danger"></i> PDF</label></div>
                                        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="meta_office" name="settings[meta_office]" value="true" <?php echo ($settings['meta_office'] ?? 'true') === 'true' ? 'checked' : ''; ?>><label class="custom-control-label" for="meta_office"><i class="fas fa-file-word text-primary"></i> Office</label></div>
                                    </div><div class="col-md-6">
                                        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="meta_video" name="settings[meta_video]" value="true" <?php echo ($settings['meta_video'] ?? 'true') === 'true' ? 'checked' : ''; ?>><label class="custom-control-label" for="meta_video"><i class="fas fa-video text-info"></i> Video</label></div>
                                        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="meta_audio" name="settings[meta_audio]" value="true" <?php echo ($settings['meta_audio'] ?? 'true') === 'true' ? 'checked' : ''; ?>><label class="custom-control-label" for="meta_audio"><i class="fas fa-music text-warning"></i> Audio</label></div>
                                    </div></div>
                                </fieldset>
                                <fieldset class="mb-4">
                                    <legend><?php echo __('Field Mapping'); ?></legend>
                                    <p class="text-muted"><?php echo __('Configure where extracted metadata is saved:'); ?></p>
                                    <table class="table table-sm table-bordered">
                                        <thead class="thead-dark"><tr><th style="width:20%">Metadata Source</th><th style="width:26%">Archives (ISAD)</th><th style="width:27%">Museum (Spectrum)</th><th style="width:27%">DAM</th></tr></thead>
                                        <tbody>
                                            <tr>
                                                <td><i class="fas fa-heading text-muted"></i> Title</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_title_isad]">
                                                    <option value="title" <?php echo ($settings['map_title_isad'] ?? 'title') === 'title' ? 'selected' : ''; ?>>Title</option>
                                                    <option value="alternateTitle" <?php echo ($settings['map_title_isad'] ?? '') === 'alternateTitle' ? 'selected' : ''; ?>>Alternate Title</option>
                                                    <option value="none" <?php echo ($settings['map_title_isad'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_title_museum]">
                                                    <option value="objectName" <?php echo ($settings['map_title_museum'] ?? 'objectName') === 'objectName' ? 'selected' : ''; ?>>Object Name</option>
                                                    <option value="title" <?php echo ($settings['map_title_museum'] ?? '') === 'title' ? 'selected' : ''; ?>>Title</option>
                                                    <option value="none" <?php echo ($settings['map_title_museum'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_title_dam]">
                                                    <option value="title" <?php echo ($settings['map_title_dam'] ?? 'title') === 'title' ? 'selected' : ''; ?>>Title / Filename</option>
                                                    <option value="caption" <?php echo ($settings['map_title_dam'] ?? '') === 'caption' ? 'selected' : ''; ?>>Caption</option>
                                                    <option value="none" <?php echo ($settings['map_title_dam'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-user text-muted"></i> Creator/Author</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_creator_isad]">
                                                    <option value="nameAccessPoints" <?php echo ($settings['map_creator_isad'] ?? 'nameAccessPoints') === 'nameAccessPoints' ? 'selected' : ''; ?>>Name Access Points</option>
                                                    <option value="creators" <?php echo ($settings['map_creator_isad'] ?? '') === 'creators' ? 'selected' : ''; ?>>Creators (Event)</option>
                                                    <option value="none" <?php echo ($settings['map_creator_isad'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_creator_museum]">
                                                    <option value="productionPerson" <?php echo ($settings['map_creator_museum'] ?? 'productionPerson') === 'productionPerson' ? 'selected' : ''; ?>>Production Person</option>
                                                    <option value="nameAccessPoints" <?php echo ($settings['map_creator_museum'] ?? '') === 'nameAccessPoints' ? 'selected' : ''; ?>>Name Access Points</option>
                                                    <option value="none" <?php echo ($settings['map_creator_museum'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_creator_dam]">
                                                    <option value="creator" <?php echo ($settings['map_creator_dam'] ?? 'creator') === 'creator' ? 'selected' : ''; ?>>Creator / Photographer</option>
                                                    <option value="creditLine" <?php echo ($settings['map_creator_dam'] ?? '') === 'creditLine' ? 'selected' : ''; ?>>Credit Line</option>
                                                    <option value="none" <?php echo ($settings['map_creator_dam'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-tags text-muted"></i> Keywords</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_keywords_isad]">
                                                    <option value="subjectAccessPoints" <?php echo ($settings['map_keywords_isad'] ?? 'subjectAccessPoints') === 'subjectAccessPoints' ? 'selected' : ''; ?>>Subject Access Points</option>
                                                    <option value="genreAccessPoints" <?php echo ($settings['map_keywords_isad'] ?? '') === 'genreAccessPoints' ? 'selected' : ''; ?>>Genre Access Points</option>
                                                    <option value="none" <?php echo ($settings['map_keywords_isad'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_keywords_museum]">
                                                    <option value="objectCategory" <?php echo ($settings['map_keywords_museum'] ?? 'objectCategory') === 'objectCategory' ? 'selected' : ''; ?>>Object Category</option>
                                                    <option value="subjectAccessPoints" <?php echo ($settings['map_keywords_museum'] ?? '') === 'subjectAccessPoints' ? 'selected' : ''; ?>>Subject Access Points</option>
                                                    <option value="none" <?php echo ($settings['map_keywords_museum'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_keywords_dam]">
                                                    <option value="keywords" <?php echo ($settings['map_keywords_dam'] ?? 'keywords') === 'keywords' ? 'selected' : ''; ?>>Keywords / Tags</option>
                                                    <option value="category" <?php echo ($settings['map_keywords_dam'] ?? '') === 'category' ? 'selected' : ''; ?>>Category</option>
                                                    <option value="none" <?php echo ($settings['map_keywords_dam'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-align-left text-muted"></i> Description</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_description_isad]">
                                                    <option value="scopeAndContent" <?php echo ($settings['map_description_isad'] ?? 'scopeAndContent') === 'scopeAndContent' ? 'selected' : ''; ?>>Scope and Content</option>
                                                    <option value="archivalHistory" <?php echo ($settings['map_description_isad'] ?? '') === 'archivalHistory' ? 'selected' : ''; ?>>Archival History</option>
                                                    <option value="none" <?php echo ($settings['map_description_isad'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_description_museum]">
                                                    <option value="briefDescription" <?php echo ($settings['map_description_museum'] ?? 'briefDescription') === 'briefDescription' ? 'selected' : ''; ?>>Brief Description</option>
                                                    <option value="physicalDescription" <?php echo ($settings['map_description_museum'] ?? '') === 'physicalDescription' ? 'selected' : ''; ?>>Physical Description</option>
                                                    <option value="none" <?php echo ($settings['map_description_museum'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_description_dam]">
                                                    <option value="caption" <?php echo ($settings['map_description_dam'] ?? 'caption') === 'caption' ? 'selected' : ''; ?>>Caption / Description</option>
                                                    <option value="instructions" <?php echo ($settings['map_description_dam'] ?? '') === 'instructions' ? 'selected' : ''; ?>>Special Instructions</option>
                                                    <option value="none" <?php echo ($settings['map_description_dam'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-calendar text-muted"></i> Date Created</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_date_isad]">
                                                    <option value="creationEvent" <?php echo ($settings['map_date_isad'] ?? 'creationEvent') === 'creationEvent' ? 'selected' : ''; ?>>Creation Event Date</option>
                                                    <option value="none" <?php echo ($settings['map_date_isad'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_date_museum]">
                                                    <option value="productionDate" <?php echo ($settings['map_date_museum'] ?? 'productionDate') === 'productionDate' ? 'selected' : ''; ?>>Production Date</option>
                                                    <option value="none" <?php echo ($settings['map_date_museum'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_date_dam]">
                                                    <option value="dateCreated" <?php echo ($settings['map_date_dam'] ?? 'dateCreated') === 'dateCreated' ? 'selected' : ''; ?>>Date Created / Taken</option>
                                                    <option value="dateModified" <?php echo ($settings['map_date_dam'] ?? '') === 'dateModified' ? 'selected' : ''; ?>>Date Modified</option>
                                                    <option value="none" <?php echo ($settings['map_date_dam'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-copyright text-muted"></i> Copyright</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_copyright_isad]">
                                                    <option value="accessConditions" <?php echo ($settings['map_copyright_isad'] ?? 'accessConditions') === 'accessConditions' ? 'selected' : ''; ?>>Access Conditions</option>
                                                    <option value="reproductionConditions" <?php echo ($settings['map_copyright_isad'] ?? '') === 'reproductionConditions' ? 'selected' : ''; ?>>Reproduction Conditions</option>
                                                    <option value="none" <?php echo ($settings['map_copyright_isad'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_copyright_museum]">
                                                    <option value="rightsNotes" <?php echo ($settings['map_copyright_museum'] ?? 'rightsNotes') === 'rightsNotes' ? 'selected' : ''; ?>>Rights Notes</option>
                                                    <option value="none" <?php echo ($settings['map_copyright_museum'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_copyright_dam]">
                                                    <option value="copyrightNotice" <?php echo ($settings['map_copyright_dam'] ?? 'copyrightNotice') === 'copyrightNotice' ? 'selected' : ''; ?>>Copyright Notice</option>
                                                    <option value="usageRights" <?php echo ($settings['map_copyright_dam'] ?? '') === 'usageRights' ? 'selected' : ''; ?>>Usage Rights</option>
                                                    <option value="none" <?php echo ($settings['map_copyright_dam'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-camera text-muted"></i> Technical Data</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_technical_isad]">
                                                    <option value="physicalCharacteristics" <?php echo ($settings['map_technical_isad'] ?? 'physicalCharacteristics') === 'physicalCharacteristics' ? 'selected' : ''; ?>>Physical Characteristics</option>
                                                    <option value="extentAndMedium" <?php echo ($settings['map_technical_isad'] ?? '') === 'extentAndMedium' ? 'selected' : ''; ?>>Extent and Medium</option>
                                                    <option value="none" <?php echo ($settings['map_technical_isad'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_technical_museum]">
                                                    <option value="technicalDescription" <?php echo ($settings['map_technical_museum'] ?? 'technicalDescription') === 'technicalDescription' ? 'selected' : ''; ?>>Technical Description</option>
                                                    <option value="none" <?php echo ($settings['map_technical_museum'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_technical_dam]">
                                                    <option value="technicalInfo" <?php echo ($settings['map_technical_dam'] ?? 'technicalInfo') === 'technicalInfo' ? 'selected' : ''; ?>>Technical Info (EXIF)</option>
                                                    <option value="cameraInfo" <?php echo ($settings['map_technical_dam'] ?? '') === 'cameraInfo' ? 'selected' : ''; ?>>Camera / Equipment</option>
                                                    <option value="none" <?php echo ($settings['map_technical_dam'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-map-marker-alt text-muted"></i> GPS Location</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_gps_isad]">
                                                    <option value="placeAccessPoints" <?php echo ($settings['map_gps_isad'] ?? 'placeAccessPoints') === 'placeAccessPoints' ? 'selected' : ''; ?>>Place Access Points</option>
                                                    <option value="physicalCharacteristics" <?php echo ($settings['map_gps_isad'] ?? '') === 'physicalCharacteristics' ? 'selected' : ''; ?>>Physical Characteristics</option>
                                                    <option value="none" <?php echo ($settings['map_gps_isad'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_gps_museum]">
                                                    <option value="fieldCollectionPlace" <?php echo ($settings['map_gps_museum'] ?? 'fieldCollectionPlace') === 'fieldCollectionPlace' ? 'selected' : ''; ?>>Field Collection Place</option>
                                                    <option value="placeAccessPoints" <?php echo ($settings['map_gps_museum'] ?? '') === 'placeAccessPoints' ? 'selected' : ''; ?>>Place Access Points</option>
                                                    <option value="none" <?php echo ($settings['map_gps_museum'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_gps_dam]">
                                                    <option value="gpsLocation" <?php echo ($settings['map_gps_dam'] ?? 'gpsLocation') === 'gpsLocation' ? 'selected' : ''; ?>>GPS Coordinates</option>
                                                    <option value="location" <?php echo ($settings['map_gps_dam'] ?? '') === 'location' ? 'selected' : ''; ?>>Location Name</option>
                                                    <option value="none" <?php echo ($settings['map_gps_dam'] ?? '') === 'none' ? 'selected' : ''; ?>>Do not map</option>
                                                </select></td>
                                            </tr>
                                        </tbody>
                                    </table>
                            <?php break; ?>
                                </fieldset>
                            <?php case 'faces': ?>
                                <fieldset class="mb-4">
                                    <legend><?php echo __('Face Detection'); ?></legend>
                                    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <?php echo __('Experimental feature.'); ?></div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Enable'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="face_enabled" name="settings[face_enabled]" value="true" <?php echo ($settings['face_enabled'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="face_enabled"><?php echo __('Detect faces'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Backend'); ?></label>
                                        <div class="col-sm-9">
                                            <select class="form-control" name="settings[face_backend]">
                                                <option value="local" <?php echo ($settings['face_backend'] ?? 'local') === 'local' ? 'selected' : ''; ?>>Local (OpenCV)</option>
                                                <option value="aws" <?php echo ($settings['face_backend'] ?? '') === 'aws' ? 'selected' : ''; ?>>AWS Rekognition</option>
                                                <option value="azure" <?php echo ($settings['face_backend'] ?? '') === 'azure' ? 'selected' : ''; ?>>Azure Face API</option>
                                            </select>
                                        </div>
                                    </div>
                                </fieldset>
                            <?php break; ?>

                        <?php case 'multi_tenant': ?>
                            <?php include_partial('ahgSettings/multiTenantSettings', ['settings' => $settings]) ?>
                            <?php break; ?>

                        <?php case 'ingest': ?>
                            <!-- AI & Processing Defaults -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-brain me-2"></i><?php echo __('AI & Processing Defaults') ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3"><?php echo __('These defaults are pre-selected when creating a new ingest session. Users can override per session.') ?></p>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_virus_scan"
                                                       name="settings[ingest_virus_scan]" value="true"
                                                       <?php echo ($settings['ingest_virus_scan'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_virus_scan">
                                                    <strong><i class="fas fa-shield-virus me-1 text-danger"></i><?php echo __('Virus Scan (ClamAV)') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Scan all uploaded files for malware before commit. Infected files are quarantined.') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_ocr"
                                                       name="settings[ingest_ocr]" value="true"
                                                       <?php echo ($settings['ingest_ocr'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_ocr">
                                                    <strong><i class="fas fa-file-alt me-1 text-primary"></i><?php echo __('OCR (Tesseract)') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Extract text from images and PDFs using Tesseract / pdftotext.') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_ner"
                                                       name="settings[ingest_ner]" value="true"
                                                       <?php echo ($settings['ingest_ner'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_ner">
                                                    <strong><i class="fas fa-tags me-1 text-success"></i><?php echo __('NER (Named Entity Recognition)') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Extract persons, organizations, places and dates from text fields. Creates access points automatically.') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_summarize"
                                                       name="settings[ingest_summarize]" value="true"
                                                       <?php echo ($settings['ingest_summarize'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_summarize">
                                                    <strong><i class="fas fa-compress-alt me-1 text-warning"></i><?php echo __('Auto-Summarize') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Generate scope and content summaries for records with extensive text.') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_spellcheck"
                                                       name="settings[ingest_spellcheck]" value="true"
                                                       <?php echo ($settings['ingest_spellcheck'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_spellcheck">
                                                    <strong><i class="fas fa-spell-check me-1 text-info"></i><?php echo __('Spell Check (aspell)') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Check spelling and grammar on title, scope and content, and archival history fields.') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_format_id"
                                                       name="settings[ingest_format_id]" value="true"
                                                       <?php echo ($settings['ingest_format_id'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_format_id">
                                                    <strong><i class="fas fa-fingerprint me-1 text-secondary"></i><?php echo __('Format Identification (Siegfried/PRONOM)') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Identify file formats using PRONOM registry via Siegfried. Records PUID, MIME type, and confidence.') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_face_detect"
                                                       name="settings[ingest_face_detect]" value="true"
                                                       <?php echo ($settings['ingest_face_detect'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_face_detect">
                                                    <strong><i class="fas fa-user-circle me-1 text-dark"></i><?php echo __('Face Detection') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Detect and match faces in images to authority records. Supports OpenCV, AWS, Azure, Google backends.') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_translate"
                                                       name="settings[ingest_translate]" value="true"
                                                       <?php echo ($settings['ingest_translate'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_translate">
                                                    <strong><i class="fas fa-language me-1 text-primary"></i><?php echo __('Auto-Translate (Argos)') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Translate metadata fields using offline Argos Translate engine.') ?></div>
                                        </div>
                                    </div>

                                    <!-- Translation language -->
                                    <div class="row g-3 mt-2" id="translate-lang-row">
                                        <div class="col-md-4">
                                            <label for="ingest_translate_from" class="form-label"><?php echo __('Translate from') ?></label>
                                            <select class="form-select" id="ingest_translate_from" name="settings[ingest_translate_from]">
                                                <?php foreach (['en' => 'English', 'af' => 'Afrikaans', 'zu' => 'Zulu', 'xh' => 'Xhosa', 'st' => 'Sotho', 'fr' => 'French', 'de' => 'German', 'pt' => 'Portuguese', 'es' => 'Spanish', 'nl' => 'Dutch'] as $code => $name): ?>
                                                    <option value="<?php echo $code ?>" <?php echo ($settings['ingest_translate_from'] ?? 'en') === $code ? 'selected' : '' ?>><?php echo $name ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="ingest_translate_to" class="form-label"><?php echo __('Translate to') ?></label>
                                            <select class="form-select" id="ingest_translate_to" name="settings[ingest_translate_to]">
                                                <?php foreach (['af' => 'Afrikaans', 'en' => 'English', 'zu' => 'Zulu', 'xh' => 'Xhosa', 'st' => 'Sotho', 'fr' => 'French', 'de' => 'German', 'pt' => 'Portuguese', 'es' => 'Spanish', 'nl' => 'Dutch'] as $code => $name): ?>
                                                    <option value="<?php echo $code ?>" <?php echo ($settings['ingest_translate_to'] ?? 'af') === $code ? 'selected' : '' ?>><?php echo $name ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="ingest_spellcheck_lang" class="form-label"><?php echo __('Spellcheck language') ?></label>
                                            <select class="form-select" id="ingest_spellcheck_lang" name="settings[ingest_spellcheck_lang]">
                                                <?php foreach (['en_ZA' => 'English (ZA)', 'en_US' => 'English (US)', 'en_GB' => 'English (UK)', 'af' => 'Afrikaans'] as $code => $name): ?>
                                                    <option value="<?php echo $code ?>" <?php echo ($settings['ingest_spellcheck_lang'] ?? 'en_ZA') === $code ? 'selected' : '' ?>><?php echo $name ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Output Defaults -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i><?php echo __('Output Defaults') ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_create_records"
                                                       name="settings[ingest_create_records]" value="true"
                                                       <?php echo ($settings['ingest_create_records'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_create_records"><?php echo __('Create AtoM records') ?></label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_generate_sip"
                                                       name="settings[ingest_generate_sip]" value="true"
                                                       <?php echo ($settings['ingest_generate_sip'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_generate_sip"><?php echo __('Generate SIP package') ?></label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_generate_aip"
                                                       name="settings[ingest_generate_aip]" value="true"
                                                       <?php echo ($settings['ingest_generate_aip'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_generate_aip"><?php echo __('Generate AIP package') ?></label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_generate_dip"
                                                       name="settings[ingest_generate_dip]" value="true"
                                                       <?php echo ($settings['ingest_generate_dip'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_generate_dip"><?php echo __('Generate DIP package') ?></label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_thumbnails"
                                                       name="settings[ingest_thumbnails]" value="true"
                                                       <?php echo ($settings['ingest_thumbnails'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_thumbnails"><?php echo __('Generate thumbnails') ?></label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ingest_reference"
                                                       name="settings[ingest_reference]" value="true"
                                                       <?php echo ($settings['ingest_reference'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ingest_reference"><?php echo __('Generate reference images') ?></label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <label for="ingest_sip_path" class="form-label"><?php echo __('Default SIP output path') ?></label>
                                            <input type="text" class="form-control" id="ingest_sip_path" name="settings[ingest_sip_path]"
                                                   value="<?php echo htmlspecialchars($settings['ingest_sip_path'] ?? '') ?>"
                                                   placeholder="/uploads/sip">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="ingest_aip_path" class="form-label"><?php echo __('Default AIP output path') ?></label>
                                            <input type="text" class="form-control" id="ingest_aip_path" name="settings[ingest_aip_path]"
                                                   value="<?php echo htmlspecialchars($settings['ingest_aip_path'] ?? '') ?>"
                                                   placeholder="/uploads/aip">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="ingest_dip_path" class="form-label"><?php echo __('Default DIP output path') ?></label>
                                            <input type="text" class="form-control" id="ingest_dip_path" name="settings[ingest_dip_path]"
                                                   value="<?php echo htmlspecialchars($settings['ingest_dip_path'] ?? '') ?>"
                                                   placeholder="/uploads/dip">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="ingest_default_sector" class="form-label"><?php echo __('Default sector') ?></label>
                                            <select class="form-select" id="ingest_default_sector" name="settings[ingest_default_sector]">
                                                <?php foreach (['archive' => 'Archive', 'museum' => 'Museum', 'library' => 'Library', 'gallery' => 'Gallery', 'dam' => 'DAM'] as $val => $label): ?>
                                                    <option value="<?php echo $val ?>" <?php echo ($settings['ingest_default_sector'] ?? 'archive') === $val ? 'selected' : '' ?>><?php echo __($label) ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="ingest_default_standard" class="form-label"><?php echo __('Default descriptive standard') ?></label>
                                            <select class="form-select" id="ingest_default_standard" name="settings[ingest_default_standard]">
                                                <?php foreach (['isadg' => 'ISAD(G)', 'dc' => 'Dublin Core', 'rad' => 'RAD', 'dacs' => 'DACS', 'spectrum' => 'SPECTRUM', 'cco' => 'CCO'] as $val => $label): ?>
                                                    <option value="<?php echo $val ?>" <?php echo ($settings['ingest_default_standard'] ?? 'isadg') === $val ? 'selected' : '' ?>><?php echo $label ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Service Status -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i><?php echo __('Service Availability') ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3"><?php echo __('Processing options require the corresponding services to be installed and running.') ?></p>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th><?php echo __('Service') ?></th>
                                                    <th><?php echo __('Required Plugin / Tool') ?></th>
                                                    <th><?php echo __('Status') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $services = [
                                                    ['Virus Scan', 'ClamAV daemon', function() { return @shell_exec('clamdscan --version 2>/dev/null') ? true : false; }],
                                                    ['OCR', 'tesseract + pdftotext', function() { return @shell_exec('tesseract --version 2>&1') ? true : false; }],
                                                    ['NER', 'ahgAIPlugin + Python API', function() { return class_exists('ahgAIPluginConfiguration'); }],
                                                    ['Summarize', 'ahgAIPlugin + Python API', function() { return class_exists('ahgAIPluginConfiguration'); }],
                                                    ['Spell Check', 'aspell', function() { return @shell_exec('aspell --version 2>&1') ? true : false; }],
                                                    ['Translation', 'ahgAIPlugin + Argos Translate', function() { return class_exists('ahgAIPluginConfiguration'); }],
                                                    ['Format ID', 'ahgPreservationPlugin + Siegfried', function() { return @shell_exec('sf -version 2>&1') ? true : false; }],
                                                    ['Face Detection', 'ahgAIPlugin', function() { return class_exists('ahgAIPluginConfiguration'); }],
                                                ];
                                                foreach ($services as $svc):
                                                    $available = $svc[2]();
                                                ?>
                                                <tr>
                                                    <td><strong><?php echo __($svc[0]) ?></strong></td>
                                                    <td><code><?php echo $svc[1] ?></code></td>
                                                    <td>
                                                        <?php if ($available): ?>
                                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Available') ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary"><i class="fas fa-times me-1"></i><?php echo __('Not installed') ?></span>
                                                        <?php endif ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php break; ?>

                        <?php case 'portable_export': ?>
                                <!-- Portable Export Settings -->
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fas fa-compact-disc me-2"></i><?php echo __('Portable Export Configuration') ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3"><?php echo __('Configure defaults for standalone portable catalogue exports (CD/USB/ZIP distribution).') ?></p>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_enabled"
                                                           name="settings[portable_export_enabled]" value="true"
                                                           <?php echo ($settings['portable_export_enabled'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="portable_export_enabled">
                                                        <strong><?php echo __('Enable Portable Export') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Allow creation of offline portable catalogues from Admin UI.') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo __('Retention (days)') ?></label>
                                                <input type="number" class="form-control" name="settings[portable_export_retention_days]"
                                                       value="<?php echo htmlspecialchars($settings['portable_export_retention_days'] ?? '30') ?>" min="1" max="365">
                                                <div class="form-text"><?php echo __('Completed exports are auto-deleted after this many days. Run portable:cleanup.') ?></div>
                                            </div>
                                        </div>

                                        <hr>
                                        <h6 class="mb-3"><?php echo __('Default Content Options') ?></h6>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_include_objects"
                                                           name="settings[portable_export_include_objects]" value="true"
                                                           <?php echo ($settings['portable_export_include_objects'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="portable_export_include_objects"><?php echo __('Digital Objects') ?></label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_include_thumbnails"
                                                           name="settings[portable_export_include_thumbnails]" value="true"
                                                           <?php echo ($settings['portable_export_include_thumbnails'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="portable_export_include_thumbnails"><?php echo __('Thumbnails') ?></label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_include_references"
                                                           name="settings[portable_export_include_references]" value="true"
                                                           <?php echo ($settings['portable_export_include_references'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="portable_export_include_references"><?php echo __('Reference Images') ?></label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_include_masters"
                                                           name="settings[portable_export_include_masters]" value="true"
                                                           <?php echo ($settings['portable_export_include_masters'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="portable_export_include_masters"><?php echo __('Master Files') ?></label>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>
                                        <h6 class="mb-3"><?php echo __('Default Settings') ?></h6>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label"><?php echo __('Default Viewer Mode') ?></label>
                                                <select class="form-select" name="settings[portable_export_default_mode]">
                                                    <option value="read_only" <?php echo ($settings['portable_export_default_mode'] ?? 'read_only') === 'read_only' ? 'selected' : '' ?>><?php echo __('Read Only') ?></option>
                                                    <option value="editable" <?php echo ($settings['portable_export_default_mode'] ?? '') === 'editable' ? 'selected' : '' ?>><?php echo __('Editable') ?></option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label"><?php echo __('Default Language') ?></label>
                                                <select class="form-select" name="settings[portable_export_default_culture]">
                                                    <option value="en" <?php echo ($settings['portable_export_default_culture'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                                                    <option value="fr" <?php echo ($settings['portable_export_default_culture'] ?? '') === 'fr' ? 'selected' : '' ?>>French</option>
                                                    <option value="af" <?php echo ($settings['portable_export_default_culture'] ?? '') === 'af' ? 'selected' : '' ?>>Afrikaans</option>
                                                    <option value="pt" <?php echo ($settings['portable_export_default_culture'] ?? '') === 'pt' ? 'selected' : '' ?>>Portuguese</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label"><?php echo __('Max Export Size (MB)') ?></label>
                                                <input type="number" class="form-control" name="settings[portable_export_max_size_mb]"
                                                       value="<?php echo htmlspecialchars($settings['portable_export_max_size_mb'] ?? '2048') ?>" min="100" max="10240">
                                            </div>
                                        </div>

                                        <hr>
                                        <h6 class="mb-3"><?php echo __('Integration') ?></h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_description_button"
                                                           name="settings[portable_export_description_button]" value="true"
                                                           <?php echo ($settings['portable_export_description_button'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="portable_export_description_button">
                                                        <?php echo __('Show export button on description pages') ?>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Adds "Portable Viewer" to the Export section on archival description pages.') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_clipboard_button"
                                                           name="settings[portable_export_clipboard_button]" value="true"
                                                           <?php echo ($settings['portable_export_clipboard_button'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="portable_export_clipboard_button">
                                                        <?php echo __('Show export button on clipboard page') ?>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Adds "Portable Catalogue" option to the clipboard export page.') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php break; ?>

                        <?php case 'encryption': ?>
                                <!-- Encryption Master Toggle -->
                                <div class="card mb-4">
                                    <div class="card-header bg-dark text-white">
                                        <h5 class="mb-0"><i class="fas fa-lock me-2"></i><?php echo __('Encryption Configuration') ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3"><?php echo __('Encryption for digital object files and sensitive database fields using') ?> <strong><?php echo $algoName ?></strong>. <?php echo __('Requires an encryption key at /etc/atom/encryption.key.') ?></p>

                                        <?php
                                            $keyExists = file_exists('/etc/atom/encryption.key');
                                            $keyPerms = $keyExists ? substr(sprintf('%o', fileperms('/etc/atom/encryption.key')), -4) : null;
                                            $hasSodium = extension_loaded('sodium') && function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push');
                                            $algoName = $hasSodium ? 'XChaCha20-Poly1305 (libsodium)' : 'AES-256-GCM (OpenSSL)';
                                        ?>

                                        <!-- Key Status -->
                                        <div class="alert <?php echo $keyExists ? 'alert-success' : 'alert-warning' ?> mb-3">
                                            <i class="fas <?php echo $keyExists ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
                                            <?php if ($keyExists): ?>
                                                <strong><?php echo __('Encryption key found') ?></strong>
                                                <span class="ms-2 text-muted"><?php echo __('Path:') ?> <code>/etc/atom/encryption.key</code> | <?php echo __('Permissions:') ?> <code><?php echo $keyPerms ?></code> | <?php echo __('Algorithm:') ?> <code><?php echo $algoName ?></code></span>
                                                <?php if ($keyPerms !== '0600'): ?>
                                                    <br><small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i><?php echo __('Permissions should be 0600 for security.') ?></small>
                                                <?php endif ?>
                                            <?php else: ?>
                                                <strong><?php echo __('No encryption key found') ?></strong>
                                                <br><small><?php echo __('Generate with:') ?> <code>php bin/atom encryption:key --generate</code></small>
                                            <?php endif ?>
                                        </div>

                                        <!-- Master Toggle -->
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="encryption_enabled"
                                                   name="settings[encryption_enabled]" value="true"
                                                   <?php echo ($settings['encryption_enabled'] ?? '') === 'true' ? 'checked' : '' ?>
                                                   <?php echo !$keyExists ? 'disabled' : '' ?>>
                                            <label class="form-check-label" for="encryption_enabled">
                                                <strong><?php echo __('Enable Encryption') ?></strong>
                                            </label>
                                        </div>
                                        <div class="form-text mb-3"><?php echo __('Master toggle. When enabled, new file uploads will be encrypted automatically.') ?></div>
                                    </div>
                                </div>

                                <!-- Layer 1: File Encryption -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-file-shield me-2"></i><?php echo __('Layer 1: Digital Object Encryption') ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3"><?php echo __('Encrypts uploaded files (masters and derivatives) on disk using') ?> <?php echo $algoName ?>.</p>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="encryption_encrypt_derivatives"
                                                   name="settings[encryption_encrypt_derivatives]" value="true"
                                                   <?php echo ($settings['encryption_encrypt_derivatives'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="encryption_encrypt_derivatives">
                                                <strong><?php echo __('Encrypt derivatives') ?></strong>
                                            </label>
                                        </div>
                                        <div class="form-text mb-3"><?php echo __('Also encrypt thumbnails and reference images. Recommended for full protection.') ?></div>

                                        <?php
                                            $totalDOs = 0;
                                            try {
                                                $totalDOs = \Illuminate\Database\Capsule\Manager::table('digital_object')
                                                    ->whereNotNull('path')
                                                    ->whereNotNull('name')
                                                    ->count();
                                            } catch (\Exception $e) {}
                                        ?>

                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong><?php echo $totalDOs ?></strong> <?php echo __('digital objects on disk.') ?>
                                            <br><small><?php echo __('To encrypt existing files:') ?> <code>php bin/atom encryption:encrypt-files --limit=100</code></small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Layer 2: Field Encryption -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-database me-2"></i><?php echo __('Layer 2: Database Field Encryption') ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3"><?php echo __('Transparent encryption of sensitive database columns. Toggle categories below, then run the CLI to encrypt existing data.') ?></p>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="encryption_field_contact_details"
                                                           name="settings[encryption_field_contact_details]" value="true"
                                                           <?php echo ($settings['encryption_field_contact_details'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="encryption_field_contact_details">
                                                        <strong><i class="fas fa-address-card me-1 text-primary"></i><?php echo __('Contact Details') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Email, address, telephone, fax, contact person (contact_information tables).') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="encryption_field_financial_data"
                                                           name="settings[encryption_field_financial_data]" value="true"
                                                           <?php echo ($settings['encryption_field_financial_data'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="encryption_field_financial_data">
                                                        <strong><i class="fas fa-coins me-1 text-warning"></i><?php echo __('Financial Data') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Appraisal values in accession records.') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="encryption_field_donor_information"
                                                           name="settings[encryption_field_donor_information]" value="true"
                                                           <?php echo ($settings['encryption_field_donor_information'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="encryption_field_donor_information">
                                                        <strong><i class="fas fa-user-shield me-1 text-success"></i><?php echo __('Donor Information') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Actor history (biographical/administrative history for donors).') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="encryption_field_personal_notes"
                                                           name="settings[encryption_field_personal_notes]" value="true"
                                                           <?php echo ($settings['encryption_field_personal_notes'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="encryption_field_personal_notes">
                                                        <strong><i class="fas fa-sticky-note me-1 text-info"></i><?php echo __('Personal Notes') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Note content (internal staff notes on records).') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="encryption_field_access_restrictions"
                                                           name="settings[encryption_field_access_restrictions]" value="true"
                                                           <?php echo ($settings['encryption_field_access_restrictions'] ?? '') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="encryption_field_access_restrictions">
                                                        <strong><i class="fas fa-ban me-1 text-danger"></i><?php echo __('Access Restrictions') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Rights notes (access restriction details in rights statements).') ?></div>
                                            </div>
                                        </div>

                                        <div class="alert alert-secondary mt-3 mb-0">
                                            <i class="fas fa-terminal me-2"></i>
                                            <strong><?php echo __('CLI Commands') ?></strong>
                                            <br><code>php bin/atom encryption:encrypt-fields --category=contact_details</code> — <?php echo __('Encrypt a category') ?>
                                            <br><code>php bin/atom encryption:encrypt-fields --category=contact_details --reverse</code> — <?php echo __('Decrypt a category') ?>
                                            <br><code>php bin/atom encryption:encrypt-fields --list</code> — <?php echo __('Show category status') ?>
                                            <br><code>php bin/atom encryption:status</code> — <?php echo __('Full encryption dashboard') ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Compliance Note -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-gavel me-2"></i><?php echo __('Compliance') ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2"><?php echo __('Encryption at rest satisfies requirements from:') ?></p>
                                        <ul class="mb-0">
                                            <li><strong>POPIA</strong> — <?php echo __('Protection of Personal Information Act (South Africa), Section 19') ?></li>
                                            <li><strong>GDPR</strong> — <?php echo __('General Data Protection Regulation (EU), Article 32') ?></li>
                                            <li><strong>CCPA</strong> — <?php echo __('California Consumer Privacy Act, reasonable security measures') ?></li>
                                            <li><strong>NARSSA</strong> — <?php echo __('National Archives and Record Service of South Africa') ?></li>
                                            <li><strong>PAIA</strong> — <?php echo __('Promotion of Access to Information Act, secure record keeping') ?></li>
                                        </ul>
                                    </div>
                                </div>
                            <?php break; ?>

                        <?php case 'voice_ai': ?>
                                <!-- Voice Commands -->
                                <fieldset class="mb-4">
                                    <legend><i class="fas fa-microphone me-2"></i><?php echo __('Voice Commands') ?></legend>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="voice_enabled"
                                                       name="settings[voice_enabled]" value="true"
                                                       <?php echo ($settings['voice_enabled'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="voice_enabled">
                                                    <strong><?php echo __('Enable Voice Commands') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Allow users to navigate and control the application using voice commands.') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="voice_language"><?php echo __('Voice Language') ?></label>
                                            <select class="form-select" id="voice_language" name="settings[voice_language]">
                                                <option value="en-US" <?php echo ($settings['voice_language'] ?? 'en-US') === 'en-US' ? 'selected' : '' ?>>English (US)</option>
                                                <option value="en-GB" <?php echo ($settings['voice_language'] ?? '') === 'en-GB' ? 'selected' : '' ?>>English (UK)</option>
                                                <option value="af-ZA" <?php echo ($settings['voice_language'] ?? '') === 'af-ZA' ? 'selected' : '' ?>>Afrikaans</option>
                                                <option value="zu-ZA" <?php echo ($settings['voice_language'] ?? '') === 'zu-ZA' ? 'selected' : '' ?>>isiZulu</option>
                                                <option value="xh-ZA" <?php echo ($settings['voice_language'] ?? '') === 'xh-ZA' ? 'selected' : '' ?>>isiXhosa</option>
                                                <option value="st-ZA" <?php echo ($settings['voice_language'] ?? '') === 'st-ZA' ? 'selected' : '' ?>>Sesotho</option>
                                                <option value="fr-FR" <?php echo ($settings['voice_language'] ?? '') === 'fr-FR' ? 'selected' : '' ?>>French</option>
                                                <option value="pt-PT" <?php echo ($settings['voice_language'] ?? '') === 'pt-PT' ? 'selected' : '' ?>>Portuguese</option>
                                                <option value="es-ES" <?php echo ($settings['voice_language'] ?? '') === 'es-ES' ? 'selected' : '' ?>>Spanish</option>
                                                <option value="de-DE" <?php echo ($settings['voice_language'] ?? '') === 'de-DE' ? 'selected' : '' ?>>German</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <label class="form-label" for="voice_confidence_threshold"><?php echo __('Confidence Threshold') ?>: <span id="voice_confidence_threshold_val"><?php echo htmlspecialchars($settings['voice_confidence_threshold'] ?? '0.4') ?></span></label>
                                            <input type="range" class="form-range" id="voice_confidence_threshold"
                                                   name="settings[voice_confidence_threshold]"
                                                   min="0.3" max="0.95" step="0.05"
                                                   value="<?php echo htmlspecialchars($settings['voice_confidence_threshold'] ?? '0.4') ?>"
                                                   oninput="document.getElementById('voice_confidence_threshold_val').textContent=this.value">
                                            <div class="form-text"><?php echo __('Minimum confidence score for voice recognition (0.3 = lenient, 0.95 = strict).') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="voice_speech_rate"><?php echo __('Speech Rate') ?>: <span id="voice_speech_rate_val"><?php echo htmlspecialchars($settings['voice_speech_rate'] ?? '1.0') ?></span></label>
                                            <input type="range" class="form-range" id="voice_speech_rate"
                                                   name="settings[voice_speech_rate]"
                                                   min="0.5" max="2.0" step="0.1"
                                                   value="<?php echo htmlspecialchars($settings['voice_speech_rate'] ?? '1.0') ?>"
                                                   oninput="document.getElementById('voice_speech_rate_val').textContent=this.value">
                                            <div class="form-text"><?php echo __('Text-to-speech playback rate (0.5 = slow, 2.0 = fast).') ?></div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="voice_continuous_listening"
                                                       name="settings[voice_continuous_listening]" value="true"
                                                       <?php echo ($settings['voice_continuous_listening'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="voice_continuous_listening">
                                                    <strong><?php echo __('Continuous Listening') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Keep microphone active after each command (no need to re-activate).') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="voice_show_floating_btn"
                                                       name="settings[voice_show_floating_btn]" value="true"
                                                       <?php echo ($settings['voice_show_floating_btn'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="voice_show_floating_btn">
                                                    <strong><?php echo __('Show Floating Mic Button') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Display a floating microphone button on all pages for quick voice activation.') ?></div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="voice_hover_read_enabled"
                                                       name="settings[voice_hover_read_enabled]" value="true"
                                                       <?php echo ($settings['voice_hover_read_enabled'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="voice_hover_read_enabled">
                                                    <strong><?php echo __('Mouseover Read-Aloud') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Read button and link text aloud when hovering with the mouse (when voice mode is active).') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="voice_hover_read_delay"><?php echo __('Hover Read Delay') ?>: <span id="voice_hover_read_delay_val"><?php echo htmlspecialchars($settings['voice_hover_read_delay'] ?? '400') ?></span>ms</label>
                                            <input type="range" class="form-range" id="voice_hover_read_delay"
                                                   name="settings[voice_hover_read_delay]"
                                                   min="100" max="1000" step="50"
                                                   value="<?php echo htmlspecialchars($settings['voice_hover_read_delay'] ?? '400') ?>"
                                                   oninput="document.getElementById('voice_hover_read_delay_val').textContent=this.value">
                                            <div class="form-text"><?php echo __('Milliseconds to wait before reading (100 = instant, 1000 = slow). Lower values are more responsive.') ?></div>
                                        </div>
                                    </div>
                                </fieldset>

                                <!-- AI Image Description -->
                                <fieldset class="mb-4">
                                    <legend><i class="fas fa-brain me-2"></i><?php echo __('AI Image Description') ?></legend>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="voice_llm_provider"><?php echo __('LLM Provider') ?></label>
                                            <select class="form-select" id="voice_llm_provider" name="settings[voice_llm_provider]">
                                                <option value="local" <?php echo ($settings['voice_llm_provider'] ?? '') === 'local' ? 'selected' : '' ?>><?php echo __('Local Only') ?></option>
                                                <option value="cloud" <?php echo ($settings['voice_llm_provider'] ?? '') === 'cloud' ? 'selected' : '' ?>><?php echo __('Cloud Only') ?></option>
                                                <option value="hybrid" <?php echo ($settings['voice_llm_provider'] ?? 'hybrid') === 'hybrid' ? 'selected' : '' ?>><?php echo __('Hybrid (Local + Cloud Fallback)') ?></option>
                                            </select>
                                            <div class="form-text"><?php echo __('Choose where AI image descriptions are processed.') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="voice_daily_cloud_limit"><?php echo __('Daily Cloud Limit') ?></label>
                                            <input type="number" class="form-control" id="voice_daily_cloud_limit"
                                                   name="settings[voice_daily_cloud_limit]"
                                                   value="<?php echo htmlspecialchars($settings['voice_daily_cloud_limit'] ?? '50') ?>" min="0" max="10000">
                                            <div class="form-text"><?php echo __('Maximum cloud API calls per day (0 = unlimited).') ?></div>
                                        </div>
                                    </div>

                                    <hr>
                                    <h6 class="mb-3"><?php echo __('Local LLM Settings') ?></h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label" for="voice_local_llm_url"><?php echo __('Local LLM URL') ?></label>
                                            <input type="text" class="form-control" id="voice_local_llm_url"
                                                   name="settings[voice_local_llm_url]"
                                                   value="<?php echo htmlspecialchars($settings['voice_local_llm_url'] ?? 'http://localhost:11434') ?>"
                                                   placeholder="http://localhost:11434">
                                            <div class="form-text"><?php echo __('Ollama or compatible API endpoint.') ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="voice_local_llm_model"><?php echo __('Local LLM Model') ?></label>
                                            <input type="text" class="form-control" id="voice_local_llm_model"
                                                   name="settings[voice_local_llm_model]"
                                                   value="<?php echo htmlspecialchars($settings['voice_local_llm_model'] ?? 'llava:7b') ?>"
                                                   placeholder="llava:7b">
                                            <div class="form-text"><?php echo __('Vision-capable model name (e.g. llava:7b, bakllava).') ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="voice_local_llm_timeout"><?php echo __('Timeout (seconds)') ?></label>
                                            <input type="number" class="form-control" id="voice_local_llm_timeout"
                                                   name="settings[voice_local_llm_timeout]"
                                                   value="<?php echo htmlspecialchars($settings['voice_local_llm_timeout'] ?? '30') ?>" min="5" max="300">
                                            <div class="form-text"><?php echo __('Request timeout for local LLM API calls.') ?></div>
                                        </div>
                                    </div>

                                    <hr>
                                    <h6 class="mb-3"><?php echo __('Cloud LLM Settings') ?></h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label" for="voice_anthropic_api_key"><?php echo __('Anthropic API Key') ?></label>
                                            <input type="password" class="form-control" id="voice_anthropic_api_key"
                                                   name="settings[voice_anthropic_api_key]"
                                                   value="<?php echo htmlspecialchars($settings['voice_anthropic_api_key'] ?? '') ?>"
                                                   placeholder="sk-ant-...">
                                            <div class="form-text"><?php echo __('API key for Claude cloud vision. Stored encrypted.') ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="voice_cloud_model"><?php echo __('Cloud Model') ?></label>
                                            <input type="text" class="form-control" id="voice_cloud_model"
                                                   name="settings[voice_cloud_model]"
                                                   value="<?php echo htmlspecialchars($settings['voice_cloud_model'] ?? 'claude-sonnet-4-20250514') ?>"
                                                   placeholder="claude-sonnet-4-20250514">
                                            <div class="form-text"><?php echo __('Anthropic model ID for image descriptions.') ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" id="voice_audit_ai_calls"
                                                       name="settings[voice_audit_ai_calls]" value="true"
                                                       <?php echo ($settings['voice_audit_ai_calls'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="voice_audit_ai_calls">
                                                    <strong><?php echo __('Audit AI Calls') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Log all AI image description requests to the audit trail.') ?></div>
                                        </div>
                                    </div>
                                </fieldset>
                            <?php break; ?>

                        <?php case 'integrity': ?>
                                <!-- Integrity Verification Defaults -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?php echo __('Integrity Verification Defaults') ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="integrity_enabled"
                                                           name="settings[integrity_enabled]" value="true"
                                                           <?php echo ($settings['integrity_enabled'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="integrity_enabled">
                                                        <strong><?php echo __('Enable Integrity Assurance') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text mb-3"><?php echo __('Master switch for all integrity verification functionality.') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="integrity_auto_baseline"
                                                           name="settings[integrity_auto_baseline]" value="true"
                                                           <?php echo ($settings['integrity_auto_baseline'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="integrity_auto_baseline">
                                                        <strong><?php echo __('Auto-Generate Baselines') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text mb-3"><?php echo __('Automatically generate baseline checksums on first verification if none exist.') ?></div>
                                            </div>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label" for="integrity_default_algorithm"><?php echo __('Default Algorithm') ?></label>
                                                <select class="form-select" id="integrity_default_algorithm" name="settings[integrity_default_algorithm]">
                                                    <option value="sha256" <?php echo ($settings['integrity_default_algorithm'] ?? 'sha256') === 'sha256' ? 'selected' : '' ?>>SHA-256 (faster)</option>
                                                    <option value="sha512" <?php echo ($settings['integrity_default_algorithm'] ?? '') === 'sha512' ? 'selected' : '' ?>>SHA-512 (more secure)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="integrity_default_batch_size"><?php echo __('Default Batch Size') ?></label>
                                                <input type="number" class="form-control" id="integrity_default_batch_size"
                                                       name="settings[integrity_default_batch_size]"
                                                       value="<?php echo htmlspecialchars($settings['integrity_default_batch_size'] ?? '200') ?>" min="0" max="50000">
                                                <div class="form-text"><?php echo __('Objects per run (0 = unlimited).') ?></div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="integrity_io_throttle_ms"><?php echo __('IO Throttle (ms)') ?></label>
                                                <input type="number" class="form-control" id="integrity_io_throttle_ms"
                                                       name="settings[integrity_io_throttle_ms]"
                                                       value="<?php echo htmlspecialchars($settings['integrity_io_throttle_ms'] ?? '10') ?>" min="0" max="1000">
                                                <div class="form-text"><?php echo __('Millisecond pause between objects to reduce disk pressure.') ?></div>
                                            </div>
                                        </div>
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-4">
                                                <label class="form-label" for="integrity_default_max_runtime"><?php echo __('Max Runtime (minutes)') ?></label>
                                                <input type="number" class="form-control" id="integrity_default_max_runtime"
                                                       name="settings[integrity_default_max_runtime]"
                                                       value="<?php echo htmlspecialchars($settings['integrity_default_max_runtime'] ?? '120') ?>" min="1" max="1440">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="integrity_default_max_memory"><?php echo __('Max Memory (MB)') ?></label>
                                                <input type="number" class="form-control" id="integrity_default_max_memory"
                                                       name="settings[integrity_default_max_memory]"
                                                       value="<?php echo htmlspecialchars($settings['integrity_default_max_memory'] ?? '512') ?>" min="64" max="4096">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="integrity_dead_letter_threshold"><?php echo __('Dead Letter Threshold') ?></label>
                                                <input type="number" class="form-control" id="integrity_dead_letter_threshold"
                                                       name="settings[integrity_dead_letter_threshold]"
                                                       value="<?php echo htmlspecialchars($settings['integrity_dead_letter_threshold'] ?? '3') ?>" min="1" max="100">
                                                <div class="form-text"><?php echo __('Consecutive failures before escalation to dead letter queue.') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notification Defaults -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i><?php echo __('Notification Defaults') ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="integrity_notify_on_failure"
                                                           name="settings[integrity_notify_on_failure]" value="true"
                                                           <?php echo ($settings['integrity_notify_on_failure'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="integrity_notify_on_failure">
                                                        <strong><?php echo __('Notify on Run Failure') ?></strong>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="integrity_notify_on_mismatch"
                                                           name="settings[integrity_notify_on_mismatch]" value="true"
                                                           <?php echo ($settings['integrity_notify_on_mismatch'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="integrity_notify_on_mismatch">
                                                        <strong><?php echo __('Notify on Hash Mismatch') ?></strong>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="integrity_alert_email"><?php echo __('Default Alert Email') ?></label>
                                                <input type="email" class="form-control" id="integrity_alert_email"
                                                       name="settings[integrity_alert_email]"
                                                       value="<?php echo htmlspecialchars($settings['integrity_alert_email'] ?? '') ?>"
                                                       placeholder="admin@example.com">
                                                <div class="form-text"><?php echo __('Default email for integrity alerts (used by new schedules and alert rules).') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="integrity_webhook_url"><?php echo __('Default Webhook URL') ?></label>
                                                <input type="url" class="form-control" id="integrity_webhook_url"
                                                       name="settings[integrity_webhook_url]"
                                                       value="<?php echo htmlspecialchars($settings['integrity_webhook_url'] ?? '') ?>"
                                                       placeholder="https://hooks.slack.com/...">
                                                <div class="form-text"><?php echo __('Default webhook URL for alert notifications (Slack, Teams, PagerDuty, etc).') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Links -->
                                <div class="card mb-4 border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Quick Links') ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-auto">
                                                <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']) ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-tachometer-alt me-1"></i><?php echo __('Dashboard') ?>
                                                </a>
                                            </div>
                                            <div class="col-auto">
                                                <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'schedules']) ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-clock me-1"></i><?php echo __('Schedules') ?>
                                                </a>
                                            </div>
                                            <div class="col-auto">
                                                <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'policies']) ?>" class="btn btn-outline-warning">
                                                    <i class="fas fa-archive me-1"></i><?php echo __('Retention Policies') ?>
                                                </a>
                                            </div>
                                            <div class="col-auto">
                                                <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'alerts']) ?>" class="btn btn-outline-dark">
                                                    <i class="fas fa-bell me-1"></i><?php echo __('Alert Rules') ?>
                                                </a>
                                            </div>
                                            <div class="col-auto">
                                                <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'export']) ?>" class="btn btn-outline-success">
                                                    <i class="fas fa-download me-1"></i><?php echo __('Export') ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php break; ?>

                        <?php case 'accession': ?>
                                <!-- Intake Queue -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-inbox me-2"></i><?php echo __('Intake Queue') ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="accession_numbering_mask"><?php echo __('Numbering Mask') ?></label>
                                                <input type="text" class="form-control" id="accession_numbering_mask"
                                                       name="settings[accession_numbering_mask]"
                                                       value="<?php echo htmlspecialchars($settings['accession_numbering_mask'] ?? 'ACC-{YYYY}-{####}') ?>"
                                                       placeholder="ACC-{YYYY}-{####}">
                                                <div class="form-text"><?php echo __('Pattern for auto-generated accession numbers. Use {YYYY} for year and {####} for sequence.') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="accession_default_priority"><?php echo __('Default Priority') ?></label>
                                                <select class="form-select" id="accession_default_priority" name="settings[accession_default_priority]">
                                                    <option value="low" <?php echo ($settings['accession_default_priority'] ?? 'normal') === 'low' ? 'selected' : '' ?>><?php echo __('Low') ?></option>
                                                    <option value="normal" <?php echo ($settings['accession_default_priority'] ?? 'normal') === 'normal' ? 'selected' : '' ?>><?php echo __('Normal') ?></option>
                                                    <option value="high" <?php echo ($settings['accession_default_priority'] ?? 'normal') === 'high' ? 'selected' : '' ?>><?php echo __('High') ?></option>
                                                    <option value="urgent" <?php echo ($settings['accession_default_priority'] ?? 'normal') === 'urgent' ? 'selected' : '' ?>><?php echo __('Urgent') ?></option>
                                                </select>
                                                <div class="form-text"><?php echo __('Default priority assigned to new accessions in the intake queue.') ?></div>
                                            </div>
                                        </div>
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-4">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="accession_auto_assign_enabled"
                                                           name="settings[accession_auto_assign_enabled]" value="true"
                                                           <?php echo ($settings['accession_auto_assign_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="accession_auto_assign_enabled">
                                                        <strong><?php echo __('Auto-Assign to Archivist') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Automatically assign new accessions to the creating archivist.') ?></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="accession_require_donor_agreement"
                                                           name="settings[accession_require_donor_agreement]" value="true"
                                                           <?php echo ($settings['accession_require_donor_agreement'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="accession_require_donor_agreement">
                                                        <strong><?php echo __('Require Donor Agreement') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Donor agreement must be attached before an accession can be finalised.') ?></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="accession_require_appraisal"
                                                           name="settings[accession_require_appraisal]" value="true"
                                                           <?php echo ($settings['accession_require_appraisal'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="accession_require_appraisal">
                                                        <strong><?php echo __('Require Appraisal') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Appraisal must be completed before an accession can be finalised.') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Containers & Rights -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-box me-2"></i><?php echo __('Containers & Rights') ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="accession_allow_container_barcodes"
                                                           name="settings[accession_allow_container_barcodes]" value="true"
                                                           <?php echo ($settings['accession_allow_container_barcodes'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="accession_allow_container_barcodes">
                                                        <strong><?php echo __('Allow Container Barcodes') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Enable barcode scanning for linking containers to accessions.') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="accession_rights_inheritance_enabled"
                                                           name="settings[accession_rights_inheritance_enabled]" value="true"
                                                           <?php echo ($settings['accession_rights_inheritance_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="accession_rights_inheritance_enabled">
                                                        <strong><?php echo __('Rights Inheritance') ?></strong>
                                                    </label>
                                                </div>
                                                <div class="form-text"><?php echo __('Automatically inherit rights from the donor agreement to created information objects.') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php break; ?>

                        <?php case 'authority': ?>
                            <!-- Authority Records Settings -->
                            <div class="card mb-4">
                                <div class="card-header"><i class="fas fa-globe me-2"></i><?php echo __('External Authority Sources') ?></div>
                                <div class="card-body">
                                    <p class="text-muted mb-3"><?php echo __('Enable external authority file linking for reconciliation and enrichment.') ?></p>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="authority_wikidata_enabled"
                                                       name="settings[authority_wikidata_enabled]" value="true"
                                                       <?php echo ($settings['authority_wikidata_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="authority_wikidata_enabled">
                                                    <strong><?php echo __('Wikidata') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Enable Wikidata entity linking and reconciliation.') ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="authority_viaf_enabled"
                                                       name="settings[authority_viaf_enabled]" value="true"
                                                       <?php echo ($settings['authority_viaf_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="authority_viaf_enabled">
                                                    <strong><?php echo __('VIAF') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Virtual International Authority File linking.') ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="authority_ulan_enabled"
                                                       name="settings[authority_ulan_enabled]" value="true"
                                                       <?php echo ($settings['authority_ulan_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="authority_ulan_enabled">
                                                    <strong><?php echo __('Getty ULAN') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Union List of Artist Names linking.') ?></div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="authority_lcnaf_enabled"
                                                       name="settings[authority_lcnaf_enabled]" value="true"
                                                       <?php echo ($settings['authority_lcnaf_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="authority_lcnaf_enabled">
                                                    <strong><?php echo __('LCNAF') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Library of Congress Name Authority File.') ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="authority_isni_enabled"
                                                       name="settings[authority_isni_enabled]" value="true"
                                                       <?php echo ($settings['authority_isni_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="authority_isni_enabled">
                                                    <strong><?php echo __('ISNI') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('International Standard Name Identifier linking.') ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="authority_auto_verify_wikidata"
                                                       name="settings[authority_auto_verify_wikidata]" value="true"
                                                       <?php echo ($settings['authority_auto_verify_wikidata'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="authority_auto_verify_wikidata">
                                                    <strong><?php echo __('Auto-Verify Wikidata') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Automatically mark Wikidata identifiers as verified when added via reconciliation.') ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header"><i class="fas fa-chart-bar me-2"></i><?php echo __('Completeness & Quality') ?></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="authority_completeness_auto_recalc"
                                                       name="settings[authority_completeness_auto_recalc]" value="true"
                                                       <?php echo ($settings['authority_completeness_auto_recalc'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="authority_completeness_auto_recalc">
                                                    <strong><?php echo __('Auto-Recalculate Completeness') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Automatically recalculate completeness scores when the CLI scan runs.') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="authority_hide_stubs_from_public"
                                                       name="settings[authority_hide_stubs_from_public]" value="true"
                                                       <?php echo ($settings['authority_hide_stubs_from_public'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="authority_hide_stubs_from_public">
                                                    <strong><?php echo __('Hide Stubs from Public') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Hide stub-level authority records from public browse and search results.') ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header"><i class="fas fa-robot me-2"></i><?php echo __('NER Pipeline') ?></div>
                                <div class="card-body">
                                    <p class="text-muted mb-3"><?php echo __('Configure how Named Entity Recognition creates authority record stubs.') ?></p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="authority_ner_auto_stub_enabled"
                                                       name="settings[authority_ner_auto_stub_enabled]" value="true"
                                                       <?php echo ($settings['authority_ner_auto_stub_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="authority_ner_auto_stub_enabled">
                                                    <strong><?php echo __('Auto-Create Stubs') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Automatically create authority record stubs from NER entities above the confidence threshold.') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="authority_ner_auto_stub_threshold" class="form-label">
                                                <strong><?php echo __('Confidence Threshold') ?></strong>
                                            </label>
                                            <input type="number" class="form-control" id="authority_ner_auto_stub_threshold"
                                                   name="settings[authority_ner_auto_stub_threshold]"
                                                   value="<?php echo htmlspecialchars($settings['authority_ner_auto_stub_threshold'] ?? '0.85') ?>"
                                                   min="0" max="1" step="0.05">
                                            <div class="form-text"><?php echo __('Minimum confidence score (0.0-1.0) for auto-creating stubs. Default: 0.85') ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header"><i class="fas fa-code-branch me-2"></i><?php echo __('Merge & Deduplication') ?></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="authority_merge_require_approval"
                                                       name="settings[authority_merge_require_approval]" value="true"
                                                       <?php echo ($settings['authority_merge_require_approval'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="authority_merge_require_approval">
                                                    <strong><?php echo __('Require Approval for Merge') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Require workflow approval before merging authority records. Requires ahgWorkflowPlugin.') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="authority_dedup_threshold" class="form-label">
                                                <strong><?php echo __('Dedup Similarity Threshold') ?></strong>
                                            </label>
                                            <input type="number" class="form-control" id="authority_dedup_threshold"
                                                   name="settings[authority_dedup_threshold]"
                                                   value="<?php echo htmlspecialchars($settings['authority_dedup_threshold'] ?? '0.80') ?>"
                                                   min="0" max="1" step="0.05">
                                            <div class="form-text"><?php echo __('Minimum similarity score (0.0-1.0) for flagging potential duplicates. Default: 0.80') ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header"><i class="fas fa-project-diagram me-2"></i><?php echo __('ISDF Functions') ?></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="authority_function_linking_enabled"
                                                       name="settings[authority_function_linking_enabled]" value="true"
                                                       <?php echo ($settings['authority_function_linking_enabled'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="authority_function_linking_enabled">
                                                    <strong><?php echo __('Function Linking') ?></strong>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo __('Enable structured actor-to-function linking (ISDF). Requires ahgFunctionManagePlugin.') ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php break; ?>

                        <?php case 'library': ?>
                            <?php
                                $loanRules = \Illuminate\Database\Capsule\Manager::table('library_loan_rule')->orderBy('material_type')->orderBy('patron_type')->get();
                            ?>
                            <!-- Loan Rules -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i><?php echo __('Loan Rules'); ?></h5>
                                </div>
                                <div class="card-body p-0">
                                    <p class="text-muted px-3 pt-3 mb-2"><?php echo __('Configure loan periods, renewal limits, and fine rates per material type and patron type.'); ?></p>
                                    <table class="table table-striped table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th><?php echo __('Material Type'); ?></th>
                                                <th><?php echo __('Patron Type'); ?></th>
                                                <th class="text-center"><?php echo __('Loan Days'); ?></th>
                                                <th class="text-center"><?php echo __('Renewal Days'); ?></th>
                                                <th class="text-center"><?php echo __('Max Renewals'); ?></th>
                                                <th class="text-center"><?php echo __('Fine/Day'); ?></th>
                                                <th class="text-center"><?php echo __('Fine Cap'); ?></th>
                                                <th class="text-center"><?php echo __('Grace Days'); ?></th>
                                                <th class="text-center"><?php echo __('Loanable'); ?></th>
                                                <th><?php echo __('Actions'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($loanRules as $rule): ?>
                                            <tr>
                                                <td><strong><?php echo esc_specialchars($rule->material_type); ?></strong></td>
                                                <td><?php echo $rule->patron_type === '*' ? __('All') : esc_specialchars($rule->patron_type); ?></td>
                                                <td class="text-center"><input type="number" class="form-control form-control-sm text-center" name="loan_rule[<?php echo $rule->id; ?>][loan_period_days]" value="<?php echo $rule->loan_period_days; ?>" min="0" style="width:70px;display:inline-block"></td>
                                                <td class="text-center"><input type="number" class="form-control form-control-sm text-center" name="loan_rule[<?php echo $rule->id; ?>][renewal_period_days]" value="<?php echo $rule->renewal_period_days; ?>" min="0" style="width:70px;display:inline-block"></td>
                                                <td class="text-center"><input type="number" class="form-control form-control-sm text-center" name="loan_rule[<?php echo $rule->id; ?>][max_renewals]" value="<?php echo $rule->max_renewals; ?>" min="0" style="width:70px;display:inline-block"></td>
                                                <td class="text-center"><input type="number" class="form-control form-control-sm text-center" name="loan_rule[<?php echo $rule->id; ?>][fine_per_day]" value="<?php echo $rule->fine_per_day; ?>" min="0" step="0.01" style="width:80px;display:inline-block"></td>
                                                <td class="text-center"><input type="number" class="form-control form-control-sm text-center" name="loan_rule[<?php echo $rule->id; ?>][fine_cap]" value="<?php echo $rule->fine_cap ?? ''; ?>" min="0" step="0.01" style="width:80px;display:inline-block" placeholder="∞"></td>
                                                <td class="text-center"><input type="number" class="form-control form-control-sm text-center" name="loan_rule[<?php echo $rule->id; ?>][grace_period_days]" value="<?php echo $rule->grace_period_days; ?>" min="0" style="width:70px;display:inline-block"></td>
                                                <td class="text-center"><input type="checkbox" class="form-check-input" name="loan_rule[<?php echo $rule->id; ?>][is_loanable]" value="1" <?php echo $rule->is_loanable ? 'checked' : ''; ?>></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-rule" data-rule-id="<?php echo $rule->id; ?>" title="<?php echo __('Delete'); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <div class="p-3 border-top">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-auto">
                                                <label class="form-label small"><?php echo __('Material Type'); ?></label>
                                                <select name="new_rule_material_type" class="form-select form-select-sm">
                                                    <?php foreach (['monograph','serial','volume','issue','article','manuscript','map','pamphlet','score','electronic','audiovisual','microform','kit','realia','music_score','graphic_material','mixed_material','newspaper','thesis','government_document'] as $mt): ?>
                                                        <option value="<?php echo $mt; ?>"><?php echo $mt; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <label class="form-label small"><?php echo __('Patron Type'); ?></label>
                                                <select name="new_rule_patron_type" class="form-select form-select-sm">
                                                    <option value="*"><?php echo __('All (*)'); ?></option>
                                                    <?php foreach (['public','student','faculty','staff','researcher','institutional'] as $pt): ?>
                                                        <option value="<?php echo $pt; ?>"><?php echo $pt; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <button type="button" class="btn btn-sm btn-primary" id="btnAddRule">
                                                    <i class="fas fa-plus me-1"></i><?php echo __('Add Rule'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Circulation Defaults -->
                            <div class="card mb-4">
                                <div class="card-header"><h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i><?php echo __('Circulation Defaults'); ?></h5></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="library_default_loan_days" class="form-label"><?php echo __('Default Loan Period (days)'); ?></label>
                                            <input type="number" class="form-control" id="library_default_loan_days" name="settings[library_default_loan_days]" value="<?php echo htmlspecialchars($settings['library_default_loan_days'] ?? '14'); ?>" min="1">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="library_max_renewals" class="form-label"><?php echo __('Default Max Renewals'); ?></label>
                                            <input type="number" class="form-control" id="library_max_renewals" name="settings[library_max_renewals]" value="<?php echo htmlspecialchars($settings['library_max_renewals'] ?? '2'); ?>" min="0">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="library_currency" class="form-label"><?php echo __('Currency'); ?></label>
                                            <input type="text" class="form-control" id="library_currency" name="settings[library_currency]" value="<?php echo htmlspecialchars($settings['library_currency'] ?? 'ZAR'); ?>" maxlength="3">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="library_auto_fine" name="settings[library_auto_fine]" value="true" <?php echo ($settings['library_auto_fine'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="library_auto_fine"><?php echo __('Auto-generate daily overdue fines'); ?></label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="library_barcode_auto_generate" name="settings[library_barcode_auto_generate]" value="true" <?php echo ($settings['library_barcode_auto_generate'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="library_barcode_auto_generate"><?php echo __('Auto-generate barcodes for new copies'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="library_auto_expire_holds" name="settings[library_auto_expire_holds]" value="true" <?php echo ($settings['library_auto_expire_holds'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="library_auto_expire_holds"><?php echo __('Auto-expire unfulfilled holds'); ?></label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="library_auto_expire_patrons" name="settings[library_auto_expire_patrons]" value="true" <?php echo ($settings['library_auto_expire_patrons'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="library_auto_expire_patrons"><?php echo __('Auto-expire patron memberships'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Patron Defaults -->
                            <div class="card mb-4">
                                <div class="card-header"><h5 class="mb-0"><i class="fas fa-users me-2"></i><?php echo __('Patron Defaults'); ?></h5></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label"><?php echo __('Max Checkouts'); ?></label>
                                            <input type="number" class="form-control" name="settings[library_patron_max_checkouts]" value="<?php echo htmlspecialchars($settings['library_patron_max_checkouts'] ?? '5'); ?>" min="1">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label"><?php echo __('Max Renewals'); ?></label>
                                            <input type="number" class="form-control" name="settings[library_patron_max_renewals]" value="<?php echo htmlspecialchars($settings['library_patron_max_renewals'] ?? '2'); ?>" min="0">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label"><?php echo __('Max Holds'); ?></label>
                                            <input type="number" class="form-control" name="settings[library_patron_max_holds]" value="<?php echo htmlspecialchars($settings['library_patron_max_holds'] ?? '3'); ?>" min="0">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label"><?php echo __('Membership Duration (months)'); ?></label>
                                            <input type="number" class="form-control" name="settings[library_patron_membership_months]" value="<?php echo htmlspecialchars($settings['library_patron_membership_months'] ?? '12'); ?>" min="1">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><?php echo __('Fine Threshold (block borrowing)'); ?></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo htmlspecialchars($settings['library_currency'] ?? 'ZAR'); ?></span>
                                                <input type="number" class="form-control" name="settings[library_patron_fine_threshold]" value="<?php echo htmlspecialchars($settings['library_patron_fine_threshold'] ?? '50.00'); ?>" min="0" step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><?php echo __('Default Patron Type'); ?></label>
                                            <select class="form-select" name="settings[library_patron_default_type]">
                                                <?php foreach (['public','student','faculty','staff','researcher','institutional'] as $pt): ?>
                                                    <option value="<?php echo $pt; ?>" <?php echo ($settings['library_patron_default_type'] ?? 'public') === $pt ? 'selected' : ''; ?>><?php echo ucfirst($pt); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><?php echo __('Expiry Grace Period (days)'); ?></label>
                                            <input type="number" class="form-control" name="settings[library_patron_expiry_grace_days]" value="<?php echo htmlspecialchars($settings['library_patron_expiry_grace_days'] ?? '7'); ?>" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- OPAC Settings -->
                            <div class="card mb-4">
                                <div class="card-header"><h5 class="mb-0"><i class="fas fa-globe me-2"></i><?php echo __('OPAC (Public Catalog)'); ?></h5></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="library_opac_enabled" name="settings[library_opac_enabled]" value="true" <?php echo ($settings['library_opac_enabled'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="library_opac_enabled"><?php echo __('Enable public OPAC'); ?></label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="library_opac_show_availability" name="settings[library_opac_show_availability]" value="true" <?php echo ($settings['library_opac_show_availability'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="library_opac_show_availability"><?php echo __('Show copy availability in search results'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="library_opac_show_covers" name="settings[library_opac_show_covers]" value="true" <?php echo ($settings['library_opac_show_covers'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="library_opac_show_covers"><?php echo __('Show book cover images'); ?></label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="library_opac_allow_holds" name="settings[library_opac_allow_holds]" value="true" <?php echo ($settings['library_opac_allow_holds'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="library_opac_allow_holds"><?php echo __('Allow patrons to place holds via OPAC'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><?php echo __('Results Per Page'); ?></label>
                                            <input type="number" class="form-control" name="settings[library_opac_results_per_page]" value="<?php echo htmlspecialchars($settings['library_opac_results_per_page'] ?? '20'); ?>" min="5" max="100">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><?php echo __('New Arrivals Count'); ?></label>
                                            <input type="number" class="form-control" name="settings[library_opac_new_arrivals_count]" value="<?php echo htmlspecialchars($settings['library_opac_new_arrivals_count'] ?? '8'); ?>" min="1" max="50">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><?php echo __('Popular Items — Days Window'); ?></label>
                                            <input type="number" class="form-control" name="settings[library_opac_popular_days]" value="<?php echo htmlspecialchars($settings['library_opac_popular_days'] ?? '90'); ?>" min="7" max="365">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hold Settings -->
                            <div class="card mb-4">
                                <div class="card-header"><h5 class="mb-0"><i class="fas fa-hand-paper me-2"></i><?php echo __('Hold Settings'); ?></h5></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><?php echo __('Hold Expiry (days after ready)'); ?></label>
                                            <input type="number" class="form-control" name="settings[library_hold_expiry_days]" value="<?php echo htmlspecialchars($settings['library_hold_expiry_days'] ?? '7'); ?>" min="1">
                                            <div class="form-text"><?php echo __('Days a hold remains ready for pickup before expiring.'); ?></div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><?php echo __('Max Queue Size Per Item'); ?></label>
                                            <input type="number" class="form-control" name="settings[library_hold_max_queue]" value="<?php echo htmlspecialchars($settings['library_hold_max_queue'] ?? '10'); ?>" min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ISBN Providers -->
                            <div class="card mb-4">
                                <div class="card-header"><h5 class="mb-0"><i class="fas fa-barcode me-2"></i><?php echo __('ISBN Providers'); ?></h5></div>
                                <div class="card-body">
                                    <p class="text-muted mb-3"><?php echo __('Manage ISBN lookup providers (Open Library, Google Books, WorldCat) for automatic metadata retrieval.'); ?></p>
                                    <a href="<?php echo url_for('library/isbnProviders'); ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-1"></i><?php echo __('Manage ISBN Providers'); ?>
                                    </a>
                                </div>
                            </div>
                            <?php break; ?>

                        <?php endswitch; ?>

                        <!-- Submit Button -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> <?php echo __('Save Settings'); ?>
                            </button>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Color picker sync
document.querySelectorAll('[id$="_color_picker"]').forEach(function(picker) {
    var textInput = document.getElementById(picker.id.replace('_picker', ''));
    if (textInput) {
        picker.addEventListener('input', function() {
            textInput.value = this.value;
        });
        textInput.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                picker.value = this.value;
            }
        });
    }
});

// Range slider value display
document.querySelectorAll('input[type="range"]').forEach(function(range) {
    var display = document.createElement('span');
    display.className = 'ml-2';
    display.textContent = range.value;
    range.parentNode.appendChild(display);
    
    range.addEventListener('input', function() {
        display.textContent = this.value;
    });
});
</script>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.ahg-settings-page .list-group-item.active {
    background-color: var(--primary, #007bff);
    border-color: var(--primary, #007bff);
}
.ahg-settings-page .form-control-color {
    padding: 0;
    height: auto;
}
.ahg-settings-page fieldset {
    border: 1px solid #dee2e6;
    padding: 1.5rem;
    border-radius: 0.5rem;
    background: #fafafa;
}
.ahg-settings-page legend {
    font-size: 1.1rem;
    font-weight: 600;
    padding: 0 0.5rem;
    width: auto;
    margin-bottom: 1rem;
}
.ahg-settings-page .font-monospace {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.875rem;
}
</style>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Color picker sync for extended options
document.addEventListener('DOMContentLoaded', function() {
    const colorFields = [
        'ahg_card_header_bg', 'ahg_card_header_text',
        'ahg_button_bg', 'ahg_button_text',
        'ahg_link_color', 'ahg_sidebar_bg', 'ahg_sidebar_text'
    ];
    
    colorFields.forEach(function(field) {
        const picker = document.getElementById(field + '_picker');
        const text = document.getElementById(field);
        if (picker && text) {
            picker.addEventListener('input', function() {
                text.value = this.value;
                updatePreview();
            });
            text.addEventListener('input', function() {
                if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                    picker.value = this.value;
                    updatePreview();
                }
            });
        }
    });
    
    function updatePreview() {
        const headerBg = document.getElementById('ahg_card_header_bg');
        const headerText = document.getElementById('ahg_card_header_text');
        const buttonBg = document.getElementById('ahg_button_bg');
        const buttonText = document.getElementById('ahg_button_text');
        const linkColor = document.getElementById('ahg_link_color');
        
        const previewHeader = document.getElementById('preview-card-header');
        const previewButton = document.getElementById('preview-button');
        const previewLink = document.getElementById('preview-link');
        
        if (previewHeader && headerBg && headerText) {
            previewHeader.style.backgroundColor = headerBg.value;
            previewHeader.style.color = headerText.value;
            previewHeader.querySelector('h6').style.color = headerText.value;
        }
        if (previewButton && buttonBg && buttonText) {
            previewButton.style.backgroundColor = buttonBg.value;
            previewButton.style.color = buttonText.value;
        }
        if (previewLink && linkColor) {
            previewLink.style.color = linkColor.value;
        }
    }
});
</script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Color picker sync for PRIMARY and SECONDARY colors
document.addEventListener('DOMContentLoaded', function() {
    // Sync primary color
    const primaryPicker = document.getElementById('ahg_primary_color_picker');
    const primaryText = document.getElementById('ahg_primary_color');
    if (primaryPicker && primaryText) {
        primaryPicker.addEventListener('input', function() {
            primaryText.value = this.value;
        });
        primaryText.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                primaryPicker.value = this.value;
            }
        });
    }
    
    // Sync secondary color
    const secondaryPicker = document.getElementById('ahg_secondary_color_picker');
    const secondaryText = document.getElementById('ahg_secondary_color');
    if (secondaryPicker && secondaryText) {
        secondaryPicker.addEventListener('input', function() {
            secondaryText.value = this.value;
        });
        secondaryText.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                secondaryPicker.value = this.value;
            }
        });
    }
    
    // Also sync all other color fields on page load (in case they weren't)
    document.querySelectorAll('input[type="color"]').forEach(function(picker) {
        const textId = picker.id.replace('_picker', '');
        const textInput = document.getElementById(textId);
        if (textInput && picker.value !== textInput.value) {
            // Sync picker to text value on load
            if (textInput.value && /^#[0-9A-Fa-f]{6}$/.test(textInput.value)) {
                picker.value = textInput.value;
            }
        }
        // Add listener if not already added
        picker.addEventListener('change', function() {
            if (textInput) {
                textInput.value = this.value;
            }
        });
    });
});

// Library loan rule buttons
document.addEventListener('DOMContentLoaded', function() {
    var addBtn = document.getElementById('btnAddRule');
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = '_add_rule';
            hidden.value = '1';
            this.closest('form').appendChild(hidden);
            this.closest('form').submit();
        });
    }
    document.querySelectorAll('.btn-delete-rule').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this loan rule?')) return;
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = '_delete_rule';
            hidden.value = this.dataset.ruleId;
            this.closest('form').appendChild(hidden);
            this.closest('form').submit();
        });
    });
});
</script>
