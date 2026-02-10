@extends('layouts.page')

@section('title')
<h1><i class="bi bi-shield-exclamation text-danger me-2"></i>{{ __('Virus Scanning') }}</h1>
@endsection

@section('content')

<!-- ClamAV Status -->
<div class="alert {{ $clamAvAvailable ? 'alert-success' : 'alert-warning' }} mb-4">
    <div class="d-flex align-items-center">
        <i class="bi {{ $clamAvAvailable ? 'bi-check-circle' : 'bi-exclamation-triangle' }} fs-3 me-3"></i>
        <div class="flex-grow-1">
            @if($clamAvAvailable)
                <strong>ClamAV is installed and available</strong>
                @if($clamAvVersion)
                <br><small class="text-muted">
                    Scanner: {{ $clamAvVersion['scanner'] }} |
                    Version: {{ $clamAvVersion['version'] }} |
                    Database: {{ $clamAvVersion['database'] }}
                </small>
                @endif
            @else
                <strong>ClamAV is not installed</strong>
                <br><small>Install with: <code>sudo apt install clamav clamav-daemon && sudo freshclam</code></small>
            @endif
        </div>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'index']) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Clean') }}</h6>
                        <h2 class="mb-0">{{ number_format($scanStats['clean'] ?? 0) }}</h2>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Infected') }}</h6>
                        <h2 class="mb-0">{{ number_format($scanStats['infected'] ?? 0) }}</h2>
                    </div>
                    <i class="bi bi-bug fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Errors') }}</h6>
                        <h2 class="mb-0">{{ number_format($scanStats['error'] ?? 0) }}</h2>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Not Scanned') }}</h6>
                        <h2 class="mb-0">{{ number_format($unscannedObjects ?? 0) }}</h2>
                    </div>
                    <i class="bi bi-question-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CLI Commands -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-terminal me-2"></i>{{ __('CLI Commands') }}
    </div>
    <div class="card-body">
        <p class="mb-2">Run virus scans from the command line:</p>
        <pre class="bg-dark text-light p-3 rounded mb-0"><code># Show ClamAV status
php symfony preservation:virus-scan --status

# Scan up to 100 new objects
php symfony preservation:virus-scan

# Scan specific object
php symfony preservation:virus-scan --object-id=123

# Scan 500 objects
php symfony preservation:virus-scan --limit=500</code></pre>
    </div>
</div>

<!-- Recent Scans Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul me-2"></i>{{ __('Recent Virus Scans') }}
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('File') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Threat') }}</th>
                    <th>{{ __('Scanner') }}</th>
                    <th>{{ __('Scanned') }}</th>
                    <th>{{ __('By') }}</th>
                </tr>
            </thead>
            <tbody>
                @if(empty($recentScans))
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">
                        {{ __('No virus scans performed yet') }}
                    </td>
                </tr>
                @else
                    @foreach($recentScans as $scan)
                    <tr>
                        <td>
                            <a href="{{ url_for(['module' => 'preservation', 'action' => 'object', 'id' => $scan->digital_object_id]) }}">
                                {{ substr($scan->filename ?? 'Unknown', 0, 40) }}
                            </a>
                        </td>
                        <td>
                            @if($scan->status === 'clean')
                                <span class="badge bg-success">Clean</span>
                            @elseif($scan->status === 'infected')
                                <span class="badge bg-danger">Infected</span>
                            @elseif($scan->status === 'error')
                                <span class="badge bg-warning">Error</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($scan->status) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($scan->threat_name)
                                <span class="text-danger">{{ $scan->threat_name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><small>{{ $scan->scanner_name ?? 'unknown' }}</small></td>
                        <td><small class="text-muted">{{ date('Y-m-d H:i', strtotime($scan->scanned_at)) }}</small></td>
                        <td><small>{{ $scan->scanned_by ?? 'system' }}</small></td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

@endsection
