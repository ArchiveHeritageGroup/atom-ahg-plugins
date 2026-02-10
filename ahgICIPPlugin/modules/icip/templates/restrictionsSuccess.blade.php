@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_dashboard') }}">ICIP</a></li>
            <li class="breadcrumb-item active">Access Restrictions</li>
        </ol>
    </nav>

    <h1 class="mb-4">
        <i class="bi bi-lock me-2"></i>
        ICIP Access Restrictions
    </h1>

    <div class="row">
        <div class="col-lg-8">
            <!-- Active Restrictions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Active Restrictions</h5>
                </div>
                <div class="card-body p-0">
                    @if (empty($restrictions))
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-lock fs-1"></i>
                            <p class="mb-0 mt-2">No restrictions applied</p>
                            <p class="small">Restrictions can be applied from individual record ICIP pages</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Restriction Type</th>
                                        <th>Record</th>
                                        <th>Community</th>
                                        <th>Period</th>
                                        <th>Override</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($restrictions as $restriction)
                                        <tr>
                                            <td>
                                                <i class="bi bi-lock-fill text-danger me-1"></i>
                                                {{ $restrictionTypes[$restriction->restriction_type] ?? ucwords(str_replace('_', ' ', $restriction->restriction_type)) }}
                                                @if ($restriction->restriction_type === 'custom' && $restriction->custom_restriction_text)
                                                    <br><small class="text-muted">{{ $restriction->custom_restriction_text }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($restriction->slug)
                                                    <a href="{{ url_for('@icip_object?slug=' . $restriction->slug) }}">
                                                        {{ $restriction->object_title ?? 'Untitled' }}
                                                    </a>
                                                @else
                                                    {{ $restriction->object_title ?? 'Untitled' }}
                                                @endif
                                            </td>
                                            <td>{{ $restriction->community_name ?? '-' }}</td>
                                            <td>
                                                @if ($restriction->start_date || $restriction->end_date)
                                                    {{ $restriction->start_date ? date('j M Y', strtotime($restriction->start_date)) : 'Start' }}
                                                    &ndash;
                                                    {{ $restriction->end_date ? date('j M Y', strtotime($restriction->end_date)) : 'Indefinite' }}
                                                @else
                                                    <span class="badge bg-secondary">Indefinite</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($restriction->override_security_clearance)
                                                    <span class="badge bg-danger" title="Overrides standard security clearance">
                                                        <i class="bi bi-shield-x"></i> Yes
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">No</span>
                                                @endif
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
            <!-- Restriction Types Reference -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Restriction Types</h5>
                </div>
                <div class="card-body">
                    <dl class="mb-0">
                        @foreach ($restrictionTypes as $code => $label)
                            <dt>
                                <i class="bi bi-lock text-danger me-1"></i>
                                {{ $label }}
                            </dt>
                            <dd class="text-muted small mb-3">
                                @php
                                $description = match ($code) {
                                    'community_permission_required' => 'Written permission from the community is required before access.',
                                    'gender_restricted_male' => 'Material may only be accessed by men who have appropriate cultural standing.',
                                    'gender_restricted_female' => 'Material may only be accessed by women who have appropriate cultural standing.',
                                    'initiated_only' => 'Material may only be accessed by initiated community members.',
                                    'seasonal' => 'Access is restricted to certain times of year based on cultural protocols.',
                                    'mourning_period' => 'Access is temporarily restricted during a period of mourning.',
                                    'repatriation_pending' => 'Material is pending return to the community.',
                                    'under_consultation' => 'Access decisions are pending community consultation.',
                                    'elder_approval_required' => 'Approval from community Elders is required before access.',
                                    'custom' => 'Custom restriction defined by the community.',
                                    default => ''
                                };
                                @endphp
                                {{ $description }}
                            </dd>
                        @endforeach
                    </dl>
                </div>
            </div>

            <!-- Override Info -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Security Override</h5>
                </div>
                <div class="card-body small">
                    <p>When "Override Security Clearance" is enabled, ICIP restrictions take precedence over standard security clearance levels.</p>
                    <p class="mb-0">This ensures that cultural protocols are respected even for users with high security clearance.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
