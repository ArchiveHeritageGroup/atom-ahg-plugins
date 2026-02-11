@extends('layouts.page')

@section('title')
  <h1>{{ __('Edit Embargo') }}</h1>
  <p class="lead">{{ $resource->title ?? $resource->slug }}</p>
@endsection

@section('content')
@php
// Get object ID for propagation count
$objectId = $embargo->object_id ?? $resource->id ?? 0;

// Get taxonomy options
$taxonomyService = new \ahgCorePlugin\Services\AhgTaxonomyService();
$embargoTypes = $taxonomyService->getEmbargoTypes(false);
$embargoReasons = $taxonomyService->getEmbargoReasons(true);
$embargoStatuses = $taxonomyService->getEmbargoStatuses(false);
@endphp

<form method="post" action="{{ url_for(['module' => 'embargo', 'action' => 'edit', 'id' => $embargo->id]) }}">
  <div class="card mb-4">
    <div class="card-header">
      <h4 class="mb-0">{{ __('Embargo Details') }}</h4>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="embargo_type" class="form-label">{{ __('Embargo Type') }} <span class="text-danger">*</span></label>
          <select name="embargo_type" id="embargo_type" class="form-select" required>
            @foreach ($embargoTypes as $code => $label)
              <option value="{{ $code }}" {{ ($embargo->embargo_type ?? '') === $code ? 'selected' : '' }}>{{ __($label) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="reason" class="form-label">{{ __('Reason') }}</label>
          <select name="reason" id="reason" class="form-select">
            @foreach ($embargoReasons as $code => $label)
              <option value="{{ $code }}" {{ ($embargo->reason ?? '') === $code ? 'selected' : '' }}>{{ __($label) }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label for="start_date" class="form-label">{{ __('Start Date') }} <span class="text-danger">*</span></label>
          <input type="date" name="start_date" id="start_date" class="form-control" required value="{{ $embargo->start_date ?? date('Y-m-d') }}">
        </div>
        <div class="col-md-4 mb-3">
          <label for="end_date" class="form-label">{{ __('End Date') }}</label>
          <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $embargo->end_date ?? '' }}">
          <small class="text-muted">{{ __('Leave blank for perpetual embargo') }}</small>
        </div>
        <div class="col-md-4 mb-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_perpetual" value="1" id="is_perpetual" {{ empty($embargo->auto_release) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_perpetual">
              {{ __('Perpetual (no end date)') }}
            </label>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="status" class="form-label">{{ __('Status') }}</label>
          <select name="status" id="status" class="form-select">
            @foreach ($embargoStatuses as $code => $label)
              <option value="{{ $code }}" {{ ($embargo->status ?? '') === $code ? 'selected' : '' }}>{{ __($label) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="notify_before_days" class="form-label">{{ __('Notify Days Before Expiry') }}</label>
          <input type="number" name="notify_before_days" id="notify_before_days" class="form-control" value="{{ $embargo->notify_before_days ?? 30 }}" min="0">
        </div>
      </div>
    </div>
  </div>

  <!-- Propagation Options -->
  <div class="card mb-4">
    <div class="card-header">
      <h4 class="mb-0"><i class="fas fa-sitemap me-2"></i>{{ __('Apply to Hierarchy') }}</h4>
    </div>
    <div class="card-body">
      @php
      // Count descendants
      $io = \Illuminate\Database\Capsule\Manager::table('information_object')
          ->where('id', $objectId)
          ->select('lft', 'rgt')
          ->first();
      $descendantCount = 0;
      if ($io && $io->lft && $io->rgt) {
          $descendantCount = \Illuminate\Database\Capsule\Manager::table('information_object')
              ->where('lft', '>', $io->lft)
              ->where('rgt', '<', $io->rgt)
              ->count();
      }
      @endphp
      @if ($descendantCount > 0)
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="apply_to_children" value="1" id="apply_to_children">
          <label class="form-check-label" for="apply_to_children">
            <strong>{{ __('Apply changes to all descendants') }}</strong>
            <span class="badge bg-info ms-2">{{ $descendantCount }} {{ __($descendantCount === 1 ? 'record' : 'records') }}</span>
          </label>
          <div class="form-text text-muted">
            {{ __('This will create or update embargoes on all child records below this item.') }}
          </div>
        </div>
        <div class="alert alert-warning mb-0" id="propagation-warning" style="display: none;">
          <i class="fas fa-exclamation-triangle me-2"></i>
          {{ __('Warning: This will create new embargoes on descendants that do not have one, and update those that do.') }}
        </div>
      @else
        <p class="text-muted mb-0">
          <i class="fas fa-info-circle me-2"></i>
          {{ __('This record has no child records.') }}
        </p>
      @endif
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
    <a href="{{ url_for(['module' => 'informationobject', 'slug' => $resource->slug]) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
  </div>
</form>

<script {!! $csp_nonce !!}>
document.getElementById('is_perpetual').addEventListener('change', function() {
  var endDateInput = document.getElementById('end_date');
  if (endDateInput) {
    endDateInput.disabled = this.checked;
    if (this.checked) {
      endDateInput.value = '';
    }
  }
});

// Show warning when propagation is selected
var propagationCheckbox = document.getElementById('apply_to_children');
if (propagationCheckbox) {
  propagationCheckbox.addEventListener('change', function() {
    var warning = document.getElementById('propagation-warning');
    if (warning) {
      warning.style.display = this.checked ? 'block' : 'none';
    }
  });
}
</script>
@endsection
