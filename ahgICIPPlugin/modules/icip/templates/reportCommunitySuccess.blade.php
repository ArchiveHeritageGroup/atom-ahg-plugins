@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_dashboard') }}">ICIP</a></li>
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_reports') }}">Reports</a></li>
            <li class="breadcrumb-item active">{{ $community->name }}</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1>
                <i class="bi bi-people me-2"></i>
                {{ $community->name }}
            </h1>
            <p class="text-muted mb-0">
                {{ $states[$community->state_territory] ?? $community->state_territory }}
                @if ($community->language_group)
                    &bull; {{ $community->language_group }}
                @endif
            </p>
        </div>
        <div>
            <a href="{{ url_for('@icip_community_view?id=' . $id) }}" class="btn btn-outline-primary">
                <i class="bi bi-eye me-1"></i> View Community
            </a>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-primary">{{ count($consents) }}</div>
                    <div class="text-muted">Consent Records</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-success">{{ count($consultations) }}</div>
                    <div class="text-muted">Consultations</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-warning">{{ count($notices) }}</div>
                    <div class="text-muted">Cultural Notices</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-info">{{ count($labels) }}</div>
                    <div class="text-muted">TK Labels</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Consent Records -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Consent Records</h5>
        </div>
        <div class="card-body p-0">
            @if (empty($consents))
                <div class="p-4 text-center text-muted">No consent records linked to this community</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Record</th>
                                <th>Status</th>
                                <th>Consent Date</th>
                                <th>Expiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($consents as $consent)
                                <tr>
                                    <td>
                                        @if ($consent->slug)
                                            <a href="{{ url_for('@icip_object?slug=' . $consent->slug) }}">
                                                {{ $consent->object_title ?? 'Untitled' }}
                                            </a>
                                        @else
                                            {{ $consent->object_title ?? 'Untitled' }}
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                        $statusClass = match ($consent->consent_status) {
                                            'full_consent' => 'bg-success',
                                            'conditional_consent', 'restricted_consent' => 'bg-info',
                                            'pending_consultation', 'consultation_in_progress' => 'bg-warning text-dark',
                                            'denied' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ $statusOptions[$consent->consent_status] ?? ucwords(str_replace('_', ' ', $consent->consent_status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $consent->consent_date ? date('j M Y', strtotime($consent->consent_date)) : '-' }}</td>
                                    <td>{{ $consent->consent_expiry_date ? date('j M Y', strtotime($consent->consent_expiry_date)) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Consultations -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Consultation History</h5>
        </div>
        <div class="card-body p-0">
            @if (empty($consultations))
                <div class="p-4 text-center text-muted">No consultations recorded</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Summary</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($consultations as $consultation)
                                <tr>
                                    <td>{{ date('j M Y', strtotime($consultation->consultation_date)) }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $consultation->consultation_type)) }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $consultation->consultation_method)) }}</td>
                                    <td>{{ mb_substr($consultation->summary, 0, 80) . (strlen($consultation->summary) > 80 ? '...' : '') }}</td>
                                    <td>
                                        @php
                                        $statusClass = match ($consultation->status) {
                                            'completed' => 'bg-success',
                                            'scheduled' => 'bg-info',
                                            'follow_up_required' => 'bg-warning text-dark',
                                            default => 'bg-secondary'
                                        };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ ucwords(str_replace('_', ' ', $consultation->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Cultural Notices -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Cultural Notices</h5>
                </div>
                <div class="card-body p-0">
                    @if (empty($notices))
                        <div class="p-4 text-center text-muted">No cultural notices</div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($notices as $notice)
                                <li class="list-group-item">
                                    <strong>{{ $notice->notice_name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $notice->object_title ?? 'Untitled' }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <!-- TK Labels -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">TK Labels</h5>
                </div>
                <div class="card-body p-0">
                    @if (empty($labels))
                        <div class="p-4 text-center text-muted">No TK labels</div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($labels as $label)
                                <li class="list-group-item">
                                    <span class="badge bg-secondary me-2">{{ strtoupper($label->code) }}</span>
                                    {{ $label->label_name }}
                                    <br>
                                    <small class="text-muted">{{ $label->object_title ?? 'Untitled' }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
