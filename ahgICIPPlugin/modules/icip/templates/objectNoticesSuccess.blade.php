@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'informationobject', 'slug' => $object->slug]) }}">{{ $object->title ?? 'Record' }}</a></li>
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_object?slug=' . $object->slug) }}">ICIP</a></li>
            <li class="breadcrumb-item active">Cultural Notices</li>
        </ol>
    </nav>

    <h1 class="mb-4">
        <i class="bi bi-bell me-2"></i>
        Manage Cultural Notices
    </h1>

    @if ($sf_user->hasFlash('notice'))
        <div class="alert alert-success alert-dismissible fade show">
            {!! $sf_user->getFlash('notice') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <!-- Active Notices -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Active Notices</h5>
                </div>
                <div class="card-body">
                    @if (empty($notices))
                        <p class="text-muted">No cultural notices applied to this record.</p>
                    @else
                        @foreach ($notices as $notice)
                            <div class="icip-notice icip-notice-{{ $notice->severity }} mb-3 p-3 rounded d-flex justify-content-between align-items-start">
                                <div class="d-flex">
                                    @php
                                    $severityIcon = match ($notice->severity) {
                                        'critical' => 'bi-exclamation-triangle-fill text-danger',
                                        'warning' => 'bi-exclamation-circle text-warning',
                                        default => 'bi-info-circle text-info'
                                    };
                                    @endphp
                                    <i class="bi {{ $severityIcon }} fs-4 me-3"></i>
                                    <div>
                                        <strong>{{ $notice->notice_name }}</strong>
                                        @if ($notice->requires_acknowledgement)
                                            <span class="badge bg-warning text-dark ms-2">Requires Acknowledgement</span>
                                        @endif
                                        @if ($notice->blocks_access)
                                            <span class="badge bg-danger ms-2">Blocks Access</span>
                                        @endif
                                        <p class="mb-1 mt-1">{{ $notice->custom_text ?? $notice->default_text ?? '' }}</p>
                                        @if ($notice->community_name)
                                            <small class="text-muted">Community: {{ $notice->community_name }}</small>
                                        @endif
                                    </div>
                                </div>
                                <form method="post" class="d-inline" onsubmit="return confirm('Remove this notice?');">
                                    <input type="hidden" name="form_action" value="remove">
                                    <input type="hidden" name="notice_id" value="{{ $notice->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Add Notice -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add Cultural Notice</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="form_action" value="add">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Notice Type <span class="text-danger">*</span></label>
                                <select name="notice_type_id" class="form-select" required>
                                    <option value="">Select notice type</option>
                                    @foreach ($noticeTypes as $type)
                                        <option value="{{ $type->id }}">
                                            {{ $type->name }}
                                            ({{ ucfirst($type->severity) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Community</label>
                                <select name="community_id" class="form-select">
                                    <option value="">Not specified</option>
                                    @foreach ($communities as $community)
                                        <option value="{{ $community->id }}">{{ $community->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Custom Text (optional)</label>
                            <textarea name="custom_text" class="form-control" rows="3" placeholder="Override the default notice text..."></textarea>
                            <div class="form-text">Leave blank to use the default text for this notice type</div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control">
                                <div class="form-text">For seasonal notices</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="applies_to_descendants" value="1" class="form-check-input" id="applyDescendants" checked>
                                    <label class="form-check-label" for="applyDescendants">Apply to child records</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add Notice
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Notice Types</h5>
                </div>
                <div class="card-body small">
                    @foreach ($noticeTypes as $type)
                        <div class="mb-2 pb-2 border-bottom">
                            @php
                            $severityIcon = match ($type->severity) {
                                'critical' => 'bi-exclamation-triangle-fill text-danger',
                                'warning' => 'bi-exclamation-circle text-warning',
                                default => 'bi-info-circle text-info'
                            };
                            @endphp
                            <i class="bi {{ $severityIcon }} me-1"></i>
                            <strong>{{ $type->name }}</strong>
                            <br>
                            <small class="text-muted">{{ $type->description ?? '' }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<style {!! $csp_nonce !!}>
.icip-notice-critical { background-color: #f8d7da; border-left: 4px solid #dc3545; }
.icip-notice-warning { background-color: #fff3cd; border-left: 4px solid #ffc107; }
.icip-notice-info { background-color: #cff4fc; border-left: 4px solid #0dcaf0; }
</style>
@endsection
