@extends('layouts.page')

@section('title', __('Heritage Asset Reports'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-3"><i class="fas fa-chart-bar me-2"></i>{{ __('Heritage Asset Reports') }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>{{ __('Asset Register') }}</h5>
                </div>
                <div class="card-body">
                    <p>{{ __('Complete register of all heritage assets with carrying amounts and recognition status.') }}</p>
                </div>
                <div class="card-footer">
                    <a href="{{ url_for(['module' => 'heritageReport', 'action' => 'assetRegister']) }}" class="btn btn-primary">
                        {{ __('View Report') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>{{ __('Valuation Report') }}</h5>
                </div>
                <div class="card-body">
                    <p>{{ __('Assets with valuation history, revaluation surplus, and impairment losses.') }}</p>
                </div>
                <div class="card-footer">
                    <a href="{{ url_for(['module' => 'heritageReport', 'action' => 'valuation']) }}" class="btn btn-success">
                        {{ __('View Report') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>{{ __('Movement Report') }}</h5>
                </div>
                <div class="card-body">
                    <p>{{ __('Track loans, transfers, exhibitions and storage changes by date range.') }}</p>
                </div>
                <div class="card-footer">
                    <a href="{{ url_for(['module' => 'heritageReport', 'action' => 'movement']) }}" class="btn btn-info">
                        {{ __('View Report') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Standard-specific reports -->
    <h4 class="mt-4 mb-3">{{ __('Standard-Specific Reports') }}</h4>
    <div class="row">
        @foreach($standards as $std)
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h5>{{ $std->code }}</h5>
                        <small class="text-muted">{{ $std->country }}</small>
                        <div class="mt-3">
                            <a href="{{ url_for(['module' => 'heritageReport', 'action' => 'assetRegister', 'standard_id' => $std->id]) }}" class="btn btn-sm btn-outline-primary">
                                {{ __('View') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'dashboard']) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
        </a>
    </div>
</div>
@endsection
