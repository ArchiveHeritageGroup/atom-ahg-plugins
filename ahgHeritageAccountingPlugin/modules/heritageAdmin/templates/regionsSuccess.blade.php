@extends('layouts.page')

@section('title', __('Heritage Accounting Regions'))

@section('content')
@php
$activeRegion = $activeConfig ? $activeConfig->region_code : null;
@endphp

<h1><i class="fas fa-globe me-2"></i>{{ __('Heritage Accounting Regions') }}</h1>

<p class="text-muted mb-4">
  <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'index']) }}">
    <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Heritage Admin') }}
  </a>
</p>

@if($sf_user->hasFlash('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    {!! $sf_user->getFlash('success') !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if($sf_user->hasFlash('error'))
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-triangle me-2"></i>
    {!! $sf_user->getFlash('error') !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if($sf_user->hasFlash('notice'))
  <div class="alert alert-info alert-dismissible fade show">
    <i class="fas fa-info-circle me-2"></i>
    {!! $sf_user->getFlash('notice') !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<!-- Active Region Banner -->
@if($activeRegion)
  @php
    $activeRegionData = null;
    foreach ($regions as $r) {
        if ($r->region_code === $activeRegion) {
            $activeRegionData = $r;
            break;
        }
    }
  @endphp
  @if($activeRegionData)
    <div class="alert alert-primary mb-4">
      <div class="d-flex align-items-center">
        <i class="fas fa-check-circle me-3 fs-4"></i>
        <div>
          <strong>{{ __('Active Region:') }}</strong>
          {{ $activeRegionData->region_name }}
          <span class="badge bg-white text-primary ms-2">{{ $activeConfig->currency ?? $activeRegionData->default_currency }}</span>
        </div>
      </div>
    </div>
  @endif
@else
  <div class="alert alert-warning mb-4">
    <i class="fas fa-exclamation-triangle me-2"></i>
    {{ __('No active region set. Install and activate a region to enable compliance checking.') }}
  </div>
@endif

<!-- Explanation Card -->
<div class="card mb-4">
  <div class="card-body">
    <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>{{ __('About Regional Standards') }}</h5>
    <p class="card-text text-muted mb-0">
      {{ __('Each region implements specific accounting standards for heritage assets. Install only the regions you need - this keeps your database lean and compliance rules relevant to your jurisdiction.') }}
    </p>
  </div>
</div>

<!-- Regions Grid -->
<div class="row g-4">
  @foreach($regions as $region)
    @php
      $isInstalled = $region->is_installed;
      $isActive = $region->region_code === $activeRegion;
      $rulesCount = $rulesByRegion[$region->region_code] ?? 0;
      $countries = is_array($region->countries) ? $region->countries : [];
    @endphp
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 {{ $isActive ? 'border-primary border-2' : ($isInstalled ? 'border-success' : '') }}">
        <div class="card-header {{ $isActive ? 'bg-primary text-white' : ($isInstalled ? 'bg-success bg-opacity-10' : 'bg-light') }}">
          <div class="d-flex justify-content-between align-items-center">
            <strong>{{ $region->region_name }}</strong>
            @if($isActive)
              <span class="badge bg-white text-primary">ACTIVE</span>
            @elseif($isInstalled)
              <span class="badge bg-success">Installed</span>
            @else
              <span class="badge bg-secondary">Not Installed</span>
            @endif
          </div>
        </div>
        <div class="card-body">
          <p class="card-text small text-muted mb-2">
            <i class="fas fa-map-marker-alt me-1"></i>
            {{ implode(', ', array_slice($countries, 0, 3)) }}
            @if(count($countries) > 3)
              <span class="text-muted">+{{ count($countries) - 3 }} more</span>
            @endif
          </p>

          <div class="mb-3">
            <span class="badge bg-light text-dark me-1">
              <i class="fas fa-money-bill me-1"></i>{{ $region->default_currency }}
            </span>
            @if($isInstalled)
              <span class="badge bg-info text-white">
                <i class="fas fa-check-square me-1"></i>{{ $rulesCount }} rules
              </span>
            @endif
          </div>

          <p class="card-text small">
            <strong>{{ __('Regulatory Body:') }}</strong><br>
            {{ $region->regulatory_body }}
          </p>

          @if($isInstalled && $region->installed_at)
            <p class="card-text small text-muted">
              <i class="fas fa-calendar-check me-1"></i>
              {{ __('Installed:') }} {{ date('Y-m-d', strtotime($region->installed_at)) }}
            </p>
          @endif
        </div>
        <div class="card-footer bg-transparent">
          <div class="btn-group w-100" role="group">
            @if(!$isInstalled)
              <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regionInstall', 'region' => $region->region_code]) }}"
                 class="btn btn-success btn-sm"
                 onclick="return confirm('Install {{ $region->region_name }}? This will add the accounting standard and compliance rules.');">
                <i class="fas fa-download me-1"></i>{{ __('Install') }}
              </a>
            @else
              <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regionInfo', 'region' => $region->region_code]) }}"
                 class="btn btn-outline-primary btn-sm">
                <i class="fas fa-info-circle me-1"></i>{{ __('Details') }}
              </a>
              @if(!$isActive)
                <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regionSetActive', 'region' => $region->region_code]) }}"
                   class="btn btn-primary btn-sm"
                   onclick="return confirm('Set {{ $region->region_name }} as the active region?');">
                  <i class="fas fa-check-circle me-1"></i>{{ __('Activate') }}
                </a>
                <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regionUninstall', 'region' => $region->region_code]) }}"
                   class="btn btn-outline-danger btn-sm"
                   onclick="return confirm('Uninstall {{ $region->region_name }}? This will remove the standard and compliance rules.');">
                  <i class="fas fa-trash me-1"></i>
                </a>
              @endif
            @endif
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>

<!-- Help Section -->
<div class="card mt-4">
  <div class="card-header">
    <i class="fas fa-terminal me-2"></i>{{ __('CLI Commands') }}
  </div>
  <div class="card-body">
    <p class="text-muted small mb-2">{{ __('You can also manage regions via command line:') }}</p>
    <pre class="bg-dark text-light p-3 rounded small mb-0"><code>php symfony heritage:region                        # List all regions
php symfony heritage:region --install=africa_ipsas # Install a region
php symfony heritage:region --set-active=uk_frs    # Set active region
php symfony heritage:region --info=south_africa_grap # View details
php symfony heritage:region --uninstall=uk_frs     # Uninstall a region</code></pre>
  </div>
</div>
@endsection
