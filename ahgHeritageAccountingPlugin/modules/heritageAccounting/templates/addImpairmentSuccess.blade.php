@extends('layouts.page')

@section('title', __('Add Impairment'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-1"><i class="fas fa-exclamation-triangle me-2"></i>{{ __('Add Impairment') }}</h1>
            <p class="text-muted">{{ $asset->object_identifier ?? '' }} - {{ $asset->object_title ?? 'Untitled' }}</p>
        </div>
    </div>

    @if (isset($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <form method="post">
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">{{ __('Impairment Details') }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Assessment Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="assessment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Impairment Amount') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="impairment_amount" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Impairment Type') }}</label>
                        <select name="impairment_type" class="form-select">
                            <option value="">{{ __('-- Select --') }}</option>
                            <option value="physical">{{ __('Physical Damage') }}</option>
                            <option value="obsolescence">{{ __('Obsolescence') }}</option>
                            <option value="market">{{ __('Market Decline') }}</option>
                            <option value="legal">{{ __('Legal/Regulatory') }}</option>
                            <option value="environmental">{{ __('Environmental') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Reason') }}</label>
                        <textarea name="reason" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Reviewed By') }}</label>
                        <input type="text" name="reviewed_by" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_reversed" class="form-check-input" id="isReversed" value="1">
                            <label class="form-check-label" for="isReversed">{{ __('Reversal of Previous Impairment') }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-warning btn-lg"><i class="fas fa-save me-2"></i>{{ __('Save Impairment') }}</button>
            <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
