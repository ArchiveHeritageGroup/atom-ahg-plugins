@extends('layouts.page')

@section('title', __('GRAP 103 Compliance Check'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1"><i class="fas fa-clipboard-check me-2"></i>{{ __('Compliance Check') }}</h1>
                <p class="text-muted mb-0">{{ $asset->object_identifier }}</p>
            </div>
            <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Asset') }}
            </a>
        </div>
    </div>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h1 class="display-4 {{ $complianceResult['summary']['status'] == 'compliant' ? 'text-success' : ($complianceResult['summary']['status'] == 'partially_compliant' ? 'text-warning' : 'text-danger') }}">
                        {{ $complianceResult['summary']['score'] }}%
                    </h1>
                    <p class="mb-0">{{ __('Compliance Score') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h2>{{ $complianceResult['summary']['passed'] }}</h2>
                    <p class="mb-0">{{ __('Passed') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h2>{{ $complianceResult['summary']['warnings'] }}</h2>
                    <p class="mb-0">{{ __('Warnings') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h2>{{ $complianceResult['summary']['failed'] }}</h2>
                    <p class="mb-0">{{ __('Failed') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Results -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>{{ __('Compliance Checklist') }}</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 100px;">{{ __('Code') }}</th>
                        <th>{{ __('Check') }}</th>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th class="text-center">{{ __('Status') }}</th>
                        <th>{{ __('Message') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($complianceResult['checks'] as $check)
                        <tr>
                            <td><code>{!! $check['code'] !!}</code></td>
                            <td><strong>{!! $check['title'] !!}</strong></td>
                            <td><small class="text-muted">{!! $check['reference'] !!}</small></td>
                            <td>
                                @php
                                $catColors = ['recognition' => 'primary', 'measurement' => 'info', 'disclosure' => 'secondary', 'documentation' => 'dark'];
                                $catColor = $catColors[$check['category']] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $catColor }}">{{ ucfirst($check['category']) }}</span>
                            </td>
                            <td class="text-center">
                                @if($check['status'] == 'passed')
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Passed</span>
                                @elseif($check['status'] == 'warning')
                                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> Warning</span>
                                @else
                                    <span class="badge bg-danger"><i class="fas fa-times"></i> Failed</span>
                                @endif
                            </td>
                            <td><small>{!! $check['message'] !!}</small></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <small class="text-muted">
                <i class="fas fa-clock me-1"></i>{{ __('Checked at: %1%', ['%1%' => $complianceResult['checked_at']]) }}
            </small>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'edit', 'id' => $asset->id]) }}" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>{{ __('Edit Asset to Fix Issues') }}
        </a>
        <a href="{{ url_for(['module' => 'grapCompliance', 'action' => 'dashboard']) }}" class="btn btn-outline-secondary ms-2">
            <i class="fas fa-arrow-left me-1"></i>{{ __('Back to GRAP Dashboard') }}
        </a>
    </div>
</div>
@endsection
