@extends('layouts.page')

@php
// Helper to convert Symfony escaped arrays to plain arrays
$toArray = function($val) {
    if (is_array($val)) return $val;
    if ($val instanceof Traversable) return iterator_to_array($val);
    return [];
};

$contentRaw = $toArray($contentData ?? []);
$topContent = $toArray($contentRaw['top_content'] ?? []);
$lowPerforming = $toArray($contentRaw['low_performing'] ?? []);
$qualityIssues = $toArray($contentRaw['quality_issues'] ?? []);
$summaryRaw = $toArray($contentRaw['summary'] ?? []);
$summary = [
    'total_items' => $summaryRaw['total_items'] ?? 0,
    'total_views' => $summaryRaw['total_views'] ?? 0,
    'total_downloads' => $summaryRaw['total_downloads'] ?? 0,
    'avg_ctr' => $summaryRaw['avg_ctr'] ?? 0,
    'by_level' => $toArray($summaryRaw['by_level'] ?? ['Fonds' => 10, 'Series' => 25, 'File' => 100, 'Item' => 500]),
    'by_repository' => $toArray($summaryRaw['by_repository'] ?? ['Main Archive' => 400, 'Digital' => 150, 'Photos' => 85])
];
@endphp

@section('title')
<h1 class="h3">
    <i class="fas fa-chart-bar me-2"></i>Content Insights
</h1>
@endsection

@section('sidebar')
@include('heritage/adminSidebar', ['active' => 'content-analytics'])
@endsection

@section('content')
<!-- Summary Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <h3 class="mb-0">{{ number_format($summary['total_items'] ?? 0) }}</h3>
                <small class="text-muted">Total Items</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <h3 class="mb-0">{{ number_format($summary['total_views'] ?? 0) }}</h3>
                <small class="text-muted">Total Views (30d)</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <h3 class="mb-0">{{ number_format($summary['total_downloads'] ?? 0) }}</h3>
                <small class="text-muted">Downloads (30d)</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <h3 class="mb-0 text-success">{{ $summary['avg_ctr'] ?? 0 }}%</h3>
                <small class="text-muted">Avg Click-Through</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Top Performing Content -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Top Performing</h5>
            </div>
            <div class="card-body p-0">
                @if (!empty($topContent))
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Views</th>
                                <th class="text-center">Downloads</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_slice($topContent, 0, 10) as $item)
                            <tr>
                                <td>
                                    <a href="{{ url_for(['module' => 'informationobject', 'slug' => $item->slug]) }}">
                                        {{ mb_strimwidth($item->title ?? $item->slug, 0, 35, '...') }}
                                    </a>
                                </td>
                                <td class="text-center">{{ number_format($item->view_count) }}</td>
                                <td class="text-center">{{ number_format($item->download_count ?? 0) }}</td>
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

    <!-- Low Performing / Needs Attention -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="fas fa-eye-slash me-2 text-danger"></i>Needs Attention</h5>
            </div>
            <div class="card-body p-0">
                @if (!empty($lowPerforming))
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Views</th>
                                <th>Issue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_slice($lowPerforming, 0, 10) as $item)
                            <tr>
                                <td>
                                    <a href="{{ url_for(['module' => 'informationobject', 'slug' => $item->slug]) }}">
                                        {{ mb_strimwidth($item->title ?? $item->slug, 0, 30, '...') }}
                                    </a>
                                </td>
                                <td class="text-center">{{ number_format($item->view_count) }}</td>
                                <td>
                                    <span class="badge bg-warning">{{ $item->issue ?? 'Low visibility' }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center py-4">All content performing well!</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Quality Issues -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Quality Issues</h5>
        <span class="badge bg-warning">{{ count($qualityIssues) }} items</span>
    </div>
    <div class="card-body p-0">
        @if (!empty($qualityIssues))
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Issue Type</th>
                        <th>Details</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($qualityIssues as $issue)
                    <tr>
                        <td>
                            <a href="{{ url_for(['module' => 'informationobject', 'slug' => $issue->slug]) }}">
                                {{ mb_strimwidth($issue->title ?? $issue->slug, 0, 40, '...') }}
                            </a>
                        </td>
                        <td>
                            @php
                            $issueColors = [
                                'missing_description' => 'warning',
                                'no_digital_object' => 'info',
                                'poor_metadata' => 'danger',
                                'broken_links' => 'danger'
                            ];
                            $color = $issueColors[$issue->issue_type] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }}">
                                {{ ucwords(str_replace('_', ' ', $issue->issue_type)) }}
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $issue->details ?? '-' }}</small>
                        </td>
                        <td>
                            <a href="{{ url_for(['module' => 'heritage', 'action' => 'custodianItem', 'slug' => $issue->slug]) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-pencil-alt"></i> Fix
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center text-muted py-4">
            <i class="fas fa-check-circle fs-1 text-success mb-3 d-block"></i>
            <p>No quality issues detected.</p>
        </div>
        @endif
    </div>
</div>

<!-- Content Distribution Chart -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Content Distribution</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>By Level of Description</h6>
                <canvas id="levelChart" style="height: 250px;"></canvas>
            </div>
            <div class="col-md-6">
                <h6>By Repository</h6>
                <canvas id="repoChart" style="height: 250px;"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js" {!! $csp_nonce !!}></script>
<script {!! $csp_nonce !!}>
@php
$levelData = $summary['by_level'];
$repoData = $summary['by_repository'];
@endphp

new Chart(document.getElementById('levelChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(array_keys($levelData)) !!},
        datasets: [{
            data: {!! json_encode(array_values($levelData)) !!},
            backgroundColor: ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545', '#fd7e14', '#ffc107']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'right' } }
    }
});

new Chart(document.getElementById('repoChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(array_keys($repoData)) !!},
        datasets: [{
            data: {!! json_encode(array_values($repoData)) !!},
            backgroundColor: ['#198754', '#20c997', '#0dcaf0', '#0d6efd', '#6610f2']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'right' } }
    }
});
</script>
@endsection
