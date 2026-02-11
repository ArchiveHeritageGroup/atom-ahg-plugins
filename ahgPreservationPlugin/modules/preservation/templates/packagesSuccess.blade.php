@extends('layouts.page')

@section('title')
<h1><i class="bi bi-archive text-primary me-2"></i>{{ __('OAIS Packages') }}</h1>
@endsection

@section('content')

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Total Packages') }}</h6>
                        <h2 class="mb-0">{{ number_format($stats['total_packages']) }}</h2>
                        <small>{{ $stats['total_size_formatted'] }}</small>
                    </div>
                    <i class="bi bi-archive fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('SIPs') }}</h6>
                        <h2 class="mb-0">{{ number_format($stats['by_type']['sip']['count'] ?? 0) }}</h2>
                        <small>{{ __('Submission') }}</small>
                    </div>
                    <i class="bi bi-box-arrow-in-right fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('AIPs') }}</h6>
                        <h2 class="mb-0">{{ number_format($stats['by_type']['aip']['count'] ?? 0) }}</h2>
                        <small>{{ __('Archival') }}</small>
                    </div>
                    <i class="bi bi-safe fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('DIPs') }}</h6>
                        <h2 class="mb-0">{{ number_format($stats['by_type']['dip']['count'] ?? 0) }}</h2>
                        <small>{{ __('Dissemination') }}</small>
                    </div>
                    <i class="bi bi-box-arrow-right fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actions and Filters -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'packageEdit']) }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Create Package') }}
        </a>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'index']) }}" class="btn btn-outline-secondary ms-2">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
        </a>
    </div>
    <div class="d-flex gap-2">
        <!-- Type Filter -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-funnel me-1"></i>
                {{ $currentType ? strtoupper($currentType) : __('All Types') }}
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item {{ !$currentType ? 'active' : '' }}" href="{{ url_for(['module' => 'preservation', 'action' => 'packages']) }}">{{ __('All Types') }}</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item {{ 'sip' === $currentType ? 'active' : '' }}" href="{{ url_for(['module' => 'preservation', 'action' => 'packages', 'type' => 'sip']) }}">SIP - Submission</a></li>
                <li><a class="dropdown-item {{ 'aip' === $currentType ? 'active' : '' }}" href="{{ url_for(['module' => 'preservation', 'action' => 'packages', 'type' => 'aip']) }}">AIP - Archival</a></li>
                <li><a class="dropdown-item {{ 'dip' === $currentType ? 'active' : '' }}" href="{{ url_for(['module' => 'preservation', 'action' => 'packages', 'type' => 'dip']) }}">DIP - Dissemination</a></li>
            </ul>
        </div>
        <!-- Status Filter -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                {{ $currentStatus ? ucfirst($currentStatus) : __('All Statuses') }}
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item {{ !$currentStatus ? 'active' : '' }}" href="{{ url_for(['module' => 'preservation', 'action' => 'packages', 'type' => $currentType]) }}">{{ __('All Statuses') }}</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ url_for(['module' => 'preservation', 'action' => 'packages', 'type' => $currentType, 'status' => 'draft']) }}">Draft</a></li>
                <li><a class="dropdown-item" href="{{ url_for(['module' => 'preservation', 'action' => 'packages', 'type' => $currentType, 'status' => 'complete']) }}">Complete</a></li>
                <li><a class="dropdown-item" href="{{ url_for(['module' => 'preservation', 'action' => 'packages', 'type' => $currentType, 'status' => 'validated']) }}">Validated</a></li>
                <li><a class="dropdown-item" href="{{ url_for(['module' => 'preservation', 'action' => 'packages', 'type' => $currentType, 'status' => 'exported']) }}">Exported</a></li>
                <li><a class="dropdown-item" href="{{ url_for(['module' => 'preservation', 'action' => 'packages', 'type' => $currentType, 'status' => 'error']) }}">Error</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Packages Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul me-2"></i>{{ __('Packages') }}
        @if($currentType || $currentStatus)
            <span class="badge bg-secondary ms-2">
                @php
                $filters = [];
                if ($currentType) $filters[] = strtoupper($currentType);
                if ($currentStatus) $filters[] = ucfirst($currentStatus);
                @endphp
                {{ implode(' / ', $filters) }}
            </span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Package') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Objects') }}</th>
                    <th>{{ __('Size') }}</th>
                    <th>{{ __('Created') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @if(empty($packages))
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-archive fs-1 d-block mb-2 opacity-25"></i>
                        {{ __('No packages found.') }}
                        <a href="{{ url_for(['module' => 'preservation', 'action' => 'packageEdit']) }}" class="d-block mt-2">{{ __('Create your first package') }}</a>
                    </td>
                </tr>
                @else
                    @foreach($packages as $pkg)
                    <tr>
                        <td>
                            <a href="{{ url_for(['module' => 'preservation', 'action' => 'packageView', 'id' => $pkg->id]) }}" class="fw-bold text-decoration-none">
                                {{ $pkg->name }}
                            </a>
                            <br>
                            <small class="text-muted font-monospace">{{ substr($pkg->uuid, 0, 8) }}...</small>
                        </td>
                        <td>
                            @php
                            $typeClass = ['sip' => 'info', 'aip' => 'success', 'dip' => 'warning'][$pkg->package_type] ?? 'secondary';
                            $typeIcon = ['sip' => 'box-arrow-in-right', 'aip' => 'safe', 'dip' => 'box-arrow-right'][$pkg->package_type] ?? 'archive';
                            @endphp
                            <span class="badge bg-{{ $typeClass }}">
                                <i class="bi bi-{{ $typeIcon }} me-1"></i>
                                {{ strtoupper($pkg->package_type) }}
                            </span>
                        </td>
                        <td>
                            @php
                            $statusClass = [
                                'draft' => 'secondary',
                                'building' => 'warning',
                                'complete' => 'info',
                                'validated' => 'primary',
                                'exported' => 'success',
                                'error' => 'danger'
                            ][$pkg->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $statusClass }}">{{ ucfirst($pkg->status) }}</span>
                        </td>
                        <td>{{ number_format($pkg->object_count) }}</td>
                        <td>{{ $pkg->total_size ? formatBytes($pkg->total_size) : '-' }}</td>
                        <td>
                            <small>{{ date('Y-m-d H:i', strtotime($pkg->created_at)) }}</small>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ url_for(['module' => 'preservation', 'action' => 'packageView', 'id' => $pkg->id]) }}" class="btn btn-outline-primary" title="{{ __('View') }}">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if('draft' === $pkg->status)
                                <a href="{{ url_for(['module' => 'preservation', 'action' => 'packageEdit', 'id' => $pkg->id]) }}" class="btn btn-outline-secondary" title="{{ __('Edit') }}">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if($pkg->export_path)
                                <a href="{{ url_for(['module' => 'preservation', 'action' => 'packageDownload', 'id' => $pkg->id]) }}" class="btn btn-outline-success" title="{{ __('Download') }}">
                                    <i class="bi bi-download"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

@php
if (!function_exists('formatBytes')) {
    function formatBytes($bytes) {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' bytes';
    }
}
@endphp

@endsection
