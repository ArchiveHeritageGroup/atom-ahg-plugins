@extends('layouts.page')

@section('title', __('Heritage Accounting Administration'))

@section('content')
@php
$regions = $regions;
$activeConfig = $activeConfig;
$activeRegion = $activeConfig ? $activeConfig->region_code : null;
$activeRegionData = null;
if ($activeRegion && $regions) {
    foreach ($regions as $r) {
        if ($r->region_code === $activeRegion) {
            $activeRegionData = $r;
            break;
        }
    }
}
$installedCount = 0;
if ($regions) {
    foreach ($regions as $r) {
        if ($r->is_installed) {
            $installedCount++;
        }
    }
}
@endphp

<h1><i class="fas fa-landmark me-2"></i>{{ __('Heritage Accounting Administration') }}</h1>

<!-- Active Region Banner -->
@if($activeRegionData)
  <div class="alert alert-primary mb-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-globe me-3 fs-4"></i>
      <div>
        <strong>{{ __('Active Region:') }}</strong>
        {{ $activeRegionData->region_name }}
        <span class="badge bg-white text-primary ms-2">{{ $activeConfig->currency ?? $activeRegionData->default_currency }}</span>
      </div>
      <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regions']) }}" class="btn btn-sm btn-outline-light ms-auto">
        <i class="fas fa-cog me-1"></i>{{ __('Change') }}
      </a>
    </div>
  </div>
@else
  <div class="alert alert-warning mb-4">
    <i class="fas fa-exclamation-triangle me-2"></i>
    {{ __('No active region set.') }}
    <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regions']) }}" class="alert-link">
      {{ __('Configure regions') }}
    </a>
  </div>
@endif

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary">{{ $stats['total_assets'] ?? 0 }}</h3>
                <small class="text-muted">{{ __('Total Assets') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-success">{{ $installedCount }}</h3>
                <small class="text-muted">{{ __('Regions Installed') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-info">{{ count($standards ?? []) }}</h3>
                <small class="text-muted">{{ __('Standards') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-secondary">{{ count($regions ?? []) }}</h3>
                <small class="text-muted">{{ __('Available Regions') }}</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Assets by Standard') }}</h5>
            </div>
            <div class="card-body">
                @if(!empty($stats['by_standard']) && count($stats['by_standard']) > 0)
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Standard') }}</th>
                                <th>{{ __('Code') }}</th>
                                <th class="text-end">{{ __('Assets') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['by_standard'] as $row)
                                <tr>
                                    <td>{!! $row->name !!}</td>
                                    <td><code>{!! $row->code !!}</code></td>
                                    <td class="text-end">{{ number_format($row->count) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted mb-0">{{ __('No assets recorded yet.') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-globe me-2"></i>{{ __('Regions') }}</h5>
            </div>
            <div class="card-body">
                <p class="small">{{ __('Install and configure regional accounting standards (IPSAS, GRAP, FRS, etc.)') }}</p>
                <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regions']) }}" class="btn btn-success">
                    <i class="fas fa-globe me-1"></i>{{ __('Manage Regions') }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>{{ __('Standards') }}</h5>
            </div>
            <div class="card-body">
                <p class="small">{{ __('View and edit installed accounting standards.') }}</p>
                <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'standardList']) }}" class="btn btn-primary">
                    <i class="fas fa-list me-1"></i>{{ __('Manage Standards') }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-check-square me-2"></i>{{ __('Compliance Rules') }}</h5>
            </div>
            <div class="card-body">
                <p class="small">{{ __('Configure compliance validation rules for each standard.') }}</p>
                <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'ruleList']) }}" class="btn btn-info">
                    <i class="fas fa-calculator me-1"></i>{{ __('Manage Rules') }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>{{ __('Reports') }}</h5>
            </div>
            <div class="card-body">
                <p class="small">{{ __('Generate heritage asset accounting reports.') }}</p>
                <a href="{{ url_for(['module' => 'heritageReport', 'action' => 'index']) }}" class="btn btn-secondary">
                    <i class="fas fa-file-alt me-1"></i>{{ __('View Reports') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
