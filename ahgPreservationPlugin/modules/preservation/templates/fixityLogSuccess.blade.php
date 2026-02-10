@extends('layouts.page')

@section('title')
<h1><i class="bi bi-list-check text-primary me-2"></i>{{ __('Fixity Check Log') }}</h1>
@endsection

@section('content')

<!-- Status Filter -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="btn-group">
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'fixityLog']) }}"
           class="btn btn-{{ !$currentStatus ? 'primary' : 'outline-primary' }}">
            All ({{ $statusCounts['all'] }})
        </a>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'fixityLog', 'status' => 'pass']) }}"
           class="btn btn-{{ $currentStatus === 'pass' ? 'success' : 'outline-success' }}">
            Pass ({{ $statusCounts['pass'] }})
        </a>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'fixityLog', 'status' => 'fail']) }}"
           class="btn btn-{{ $currentStatus === 'fail' ? 'danger' : 'outline-danger' }}">
            Fail ({{ $statusCounts['fail'] }})
        </a>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'fixityLog', 'status' => 'error']) }}"
           class="btn btn-{{ $currentStatus === 'error' ? 'warning' : 'outline-warning' }}">
            Error ({{ $statusCounts['error'] }})
        </a>
    </div>
    <a href="{{ url_for(['module' => 'preservation', 'action' => 'index']) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
    </a>
</div>

<!-- Fixity Log Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Object') }}</th>
                    <th>{{ __('File') }}</th>
                    <th>{{ __('Algorithm') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Checked By') }}</th>
                    <th>{{ __('Duration') }}</th>
                    <th>{{ __('Checked At') }}</th>
                </tr>
            </thead>
            <tbody>
                @if (empty($checks))
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        {{ __('No fixity checks found') }}
                    </td>
                </tr>
                @else
                    @foreach ($checks as $check)
                    <tr>
                        <td>
                            <a href="{{ url_for(['module' => 'preservation', 'action' => 'object', 'id' => $check->digital_object_id]) }}">
                                {{ substr($check->object_title ?? 'Untitled', 0, 30) }}
                            </a>
                        </td>
                        <td>{{ substr($check->filename ?? 'Unknown', 0, 25) }}</td>
                        <td><code>{{ strtoupper($check->algorithm) }}</code></td>
                        <td>
                            @if ($check->status === 'pass')
                                <span class="badge bg-success">Pass</span>
                            @elseif ($check->status === 'fail')
                                <span class="badge bg-danger" title="{{ $check->error_message ?? '' }}">Fail</span>
                            @elseif ($check->status === 'error')
                                <span class="badge bg-warning" title="{{ $check->error_message ?? '' }}">Error</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($check->status) }}</span>
                            @endif
                        </td>
                        <td>{{ $check->checked_by }}</td>
                        <td>{{ $check->duration_ms }}ms</td>
                        <td>{{ date('Y-m-d H:i:s', strtotime($check->checked_at)) }}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

@endsection
