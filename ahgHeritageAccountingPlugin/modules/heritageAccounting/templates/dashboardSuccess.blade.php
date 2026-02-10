@extends('layouts.page')

@section('title', __('Heritage Asset Accounting'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-3">
                <i class="fas fa-landmark me-2"></i>{{ __('Heritage Asset Accounting') }}
            </h1>
            <p class="text-muted">{{ __('Multi-standard heritage asset financial accounting') }}</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h6 class="text-white-50">{{ __('Total Assets') }}</h6>
                    <h2 class="mb-0">{{ number_format($stats['total']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h6 class="text-white-50">{{ __('Recognised') }}</h6>
                    <h2 class="mb-0">{{ number_format($stats['recognised']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <h6 class="text-dark-50">{{ __('Pending') }}</h6>
                    <h2 class="mb-0">{{ number_format($stats['pending']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h6 class="text-white-50">{{ __('Total Value') }}</h6>
                    <h2 class="mb-0">{{ number_format($stats['total_value'], 2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>{{ __('Quick Actions') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'browse']) }}" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>{{ __('Browse Assets') }}
                        </a>
                        <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'add']) }}" class="btn btn-outline-success">
                            <i class="fas fa-plus me-2"></i>{{ __('Add Asset') }}
                        </a>
                        <a href="{{ url_for(['module' => 'heritageReport', 'action' => 'index']) }}" class="btn btn-outline-info">
                            <i class="fas fa-chart-bar me-2"></i>{{ __('Reports') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- By Asset Class -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-th-large me-2"></i>{{ __('By Asset Class') }}</h5>
                </div>
                <div class="card-body">
                    @if(!empty($stats['by_class']))
                        <ul class="list-group list-group-flush">
                            @foreach($stats['by_class'] as $class)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {!! $class->class_name ?: 'Unclassified' !!}
                                    <span class="badge bg-primary rounded-pill">{!! $class->count !!}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted text-center mb-0">{{ __('No assets yet') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Accounting Standards -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>{{ __('Supported Standards') }}</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @foreach($standards as $standard)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $standard->code }}</strong><br>
                                    <small class="text-muted">{{ $standard->country }}</small>
                                </div>
                                @if($standard->capitalisation_required)
                                    <span class="badge bg-success">Required</span>
                                @else
                                    <span class="badge bg-secondary">Optional</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <small class="text-muted d-block mt-2"><i class="fas fa-info-circle me-1"></i><em>Required</em> = capitalisation mandatory; <em>Optional</em> = at discretion</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Assets -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>{{ __('Recent Assets') }}</h5>
                    <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'browse']) }}" class="btn btn-sm btn-light">
                        {{ __('View All') }}
                    </a>
                </div>
                <div class="card-body p-0">
                    @if(!empty($recentAssets))
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Identifier') }}</th>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Class') }}</th>
                                        <th>{{ __('Standard') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th class="text-end">{{ __('Carrying Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentAssets as $asset)
                                        <tr>
                                            <td>
                                                <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) }}">
                                                    {{ $asset->object_identifier ?: 'N/A' }}
                                                </a>
                                            </td>
                                            <td>{{ $asset->object_title ?: '-' }}</td>
                                            <td>{!! $asset->class_name ?: '-' !!}</td>
                                            <td>{{ $asset->standard_code ?: '-' }}</td>
                                            <td>
                                                @php
                                                $statusColors = [
                                                    'recognised' => 'success',
                                                    'not_recognised' => 'secondary',
                                                    'pending' => 'warning',
                                                    'derecognised' => 'danger'
                                                ];
                                                $color = $statusColors[$asset->recognition_status] ?? 'secondary';
                                                @endphp
                                                <span class="badge bg-{{ $color }}">{{ ucfirst($asset->recognition_status) }}</span>
                                            </td>
                                            <td class="text-end">{{ number_format($asset->current_carrying_amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-landmark fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('No heritage assets recorded yet.') }}</p>
                            <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'add']) }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>{{ __('Add First Asset') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
