@php
$embargoes = $embargoes ?? [];
$days = $days ?? 30;
@endphp

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url_for('@homepage') }}">{{ __('Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'extendedRights', 'action' => 'dashboard']) }}">{{ __('Rights Management') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Expiring Embargoes') }}</li>
            </ol>
        </nav>
        <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'dashboard']) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
        </a>
    </div>

    <div class="card">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-clock me-2"></i>{{ __('Embargoes Expiring Within %1% Days', ['%1%' => $days]) }}</h4>
            <div class="btn-group btn-group-sm">
                <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'expiringEmbargoes', 'days' => 7]) }}"
                   class="btn {{ $days == 7 ? 'btn-dark' : 'btn-outline-dark' }}">7 days</a>
                <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'expiringEmbargoes', 'days' => 30]) }}"
                   class="btn {{ $days == 30 ? 'btn-dark' : 'btn-outline-dark' }}">30 days</a>
                <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'expiringEmbargoes', 'days' => 90]) }}"
                   class="btn {{ $days == 90 ? 'btn-dark' : 'btn-outline-dark' }}">90 days</a>
            </div>
        </div>

        <div class="card-body p-0">
            @if (empty($embargoes))
            <div class="alert alert-success m-3">
                <i class="fas fa-check-circle me-2"></i>{{ __('No embargoes expiring within the next %1% days.', ['%1%' => $days]) }}
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Expiry Date') }}</th>
                            <th>{{ __('Days Remaining') }}</th>
                            <th>{{ __('Restriction') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($embargoes as $embargo)
                        @php
                            $embargo = (object) $embargo;
                            $daysRemaining = (int) $embargo->days_remaining;
                            $urgencyClass = $daysRemaining <= 7 ? 'table-danger' : ($daysRemaining <= 14 ? 'table-warning' : '');
                        @endphp
                        <tr class="{{ $urgencyClass }}">
                            <td>
                                <a href="{{ url_for(['module' => 'informationobject', 'slug' => $embargo->slug ?? $embargo->object_id]) }}">
                                    {{ $embargo->title ?? 'Untitled' }}
                                </a>
                            </td>
                            <td>{{ $embargo->end_date }}</td>
                            <td>
                                @if ($daysRemaining <= 7)
                                <span class="badge bg-danger">{{ $daysRemaining }} {{ __('days') }}</span>
                                @elseif ($daysRemaining <= 14)
                                <span class="badge bg-warning text-dark">{{ $daysRemaining }} {{ __('days') }}</span>
                                @else
                                <span class="badge bg-info">{{ $daysRemaining }} {{ __('days') }}</span>
                                @endif
                            </td>
                            <td>{{ $embargo->embargo_type . " - " . $embargo->reason ?? '-' }}</td>
                            <td>
                                <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'edit', 'id' => $embargo->id]) }}"
                                   class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'liftEmbargo', 'id' => $embargo->id]) }}"
                                   class="btn btn-sm btn-outline-success" title="{{ __('Lift Embargo') }}">
                                    <i class="fas fa-unlock"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <div class="card-footer text-muted">
            {{ __('Total: %1% embargoes', ['%1%' => count($embargoes)]) }}
        </div>
    </div>
</div>
