@extends('layouts.page')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'index']) }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="h2"><i class="fas fa-exclamation-circle me-2"></i>{{ __('Breach Register') }}</span>
        </div>
        <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'breachAdd']) }}" class="btn btn-danger">
            <i class="fas fa-plus me-1"></i>{{ __('Report Breach') }}
        </a>
    </div>

    @if($sf_user->hasFlash('success'))
    <div class="alert alert-success">{{ $sf_user->getFlash('success') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Severity') }}</th>
                        <th>{{ __('Detected') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Regulator Notified') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @if($breaches->isEmpty())
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No breaches recorded') }}</td></tr>
                    @else
                    @foreach($breaches as $breach)
                    @php
$severityClasses = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];
                    $statusClasses = ['detected' => 'danger', 'investigating' => 'warning', 'contained' => 'info', 'resolved' => 'success', 'closed' => 'secondary'];
@endphp
                    <tr>
                        <td>
                            <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'breachView', 'id' => $breach->id]) }}">
                                <strong>{{ $breach->reference_number }}</strong>
                            </a>
                        </td>
                        <td>{{ ucfirst($breach->breach_type) }}</td>
                        <td>
                            <span class="badge bg-{{ $severityClasses[$breach->severity] ?? 'secondary' }}">
                                {{ ucfirst($breach->severity) }}
                            </span>
                        </td>
                        <td>{{ $breach->detected_date }}</td>
                        <td>
                            <span class="badge bg-{{ $statusClasses[$breach->status] ?? 'secondary' }}">
                                {{ ucfirst($breach->status) }}
                            </span>
                        </td>
                        <td>
                            @if($breach->regulator_notified)
                            <span class="text-success"><i class="fas fa-check"></i> {{ $breach->regulator_notified_date }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'breachView', 'id' => $breach->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
