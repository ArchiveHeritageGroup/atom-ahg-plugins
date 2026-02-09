@extends('layouts.page')

@section('content')
@php
// Check if DAM features are explicitly enabled
$damEnabled = false;
try {
    $result = \Illuminate\Database\Capsule\Manager::table('ahg_settings')
        ->where('setting_key', 'dam_tools_enabled')
        ->where('setting_group', 'general')
        ->first();
    $damEnabled = $result && $result->setting_value === '1';
} catch (\Exception $e) {
    $damEnabled = false;
}
@endphp

<div class="d-flex justify-content-between align-items-center mb-2">
    <h1 class="mb-0"><i class="fas fa-cogs"></i> {{ __('AHG Plugin Settings') }}</h1>
    <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'global']) }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Admin Settings') }}
    </a>
</div>
<p class="text-muted mb-4">{{ __('Configure AHG theme and plugin settings') }}</p>

<div class="row">
    @foreach ($sections as $key => $section)
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center py-4">
                <div class="mb-3">
                    <i class="fas {{ $section['icon'] }} fa-3x text-primary"></i>
                </div>
                <h5 class="card-title">{{ __($section['label']) }}</h5>
                <p class="card-text text-muted small">{{ __($section['description']) }}</p>
            </div>
            <div class="card-footer bg-white border-0 text-center pb-4">
                <a href="/index.php/{{ $section['url'] }}" class="btn btn-primary">
                    <i class="fas fa-cog"></i> {{ __('Configure') }}
                </a>
            </div>
        </div>
    </div>
    @endforeach

    @if ($damEnabled)
    <!-- DAM Tools Card -->
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-info">
            <div class="card-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-photo-video fa-3x text-info"></i>
                </div>
                <h5 class="card-title">{{ __('Digital Asset Management') }}</h5>
                <p class="card-text text-muted small">{{ __('PDF merge, digital objects, 3D viewer, and media tools') }}</p>
            </div>
            <div class="card-footer bg-white border-0 text-center pb-4">
                <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'damTools']) }}" class="btn btn-info">
                    <i class="fas fa-tools"></i> {{ __('Open Tools') }}
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Preservation & Backup Card -->
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-success">
            <div class="card-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-cloud-upload-alt fa-3x text-success"></i>
                </div>
                <h5 class="card-title">{{ __('Preservation & Backup') }}</h5>
                <p class="card-text text-muted small">{{ __('Configure backup replication targets, verify integrity, and manage preservation') }}</p>
            </div>
            <div class="card-footer bg-white border-0 text-center pb-4">
                <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'preservation']) }}" class="btn btn-success">
                    <i class="fas fa-cog"></i> {{ __('Configure') }}
                </a>
            </div>
        </div>
    </div>
</div>

@if ($damEnabled)
<!-- Quick Access - TIFF to PDF Merge -->
<div class="card mt-4 border-primary">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>{{ __('Quick Access: TIFF to PDF Merge') }}</h5>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <p class="mb-0">
                    <strong>{{ __('Create multi-page PDF documents from images') }}</strong><br>
                    <small class="text-muted">{{ __('Upload multiple TIFF, JPEG, or PNG files and merge them into a single PDF/A archival document. Jobs run in the background and can be attached directly to archival records.') }}</small>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <a href="{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'index']) }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-file-pdf me-1"></i> {{ __('Create PDF') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
