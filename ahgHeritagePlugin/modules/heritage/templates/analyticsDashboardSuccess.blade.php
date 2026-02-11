@extends('layouts.page')

@php
// Helper to convert Symfony escaped arrays to plain arrays
$toArray = function($val) {
    if (is_array($val)) return $val;
    if ($val instanceof Traversable) return iterator_to_array($val);
    return [];
};

$dashboardRaw = $toArray($dashboardData ?? []);
$overview = $toArray($dashboardRaw['overview'] ?? []);
$search = $toArray($dashboardRaw['search'] ?? []);
$access = $toArray($dashboardRaw['access'] ?? []);
$trendsRaw = $toArray($dashboardRaw['trends'] ?? []);
$trends = [
    'searches' => $toArray($trendsRaw['searches'] ?? []),
    'clicks' => $toArray($trendsRaw['clicks'] ?? [])
];
@endphp

@section('title')
<h1 class="h3">
    <i class="fas fa-chart-line me-2"></i>Analytics Dashboard
</h1>
@endsection

@section('sidebar')
@include('heritage/adminSidebar', ['active' => 'analytics'])

<div class="mt-4">
    <label class="form-label">Time Period</label>
    <div class="btn-group-vertical w-100">
        <a href="?days=7" class="btn btn-outline-primary {{ $days == 7 ? 'active' : '' }}">Last 7 Days</a>
        <a href="?days=30" class="btn btn-outline-primary {{ $days == 30 ? 'active' : '' }}">Last 30 Days</a>
        <a href="?days=90" class="btn btn-outline-primary {{ $days == 90 ? 'active' : '' }}">Last 90 Days</a>
    </div>
</div>
@endsection

@section('content')
<!-- Overview Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-primary bg-opacity-10">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ number_format($overview['total_views'] ?? 0) }}</h3>
                <small class="text-muted">Page Views</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-success bg-opacity-10">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ number_format($overview['total_searches'] ?? 0) }}</h3>
                <small class="text-muted">Searches</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-info bg-opacity-10">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ number_format($overview['total_downloads'] ?? 0) }}</h3>
                <small class="text-muted">Downloads</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-warning bg-opacity-10">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ number_format($overview['unique_visitors'] ?? 0) }}</h3>
                <small class="text-muted">Unique Visitors</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Search Performance -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Search Performance</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <h4 class="mb-0">{{ $search['avg_results'] ?? 0 }}</h4>
                        <small class="text-muted">Avg Results</small>
                    </div>
                    <div class="col-4">
                        <h4 class="mb-0 {{ ($search['zero_result_rate'] ?? 0) > 20 ? 'text-danger' : '' }}">
                            {{ $search['zero_result_rate'] ?? 0 }}%
                        </h4>
                        <small class="text-muted">Zero Results</small>
                    </div>
                    <div class="col-4">
                        <h4 class="mb-0 text-success">{{ $search['click_through_rate'] ?? 0 }}%</h4>
                        <small class="text-muted">Click-through</small>
                    </div>
                </div>
                <hr>
                <a href="{{ url_for(['module' => 'heritage', 'action' => 'analyticsSearch']) }}" class="btn btn-outline-primary w-100">
                    View Search Insights
                </a>
            </div>
        </div>
    </div>

    <!-- Access Control -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Access Control</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <h4 class="mb-0 {{ ($access['pending_requests'] ?? 0) > 0 ? 'text-warning' : '' }}">
                            {{ $access['pending_requests'] ?? 0 }}
                        </h4>
                        <small class="text-muted">Pending</small>
                    </div>
                    <div class="col-4">
                        <h4 class="mb-0 text-success">{{ $access['approval_rate'] ?? 0 }}%</h4>
                        <small class="text-muted">Approval Rate</small>
                    </div>
                    <div class="col-4">
                        <h4 class="mb-0 {{ ($access['unresolved_popia'] ?? 0) > 0 ? 'text-danger' : '' }}">
                            {{ $access['unresolved_popia'] ?? 0 }}
                        </h4>
                        <small class="text-muted">POPIA Flags</small>
                    </div>
                </div>
                <hr>
                <div class="d-flex gap-2">
                    <a href="{{ url_for(['module' => 'heritage', 'action' => 'adminAccessRequests']) }}" class="btn btn-outline-primary flex-fill">
                        Requests
                    </a>
                    <a href="{{ url_for(['module' => 'heritage', 'action' => 'adminPopia']) }}" class="btn btn-outline-primary flex-fill">
                        POPIA Flags
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trends Chart -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h5 class="mb-0"><i class="fas fa-chart-line-arrow me-2"></i>Search & Click Trends</h5>
    </div>
    <div class="card-body">
        @if (!empty($trends['searches']))
        <div style="height: 250px;">
            <canvas id="trendsChart"></canvas>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js" {!! $csp_nonce !!}></script>
        <script {!! $csp_nonce !!}>
        new Chart(document.getElementById('trendsChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode(array_keys($trends['searches'])) !!},
                datasets: [{
                    label: 'Searches',
                    data: {!! json_encode(array_values($trends['searches'])) !!},
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.3,
                    fill: true
                }, {
                    label: 'Clicks',
                    data: {!! json_encode(array_values($trends['clicks'] ?? [])) !!},
                    borderColor: 'rgb(25, 135, 84)',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });
        </script>
        @else
        <p class="text-muted text-center py-4">No trend data available for this period.</p>
        @endif
    </div>
</div>
@endsection
