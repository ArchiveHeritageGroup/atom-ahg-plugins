@extends('layouts.page')

@php
$alerts = $alertData['alerts'] ?? [];
$stats = $alertData['stats'] ?? [];
@endphp

@section('title')
<h1 class="h3">
    <i class="fas fa-bell me-2"></i>Alerts & Notifications
</h1>
@endsection

@section('sidebar')
@include('heritage/adminSidebar', ['active' => 'alerts'])

<div class="mt-4">
    <h6 class="text-muted mb-3">Filter by Severity</h6>
    <div class="list-group">
        <a href="?" class="list-group-item list-group-item-action {{ !$sf_request->getParameter('severity') ? 'active' : '' }}">
            All Alerts
        </a>
        <a href="?severity=critical" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $sf_request->getParameter('severity') === 'critical' ? 'active' : '' }}">
            Critical
            <span class="badge bg-danger">{{ $stats['critical'] ?? 0 }}</span>
        </a>
        <a href="?severity=warning" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $sf_request->getParameter('severity') === 'warning' ? 'active' : '' }}">
            Warning
            <span class="badge bg-warning">{{ $stats['warning'] ?? 0 }}</span>
        </a>
        <a href="?severity=info" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $sf_request->getParameter('severity') === 'info' ? 'active' : '' }}">
            Info
            <span class="badge bg-info">{{ $stats['info'] ?? 0 }}</span>
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0">Alert Types</h6>
    </div>
    <div class="card-body">
        @php
        $alertTypes = [
            'content_quality' => ['icon' => 'file-text', 'label' => 'Content Quality'],
            'access_request' => ['icon' => 'key', 'label' => 'Access Requests'],
            'system' => ['icon' => 'gear', 'label' => 'System'],
            'security' => ['icon' => 'shield-exclamation', 'label' => 'Security'],
            'performance' => ['icon' => 'speedometer', 'label' => 'Performance']
        ];
        @endphp
        @foreach ($alertTypes as $type => $info)
        <a href="?type={{ $type }}" class="d-flex align-items-center text-decoration-none text-dark mb-2">
            <i class="fas fa-{{ $info['icon'] }} me-2"></i>
            <span>{{ $info['label'] }}</span>
        </a>
        @endforeach
    </div>
</div>
@endsection

@section('content')
<!-- Alert Summary -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-danger border-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0 text-danger">{{ $stats['critical'] ?? 0 }}</h3>
                    <small class="text-muted">Critical</small>
                </div>
                <i class="fas fa-exclamation-octagon fs-1 text-danger opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-warning border-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0 text-warning">{{ $stats['warning'] ?? 0 }}</h3>
                    <small class="text-muted">Warnings</small>
                </div>
                <i class="fas fa-exclamation-triangle fs-1 text-warning opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-info border-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0 text-info">{{ $stats['info'] ?? 0 }}</h3>
                    <small class="text-muted">Info</small>
                </div>
                <i class="fas fa-info-circle fs-1 text-info opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<!-- Alerts List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Active Alerts</h5>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="dismissAllRead()">
                <i class="fas fa-check-all me-1"></i>Dismiss All Read
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        @if (empty($alerts))
        <div class="text-center text-muted py-5">
            <i class="fas fa-bell-slash fs-1 mb-3 d-block"></i>
            <p>No alerts at this time.</p>
        </div>
        @else
        <div class="list-group list-group-flush">
            @foreach ($alerts as $alert)
            @php
            $severityColors = ['critical' => 'danger', 'warning' => 'warning', 'info' => 'info'];
            $severityIcons = ['critical' => 'exclamation-octagon', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
            $color = $severityColors[$alert->severity] ?? 'secondary';
            $icon = $severityIcons[$alert->severity] ?? 'bell';
            @endphp
            <div class="list-group-item list-group-item-action" id="alert-{{ $alert->id }}">
                <div class="d-flex w-100 justify-content-between align-items-start">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <span class="badge bg-{{ $color }} rounded-pill p-2">
                                <i class="fas fa-{{ $icon }}"></i>
                            </span>
                        </div>
                        <div>
                            <h6 class="mb-1">{{ $alert->title }}</h6>
                            <p class="mb-1 text-muted">{{ $alert->message ?? '' }}</p>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                {{ date('M d, Y H:i', strtotime($alert->created_at)) }}
                                @if ($alert->alert_type)
                                <span class="ms-2 badge bg-light text-dark">{{ ucwords(str_replace('_', ' ', $alert->alert_type)) }}</span>
                                @endif
                            </small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        @if ($alert->action_url ?? null)
                        <a href="{{ $alert->action_url }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="dismissAlert({{ $alert->id }})" title="Dismiss">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<!-- Dismissed Alerts -->
@if (!empty($alertData['dismissed_count']))
<div class="mt-4 text-center">
    <a href="?show_dismissed=1" class="text-muted">
        <i class="fas fa-archive me-1"></i>
        View {{ $alertData['dismissed_count'] }} dismissed alerts
    </a>
</div>
@endif

<script {!! $csp_nonce !!}>
function dismissAlert(alertId) {
    fetch('/heritage/api/analytics/alerts/' + alertId + '/dismiss', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const alertEl = document.getElementById('alert-' + alertId);
            if (alertEl) {
                alertEl.style.transition = 'opacity 0.3s';
                alertEl.style.opacity = '0';
                setTimeout(() => alertEl.remove(), 300);
            }
        }
    })
    .catch(err => console.error('Error dismissing alert:', err));
}

function dismissAllRead() {
    if (!confirm('Dismiss all info-level alerts?')) return;

    fetch('/heritage/api/analytics/alerts/dismiss-all', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ severity: 'info' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(err => console.error('Error:', err));
}
</script>
@endsection
