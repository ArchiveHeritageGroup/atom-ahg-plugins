@extends('layouts.page')

@section('title')
<h1><i class="bi bi-file-earmark-binary text-primary me-2"></i>{{ __('Preservation Details') }}</h1>
@endsection

@section('content')

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'preservation', 'action' => 'index']) }}">{{ __('Preservation') }}</a></li>
        <li class="breadcrumb-item active">{{ $digitalObject->name ?? 'Object' }}</li>
    </ol>
</nav>

<!-- Object Info -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-info-circle me-2"></i>{{ __('Digital Object Information') }}
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="150">{{ __('ID') }}</th>
                        <td>{{ $digitalObject->id }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Filename') }}</th>
                        <td>{{ $digitalObject->name ?? 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Parent Object') }}</th>
                        <td>
                            @if ($digitalObject->slug)
                                <a href="{{ url_for(['module' => 'informationobject', 'slug' => $digitalObject->slug]) }}">
                                    {{ $digitalObject->object_title ?? 'View' }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('File Size') }}</th>
                        <td>{{ number_format($digitalObject->byte_size ?? 0) }} bytes</td>
                    </tr>
                    <tr>
                        <th>{{ __('MIME Type') }}</th>
                        <td>{{ $digitalObject->mime_type ?? 'Unknown' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                @if ($formatInfo)
                <div class="alert {{ $formatInfo->risk_level === 'low' ? 'alert-success' : ($formatInfo->risk_level === 'high' || $formatInfo->risk_level === 'critical' ? 'alert-danger' : 'alert-warning') }}">
                    <h6><i class="bi bi-file-code me-1"></i>{{ __('Format Information') }}</h6>
                    <p class="mb-1"><strong>{{ $formatInfo->format_name }}</strong></p>
                    <p class="mb-1">Risk Level: <strong>{{ ucfirst($formatInfo->risk_level ?? 'unknown') }}</strong></p>
                    @if ($formatInfo->is_preservation_format)
                        <span class="badge bg-success">{{ __('Preservation Format') }}</span>
                    @endif
                </div>
                @endif

                <div class="d-grid gap-2">
                    <button class="btn btn-primary" onclick="generateChecksums({{ $digitalObject->id }})">
                        <i class="bi bi-arrow-repeat me-1"></i>{{ __('Regenerate Checksums') }}
                    </button>
                    <button class="btn btn-outline-primary" onclick="verifyFixity({{ $digitalObject->id }})">
                        <i class="bi bi-check-circle me-1"></i>{{ __('Verify Fixity Now') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checksums -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-fingerprint me-2"></i>{{ __('Checksums') }}
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Algorithm') }}</th>
                    <th>{{ __('Value') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Generated') }}</th>
                    <th>{{ __('Last Verified') }}</th>
                </tr>
            </thead>
            <tbody>
                @if (empty($checksums))
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        {{ __('No checksums generated yet') }}
                    </td>
                </tr>
                @else
                    @foreach ($checksums as $cs)
                    <tr>
                        <td><strong>{{ strtoupper($cs->algorithm) }}</strong></td>
                        <td><code style="font-size: 0.8em;">{{ $cs->checksum_value }}</code></td>
                        <td>
                            @if ($cs->verification_status === 'valid')
                                <span class="badge bg-success">Valid</span>
                            @elseif ($cs->verification_status === 'invalid')
                                <span class="badge bg-danger">Invalid</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($cs->verification_status) }}</span>
                            @endif
                        </td>
                        <td><small>{{ date('Y-m-d H:i', strtotime($cs->generated_at)) }}</small></td>
                        <td><small>{{ $cs->verified_at ? date('Y-m-d H:i', strtotime($cs->verified_at)) : '-' }}</small></td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- Fixity History -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-clock-history me-2"></i>{{ __('Fixity Check History') }}
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Algorithm') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Checked By') }}</th>
                    <th>{{ __('Duration') }}</th>
                    <th>{{ __('Checked At') }}</th>
                </tr>
            </thead>
            <tbody>
                @if (empty($fixityHistory))
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        {{ __('No fixity checks performed yet') }}
                    </td>
                </tr>
                @else
                    @foreach ($fixityHistory as $check)
                    <tr>
                        <td>{{ strtoupper($check->algorithm) }}</td>
                        <td>
                            @if ($check->status === 'pass')
                                <span class="badge bg-success">Pass</span>
                            @elseif ($check->status === 'fail')
                                <span class="badge bg-danger">Fail</span>
                            @else
                                <span class="badge bg-warning">{{ ucfirst($check->status) }}</span>
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

<!-- Preservation Events -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-calendar-event me-2"></i>{{ __('Preservation Events (PREMIS)') }}
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Event Type') }}</th>
                    <th>{{ __('Detail') }}</th>
                    <th>{{ __('Outcome') }}</th>
                    <th>{{ __('Agent') }}</th>
                    <th>{{ __('Date/Time') }}</th>
                </tr>
            </thead>
            <tbody>
                @if (empty($events))
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        {{ __('No preservation events recorded') }}
                    </td>
                </tr>
                @else
                    @foreach ($events as $event)
                    <tr>
                        <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $event->event_type) }}</span></td>
                        <td>{{ substr($event->event_detail ?? '', 0, 50) }}</td>
                        <td>
                            @if ($event->event_outcome === 'success')
                                <span class="text-success"><i class="bi bi-check-circle"></i> Success</span>
                            @elseif ($event->event_outcome === 'failure')
                                <span class="text-danger"><i class="bi bi-x-circle"></i> Failure</span>
                            @else
                                <span class="text-muted">{{ ucfirst($event->event_outcome) }}</span>
                            @endif
                        </td>
                        <td><small>{{ $event->linking_agent_value ?? '-' }}</small></td>
                        <td><small>{{ date('Y-m-d H:i:s', strtotime($event->event_datetime)) }}</small></td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

<script {!! $csp_nonce !!}>
function generateChecksums(id) {
    if (!confirm('Generate new checksums for this object?')) return;

    fetch('/api/preservation/checksum/' + id + '/generate', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Checksums generated successfully');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(e => alert('Error: ' + e));
}

function verifyFixity(id) {
    fetch('/api/preservation/fixity/' + id + '/verify', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            let msg = 'Fixity verification complete:\n';
            for (let algo in data.results) {
                msg += algo.toUpperCase() + ': ' + data.results[algo].status + '\n';
            }
            alert(msg);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(e => alert('Error: ' + e));
}
</script>

@endsection
