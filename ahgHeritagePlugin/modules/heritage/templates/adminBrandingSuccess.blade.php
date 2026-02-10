@extends('layouts.page')

@php
$branding = (array) $branding;
@endphp

@section('title')
<h1 class="h3">
    <i class="fas fa-palette me-2"></i>Branding Configuration
</h1>
@endsection

@section('sidebar')
@include('heritage/adminSidebar', ['active' => 'branding'])
@endsection

@section('content')
<form action="{{ url_for(['module' => 'heritage', 'action' => 'adminBranding']) }}" method="post" enctype="multipart/form-data">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0">Colors</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="primary_color" class="form-label">Primary Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="primary_color_picker"
                               value="{{ $branding['primary_color'] ?? '#0d6efd' }}"
                               onchange="document.getElementById('primary_color').value = this.value;">
                        <input type="text" class="form-control" id="primary_color" name="primary_color"
                               value="{{ $branding['primary_color'] ?? '#0d6efd' }}" pattern="#[0-9A-Fa-f]{6}">
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="secondary_color" class="form-label">Secondary Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="secondary_color_picker"
                               value="{{ $branding['secondary_color'] ?? '#6c757d' }}"
                               onchange="document.getElementById('secondary_color').value = this.value;">
                        <input type="text" class="form-control" id="secondary_color" name="secondary_color"
                               value="{{ $branding['secondary_color'] ?? '' }}" pattern="#[0-9A-Fa-f]{6}">
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="accent_color" class="form-label">Accent Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="accent_color_picker"
                               value="{{ $branding['accent_color'] ?? '#198754' }}"
                               onchange="document.getElementById('accent_color').value = this.value;">
                        <input type="text" class="form-control" id="accent_color" name="accent_color"
                               value="{{ $branding['accent_color'] ?? '' }}" pattern="#[0-9A-Fa-f]{6}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0">Logos</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="logo_path" class="form-label">Logo URL</label>
                    <input type="text" class="form-control" id="logo_path" name="logo_path"
                           value="{{ $branding['logo_path'] ?? '' }}"
                           placeholder="/uploads/logo.png">
                    <div class="form-text">Path to main logo image.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="favicon_path" class="form-label">Favicon URL</label>
                    <input type="text" class="form-control" id="favicon_path" name="favicon_path"
                           value="{{ $branding['favicon_path'] ?? '' }}"
                           placeholder="/uploads/favicon.ico">
                    <div class="form-text">Path to favicon image.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0">Text</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="banner_text" class="form-label">Banner Text</label>
                <input type="text" class="form-control" id="banner_text" name="banner_text"
                       value="{{ $branding['banner_text'] ?? '' }}"
                       placeholder="Optional announcement banner">
            </div>
            <div class="mb-3">
                <label for="footer_text" class="form-label">Footer Text</label>
                <textarea class="form-control" id="footer_text" name="footer_text" rows="2">{{ $branding['footer_text'] ?? '' }}</textarea>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0">Social Links</h5>
        </div>
        <div class="card-body">
            @php $socialLinks = $branding['social_links'] ?? []; @endphp
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="social_facebook" class="form-label"><i class="fab fa-facebook me-2"></i>Facebook</label>
                    <input type="url" class="form-control" id="social_facebook" name="social_facebook"
                           value="{{ $socialLinks['facebook'] ?? '' }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="social_twitter" class="form-label"><i class="fab fa-twitter me-2"></i>Twitter/X</label>
                    <input type="url" class="form-control" id="social_twitter" name="social_twitter"
                           value="{{ $socialLinks['twitter'] ?? '' }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="social_instagram" class="form-label"><i class="fab fa-instagram me-2"></i>Instagram</label>
                    <input type="url" class="form-control" id="social_instagram" name="social_instagram"
                           value="{{ $socialLinks['instagram'] ?? '' }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="social_linkedin" class="form-label"><i class="fas fa-linkedin me-2"></i>LinkedIn</label>
                    <input type="url" class="form-control" id="social_linkedin" name="social_linkedin"
                           value="{{ $socialLinks['linkedin'] ?? '' }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0">Custom CSS</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <textarea class="form-control font-monospace" id="custom_css" name="custom_css" rows="6"
                          placeholder="/* Add custom CSS styles here */">{{ $branding['custom_css'] ?? '' }}</textarea>
                <div class="form-text">Custom CSS to apply across the site.</div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ url_for(['module' => 'heritage', 'action' => 'adminDashboard']) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-check me-2"></i>Save Branding
        </button>
    </div>
</form>
@endsection
