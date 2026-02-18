@extends('layouts.page')

@section('content')
<style {!! $csp_nonce ?? '' !!}>
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
        <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'index']) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Settings Overview') }}
        </a>
    </div>

    <!-- Page Header -->
    <div class="page-header mb-4">
        <h1><i class="fas fa-cogs"></i> {{ __('AHG Settings') }}</h1>
        <p class="text-muted">{{ __('Configure AHG theme and plugin settings') }}</p>
    </div>

    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> {{ __('Settings Sections') }}</h5>
                </div>
                <div class="list-group list-group-flush">
                    @foreach ($sections as $sectionKey => $sectionInfo)
                        <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'section', 'section' => $sectionKey]) }}"
                           class="list-group-item list-group-item-action {{ $currentSection === $sectionKey ? 'active' : '' }}">
                            <i class="fas {{ $sectionInfo['icon'] }} fa-fw mr-2"></i>
                            {{ __($sectionInfo['label']) }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> {{ __('Quick Actions') }}</h5>
                </div>
                <div class="card-body">
                    <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'export']) }}" class="btn btn-outline-secondary btn-sm btn-block mb-2">
                        <i class="fas fa-download"></i> {{ __('Export Settings') }}
                    </a>
                    <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'import']) }}" class="btn btn-outline-secondary btn-sm btn-block mb-2">
                        <i class="fas fa-upload"></i> {{ __('Import Settings') }}
                    </a>
                    <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'reset', 'section' => $currentSection]) }}"
                       class="btn btn-outline-danger btn-sm btn-block"
                       onclick="return confirm('{{ __('Reset all settings in this section to defaults?') }}');">
                        <i class="fas fa-undo"></i> {{ __('Reset to Defaults') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas {{ $sections[$currentSection]['icon'] }}"></i>
                        {{ __($sections[$currentSection]['label']) }}
                    </h4>
                    <small class="text-muted">{{ __($sections[$currentSection]['description'] ?? '') }}</small>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ url_for(['module' => 'ahgSettings', 'action' => 'section', 'section' => $currentSection]) }}" id="settings-form">

                        @switch($currentSection)
                            @case('general')
                                <!-- General Settings -->
                                <fieldset class="mb-4">
                                    <legend>{{ __('Theme Configuration') }}</legend>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Enable AHG Theme') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="ahg_theme_enabled" name="settings[ahg_theme_enabled]" value="true" {{ $settings['ahg_theme_enabled'] ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="ahg_theme_enabled">{{ __('Use AHG theme customizations') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_logo_path">{{ __('Custom Logo') }}</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="ahg_logo_path" name="settings[ahg_logo_path]" value="{{ e($settings['ahg_logo_path'] ?? '') }}" placeholder="/uploads/logo.png">
                                            <small class="form-text text-muted">{{ __('Path to custom logo image') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_primary_color">{{ __('Primary Color') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_primary_color_picker" value="{{ e($settings['ahg_primary_color'] ?? '#1a5f7a') }}" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_primary_color" name="settings[ahg_primary_color]" value="{{ e($settings['ahg_primary_color'] ?? '#1a5f7a') }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_secondary_color">{{ __('Secondary Color') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_secondary_color_picker" value="{{ e($settings['ahg_secondary_color'] ?? '#57837b') }}" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_secondary_color" name="settings[ahg_secondary_color]" value="{{ e($settings['ahg_secondary_color'] ?? '#57837b') }}">
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    <h6 class="text-muted mb-3"><i class="fas fa-palette me-2"></i>{{ __('Extended Color Options') }}</h6>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_card_header_bg">{{ __('Card Header Background') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_card_header_bg_picker" value="{{ e($settings['ahg_card_header_bg'] ?? '#1a5f2a') }}" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_card_header_bg" name="settings[ahg_card_header_bg]" value="{{ e($settings['ahg_card_header_bg'] ?? '#1a5f2a') }}">
                                            </div>
                                            <small class="form-text text-muted">{{ __('Background color for card headers') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_card_header_text">{{ __('Card Header Text') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_card_header_text_picker" value="{{ e($settings['ahg_card_header_text'] ?? '#ffffff') }}" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_card_header_text" name="settings[ahg_card_header_text]" value="{{ e($settings['ahg_card_header_text'] ?? '#ffffff') }}">
                                            </div>
                                            <small class="form-text text-muted">{{ __('Text color for card headers') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_button_bg">{{ __('Button Background') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_button_bg_picker" value="{{ e($settings['ahg_button_bg'] ?? '#1a5f2a') }}" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_button_bg" name="settings[ahg_button_bg]" value="{{ e($settings['ahg_button_bg'] ?? '#1a5f2a') }}">
                                            </div>
                                            <small class="form-text text-muted">{{ __('Background color for primary buttons') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_button_text">{{ __('Button Text') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_button_text_picker" value="{{ e($settings['ahg_button_text'] ?? '#ffffff') }}" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_button_text" name="settings[ahg_button_text]" value="{{ e($settings['ahg_button_text'] ?? '#ffffff') }}">
                                            </div>
                                            <small class="form-text text-muted">{{ __('Text color for primary buttons') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_link_color">{{ __('Link Color') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_link_color_picker" value="{{ e($settings['ahg_link_color'] ?? '#1a5f2a') }}" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_link_color" name="settings[ahg_link_color]" value="{{ e($settings['ahg_link_color'] ?? '#1a5f2a') }}">
                                            </div>
                                            <small class="form-text text-muted">{{ __('Color for hyperlinks') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_sidebar_bg">{{ __('Sidebar Background') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_sidebar_bg_picker" value="{{ e($settings['ahg_sidebar_bg'] ?? '#f8f9fa') }}" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_sidebar_bg" name="settings[ahg_sidebar_bg]" value="{{ e($settings['ahg_sidebar_bg'] ?? '#f8f9fa') }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_sidebar_text">{{ __('Sidebar Text') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="ahg_sidebar_text_picker" value="{{ e($settings['ahg_sidebar_text'] ?? '#333333') }}" style="width: 50px; padding: 2px;">
                                                <input type="text" class="form-control" id="ahg_sidebar_text" name="settings[ahg_sidebar_text]" value="{{ e($settings['ahg_sidebar_text'] ?? '#333333') }}">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Preview Box -->
                                    <div class="form-group row mt-4">
                                        <label class="col-sm-3 col-form-label">{{ __('Preview') }}</label>
                                        <div class="col-sm-9">
                                            <div class="card" id="theme-preview-card">
                                                <div class="card-header" id="preview-card-header" style="background-color: {{ e($settings['ahg_card_header_bg'] ?? '#1a5f2a') }}; color: {{ e($settings['ahg_card_header_text'] ?? '#ffffff') }};">
                                                    <h6 class="mb-0" style="color: inherit;"><i class="fas fa-eye me-2"></i>Preview Header</h6>
                                                </div>
                                                <div class="card-body">
                                                    <p>Sample text with <a href="#" id="preview-link" style="color: {{ e($settings['ahg_link_color'] ?? '#1a5f2a') }};">a link</a>.</p>
                                                    <button type="button" class="btn" id="preview-button" style="background-color: {{ e($settings['ahg_button_bg'] ?? '#1a5f2a') }}; color: {{ e($settings['ahg_button_text'] ?? '#ffffff') }};">Sample Button</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_footer_text">{{ __('Footer Text') }}</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="ahg_footer_text" name="settings[ahg_footer_text]" value="{{ e($settings['ahg_footer_text'] ?? '') }}">
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Show Branding') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="ahg_show_branding" name="settings[ahg_show_branding]" value="true" {{ ($settings['ahg_show_branding'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="ahg_show_branding">{{ __('Display AHG branding') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="ahg_custom_css">{{ __('Custom CSS') }}</label>
                                        <div class="col-sm-9">
                                            <textarea class="form-control font-monospace" id="ahg_custom_css" name="settings[ahg_custom_css]" rows="6" placeholder="/* Custom CSS styles */">{{ e($settings['ahg_custom_css'] ?? '') }}</textarea>
                                        </div>
                                    </div>
                                </fieldset>
                            @break

                            @case('spectrum')
                                <!-- Spectrum Settings -->
                                <fieldset class="mb-4">
                                    <legend>{{ __('Collections Management') }}</legend>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Enable Spectrum') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="spectrum_enabled" name="settings[spectrum_enabled]" value="true" {{ ($settings['spectrum_enabled'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="spectrum_enabled">{{ __('Enable Spectrum collections management') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="spectrum_default_currency">{{ __('Default Currency') }}</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="spectrum_default_currency" name="settings[spectrum_default_currency]">
                                                @foreach (['ZAR' => 'South African Rand (ZAR)', 'USD' => 'US Dollar (USD)', 'EUR' => 'Euro (EUR)', 'GBP' => 'British Pound (GBP)'] as $code => $name)
                                                    <option value="{{ $code }}" {{ ($settings['spectrum_default_currency'] ?? 'ZAR') === $code ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="spectrum_valuation_reminder_days">{{ __('Valuation Reminder') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="spectrum_valuation_reminder_days" name="settings[spectrum_valuation_reminder_days]" value="{{ $settings['spectrum_valuation_reminder_days'] ?? 365 }}" min="30" max="1825">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">{{ __('days') }}</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">{{ __('Remind to re-value after this many days') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="spectrum_loan_default_period">{{ __('Default Loan Period') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="spectrum_loan_default_period" name="settings[spectrum_loan_default_period]" value="{{ $settings['spectrum_loan_default_period'] ?? 90 }}" min="1" max="365">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">{{ __('days') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="spectrum_condition_check_interval">{{ __('Condition Check Interval') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="spectrum_condition_check_interval" name="settings[spectrum_condition_check_interval]" value="{{ $settings['spectrum_condition_check_interval'] ?? 180 }}" min="30" max="730">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">{{ __('days') }}</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">{{ __('Recommended interval between condition checks') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Auto-create Movements') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="spectrum_auto_create_movement" name="settings[spectrum_auto_create_movement]" value="true" {{ ($settings['spectrum_auto_create_movement'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="spectrum_auto_create_movement">{{ __('Automatically create movement records on location change') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Require Photos') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="spectrum_require_photos" name="settings[spectrum_require_photos]" value="true" {{ ($settings['spectrum_require_photos'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="spectrum_require_photos">{{ __('Require at least one photo for condition reports') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Email Notifications') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="spectrum_email_notifications" name="settings[spectrum_email_notifications]" value="true" {{ ($settings['spectrum_email_notifications'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="spectrum_email_notifications">{{ __('Send email notifications for task assignments and state transitions') }}</label>
                                            </div>
                                            <small class="form-text text-muted">{{ __('Requires SMTP to be configured in Email settings') }}</small>
                                        </div>
                                    </div>
                                </fieldset>
                            @break

                            @case('media')
                                <!-- Media Player Settings -->
                                <fieldset class="mb-4">
                                    <legend>{{ __('Media Player Configuration') }}</legend>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="media_player_type">{{ __('Player Type') }}</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="media_player_type" name="settings[media_player_type]">
                                                <option value="basic" {{ ($settings['media_player_type'] ?? 'enhanced') === 'basic' ? 'selected' : '' }}>{{ __('Basic HTML5 Player') }}</option>
                                                <option value="enhanced" {{ ($settings['media_player_type'] ?? 'enhanced') === 'enhanced' ? 'selected' : '' }}>{{ __('Enhanced Player (Recommended)') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Auto-play') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="media_autoplay" name="settings[media_autoplay]" value="true" {{ ($settings['media_autoplay'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="media_autoplay">{{ __('Auto-play media on load') }}</label>
                                            </div>
                                            <small class="form-text text-muted">{{ __('Note: Most browsers block autoplay with sound') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Show Controls') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="media_show_controls" name="settings[media_show_controls]" value="true" {{ ($settings['media_show_controls'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="media_show_controls">{{ __('Display player controls') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Loop Playback') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="media_loop" name="settings[media_loop]" value="true" {{ ($settings['media_loop'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="media_loop">{{ __('Loop media automatically') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="media_default_volume">{{ __('Default Volume') }}</label>
                                        <div class="col-sm-9">
                                            <input type="range" class="form-control-range" id="media_default_volume" name="settings[media_default_volume]" min="0" max="1" step="0.1" value="{{ $settings['media_default_volume'] ?? 0.8 }}">
                                            <small class="form-text text-muted">{{ __('Default volume level (0-100%)') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Show Download') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="media_show_download" name="settings[media_show_download]" value="true" {{ ($settings['media_show_download'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="media_show_download">{{ __('Show download button') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            @break

                            @case('photos')
                                <!-- Photo Settings -->
                                <fieldset class="mb-4">
                                    <legend>{{ __('Photo Upload Settings') }}</legend>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="photo_upload_path">{{ __('Upload Path') }}</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="photo_upload_path" name="settings[photo_upload_path]" value="{{ e($settings['photo_upload_path'] ?? sfConfig::get('sf_root_dir') . '/uploads/condition_photos') }}">
                                            <small class="form-text text-muted">{{ __('Absolute path for condition photo storage') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="photo_max_upload_size">{{ __('Max Upload Size') }}</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="photo_max_upload_size" name="settings[photo_max_upload_size]">
                                                <option value="5242880" {{ ($settings['photo_max_upload_size'] ?? 10485760) == 5242880 ? 'selected' : '' }}>5 MB</option>
                                                <option value="10485760" {{ ($settings['photo_max_upload_size'] ?? 10485760) == 10485760 ? 'selected' : '' }}>10 MB</option>
                                                <option value="20971520" {{ ($settings['photo_max_upload_size'] ?? 10485760) == 20971520 ? 'selected' : '' }}>20 MB</option>
                                                <option value="52428800" {{ ($settings['photo_max_upload_size'] ?? 10485760) == 52428800 ? 'selected' : '' }}>50 MB</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Create Thumbnails') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="photo_create_thumbnails" name="settings[photo_create_thumbnails]" value="true" {{ ($settings['photo_create_thumbnails'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="photo_create_thumbnails">{{ __('Auto-create thumbnails on upload') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Thumbnail Sizes') }}</label>
                                        <div class="col-sm-9">
                                            <div class="row">
                                                <div class="col-4">
                                                    <label class="small">{{ __('Small') }}</label>
                                                    <input type="number" class="form-control" name="settings[photo_thumbnail_small]" value="{{ $settings['photo_thumbnail_small'] ?? 150 }}" min="50" max="300">
                                                </div>
                                                <div class="col-4">
                                                    <label class="small">{{ __('Medium') }}</label>
                                                    <input type="number" class="form-control" name="settings[photo_thumbnail_medium]" value="{{ $settings['photo_thumbnail_medium'] ?? 300 }}" min="100" max="600">
                                                </div>
                                                <div class="col-4">
                                                    <label class="small">{{ __('Large') }}</label>
                                                    <input type="number" class="form-control" name="settings[photo_thumbnail_large]" value="{{ $settings['photo_thumbnail_large'] ?? 600 }}" min="300" max="1200">
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">{{ __('Maximum dimension in pixels') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="photo_jpeg_quality">{{ __('JPEG Quality') }}</label>
                                        <div class="col-sm-9">
                                            <input type="range" class="form-control-range" id="photo_jpeg_quality" name="settings[photo_jpeg_quality]" min="60" max="100" value="{{ $settings['photo_jpeg_quality'] ?? 85 }}">
                                            <small class="form-text text-muted">{{ __('Quality for JPEG thumbnails (60-100)') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Extract EXIF') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="photo_extract_exif" name="settings[photo_extract_exif]" value="true" {{ ($settings['photo_extract_exif'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="photo_extract_exif">{{ __('Extract camera info from EXIF data') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Auto-rotate') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="photo_auto_rotate" name="settings[photo_auto_rotate]" value="true" {{ ($settings['photo_auto_rotate'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="photo_auto_rotate">{{ __('Auto-rotate based on EXIF orientation') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            @break

                            @case('data_protection')
                                <!-- Data Protection Settings -->
                                <fieldset class="mb-4">
                                    <legend>{{ __('Data Protection Compliance') }}</legend>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Enable Module') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="dp_enabled" name="settings[dp_enabled]" value="true" {{ ($settings['dp_enabled'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="dp_enabled">{{ __('Enable data protection module') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="dp_default_regulation">{{ __('Default Regulation') }}</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="dp_default_regulation" name="settings[dp_default_regulation]">
                                                <option value="popia" {{ ($settings['dp_default_regulation'] ?? 'popia') === 'popia' ? 'selected' : '' }}>POPIA (South Africa)</option>
                                                <option value="gdpr" {{ ($settings['dp_default_regulation'] ?? 'popia') === 'gdpr' ? 'selected' : '' }}>GDPR (European Union)</option>
                                                <option value="paia" {{ ($settings['dp_default_regulation'] ?? 'popia') === 'paia' ? 'selected' : '' }}>PAIA (South Africa)</option>
                                                <option value="ccpa" {{ ($settings['dp_default_regulation'] ?? 'popia') === 'ccpa' ? 'selected' : '' }}>CCPA (California)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Notify Overdue') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="dp_notify_overdue" name="settings[dp_notify_overdue]" value="true" {{ ($settings['dp_notify_overdue'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="dp_notify_overdue">{{ __('Send email notifications for overdue requests') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="dp_notify_email">{{ __('Notification Email') }}</label>
                                        <div class="col-sm-9">
                                            <input type="email" class="form-control" id="dp_notify_email" name="settings[dp_notify_email]" value="{{ e($settings['dp_notify_email'] ?? '') }}" placeholder="dpo@example.com">
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="mb-4">
                                    <legend>{{ __('POPIA/PAIA Settings') }}</legend>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="dp_popia_fee">{{ __('POPIA Request Fee') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">R</span>
                                                </div>
                                                <input type="number" class="form-control" id="dp_popia_fee" name="settings[dp_popia_fee]" value="{{ $settings['dp_popia_fee'] ?? 50 }}" min="0" step="0.01">
                                            </div>
                                            <small class="form-text text-muted">{{ __('Standard request fee (R50 per regulation)') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="dp_popia_fee_special">{{ __('Special Category Fee') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">R</span>
                                                </div>
                                                <input type="number" class="form-control" id="dp_popia_fee_special" name="settings[dp_popia_fee_special]" value="{{ $settings['dp_popia_fee_special'] ?? 140 }}" min="0" step="0.01">
                                            </div>
                                            <small class="form-text text-muted">{{ __('Fee for special categories of personal info (R140)') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="dp_popia_response_days">{{ __('Response Days') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="dp_popia_response_days" name="settings[dp_popia_response_days]" value="{{ $settings['dp_popia_response_days'] ?? 30 }}" min="1" max="90">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">{{ __('days') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            @break

                            @case('iiif')
                                <!-- IIIF Settings -->
                                <fieldset class="mb-4">
                                    <legend>{{ __('IIIF Image Viewer') }}</legend>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Enable IIIF') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="iiif_enabled" name="settings[iiif_enabled]" value="true" {{ ($settings['iiif_enabled'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="iiif_enabled">{{ __('Enable IIIF viewer') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="iiif_viewer">{{ __('Viewer Library') }}</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="iiif_viewer" name="settings[iiif_viewer]">
                                                <option value="openseadragon" {{ ($settings['iiif_viewer'] ?? 'openseadragon') === 'openseadragon' ? 'selected' : '' }}>OpenSeadragon</option>
                                                <option value="mirador" {{ ($settings['iiif_viewer'] ?? 'openseadragon') === 'mirador' ? 'selected' : '' }}>Mirador</option>
                                                <option value="leaflet" {{ ($settings['iiif_viewer'] ?? 'openseadragon') === 'leaflet' ? 'selected' : '' }}>Leaflet-IIIF</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="iiif_server_url">{{ __('IIIF Server URL') }}</label>
                                        <div class="col-sm-9">
                                            <input type="url" class="form-control" id="iiif_server_url" name="settings[iiif_server_url]" value="{{ e($settings['iiif_server_url'] ?? '') }}" placeholder="https://iiif.example.com">
                                            <small class="form-text text-muted">{{ __('External IIIF server URL (leave blank to use built-in)') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Show Navigator') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="iiif_show_navigator" name="settings[iiif_show_navigator]" value="true" {{ ($settings['iiif_show_navigator'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="iiif_show_navigator">{{ __('Show mini-map navigator') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Enable Rotation') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="iiif_show_rotation" name="settings[iiif_show_rotation]" value="true" {{ ($settings['iiif_show_rotation'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="iiif_show_rotation">{{ __('Allow image rotation') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="iiif_max_zoom">{{ __('Max Zoom Level') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control" id="iiif_max_zoom" name="settings[iiif_max_zoom]" value="{{ $settings['iiif_max_zoom'] ?? 10 }}" min="1" max="20">
                                        </div>
                                    </div>
                                </fieldset>
                            @break

                            @case('jobs')
                                <!-- Jobs Settings -->
                                <fieldset class="mb-4">
                                    <legend>{{ __('Background Job Settings') }}</legend>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Enable Jobs') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="jobs_enabled" name="settings[jobs_enabled]" value="true" {{ ($settings['jobs_enabled'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="jobs_enabled">{{ __('Enable background job processing') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="jobs_max_concurrent">{{ __('Max Concurrent Jobs') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control" id="jobs_max_concurrent" name="settings[jobs_max_concurrent]" value="{{ $settings['jobs_max_concurrent'] ?? 2 }}" min="1" max="10">
                                            <small class="form-text text-muted">{{ __('Maximum number of jobs to run simultaneously') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="jobs_timeout">{{ __('Job Timeout') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="jobs_timeout" name="settings[jobs_timeout]" value="{{ $settings['jobs_timeout'] ?? 3600 }}" min="60" max="86400">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">{{ __('seconds') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="jobs_retry_attempts">{{ __('Retry Attempts') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control" id="jobs_retry_attempts" name="settings[jobs_retry_attempts]" value="{{ $settings['jobs_retry_attempts'] ?? 3 }}" min="0" max="10">
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="jobs_cleanup_days">{{ __('Cleanup After') }}</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="jobs_cleanup_days" name="settings[jobs_cleanup_days]" value="{{ $settings['jobs_cleanup_days'] ?? 30 }}" min="1" max="365">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">{{ __('days') }}</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">{{ __('Delete completed jobs after this many days') }}</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Notify on Failure') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="jobs_notify_on_failure" name="settings[jobs_notify_on_failure]" value="true" {{ ($settings['jobs_notify_on_failure'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="jobs_notify_on_failure">{{ __('Send email when jobs fail') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="jobs_notify_email">{{ __('Notification Email') }}</label>
                                        <div class="col-sm-9">
                                            <input type="email" class="form-control" id="jobs_notify_email" name="settings[jobs_notify_email]" value="{{ e($settings['jobs_notify_email'] ?? '') }}" placeholder="admin@example.com">
                                        </div>
                                    </div>
                                </fieldset>

                                <!-- Job Status -->
                                <fieldset class="mb-4">
                                    <legend>{{ __('Job Queue Status') }}</legend>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <a href="{{ url_for(['module' => 'jobs', 'action' => 'browse']) }}">{{ __('View all jobs in Job Manager') }}</a>
                                    </div>
                                </fieldset>
                            @break

                            @case('fuseki')
                                <!-- Fuseki Connection Settings -->
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fa fa-server me-2"></i>{{ __('Fuseki Connection') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-8">
                                                <label for="fuseki_endpoint" class="form-label">{{ __('Fuseki SPARQL Endpoint') }}</label>
                                                <input type="url" class="form-control" id="fuseki_endpoint" name="settings[fuseki_endpoint]"
                                                       value="{{ e($settings['fuseki_endpoint'] ?? sfConfig::get('app_ric_fuseki_endpoint', 'http://localhost:3030/ric')) }}"
                                                       placeholder="http://localhost:3030/ric">
                                                <div class="form-text">{{ __('Full URL to Fuseki SPARQL endpoint (e.g., http://localhost:3030/ric)') }}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="button" class="btn btn-outline-secondary d-block w-100" id="test-fuseki-btn">
                                                    <i class="fa fa-plug me-1"></i>{{ __('Test Connection') }}
                                                </button>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="fuseki_username" class="form-label">{{ __('Username') }}</label>
                                                <input type="text" class="form-control" id="fuseki_username" name="settings[fuseki_username]"
                                                       value="{{ e($settings['fuseki_username'] ?? 'admin') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="fuseki_password" class="form-label">{{ __('Password') }}</label>
                                                <input type="password" class="form-control" id="fuseki_password" name="settings[fuseki_password]"
                                                       value="{{ e($settings['fuseki_password'] ?? '') }}"
                                                       placeholder="{{ __('Leave blank to keep current') }}">
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
                                        <h5 class="mb-0"><i class="fa fa-sync-alt me-2"></i>{{ __('RIC Sync Settings') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="fuseki_sync_enabled"
                                                           name="settings[fuseki_sync_enabled]" value="1"
                                                           {{ ($settings['fuseki_sync_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="fuseki_sync_enabled">
                                                        <strong>{{ __('Enable Automatic Sync') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Master switch for all RIC sync operations') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="fuseki_queue_enabled"
                                                           name="settings[fuseki_queue_enabled]" value="1"
                                                           {{ ($settings['fuseki_queue_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="fuseki_queue_enabled">
                                                        <strong>{{ __('Use Async Queue') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Queue sync operations for background processing') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="fuseki_sync_on_save"
                                                           name="settings[fuseki_sync_on_save]" value="1"
                                                           {{ ($settings['fuseki_sync_on_save'] ?? '1') === '1' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="fuseki_sync_on_save">
                                                        {{ __('Sync on Record Save') }}
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Automatically sync to Fuseki when records are created/updated') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="fuseki_sync_on_delete"
                                                           name="settings[fuseki_sync_on_delete]" value="1"
                                                           {{ ($settings['fuseki_sync_on_delete'] ?? '1') === '1' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="fuseki_sync_on_delete">
                                                        {{ __('Sync on Record Delete') }}
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Remove from Fuseki when records are deleted in AtoM') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="fuseki_cascade_delete"
                                                           name="settings[fuseki_cascade_delete]" value="1"
                                                           {{ ($settings['fuseki_cascade_delete'] ?? '1') === '1' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="fuseki_cascade_delete">
                                                        {{ __('Cascade Delete References') }}
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Also remove triples where deleted record is the object') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="fuseki_batch_size" class="form-label">{{ __('Batch Size') }}</label>
                                                <input type="number" class="form-control" id="fuseki_batch_size"
                                                       name="settings[fuseki_batch_size]" min="10" max="1000" step="10"
                                                       value="{{ e($settings['fuseki_batch_size'] ?? '100') }}">
                                                <div class="form-text">{{ __('Records per batch for bulk sync operations') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Integrity Check Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fa fa-check-double me-2"></i>{{ __('Integrity Check Settings') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="fuseki_integrity_schedule" class="form-label">{{ __('Check Schedule') }}</label>
                                                <select class="form-select" id="fuseki_integrity_schedule" name="settings[fuseki_integrity_schedule]">
                                                    <option value="daily" {{ ($settings['fuseki_integrity_schedule'] ?? '') === 'daily' ? 'selected' : '' }}>
                                                        {{ __('Daily') }}
                                                    </option>
                                                    <option value="weekly" {{ ($settings['fuseki_integrity_schedule'] ?? 'weekly') === 'weekly' ? 'selected' : '' }}>
                                                        {{ __('Weekly') }}
                                                    </option>
                                                    <option value="monthly" {{ ($settings['fuseki_integrity_schedule'] ?? '') === 'monthly' ? 'selected' : '' }}>
                                                        {{ __('Monthly') }}
                                                    </option>
                                                    <option value="disabled" {{ ($settings['fuseki_integrity_schedule'] ?? '') === 'disabled' ? 'selected' : '' }}>
                                                        {{ __('Disabled') }}
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="fuseki_orphan_retention_days" class="form-label">{{ __('Orphan Retention (days)') }}</label>
                                                <input type="number" class="form-control" id="fuseki_orphan_retention_days"
                                                       name="settings[fuseki_orphan_retention_days]" min="1" max="365"
                                                       value="{{ e($settings['fuseki_orphan_retention_days'] ?? '30') }}">
                                                <div class="form-text">{{ __('Days to retain orphaned triples before cleanup') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="card mb-4">
                                    <div class="card-header bg-secondary text-white">
                                        <h5 class="mb-0"><i class="fa fa-bolt me-2"></i>{{ __('Quick Actions') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="{{ url_for(['module' => 'ricDashboard', 'action' => 'index']) }}" class="btn btn-outline-primary">
                                                <i class="fa fa-tachometer-alt me-1"></i>{{ __('RIC Dashboard') }}
                                            </a>
                                            <a href="https://www.ica.org/standards/RiC/ontology" target="_blank" class="btn btn-outline-info">
                                                <i class="fa fa-book me-1"></i>{{ __('RiC-O Reference') }}
                                            </a>
                                            @php
                                                $fusekiAdmin = preg_replace('#/[^/]+$#', '/', $settings['fuseki_endpoint'] ?? sfConfig::get('app_ric_fuseki_endpoint', 'http://localhost:3030/ric'));
                                            @endphp
                                            <a href="{{ e($fusekiAdmin) }}" target="_blank" class="btn btn-outline-secondary">
                                                <i class="fa fa-database me-1"></i>{{ __('Fuseki Admin') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fuseki Test Connection Script -->
                                <script {!! $csp_nonce !!}>
                                document.getElementById('test-fuseki-btn')?.addEventListener('click', function() {
                                    const btn = this;
                                    const resultDiv = document.getElementById('fuseki-test-result');
                                    const endpoint = document.getElementById('fuseki_endpoint').value;
                                    const username = document.getElementById('fuseki_username').value;
                                    const password = document.getElementById('fuseki_password').value;

                                    btn.disabled = true;
                                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> {{ __("Testing...") }}';
                                    resultDiv.classList.add('d-none');

                                    // Test via AJAX endpoint
                                    fetch('{{ url_for(['module' => 'ahgSettings', 'action' => 'fusekiTest']) }}', {
                                        method: 'POST',
                                        headers: {'Content-Type': 'application/json'},
                                        body: JSON.stringify({endpoint, username, password})
                                    })
                                    .then(r => r.json())
                                    .then(data => {
                                        btn.disabled = false;
                                        btn.innerHTML = '<i class="fa fa-plug me-1"></i>{{ __("Test Connection") }}';
                                        resultDiv.classList.remove('d-none', 'alert-success', 'alert-danger');

                                        if (data.success) {
                                            resultDiv.classList.add('alert-success');
                                            resultDiv.innerHTML = '<i class="fa fa-check-circle me-2"></i>' +
                                                '{{ __("Connection successful!") }} ' +
                                                '{{ __("Triple count") }}: ' + (data.triple_count || 'N/A');
                                        } else {
                                            resultDiv.classList.add('alert-danger');
                                            resultDiv.innerHTML = '<i class="fa fa-times-circle me-2"></i>' +
                                                '{{ __("Connection failed") }}: ' + (data.error || '{{ __("Unknown error") }}');
                                        }
                                    })
                                    .catch(err => {
                                        btn.disabled = false;
                                        btn.innerHTML = '<i class="fa fa-plug me-1"></i>{{ __("Test Connection") }}';
                                        resultDiv.classList.remove('d-none', 'alert-success');
                                        resultDiv.classList.add('alert-danger');
                                        resultDiv.innerHTML = '<i class="fa fa-times-circle me-2"></i>{{ __("Error") }}: ' + err.message;
                                    });
                                });
                                </script>
                            @break

                            @case('metadata')
                                <fieldset class="mb-4">
                                    <legend>{{ __('Metadata Extraction') }}</legend>
                                    <div class="alert alert-info"><i class="fas fa-info-circle"></i> {{ __('Configure automatic metadata extraction.') }}</div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Extract on Upload') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="meta_extract_on_upload" name="settings[meta_extract_on_upload]" value="true" {{ ($settings['meta_extract_on_upload'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="meta_extract_on_upload">{{ __('Auto-extract metadata') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Auto-Populate') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="meta_auto_populate" name="settings[meta_auto_populate]" value="true" {{ ($settings['meta_auto_populate'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="meta_auto_populate">{{ __('Populate description fields') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <fieldset class="mb-4">
                                    <legend>{{ __('File Types') }}</legend>
                                    <div class="row"><div class="col-md-6">
                                        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="meta_images" name="settings[meta_images]" value="true" {{ ($settings['meta_images'] ?? 'true') === 'true' ? 'checked' : '' }}><label class="custom-control-label" for="meta_images"><i class="fas fa-image text-success"></i> Images</label></div>
                                        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="meta_pdf" name="settings[meta_pdf]" value="true" {{ ($settings['meta_pdf'] ?? 'true') === 'true' ? 'checked' : '' }}><label class="custom-control-label" for="meta_pdf"><i class="fas fa-file-pdf text-danger"></i> PDF</label></div>
                                        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="meta_office" name="settings[meta_office]" value="true" {{ ($settings['meta_office'] ?? 'true') === 'true' ? 'checked' : '' }}><label class="custom-control-label" for="meta_office"><i class="fas fa-file-word text-primary"></i> Office</label></div>
                                    </div><div class="col-md-6">
                                        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="meta_video" name="settings[meta_video]" value="true" {{ ($settings['meta_video'] ?? 'true') === 'true' ? 'checked' : '' }}><label class="custom-control-label" for="meta_video"><i class="fas fa-video text-info"></i> Video</label></div>
                                        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="meta_audio" name="settings[meta_audio]" value="true" {{ ($settings['meta_audio'] ?? 'true') === 'true' ? 'checked' : '' }}><label class="custom-control-label" for="meta_audio"><i class="fas fa-music text-warning"></i> Audio</label></div>
                                    </div></div>
                                </fieldset>
                                <fieldset class="mb-4">
                                    <legend>{{ __('Field Mapping') }}</legend>
                                    <p class="text-muted">{{ __('Configure where extracted metadata is saved:') }}</p>
                                    <table class="table table-sm table-bordered">
                                        <thead class="thead-dark"><tr><th style="width:20%">Metadata Source</th><th style="width:26%">Archives (ISAD)</th><th style="width:27%">Museum (Spectrum)</th><th style="width:27%">DAM</th></tr></thead>
                                        <tbody>
                                            <tr>
                                                <td><i class="fas fa-heading text-muted"></i> Title</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_title_isad]">
                                                    <option value="title" {{ ($settings['map_title_isad'] ?? 'title') === 'title' ? 'selected' : '' }}>Title</option>
                                                    <option value="alternateTitle" {{ ($settings['map_title_isad'] ?? '') === 'alternateTitle' ? 'selected' : '' }}>Alternate Title</option>
                                                    <option value="none" {{ ($settings['map_title_isad'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_title_museum]">
                                                    <option value="objectName" {{ ($settings['map_title_museum'] ?? 'objectName') === 'objectName' ? 'selected' : '' }}>Object Name</option>
                                                    <option value="title" {{ ($settings['map_title_museum'] ?? '') === 'title' ? 'selected' : '' }}>Title</option>
                                                    <option value="none" {{ ($settings['map_title_museum'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_title_dam]">
                                                    <option value="title" {{ ($settings['map_title_dam'] ?? 'title') === 'title' ? 'selected' : '' }}>Title / Filename</option>
                                                    <option value="caption" {{ ($settings['map_title_dam'] ?? '') === 'caption' ? 'selected' : '' }}>Caption</option>
                                                    <option value="none" {{ ($settings['map_title_dam'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-user text-muted"></i> Creator/Author</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_creator_isad]">
                                                    <option value="nameAccessPoints" {{ ($settings['map_creator_isad'] ?? 'nameAccessPoints') === 'nameAccessPoints' ? 'selected' : '' }}>Name Access Points</option>
                                                    <option value="creators" {{ ($settings['map_creator_isad'] ?? '') === 'creators' ? 'selected' : '' }}>Creators (Event)</option>
                                                    <option value="none" {{ ($settings['map_creator_isad'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_creator_museum]">
                                                    <option value="productionPerson" {{ ($settings['map_creator_museum'] ?? 'productionPerson') === 'productionPerson' ? 'selected' : '' }}>Production Person</option>
                                                    <option value="nameAccessPoints" {{ ($settings['map_creator_museum'] ?? '') === 'nameAccessPoints' ? 'selected' : '' }}>Name Access Points</option>
                                                    <option value="none" {{ ($settings['map_creator_museum'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_creator_dam]">
                                                    <option value="creator" {{ ($settings['map_creator_dam'] ?? 'creator') === 'creator' ? 'selected' : '' }}>Creator / Photographer</option>
                                                    <option value="creditLine" {{ ($settings['map_creator_dam'] ?? '') === 'creditLine' ? 'selected' : '' }}>Credit Line</option>
                                                    <option value="none" {{ ($settings['map_creator_dam'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-tags text-muted"></i> Keywords</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_keywords_isad]">
                                                    <option value="subjectAccessPoints" {{ ($settings['map_keywords_isad'] ?? 'subjectAccessPoints') === 'subjectAccessPoints' ? 'selected' : '' }}>Subject Access Points</option>
                                                    <option value="genreAccessPoints" {{ ($settings['map_keywords_isad'] ?? '') === 'genreAccessPoints' ? 'selected' : '' }}>Genre Access Points</option>
                                                    <option value="none" {{ ($settings['map_keywords_isad'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_keywords_museum]">
                                                    <option value="objectCategory" {{ ($settings['map_keywords_museum'] ?? 'objectCategory') === 'objectCategory' ? 'selected' : '' }}>Object Category</option>
                                                    <option value="subjectAccessPoints" {{ ($settings['map_keywords_museum'] ?? '') === 'subjectAccessPoints' ? 'selected' : '' }}>Subject Access Points</option>
                                                    <option value="none" {{ ($settings['map_keywords_museum'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_keywords_dam]">
                                                    <option value="keywords" {{ ($settings['map_keywords_dam'] ?? 'keywords') === 'keywords' ? 'selected' : '' }}>Keywords / Tags</option>
                                                    <option value="category" {{ ($settings['map_keywords_dam'] ?? '') === 'category' ? 'selected' : '' }}>Category</option>
                                                    <option value="none" {{ ($settings['map_keywords_dam'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-align-left text-muted"></i> Description</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_description_isad]">
                                                    <option value="scopeAndContent" {{ ($settings['map_description_isad'] ?? 'scopeAndContent') === 'scopeAndContent' ? 'selected' : '' }}>Scope and Content</option>
                                                    <option value="archivalHistory" {{ ($settings['map_description_isad'] ?? '') === 'archivalHistory' ? 'selected' : '' }}>Archival History</option>
                                                    <option value="none" {{ ($settings['map_description_isad'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_description_museum]">
                                                    <option value="briefDescription" {{ ($settings['map_description_museum'] ?? 'briefDescription') === 'briefDescription' ? 'selected' : '' }}>Brief Description</option>
                                                    <option value="physicalDescription" {{ ($settings['map_description_museum'] ?? '') === 'physicalDescription' ? 'selected' : '' }}>Physical Description</option>
                                                    <option value="none" {{ ($settings['map_description_museum'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_description_dam]">
                                                    <option value="caption" {{ ($settings['map_description_dam'] ?? 'caption') === 'caption' ? 'selected' : '' }}>Caption / Description</option>
                                                    <option value="instructions" {{ ($settings['map_description_dam'] ?? '') === 'instructions' ? 'selected' : '' }}>Special Instructions</option>
                                                    <option value="none" {{ ($settings['map_description_dam'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-calendar text-muted"></i> Date Created</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_date_isad]">
                                                    <option value="creationEvent" {{ ($settings['map_date_isad'] ?? 'creationEvent') === 'creationEvent' ? 'selected' : '' }}>Creation Event Date</option>
                                                    <option value="none" {{ ($settings['map_date_isad'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_date_museum]">
                                                    <option value="productionDate" {{ ($settings['map_date_museum'] ?? 'productionDate') === 'productionDate' ? 'selected' : '' }}>Production Date</option>
                                                    <option value="none" {{ ($settings['map_date_museum'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_date_dam]">
                                                    <option value="dateCreated" {{ ($settings['map_date_dam'] ?? 'dateCreated') === 'dateCreated' ? 'selected' : '' }}>Date Created / Taken</option>
                                                    <option value="dateModified" {{ ($settings['map_date_dam'] ?? '') === 'dateModified' ? 'selected' : '' }}>Date Modified</option>
                                                    <option value="none" {{ ($settings['map_date_dam'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-copyright text-muted"></i> Copyright</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_copyright_isad]">
                                                    <option value="accessConditions" {{ ($settings['map_copyright_isad'] ?? 'accessConditions') === 'accessConditions' ? 'selected' : '' }}>Access Conditions</option>
                                                    <option value="reproductionConditions" {{ ($settings['map_copyright_isad'] ?? '') === 'reproductionConditions' ? 'selected' : '' }}>Reproduction Conditions</option>
                                                    <option value="none" {{ ($settings['map_copyright_isad'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_copyright_museum]">
                                                    <option value="rightsNotes" {{ ($settings['map_copyright_museum'] ?? 'rightsNotes') === 'rightsNotes' ? 'selected' : '' }}>Rights Notes</option>
                                                    <option value="none" {{ ($settings['map_copyright_museum'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_copyright_dam]">
                                                    <option value="copyrightNotice" {{ ($settings['map_copyright_dam'] ?? 'copyrightNotice') === 'copyrightNotice' ? 'selected' : '' }}>Copyright Notice</option>
                                                    <option value="usageRights" {{ ($settings['map_copyright_dam'] ?? '') === 'usageRights' ? 'selected' : '' }}>Usage Rights</option>
                                                    <option value="none" {{ ($settings['map_copyright_dam'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-camera text-muted"></i> Technical Data</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_technical_isad]">
                                                    <option value="physicalCharacteristics" {{ ($settings['map_technical_isad'] ?? 'physicalCharacteristics') === 'physicalCharacteristics' ? 'selected' : '' }}>Physical Characteristics</option>
                                                    <option value="extentAndMedium" {{ ($settings['map_technical_isad'] ?? '') === 'extentAndMedium' ? 'selected' : '' }}>Extent and Medium</option>
                                                    <option value="none" {{ ($settings['map_technical_isad'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_technical_museum]">
                                                    <option value="technicalDescription" {{ ($settings['map_technical_museum'] ?? 'technicalDescription') === 'technicalDescription' ? 'selected' : '' }}>Technical Description</option>
                                                    <option value="none" {{ ($settings['map_technical_museum'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_technical_dam]">
                                                    <option value="technicalInfo" {{ ($settings['map_technical_dam'] ?? 'technicalInfo') === 'technicalInfo' ? 'selected' : '' }}>Technical Info (EXIF)</option>
                                                    <option value="cameraInfo" {{ ($settings['map_technical_dam'] ?? '') === 'cameraInfo' ? 'selected' : '' }}>Camera / Equipment</option>
                                                    <option value="none" {{ ($settings['map_technical_dam'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-map-marker-alt text-muted"></i> GPS Location</td>
                                                <td><select class="form-control form-control-sm" name="settings[map_gps_isad]">
                                                    <option value="placeAccessPoints" {{ ($settings['map_gps_isad'] ?? 'placeAccessPoints') === 'placeAccessPoints' ? 'selected' : '' }}>Place Access Points</option>
                                                    <option value="physicalCharacteristics" {{ ($settings['map_gps_isad'] ?? '') === 'physicalCharacteristics' ? 'selected' : '' }}>Physical Characteristics</option>
                                                    <option value="none" {{ ($settings['map_gps_isad'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_gps_museum]">
                                                    <option value="fieldCollectionPlace" {{ ($settings['map_gps_museum'] ?? 'fieldCollectionPlace') === 'fieldCollectionPlace' ? 'selected' : '' }}>Field Collection Place</option>
                                                    <option value="placeAccessPoints" {{ ($settings['map_gps_museum'] ?? '') === 'placeAccessPoints' ? 'selected' : '' }}>Place Access Points</option>
                                                    <option value="none" {{ ($settings['map_gps_museum'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                                <td><select class="form-control form-control-sm" name="settings[map_gps_dam]">
                                                    <option value="gpsLocation" {{ ($settings['map_gps_dam'] ?? 'gpsLocation') === 'gpsLocation' ? 'selected' : '' }}>GPS Coordinates</option>
                                                    <option value="location" {{ ($settings['map_gps_dam'] ?? '') === 'location' ? 'selected' : '' }}>Location Name</option>
                                                    <option value="none" {{ ($settings['map_gps_dam'] ?? '') === 'none' ? 'selected' : '' }}>Do not map</option>
                                                </select></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </fieldset>
                            @break

                            @case('faces')
                                <fieldset class="mb-4">
                                    <legend>{{ __('Face Detection') }}</legend>
                                    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> {{ __('Experimental feature.') }}</div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Enable') }}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="face_enabled" name="settings[face_enabled]" value="true" {{ ($settings['face_enabled'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="face_enabled">{{ __('Detect faces') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">{{ __('Backend') }}</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" name="settings[face_backend]">
                                                <option value="local" {{ ($settings['face_backend'] ?? 'local') === 'local' ? 'selected' : '' }}>Local (OpenCV)</option>
                                                <option value="aws" {{ ($settings['face_backend'] ?? '') === 'aws' ? 'selected' : '' }}>AWS Rekognition</option>
                                                <option value="azure" {{ ($settings['face_backend'] ?? '') === 'azure' ? 'selected' : '' }}>Azure Face API</option>
                                            </select>
                                        </div>
                                    </div>
                                </fieldset>
                            @break

                            @case('multi_tenant')
                                @include('_multiTenantSettings', ['settings' => $settings])
                            @break

                            @case('ingest')
                                <!-- AI & Processing Defaults -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-brain me-2"></i>{{ __('AI & Processing Defaults') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">{{ __('These defaults are pre-selected when creating a new ingest session. Users can override per session.') }}</p>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_virus_scan"
                                                           name="settings[ingest_virus_scan]" value="true"
                                                           {{ ($settings['ingest_virus_scan'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_virus_scan">
                                                        <strong><i class="fas fa-shield-virus me-1 text-danger"></i>{{ __('Virus Scan (ClamAV)') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Scan all uploaded files for malware before commit. Infected files are quarantined.') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_ocr"
                                                           name="settings[ingest_ocr]" value="true"
                                                           {{ ($settings['ingest_ocr'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_ocr">
                                                        <strong><i class="fas fa-file-alt me-1 text-primary"></i>{{ __('OCR (Tesseract)') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Extract text from images and PDFs using Tesseract / pdftotext.') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_ner"
                                                           name="settings[ingest_ner]" value="true"
                                                           {{ ($settings['ingest_ner'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_ner">
                                                        <strong><i class="fas fa-tags me-1 text-success"></i>{{ __('NER (Named Entity Recognition)') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Extract persons, organizations, places and dates from text fields. Creates access points automatically.') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_summarize"
                                                           name="settings[ingest_summarize]" value="true"
                                                           {{ ($settings['ingest_summarize'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_summarize">
                                                        <strong><i class="fas fa-compress-alt me-1 text-warning"></i>{{ __('Auto-Summarize') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Generate scope and content summaries for records with extensive text.') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_spellcheck"
                                                           name="settings[ingest_spellcheck]" value="true"
                                                           {{ ($settings['ingest_spellcheck'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_spellcheck">
                                                        <strong><i class="fas fa-spell-check me-1 text-info"></i>{{ __('Spell Check (aspell)') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Check spelling and grammar on title, scope and content, and archival history fields.') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_format_id"
                                                           name="settings[ingest_format_id]" value="true"
                                                           {{ ($settings['ingest_format_id'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_format_id">
                                                        <strong><i class="fas fa-fingerprint me-1 text-secondary"></i>{{ __('Format Identification (Siegfried/PRONOM)') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Identify file formats using PRONOM registry via Siegfried. Records PUID, MIME type, and confidence.') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_face_detect"
                                                           name="settings[ingest_face_detect]" value="true"
                                                           {{ ($settings['ingest_face_detect'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_face_detect">
                                                        <strong><i class="fas fa-user-circle me-1 text-dark"></i>{{ __('Face Detection') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Detect and match faces in images to authority records.') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_translate"
                                                           name="settings[ingest_translate]" value="true"
                                                           {{ ($settings['ingest_translate'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_translate">
                                                        <strong><i class="fas fa-language me-1 text-primary"></i>{{ __('Auto-Translate (Argos)') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Translate metadata fields using offline Argos Translate engine.') }}</div>
                                            </div>
                                        </div>

                                        <!-- Translation/Spellcheck language -->
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-4">
                                                <label for="ingest_translate_from" class="form-label">{{ __('Translate from') }}</label>
                                                <select class="form-select" id="ingest_translate_from" name="settings[ingest_translate_from]">
                                                    @foreach (['en' => 'English', 'af' => 'Afrikaans', 'zu' => 'Zulu', 'xh' => 'Xhosa', 'st' => 'Sotho', 'fr' => 'French', 'de' => 'German', 'pt' => 'Portuguese', 'es' => 'Spanish', 'nl' => 'Dutch'] as $code => $name)
                                                        <option value="{{ $code }}" {{ ($settings['ingest_translate_from'] ?? 'en') === $code ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="ingest_translate_to" class="form-label">{{ __('Translate to') }}</label>
                                                <select class="form-select" id="ingest_translate_to" name="settings[ingest_translate_to]">
                                                    @foreach (['af' => 'Afrikaans', 'en' => 'English', 'zu' => 'Zulu', 'xh' => 'Xhosa', 'st' => 'Sotho', 'fr' => 'French', 'de' => 'German', 'pt' => 'Portuguese', 'es' => 'Spanish', 'nl' => 'Dutch'] as $code => $name)
                                                        <option value="{{ $code }}" {{ ($settings['ingest_translate_to'] ?? 'af') === $code ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="ingest_spellcheck_lang" class="form-label">{{ __('Spellcheck language') }}</label>
                                                <select class="form-select" id="ingest_spellcheck_lang" name="settings[ingest_spellcheck_lang]">
                                                    @foreach (['en_ZA' => 'English (ZA)', 'en_US' => 'English (US)', 'en_GB' => 'English (UK)', 'af' => 'Afrikaans'] as $code => $name)
                                                        <option value="{{ $code }}" {{ ($settings['ingest_spellcheck_lang'] ?? 'en_ZA') === $code ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Output Defaults -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>{{ __('Output Defaults') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_create_records"
                                                           name="settings[ingest_create_records]" value="true"
                                                           {{ ($settings['ingest_create_records'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_create_records">{{ __('Create AtoM records') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_generate_sip"
                                                           name="settings[ingest_generate_sip]" value="true"
                                                           {{ ($settings['ingest_generate_sip'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_generate_sip">{{ __('Generate SIP package') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_generate_aip"
                                                           name="settings[ingest_generate_aip]" value="true"
                                                           {{ ($settings['ingest_generate_aip'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_generate_aip">{{ __('Generate AIP package') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_generate_dip"
                                                           name="settings[ingest_generate_dip]" value="true"
                                                           {{ ($settings['ingest_generate_dip'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_generate_dip">{{ __('Generate DIP package') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_thumbnails"
                                                           name="settings[ingest_thumbnails]" value="true"
                                                           {{ ($settings['ingest_thumbnails'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_thumbnails">{{ __('Generate thumbnails') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ingest_reference"
                                                           name="settings[ingest_reference]" value="true"
                                                           {{ ($settings['ingest_reference'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ingest_reference">{{ __('Generate reference images') }}</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <label for="ingest_sip_path" class="form-label">{{ __('Default SIP output path') }}</label>
                                                <input type="text" class="form-control" id="ingest_sip_path" name="settings[ingest_sip_path]"
                                                       value="{{ htmlspecialchars($settings['ingest_sip_path'] ?? '') }}"
                                                       placeholder="/uploads/sip">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="ingest_aip_path" class="form-label">{{ __('Default AIP output path') }}</label>
                                                <input type="text" class="form-control" id="ingest_aip_path" name="settings[ingest_aip_path]"
                                                       value="{{ htmlspecialchars($settings['ingest_aip_path'] ?? '') }}"
                                                       placeholder="/uploads/aip">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="ingest_dip_path" class="form-label">{{ __('Default DIP output path') }}</label>
                                                <input type="text" class="form-control" id="ingest_dip_path" name="settings[ingest_dip_path]"
                                                       value="{{ htmlspecialchars($settings['ingest_dip_path'] ?? '') }}"
                                                       placeholder="/uploads/dip">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="ingest_default_sector" class="form-label">{{ __('Default sector') }}</label>
                                                <select class="form-select" id="ingest_default_sector" name="settings[ingest_default_sector]">
                                                    @foreach (['archive' => 'Archive', 'museum' => 'Museum', 'library' => 'Library', 'gallery' => 'Gallery', 'dam' => 'DAM'] as $val => $label)
                                                        <option value="{{ $val }}" {{ ($settings['ingest_default_sector'] ?? 'archive') === $val ? 'selected' : '' }}>{{ __($label) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="ingest_default_standard" class="form-label">{{ __('Default descriptive standard') }}</label>
                                                <select class="form-select" id="ingest_default_standard" name="settings[ingest_default_standard]">
                                                    @foreach (['isadg' => 'ISAD(G)', 'dc' => 'Dublin Core', 'rad' => 'RAD', 'dacs' => 'DACS', 'spectrum' => 'SPECTRUM', 'cco' => 'CCO'] as $val => $label)
                                                        <option value="{{ $val }}" {{ ($settings['ingest_default_standard'] ?? 'isadg') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Service Availability -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>{{ __('Service Availability') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">{{ __('Processing options require the corresponding services to be installed and running.') }}</p>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>{{ __('Service') }}</th>
                                                        <th>{{ __('Required Plugin / Tool') }}</th>
                                                        <th>{{ __('Status') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                    $services = [
                                                        ['Virus Scan', 'ClamAV daemon', @shell_exec('clamdscan --version 2>/dev/null') ? true : false],
                                                        ['OCR', 'tesseract + pdftotext', @shell_exec('tesseract --version 2>&1') ? true : false],
                                                        ['NER', 'ahgAIPlugin + Python API', class_exists('ahgAIPluginConfiguration')],
                                                        ['Summarize', 'ahgAIPlugin + Python API', class_exists('ahgAIPluginConfiguration')],
                                                        ['Spell Check', 'aspell', @shell_exec('aspell --version 2>&1') ? true : false],
                                                        ['Translation', 'ahgAIPlugin + Argos Translate', class_exists('ahgAIPluginConfiguration')],
                                                        ['Format ID', 'ahgPreservationPlugin + Siegfried', @shell_exec('sf -version 2>&1') ? true : false],
                                                        ['Face Detection', 'ahgAIPlugin', class_exists('ahgAIPluginConfiguration')],
                                                    ];
                                                    @endphp
                                                    @foreach ($services as $svc)
                                                    <tr>
                                                        <td><strong>{{ __($svc[0]) }}</strong></td>
                                                        <td><code>{{ $svc[1] }}</code></td>
                                                        <td>
                                                            @if ($svc[2])
                                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>{{ __('Available') }}</span>
                                                            @else
                                                                <span class="badge bg-secondary"><i class="fas fa-times me-1"></i>{{ __('Not installed') }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @break

                            @case('portable_export')
                                <!-- Portable Export Settings -->
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fas fa-compact-disc me-2"></i>{{ __('Portable Export Configuration') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">{{ __('Configure defaults for standalone portable catalogue exports (CD/USB/ZIP distribution).') }}</p>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_enabled"
                                                           name="settings[portable_export_enabled]" value="true"
                                                           {{ ($settings['portable_export_enabled'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="portable_export_enabled">
                                                        <strong>{{ __('Enable Portable Export') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Allow creation of offline portable catalogues from Admin UI.') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Retention (days)') }}</label>
                                                <input type="number" class="form-control" name="settings[portable_export_retention_days]"
                                                       value="{{ $settings['portable_export_retention_days'] ?? '30' }}" min="1" max="365">
                                                <div class="form-text">{{ __('Completed exports are auto-deleted after this many days. Run portable:cleanup.') }}</div>
                                            </div>
                                        </div>

                                        <hr>
                                        <h6 class="mb-3">{{ __('Default Content Options') }}</h6>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_include_objects"
                                                           name="settings[portable_export_include_objects]" value="true"
                                                           {{ ($settings['portable_export_include_objects'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="portable_export_include_objects">{{ __('Digital Objects') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_include_thumbnails"
                                                           name="settings[portable_export_include_thumbnails]" value="true"
                                                           {{ ($settings['portable_export_include_thumbnails'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="portable_export_include_thumbnails">{{ __('Thumbnails') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_include_references"
                                                           name="settings[portable_export_include_references]" value="true"
                                                           {{ ($settings['portable_export_include_references'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="portable_export_include_references">{{ __('Reference Images') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_include_masters"
                                                           name="settings[portable_export_include_masters]" value="true"
                                                           {{ ($settings['portable_export_include_masters'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="portable_export_include_masters">{{ __('Master Files') }}</label>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>
                                        <h6 class="mb-3">{{ __('Default Settings') }}</h6>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">{{ __('Default Viewer Mode') }}</label>
                                                <select class="form-select" name="settings[portable_export_default_mode]">
                                                    <option value="read_only" {{ ($settings['portable_export_default_mode'] ?? 'read_only') === 'read_only' ? 'selected' : '' }}>{{ __('Read Only') }}</option>
                                                    <option value="editable" {{ ($settings['portable_export_default_mode'] ?? '') === 'editable' ? 'selected' : '' }}>{{ __('Editable') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">{{ __('Default Language') }}</label>
                                                <select class="form-select" name="settings[portable_export_default_culture]">
                                                    <option value="en" {{ ($settings['portable_export_default_culture'] ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
                                                    <option value="fr" {{ ($settings['portable_export_default_culture'] ?? '') === 'fr' ? 'selected' : '' }}>French</option>
                                                    <option value="af" {{ ($settings['portable_export_default_culture'] ?? '') === 'af' ? 'selected' : '' }}>Afrikaans</option>
                                                    <option value="pt" {{ ($settings['portable_export_default_culture'] ?? '') === 'pt' ? 'selected' : '' }}>Portuguese</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">{{ __('Max Export Size (MB)') }}</label>
                                                <input type="number" class="form-control" name="settings[portable_export_max_size_mb]"
                                                       value="{{ $settings['portable_export_max_size_mb'] ?? '2048' }}" min="100" max="10240">
                                            </div>
                                        </div>

                                        <hr>
                                        <h6 class="mb-3">{{ __('Integration') }}</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_description_button"
                                                           name="settings[portable_export_description_button]" value="true"
                                                           {{ ($settings['portable_export_description_button'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="portable_export_description_button">
                                                        {{ __('Show export button on description pages') }}
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Adds "Portable Viewer" to the Export section on archival description pages.') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="portable_export_clipboard_button"
                                                           name="settings[portable_export_clipboard_button]" value="true"
                                                           {{ ($settings['portable_export_clipboard_button'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="portable_export_clipboard_button">
                                                        {{ __('Show export button on clipboard page') }}
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Adds "Portable Catalogue" option to the clipboard export page.') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @break

                            @case('encryption')
                                <!-- Encryption Master Toggle -->
                                <div class="card mb-4">
                                    <div class="card-header bg-dark text-white">
                                        <h5 class="mb-0"><i class="fas fa-lock me-2"></i>{{ __('Encryption Configuration') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">{{ __('Encryption for digital object files and sensitive database fields using') }} <strong>{{ $algoName }}</strong>. {{ __('Requires an encryption key at /etc/atom/encryption.key.') }}</p>

                                        @php
                                            $keyExists = file_exists('/etc/atom/encryption.key');
                                            $keyPerms = $keyExists ? substr(sprintf('%o', fileperms('/etc/atom/encryption.key')), -4) : null;
                                            $hasSodium = extension_loaded('sodium') && function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push');
                                            $algoName = $hasSodium ? 'XChaCha20-Poly1305 (libsodium)' : 'AES-256-GCM (OpenSSL)';
                                        @endphp

                                        <!-- Key Status -->
                                        <div class="alert {{ $keyExists ? 'alert-success' : 'alert-warning' }} mb-3">
                                            <i class="fas {{ $keyExists ? 'fa-check-circle' : 'fa-exclamation-triangle' }} me-2"></i>
                                            @if ($keyExists)
                                                <strong>{{ __('Encryption key found') }}</strong>
                                                <span class="ms-2 text-muted">{{ __('Path:') }} <code>/etc/atom/encryption.key</code> | {{ __('Permissions:') }} <code>{{ $keyPerms }}</code> | {{ __('Algorithm:') }} <code>{{ $algoName }}</code></span>
                                                @if ($keyPerms !== '0600')
                                                    <br><small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>{{ __('Permissions should be 0600 for security.') }}</small>
                                                @endif
                                            @else
                                                <strong>{{ __('No encryption key found') }}</strong>
                                                <br><small>{{ __('Generate with:') }} <code>php bin/atom encryption:key --generate</code></small>
                                            @endif
                                        </div>

                                        <!-- Master Toggle -->
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="encryption_enabled"
                                                   name="settings[encryption_enabled]" value="true"
                                                   {{ ($settings['encryption_enabled'] ?? '') === 'true' ? 'checked' : '' }}
                                                   {{ !$keyExists ? 'disabled' : '' }}>
                                            <label class="form-check-label" for="encryption_enabled">
                                                <strong>{{ __('Enable Encryption') }}</strong>
                                            </label>
                                        </div>
                                        <div class="form-text mb-3">{{ __('Master toggle. When enabled, new file uploads will be encrypted automatically.') }}</div>
                                    </div>
                                </div>

                                <!-- Layer 1: File Encryption -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-file-shield me-2"></i>{{ __('Layer 1: Digital Object Encryption') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">{{ __('Encrypts uploaded files (masters and derivatives) on disk using') }} {{ $algoName }}.</p>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="encryption_encrypt_derivatives"
                                                   name="settings[encryption_encrypt_derivatives]" value="true"
                                                   {{ ($settings['encryption_encrypt_derivatives'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="encryption_encrypt_derivatives">
                                                <strong>{{ __('Encrypt derivatives') }}</strong>
                                            </label>
                                        </div>
                                        <div class="form-text mb-3">{{ __('Also encrypt thumbnails and reference images. Recommended for full protection.') }}</div>

                                        @php
                                            $totalDOs = 0;
                                            try {
                                                $totalDOs = \Illuminate\Database\Capsule\Manager::table('digital_object')
                                                    ->whereNotNull('path')
                                                    ->whereNotNull('name')
                                                    ->count();
                                            } catch (\Exception $e) {}
                                        @endphp

                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>{{ $totalDOs }}</strong> {{ __('digital objects on disk.') }}
                                            <br><small>{{ __('To encrypt existing files:') }} <code>php bin/atom encryption:encrypt-files --limit=100</code></small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Layer 2: Field Encryption -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-database me-2"></i>{{ __('Layer 2: Database Field Encryption') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">{{ __('Transparent encryption of sensitive database columns. Toggle categories below, then run the CLI to encrypt existing data.') }}</p>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="encryption_field_contact_details"
                                                           name="settings[encryption_field_contact_details]" value="true"
                                                           {{ ($settings['encryption_field_contact_details'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="encryption_field_contact_details">
                                                        <strong><i class="fas fa-address-card me-1 text-primary"></i>{{ __('Contact Details') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Email, address, telephone, fax, contact person (contact_information tables).') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="encryption_field_financial_data"
                                                           name="settings[encryption_field_financial_data]" value="true"
                                                           {{ ($settings['encryption_field_financial_data'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="encryption_field_financial_data">
                                                        <strong><i class="fas fa-coins me-1 text-warning"></i>{{ __('Financial Data') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Appraisal values in accession records.') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="encryption_field_donor_information"
                                                           name="settings[encryption_field_donor_information]" value="true"
                                                           {{ ($settings['encryption_field_donor_information'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="encryption_field_donor_information">
                                                        <strong><i class="fas fa-user-shield me-1 text-success"></i>{{ __('Donor Information') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Actor history (biographical/administrative history for donors).') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="encryption_field_personal_notes"
                                                           name="settings[encryption_field_personal_notes]" value="true"
                                                           {{ ($settings['encryption_field_personal_notes'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="encryption_field_personal_notes">
                                                        <strong><i class="fas fa-sticky-note me-1 text-info"></i>{{ __('Personal Notes') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Note content (internal staff notes on records).') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="encryption_field_access_restrictions"
                                                           name="settings[encryption_field_access_restrictions]" value="true"
                                                           {{ ($settings['encryption_field_access_restrictions'] ?? '') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="encryption_field_access_restrictions">
                                                        <strong><i class="fas fa-ban me-1 text-danger"></i>{{ __('Access Restrictions') }}</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('Rights notes (access restriction details in rights statements).') }}</div>
                                            </div>
                                        </div>

                                        <div class="alert alert-secondary mt-3 mb-0">
                                            <i class="fas fa-terminal me-2"></i>
                                            <strong>{{ __('CLI Commands') }}</strong>
                                            <br><code>php bin/atom encryption:encrypt-fields --category=contact_details</code> — {{ __('Encrypt a category') }}
                                            <br><code>php bin/atom encryption:encrypt-fields --category=contact_details --reverse</code> — {{ __('Decrypt a category') }}
                                            <br><code>php bin/atom encryption:encrypt-fields --list</code> — {{ __('Show category status') }}
                                            <br><code>php bin/atom encryption:status</code> — {{ __('Full encryption dashboard') }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Compliance Note -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-gavel me-2"></i>{{ __('Compliance') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2">{{ __('Encryption at rest satisfies requirements from:') }}</p>
                                        <ul class="mb-0">
                                            <li><strong>POPIA</strong> — {{ __('Protection of Personal Information Act (South Africa), Section 19') }}</li>
                                            <li><strong>GDPR</strong> — {{ __('General Data Protection Regulation (EU), Article 32') }}</li>
                                            <li><strong>CCPA</strong> — {{ __('California Consumer Privacy Act, reasonable security measures') }}</li>
                                            <li><strong>NARSSA</strong> — {{ __('National Archives and Record Service of South Africa') }}</li>
                                            <li><strong>PAIA</strong> — {{ __('Promotion of Access to Information Act, secure record keeping') }}</li>
                                        </ul>
                                    </div>
                                </div>
                            @break

                            @case('voice_ai')
                                <!-- Voice Commands -->
                                <fieldset class="mb-4">
                                    <legend><i class="fas fa-microphone me-2"></i>{{ __('Voice Commands') }}</legend>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="voice_enabled"
                                                       name="settings[voice_enabled]" value="true"
                                                       {{ ($settings['voice_enabled'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="voice_enabled">
                                                    <strong>{{ __('Enable Voice Commands') }}</strong>
                                                </label>
                                            </div>
                                            <div class="form-text">{{ __('Allow users to navigate and control the application using voice commands.') }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="voice_language">{{ __('Voice Language') }}</label>
                                            <select class="form-select" id="voice_language" name="settings[voice_language]">
                                                <option value="en-US" {{ ($settings['voice_language'] ?? 'en-US') === 'en-US' ? 'selected' : '' }}>English (US)</option>
                                                <option value="en-GB" {{ ($settings['voice_language'] ?? '') === 'en-GB' ? 'selected' : '' }}>English (UK)</option>
                                                <option value="af-ZA" {{ ($settings['voice_language'] ?? '') === 'af-ZA' ? 'selected' : '' }}>Afrikaans</option>
                                                <option value="zu-ZA" {{ ($settings['voice_language'] ?? '') === 'zu-ZA' ? 'selected' : '' }}>isiZulu</option>
                                                <option value="xh-ZA" {{ ($settings['voice_language'] ?? '') === 'xh-ZA' ? 'selected' : '' }}>isiXhosa</option>
                                                <option value="st-ZA" {{ ($settings['voice_language'] ?? '') === 'st-ZA' ? 'selected' : '' }}>Sesotho</option>
                                                <option value="fr-FR" {{ ($settings['voice_language'] ?? '') === 'fr-FR' ? 'selected' : '' }}>French</option>
                                                <option value="pt-PT" {{ ($settings['voice_language'] ?? '') === 'pt-PT' ? 'selected' : '' }}>Portuguese</option>
                                                <option value="es-ES" {{ ($settings['voice_language'] ?? '') === 'es-ES' ? 'selected' : '' }}>Spanish</option>
                                                <option value="de-DE" {{ ($settings['voice_language'] ?? '') === 'de-DE' ? 'selected' : '' }}>German</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <label class="form-label" for="voice_confidence_threshold">{{ __('Confidence Threshold') }}: <span id="voice_confidence_threshold_val">{{ $settings['voice_confidence_threshold'] ?? '0.4' }}</span></label>
                                            <input type="range" class="form-range" id="voice_confidence_threshold"
                                                   name="settings[voice_confidence_threshold]"
                                                   min="0.3" max="0.95" step="0.05"
                                                   value="{{ $settings['voice_confidence_threshold'] ?? '0.4' }}"
                                                   oninput="document.getElementById('voice_confidence_threshold_val').textContent=this.value">
                                            <div class="form-text">{{ __('Minimum confidence score for voice recognition (0.3 = lenient, 0.95 = strict).') }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="voice_speech_rate">{{ __('Speech Rate') }}: <span id="voice_speech_rate_val">{{ $settings['voice_speech_rate'] ?? '1.0' }}</span></label>
                                            <input type="range" class="form-range" id="voice_speech_rate"
                                                   name="settings[voice_speech_rate]"
                                                   min="0.5" max="2.0" step="0.1"
                                                   value="{{ $settings['voice_speech_rate'] ?? '1.0' }}"
                                                   oninput="document.getElementById('voice_speech_rate_val').textContent=this.value">
                                            <div class="form-text">{{ __('Text-to-speech playback rate (0.5 = slow, 2.0 = fast).') }}</div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="voice_continuous_listening"
                                                       name="settings[voice_continuous_listening]" value="true"
                                                       {{ ($settings['voice_continuous_listening'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="voice_continuous_listening">
                                                    <strong>{{ __('Continuous Listening') }}</strong>
                                                </label>
                                            </div>
                                            <div class="form-text">{{ __('Keep microphone active after each command (no need to re-activate).') }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="voice_show_floating_btn"
                                                       name="settings[voice_show_floating_btn]" value="true"
                                                       {{ ($settings['voice_show_floating_btn'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="voice_show_floating_btn">
                                                    <strong>{{ __('Show Floating Mic Button') }}</strong>
                                                </label>
                                            </div>
                                            <div class="form-text">{{ __('Display a floating microphone button on all pages for quick voice activation.') }}</div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="voice_hover_read_enabled"
                                                       name="settings[voice_hover_read_enabled]" value="true"
                                                       {{ ($settings['voice_hover_read_enabled'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="voice_hover_read_enabled">
                                                    <strong>{{ __('Mouseover Read-Aloud') }}</strong>
                                                </label>
                                            </div>
                                            <div class="form-text">{{ __('Read button and link text aloud when hovering with the mouse (when voice mode is active).') }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="voice_hover_read_delay">{{ __('Hover Read Delay') }}: <span id="voice_hover_read_delay_val">{{ $settings['voice_hover_read_delay'] ?? '400' }}</span>ms</label>
                                            <input type="range" class="form-range" id="voice_hover_read_delay"
                                                   name="settings[voice_hover_read_delay]"
                                                   min="100" max="1000" step="50"
                                                   value="{{ $settings['voice_hover_read_delay'] ?? '400' }}"
                                                   oninput="document.getElementById('voice_hover_read_delay_val').textContent=this.value">
                                            <div class="form-text">{{ __('Milliseconds to wait before reading (100 = instant, 1000 = slow). Lower values are more responsive.') }}</div>
                                        </div>
                                    </div>
                                </fieldset>

                                <!-- AI Image Description -->
                                <fieldset class="mb-4">
                                    <legend><i class="fas fa-brain me-2"></i>{{ __('AI Image Description') }}</legend>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="voice_llm_provider">{{ __('LLM Provider') }}</label>
                                            <select class="form-select" id="voice_llm_provider" name="settings[voice_llm_provider]">
                                                <option value="local" {{ ($settings['voice_llm_provider'] ?? '') === 'local' ? 'selected' : '' }}>{{ __('Local Only') }}</option>
                                                <option value="cloud" {{ ($settings['voice_llm_provider'] ?? '') === 'cloud' ? 'selected' : '' }}>{{ __('Cloud Only') }}</option>
                                                <option value="hybrid" {{ ($settings['voice_llm_provider'] ?? 'hybrid') === 'hybrid' ? 'selected' : '' }}>{{ __('Hybrid (Local + Cloud Fallback)') }}</option>
                                            </select>
                                            <div class="form-text">{{ __('Choose where AI image descriptions are processed.') }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="voice_daily_cloud_limit">{{ __('Daily Cloud Limit') }}</label>
                                            <input type="number" class="form-control" id="voice_daily_cloud_limit"
                                                   name="settings[voice_daily_cloud_limit]"
                                                   value="{{ e($settings['voice_daily_cloud_limit'] ?? '50') }}" min="0" max="10000">
                                            <div class="form-text">{{ __('Maximum cloud API calls per day (0 = unlimited).') }}</div>
                                        </div>
                                    </div>

                                    <hr>
                                    <h6 class="mb-3">{{ __('Local LLM Settings') }}</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label" for="voice_local_llm_url">{{ __('Local LLM URL') }}</label>
                                            <input type="text" class="form-control" id="voice_local_llm_url"
                                                   name="settings[voice_local_llm_url]"
                                                   value="{{ e($settings['voice_local_llm_url'] ?? 'http://localhost:11434') }}"
                                                   placeholder="http://localhost:11434">
                                            <div class="form-text">{{ __('Ollama or compatible API endpoint.') }}</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="voice_local_llm_model">{{ __('Local LLM Model') }}</label>
                                            <input type="text" class="form-control" id="voice_local_llm_model"
                                                   name="settings[voice_local_llm_model]"
                                                   value="{{ e($settings['voice_local_llm_model'] ?? 'llava:7b') }}"
                                                   placeholder="llava:7b">
                                            <div class="form-text">{{ __('Vision-capable model name (e.g. llava:7b, bakllava).') }}</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="voice_local_llm_timeout">{{ __('Timeout (seconds)') }}</label>
                                            <input type="number" class="form-control" id="voice_local_llm_timeout"
                                                   name="settings[voice_local_llm_timeout]"
                                                   value="{{ e($settings['voice_local_llm_timeout'] ?? '30') }}" min="5" max="300">
                                            <div class="form-text">{{ __('Request timeout for local LLM API calls.') }}</div>
                                        </div>
                                    </div>

                                    <hr>
                                    <h6 class="mb-3">{{ __('Cloud LLM Settings') }}</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label" for="voice_anthropic_api_key">{{ __('Anthropic API Key') }}</label>
                                            <input type="password" class="form-control" id="voice_anthropic_api_key"
                                                   name="settings[voice_anthropic_api_key]"
                                                   value="{{ e($settings['voice_anthropic_api_key'] ?? '') }}"
                                                   placeholder="sk-ant-...">
                                            <div class="form-text">{{ __('API key for Claude cloud vision. Stored encrypted.') }}</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="voice_cloud_model">{{ __('Cloud Model') }}</label>
                                            <input type="text" class="form-control" id="voice_cloud_model"
                                                   name="settings[voice_cloud_model]"
                                                   value="{{ e($settings['voice_cloud_model'] ?? 'claude-sonnet-4-20250514') }}"
                                                   placeholder="claude-sonnet-4-20250514">
                                            <div class="form-text">{{ __('Anthropic model ID for image descriptions.') }}</div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" id="voice_audit_ai_calls"
                                                       name="settings[voice_audit_ai_calls]" value="true"
                                                       {{ ($settings['voice_audit_ai_calls'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="voice_audit_ai_calls">
                                                    <strong>{{ __('Audit AI Calls') }}</strong>
                                                </label>
                                            </div>
                                            <div class="form-text">{{ __('Log all AI image description requests to the audit trail.') }}</div>
                                        </div>
                                    </div>
                                </fieldset>
                            @break

                        @endswitch

                        <!-- Submit Button -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> {{ __('Save Settings') }}
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script {!! $csp_nonce !!}>
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

<style {!! $csp_nonce !!}>
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

<script {!! $csp_nonce !!}>
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

<script {!! $csp_nonce !!}>
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
@endsection
