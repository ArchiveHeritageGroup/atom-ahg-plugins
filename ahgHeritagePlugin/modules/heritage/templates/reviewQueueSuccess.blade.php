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

$queueData = $toArray($queueData ?? []);
$types = $toArray($types ?? []);
$contributions = $queueData['contributions'] ?? [];
$countsByType = $queueData['counts_by_type'] ?? [];
$total = $queueData['total'] ?? 0;
@endphp

@section('title')
<h1 class="h3">
    <i class="fas fa-inbox me-2"></i>Review Queue
</h1>
@endsection

@section('sidebar')
@include('heritage/adminSidebar', ['active' => 'contributions'])

<!-- Queue Stats -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="fas fa-funnel me-2"></i>Filter by Type</h6>
    </div>
    <div class="list-group list-group-flush">
        <a href="{{ url_for(['module' => 'heritage', 'action' => 'reviewQueue']) }}"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ empty($_GET['type']) ? 'active' : '' }}">
            All Types
            <span class="badge bg-primary">{{ $total }}</span>
        </a>
        @foreach ($countsByType as $type)
        <a href="{{ url_for(['module' => 'heritage', 'action' => 'reviewQueue', 'type' => $type['code']]) }}"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ ($_GET['type'] ?? '') === $type['code'] ? 'active' : '' }}">
            <span>
                <i class="fas {{ $type['icon'] }} me-2"></i>
                {{ $type['name'] }}
            </span>
            <span class="badge bg-warning">{{ $type['count'] }}</span>
        </a>
        @endforeach
    </div>
</div>
@endsection

@section('content')
<!-- Main Content -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Pending Contributions</h5>
        <span class="badge bg-warning">{{ $total }} pending</span>
    </div>
    <div class="card-body p-0">
        @if (empty($contributions))
        <div class="text-center text-muted py-5">
            <i class="fas fa-check-circle display-1 text-success mb-3 d-block"></i>
            <p class="mb-0">All caught up! No pending contributions to review.</p>
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
                                <a href="{{ url_for(['module' => 'heritage', 'action' => 'reviewContribution', 'id' => $contrib['id']]) }}" class="text-decoration-none">
                                    {{ $contrib['item']['title'] }}
                                </a>
                            </h6>
                            <small class="text-muted">
                                {!! $contrib['type']['name'] !!} &middot;
                                Submitted {{ date('M d, Y H:i', strtotime($contrib['created_at'])) }}
                            </small>
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-{{ match($contrib['contributor']['trust_level']) {
                            'expert' => 'primary',
                            'trusted' => 'success',
                            'contributor' => 'info',
                            default => 'secondary'
                        } }}">
                            {{ ucfirst($contrib['contributor']['trust_level']) }}
                        </span>
                    </div>
                </div>

                <!-- Contributor Info -->
                <div class="d-flex align-items-center mb-2 small text-muted">
                    <span class="me-3">
                        <i class="fas fa-user me-1"></i>
                        {{ $contrib['contributor']['display_name'] }}
                    </span>
                    <span class="me-3">
                        <i class="fas fa-check-circle text-success me-1"></i>
                        {{ $contrib['contributor']['approved_count'] }} approved
                    </span>
                    <span>
                        <i class="fas fa-gift text-primary me-1"></i>
                        +{{ $contrib['type']['points_value'] }} pts
                    </span>
                </div>

                <!-- Content Preview -->
                <div class="bg-light rounded p-2 small mb-2">
                    @php
                    $content = $contrib['content'];
                    if (!empty($content['text'])) {
                        echo e(substr($content['text'], 0, 200)) . (strlen($content['text'] ?? '') > 200 ? '...' : '');
                    } elseif (!empty($content['name'])) {
                        echo 'Identified: <strong>' . e($content['name']) . '</strong>';
                        if (!empty($content['relationship'])) {
                            echo ' (' . e($content['relationship']) . ')';
                        }
                    } elseif (!empty($content['suggestion'])) {
                        echo 'Field: <strong>' . e($content['field'] ?? 'unknown') . '</strong> &rarr; ';
                        echo e(substr($content['suggestion'], 0, 100));
                    } elseif (!empty($content['tags'])) {
                        echo 'Tags: ';
                        foreach ($content['tags'] as $tag) {
                            echo '<span class="badge bg-secondary me-1">' . e($tag) . '</span>';
                        }
                    }
                    @endphp
                </div>

                <!-- Actions -->
                <div class="d-flex gap-2">
                    <a href="{{ url_for(['module' => 'heritage', 'action' => 'reviewContribution', 'id' => $contrib['id']]) }}"
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>Review
                    </a>
                    <a href="{{ url_for(['module' => 'informationobject', 'slug' => $contrib['item']['slug']]) }}"
                       class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="fas fa-box-arrow-up-right me-1"></i>View Item
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if (($queueData['pages'] ?? 1) > 1)
        <div class="card-footer bg-transparent">
            <nav aria-label="Queue pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    @for ($i = 1; $i <= $queueData['pages']; $i++)
                    <li class="page-item {{ $queueData['page'] == $i ? 'active' : '' }}">
                        <a class="page-link" href="{{ url_for(['module' => 'heritage', 'action' => 'reviewQueue', 'page' => $i, 'type' => $_GET['type'] ?? null]) }}">
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
