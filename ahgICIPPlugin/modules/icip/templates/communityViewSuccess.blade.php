@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_dashboard') }}">ICIP</a></li>
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_communities') }}">Communities</a></li>
            <li class="breadcrumb-item active">{{ $community->name }}</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1>
                <i class="bi bi-people me-2"></i>
                {{ $community->name }}
            </h1>
            @if (!$community->is_active)
                <span class="badge bg-secondary">Inactive</span>
            @endif
        </div>
        <div>
            <a href="{{ url_for('@icip_community_edit?id=' . $id) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="{{ url_for('@icip_report_community?id=' . $id) }}" class="btn btn-outline-secondary">
                <i class="bi bi-graph-up me-1"></i> Full Report
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Basic Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Community Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl>
                                <dt>Language Group</dt>
                                <dd>{{ $community->language_group ?? '-' }}</dd>

                                <dt>Region</dt>
                                <dd>{{ $community->region ?? '-' }}</dd>

                                <dt>State/Territory</dt>
                                <dd>
                                    <span class="badge bg-secondary">{{ $community->state_territory }}</span>
                                    {{ $states[$community->state_territory] ?? '' }}
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            @if ($community->alternate_names)
                                <dl>
                                    <dt>Alternate Names</dt>
                                    <dd>
                                        @php
                                        $altNames = json_decode($community->alternate_names, true) ?? [];
                                        @endphp
                                        {{ implode(', ', $altNames) ?: '-' }}
                                    </dd>
                                </dl>
                            @endif

                            @if ($community->native_title_reference)
                                <dl>
                                    <dt>Native Title Reference</dt>
                                    <dd>{{ $community->native_title_reference }}</dd>
                                </dl>
                            @endif
                        </div>
                    </div>

                    @if ($community->notes)
                        <hr>
                        <h6>Notes</h6>
                        <p class="mb-0">{!! nl2br(e($community->notes)) !!}</p>
                    @endif
                </div>
            </div>

            <!-- Recent Consents -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Consent Records</h5>
                    <span class="badge bg-primary">{{ count($consents) }}</span>
                </div>
                <div class="card-body p-0">
                    @if (empty($consents))
                        <div class="p-4 text-center text-muted">
                            No consent records linked to this community
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Record</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($consents as $consent)
                                        <tr>
                                            <td>{{ $consent->object_title ?? 'Untitled' }}</td>
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
                                                    {{ ucwords(str_replace('_', ' ', $consent->consent_status)) }}
                                                </span>
                                            </td>
                                            <td>{{ $consent->consent_date ? date('j M Y', strtotime($consent->consent_date)) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Consultations -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Consultations</h5>
                    <span class="badge bg-primary">{{ count($consultations) }}</span>
                </div>
                <div class="card-body p-0">
                    @if (empty($consultations))
                        <div class="p-4 text-center text-muted">
                            No consultations recorded
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Summary</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($consultations as $consultation)
                                        <tr>
                                            <td>{{ date('j M Y', strtotime($consultation->consultation_date)) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $consultation->consultation_type)) }}</td>
                                            <td>{{ mb_substr($consultation->summary, 0, 50) . (strlen($consultation->summary) > 50 ? '...' : '') }}</td>
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
        </div>

        <div class="col-lg-4">
            <!-- Contact Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Contact Information</h5>
                </div>
                <div class="card-body">
                    @if ($community->contact_name)
                        <p class="mb-2">
                            <i class="bi bi-person me-2 text-muted"></i>
                            <strong>{{ $community->contact_name }}</strong>
                        </p>
                    @endif

                    @if ($community->contact_email)
                        <p class="mb-2">
                            <i class="bi bi-envelope me-2 text-muted"></i>
                            <a href="mailto:{{ $community->contact_email }}">
                                {{ $community->contact_email }}
                            </a>
                        </p>
                    @endif

                    @if ($community->contact_phone)
                        <p class="mb-2">
                            <i class="bi bi-telephone me-2 text-muted"></i>
                            <a href="tel:{{ $community->contact_phone }}">
                                {{ $community->contact_phone }}
                            </a>
                        </p>
                    @endif

                    @if ($community->contact_address)
                        <p class="mb-0">
                            <i class="bi bi-geo-alt me-2 text-muted"></i>
                            {!! nl2br(e($community->contact_address)) !!}
                        </p>
                    @endif

                    @if (!$community->contact_name && !$community->contact_email && !$community->contact_phone && !$community->contact_address)
                        <p class="text-muted mb-0">No contact information recorded</p>
                    @endif
                </div>
            </div>

            <!-- PBC Information -->
            @if ($community->prescribed_body_corporate)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Prescribed Body Corporate</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>{{ $community->prescribed_body_corporate }}</strong>
                        </p>
                        @if ($community->pbc_contact_email)
                            <p class="mb-0">
                                <i class="bi bi-envelope me-2 text-muted"></i>
                                <a href="mailto:{{ $community->pbc_contact_email }}">
                                    {{ $community->pbc_contact_email }}
                                </a>
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <a href="{{ url_for('@icip_consent_add') }}?community_id={{ $id }}" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-file-earmark-check me-1"></i> Add Consent Record
                    </a>
                    <a href="{{ url_for('@icip_consultation_add') }}?community_id={{ $id }}" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-chat-dots me-1"></i> Log Consultation
                    </a>
                    <a href="{{ url_for('@icip_report_community?id=' . $id) }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-graph-up me-1"></i> View Full Report
                    </a>
                </div>
            </div>

            <!-- Metadata -->
            <div class="card mt-4">
                <div class="card-body small text-muted">
                    <p class="mb-1">
                        <strong>Created:</strong>
                        {{ $community->created_at ? date('j M Y H:i', strtotime($community->created_at)) : '-' }}
                    </p>
                    <p class="mb-0">
                        <strong>Last Updated:</strong>
                        {{ $community->updated_at ? date('j M Y H:i', strtotime($community->updated_at)) : '-' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
