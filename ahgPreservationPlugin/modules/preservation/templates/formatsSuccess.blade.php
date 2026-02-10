@extends('layouts.page')

@section('title')
<h1><i class="bi bi-file-code text-primary me-2"></i>{{ __('Format Registry') }}</h1>
@endsection

@section('content')

<div class="d-flex justify-content-between mb-4">
    <p class="text-muted mb-0">File formats and their preservation risk assessment based on PRONOM registry.</p>
    <a href="{{ url_for(['module' => 'preservation', 'action' => 'index']) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Dashboard') }}
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Format') }}</th>
                    <th>{{ __('MIME Type') }}</th>
                    <th>{{ __('Extension') }}</th>
                    <th>{{ __('PUID') }}</th>
                    <th>{{ __('Risk') }}</th>
                    <th>{{ __('Action') }}</th>
                    <th>{{ __('Usage') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($formats as $format)
                <tr>
                    <td>
                        <strong>{{ $format->format_name }}</strong>
                        @if($format->is_preservation_format)
                            <span class="badge bg-success ms-1">Preservation</span>
                        @endif
                    </td>
                    <td><code>{{ $format->mime_type }}</code></td>
                    <td>.{{ $format->extension ?? '-' }}</td>
                    <td><small>{{ $format->puid ?? '-' }}</small></td>
                    <td>
                        @php
                        $riskClass = match ($format->risk_level) {
                            'low' => 'success',
                            'medium' => 'warning',
                            'high' => 'danger',
                            'critical' => 'danger',
                            default => 'secondary'
                        };
                        @endphp
                        <span class="badge bg-{{ $riskClass }}">{{ ucfirst($format->risk_level) }}</span>
                    </td>
                    <td><small>{{ ucfirst($format->preservation_action ?? 'none') }}</small></td>
                    <td>
                        @php
                        $count = $formatCounts[$format->id]->count ?? 0;
                        @endphp
                        {{ $count > 0 ? number_format($count) : '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
