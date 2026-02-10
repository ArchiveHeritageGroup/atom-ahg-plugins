@extends('layouts.page')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-0">
                @if($jurisdiction->icon)
                <span class="me-2">{{ $jurisdiction->icon }}</span>
                @endif
                {{ $jurisdiction->name }}
            </h1>
            <p class="text-muted mb-0">{{ $jurisdiction->full_name }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictions']) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Jurisdictions') }}
            </a>
            @if(!$jurisdiction->is_installed)
            <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionInstall', 'code' => $jurisdiction->code]) }}"
               class="btn btn-success"
               onclick="return confirm('{{ __('Install this jurisdiction?') }}');">
                <i class="fas fa-download me-1"></i>{{ __('Install') }}
            </a>
            @elseif(!$activeJurisdiction || $activeJurisdiction->code !== $jurisdiction->code)
            <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionSetActive', 'code' => $jurisdiction->code]) }}"
               class="btn btn-primary"
               onclick="return confirm('{{ __('Set as active jurisdiction?') }}');">
                <i class="fas fa-check me-1"></i>{{ __('Set as Active') }}
            </a>
            @else
            <span class="btn btn-success disabled">
                <i class="fas fa-star me-1"></i>{{ __('Currently Active') }}
            </span>
            @endif
        </div>
    </div>

    @if($sf_user->hasFlash('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ $sf_user->getFlash('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <!-- Main Info Column -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('Jurisdiction Details') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted">{{ __('Code') }}</td>
                            <td><code>{{ $jurisdiction->code }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Country') }}</td>
                            <td>{{ $jurisdiction->country }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Region') }}</td>
                            <td>{{ $jurisdiction->region }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Effective Date') }}</td>
                            <td>{{ $jurisdiction->effective_date ? date('d M Y', strtotime($jurisdiction->effective_date)) : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Currency') }}</td>
                            <td>{{ $jurisdiction->default_currency }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>{{ __('Response Deadlines') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="display-4 text-primary">{{ $jurisdiction->dsar_days }}</div>
                            <small class="text-muted">{{ __('DSAR Response Days') }}</small>
                        </div>
                        <div class="col-6">
                            <div class="display-4 text-danger">{{ $jurisdiction->breach_hours }}</div>
                            <small class="text-muted">{{ __('Breach Notification Hours') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-landmark me-2"></i>{{ __('Regulator') }}</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>{{ $jurisdiction->regulator }}</strong></p>
                    @if($jurisdiction->regulator_url)
                    <a href="{{ $jurisdiction->regulator_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt me-1"></i>{{ __('Visit Website') }}
                    </a>
                    @endif
                </div>
            </div>

            @if($jurisdiction->is_installed)
            <div class="card mt-3 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>{{ __('Installation Status') }}</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <i class="fas fa-calendar me-2 text-muted"></i>
                        {{ __('Installed') }}: {{ date('d M Y H:i', strtotime($jurisdiction->installed_at)) }}
                    </p>
                    @if(isset($stats))
                    <hr>
                    <div class="row text-center">
                        <div class="col-6 mb-2">
                            <strong class="text-primary">{{ $stats['lawful_bases'] ?? 0 }}</strong><br>
                            <small class="text-muted">{{ __('Lawful Bases') }}</small>
                        </div>
                        <div class="col-6 mb-2">
                            <strong class="text-info">{{ $stats['special_categories'] ?? 0 }}</strong><br>
                            <small class="text-muted">{{ __('Special Categories') }}</small>
                        </div>
                        <div class="col-6 mb-2">
                            <strong class="text-success">{{ $stats['request_types'] ?? 0 }}</strong><br>
                            <small class="text-muted">{{ __('Request Types') }}</small>
                        </div>
                        <div class="col-6 mb-2">
                            <strong class="text-warning">{{ $stats['compliance_rules'] ?? 0 }}</strong><br>
                            <small class="text-muted">{{ __('Compliance Rules') }}</small>
                        </div>
                    </div>
                    @if(($stats['dsars'] ?? 0) > 0 || ($stats['breaches'] ?? 0) > 0)
                    <hr>
                    <p class="mb-1 small text-muted">{{ __('Usage') }}:</p>
                    <span class="badge bg-primary me-1">{{ $stats['dsars'] ?? 0 }} DSARs</span>
                    <span class="badge bg-danger">{{ $stats['breaches'] ?? 0 }} Breaches</span>
                    @endif
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Components Column -->
        <div class="col-lg-8">
            @if(!$jurisdiction->is_installed)
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                {{ __('This jurisdiction is not installed. Install it to see lawful bases, special categories, request types, and compliance rules.') }}
            </div>
            @else

            <!-- Lawful Bases -->
            @if(isset($lawfulBases) && count($lawfulBases) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>{{ __('Lawful Bases') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Legal Reference') }}</th>
                                    <th class="text-center">{{ __('Consent') }}</th>
                                    <th class="text-center">{{ __('LIA') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lawfulBases as $lb)
                                <tr>
                                    <td><code>{{ $lb->code }}</code></td>
                                    <td>
                                        <strong>{{ $lb->name }}</strong>
                                        @if($lb->description)
                                        <br><small class="text-muted">{{ truncate_text($lb->description, 100) }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ $lb->legal_reference ?? '-' }}</small></td>
                                    <td class="text-center">
                                        {!! $lb->requires_consent ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-minus text-muted"></i>' !!}
                                    </td>
                                    <td class="text-center">
                                        {!! $lb->requires_lia ? '<i class="fas fa-check text-warning"></i>' : '<i class="fas fa-minus text-muted"></i>' !!}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Special Categories -->
            @if(isset($specialCategories) && count($specialCategories) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>{{ __('Special Categories of Data') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Legal Reference') }}</th>
                                    <th class="text-center">{{ __('Explicit Consent') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($specialCategories as $sc)
                                <tr>
                                    <td><code>{{ $sc->code }}</code></td>
                                    <td>
                                        <strong>{{ $sc->name }}</strong>
                                        @if($sc->description)
                                        <br><small class="text-muted">{{ $sc->description }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ $sc->legal_reference ?? '-' }}</small></td>
                                    <td class="text-center">
                                        {!! $sc->requires_explicit_consent ? '<i class="fas fa-exclamation-triangle text-danger"></i>' : '<i class="fas fa-minus text-muted"></i>' !!}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Request Types -->
            @if(isset($requestTypes) && count($requestTypes) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>{{ __('Data Subject Request Types') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Request Type') }}</th>
                                    <th>{{ __('Legal Reference') }}</th>
                                    <th class="text-center">{{ __('Response Days') }}</th>
                                    <th class="text-center">{{ __('Fee Allowed') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requestTypes as $rt)
                                <tr>
                                    <td><code>{{ $rt->code }}</code></td>
                                    <td>
                                        <strong>{{ $rt->name }}</strong>
                                        @if($rt->description)
                                        <br><small class="text-muted">{{ $rt->description }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ $rt->legal_reference ?? '-' }}</small></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $rt->response_days ?? $jurisdiction->dsar_days }}</span>
                                    </td>
                                    <td class="text-center">
                                        {!! $rt->fee_allowed ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>' !!}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Compliance Rules -->
            @if(isset($complianceRules) && count($complianceRules) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>{{ __('Compliance Rules') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Rule') }}</th>
                                    <th>{{ __('Legal Ref') }}</th>
                                    <th class="text-center">{{ __('Severity') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
$severityClasses = [
                                    'error' => 'danger',
                                    'warning' => 'warning',
                                    'info' => 'info',
                                ];
@endphp
                                @foreach($complianceRules as $rule)
                                <tr>
                                    <td><code class="small">{{ $rule->code }}</code></td>
                                    <td><span class="badge bg-secondary">{{ $rule->category }}</span></td>
                                    <td>
                                        <strong>{{ $rule->name }}</strong>
                                        @if($rule->description)
                                        <br><small class="text-muted">{{ truncate_text($rule->description, 80) }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ $rule->legal_reference ?? '-' }}</small></td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $severityClasses[$rule->severity] ?? 'secondary' }}">
                                            {{ $rule->severity }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            @endif
        </div>
    </div>
</div>
@endsection
