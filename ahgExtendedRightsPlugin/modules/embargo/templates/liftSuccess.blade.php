@extends('layouts.page')

@section('title')
  <h1>{{ __('Lift Embargo') }}</h1>
  <p class="lead">{{ $resource->title ?? $resource->slug }}</p>
@endsection

@section('content')
<div class="alert alert-info mb-4">
  <i class="fas fa-info-circle me-2"></i>
  {{ __('Lifting this embargo will immediately restore access to the record.') }}
</div>

<form method="post" action="{{ url_for(['module' => 'embargo', 'action' => 'lift', 'id' => $embargo->id]) }}">
  <div class="card mb-4">
    <div class="card-header bg-success text-white">
      <h4 class="mb-0"><i class="fas fa-unlock me-2"></i>{{ __('Confirm Lift Embargo') }}</h4>
    </div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-4"><strong>{{ __('Embargo Type') }}:</strong></div>
        <div class="col-md-8">{{ ucfirst(str_replace('_', ' ', $embargo->embargo_type ?? 'full')) }}</div>
      </div>
      <div class="row mb-3">
        <div class="col-md-4"><strong>{{ __('Start Date') }}:</strong></div>
        <div class="col-md-8">{{ $embargo->start_date }}</div>
      </div>
      @if ($embargo->end_date)
      <div class="row mb-3">
        <div class="col-md-4"><strong>{{ __('End Date') }}:</strong></div>
        <div class="col-md-8">{{ $embargo->end_date }}</div>
      </div>
      @endif

      <hr>

      <div class="mb-3">
        <label for="lift_reason" class="form-label">{{ __('Reason for lifting (optional)') }}</label>
        <textarea name="lift_reason" id="lift_reason" class="form-control" rows="3" placeholder="{{ __('e.g., Embargo period completed, Permission granted, Error correction') }}"></textarea>
      </div>
    </div>
  </div>

  <div class="actions">
    <a href="{{ url_for(['module' => 'informationobject', 'slug' => $resource->slug]) }}" class="btn btn-secondary">
      {{ __('Cancel') }}
    </a>
    <button type="submit" class="btn btn-success">
      <i class="fas fa-unlock"></i> {{ __('Lift Embargo') }}
    </button>
  </div>
</form>
@endsection
