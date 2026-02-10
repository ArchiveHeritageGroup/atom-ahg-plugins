@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_dashboard') }}">ICIP</a></li>
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_reports') }}">Reports</a></li>
            <li class="breadcrumb-item active">Consent Expiry</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="bi bi-calendar-x text-danger me-2"></i>
            Expiring Consents
        </h1>
        <div>
            <a href="?days=30" class="btn btn-sm {{ $days == 30 ? 'btn-primary' : 'btn-outline-primary' }}">30 days</a>
            <a href="?days=60" class="btn btn-sm {{ $days == 60 ? 'btn-primary' : 'btn-outline-primary' }}">60 days</a>
            <a href="?days=90" class="btn btn-sm {{ $days == 90 ? 'btn-primary' : 'btn-outline-primary' }}">90 days</a>
            <a href="?days=180" class="btn btn-sm {{ $days == 180 ? 'btn-primary' : 'btn-outline-primary' }}">180 days</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>{{ count($records) }}</strong> consents expiring within {{ $days }} days
        </div>
        <div class="card-body p-0">
            @if (empty($records))
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <p class="mb-0 mt-2">No consents expiring within {{ $days }} days</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Record</th>
                                <th>Community</th>
                                <th>Expiry Date</th>
                                <th>Days Remaining</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($records as $record)
                                @php
                                $expiryDate = new DateTime($record->consent_expiry_date);
                                $today = new DateTime();
                                $daysLeft = $today->diff($expiryDate)->days;
                                @endphp
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
                                    <td>{{ $record->community_name ?? 'Not specified' }}</td>
                                    <td>
                                        <strong>{{ date('j M Y', strtotime($record->consent_expiry_date)) }}</strong>
                                    </td>
                                    <td>
                                        @php
                                        $urgencyClass = 'bg-success';
                                        if ($daysLeft <= 30) {
                                            $urgencyClass = 'bg-danger';
                                        } elseif ($daysLeft <= 60) {
                                            $urgencyClass = 'bg-warning text-dark';
                                        }
                                        @endphp
                                        <span class="badge {{ $urgencyClass }}">
                                            {{ $daysLeft }} days
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ url_for('@icip_consent_edit?id=' . $record->id) }}" class="btn btn-outline-primary" title="Edit/Renew Consent">
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

    <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Renewal Process:</strong> Contact the relevant community to discuss consent renewal before expiry. Log the consultation and update the consent record with new dates.
    </div>
</div>
@endsection
