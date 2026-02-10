@extends('layouts.page')

@section('title')
<h1><i class="bi bi-calendar-event text-primary me-2"></i>{{ __('Preservation Events (PREMIS)') }}</h1>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="btn-group flex-wrap">
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'events']) }}"
           class="btn btn-sm btn-{{ !$currentType ? 'primary' : 'outline-primary' }}">All</a>
        @foreach ($eventTypes as $type)
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'events', 'type' => $type->event_type]) }}"
           class="btn btn-sm btn-{{ $currentType === $type->event_type ? 'primary' : 'outline-primary' }}">
            {{ str_replace('_', ' ', ucfirst($type->event_type)) }} ({{ $type->count }})
        </a>
        @endforeach
    </div>
    <a href="{{ url_for(['module' => 'preservation', 'action' => 'index']) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Dashboard') }}
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Event Type') }}</th>
                    <th>{{ __('Object') }}</th>
                    <th>{{ __('Detail') }}</th>
                    <th>{{ __('Outcome') }}</th>
                    <th>{{ __('Agent') }}</th>
                    <th>{{ __('Date/Time') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                <tr>
                    <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $event->event_type) }}</span></td>
                    <td>
                        @if ($event->digital_object_id)
                            <a href="{{ url_for(['module' => 'preservation', 'action' => 'object', 'id' => $event->digital_object_id]) }}">
                                {{ substr($event->filename ?? $event->object_title ?? 'Object', 0, 30) }}
                            </a>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ substr($event->event_detail ?? '', 0, 50) }}</td>
                    <td>
                        @if ($event->event_outcome === 'success')
                            <span class="badge bg-success">Success</span>
                        @elseif ($event->event_outcome === 'failure')
                            <span class="badge bg-danger">Failure</span>
                        @elseif ($event->event_outcome === 'warning')
                            <span class="badge bg-warning">Warning</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($event->event_outcome) }}</span>
                        @endif
                    </td>
                    <td><small>{{ $event->linking_agent_value ?? '-' }}</small></td>
                    <td><small>{{ date('Y-m-d H:i', strtotime($event->event_datetime)) }}</small></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
