@extends('layouts.page')

@section('title')
<div class="d-flex justify-content-between align-items-center">
  <h1><i class="bi bi-shield-check text-success me-2"></i>{{ __('Digital Preservation Dashboard') }}</h1>
  <a href="{{ url_for(['module' => 'reports', 'action' => 'index']) }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>{{ __('Return to Central Dashboard') }}
  </a>
</div>
@endsection

@section('content')

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Digital Objects') }}</h6>
                        <h2 class="mb-0">{{ number_format($stats['total_objects']) }}</h2>
                        <small>{{ $stats['total_size_formatted'] }}</small>
                    </div>
                    <i class="bi bi-file-earmark-binary fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Checksum Coverage') }}</h6>
                        <h2 class="mb-0">{{ $stats['checksum_coverage'] }}%</h2>
                        <small>{{ number_format($stats['objects_with_checksum']) }} objects</small>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card {{ $stats['fixity_failures_30d'] > 0 ? 'bg-danger' : 'bg-info' }} text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Fixity Checks (30d)') }}</h6>
                        <h2 class="mb-0">{{ number_format($stats['fixity_checks_30d']) }}</h2>
                        <small>{{ $stats['fixity_failures_30d'] }} failures</small>
                    </div>
                    <i class="bi bi-fingerprint fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card {{ $stats['formats_at_risk'] > 0 ? 'bg-warning' : 'bg-secondary' }} text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('At-Risk Formats') }}</h6>
                        <h2 class="mb-0">{{ number_format($stats['formats_at_risk']) }}</h2>
                        <small>{{ $stats['pending_verification'] }} pending verification</small>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'identification']) }}" class="btn btn-outline-info">
            <i class="bi bi-fingerprint me-1"></i>{{ __('Format ID') }}
        </a>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'fixityLog']) }}" class="btn btn-outline-primary">
            <i class="bi bi-list-check me-1"></i>{{ __('Fixity Log') }}
        </a>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'events']) }}" class="btn btn-outline-primary">
            <i class="bi bi-calendar-event me-1"></i>{{ __('Events') }}
        </a>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'formats']) }}" class="btn btn-outline-primary">
            <i class="bi bi-file-code me-1"></i>{{ __('Format Registry') }}
        </a>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'scheduler']) }}" class="btn btn-outline-dark">
            <i class="bi bi-clock-history me-1"></i>{{ __('Scheduler') }}
        </a>
    </div>
    <div>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'packages']) }}" class="btn btn-outline-primary">
            <i class="bi bi-archive me-1"></i>{{ __('OAIS Packages') }}
        </a>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'virusScan']) }}" class="btn btn-outline-danger">
            <i class="bi bi-shield-exclamation me-1"></i>{{ __('Virus Scan') }}
        </a>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'conversion']) }}" class="btn btn-outline-success">
            <i class="bi bi-arrow-repeat me-1"></i>{{ __('Format Conversion') }}
        </a>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'backup']) }}" class="btn btn-outline-info">
            <i class="bi bi-cloud-arrow-up me-1"></i>{{ __('Backup') }}
        </a>
    </div>
</div>

<div class="row">
    <!-- Recent Fixity Checks -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-fingerprint me-2"></i>{{ __('Recent Fixity Checks') }}</span>
                <a href="{{ url_for(['module' => 'preservation', 'action' => 'fixityLog']) }}" class="btn btn-sm btn-outline-secondary">
                    {{ __('View All') }}
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('File') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Checked') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (empty($recentFixityChecks))
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">
                                {{ __('No fixity checks yet') }}
                            </td>
                        </tr>
                        @else
                            @foreach ($recentFixityChecks as $check)
                            <tr>
                                <td>
                                    <a href="{{ url_for(['module' => 'preservation', 'action' => 'object', 'id' => $check->digital_object_id]) }}">
                                        {{ substr($check->filename ?? 'Unknown', 0, 30) }}
                                    </a>
                                </td>
                                <td>
                                    @if ($check->status === 'pass')
                                        <span class="badge bg-success">Pass</span>
                                    @elseif ($check->status === 'fail')
                                        <span class="badge bg-danger">Fail</span>
                                    @else
                                        <span class="badge bg-warning">{{ ucfirst($check->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ date('Y-m-d H:i', strtotime($check->checked_at)) }}</small>
                                </td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Events -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-event me-2"></i>{{ __('Recent Preservation Events') }}</span>
                <a href="{{ url_for(['module' => 'preservation', 'action' => 'events']) }}" class="btn btn-sm btn-outline-secondary">
                    {{ __('View All') }}
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Event') }}</th>
                            <th>{{ __('Outcome') }}</th>
                            <th>{{ __('Time') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (empty($recentEvents))
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">
                                {{ __('No preservation events yet') }}
                            </td>
                        </tr>
                        @else
                            @foreach ($recentEvents as $event)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">{{ str_replace('_', ' ', $event->event_type) }}</span>
                                </td>
                                <td>
                                    @if ($event->event_outcome === 'success')
                                        <span class="text-success"><i class="bi bi-check-circle"></i></span>
                                    @elseif ($event->event_outcome === 'failure')
                                        <span class="text-danger"><i class="bi bi-x-circle"></i></span>
                                    @else
                                        <span class="text-muted"><i class="bi bi-question-circle"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ date('Y-m-d H:i', strtotime($event->event_datetime)) }}</small>
                                </td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- At-Risk Objects Alert -->
@if (!empty($atRiskObjects))
<div class="card border-danger mb-4">
    <div class="card-header bg-danger text-white">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ __('Objects Requiring Attention') }}
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Object') }}</th>
                    <th>{{ __('File') }}</th>
                    <th>{{ __('Issue') }}</th>
                    <th>{{ __('Detected') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($atRiskObjects as $obj)
                <tr>
                    <td>{{ $obj->title ?? 'Untitled' }}</td>
                    <td>
                        <a href="{{ url_for(['module' => 'preservation', 'action' => 'object', 'id' => $obj->id]) }}">
                            {{ $obj->filename ?? 'Unknown' }}
                        </a>
                    </td>
                    <td><span class="text-danger">{{ $obj->error_message ?? 'Fixity check failed' }}</span></td>
                    <td><small class="text-muted">{{ date('Y-m-d', strtotime($obj->checked_at)) }}</small></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
