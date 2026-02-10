@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'informationobject', 'slug' => $object->slug]) }}">{{ $object->title ?? $object->identifier ?? 'Record' }}</a></li>
            <li class="breadcrumb-item active">ICIP</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1>
                <i class="bi bi-shield-check me-2"></i>
                ICIP Information
            </h1>
            <p class="text-muted mb-0">{{ $object->title ?? 'Untitled' }}</p>
        </div>
        <a href="{{ url_for(['module' => 'informationobject', 'slug' => $object->slug]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Record
        </a>
    </div>

    @if ($sf_user->hasFlash('notice'))
        <div class="alert alert-success alert-dismissible fade show">
            {!! $sf_user->getFlash('notice') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Summary Card -->
    @if ($summary && $summary->has_icip_content)
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>This record has ICIP content</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="fs-4">{{ $summary->consent_status ? ucwords(str_replace('_', ' ', $summary->consent_status)) : 'Unknown' }}</div>
                        <div class="text-muted small">Consent Status</div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="fs-4">{{ $summary->cultural_notice_count ?? 0 }}</div>
                        <div class="text-muted small">Cultural Notices</div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="fs-4">{{ $summary->tk_label_count ?? 0 }}</div>
                        <div class="text-muted small">TK Labels</div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="fs-4">{{ $summary->restriction_count ?? 0 }}</div>
                        <div class="text-muted small">Restrictions</div>
                    </div>
                </div>
                @if ($summary->requires_acknowledgement)
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        This record requires user acknowledgement before viewing.
                    </div>
                @endif
                @if ($summary->blocks_access)
                    <div class="alert alert-danger mt-3 mb-0">
                        <i class="bi bi-lock me-2"></i>
                        Access to this record is blocked by cultural notices.
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-secondary">
            <i class="bi bi-info-circle me-2"></i>
            No ICIP content has been recorded for this item yet.
        </div>
    @endif

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="{{ url_for('@icip_object?slug=' . $object->slug) }}">Overview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ url_for('@icip_object_consent?slug=' . $object->slug) }}">
                Consent <span class="badge bg-secondary">{{ count($consents) }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ url_for('@icip_object_notices?slug=' . $object->slug) }}">
                Notices <span class="badge bg-secondary">{{ count($notices) }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ url_for('@icip_object_labels?slug=' . $object->slug) }}">
                TK Labels <span class="badge bg-secondary">{{ count($labels) }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ url_for('@icip_object_restrictions?slug=' . $object->slug) }}">
                Restrictions <span class="badge bg-secondary">{{ count($restrictions) }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ url_for('@icip_object_consultations?slug=' . $object->slug) }}">
                Consultations <span class="badge bg-secondary">{{ count($consultations) }}</span>
            </a>
        </li>
    </ul>

    <div class="row">
        <div class="col-lg-8">
            <!-- Consent Summary -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Consent Status</h5>
                    <a href="{{ url_for('@icip_object_consent?slug=' . $object->slug) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Add Consent
                    </a>
                </div>
                <div class="card-body">
                    @if (empty($consents))
                        <p class="text-muted mb-0">No consent records. <a href="{{ url_for('@icip_object_consent?slug=' . $object->slug) }}">Add consent record</a></p>
                    @else
                        @php $latest = $consents[0]; @endphp
                        <div class="d-flex align-items-center mb-3">
                            @php
                            $statusClass = match ($latest->consent_status) {
                                'full_consent' => 'bg-success',
                                'conditional_consent', 'restricted_consent' => 'bg-info',
                                'pending_consultation', 'consultation_in_progress' => 'bg-warning text-dark',
                                'denied' => 'bg-danger',
                                'not_required' => 'bg-light text-dark',
                                default => 'bg-secondary'
                            };
                            @endphp
                            <span class="badge {{ $statusClass }} fs-6 me-3">
                                {{ $statusOptions[$latest->consent_status] ?? ucwords(str_replace('_', ' ', $latest->consent_status)) }}
                            </span>
                            @if ($latest->community_name)
                                <span class="text-muted">Community: {{ $latest->community_name }}</span>
                            @endif
                        </div>
                        @if ($latest->consent_date)
                            <p class="mb-1"><strong>Consent Date:</strong> {{ date('j M Y', strtotime($latest->consent_date)) }}</p>
                        @endif
                        @if ($latest->consent_expiry_date)
                            @php
                            $expiryDate = new DateTime($latest->consent_expiry_date);
                            $today = new DateTime();
                            $isExpired = $expiryDate < $today;
                            @endphp
                            <p class="mb-0">
                                <strong>Expiry:</strong>
                                <span class="{{ $isExpired ? 'text-danger' : '' }}">
                                    {{ date('j M Y', strtotime($latest->consent_expiry_date)) }}
                                    @if ($isExpired)
                                        <span class="badge bg-danger">Expired</span>
                                    @endif
                                </span>
                            </p>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Cultural Notices -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Cultural Notices</h5>
                    <a href="{{ url_for('@icip_object_notices?slug=' . $object->slug) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Add Notice
                    </a>
                </div>
                <div class="card-body">
                    @if (empty($notices))
                        <p class="text-muted mb-0">No cultural notices applied.</p>
                    @else
                        @foreach ($notices as $notice)
                            <div class="icip-notice icip-notice-{{ $notice->severity }} mb-3 p-3 rounded">
                                @php
                                $severityIcon = match ($notice->severity) {
                                    'critical' => 'bi-exclamation-triangle-fill text-danger',
                                    'warning' => 'bi-exclamation-circle text-warning',
                                    default => 'bi-info-circle text-info'
                                };
                                @endphp
                                <div class="d-flex">
                                    <i class="bi {{ $severityIcon }} fs-4 me-3"></i>
                                    <div>
                                        <strong>{{ $notice->notice_name }}</strong>
                                        @if ($notice->requires_acknowledgement)
                                            <span class="badge bg-warning text-dark ms-2">Requires Acknowledgement</span>
                                        @endif
                                        <p class="mb-0 mt-1">
                                            {{ $notice->custom_text ?? $notice->default_text ?? '' }}
                                        </p>
                                        @if ($notice->community_name)
                                            <small class="text-muted">Requested by: {{ $notice->community_name }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- TK Labels -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">TK Labels</h5>
                    <a href="{{ url_for('@icip_object_labels?slug=' . $object->slug) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Add Label
                    </a>
                </div>
                <div class="card-body">
                    @if (empty($labels))
                        <p class="text-muted mb-0">No TK labels applied.</p>
                    @else
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($labels as $label)
                                <div class="icip-tk-label-card p-2 border rounded" style="min-width: 200px;">
                                    <div class="d-flex align-items-center">
                                        <span class="badge {{ $label->category === 'TK' ? 'icip-tk-label' : 'icip-bc-label' }} me-2">
                                            {{ strtoupper($label->label_code) }}
                                        </span>
                                        <div>
                                            <strong>{{ $label->label_name }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                Applied by: {{ ucfirst($label->applied_by) }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Access Restrictions -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Restrictions</h5>
                    <a href="{{ url_for('@icip_object_restrictions?slug=' . $object->slug) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
                <div class="card-body">
                    @if (empty($restrictions))
                        <p class="text-muted mb-0">No access restrictions.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($restrictions as $restriction)
                                <li class="mb-2">
                                    <i class="bi bi-lock-fill text-danger me-2"></i>
                                    {{ $restrictionTypes[$restriction->restriction_type] ?? ucwords(str_replace('_', ' ', $restriction->restriction_type)) }}
                                    @if ($restriction->override_security_clearance)
                                        <span class="badge bg-danger ms-1" title="Overrides security clearance">Override</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <!-- Recent Consultations -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Consultations</h5>
                    <a href="{{ url_for('@icip_object_consultations?slug=' . $object->slug) }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @if (empty($consultations))
                        <p class="text-muted mb-0">No consultations recorded.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach (array_slice($consultations, 0, 3) as $consultation)
                                <li class="mb-2 pb-2 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $consultation->community_name }}</strong>
                                        <small class="text-muted">{{ date('j M Y', strtotime($consultation->consultation_date)) }}</small>
                                    </div>
                                    <small>{{ ucwords(str_replace('_', ' ', $consultation->consultation_type)) }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="{{ url_for('@icip_consultation_add') }}?object_id={{ $object->id }}" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-chat-dots me-1"></i> Log Consultation
                    </a>
                    <a href="{{ url_for('@icip_consent_add') }}?object_id={{ $object->id }}" class="btn btn-outline-primary w-100">
                        <i class="bi bi-file-earmark-check me-1"></i> Add Consent Record
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style {!! $csp_nonce !!}>
.icip-notice-critical {
    background-color: #f8d7da;
    border-left: 4px solid #dc3545;
}
.icip-notice-warning {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}
.icip-notice-info {
    background-color: #cff4fc;
    border-left: 4px solid #0dcaf0;
}
.icip-tk-label {
    background-color: #8B4513;
    color: white;
}
.icip-bc-label {
    background-color: #228B22;
    color: white;
}
</style>
@endsection
