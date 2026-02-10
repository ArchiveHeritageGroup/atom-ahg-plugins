@extends('layouts.page')

@section('sidebar')
<div class="sidebar-content">
  <div class="card">
    <div class="card-header bg-light">
      <h5 class="mb-0"><i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}</h5>
    </div>
    <div class="card-body">
      <a href="/{{ $resource->slug }}" class="btn btn-outline-secondary w-100">
        <i class="fas fa-arrow-left me-2"></i>{{ __('Cancel and return') }}
      </a>
    </div>
  </div>
</div>
@endsection

@section('title')
<h1><i class="fas fa-eraser me-2"></i>{{ __('Clear Extended Rights') }}</h1>
@endsection

@section('content')
<div class="card">
  <div class="card-header bg-warning">
    <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>{{ __('Confirm Clear Rights') }}</h4>
  </div>
  <div class="card-body">
    <p class="lead">{{ __('Are you sure you want to clear all extended rights from this record?') }}</p>

    <div class="alert alert-info">
      <strong>{{ __('The following rights will be removed:') }}</strong>
      <ul class="mb-0 mt-2">
        @if ($currentRights->rights_statement)
          <li><i class="fas fa-balance-scale me-1"></i>{{ __('Rights Statement:') }} {{ $currentRights->rights_statement->name }}</li>
        @endif
        @if ($currentRights->cc_license)
          <li><i class="fab fa-creative-commons me-1"></i>{{ __('Creative Commons License:') }} {{ $currentRights->cc_license->name }}</li>
        @endif
        @php $tkLabels = $currentRights->tk_labels ?? []; @endphp
        @if (!empty($tkLabels))
          <li><i class="fas fa-hand-holding-heart me-1"></i>{{ __('Traditional Knowledge Labels:') }} {{ implode(', ', $tkLabels) }}</li>
        @endif
        @if ($currentRights->rights_holder)
          <li><i class="fas fa-user me-1"></i>{{ __('Rights Holder:') }} {{ $currentRights->rights_holder->name }}</li>
        @endif
        @if (!$currentRights->rights_statement && !$currentRights->cc_license && empty($currentRights->tk_labels) && !$currentRights->rights_holder)
          <li class="text-muted"><em>{{ __('No extended rights currently assigned') }}</em></li>
        @endif
      </ul>
    </div>

    <p class="text-muted small">{{ __('Note: This action will not affect embargoes. Use the embargo management to lift embargoes.') }}</p>

    <form method="post">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-danger">
          <i class="fas fa-eraser me-2"></i>{{ __('Yes, clear all rights') }}
        </button>
        <a href="/{{ $resource->slug }}" class="btn btn-secondary">
          <i class="fas fa-times me-2"></i>{{ __('Cancel') }}
        </a>
      </div>
    </form>
  </div>
</div>
@endsection
