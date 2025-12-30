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
                        <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => $sectionKey]); ?>" 
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
                    <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgExportSettings']); ?>" class="btn btn-outline-secondary btn-sm btn-block mb-2">
                        <i class="fas fa-download"></i> <?php echo __('Export Settings'); ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgImportSettings']); ?>" class="btn btn-outline-secondary btn-sm btn-block mb-2">
                        <i class="fas fa-upload"></i> <?php echo __('Import Settings'); ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgResetSettings', 'section' => $currentSection]); ?>" 
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
                    <form method="post" action="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => $currentSection]); ?>" id="settings-form">
                        
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
                                                <input type="checkbox" class="custom-control-input" id="ahg_show_branding" name="settings[ahg_show_branding]" value="true" <?php echo ($settings['ahg_show_branding'] ?? true) ? 'checked' : ''; ?>>
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
                                                <input type="checkbox" class="custom-control-input" id="spectrum_enabled" name="settings[spectrum_enabled]" value="true" <?php echo ($settings['spectrum_enabled'] ?? true) ? 'checked' : ''; ?>>
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
                                                <input type="checkbox" class="custom-control-input" id="spectrum_auto_create_movement" name="settings[spectrum_auto_create_movement]" value="true" <?php echo ($settings['spectrum_auto_create_movement'] ?? true) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="spectrum_auto_create_movement"><?php echo __('Automatically create movement records on location change'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Require Photos'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="spectrum_require_photos" name="settings[spectrum_require_photos]" value="true" <?php echo ($settings['spectrum_require_photos'] ?? false) ? 'checked' : ''; ?>>
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
                                                <input type="checkbox" class="custom-control-input" id="media_autoplay" name="settings[media_autoplay]" value="true" <?php echo ($settings['media_autoplay'] ?? false) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="media_autoplay"><?php echo __('Auto-play media on load'); ?></label>
                                            </div>
                                            <small class="form-text text-muted"><?php echo __('Note: Most browsers block autoplay with sound'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Show Controls'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="media_show_controls" name="settings[media_show_controls]" value="true" <?php echo ($settings['media_show_controls'] ?? true) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="media_show_controls"><?php echo __('Display player controls'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Loop Playback'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="media_loop" name="settings[media_loop]" value="true" <?php echo ($settings['media_loop'] ?? false) ? 'checked' : ''; ?>>
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
                                                <input type="checkbox" class="custom-control-input" id="media_show_download" name="settings[media_show_download]" value="true" <?php echo ($settings['media_show_download'] ?? false) ? 'checked' : ''; ?>>
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
                                                <input type="checkbox" class="custom-control-input" id="photo_create_thumbnails" name="settings[photo_create_thumbnails]" value="true" <?php echo ($settings['photo_create_thumbnails'] ?? true) ? 'checked' : ''; ?>>
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
                                                <input type="checkbox" class="custom-control-input" id="photo_extract_exif" name="settings[photo_extract_exif]" value="true" <?php echo ($settings['photo_extract_exif'] ?? true) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="photo_extract_exif"><?php echo __('Extract camera info from EXIF data'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Auto-rotate'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="photo_auto_rotate" name="settings[photo_auto_rotate]" value="true" <?php echo ($settings['photo_auto_rotate'] ?? true) ? 'checked' : ''; ?>>
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
                                                <input type="checkbox" class="custom-control-input" id="dp_enabled" name="settings[dp_enabled]" value="true" <?php echo ($settings['dp_enabled'] ?? true) ? 'checked' : ''; ?>>
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
                                                <input type="checkbox" class="custom-control-input" id="dp_notify_overdue" name="settings[dp_notify_overdue]" value="true" <?php echo ($settings['dp_notify_overdue'] ?? true) ? 'checked' : ''; ?>>
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
                                                <input type="checkbox" class="custom-control-input" id="iiif_enabled" name="settings[iiif_enabled]" value="true" <?php echo ($settings['iiif_enabled'] ?? true) ? 'checked' : ''; ?>>
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
                                                <input type="checkbox" class="custom-control-input" id="iiif_show_navigator" name="settings[iiif_show_navigator]" value="true" <?php echo ($settings['iiif_show_navigator'] ?? true) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="iiif_show_navigator"><?php echo __('Show mini-map navigator'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label"><?php echo __('Enable Rotation'); ?></label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="iiif_show_rotation" name="settings[iiif_show_rotation]" value="true" <?php echo ($settings['iiif_show_rotation'] ?? true) ? 'checked' : ''; ?>>
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
                                                <input type="checkbox" class="custom-control-input" id="jobs_enabled" name="settings[jobs_enabled]" value="true" <?php echo ($settings['jobs_enabled'] ?? true) ? 'checked' : ''; ?>>
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
                                                <input type="checkbox" class="custom-control-input" id="jobs_notify_on_failure" name="settings[jobs_notify_on_failure]" value="true" <?php echo ($settings['jobs_notify_on_failure'] ?? true) ? 'checked' : ''; ?>>
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

<script>
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

<style>
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
