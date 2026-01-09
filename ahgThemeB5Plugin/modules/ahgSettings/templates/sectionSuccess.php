<?php
/**
 * AHG Settings Template
 * 
 * Centralized settings management for AHG theme and plugins
 */

$title = __('AHG Settings');
slot('title', $title);
?>

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
                <div class="card-header bg-primary text-white">
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
                                                       value="<?php echo esc_specialchars($settings['fuseki_endpoint'] ?? 'http://192.168.0.112:3030/ric'); ?>"
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
                                            <a href="/ric-dashboard/" target="_blank" class="btn btn-outline-info">
                                                <i class="fa fa-external-link-alt me-1"></i><?php echo __('RIC Explorer'); ?>
                                            </a>
                                            <a href="http://192.168.0.112:3030/" target="_blank" class="btn btn-outline-secondary">
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
</script>
