@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_dashboard') }}">ICIP</a></li>
            <li class="breadcrumb-item active">Reports</li>
        </ol>
    </nav>

    <h1 class="mb-4">
        <i class="bi bi-graph-up me-2"></i>
        ICIP Reports
    </h1>

    <div class="row">
        <div class="col-lg-8">
            <!-- Overview Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Overview Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="display-5 text-primary">{{ number_format($stats['total_icip_objects'] ?? 0) }}</div>
                            <div class="text-muted">Records with ICIP</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="display-5 text-success">{{ number_format($stats['total_communities'] ?? 0) }}</div>
                            <div class="text-muted">Communities</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="display-5 text-info">{{ number_format($stats['tk_labels_applied'] ?? 0) }}</div>
                            <div class="text-muted">TK Labels</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="display-5 text-warning">{{ number_format($stats['active_restrictions'] ?? 0) }}</div>
                            <div class="text-muted">Active Restrictions</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Consent by Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Consent Status Distribution</h5>
                </div>
                <div class="card-body">
                    @if (empty($consentByStatus))
                        <p class="text-muted">No consent records yet</p>
                    @else
                        @php
                        $total = array_sum(array_column($consentByStatus, 'count'));
                        $statusLabels = [
                            'not_required' => ['label' => 'Not Required', 'class' => 'bg-secondary'],
                            'pending_consultation' => ['label' => 'Pending Consultation', 'class' => 'bg-warning'],
                            'consultation_in_progress' => ['label' => 'In Progress', 'class' => 'bg-info'],
                            'conditional_consent' => ['label' => 'Conditional', 'class' => 'bg-primary'],
                            'full_consent' => ['label' => 'Full Consent', 'class' => 'bg-success'],
                            'restricted_consent' => ['label' => 'Restricted', 'class' => 'bg-info'],
                            'denied' => ['label' => 'Denied', 'class' => 'bg-danger'],
                            'unknown' => ['label' => 'Unknown', 'class' => 'bg-secondary'],
                        ];
                        @endphp
                        @foreach ($consentByStatus as $status)
                            @php
                            $info = $statusLabels[$status->consent_status] ?? ['label' => ucwords(str_replace('_', ' ', $status->consent_status)), 'class' => 'bg-secondary'];
                            $percent = $total > 0 ? round(($status->count / $total) * 100) : 0;
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ $info['label'] }}</span>
                                    <span>{{ $status->count }} ({{ $percent }}%)</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar {{ $info['class'] }}" role="progressbar" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Records by Community -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Records by Community</h5>
                </div>
                <div class="card-body p-0">
                    @if (empty($recordsByCommunity))
                        <div class="p-4 text-center text-muted">No community records yet</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Community</th>
                                        <th>State</th>
                                        <th>Records</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recordsByCommunity as $community)
                                        <tr>
                                            <td>
                                                <a href="{{ url_for('@icip_community_view?id=' . $community->id) }}">
                                                    {{ $community->name }}
                                                </a>
                                            </td>
                                            <td><span class="badge bg-secondary">{{ $community->state_territory }}</span></td>
                                            <td><strong>{{ $community->record_count }}</strong></td>
                                            <td>
                                                <a href="{{ url_for('@icip_report_community?id=' . $community->id) }}" class="btn btn-sm btn-outline-primary">
                                                    View Report
                                                </a>
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
            <!-- Report Links -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Available Reports</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ url_for('@icip_report_pending') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-clock-history text-warning me-2"></i>
                            Pending Consultation
                        </span>
                        <span class="badge bg-warning text-dark">{{ $stats['pending_consultations'] ?? 0 }}</span>
                    </a>
                    <a href="{{ url_for('@icip_report_expiry') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-calendar-x text-danger me-2"></i>
                            Expiring Consents
                        </span>
                        <span class="badge bg-danger">{{ $stats['expiring_consents'] ?? 0 }}</span>
                    </a>
                    <a href="{{ url_for('@icip_consultations') }}?status=follow_up_required" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-bell text-info me-2"></i>
                            Follow-ups Due
                        </span>
                        <span class="badge bg-info">{{ $stats['follow_ups_due'] ?? 0 }}</span>
                    </a>
                </div>
            </div>

            <!-- Quick Metrics -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Metrics</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>Pending Consultations</span>
                            <strong class="text-warning">{{ $stats['pending_consultations'] ?? 0 }}</strong>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>Expiring Consents (90 days)</span>
                            <strong class="text-danger">{{ $stats['expiring_consents'] ?? 0 }}</strong>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>TK Labels Applied</span>
                            <strong class="text-info">{{ $stats['tk_labels_applied'] ?? 0 }}</strong>
                        </li>
                        <li class="d-flex justify-content-between py-2">
                            <span>Active Restrictions</span>
                            <strong class="text-secondary">{{ $stats['active_restrictions'] ?? 0 }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
