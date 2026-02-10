@extends('layouts.page')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'index']) }}" class="btn btn-outline-secondary btn-sm me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="h2 mb-0">
                <i class="fas fa-bell me-2"></i>{{ __('Notifications') }}
                @if(count($notifications) > 0)
                <span class="badge bg-danger">{{ count($notifications) }}</span>
                @endif
            </h1>
        </div>
        @if(count($notifications) > 0)
        <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'notificationMarkAllRead']) }}" class="btn btn-outline-secondary">
            <i class="fas fa-check-double me-1"></i>{{ __('Mark All Read') }}
        </a>
        @endif
    </div>

    @if($sf_user->hasFlash('success'))
    <div class="alert alert-success">{{ $sf_user->getFlash('success') }}</div>
    @endif

    @if(count($notifications) === 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">{{ __('No unread notifications') }}</h5>
            <p class="text-muted">You are all caught up!</p>
        </div>
    </div>
    @else
    <div class="card">
        <ul class="list-group list-group-flush">
            @foreach($notifications as $notification)
            @php
$typeIcons = [
                'submitted' => 'paper-plane text-primary',
                'approved' => 'check-circle text-success',
                'rejected' => 'times-circle text-danger',
                'comment' => 'comment text-info',
                'reminder' => 'clock text-warning'
            ];
            $icon = $typeIcons[$notification->notification_type] ?? 'bell text-secondary';
@endphp
            <li class="list-group-item list-group-item-action">
                <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'notificationRead', 'id' => $notification->id]) }}" class="text-decoration-none text-dark d-block">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-{{ $icon }} fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $notification->subject }}</strong>
                                <small class="text-muted">{{ $notification->created_at }}</small>
                            </div>
                            @if($notification->message)
                            <p class="mb-0 text-muted small">{{ $notification->message }}</p>
                            @endif
                        </div>
                    </div>
                </a>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endsection
