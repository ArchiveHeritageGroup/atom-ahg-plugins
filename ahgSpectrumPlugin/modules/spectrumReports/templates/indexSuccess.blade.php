@extends('layouts.page')

@section('sidebar')
<div class="sidebar-content">
    <h4>{{ __('Spectrum Reports') }}</h4>
    <ul class="list-unstyled">
        <li><a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'objectEntry']) }}"><i class="fas fa-sign-in-alt me-2"></i>{{ __('Object Entry') }}</a></li>
        <li><a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'loans']) }}"><i class="fas fa-exchange-alt me-2"></i>{{ __('Loans') }}</a></li>
        <li><a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'acquisitions']) }}"><i class="fas fa-hand-holding me-2"></i>{{ __('Acquisitions') }}</a></li>
        <li><a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'movements']) }}"><i class="fas fa-truck me-2"></i>{{ __('Movements') }}</a></li>
        <li><a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'conditions']) }}"><i class="fas fa-heartbeat me-2"></i>{{ __('Condition Checks') }}</a></li>
        <li><a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'conservation']) }}"><i class="fas fa-tools me-2"></i>{{ __('Conservation') }}</a></li>
        <li><a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'valuations']) }}"><i class="fas fa-dollar-sign me-2"></i>{{ __('Valuations') }}</a></li>
    </ul>
    <hr>
    <a href="/admin/dashboard" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i>{{ __('Back to Dashboard') }}</a>
</div>
@endsection

@section('title')
<h1><i class="fas fa-clipboard-list"></i> {{ __('Spectrum Reports Dashboard') }}</h1>
@endsection

@section('content')
<div class="spectrum-dashboard">
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h2>{{ number_format($stats['conditionCheck']) }}</h2>
                    <p class="mb-0">{{ __('Condition Checks') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h2>{{ number_format($stats['loanIn'] + $stats['loanOut']) }}</h2>
                    <p class="mb-0">{{ __('Total Loans') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h2>{{ number_format($stats['valuation']) }}</h2>
                    <p class="mb-0">{{ __('Valuations') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-dark">
                <div class="card-body">
                    <h2>{{ number_format($stats['acquisition']) }}</h2>
                    <p class="mb-0">{{ __('Acquisitions') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>{{ __('Procedure Summary') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between"><span>Object Entry</span><span class="badge bg-primary">{{ $stats['objectEntry'] }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Object Exit</span><span class="badge bg-secondary">{{ $stats['objectExit'] }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Loans In</span><span class="badge bg-success">{{ $stats['loanIn'] }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Loans Out</span><span class="badge bg-info">{{ $stats['loanOut'] }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Movements</span><span class="badge bg-warning">{{ $stats['movement'] }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Conservation</span><span class="badge bg-danger">{{ $stats['conservation'] }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Deaccession</span><span class="badge bg-dark">{{ $stats['deaccession'] }}</span></li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>{{ __('Recent Activity') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    @if(empty($recentActivity))
                    <li class="list-group-item text-muted">{{ __('No recent activity') }}</li>
                    @else
                    @foreach($recentActivity as $a)
                    <li class="list-group-item">
                        <small class="text-muted">{{ $a->action_date ?? '-' }}</small><br>
                        {{ $a->action ?? $a->event_type ?? '-' }}
                    </li>
                    @endforeach
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
