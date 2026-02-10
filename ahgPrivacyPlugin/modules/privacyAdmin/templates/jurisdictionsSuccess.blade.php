@extends('layouts.page')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fas fa-globe me-2"></i>{{ __('Privacy Jurisdictions') }}</h1>
            <p class="text-muted mb-0">{{ __('Manage privacy compliance frameworks by region') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'index']) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
            </a>
        </div>
    </div>

    @if($sf_user->hasFlash('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ $sf_user->getFlash('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($sf_user->hasFlash('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>{{ $sf_user->getFlash('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($sf_user->hasFlash('notice'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle me-2"></i>{{ $sf_user->getFlash('notice') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Active Jurisdiction Banner -->
    @if($activeJurisdiction)
    <div class="alert alert-primary mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-2x me-3"></i>
            <div>
                <h5 class="mb-1">{{ __('Active Jurisdiction') }}: {{ $activeJurisdiction->name }}</h5>
                <p class="mb-0">{{ $activeJurisdiction->full_name }} ({{ $activeJurisdiction->country }})</p>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-warning mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ __('No active jurisdiction configured. Install and activate a jurisdiction to enable compliance tracking.') }}
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-light">
                <div class="card-body">
                    <h3 class="mb-0">{{ count($jurisdictions) }}</h3>
                    <small class="text-muted">{{ __('Available') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ count(array_filter($jurisdictions, function($j) { return $j->is_installed; })) }}</h3>
                    <small>{{ __('Installed') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ count($byRegion) }}</h3>
                    <small>{{ __('Regions') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $activeJurisdiction ? 1 : 0 }}</h3>
                    <small>{{ __('Active') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Jurisdictions by Region -->
    @foreach($byRegion as $region => $regionJurisdictions)
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">
                @php
$regionIcons = [
                    'Africa' => 'fas fa-globe-africa',
                    'Europe' => 'fas fa-globe-europe',
                    'North America' => 'fas fa-globe-americas',
                    'South America' => 'fas fa-globe-americas',
                    'Asia' => 'fas fa-globe-asia',
                    'Oceania' => 'fas fa-globe',
                    'International' => 'fas fa-globe',
                ];
@endphp
                <i class="{{ $regionIcons[$region] ?? 'fas fa-globe' }} me-2"></i>
                {{ $region }}
                <span class="badge bg-secondary ms-2">{{ count($regionJurisdictions) }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;"></th>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Country') }}</th>
                            <th class="text-center">{{ __('DSAR Days') }}</th>
                            <th class="text-center">{{ __('Breach Hours') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($regionJurisdictions as $j)
                        <tr{{ ($activeJurisdiction && $activeJurisdiction->code === $j->code) ? ' class="table-primary"' : '' }}>
                            <td class="text-center">
                                @if($j->icon)
                                <span style="font-size: 1.5rem;">{{ $j->icon }}</span>
                                @endif
                            </td>
                            <td>
                                <code>{{ $j->code }}</code>
                            </td>
                            <td>
                                <strong>{{ $j->name }}</strong>
                                <br><small class="text-muted">{{ $j->full_name }}</small>
                            </td>
                            <td>{{ $j->country }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary">{{ $j->dsar_days }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger">{{ $j->breach_hours }}</span>
                            </td>
                            <td class="text-center">
                                @if($activeJurisdiction && $activeJurisdiction->code === $j->code)
                                <span class="badge bg-success"><i class="fas fa-star me-1"></i>{{ __('ACTIVE') }}</span>
                                @elseif($j->is_installed)
                                <span class="badge bg-info">{{ __('Installed') }}</span>
                                @else
                                <span class="badge bg-secondary">{{ __('Not Installed') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionInfo', 'code' => $j->code]) }}"
                                       class="btn btn-outline-secondary" title="{{ __('Details') }}">
                                        <i class="fas fa-info-circle"></i>
                                    </a>
                                    @if(!$j->is_installed)
                                    <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionInstall', 'code' => $j->code]) }}"
                                       class="btn btn-outline-success" title="{{ __('Install') }}"
                                       onclick="return confirm('{{ __('Install jurisdiction: ') }}{{ addslashes($j->name) }}?');">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    @else
                                    @if(!$activeJurisdiction || $activeJurisdiction->code !== $j->code)
                                    <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionSetActive', 'code' => $j->code]) }}"
                                       class="btn btn-outline-primary" title="{{ __('Set as Active') }}"
                                       onclick="return confirm('{{ __('Set as active jurisdiction: ') }}{{ addslashes($j->name) }}?');">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    @endif
                                    <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionUninstall', 'code' => $j->code]) }}"
                                       class="btn btn-outline-danger" title="{{ __('Uninstall') }}"
                                       onclick="return confirm('{{ __('Uninstall jurisdiction: ') }}{{ addslashes($j->name) }}? This will remove all jurisdiction-specific rules.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Help Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>{{ __('About Regional Jurisdictions') }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6><i class="fas fa-download me-2 text-success"></i>{{ __('Installing') }}</h6>
                    <p class="small text-muted">{{ __('Installing a jurisdiction loads its lawful bases, special categories, request types, compliance rules, and retention schedules.') }}</p>
                </div>
                <div class="col-md-4">
                    <h6><i class="fas fa-check me-2 text-primary"></i>{{ __('Activating') }}</h6>
                    <p class="small text-muted">{{ __('The active jurisdiction determines which compliance rules apply globally. Only one jurisdiction can be active at a time.') }}</p>
                </div>
                <div class="col-md-4">
                    <h6><i class="fas fa-terminal me-2 text-dark"></i>{{ __('CLI Management') }}</h6>
                    <p class="small text-muted">{{ __('Use the command line for advanced management:') }}</p>
                    <code class="small">php symfony privacy:jurisdiction --help</code>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
