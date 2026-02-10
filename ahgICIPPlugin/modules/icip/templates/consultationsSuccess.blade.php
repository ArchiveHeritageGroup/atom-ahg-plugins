@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_dashboard') }}">ICIP</a></li>
            <li class="breadcrumb-item active">Consultations</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="bi bi-chat-dots me-2"></i>
            Consultation Log
        </h1>
        <a href="{{ url_for('@icip_consultation_add') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Log Consultation
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="initial_contact" {{ ($filters['type'] ?? '') === 'initial_contact' ? 'selected' : '' }}>Initial Contact</option>
                        <option value="consent_request" {{ ($filters['type'] ?? '') === 'consent_request' ? 'selected' : '' }}>Consent Request</option>
                        <option value="access_request" {{ ($filters['type'] ?? '') === 'access_request' ? 'selected' : '' }}>Access Request</option>
                        <option value="repatriation" {{ ($filters['type'] ?? '') === 'repatriation' ? 'selected' : '' }}>Repatriation</option>
                        <option value="digitisation" {{ ($filters['type'] ?? '') === 'digitisation' ? 'selected' : '' }}>Digitisation</option>
                        <option value="exhibition" {{ ($filters['type'] ?? '') === 'exhibition' ? 'selected' : '' }}>Exhibition</option>
                        <option value="publication" {{ ($filters['type'] ?? '') === 'publication' ? 'selected' : '' }}>Publication</option>
                        <option value="research" {{ ($filters['type'] ?? '') === 'research' ? 'selected' : '' }}>Research</option>
                        <option value="general" {{ ($filters['type'] ?? '') === 'general' ? 'selected' : '' }}>General</option>
                        <option value="follow_up" {{ ($filters['type'] ?? '') === 'follow_up' ? 'selected' : '' }}>Follow Up</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Community</label>
                    <select name="community_id" class="form-select">
                        <option value="">All Communities</option>
                        @foreach ($communities as $community)
                            <option value="{{ $community->id }}" {{ ($filters['community_id'] ?? '') == $community->id ? 'selected' : '' }}>
                                {{ $community->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="scheduled" {{ ($filters['status'] ?? '') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="follow_up_required" {{ ($filters['status'] ?? '') === 'follow_up_required' ? 'selected' : '' }}>Follow Up Required</option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ url_for('@icip_consultations') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header">
            <strong>{{ count($consultations) }}</strong> consultations found
        </div>
        <div class="card-body p-0">
            @if (empty($consultations))
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-chat-dots fs-1"></i>
                    <p class="mb-0 mt-2">No consultations found</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Community</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Summary</th>
                                <th>Status</th>
                                <th>Follow-up</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($consultations as $consultation)
                                <tr>
                                    <td>{{ date('j M Y', strtotime($consultation->consultation_date)) }}</td>
                                    <td>{{ $consultation->community_name }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ ucwords(str_replace('_', ' ', $consultation->consultation_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                        $methodIcon = match ($consultation->consultation_method) {
                                            'in_person' => 'bi-person',
                                            'phone' => 'bi-telephone',
                                            'video' => 'bi-camera-video',
                                            'email' => 'bi-envelope',
                                            'letter' => 'bi-envelope-paper',
                                            default => 'bi-chat'
                                        };
                                        @endphp
                                        <i class="bi {{ $methodIcon }}" title="{{ ucwords(str_replace('_', ' ', $consultation->consultation_method)) }}"></i>
                                        {{ ucwords(str_replace('_', ' ', $consultation->consultation_method)) }}
                                    </td>
                                    <td>
                                        {{ mb_substr($consultation->summary, 0, 60) . (strlen($consultation->summary) > 60 ? '...' : '') }}
                                        @if ($consultation->object_title)
                                            <br><small class="text-muted">Re: {{ $consultation->object_title }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                        $statusClass = match ($consultation->status) {
                                            'completed' => 'bg-success',
                                            'scheduled' => 'bg-info',
                                            'cancelled' => 'bg-secondary',
                                            'follow_up_required' => 'bg-warning text-dark',
                                            default => 'bg-secondary'
                                        };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ ucwords(str_replace('_', ' ', $consultation->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($consultation->follow_up_date)
                                            @php
                                            $followUpDate = new DateTime($consultation->follow_up_date);
                                            $today = new DateTime();
                                            $isOverdue = $followUpDate < $today && $consultation->status === 'follow_up_required';
                                            @endphp
                                            <span class="{{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                                                {{ date('j M Y', strtotime($consultation->follow_up_date)) }}
                                                @if ($isOverdue)
                                                    <i class="bi bi-exclamation-circle"></i>
                                                @endif
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ url_for('@icip_consultation_view?id=' . $consultation->id) }}" class="btn btn-outline-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ url_for('@icip_consultation_edit?id=' . $consultation->id) }}" class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
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
