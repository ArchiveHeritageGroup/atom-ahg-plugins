@extends('layouts.page')

@php
// Helper to convert Symfony escaped arrays to plain arrays
$toArray = function($val) {
    if (is_array($val)) return $val;
    if ($val instanceof Traversable) return iterator_to_array($val);
    return [];
};

$popularQueries = $toArray($popularQueries ?? []);
$zeroResultQueries = $toArray($zeroResultQueries ?? []);
$trendingQueries = $toArray($trendingQueries ?? []);
$patternsRaw = $toArray($patterns ?? []);
$patterns = [
    'by_hour' => $toArray($patternsRaw['by_hour'] ?? []),
    'by_day_of_week' => $toArray($patternsRaw['by_day_of_week'] ?? [])
];
@endphp

@section('title')
<h1 class="h3">
    <i class="fas fa-search me-2"></i>Search Insights
</h1>
@endsection

@section('sidebar')
@include('heritage/adminSidebar', ['active' => 'search-analytics'])
@endsection

@section('content')
<!-- Conversion Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <h3 class="mb-0">{{ number_format($conversion['total_searches'] ?? 0) }}</h3>
                <small class="text-muted">Total Searches</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <h3 class="mb-0">{{ $conversion['result_rate'] ?? 0 }}%</h3>
                <small class="text-muted">Result Rate</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <h3 class="mb-0 text-success">{{ $conversion['conversion_rate'] ?? 0 }}%</h3>
                <small class="text-muted">Conversion Rate</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <h3 class="mb-0">{{ $conversion['avg_clicks'] ?? 0 }}</h3>
                <small class="text-muted">Avg Clicks/Search</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Popular Queries -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="fas fa-fire me-2 text-danger"></i>Popular Queries</h5>
            </div>
            <div class="card-body p-0">
                @if (!empty($popularQueries))
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Query</th>
                                <th class="text-center">Searches</th>
                                <th class="text-center">Clicks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_slice($popularQueries, 0, 10) as $query)
                            <tr>
                                <td>{{ $query->query_text }}</td>
                                <td class="text-center">{{ number_format($query->search_count) }}</td>
                                <td class="text-center">{{ number_format($query->total_clicks ?? 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center py-4">No data available.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Zero Result Queries -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2 text-warning"></i>Zero Result Queries</h5>
            </div>
            <div class="card-body p-0">
                @if (!empty($zeroResultQueries))
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Query</th>
                                <th class="text-center">Count</th>
                                <th>Last Searched</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_slice($zeroResultQueries, 0, 10) as $query)
                            <tr>
                                <td>{{ $query->query_text }}</td>
                                <td class="text-center">{{ number_format($query->search_count) }}</td>
                                <td><small class="text-muted">{{ date('M d', strtotime($query->last_searched)) }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center py-4">No zero-result queries.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Trending Queries -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h5 class="mb-0"><i class="fas fa-chart-line-arrow me-2 text-success"></i>Trending Queries</h5>
    </div>
    <div class="card-body">
        @if (!empty($trendingQueries))
        <div class="row">
            @foreach ($trendingQueries as $trend)
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                    <span>{{ $trend['query'] }}</span>
                    <span class="badge bg-success">+{{ $trend['growth_percent'] }}%</span>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-muted text-center">No trending queries this week.</p>
        @endif
    </div>
</div>

<!-- Search Patterns -->
@if (!empty($patterns['by_hour']))
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Search Patterns by Time</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h6>By Hour of Day</h6>
                <div style="height: 200px;">
                    <canvas id="hourChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <h6>By Day of Week</h6>
                @foreach ($patterns['by_day_of_week'] ?? [] as $day => $count)
                <div class="d-flex justify-content-between mb-1">
                    <span>{{ $day }}</span>
                    <span class="badge bg-secondary">{{ number_format($count) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js" {!! $csp_nonce !!}></script>
<script {!! $csp_nonce !!}>
new Chart(document.getElementById('hourChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode(array_keys($patterns['by_hour'])) !!},
        datasets: [{
            label: 'Searches',
            data: {!! json_encode(array_values($patterns['by_hour'])) !!},
            backgroundColor: 'rgba(13, 110, 253, 0.5)',
            borderColor: 'rgb(13, 110, 253)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
@endif
@endsection
