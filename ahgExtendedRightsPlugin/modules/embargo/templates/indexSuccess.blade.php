@extends('layouts.page')

@section('sidebar')
  <div class="sidebar-widget">
    <h3>{{ __('Embargo Management') }}</h3>
    <ul class="nav nav-pills nav-stacked">
      <li class="active"><a href="{{ url_for(['module' => 'embargo', 'action' => 'index']) }}">{{ __('Dashboard') }}</a></li>
    </ul>
  </div>

  <div class="sidebar-widget">
    <h3>{{ __('Quick Stats') }}</h3>
    <ul class="list-unstyled">
      <li><strong>{{ count($activeEmbargoes) }}</strong> {{ __('Active Embargoes') }}</li>
      <li><strong>{{ count($expiringEmbargoes) }}</strong> {{ __('Expiring Soon') }}</li>
    </ul>
  </div>
@endsection

@section('title')
  <h1>{{ __('Embargo Management') }}</h1>
@endsection

@section('content')
<!-- Expiring Soon Alert -->
@if (count($expiringEmbargoes) > 0)
<div class="alert alert-warning">
  <h5><i class="fas fa-exclamation-triangle"></i> {{ __('Embargoes Expiring Within 30 Days') }}</h5>
  <ul class="mb-0">
    @foreach ($expiringEmbargoes->take(5) as $embargo)
      <li>
        <a href="{{ url_for(['module' => 'embargo', 'action' => 'view', 'id' => $embargo->id]) }}">
          {{ __('Object #%1%', ['%1%' => $embargo->object_id]) }}
        </a>
        - {{ __('Expires: %1%', ['%1%' => $embargo->end_date->format('Y-m-d')]) }}
        ({{ $embargo->days_remaining }} {{ __('days') }})
      </li>
    @endforeach
  </ul>
</div>
@endif

<!-- Active Embargoes -->
<div class="card">
  <div class="card-header">
    <h4 class="mb-0">{{ __('Active Embargoes') }}</h4>
  </div>
  <div class="card-body p-0">
    @if (count($activeEmbargoes) > 0)
      <table class="table table-striped table-hover mb-0">
        <thead>
          <tr>
            <th>{{ __('Object') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Start Date') }}</th>
            <th>{{ __('End Date') }}</th>
            <th>{{ __('Reason') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($activeEmbargoes as $embargo)
            <tr>
              <td>
                <a href="{{ url_for(['module' => 'informationobject', 'action' => 'browse', 'id' => $embargo->object_id]) }}">
                  #{{ $embargo->object_id }}
                </a>
              </td>
              <td>
                <span class="badge bg-{{ $embargo->embargo_type === 'full' ? 'danger' : 'warning' }}">
                  {{ ucfirst(str_replace('_', ' ', $embargo->embargo_type)) }}
                </span>
              </td>
              <td>{{ $embargo->start_date->format('Y-m-d') }}</td>
              <td>
                @if ($embargo->is_perpetual)
                  <span class="text-danger">{{ __('Perpetual') }}</span>
                @elseif ($embargo->end_date)
                  {{ $embargo->end_date->format('Y-m-d') }}
                  @if ($embargo->days_remaining <= 30)
                    <span class="badge bg-warning">{{ $embargo->days_remaining }}d</span>
                  @endif
                @else
                  -
                @endif
              </td>
              <td>{{ $embargo->reason ?? '-' }}</td>
              <td>
                <a href="{{ url_for(['module' => 'embargo', 'action' => 'view', 'id' => $embargo->id]) }}" class="btn btn-sm btn-outline-primary">
                  {{ __('View') }}
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <p class="text-muted text-center py-4">{{ __('No active embargoes.') }}</p>
    @endif
  </div>
</div>
@endsection
