@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_dashboard') }}">ICIP</a></li>
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_consent_list') }}">Consent Records</a></li>
            <li class="breadcrumb-item active">View Consent</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1>
                <i class="bi bi-file-earmark-check me-2"></i>
                Consent Record
            </h1>
            <p class="text-muted mb-0">
                {{ $consent->object_title ?? 'Untitled' }}
            </p>
        </div>
        <a href="{{ url_for('@icip_consent_edit?id=' . $id) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Status Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-1">Consent Status</h5>
                            @php
                            $statusClass = match ($consent->consent_status) {
                                'full_consent' => 'bg-success',
                                'conditional_consent', 'restricted_consent' => 'bg-info',
                                'pending_consultation', 'consultation_in_progress' => 'bg-warning text-dark',
                                'denied' => 'bg-danger',
                                'not_required' => 'bg-light text-dark',
                                default => 'bg-secondary'
                            };
                            @endphp
                            <span class="badge {{ $statusClass }} fs-6">
                                {{ $statusOptions[$consent->consent_status] ?? ucwords(str_replace('_', ' ', $consent->consent_status)) }}
                            </span>
                        </div>
                        <div class="col-md-6 text-md-end">
                            @if ($consent->consent_date)
                                <small class="text-muted">Granted:</small>
                                <strong>{{ date('j M Y', strtotime($consent->consent_date)) }}</strong>
                            @endif
                            @if ($consent->consent_expiry_date)
                                <br>
                                @php
                                $expiryDate = new DateTime($consent->consent_expiry_date);
                                $today = new DateTime();
                                $isExpired = $expiryDate < $today;
                                @endphp
                                <small class="text-muted">Expires:</small>
                                <strong class="{{ $isExpired ? 'text-danger' : '' }}">
                                    {{ date('j M Y', strtotime($consent->consent_expiry_date)) }}
                                    @if ($isExpired)
                                        <span class="badge bg-danger">Expired</span>
                                    @endif
                                </strong>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scope -->
            @php
            $scopeArray = [];
            if (!empty($consent->consent_scope)) {
                $scopeArray = json_decode($consent->consent_scope, true) ?? [];
            }
            @endphp
            @if (!empty($scopeArray))
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Consent Scope</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($scopeOptions as $value => $label)
                                <div class="col-md-4 mb-2">
                                    @if (in_array($value, $scopeArray))
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    @else
                                        <i class="bi bi-x-circle text-muted me-2"></i>
                                    @endif
                                    {{ $label }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Conditions -->
            @if ($consent->conditions)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Conditions</h5>
                    </div>
                    <div class="card-body">
                        {!! nl2br(e($consent->conditions)) !!}
                    </div>
                </div>
            @endif

            <!-- Restrictions -->
            @if ($consent->restrictions)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Restrictions</h5>
                    </div>
                    <div class="card-body">
                        {!! nl2br(e($consent->restrictions)) !!}
                    </div>
                </div>
            @endif

            <!-- Notes -->
            @if ($consent->notes)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Notes</h5>
                    </div>
                    <div class="card-body">
                        {!! nl2br(e($consent->notes)) !!}
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Details</h5>
                </div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt>Information Object</dt>
                        <dd>
                            @if ($consent->slug)
                                <a href="{{ url_for('@icip_object?slug=' . $consent->slug) }}">
                                    {{ $consent->object_title ?? 'Untitled' }}
                                </a>
                            @else
                                ID: {{ $consent->information_object_id }}
                            @endif
                        </dd>

                        <dt>Community</dt>
                        <dd>
                            @if ($consent->community_id)
                                <a href="{{ url_for('@icip_community_view?id=' . $consent->community_id) }}">
                                    {{ $consent->community_name }}
                                </a>
                            @else
                                <span class="text-muted">Not specified</span>
                            @endif
                        </dd>

                        @if ($consent->consent_granted_by)
                            <dt>Granted By</dt>
                            <dd>{{ $consent->consent_granted_by }}</dd>
                        @endif

                        @if ($consent->consent_document_path)
                            <dt>Document</dt>
                            <dd>
                                <a href="{{ $consent->consent_document_path }}" target="_blank">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>
                                    View Document
                                </a>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @if ($consent->slug)
                        <a href="{{ url_for('@icip_object?slug=' . $consent->slug) }}" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-archive me-1"></i> View ICIP Summary
                        </a>
                    @endif
                    @if ($consent->community_id)
                        <a href="{{ url_for('@icip_consultation_add') }}?community_id={{ $consent->community_id }}&object_id={{ $consent->information_object_id }}" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-chat-dots me-1"></i> Log Consultation
                        </a>
                    @endif
                </div>
            </div>

            <!-- Metadata -->
            <div class="card">
                <div class="card-body small text-muted">
                    <p class="mb-1">
                        <strong>Created:</strong>
                        {{ $consent->created_at ? date('j M Y H:i', strtotime($consent->created_at)) : '-' }}
                    </p>
                    <p class="mb-0">
                        <strong>Last Updated:</strong>
                        {{ $consent->updated_at ? date('j M Y H:i', strtotime($consent->updated_at)) : '-' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
