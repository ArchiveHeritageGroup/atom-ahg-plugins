@extends('layouts.page')

@php
// Helper to convert Symfony escaped arrays to plain arrays
$toArray = function($val) use (&$toArray) {
    if (is_array($val)) {
        return array_map($toArray, $val);
    }
    if ($val instanceof Traversable) {
        return array_map($toArray, iterator_to_array($val));
    }
    return $val;
};

$contributionData = $toArray($contributionData ?? []);
$profile = $toArray($profile ?? []);
$contributions = $contributionData['contributions'] ?? [];
$stats = $contributionData['stats'] ?? [];
$contributor = $profile['contributor'] ?? [];
$badges = $profile['badges'] ?? [];
@endphp

@section('title')
<h1 class="h3">
    <i class="fas fa-journal-whills-text me-2"></i>My Contributions
</h1>
@endsection

@section('sidebar')
<!-- Profile Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body text-center">
        @if (!empty($contributor['avatar_url']))
        <img src="{{ $contributor['avatar_url'] }}"
             class="rounded-circle mb-3" width="80" height="80" alt="Avatar">
        @else
        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
            <i class="fas fa-user display-4 text-primary"></i>
        </div>
        @endif
        <h5 class="mb-1">{{ $contributor['display_name'] ?? 'Contributor' }}</h5>
        <span class="badge bg-{{ match($contributor['trust_level'] ?? 'new') {
            'expert' => 'primary',
            'trusted' => 'success',
            'contributor' => 'info',
            default => 'secondary'
        } }}">
            {{ ucfirst($contributor['trust_level'] ?? 'new') }}
        </span>
        <p class="text-muted small mt-2 mb-0">
            Member since {{ date('M Y', strtotime($contributor['created_at'] ?? 'now')) }}
        </p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-4">
    <div class="col-6">
        <div class="card border-0 bg-primary bg-opacity-10 text-center">
            <div class="card-body py-3">
                <div class="h4 mb-0 text-primary">{{ number_format($stats['total'] ?? 0) }}</div>
                <small class="text-muted">Total</small>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border-0 bg-success bg-opacity-10 text-center">
            <div class="card-body py-3">
                <div class="h4 mb-0 text-success">{{ number_format($stats['approved'] ?? 0) }}</div>
                <small class="text-muted">Approved</small>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border-0 bg-warning bg-opacity-10 text-center">
            <div class="card-body py-3">
                <div class="h4 mb-0 text-warning">{{ number_format($stats['pending'] ?? 0) }}</div>
                <small class="text-muted">Pending</small>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border-0 bg-info bg-opacity-10 text-center">
            <div class="card-body py-3">
                <div class="h4 mb-0 text-info">{{ number_format($stats['total_points'] ?? 0) }}</div>
                <small class="text-muted">Points</small>
            </div>
        </div>
    </div>
</div>

<!-- Badges -->
@if (!empty($badges))
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="fas fa-award me-2"></i>Badges</h6>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            @foreach ($badges as $badge)
            <span class="badge bg-{{ $badge['color'] ?? 'primary' }}" title="{{ $badge['description'] ?? '' }}">
                <i class="fas {{ $badge['icon'] ?? 'fas fa-award' }} me-1"></i>
                {{ $badge['name'] }}
            </span>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection

@section('content')
<!-- Main Content -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Contribution History</h5>
        <!-- Status Filter -->
        <div class="btn-group btn-group-sm">
            <a href="{{ url_for(['module' => 'heritage', 'action' => 'myContributions']) }}"
               class="btn btn-outline-secondary {{ empty($_GET['status']) ? 'active' : '' }}">All</a>
            <a href="{{ url_for(['module' => 'heritage', 'action' => 'myContributions', 'status' => 'pending']) }}"
               class="btn btn-outline-secondary {{ ($_GET['status'] ?? '') === 'pending' ? 'active' : '' }}">Pending</a>
            <a href="{{ url_for(['module' => 'heritage', 'action' => 'myContributions', 'status' => 'approved']) }}"
               class="btn btn-outline-secondary {{ ($_GET['status'] ?? '') === 'approved' ? 'active' : '' }}">Approved</a>
            <a href="{{ url_for(['module' => 'heritage', 'action' => 'myContributions', 'status' => 'rejected']) }}"
               class="btn btn-outline-secondary {{ ($_GET['status'] ?? '') === 'rejected' ? 'active' : '' }}">Rejected</a>
        </div>
    </div>
    <div class="card-body p-0">
        @if (empty($contributions))
        <div class="text-center text-muted py-5">
            <i class="fas fa-inbox display-1 mb-3 d-block"></i>
            <p class="mb-3">No contributions yet.</p>
            <a href="{{ url_for(['module' => 'heritage', 'action' => 'search']) }}" class="btn btn-primary">
                <i class="fas fa-search me-1"></i>Browse Collection
            </a>
        </div>
        @else
        <div class="list-group list-group-flush">
            @foreach ($contributions as $contrib)
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center">
                        <i class="fas {{ $contrib['type']['icon'] }} fs-4 text-{{ $contrib['type']['color'] }} me-3"></i>
                        <div>
                            <h6 class="mb-0">
                                <a href="{{ url_for(['module' => 'informationobject', 'slug' => $contrib['item']['slug']]) }}" class="text-decoration-none">
                                    {{ $contrib['item']['title'] }}
                                </a>
                            </h6>
                            <small class="text-muted">
                                {!! $contrib['type']['name'] !!} &middot;
                                {{ date('M d, Y', strtotime($contrib['created_at'])) }}
                            </small>
                        </div>
                    </div>
                    <div class="text-end">
                        @php
                        $statusColor = match($contrib['status']) {
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'pending' => 'warning',
                            default => 'secondary'
                        };
                        @endphp
                        <span class="badge bg-{{ $statusColor }}">
                            {{ ucfirst($contrib['status']) }}
                        </span>
                        @if ($contrib['points_awarded'] > 0)
                        <div class="small text-success mt-1">
                            +{{ $contrib['points_awarded'] }} pts
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Content Preview -->
                <div class="bg-light rounded p-2 small mb-2">
                    @php
                    $content = $contrib['content'];
                    if (!empty($content['text'])) {
                        echo e(substr($content['text'], 0, 200)) . (strlen($content['text'] ?? '') > 200 ? '...' : '');
                    } elseif (!empty($content['name'])) {
                        echo 'Identified: ' . e($content['name']);
                    } elseif (!empty($content['suggestion'])) {
                        echo 'Correction: ' . e(substr($content['suggestion'], 0, 100));
                    } elseif (!empty($content['tags'])) {
                        echo 'Tags: ' . e(implode(', ', $content['tags']));
                    }
                    @endphp
                </div>

                <!-- Review Notes -->
                @if (!empty($contrib['review_notes']))
                <div class="alert alert-{{ $contrib['status'] === 'approved' ? 'success' : 'danger' }} py-2 small mb-0">
                    <strong>Reviewer:</strong> {{ $contrib['review_notes'] }}
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if (($contributionData['pages'] ?? 1) > 1)
        <div class="card-footer bg-transparent">
            <nav aria-label="Contribution pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    @for ($i = 1; $i <= $contributionData['pages']; $i++)
                    <li class="page-item {{ $contributionData['page'] == $i ? 'active' : '' }}">
                        <a class="page-link" href="{{ url_for(['module' => 'heritage', 'action' => 'myContributions', 'page' => $i, 'status' => $_GET['status'] ?? null]) }}">
                            {{ $i }}
                        </a>
                    </li>
                    @endfor
                </ul>
            </nav>
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
