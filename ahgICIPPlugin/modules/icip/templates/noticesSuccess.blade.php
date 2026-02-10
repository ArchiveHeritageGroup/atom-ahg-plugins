@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_dashboard') }}">ICIP</a></li>
            <li class="breadcrumb-item active">Cultural Notices</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="bi bi-bell me-2"></i>
            Cultural Notices
        </h1>
        <a href="{{ url_for('@icip_notice_types') }}" class="btn btn-outline-secondary">
            <i class="bi bi-gear me-1"></i>
            Manage Notice Types
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Active Notices -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Applied Cultural Notices</h5>
                </div>
                <div class="card-body p-0">
                    @if (empty($appliedNotices))
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-bell fs-1"></i>
                            <p class="mb-0 mt-2">No notices applied yet</p>
                            <p class="small">Notices can be applied from individual record ICIP pages</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Notice Type</th>
                                        <th>Record</th>
                                        <th>Community</th>
                                        <th>Severity</th>
                                        <th>Applied</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($appliedNotices as $notice)
                                        <tr>
                                            <td>
                                                @php
                                                $severityIcon = match ($notice->severity) {
                                                    'critical' => 'bi-exclamation-triangle-fill text-danger',
                                                    'warning' => 'bi-exclamation-circle text-warning',
                                                    default => 'bi-info-circle text-info'
                                                };
                                                @endphp
                                                <i class="bi {{ $severityIcon }} me-1"></i>
                                                {{ $notice->notice_name }}
                                            </td>
                                            <td>
                                                @if ($notice->slug)
                                                    <a href="{{ url_for('@icip_object?slug=' . $notice->slug) }}">
                                                        {{ $notice->object_title ?? 'Untitled' }}
                                                    </a>
                                                @else
                                                    {{ $notice->object_title ?? 'Untitled' }}
                                                @endif
                                            </td>
                                            <td>{{ $notice->community_name ?? '-' }}</td>
                                            <td>
                                                @php
                                                $severityClass = match ($notice->severity) {
                                                    'critical' => 'bg-danger',
                                                    'warning' => 'bg-warning text-dark',
                                                    default => 'bg-info'
                                                };
                                                @endphp
                                                <span class="badge {{ $severityClass }}">
                                                    {{ ucfirst($notice->severity) }}
                                                </span>
                                            </td>
                                            <td>{{ date('j M Y', strtotime($notice->created_at)) }}</td>
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
            <!-- Notice Types Reference -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Notice Types</h5>
                </div>
                <div class="card-body">
                    @foreach ($noticeTypes as $type)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-start">
                                @php
                                $severityIcon = match ($type->severity) {
                                    'critical' => 'bi-exclamation-triangle-fill text-danger',
                                    'warning' => 'bi-exclamation-circle text-warning',
                                    default => 'bi-info-circle text-info'
                                };
                                @endphp
                                <i class="bi {{ $severityIcon }} fs-5 me-2 mt-1"></i>
                                <div>
                                    <strong>{{ $type->name }}</strong>
                                    @if (!$type->is_active)
                                        <span class="badge bg-secondary ms-1">Inactive</span>
                                    @endif
                                    <br>
                                    <small class="text-muted">{{ $type->description ?? '' }}</small>
                                    <div class="mt-1">
                                        @if ($type->requires_acknowledgement)
                                            <span class="badge bg-warning text-dark">Requires Acknowledgement</span>
                                        @endif
                                        @if ($type->blocks_access)
                                            <span class="badge bg-danger">Blocks Access</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
