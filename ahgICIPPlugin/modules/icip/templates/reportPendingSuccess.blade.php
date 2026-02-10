@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_dashboard') }}">ICIP</a></li>
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_reports') }}">Reports</a></li>
            <li class="breadcrumb-item active">Pending Consultation</li>
        </ol>
    </nav>

    <h1 class="mb-4">
        <i class="bi bi-clock-history text-warning me-2"></i>
        Records Pending Consultation
    </h1>

    <div class="card">
        <div class="card-header">
            <strong>{{ count($records) }}</strong> records require consultation
        </div>
        <div class="card-body p-0">
            @if (empty($records))
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <p class="mb-0 mt-2">No records pending consultation</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Record</th>
                                <th>Identifier</th>
                                <th>Community</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($records as $record)
                                <tr>
                                    <td>
                                        @if ($record->slug)
                                            <a href="{{ url_for('@icip_object?slug=' . $record->slug) }}">
                                                {{ $record->object_title ?? 'Untitled' }}
                                            </a>
                                        @else
                                            {{ $record->object_title ?? 'Untitled' }}
                                        @endif
                                    </td>
                                    <td>{{ $record->identifier ?? '-' }}</td>
                                    <td>{{ $record->community_name ?? 'Not specified' }}</td>
                                    <td>
                                        @php
                                        $statusClass = match ($record->consent_status) {
                                            'pending_consultation' => 'bg-warning text-dark',
                                            'consultation_in_progress' => 'bg-info',
                                            'unknown' => 'bg-secondary',
                                            default => 'bg-secondary'
                                        };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ $statusOptions[$record->consent_status] ?? ucwords(str_replace('_', ' ', $record->consent_status)) }}
                                        </span>
                                    </td>
                                    <td>{{ date('j M Y', strtotime($record->created_at)) }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ url_for('@icip_consent_edit?id=' . $record->id) }}" class="btn btn-outline-primary" title="Edit Consent">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @if ($record->community_id)
                                                <a href="{{ url_for('@icip_consultation_add') }}?community_id={{ $record->community_id }}&object_id={{ $record->information_object_id }}" class="btn btn-outline-success" title="Log Consultation">
                                                    <i class="bi bi-chat-dots"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
