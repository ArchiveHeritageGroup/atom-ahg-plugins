@extends('layouts.page')

@section('title', __('Add Valuation'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-1"><i class="fas fa-calculator me-2"></i>{{ __('Add Valuation') }}</h1>
            <p class="text-muted">{{ $asset->object_identifier ?? '' }} - {{ $asset->object_title ?? 'Untitled' }}</p>
        </div>
    </div>

    @if (isset($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <form method="post">
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">{{ __('Valuation Details') }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Valuation Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="valuation_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('New Value') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="new_value" class="form-control" value="{{ $asset->current_carrying_amount }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Valuation Method') }}</label>
                        <select name="valuation_method" class="form-select">
                            <option value="">{{ __('-- Select --') }}</option>
                            <option value="market">{{ __('Market Value') }}</option>
                            <option value="cost">{{ __('Cost Approach') }}</option>
                            <option value="income">{{ __('Income Approach') }}</option>
                            <option value="expert">{{ __('Expert Opinion') }}</option>
                            <option value="insurance">{{ __('Insurance Valuation') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Valuer Name') }}</label>
                        <input type="text" name="valuer_name" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Valuer Credentials') }}</label>
                        <input type="text" name="valuer_credentials" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i>{{ __('Save Valuation') }}</button>
            <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
