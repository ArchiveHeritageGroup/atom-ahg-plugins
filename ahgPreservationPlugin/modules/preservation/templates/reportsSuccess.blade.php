@extends('layouts.page')

@section('title')
<h1><i class="bi bi-graph-up text-primary me-2"></i>{{ __('Preservation Reports') }}</h1>
@endsection

@section('content')

<div class="d-flex justify-content-end mb-4">
    <a href="{{ url_for(['module' => 'preservation', 'action' => 'index']) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Dashboard') }}
    </a>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary">{{ $stats['checksum_coverage'] }}%</h3>
                <p class="mb-0">{{ __('Checksum Coverage') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="{{ $stats['fixity_failures_30d'] > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $stats['fixity_failures_30d'] }}
                </h3>
                <p class="mb-0">{{ __('Fixity Failures (30d)') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="{{ $stats['formats_at_risk'] > 0 ? 'text-warning' : 'text-success' }}">
                    {{ $stats['formats_at_risk'] }}
                </h3>
                <p class="mb-0">{{ __('At-Risk Formats') }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Objects Without Checksums -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ __('Objects Without Checksums') }}
        <span class="badge bg-dark float-end">{{ count($objectsWithoutChecksums) }}</span>
    </div>
    @if(empty($objectsWithoutChecksums))
    <div class="card-body text-center text-muted">
        <i class="bi bi-check-circle fs-1 text-success"></i>
        <p class="mt-2">{{ __('All objects have checksums') }}</p>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('Filename') }}</th>
                    <th>{{ __('Size') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($objectsWithoutChecksums as $obj)
                <tr>
                    <td>{{ $obj->id }}</td>
                    <td>{{ $obj->name ?? 'Unknown' }}</td>
                    <td>{{ number_format($obj->byte_size ?? 0) }} bytes</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<!-- Stale Verification -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <i class="bi bi-clock me-2"></i>{{ __('Stale Verification (>30 days)') }}
        <span class="badge bg-light text-dark float-end">{{ count($staleVerification) }}</span>
    </div>
    @if(empty($staleVerification))
    <div class="card-body text-center text-muted">
        <i class="bi bi-check-circle fs-1 text-success"></i>
        <p class="mt-2">{{ __('All verifications are current') }}</p>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('Filename') }}</th>
                    <th>{{ __('Algorithm') }}</th>
                    <th>{{ __('Last Verified') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staleVerification as $obj)
                <tr>
                    <td>{{ $obj->id }}</td>
                    <td>{{ $obj->name ?? 'Unknown' }}</td>
                    <td>{{ strtoupper($obj->algorithm) }}</td>
                    <td>{{ date('Y-m-d', strtotime($obj->verified_at)) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<!-- High Risk Formats -->
<div class="card mb-4">
    <div class="card-header bg-danger text-white">
        <i class="bi bi-shield-exclamation me-2"></i>{{ __('High-Risk Format Objects') }}
        <span class="badge bg-light text-dark float-end">{{ count($highRiskObjects) }}</span>
    </div>
    @if(empty($highRiskObjects))
    <div class="card-body text-center text-muted">
        <i class="bi bi-check-circle fs-1 text-success"></i>
        <p class="mt-2">{{ __('No high-risk format objects') }}</p>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('Filename') }}</th>
                    <th>{{ __('Format') }}</th>
                    <th>{{ __('Risk') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($highRiskObjects as $obj)
                <tr>
                    <td>{{ $obj->id }}</td>
                    <td>{{ $obj->name ?? 'Unknown' }}</td>
                    <td>{{ $obj->format_name }}</td>
                    <td><span class="badge bg-danger">{{ ucfirst($obj->risk_level) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
